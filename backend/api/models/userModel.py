import datetime

from werkzeug.security import check_password_hash, generate_password_hash

from api import db


class Users(db.Model):
    __tablename__ = 'users'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    email = db.Column(db.String(255), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    role = db.Column(
        db.Enum('admin', 'staff', name='user_role_enum'),
        nullable=False,
        default='staff',
    )
    status = db.Column(
        db.Enum('active', 'inactive', name='user_status_enum'),
        nullable=False,
        default='active',
    )
    last_login_at = db.Column(db.DateTime)
    created_at = db.Column(db.DateTime, default=datetime.datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.datetime.utcnow, onupdate=datetime.datetime.utcnow)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    @classmethod
    def get_all_records(cls):
        return cls.query.order_by(cls.created_at.desc()).all()

    @classmethod
    def get_by_id(cls, id):
        return cls.query.filter_by(id=id).first()

    @classmethod
    def get_by_email(cls, email):
        return cls.query.filter(db.func.lower(cls.email) == email.lower()).first()

    @classmethod
    def count_active_admins(cls):
        return cls.query.filter_by(role='admin', status='active').count()

    @classmethod
    def create(cls, data):
        new_rec = cls(
            name=data["name"],
            email=data["email"].lower(),
            password_hash=generate_password_hash(data["password"]),
            role=data.get("role", "staff"),
            status=data.get("status", "active"),
        )
        db.session.add(new_rec)
        db.session.commit()
        return new_rec

    @classmethod
    def update(cls, id, data):
        rec = cls.query.filter_by(id=id).first()
        if not rec:
            return None
        rec.name = data["name"]
        rec.email = data["email"].lower()
        rec.role = data["role"]
        rec.status = data["status"]
        if data.get("password"):
            rec.password_hash = generate_password_hash(data["password"])
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return rec

    @classmethod
    def update_name(cls, id, name):
        rec = cls.query.filter_by(id=id).first()
        if rec:
            rec.name = name
            rec.updated_at = datetime.datetime.utcnow()
            db.session.commit()
        return rec

    @classmethod
    def update_password(cls, id, password):
        rec = cls.query.filter_by(id=id).first()
        if rec:
            rec.password_hash = generate_password_hash(password)
            rec.updated_at = datetime.datetime.utcnow()
            db.session.commit()
        return rec

    @classmethod
    def update_last_login(cls, id):
        rec = cls.query.filter_by(id=id).first()
        if rec:
            rec.last_login_at = datetime.datetime.utcnow()
            db.session.commit()
        return rec

    @classmethod
    def delete_by_id(cls, id):
        rec = cls.query.filter_by(id=id).first()
        if rec:
            db.session.delete(rec)
            db.session.commit()
            return True
        return False
