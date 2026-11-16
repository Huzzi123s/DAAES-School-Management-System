"""
Run server:        python app.py
Swagger docs:      http://127.0.0.1:5000/docs

CLI commands:
    flask --app app seed            create tables + insert website content
    flask --app app create-admin    create an admin account
"""
import os

import click
from flask import Flask, redirect, send_from_directory
from flask_cors import CORS

from config import Config
from api import api, db, jwt
from api import models  # noqa: F401  (registers all tables)
from api import routes  # noqa: F401  (registers all routes)


def create_app(config_class=Config):
    app = Flask(__name__)
    app.config.from_object(config_class)

    os.makedirs(os.path.join(app.root_path, "instance"), exist_ok=True)
    os.makedirs(app.config["UPLOAD_FOLDER"], exist_ok=True)

    db.init_app(app)
    jwt.init_app(app)
    api.init_app(app)
    CORS(app, resources={r"/api/*": {"origins": app.config["CORS_ORIGINS"]}})

    # Opening http://127.0.0.1:5000 goes straight to Swagger
    @app.route("/")
    def home():
        return redirect("/docs")

    # Serve uploaded gallery images
    @app.route("/uploads/<path:filename>")
    def uploaded_file(filename):
        return send_from_directory(app.config["UPLOAD_FOLDER"], filename)

    @app.errorhandler(413)
    def file_too_large(e):
        return {"success": False, "message": "File is too large."}, 413

    register_commands(app)
    return app


def register_commands(app):
    @app.cli.command("init-db")
    def init_db():
        """Create all database tables."""
        db.create_all()
        click.echo("Database tables created.")

    @app.cli.command("seed")
    def seed():
        """Create tables and insert the website's content."""
        from seed import seed_database
        db.create_all()
        for name, count in seed_database().items():
            click.echo(f"  {name}: {count} added")
        click.echo("Seeding complete.")

    @app.cli.command("create-admin")
    @click.option("--name", prompt="Full name")
    @click.option("--email", prompt="Email")
    @click.option("--password", prompt=True, hide_input=True, confirmation_prompt=True)
    def create_admin(name, email, password):
        """Create an admin account."""
        from api.models.userModel import Users
        from api.services import is_valid_email

        db.create_all()
        if not is_valid_email(email):
            raise click.ClickException("Please enter a valid email address.")
        if len(password) < 8:
            raise click.ClickException("Password must be at least 8 characters.")
        if Users.get_by_email(email):
            raise click.ClickException("A user with this email already exists.")

        Users.create({"name": name.strip(), "email": email.strip(), "password": password, "role": "admin"})
        click.echo(f"Admin account created for {email}.")


if __name__ == "__main__":
    app = create_app()
    with app.app_context():
        db.create_all()
    app.run(
        host=os.getenv("HOST", "127.0.0.1"),
        port=int(os.getenv("PORT", 5000)),
        debug=os.getenv("FLASK_ENV", "development") == "development",
    )
