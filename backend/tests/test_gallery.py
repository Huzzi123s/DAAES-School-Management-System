import io
import unittest

from tests.base import API, PNG, BaseTestCase


class TestGallery(BaseTestCase):

    def upload(self, headers, title="Sports Day", category="sports", content=PNG, filename="day.png"):
        return self.client.post(
            f"{API}/gallery/create",
            headers=headers,
            content_type="multipart/form-data",
            data={"title": title, "category": category, "image": (io.BytesIO(content), filename)},
        )

    def test_public_gallery_from_seed(self):
        data = self.client.get(f"{API}/gallery/all").get_json()["data"]
        self.assertEqual(len(data), 12)
        self.assertTrue(data[0]["image_url"].startswith("assets/images/gallery/"))

    def test_filter_by_category(self):
        data = self.client.get(f"{API}/gallery/all?category=campus").get_json()["data"]
        self.assertEqual(len(data), 2)
        self.assertTrue(all(item["category"] == "campus" for item in data))

    def test_invalid_category(self):
        self.assertEqual(self.client.get(f"{API}/gallery/all?category=xyz").status_code, 400)

    def test_upload_image(self):
        res = self.upload(self.staff_headers())
        self.assertEqual(res.status_code, 200, res.get_json())
        item = res.get_json()["data"]
        self.assertTrue(item["image_path"].startswith("uploads/gallery/"))
        self.assertTrue(item["image_url"].startswith("http://localhost/uploads/gallery/"))

        served = self.client.get("/" + item["image_path"])
        self.assertEqual(served.status_code, 200)
        served.close()

    def test_upload_requires_token(self):
        self.assertEqual(self.upload({}).status_code, 401)

    def test_fake_image_rejected(self):
        res = self.upload(self.staff_headers(), content=b"this is not an image", filename="fake.jpg")
        self.assertEqual(res.status_code, 400)

    def test_wrong_extension_rejected(self):
        res = self.upload(self.staff_headers(), filename="image.exe")
        self.assertEqual(res.status_code, 400)

    def test_categories_count(self):
        self.upload(self.staff_headers(), category="sports")
        cats = {c["value"]: c["count"] for c in self.client.get(f"{API}/gallery/categories").get_json()["data"]}
        self.assertEqual(cats["sports"], 3)
        self.assertEqual(cats["events"], 3)

    def test_update_gallery_item(self):
        h = self.staff_headers()
        item_id = self.upload(h).get_json()["data"]["id"]
        res = self.client.put(f"{API}/gallery/update/{item_id}", headers=h,
                              content_type="multipart/form-data", data={"title": "Annual Sports Day"})
        self.assertEqual(res.status_code, 200)
        self.assertEqual(res.get_json()["data"]["title"], "Annual Sports Day")

    def test_delete_removes_uploaded_file(self):
        item = self.upload(self.staff_headers()).get_json()["data"]
        res = self.client.delete(f"{API}/gallery/delete/{item['id']}", headers=self.admin_headers())
        self.assertEqual(res.status_code, 200)
        self.assertEqual(self.client.get("/" + item["image_path"]).status_code, 404)


if __name__ == "__main__":
    unittest.main()
