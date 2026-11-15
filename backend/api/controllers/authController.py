from flask_jwt_extended import create_access_token

from api.models.userModel import Users
from api.services import clean, format_date, is_valid_email


def format_user(rec):
    return {
        "id": rec.id,
        "name": rec.name,
        "email": rec.email,
        "role": rec.role,
        "status": rec.status,
        "last_login_at": format_date(rec.last_login_at),
        "created_at": format_date(rec.created_at),
        "updated_at": format_date(rec.updated_at)
    }


# ============================================
# Auth
# ============================================

def login(data):
    email = clean(data.get("email"))
    password = data.get("password")

    if not email or not password:
        return {"success": False, "message": "Email and password are required."}, 400

    user = Users.get_by_email(email)
    if not user or not user.check_password(password):
        return {"success": False, "message": "Invalid email or password."}, 401
    if user.status != "active":
        return {"success": False, "message": "This account is inactive."}, 403

    Users.update_last_login(user.id)
    token = create_access_token(identity=str(user.id), additional_claims={"role": user.role})

    return {
        "success": True,
        "message": "Logged in successfully.",
        "data": {
            "access_token": token,
            "token_type": "Bearer",
            "user": format_user(user)
        }
    }, 200


def get_profile(user):
    return {"success": True, "message": "Profile fetched successfully.", "data": format_user(user)}, 200


def update_profile(user, data):
    name = clean(data.get("name"))
    if not name or len(name) < 2:
        return {"success": False, "message": "Name must be at least 2 characters."}, 400

    rec = Users.update_name(user.id, name)
    return {"success": True, "message": "Profile updated successfully.", "data": format_user(rec)}, 200


def change_password(user, data):
    current_password = data.get("current_password")
    new_password = data.get("new_password")

    if not user.check_password(current_password):
        return {"success": False, "message": "Current password is incorrect."}, 400
    if current_password == new_password:
        return {"success": False, "message": "New password must be different from the current password."}, 400

    Users.update_password(user.id, new_password)
    return {"success": True, "message": "Password changed successfully."}, 200


# ============================================
# Users (admin only)
# ============================================

def create_user(data):
    name = clean(data.get("name"))
    email = clean(data.get("email"))

    if not name or len(name) < 2:
        return {"success": False, "message": "Name must be at least 2 characters."}, 400
    if not is_valid_email(email):
        return {"success": False, "message": "Please enter a valid email address."}, 400
    if Users.get_by_email(email):
        return {"success": False, "message": "A user with this email already exists."}, 409

    rec = Users.create({
        "name": name,
        "email": email,
        "password": data["password"],
        "role": data.get("role", "staff")
    })
    return {"success": True, "message": "User created successfully.", "data": format_user(rec)}, 200


def get_all_users():
    records = Users.get_all_records()
    return {
        "success": True,
        "message": "Users fetched successfully.",
        "data": [format_user(rec) for rec in records]
    }, 200


def get_user_by_id(user_id):
    rec = Users.get_by_id(user_id)
    if not rec:
        return {"success": False, "message": "User not found."}, 404
    return {"success": True, "message": "User fetched successfully.", "data": format_user(rec)}, 200


def update_user(user_id, data, current_user):
    rec = Users.get_by_id(user_id)
    if not rec:
        return {"success": False, "message": "User not found."}, 404

    name = clean(data.get("name"))
    email = clean(data.get("email"))

    if not name or len(name) < 2:
        return {"success": False, "message": "Name must be at least 2 characters."}, 400
    if not is_valid_email(email):
        return {"success": False, "message": "Please enter a valid email address."}, 400

    existing = Users.get_by_email(email)
    if existing and existing.id != rec.id:
        return {"success": False, "message": "A user with this email already exists."}, 409

    if current_user.id == rec.id and data["status"] != "active":
        return {"success": False, "message": "You cannot deactivate your own account."}, 400

    # Never leave the school without an active admin
    removing_admin = rec.role == "admin" and rec.status == "active" and (
        data["role"] != "admin" or data["status"] != "active"
    )
    if removing_admin and Users.count_active_admins() <= 1:
        return {"success": False, "message": "At least one active admin is required."}, 400

    rec = Users.update(user_id, {
        "name": name,
        "email": email,
        "role": data["role"],
        "status": data["status"],
        "password": data.get("password")
    })
    return {"success": True, "message": "User updated successfully.", "data": format_user(rec)}, 200


def delete_user(user_id, current_user):
    rec = Users.get_by_id(user_id)
    if not rec:
        return {"success": False, "message": "User not found."}, 404
    if rec.id == current_user.id:
        return {"success": False, "message": "You cannot delete your own account."}, 400
    if rec.role == "admin" and rec.status == "active" and Users.count_active_admins() <= 1:
        return {"success": False, "message": "At least one active admin is required."}, 400

    Users.delete_by_id(user_id)
    return {"success": True, "message": "User deleted successfully."}, 200
