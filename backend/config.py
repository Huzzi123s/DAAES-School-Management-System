import os
import datetime

from dotenv import load_dotenv

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(BASE_DIR, ".env"))


class Config:
    SECRET_KEY = os.getenv("SECRET_KEY", "change-this-secret-key")
    JWT_SECRET_KEY = os.getenv("JWT_SECRET_KEY", "change-this-jwt-secret-key")
    JWT_ACCESS_TOKEN_EXPIRES = datetime.timedelta(hours=int(os.getenv("JWT_EXPIRES_HOURS", 12)))

    # SQLite by default. For MySQL (XAMPP) set DATABASE_URL in .env
    SQLALCHEMY_DATABASE_URI = os.getenv("DATABASE_URL") or "sqlite:///" + os.path.join(
        BASE_DIR, "instance", "daaes.db"
    )
    SQLALCHEMY_TRACK_MODIFICATIONS = False

    UPLOAD_FOLDER = os.path.join(BASE_DIR, "uploads")
    MAX_CONTENT_LENGTH = int(os.getenv("MAX_UPLOAD_MB", 5)) * 1024 * 1024
    ALLOWED_IMAGE_EXTENSIONS = {"png", "jpg", "jpeg", "gif", "webp"}

    CORS_ORIGINS = os.getenv("CORS_ORIGINS", "*")

    # flask-restx: check every request body against its payload
    RESTX_VALIDATE = True
    RESTX_MASK_SWAGGER = False
    RESTX_ERROR_404_HELP = False


class TestConfig(Config):
    TESTING = True
    SQLALCHEMY_DATABASE_URI = "sqlite:///:memory:"
    JWT_SECRET_KEY = "test-jwt-secret-key-0123456789abcdefghij"
