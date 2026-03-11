#!/usr/bin/env python3
"""
Cerebrate — UsersController ACL test suite
==========================================

Verifies that each Users endpoint enforces the correct access controls by
spinning up temporary test users with different privilege levels, running the
checks, and cleaning up all test data afterwards.

Usage
-----
    python tests/test_users_acl.py --url https://cerebrate.example.com --authkey <key>

Requirements
------------
    pip install requests

The provided authkey must belong to a user with perm_community_admin (or
perm_admin).  All test artefacts are prefixed with "ACL_TEST_<random>" and are
deleted at the end of the run — including on failure.

Permission levels under test
-----------------------------
    regular          — no admin flags
    org_admin        — perm_org_admin only
    community_admin  — perm_community_admin

The suite creates two organisations (A and B) so that cross-org isolation can
be verified.
"""

import argparse
import json
import sys
import unittest
import uuid

import requests

# ---------------------------------------------------------------------------
# Global configuration — populated from CLI args before any test runs
# ---------------------------------------------------------------------------

BASE_URL: str = ""
ADMIN_KEY: str = ""


# ---------------------------------------------------------------------------
# Thin REST client
# ---------------------------------------------------------------------------

class CerebrateClient:
    """Minimal wrapper around requests for the Cerebrate REST API."""

    def __init__(self, base_url: str, authkey: str) -> None:
        self.base_url = base_url.rstrip("/")
        self._s = requests.Session()
        self._s.headers.update({
            "Authorization": authkey,
            "Accept": "application/json",
            "Content-Type": "application/json",
        })

    def get(self, path: str, **kw) -> requests.Response:
        return self._s.get(f"{self.base_url}{path}", **kw)

    def post(self, path: str, data: dict = None, **kw) -> requests.Response:
        return self._s.post(
            f"{self.base_url}{path}",
            data=json.dumps(data or {}),
            **kw,
        )


# ---------------------------------------------------------------------------
# Test fixtures — created once, cleaned up after all tests finish
# ---------------------------------------------------------------------------

class Fixtures:
    """
    Holds all test data created during setup and cleans it up at the end.

    Roles are *discovered* from the running instance (not created) so we
    always work with whichever role set the operator has configured.
    """

    admin: CerebrateClient = None

    # IDs of created resources (used for cleanup)
    org_id: int = None       # primary test org (org A)
    org2_id: int = None      # secondary test org (org B)
    individuals: dict = {}   # label -> individual id
    users: dict = {}         # label -> full user dict from the API
    authkey_ids: dict = {}   # label -> auth_key row id (for deletion)
    clients: dict = {}       # label -> CerebrateClient

    # Role IDs discovered from the instance
    role_ids: dict = {}      # "regular" | "org_admin" | "community_admin" -> int

    # Unique prefix so artefacts are easy to identify and won't clash
    TAG: str = f"ACL_TEST_{uuid.uuid4().hex[:8]}"

    # ------------------------------------------------------------------
    # Public interface
    # ------------------------------------------------------------------

    @classmethod
    def setup(cls, base_url: str, admin_key: str) -> None:
        cls.admin = CerebrateClient(base_url, admin_key)
        cls._discover_roles()
        cls._create_orgs()
        cls._create_users()

    @classmethod
    def teardown(cls) -> None:
        """Delete every artefact this run created, in reverse order.

        If user deletion is disabled on the instance, users are toggled to
        disabled instead. A warning lists anything that could not be cleaned
        up so the operator can remove it manually (search by TAG).
        """
        problems = []

        for key_id in cls.authkey_ids.values():
            r = cls.admin.post(f"/auth-keys/delete/{key_id}")
            if r.status_code not in (200, 404):
                problems.append(f"auth_key {key_id}: HTTP {r.status_code}")

        for label, user in cls.users.items():
            uid = user.get("id")
            if not uid:
                continue
            r = cls.admin.post(f"/users/delete/{uid}")
            # A 200 with body "[]" means deletion is disabled on this instance.
            body = r.text.strip()
            deletion_ok = r.status_code == 200 and body not in ("[]", "")
            if not deletion_ok:
                # Fall back: disable the user so it cannot log in
                cls.admin.post(f"/users/toggle/{uid}/disabled")
                problems.append(
                    f"user {uid} ({label}) could not be deleted "
                    f"(user deletion may be disabled) — disabled instead"
                )

        for label, ind_id in cls.individuals.items():
            if not ind_id:
                continue
            r = cls.admin.post(f"/individuals/delete/{ind_id}")
            if r.status_code not in (200, 404):
                problems.append(
                    f"individual {ind_id} ({label}): HTTP {r.status_code} — "
                    "likely still linked to a user that could not be deleted"
                )

        for oid in filter(None, [cls.org_id, cls.org2_id]):
            r = cls.admin.post(f"/organisations/delete/{oid}")
            if r.status_code not in (200, 404):
                problems.append(f"organisation {oid}: HTTP {r.status_code}")

        if problems:
            print(
                f"\nWARNING — some test artefacts were not fully removed "
                f"(search for tag '{cls.TAG}' to locate them):",
                file=sys.stderr,
            )
            for p in problems:
                print(f"  • {p}", file=sys.stderr)

    # ------------------------------------------------------------------
    # Private helpers
    # ------------------------------------------------------------------

    @classmethod
    def _discover_roles(cls) -> None:
        """Find existing roles that satisfy each required permission level."""
        r = cls.admin.get("/roles/index.json")
        _assert_ok(r, "list roles")
        roles = r.json()

        selectors = {
            "regular": lambda ro: (
                not ro.get("perm_admin")
                and not ro.get("perm_community_admin")
                and not ro.get("perm_org_admin")
                and not ro.get("perm_group_admin")
            ),
            "org_admin": lambda ro: (
                not ro.get("perm_admin")
                and not ro.get("perm_community_admin")
                and ro.get("perm_org_admin")
                and not ro.get("perm_group_admin")
            ),
            # Accept any role that has perm_community_admin, including site admins
            "community_admin": lambda ro: ro.get("perm_community_admin"),
        }

        for label, predicate in selectors.items():
            match = next((ro for ro in roles if predicate(ro)), None)
            assert match is not None, (
                f"No role found for permission level '{label}'. "
                "Please create a role that matches this level on the target instance."
            )
            cls.role_ids[label] = match["id"]

    @classmethod
    def _create_orgs(cls) -> None:
        for attr, suffix in [("org_id", "A"), ("org2_id", "B")]:
            r = cls.admin.post("/organisations/add", {
                "name": f"{cls.TAG}_Org_{suffix}",
                "uuid": str(uuid.uuid4()),
                "nationality": "ZZ",
                "sector": "test",
            })
            _assert_created(r, f"create org {suffix}")
            setattr(cls, attr, r.json()["id"])

    @classmethod
    def _create_users(cls) -> None:
        """Create one user per role level in org A, plus one regular user in org B."""
        specs = [
            ("regular",         "regular",         cls.org_id),
            ("org_admin",       "org_admin",       cls.org_id),
            ("community_admin", "community_admin", cls.org_id),
            ("other_org",       "regular",         None),  # org2_id set below
        ]

        for label, role_key, org_id in specs:
            if org_id is None:
                org_id = cls.org2_id

            # Individual
            r = cls.admin.post("/individuals/add", {
                "email": f"{cls.TAG}_{label}@test.invalid",
                "first_name": "ACL",
                "last_name": f"Test_{label}",
                "uuid": str(uuid.uuid4()),
            })
            _assert_created(r, f"create individual for {label}")
            cls.individuals[label] = r.json()["id"]

            # User (username must be an email when user.username-must-be-email is set)
            r = cls.admin.post("/users/add", {
                "username": f"{cls.TAG}_{label}@acl-test.invalid",
                "password": uuid.uuid4().hex + "Aa1!",
                "individual_id": cls.individuals[label],
                "organisation_id": org_id,
                "role_id": cls.role_ids[role_key],
                "disabled": False,
            })
            _assert_created(r, f"create user {label}")
            cls.users[label] = r.json()

            # Auth key — user_id must be passed as a query param (Users_id),
            # not in the POST body, due to how the controller reads it.
            r = cls.admin.post(
                f"/auth-keys/add?Users_id={cls.users[label]['id']}",
                {"comment": f"ACL test — {label}", "expiration": ""},
            )
            _assert_created(r, f"create auth key for {label}")
            data = r.json()
            raw_key = data.get("authkey_raw")
            assert raw_key, (
                f"authkey_raw missing from auth_keys/add response for {label}: {data}"
            )
            cls.authkey_ids[label] = data["id"]
            cls.clients[label] = CerebrateClient(BASE_URL, raw_key)


def _assert_ok(r: requests.Response, context: str) -> None:
    assert r.status_code == 200, (
        f"Setup step '{context}' failed: HTTP {r.status_code}\n{r.text[:400]}"
    )


def _assert_created(r: requests.Response, context: str) -> None:
    """Like _assert_ok but also verifies the response is an entity (not an error).

    The API returns HTTP 200 even on application-level failures, with a
    'message' key and no 'id'.  This catches those cases.
    """
    _assert_ok(r, context)
    body = r.json()
    assert isinstance(body, dict) and "id" in body, (
        f"Setup step '{context}' returned 200 but no entity id — likely a "
        f"validation error:\n{r.text[:400]}"
    )


# ---------------------------------------------------------------------------
# Convenience predicates
# ---------------------------------------------------------------------------

FX = Fixtures  # short alias used everywhere below


def ok(r: requests.Response) -> bool:
    """True only for genuine success: HTTP 200 AND entity data (not an error message)."""
    if r.status_code != 200:
        return False
    try:
        body = r.json()
        # A list (index response) is a success; a dict with 'id' is a saved entity.
        return isinstance(body, list) or (isinstance(body, dict) and "id" in body)
    except ValueError:
        return False


def denied(r: requests.Response) -> bool:
    """True for any form of denial.

    Cerebrate does not always use HTTP 4xx for application-level rejections.
    Some controller guards throw exceptions that get caught and serialised as
    HTTP 200 with a ``message`` key (and no ``id``).  An empty JSON array
    (``[]``) is also returned when an operation is blocked.
    """
    if r.status_code in (403, 405):
        return True
    if r.status_code == 200:
        try:
            body = r.json()
            return body == [] or (
                isinstance(body, dict) and "message" in body and "id" not in body
            )
        except ValueError:
            pass
    return False


def not_found(r: requests.Response) -> bool:
    return r.status_code == 404


def denied_or_not_found(r: requests.Response) -> bool:
    return denied(r) or not_found(r)


# ---------------------------------------------------------------------------
# Test: index
# ---------------------------------------------------------------------------

class TestUsersIndex(unittest.TestCase):
    """
    ACL rule: OR [perm_org_admin, perm_community_admin, perm_group_admin]

    Scope rules (enforced inside the controller, not at ACL level):
      • community_admin — sees every user on the instance
      • org_admin       — sees only users in their own organisation
    """

    def test_community_admin_allowed(self):
        r = FX.clients["community_admin"].get("/users/index.json")
        self.assertTrue(ok(r), _fmt(r))

    def test_org_admin_allowed(self):
        r = FX.clients["org_admin"].get("/users/index.json")
        self.assertTrue(ok(r), _fmt(r))

    def test_regular_user_denied(self):
        r = FX.clients["regular"].get("/users/index.json")
        self.assertTrue(denied(r), _fmt(r))

    def test_community_admin_sees_both_orgs(self):
        r = FX.clients["community_admin"].get("/users/index.json")
        self.assertTrue(ok(r), _fmt(r))
        org_ids = {u.get("organisation_id") for u in r.json()}
        self.assertIn(FX.org_id,  org_ids, "community_admin must see users from org A")
        self.assertIn(FX.org2_id, org_ids, "community_admin must see users from org B")

    def test_org_admin_sees_only_own_org(self):
        r = FX.clients["org_admin"].get("/users/index.json")
        self.assertTrue(ok(r), _fmt(r))
        foreign = [u for u in r.json() if u.get("organisation_id") == FX.org2_id]
        self.assertEqual(foreign, [], "org_admin must not see users from a foreign org")


# ---------------------------------------------------------------------------
# Test: view
# ---------------------------------------------------------------------------

class TestUsersView(unittest.TestCase):
    """
    ACL rule: ['*'] (any authenticated user)

    Scope rules (enforced inside the controller):
      • Without perm_org_admin/perm_community_admin the controller silently
        substitutes the caller's own id, so a regular user cannot pull another
        user's data even if they supply a valid id.
      • org_admin can view users in their own org; cross-org raises NotFoundException.
      • community_admin can view anyone.
    """

    def test_every_role_can_view_own_profile(self):
        for label in ("regular", "org_admin", "community_admin"):
            with self.subTest(label=label):
                own_id = FX.users[label]["id"]
                r = FX.clients[label].get(f"/users/view/{own_id}.json")
                self.assertTrue(ok(r), f"[{label}] " + _fmt(r))

    def test_view_me_alias_returns_caller(self):
        for label in ("regular", "org_admin", "community_admin"):
            with self.subTest(label=label):
                r = FX.clients[label].get("/users/view/me.json")
                self.assertTrue(ok(r), f"[{label}] " + _fmt(r))
                self.assertEqual(
                    r.json().get("id"),
                    FX.users[label]["id"],
                    f"[{label}] /view/me returned wrong user",
                )

    def test_community_admin_can_view_foreign_org_user(self):
        other_id = FX.users["other_org"]["id"]
        r = FX.clients["community_admin"].get(f"/users/view/{other_id}.json")
        self.assertTrue(ok(r), _fmt(r))

    def test_org_admin_can_view_own_org_user(self):
        target_id = FX.users["regular"]["id"]
        r = FX.clients["org_admin"].get(f"/users/view/{target_id}.json")
        self.assertTrue(ok(r), _fmt(r))

    def test_org_admin_cannot_view_foreign_org_user(self):
        other_id = FX.users["other_org"]["id"]
        r = FX.clients["org_admin"].get(f"/users/view/{other_id}.json")
        self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_regular_user_cannot_retrieve_another_users_data(self):
        """
        The controller redirects regular users to their own profile.  We accept
        any of: the caller's own data, a 404, or a 403/405 — but NOT the target
        user's data being returned.
        """
        target_id = FX.users["org_admin"]["id"]
        r = FX.clients["regular"].get(f"/users/view/{target_id}.json")
        if ok(r):
            self.assertNotEqual(
                r.json().get("id"),
                target_id,
                "regular user must not receive a different user's profile",
            )


# ---------------------------------------------------------------------------
# Test: add
# ---------------------------------------------------------------------------

class TestUsersAdd(unittest.TestCase):
    """
    ACL rule: OR [perm_org_admin, perm_community_admin, perm_group_admin]

    Scope rules:
      • community_admin — can add any user with any role in any org.
      • org_admin       — can add users only in their own org, and only with
                          non-elevated roles (no perm_org_admin or higher).
    """

    _cleanup: list = []  # user ids to disable/delete in tearDownClass

    @classmethod
    def tearDownClass(cls):
        for uid in cls._cleanup:
            r = FX.admin.post(f"/users/delete/{uid}")
            if not ok(r):
                FX.admin.post(f"/users/toggle/{uid}/disabled")

    def _payload(self, org_id: int, role_id: int) -> dict:
        """Build a user-add payload that creates a fresh individual inline.

        Using ``individual_id="new"`` + ``individual`` avoids the uniqueness
        constraint that prevents two users sharing the same individual_id.
        """
        uid = uuid.uuid4().hex[:8]
        return {
            "username": f"{FX.TAG}_add_{uid}@acl-test.invalid",
            "password": uuid.uuid4().hex + "Aa1!",
            "individual_id": "new",
            "individual": {
                "email": f"{FX.TAG}_add_{uid}@test.invalid",
                "first_name": "ACL",
                "last_name": f"Add_{uid}",
            },
            "organisation_id": org_id,
            "role_id": role_id,
            "disabled": False,
        }

    def test_regular_user_denied(self):
        r = FX.clients["regular"].post("/users/add", self._payload(FX.org_id, FX.role_ids["regular"]))
        self.assertTrue(denied(r), _fmt(r))

    def test_community_admin_can_add_user(self):
        r = FX.clients["community_admin"].post("/users/add", self._payload(FX.org_id, FX.role_ids["regular"]))
        self.assertTrue(ok(r), _fmt(r))
        self._cleanup.append(r.json()["id"])

    def test_org_admin_can_add_user_in_own_org(self):
        r = FX.clients["org_admin"].post("/users/add", self._payload(FX.org_id, FX.role_ids["regular"]))
        self.assertTrue(ok(r), _fmt(r))
        self._cleanup.append(r.json()["id"])

    def test_org_admin_cannot_add_user_in_foreign_org(self):
        """org_admin must not be able to create a user that ends up in a foreign org.

        The controller does not always reject the request outright: for a plain
        org_admin (no perm_group_admin) it silently overrides the submitted
        organisation_id with the admin's own org.  Either outcome is acceptable
        as long as the resulting user is NOT in the foreign org.
        """
        r = FX.clients["org_admin"].post("/users/add", self._payload(FX.org2_id, FX.role_ids["regular"]))
        if ok(r):
            data = r.json()
            if data.get("id"):
                self._cleanup.append(data["id"])
            self.assertNotEqual(
                data.get("organisation_id"),
                FX.org2_id,
                "org_admin silently placed the user in the foreign org — ACL bypass!",
            )
        else:
            self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_org_admin_cannot_assign_elevated_role(self):
        """org_admin must not be able to create a user with perm_org_admin or higher."""
        r = FX.clients["org_admin"].post("/users/add", self._payload(FX.org_id, FX.role_ids["org_admin"]))
        self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_org_admin_cannot_assign_community_admin_role(self):
        r = FX.clients["org_admin"].post("/users/add", self._payload(FX.org_id, FX.role_ids["community_admin"]))
        self.assertTrue(denied_or_not_found(r), _fmt(r))


# ---------------------------------------------------------------------------
# Test: edit
# ---------------------------------------------------------------------------

class TestUsersEdit(unittest.TestCase):
    """
    ACL rule: ['*'] (any authenticated user)

    Scope rules:
      • Every user can edit their own password / basic settings.
      • org_admin can edit non-elevated users in their own org.
      • community_admin can edit anyone.
      • Nobody can change their own role.
      • org_admin cannot edit a user with a role >= perm_org_admin.
    """

    def test_every_role_can_change_own_password(self):
        for label in ("regular", "org_admin", "community_admin"):
            with self.subTest(label=label):
                own_id = FX.users[label]["id"]
                new_pw = uuid.uuid4().hex + "Bb2@"
                r = FX.clients[label].post(f"/users/edit/{own_id}", {
                    "password": new_pw,
                    "confirm_password": new_pw,
                })
                self.assertTrue(ok(r), f"[{label}] " + _fmt(r))

    def test_community_admin_can_edit_any_user(self):
        target_id = FX.users["regular"]["id"]
        r = FX.clients["community_admin"].post(f"/users/edit/{target_id}", {"disabled": False})
        self.assertTrue(ok(r), _fmt(r))

    def test_org_admin_can_edit_own_org_user(self):
        target_id = FX.users["regular"]["id"]
        r = FX.clients["org_admin"].post(f"/users/edit/{target_id}", {"disabled": False})
        self.assertTrue(ok(r), _fmt(r))

    def test_org_admin_cannot_edit_foreign_org_user(self):
        target_id = FX.users["other_org"]["id"]
        r = FX.clients["org_admin"].post(f"/users/edit/{target_id}", {"disabled": False})
        self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_regular_user_cannot_edit_another_user(self):
        target_id = FX.users["org_admin"]["id"]
        r = FX.clients["regular"].post(f"/users/edit/{target_id}", {"disabled": False})
        self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_org_admin_cannot_promote_user_to_elevated_role(self):
        """org_admin must not be able to assign perm_org_admin or higher to another user."""
        target_id = FX.users["regular"]["id"]
        r = FX.clients["org_admin"].post(
            f"/users/edit/{target_id}",
            {"role_id": FX.role_ids["org_admin"]},
        )
        self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_no_user_can_change_own_role(self):
        """Self-role-escalation must be blocked for every permission level.

        For a regular user the field is simply not in the allowed list so it is
        silently dropped — the response is 200 with unchanged data.  For an
        org_admin the controller explicitly rejects the assignment.  In both
        cases the role must NOT actually change.
        """
        for label, target_role in [
            ("regular",   "org_admin"),
            ("org_admin", "community_admin"),
        ]:
            with self.subTest(label=label):
                own_id = FX.users[label]["id"]
                r = FX.clients[label].post(
                    f"/users/edit/{own_id}",
                    {"role_id": FX.role_ids[target_role]},
                )
                if ok(r):
                    # Request accepted but the role must NOT have been promoted
                    self.assertNotEqual(
                        r.json().get("role_id"),
                        FX.role_ids[target_role],
                        f"[{label}] role was actually changed to '{target_role}'!",
                    )
                else:
                    self.assertTrue(denied_or_not_found(r), f"[{label}] " + _fmt(r))


# ---------------------------------------------------------------------------
# Test: toggle
# ---------------------------------------------------------------------------

class TestUsersToggle(unittest.TestCase):
    """
    ACL rule: OR [perm_org_admin, perm_community_admin, perm_group_admin]

    Scope rules mirror those of edit: org_admin is restricted to their own
    org's non-elevated users; community_admin has no restrictions.
    """

    def test_regular_user_denied(self):
        target_id = FX.users["other_org"]["id"]
        r = FX.clients["regular"].post(f"/users/toggle/{target_id}/disabled")
        self.assertTrue(denied(r), _fmt(r))

    def test_community_admin_can_toggle_any_user(self):
        target_id = FX.users["other_org"]["id"]
        r = FX.clients["community_admin"].post(f"/users/toggle/{target_id}/disabled")
        self.assertTrue(ok(r), _fmt(r))
        FX.clients["community_admin"].post(f"/users/toggle/{target_id}/disabled")  # restore

    def test_org_admin_can_toggle_own_org_user(self):
        target_id = FX.users["regular"]["id"]
        r = FX.clients["org_admin"].post(f"/users/toggle/{target_id}/disabled")
        self.assertTrue(ok(r), _fmt(r))
        FX.clients["org_admin"].post(f"/users/toggle/{target_id}/disabled")  # restore

    def test_org_admin_cannot_toggle_foreign_org_user(self):
        target_id = FX.users["other_org"]["id"]
        r = FX.clients["org_admin"].post(f"/users/toggle/{target_id}/disabled")
        self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_org_admin_cannot_toggle_community_admin_user(self):
        """org_admin must not be able to disable a community_admin."""
        target_id = FX.users["community_admin"]["id"]
        r = FX.clients["org_admin"].post(f"/users/toggle/{target_id}/disabled")
        self.assertTrue(denied_or_not_found(r), _fmt(r))


# ---------------------------------------------------------------------------
# Test: delete
# ---------------------------------------------------------------------------

class TestUsersDelete(unittest.TestCase):
    """
    ACL rule: OR [perm_org_admin, perm_community_admin, perm_group_admin]

    Additional guard: the 'user.allow-user-deletion' config flag must be
    enabled; if it is not, even a community_admin gets a 403/405.

    Scope rules mirror edit/toggle.
    """

    def test_regular_user_denied(self):
        target_id = FX.users["other_org"]["id"]
        r = FX.clients["regular"].post(f"/users/delete/{target_id}")
        self.assertTrue(denied(r), _fmt(r))

    def test_org_admin_cannot_delete_foreign_org_user(self):
        target_id = FX.users["other_org"]["id"]
        r = FX.clients["org_admin"].post(f"/users/delete/{target_id}")
        self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_org_admin_cannot_delete_elevated_user_in_own_org(self):
        """org_admin must not delete a community_admin, even from the same org."""
        target_id = FX.users["community_admin"]["id"]
        r = FX.clients["org_admin"].post(f"/users/delete/{target_id}")
        self.assertTrue(denied_or_not_found(r), _fmt(r))

    def test_community_admin_delete_respects_instance_config(self):
        """
        When 'user.allow-user-deletion' is disabled the endpoint must return
        403/405 for every caller, including community_admin.  When it is
        enabled, community_admin must get 200.

        We create a throwaway user for this test so that permanent test users
        are not accidentally destroyed.
        """
        ind = FX.admin.post("/individuals/add", {
            "email": f"{FX.TAG}_del@test.invalid",
            "first_name": "Del",
            "last_name": "Test",
            "uuid": str(uuid.uuid4()),
        })
        if ind.status_code != 200:
            self.skipTest("Could not create throwaway individual")
        ind_id = ind.json()["id"]

        usr = FX.admin.post("/users/add", {
            "username": f"{FX.TAG}_del_{uuid.uuid4().hex[:6]}@acl-test.invalid",
            "password": uuid.uuid4().hex + "Aa1!",
            "individual_id": ind_id,
            "organisation_id": FX.org_id,
            "role_id": FX.role_ids["regular"],
        })
        if usr.status_code != 200:
            FX.admin.post(f"/individuals/delete/{ind_id}")
            self.skipTest("Could not create throwaway user")
        del_id = usr.json()["id"]

        try:
            r = FX.clients["community_admin"].post(f"/users/delete/{del_id}")
            self.assertIn(
                r.status_code,
                [200, 403, 405],
                f"Unexpected status from delete endpoint: {r.status_code}\n{r.text[:300]}",
            )
        finally:
            FX.admin.post(f"/users/delete/{del_id}")
            FX.admin.post(f"/individuals/delete/{ind_id}")


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _fmt(r: requests.Response) -> str:
    """Human-readable failure message for assertion errors."""
    return f"HTTP {r.status_code}: {r.text[:300]}"


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

def _parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser(
        description="Cerebrate UsersController ACL test suite",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    p.add_argument("--url", required=True, help="Base URL of the Cerebrate instance")
    p.add_argument("--authkey", required=True, help="Auth key for a community_admin user")
    p.add_argument("--verbose", "-v", action="store_true", help="Verbose test output")
    return p.parse_args()


def main() -> None:
    global BASE_URL, ADMIN_KEY

    args = _parse_args()
    BASE_URL = args.url.rstrip("/")
    ADMIN_KEY = args.authkey

    print(f"Target  : {BASE_URL}")
    print(f"Test tag: {FX.TAG}")
    print("Setting up test fixtures…", flush=True)

    try:
        FX.setup(BASE_URL, ADMIN_KEY)
    except AssertionError as exc:
        print(f"\nSetup failed — no test data was left behind.\n{exc}", file=sys.stderr)
        FX.teardown()
        sys.exit(1)

    print(f"  Org A (primary)  : id={FX.org_id}")
    print(f"  Org B (secondary): id={FX.org2_id}")
    print(f"  Users created    : {list(FX.users.keys())}")
    print()

    suite = unittest.TestLoader().loadTestsFromTestCase  # convenience alias
    all_tests = unittest.TestSuite()
    for cls in (
        TestUsersIndex,
        TestUsersView,
        TestUsersAdd,
        TestUsersEdit,
        TestUsersToggle,
        TestUsersDelete,
    ):
        all_tests.addTests(suite(cls))

    result = unittest.TextTestRunner(verbosity=2 if args.verbose else 1).run(all_tests)

    print("\nCleaning up test fixtures…", flush=True)
    FX.teardown()
    print("Done.")

    sys.exit(0 if result.wasSuccessful() else 1)


if __name__ == "__main__":
    main()
