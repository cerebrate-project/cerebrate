#!/usr/bin/env python3
"""
CI bootstrap helper for Cerebrate ACL tests.

Given a base URL and an auth key (created via CLI), this script ensures
the required roles exist (regular, org_admin) for the ACL test suite.

Usage:
    python tests/ci_setup.py --url http://localhost:8765 --authkey <key>
"""

import argparse
import json
import sys

import requests


def main() -> None:
    parser = argparse.ArgumentParser(description="CI bootstrap for Cerebrate tests")
    parser.add_argument("--url", required=True, help="Base URL of the Cerebrate instance")
    parser.add_argument("--authkey", required=True, help="Admin auth key")
    args = parser.parse_args()

    base = args.url.rstrip("/")
    api = requests.Session()
    api.headers.update({
        "Authorization": args.authkey,
        "Accept": "application/json",
        "Content-Type": "application/json",
    })

    # --- Ensure the required roles exist -------------------------------------
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
            print(f"  FAILED: HTTP {r.status_code}\n{r.text[:300]}", file=sys.stderr)
            sys.exit(1)

    print("Setup complete.", file=sys.stderr)


if __name__ == "__main__":
    main()
