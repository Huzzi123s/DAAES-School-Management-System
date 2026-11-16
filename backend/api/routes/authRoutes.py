from flask_restx import Resource, abort

from api import auth_ns, users_ns
from api.payloads.authPayloads import *
from api.services import read_token_required, admin_token_required, get_current_user
from api.controllers.authController import *

import traceback


# ============================================
# Auth Routes
# ============================================

@auth_ns.route("/login")
@auth_ns.doc("Login")
class Login(Resource):
    @auth_ns.doc("Login")
    @auth_ns.expect(login_payload)
    @auth_ns.response(200, "Logged in successfully.")
    @auth_ns.response(401, "Invalid email or password.")
    def post(self):
        """Login with email and password, returns an access token"""
        data = auth_ns.payload
        try:
            result, status_code = login(data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@auth_ns.route("/me")
@auth_ns.doc("Get My Profile")
class MyProfile(Resource):
    @auth_ns.doc("Get My Profile")
    @auth_ns.response(200, "Profile fetched successfully.")
    @auth_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get the logged-in user's profile"""
        try:
            result, status_code = get_profile(get_current_user())
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@auth_ns.route("/profile/update")
@auth_ns.doc("Update My Profile")
class UpdateMyProfile(Resource):
    @auth_ns.doc("Update My Profile")
    @auth_ns.expect(update_profile_payload)
    @auth_ns.response(200, "Profile updated successfully.")
    @auth_ns.doc(security=["Authorization"])
    @read_token_required
    def put(self):
        """Update the logged-in user's name"""
        data = auth_ns.payload
        try:
            result, status_code = update_profile(get_current_user(), data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@auth_ns.route("/change-password")
@auth_ns.doc("Change Password")
class ChangePassword(Resource):
    @auth_ns.doc("Change Password")
    @auth_ns.expect(change_password_payload)
    @auth_ns.response(200, "Password changed successfully.")
    @auth_ns.response(400, "Current password is incorrect.")
    @auth_ns.doc(security=["Authorization"])
    @read_token_required
    def put(self):
        """Change the logged-in user's password"""
        data = auth_ns.payload
        try:
            result, status_code = change_password(get_current_user(), data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


# ============================================
# Users Routes (admin only)
# ============================================

@users_ns.route("/create")
@users_ns.doc("Create User")
class CreateUser(Resource):
    @users_ns.doc("Create User")
    @users_ns.expect(create_user_payload)
    @users_ns.response(200, "User created successfully.")
    @users_ns.response(409, "A user with this email already exists.")
    @users_ns.doc(security=["Authorization"])
    @admin_token_required
    def post(self):
        """Create a new admin / staff user"""
        data = users_ns.payload
        try:
            result, status_code = create_user(data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@users_ns.route("/all")
@users_ns.doc("Get All Users")
class GetAllUsers(Resource):
    @users_ns.doc("Get All Users")
    @users_ns.response(200, "Users fetched successfully.")
    @users_ns.doc(security=["Authorization"])
    @admin_token_required
    def get(self):
        """Get all users"""
        try:
            result, status_code = get_all_users()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@users_ns.route("/<int:user_id>")
@users_ns.doc("Get User")
class GetUser(Resource):
    @users_ns.doc("Get User")
    @users_ns.response(200, "User fetched successfully.")
    @users_ns.response(404, "User not found.")
    @users_ns.doc(security=["Authorization"])
    @admin_token_required
    def get(self, user_id):
        """Get a user by ID"""
        try:
            result, status_code = get_user_by_id(user_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@users_ns.route("/update/<int:user_id>")
@users_ns.doc("Update User")
class UpdateUser(Resource):
    @users_ns.doc("Update User")
    @users_ns.expect(update_user_payload)
    @users_ns.response(200, "User updated successfully.")
    @users_ns.response(404, "User not found.")
    @users_ns.doc(security=["Authorization"])
    @admin_token_required
    def put(self, user_id):
        """Update a user's name, email, role, status (and optionally password)"""
        data = users_ns.payload
        try:
            result, status_code = update_user(user_id, data, get_current_user())
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@users_ns.route("/delete/<int:user_id>")
@users_ns.doc("Delete User")
class DeleteUser(Resource):
    @users_ns.doc("Delete User")
    @users_ns.response(200, "User deleted successfully.")
    @users_ns.response(404, "User not found.")
    @users_ns.doc(security=["Authorization"])
    @admin_token_required
    def delete(self, user_id):
        """Delete a user by ID"""
        try:
            result, status_code = delete_user(user_id, get_current_user())
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})
