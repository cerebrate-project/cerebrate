#!/usr/bin/env python3
"""
CI bootstrap helper for Cerebrate ACL tests.

After migrations and the first request (which triggers checkForNewInstance()),
this script:
  1. Logs in as the default admin (admin / Password1234)
  2. Creates an API auth key for the admin
  3. Ensures the required roles exist (regular, org_admin, community_admin)
  4. Prints the auth key to stdout for use by the test suite

Usage:
    python tests/ci_setup.py --url http://localhost:8765
"""

import argparse
import json
import sys

import requests


def main() -> None:
    parser = argparse.ArgumentParser(description="CI bootstrap for Cerebrate tests")
    parser.add_argument("--url", required=True, help="Base URL of the Cerebrate instance")
    args = parser.parse_args()

    base = args.url.rstrip("/")
    s = requests.Session()
    s.headers.update({"Accept": "application/json", "Content-Type": "application/json"})

    # --- Step 1: trigger checkForNewInstance by hitting any endpoint ----------
    print("Triggering instance bootstrap…", file=sys.stderr)
    r = s.get(f"{base}/users/login")
    if r.status_code not in (200, 302, 401, 403):
        print(f"Unexpected status from /users/login: {r.status_code}", file=sys.stderr)

    # --- Step 2: log in with default admin credentials -----------------------
    print("Logging in as admin…", file=sys.stderr)
    r = s.post(f"{base}/users/login", data=json.dumps({
        "username": "admin",
        "password": "Password1234",
    }))
    if r.status_code not in (200, 302):
        print(f"Login failed: HTTP {r.status_code}\n{r.text[:500]}", file=sys.stderr)
        sys.exit(1)

    # --- Step 3: find the admin user id --------------------------------------
    print("Finding admin user…", file=sys.stderr)
    r = s.get(f"{base}/users/view/me.json")
    if r.status_code != 200:
        print(f"Could not fetch admin profile: HTTP {r.status_code}\n{r.text[:500]}", file=sys.stderr)
        sys.exit(1)
    admin_id = r.json().get("id")
    if not admin_id:
        print(f"No 'id' in /users/view/me response: {r.text[:500]}", file=sys.stderr)
        sys.exit(1)

    # --- Step 4: create an auth key for the admin ----------------------------
    print("Creating auth key…", file=sys.stderr)
    r = s.post(f"{base}/auth-keys/add?Users_id={admin_id}", data=json.dumps({
        "comment": "CI test key",
    }))
    if r.status_code != 200 or "id" not in r.json():
        print(f"Auth key creation failed: HTTP {r.status_code}\n{r.text[:500]}", file=sys.stderr)
        sys.exit(1)
    authkey = r.json().get("authkey_raw")
    if not authkey:
        print(f"No authkey_raw in response: {r.text[:500]}", file=sys.stderr)
        sys.exit(1)

    # From here on, use the auth key directly
    api = requests.Session()
    api.headers.update({
        "Authorization": authkey,
        "Accept": "application/json",
        "Content-Type": "application/json",
    })

    # --- Step 5: ensure the required roles exist -----------------------------
    print("Checking roles…", file=sys.stderr)
    r = api.get(f"{base}/roles/index.json")
    if r.status_code != 200:
        print(f"Could not list roles: HTTP {r.status_code}\n{r.text[:500]}", file=sys.stderr)
        sys.exit(1)
    existing = r.json()

    def has_role(pred):
        return any(pred(ro) for ro in existing)

    roles_to_create = []

    # Regular role (no admin perms at all)
    if not has_role(lambda ro: (
        not ro.get("perm_admin") and not ro.get("perm_community_admin")
        and not ro.get("perm_org_admin") and not ro.get("perm_group_admin")
    )):
        roles_to_create.append({
            "name": "user",
            "perm_admin": False,
            "perm_community_admin": False,
            "perm_org_admin": False,
            "perm_group_admin": False,
            "perm_sync": False,
            "perm_meta_field_editor": False,
            "is_default": True,
        })

    # Org admin role
    if not has_role(lambda ro: (
        not ro.get("perm_admin") and not ro.get("perm_community_admin")
        and ro.get("perm_org_admin") and not ro.get("perm_group_admin")
    )):
        roles_to_create.append({
            "name": "org_admin",
            "perm_admin": False,
            "perm_community_admin": False,
            "perm_org_admin": True,
            "perm_group_admin": False,
            "perm_sync": False,
            "perm_meta_field_editor": False,
        })

    for role in roles_to_create:
        print(f"  Creating role '{role['name']}'…", file=sys.stderr)
        r = api.post(f"{base}/roles/add", data=json.dumps(role))
        if r.status_code != 200 or "id" not in r.json():
            print(f"  WARNING: role creation failed: HTTP {r.status_code}\n{r.text[:300]}", file=sys.stderr)

    # --- Done ----------------------------------------------------------------
    # Print the auth key to stdout (only thing on stdout, for easy capture)
    print(authkey)
    print("Setup complete.", file=sys.stderr)


if __name__ == "__main__":
    main()
