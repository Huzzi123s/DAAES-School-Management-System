from flask_jwt_extended import JWTManager
from flask_restx import Api, Namespace
from flask_sqlalchemy import SQLAlchemy

db = SQLAlchemy()
jwt = JWTManager()

authorizations = {
    "Authorization": {
        "type": "apiKey",
        "in": "header",
        "name": "Authorization",
        "description": "Bearer <access_token>",
    }
}

api = Api(
    title="DAAES School API",
    version="1.0",
    description="Backend API for the DAAES School website",
    prefix="/api/v1",
    doc="/docs",
    authorizations=authorizations,
)

# ============================================
# Namespaces
# ============================================
auth_ns = Namespace("auth", description="Login and profile")
users_ns = Namespace("users", description="Admin / staff accounts")
school_ns = Namespace("school", description="School information and admission documents")
academics_ns = Namespace("academics", description="Classes, subjects and programs")
gallery_ns = Namespace("gallery", description="Gallery images")
enquiry_ns = Namespace("enquiries", description="Contact / admission enquiries")
dashboard_ns = Namespace("dashboard", description="Admin dashboard")

for ns in (auth_ns, users_ns, school_ns, academics_ns, gallery_ns, enquiry_ns, dashboard_ns):
    api.add_namespace(ns)
