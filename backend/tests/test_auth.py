import unittest

from tests.base import ADMIN, API, STAFF, BaseTestCase


class TestAuth(BaseTestCase):

    def test_login_success(self):
        res = self.client.post(f"{API}/auth/login", json=ADMIN)
        self.assertEqual(res.status_code, 200)
        body = res.get_json()
        self.assertTrue(body["success"])
        self.assertIn("access_token", body["data"])
        self.assertEqual(body["data"]["user"]["role"], "admin")

    def test_login_wrong_password(self):
        res = self.client.post(f"{API}/auth/login", json={"email": ADMIN["email"], "password": "wrong"})
        self.assertEqual(res.status_code, 401)

    def test_login_unknown_email(self):
        res = self.client.post(f"{API}/auth/login", json={"email": "nobody@daaes.test", "password": "x"})
        self.assertEqual(res.status_code, 401)

    def test_login_missing_password_rejected_by_payload(self):
        res = self.client.post(f"{API}/auth/login", json={"email": ADMIN["email"]})
        self.assertEqual(res.status_code, 400)

    def test_me_requires_token(self):
        self.assertEqual(self.client.get(f"{API}/auth/me").status_code, 401)
        bad = self.client.get(f"{API}/auth/me", headers={"Authorization": "Bearer invalid"})
        self.assertEqual(bad.status_code, 401)

    def test_me_returns_profile(self):
        res = self.client.get(f"{API}/auth/me", headers=self.admin_headers())
        self.assertEqual(res.status_code, 200)
        self.assertEqual(res.get_json()["data"]["email"], ADMIN["email"])
        self.assertNotIn("password_hash", res.get_json()["data"])

    def test_update_profile(self):
        res = self.client.put(f"{API}/auth/profile/update", headers=self.staff_headers(),
                              json={"name": "New Staff Name"})
        self.assertEqual(res.status_code, 200)
        self.assertEqual(res.get_json()["data"]["name"], "New Staff Name")

    def test_change_password(self):
        res = self.client.put(f"{API}/auth/change-password", headers=self.admin_headers(),
                              json={"current_password": ADMIN["password"], "new_password": "NewPass12345"})
        self.assertEqual(res.status_code, 200)
        new_login = self.client.post(f"{API}/auth/login",
                                     json={"email": ADMIN["email"], "password": "NewPass12345"})
        self.assertEqual(new_login.status_code, 200)

    def test_change_password_wrong_current(self):
        res = self.client.put(f"{API}/auth/change-password", headers=self.admin_headers(),
                              json={"current_password": "wrong-pass", "new_password": "NewPass12345"})
        self.assertEqual(res.status_code, 400)


class TestUsers(BaseTestCase):

    def test_staff_cannot_manage_users(self):
        res = self.client.get(f"{API}/users/all", headers=self.staff_headers())
        self.assertEqual(res.status_code, 403)

    def test_create_update_delete_user(self):
        h = self.admin_headers()

        res = self.client.post(f"{API}/users/create", headers=h,
                               json={"name": "Teacher", "email": "Teacher@DAAES.test", "password": "Teacher123"})
        self.assertEqual(res.status_code, 200)
        user = res.get_json()["data"]
        self.assertEqual(user["email"], "teacher@daaes.test")
        self.assertEqual(user["role"], "staff")

        upd = self.client.put(f"{API}/users/update/{user['id']}", headers=h, json={
            "name": "Teacher", "email": "teacher@daaes.test", "role": "staff", "status": "inactive"
        })
        self.assertEqual(upd.status_code, 200)

        # inactive user cannot log in
        login = self.client.post(f"{API}/auth/login",
                                 json={"email": "teacher@daaes.test", "password": "Teacher123"})
        self.assertEqual(login.status_code, 403)

        self.assertEqual(self.client.delete(f"{API}/users/delete/{user['id']}", headers=h).status_code, 200)
        self.assertEqual(self.client.get(f"{API}/users/{user['id']}", headers=h).status_code, 404)

    def test_duplicate_email(self):
        res = self.client.post(f"{API}/users/create", headers=self.admin_headers(),
                               json={"name": "Copy", "email": STAFF["email"].upper(), "password": "Password123"})
        self.assertEqual(res.status_code, 409)

    def test_invalid_email(self):
        res = self.client.post(f"{API}/users/create", headers=self.admin_headers(),
                               json={"name": "Bad", "email": "not-an-email", "password": "Password123"})
        self.assertEqual(res.status_code, 400)

    def test_admin_cannot_delete_self(self):
        h = self.admin_headers()
        me = self.client.get(f"{API}/auth/me", headers=h).get_json()["data"]
        self.assertEqual(self.client.delete(f"{API}/users/delete/{me['id']}", headers=h).status_code, 400)

    def test_last_admin_cannot_be_demoted(self):
        h = self.admin_headers()
        me = self.client.get(f"{API}/auth/me", headers=h).get_json()["data"]
        res = self.client.put(f"{API}/users/update/{me['id']}", headers=h, json={
            "name": "Admin", "email": ADMIN["email"], "role": "staff", "status": "active"
        })
        self.assertEqual(res.status_code, 400)


if __name__ == "__main__":
    unittest.main()
