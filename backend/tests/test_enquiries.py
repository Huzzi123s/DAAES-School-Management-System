import unittest

from tests.base import API, BaseTestCase

VALID_ENQUIRY = {
    "name": "Ahmed Khan",
    "phone": "0300-1234567",
    "enquiry_type": "admission",
    "subject": "Admission in Class 3",
    "message": "I would like to know about admission for my son in Class 3.",
}


class TestEnquiries(BaseTestCase):

    def create_enquiry(self, **changes):
        return self.client.post(f"{API}/enquiries/create", json={**VALID_ENQUIRY, **changes})

    def test_public_can_submit(self):
        res = self.create_enquiry()
        self.assertEqual(res.status_code, 200)
        data = res.get_json()["data"]
        self.assertEqual(data["status"], "new")
        # public response does not expose other details
        self.assertEqual(set(data.keys()), {"id", "name", "status"})

    def test_email_or_phone_required(self):
        payload = {k: v for k, v in VALID_ENQUIRY.items() if k != "phone"}
        res = self.client.post(f"{API}/enquiries/create", json=payload)
        self.assertEqual(res.status_code, 400)

    def test_short_message_rejected(self):
        self.assertEqual(self.create_enquiry(message="hi").status_code, 400)

    def test_invalid_email_rejected(self):
        self.assertEqual(self.create_enquiry(email="not-an-email").status_code, 400)

    def test_invalid_type_rejected_by_payload(self):
        self.assertEqual(self.create_enquiry(enquiry_type="complaint").status_code, 400)

    def test_list_requires_token(self):
        self.assertEqual(self.client.get(f"{API}/enquiries/all").status_code, 401)

    def test_staff_can_list_and_filter(self):
        self.create_enquiry()
        self.create_enquiry(enquiry_type="general", message="General question about school timings.")
        h = self.staff_headers()

        all_items = self.client.get(f"{API}/enquiries/all", headers=h).get_json()["data"]
        self.assertEqual(len(all_items), 2)

        admission = self.client.get(f"{API}/enquiries/all?enquiry_type=admission", headers=h).get_json()["data"]
        self.assertEqual(len(admission), 1)

    def test_update_status_and_stats(self):
        enquiry_id = self.create_enquiry().get_json()["data"]["id"]
        h = self.staff_headers()

        res = self.client.put(f"{API}/enquiries/update-status/{enquiry_id}", headers=h,
                              json={"status": "resolved", "admin_notes": "Called the parent."})
        self.assertEqual(res.status_code, 200)
        self.assertEqual(res.get_json()["data"]["admin_notes"], "Called the parent.")

        stats = self.client.get(f"{API}/enquiries/stats", headers=h).get_json()["data"]
        self.assertEqual(stats["resolved"], 1)
        self.assertEqual(stats["total"], 1)

    def test_delete_enquiry(self):
        enquiry_id = self.create_enquiry().get_json()["data"]["id"]
        res = self.client.delete(f"{API}/enquiries/delete/{enquiry_id}", headers=self.admin_headers())
        self.assertEqual(res.status_code, 200)
        missing = self.client.get(f"{API}/enquiries/{enquiry_id}", headers=self.staff_headers())
        self.assertEqual(missing.status_code, 404)


class TestDashboard(BaseTestCase):

    def test_dashboard_requires_token(self):
        self.assertEqual(self.client.get(f"{API}/dashboard/summary").status_code, 401)

    def test_dashboard_summary(self):
        self.client.post(f"{API}/enquiries/create", json=VALID_ENQUIRY)
        res = self.client.get(f"{API}/dashboard/summary", headers=self.staff_headers())
        self.assertEqual(res.status_code, 200)
        data = res.get_json()["data"]
        self.assertEqual(data["classes"], 12)
        self.assertEqual(data["subjects"], 12)
        self.assertEqual(data["users"], 2)
        self.assertEqual(data["enquiries"]["new"], 1)


if __name__ == "__main__":
    unittest.main()
