"""Shared setup for all unit tests.

Every test gets a fresh in-memory database with:
  - one admin and one staff user
  - the website content from seed.py
"""
import os
import shutil
import sys
import tempfile
import unittest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from app import create_app  # noqa: E402
from api import db  # noqa: E402
from api.models.userModel import Users  # noqa: E402
from config import TestConfig  # noqa: E402
from seed import seed_database  # noqa: E402

API = "/api/v1"
ADMIN = {"email": "admin@daaes.test", "password": "AdminPass123"}
STAFF = {"email": "staff@daaes.test", "password": "StaffPass123"}

# 1x1 PNG image used for upload tests
PNG = (
    b"\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00"
    b"\x1f\x15\xc4\x89\x00\x00\x00\rIDATx\x9cc\xf8\x0f\x00\x00\x01\x01\x00\x05\x18\xd8N\x00"
    b"\x00\x00\x00IEND\xaeB`\x82"
)

# The app is created once; the database is rebuilt for every test.
UPLOAD_DIR = tempfile.mkdtemp(prefix="daaes_test_uploads_")
app = create_app(TestConfig)
app.config["UPLOAD_FOLDER"] = UPLOAD_DIR


class BaseTestCase(unittest.TestCase):

    def setUp(self):
        self.app_context = app.app_context()
        self.app_context.push()
        db.create_all()
        Users.create({"name": "Admin", "role": "admin", **ADMIN})
        Users.create({"name": "Staff", "role": "staff", **STAFF})
        seed_database()
        self.client = app.test_client()

    def tearDown(self):
        db.session.remove()
        db.drop_all()
        self.app_context.pop()
        shutil.rmtree(os.path.join(UPLOAD_DIR, "gallery"), ignore_errors=True)

    # ---------- helpers ----------

    def login(self, creds):
        res = self.client.post(f"{API}/auth/login", json=creds)
        self.assertEqual(res.status_code, 200, res.get_json())
        return {"Authorization": "Bearer " + res.get_json()["data"]["access_token"]}

    def admin_headers(self):
        return self.login(ADMIN)

    def staff_headers(self):
        return self.login(STAFF)
