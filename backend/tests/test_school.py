import unittest

from tests.base import API, BaseTestCase


class TestSchoolInfo(BaseTestCase):

    def test_get_school_info(self):
        res = self.client.get(f"{API}/school/info")
        self.assertEqual(res.status_code, 200)
        data = res.get_json()["data"]
        self.assertEqual(data["name"], "DAAES School")
        self.assertTrue(data["vision"])
        self.assertTrue(data["mission"])

    def test_update_school_info(self):
        res = self.client.put(f"{API}/school/info/update", headers=self.staff_headers(),
                              json={"phone": "+92 300 1234567", "email": "info@daaes.edu.pk"})
        self.assertEqual(res.status_code, 200)
        data = res.get_json()["data"]
        self.assertEqual(data["phone"], "+92 300 1234567")
        self.assertEqual(data["email"], "info@daaes.edu.pk")
        # fields not sent stay the same
        self.assertEqual(data["name"], "DAAES School")

    def test_update_requires_token(self):
        res = self.client.put(f"{API}/school/info/update", json={"phone": "03001234567"})
        self.assertEqual(res.status_code, 401)

    def test_invalid_phone_and_email(self):
        h = self.staff_headers()
        self.assertEqual(self.client.put(f"{API}/school/info/update", headers=h, json={"phone": "abc"}).status_code, 400)
        self.assertEqual(self.client.put(f"{API}/school/info/update", headers=h, json={"email": "bad"}).status_code, 400)

    def test_empty_name_rejected(self):
        res = self.client.put(f"{API}/school/info/update", headers=self.staff_headers(), json={"name": "  "})
        self.assertEqual(res.status_code, 400)


class TestAdmissionDocuments(BaseTestCase):

    def test_public_documents_from_seed(self):
        data = self.client.get(f"{API}/school/documents/all").get_json()["data"]
        self.assertEqual(len(data), 5)
        self.assertIn("Child's B-Form", [d["name"] for d in data])

    def test_document_crud(self):
        h = self.staff_headers()
        res = self.client.post(f"{API}/school/documents/create", headers=h,
                               json={"name": "Previous School Result", "is_required": False})
        self.assertEqual(res.status_code, 200)
        doc = res.get_json()["data"]
        self.assertFalse(doc["is_required"])

        upd = self.client.put(f"{API}/school/documents/update/{doc['id']}", headers=h,
                              json={"name": "Previous School Result", "status": "inactive"})
        self.assertEqual(upd.status_code, 200)
        public = [d["name"] for d in self.client.get(f"{API}/school/documents/all").get_json()["data"]]
        self.assertNotIn("Previous School Result", public)

        delete = self.client.delete(f"{API}/school/documents/delete/{doc['id']}", headers=self.admin_headers())
        self.assertEqual(delete.status_code, 200)

    def test_duplicate_document(self):
        res = self.client.post(f"{API}/school/documents/create", headers=self.staff_headers(),
                               json={"name": "child's b-form"})
        self.assertEqual(res.status_code, 409)


if __name__ == "__main__":
    unittest.main()
