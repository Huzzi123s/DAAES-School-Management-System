"""
Start the backend:   python main.py
Swagger:             http://127.0.0.1:5000/docs
"""
import os
import subprocess
import sys

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# If Flask is not installed in this Python, re-run with the project's .venv Python
try:
    import flask  # noqa: F401
except ImportError:
    venv_python = os.path.join(BASE_DIR, ".venv", "Scripts" if os.name == "nt" else "bin",
                               "python.exe" if os.name == "nt" else "python")
    if os.path.exists(venv_python) and os.path.abspath(sys.executable) != os.path.abspath(venv_python):
        sys.exit(subprocess.call([venv_python, os.path.abspath(__file__)] + sys.argv[1:]))
    print("Flask is not installed. Run:  pip install -r requirements.txt")
    sys.exit(1)

# Work from the backend folder no matter where the command was run
os.chdir(BASE_DIR)
sys.path.insert(0, BASE_DIR)

from app import create_app  # noqa: E402
from api import db  # noqa: E402
from api.models.academicsModels import SchoolClasses  # noqa: E402
from seed import seed_database  # noqa: E402

app = create_app()

with app.app_context():
    db.create_all()
    if not SchoolClasses.get_all_records():
        seed_database()

if __name__ == "__main__":
    host = os.getenv("HOST", "127.0.0.1")
    port = int(os.getenv("PORT", 5000))

    if os.environ.get("WERKZEUG_RUN_MAIN") != "true":
        print("\n  DAAES School backend is running")
        print(f"  Swagger docs:  http://{host}:{port}/docs\n")

    app.run(host=host, port=port, debug=os.getenv("FLASK_ENV", "development") == "development")
