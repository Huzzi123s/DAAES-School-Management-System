import os
import re
import uuid
from functools import wraps

from flask import current_app, g
from flask_jwt_extended import get_jwt_identity, verify_jwt_in_request

from api.models.userModel import Users


# ============================================
# Token decorators
# ============================================

def _token_required(roles=None):
    def decorator(fn):
        @wraps(fn)
        def wrapper(*args, **kwargs):
            try:
                verify_jwt_in_request()
                user = Users.get_by_id(int(get_jwt_identity()))
            except Exception:
                return {"success": False, "message": "Valid token is required."}, 401

            if not user or user.status != "active":
                return {"success": False, "message": "Account not found or inactive."}, 401
            if roles and user.role not in roles:
                return {"success": False, "message": "You do not have permission for this action."}, 403

            g.current_user = user
            return fn(*args, **kwargs)
        return wrapper
    return decorator


read_token_required = _token_required()                     # any logged-in user
write_token_required = _token_required(["admin", "staff"])  # add / update
delete_token_required = _token_required(["admin"])          # delete
admin_token_required = _token_required(["admin"])           # user management


def get_current_user():
    return g.get("current_user")


# ============================================
# Helpers
# ============================================

def clean(value):
    """Strip text values. Empty text becomes None."""
    if isinstance(value, str):
        value = value.strip()
        return value or None
    return value


def is_valid_email(email):
    return bool(email) and re.match(r"^[^@\s]+@[^@\s]+\.[^@\s]+$", email) is not None


def is_valid_phone(phone):
    return bool(phone) and re.match(r"^[0-9+()\-\s]{7,30}$", phone) is not None


def format_date(value):
    return value.strftime("%Y-%m-%d %H:%M") if value else None


# ============================================
# Image upload
# ============================================

def _is_real_image(file):
    header = file.stream.read(16)
    file.stream.seek(0)
    return (
        header.startswith(b"\x89PNG")
        or header.startswith(b"\xff\xd8\xff")
        or header[:6] in (b"GIF87a", b"GIF89a")
        or (header[:4] == b"RIFF" and header[8:12] == b"WEBP")
    )


def save_image(file, folder):
    """Saves an uploaded image. Returns (path, error_message)."""
    if not file or not file.filename:
        return None, "Image file is required."

    ext = file.filename.rsplit(".", 1)[-1].lower() if "." in file.filename else ""
    if ext not in current_app.config["ALLOWED_IMAGE_EXTENSIONS"]:
        return None, "Only png, jpg, jpeg, gif and webp images are allowed."
    if not _is_real_image(file):
        return None, "File is not a valid image."

    save_dir = os.path.join(current_app.config["UPLOAD_FOLDER"], folder)
    os.makedirs(save_dir, exist_ok=True)
    filename = f"{uuid.uuid4().hex}.{ext}"
    file.save(os.path.join(save_dir, filename))
    return f"uploads/{folder}/{filename}", None


def delete_image(path):
    """Deletes an uploaded image. Frontend asset paths are ignored."""
    if not path or not path.startswith("uploads/"):
        return
    root = os.path.realpath(current_app.config["UPLOAD_FOLDER"])
    full_path = os.path.realpath(os.path.join(root, path[len("uploads/"):]))
    if full_path.startswith(root + os.sep) and os.path.isfile(full_path):
        os.remove(full_path)
