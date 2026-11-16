from flask_restx import fields

from api import auth_ns, users_ns

# Auth Payloads
login_payload = auth_ns.model('Login', {
    'email': fields.String(required=True, description='Email address'),
    'password': fields.String(required=True, description='Password')
})

update_profile_payload = auth_ns.model('UpdateProfile', {
    'name': fields.String(required=True, description='Full name')
})

change_password_payload = auth_ns.model('ChangePassword', {
    'current_password': fields.String(required=True, description='Current password'),
    'new_password': fields.String(required=True, min_length=8, description='New password (min 8 characters)')
})

# User Payloads
create_user_payload = users_ns.model('CreateUser', {
    'name': fields.String(required=True, description='Full name'),
    'email': fields.String(required=True, description='Email address'),
    'password': fields.String(required=True, min_length=8, description='Password (min 8 characters)'),
    'role': fields.String(required=False, enum=['admin', 'staff'], default='staff', description='User role')
})

update_user_payload = users_ns.model('UpdateUser', {
    'name': fields.String(required=True, description='Full name'),
    'email': fields.String(required=True, description='Email address'),
    'role': fields.String(required=True, enum=['admin', 'staff'], description='User role'),
    'status': fields.String(required=True, enum=['active', 'inactive'], description='Account status'),
    'password': fields.String(required=False, min_length=8, description='New password (optional)')
})
