# DAAES School - Backend (Flask + Flask-RESTX)

## Folder structure

```
backend/
├── main.py                 # run server: python main.py
├── app.py                  # create app + CLI commands
├── config.py               # settings (.env)
├── seed.py                 # website content (classes, subjects, gallery ...)
├── requirements.txt
├── api/
│   ├── __init__.py         # db, jwt, Api + all namespaces
│   ├── services.py         # token decorators + helpers (image upload, validation)
│   ├── models/             # database tables + classmethods (get_all_records, create, update, delete_by_id)
│   ├── payloads/           # ns.model(...) request payloads
│   ├── controllers/        # simple functions -> return result, status_code
│   └── routes/             # Resource classes (@ns.route, @ns.expect, token decorators)
├── tests/                  # unittest test cases (one file per module)
└── uploads/                # uploaded gallery images
```

Flow: **route -> controller -> model**

## Setup

```powershell
cd backend
python -m venv .venv
.\.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env

flask --app app seed            # create tables + insert website content
flask --app app create-admin    # create your admin account
python main.py                  # http://127.0.0.1:5000
```

Swagger docs (test every API in the browser): **http://127.0.0.1:5000/docs**
Login there, click **Authorize** and enter `Bearer <access_token>`.

MySQL (XAMPP): create database `daaes_school` and set in `.env`:
`DATABASE_URL=mysql+pymysql://root:@localhost:3306/daaes_school`

Unit tests: `python -m unittest discover -s tests -t .`

## Tokens

| Decorator | Who |
|---|---|
| `read_token_required` | any logged-in user |
| `write_token_required` | admin, staff |
| `delete_token_required` | admin |
| `admin_token_required` | admin (user management) |

## Endpoints (prefix `/api/v1`)

| Method | Path | Token |
|---|---|---|
| POST | `/auth/login` | - |
| GET | `/auth/me` | read |
| PUT | `/auth/profile/update` | read |
| PUT | `/auth/change-password` | read |
| POST | `/users/create` | admin |
| GET | `/users/all` , `/users/<id>` | admin |
| PUT | `/users/update/<id>` | admin |
| DELETE | `/users/delete/<id>` | admin |
| GET | `/school/info` | - |
| PUT | `/school/info/update` | write |
| GET | `/school/documents/all` | - |
| GET | `/school/documents/admin/all` | read |
| POST | `/school/documents/create` | write |
| PUT | `/school/documents/update/<id>` | write |
| DELETE | `/school/documents/delete/<id>` | delete |
| GET | `/academics/classes/all?level=` , `/academics/classes/<id>` | - |
| GET | `/academics/classes/admin/all` | read |
| POST | `/academics/classes/create` | write |
| PUT | `/academics/classes/update/<id>` | write |
| DELETE | `/academics/classes/delete/<id>` | delete |
| GET/POST/PUT/DELETE | `/academics/subjects/...` (same as classes) | |
| GET/POST/PUT/DELETE | `/academics/programs/...` (same as classes) | |
| GET | `/gallery/all?category=` , `/gallery/categories` | - |
| GET | `/gallery/admin/all` | read |
| POST | `/gallery/create` (form-data + image) | write |
| PUT | `/gallery/update/<id>` (form-data) | write |
| DELETE | `/gallery/delete/<id>` | delete |
| POST | `/enquiries/create` (contact form) | - |
| GET | `/enquiries/all?status=&enquiry_type=` , `/enquiries/stats` , `/enquiries/<id>` | read |
| PUT | `/enquiries/update-status/<id>` | write |
| DELETE | `/enquiries/delete/<id>` | delete |
| GET | `/dashboard/summary` | read |

Response: `{"success": true, "message": "...", "data": ...}`
