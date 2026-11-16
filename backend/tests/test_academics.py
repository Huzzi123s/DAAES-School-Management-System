import unittest

from tests.base import API, BaseTestCase


class TestClasses(BaseTestCase):

    def test_public_classes_from_seed(self):
        res = self.client.get(f"{API}/academics/classes/all")
        self.assertEqual(res.status_code, 200)
        names = [c["name"] for c in res.get_json()["data"]]
        self.assertEqual(len(names), 12)
        self.assertEqual(names[0], "Playgroup")

    def test_filter_by_level(self):
        data = self.client.get(f"{API}/academics/classes/all?level=secondary").get_json()["data"]
        self.assertEqual([c["name"] for c in data], ["Class 9", "Class 10 (Matric)"])

    def test_create_requires_token(self):
        res = self.client.post(f"{API}/academics/classes/create", json={"name": "Class 11", "level": "secondary"})
        self.assertEqual(res.status_code, 401)

    def test_create_class(self):
        res = self.client.post(f"{API}/academics/classes/create", headers=self.staff_headers(),
                               json={"name": "  Class 11  ", "level": "secondary"})
        self.assertEqual(res.status_code, 200)
        self.assertEqual(res.get_json()["data"]["name"], "Class 11")

    def test_create_invalid_level_rejected_by_payload(self):
        res = self.client.post(f"{API}/academics/classes/create", headers=self.staff_headers(),
                               json={"name": "Class 12", "level": "college"})
        self.assertEqual(res.status_code, 400)

    def test_create_duplicate_name(self):
        res = self.client.post(f"{API}/academics/classes/create", headers=self.staff_headers(),
                               json={"name": "class 1", "level": "primary"})
        self.assertEqual(res.status_code, 409)

    def test_inactive_class_hidden_from_public(self):
        h = self.staff_headers()
        class_id = self.client.post(f"{API}/academics/classes/create", headers=h,
                                    json={"name": "Class 11", "level": "secondary"}).get_json()["data"]["id"]

        res = self.client.put(f"{API}/academics/classes/update/{class_id}", headers=h,
                              json={"name": "Class 11", "level": "secondary", "status": "inactive"})
        self.assertEqual(res.status_code, 200)

        public = [c["name"] for c in self.client.get(f"{API}/academics/classes/all").get_json()["data"]]
        self.assertNotIn("Class 11", public)

        admin = self.client.get(f"{API}/academics/classes/admin/all", headers=h).get_json()["data"]
        self.assertIn("Class 11", [c["name"] for c in admin])

    def test_update_missing_class(self):
        res = self.client.put(f"{API}/academics/classes/update/9999", headers=self.staff_headers(),
                              json={"name": "X", "level": "primary"})
        self.assertEqual(res.status_code, 404)

    def test_only_admin_can_delete(self):
        class_id = self.client.get(f"{API}/academics/classes/all").get_json()["data"][0]["id"]

        staff = self.client.delete(f"{API}/academics/classes/delete/{class_id}", headers=self.staff_headers())
        self.assertEqual(staff.status_code, 403)

        admin = self.client.delete(f"{API}/academics/classes/delete/{class_id}", headers=self.admin_headers())
        self.assertEqual(admin.status_code, 200)
        self.assertEqual(self.client.get(f"{API}/academics/classes/{class_id}").status_code, 404)


class TestSubjects(BaseTestCase):

    def test_public_subjects_from_seed(self):
        data = self.client.get(f"{API}/academics/subjects/all").get_json()["data"]
        self.assertEqual(len(data), 12)
        self.assertEqual(data[0]["name"], "English")

    def test_subject_crud(self):
        h = self.staff_headers()
        res = self.client.post(f"{API}/academics/subjects/create", headers=h, json={"name": "Biology", "icon": "🧬"})
        self.assertEqual(res.status_code, 200)
        subject_id = res.get_json()["data"]["id"]

        upd = self.client.put(f"{API}/academics/subjects/update/{subject_id}", headers=h,
                              json={"name": "Biology", "description": "Study of living things"})
        self.assertEqual(upd.status_code, 200)
        self.assertEqual(upd.get_json()["data"]["description"], "Study of living things")

        delete = self.client.delete(f"{API}/academics/subjects/delete/{subject_id}", headers=self.admin_headers())
        self.assertEqual(delete.status_code, 200)

    def test_empty_name_rejected(self):
        res = self.client.post(f"{API}/academics/subjects/create", headers=self.staff_headers(), json={"name": "   "})
        self.assertEqual(res.status_code, 400)


class TestPrograms(BaseTestCase):

    def test_public_programs_from_seed(self):
        data = self.client.get(f"{API}/academics/programs/all").get_json()["data"]
        self.assertEqual([p["title"] for p in data],
                         ["Primary Education", "Middle Education", "Secondary Education"])

    def test_program_crud(self):
        h = self.staff_headers()
        res = self.client.post(f"{API}/academics/programs/create", headers=h,
                               json={"title": "Early Years", "level": "pre_primary", "classes_covered": "Playgroup - Nursery"})
        self.assertEqual(res.status_code, 200)
        program_id = res.get_json()["data"]["id"]

        dup = self.client.post(f"{API}/academics/programs/create", headers=h,
                               json={"title": "early years", "level": "pre_primary"})
        self.assertEqual(dup.status_code, 409)

        delete = self.client.delete(f"{API}/academics/programs/delete/{program_id}", headers=self.admin_headers())
        self.assertEqual(delete.status_code, 200)

        again = self.client.delete(f"{API}/academics/programs/delete/{program_id}", headers=self.admin_headers())
        self.assertEqual(again.status_code, 400)


if __name__ == "__main__":
    unittest.main()
