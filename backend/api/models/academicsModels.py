import datetime

from api import db

CLASS_LEVELS = ['pre_primary', 'primary', 'middle', 'secondary']
STATUSES = ['active', 'inactive']


class SchoolClasses(db.Model):
    __tablename__ = 'school_classes'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    level = db.Column(
        db.Enum(*CLASS_LEVELS, name='class_level_enum'),
        nullable=False,
    )
    description = db.Column(db.Text)
    admissions_open = db.Column(db.Boolean, nullable=False, default=True)
    sort_order = db.Column(db.Integer, nullable=False, default=0)
    status = db.Column(
        db.Enum('active', 'inactive', 'deleted', name='class_status_enum'),
        nullable=False,
        default='active',
    )
    created_at = db.Column(db.DateTime, default=datetime.datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.datetime.utcnow, onupdate=datetime.datetime.utcnow)

    @classmethod
    def get_all_records(cls):
        """Everything except deleted - for the admin panel."""
        return cls.query.filter(cls.status != 'deleted').order_by(cls.sort_order, cls.id).all()

    @classmethod
    def get_active_records(cls, level=None):
        """Only active classes - for the website."""
        query = cls.query.filter_by(status='active')
        if level:
            query = query.filter_by(level=level)
        return query.order_by(cls.sort_order, cls.id).all()

    @classmethod
    def get_by_id(cls, id):
        return cls.query.filter(cls.id == id, cls.status != 'deleted').first()

    @classmethod
    def get_by_name(cls, name):
        return cls.query.filter(
            db.func.lower(cls.name) == name.lower(),
            cls.status != 'deleted',
        ).first()

    @classmethod
    def create(cls, data):
        new_rec = cls(
            name=data["name"],
            level=data["level"],
            description=data.get("description"),
            admissions_open=data.get("admissions_open", True),
            sort_order=data.get("sort_order", 0),
            status="active",
        )
        db.session.add(new_rec)
        db.session.commit()
        return new_rec

    @classmethod
    def update(cls, id, data):
        rec = cls.get_by_id(id)
        if not rec:
            return None
        rec.name = data["name"]
        rec.level = data["level"]
        rec.description = data.get("description")
        rec.admissions_open = data.get("admissions_open", rec.admissions_open)
        rec.sort_order = data.get("sort_order", rec.sort_order)
        rec.status = data.get("status", rec.status)
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return rec

    @classmethod
    def delete_by_id(cls, id):
        """Soft-delete. Returns (success, error_message)."""
        rec = cls.query.filter_by(id=id).first()
        if not rec:
            return False, "Class not found."
        if rec.status == 'deleted':
            return False, "Class is already deleted."
        rec.status = 'deleted'
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return True, None


class Subjects(db.Model):
    __tablename__ = 'subjects'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    icon = db.Column(db.String(20))
    description = db.Column(db.Text)
    sort_order = db.Column(db.Integer, nullable=False, default=0)
    status = db.Column(
        db.Enum('active', 'inactive', 'deleted', name='subject_status_enum'),
        nullable=False,
        default='active',
    )
    created_at = db.Column(db.DateTime, default=datetime.datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.datetime.utcnow, onupdate=datetime.datetime.utcnow)

    @classmethod
    def get_all_records(cls):
        return cls.query.filter(cls.status != 'deleted').order_by(cls.sort_order, cls.id).all()

    @classmethod
    def get_active_records(cls):
        return cls.query.filter_by(status='active').order_by(cls.sort_order, cls.id).all()

    @classmethod
    def get_by_id(cls, id):
        return cls.query.filter(cls.id == id, cls.status != 'deleted').first()

    @classmethod
    def get_by_name(cls, name):
        return cls.query.filter(
            db.func.lower(cls.name) == name.lower(),
            cls.status != 'deleted',
        ).first()

    @classmethod
    def create(cls, data):
        new_rec = cls(
            name=data["name"],
            icon=data.get("icon"),
            description=data.get("description"),
            sort_order=data.get("sort_order", 0),
            status="active",
        )
        db.session.add(new_rec)
        db.session.commit()
        return new_rec

    @classmethod
    def update(cls, id, data):
        rec = cls.get_by_id(id)
        if not rec:
            return None
        rec.name = data["name"]
        rec.icon = data.get("icon")
        rec.description = data.get("description")
        rec.sort_order = data.get("sort_order", rec.sort_order)
        rec.status = data.get("status", rec.status)
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return rec

    @classmethod
    def delete_by_id(cls, id):
        rec = cls.query.filter_by(id=id).first()
        if not rec:
            return False, "Subject not found."
        if rec.status == 'deleted':
            return False, "Subject is already deleted."
        rec.status = 'deleted'
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return True, None


class AcademicPrograms(db.Model):
    __tablename__ = 'academic_programs'

    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(150), nullable=False)
    level = db.Column(
        db.Enum(*CLASS_LEVELS, name='program_level_enum'),
        nullable=False,
    )
    classes_covered = db.Column(db.String(150))
    description = db.Column(db.Text)
    sort_order = db.Column(db.Integer, nullable=False, default=0)
    status = db.Column(
        db.Enum('active', 'inactive', 'deleted', name='program_status_enum'),
        nullable=False,
        default='active',
    )
    created_at = db.Column(db.DateTime, default=datetime.datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.datetime.utcnow, onupdate=datetime.datetime.utcnow)

    @classmethod
    def get_all_records(cls):
        return cls.query.filter(cls.status != 'deleted').order_by(cls.sort_order, cls.id).all()

    @classmethod
    def get_active_records(cls):
        return cls.query.filter_by(status='active').order_by(cls.sort_order, cls.id).all()

    @classmethod
    def get_by_id(cls, id):
        return cls.query.filter(cls.id == id, cls.status != 'deleted').first()

    @classmethod
    def get_by_title(cls, title):
        return cls.query.filter(
            db.func.lower(cls.title) == title.lower(),
            cls.status != 'deleted',
        ).first()

    @classmethod
    def create(cls, data):
        new_rec = cls(
            title=data["title"],
            level=data["level"],
            classes_covered=data.get("classes_covered"),
            description=data.get("description"),
            sort_order=data.get("sort_order", 0),
            status="active",
        )
        db.session.add(new_rec)
        db.session.commit()
        return new_rec

    @classmethod
    def update(cls, id, data):
        rec = cls.get_by_id(id)
        if not rec:
            return None
        rec.title = data["title"]
        rec.level = data["level"]
        rec.classes_covered = data.get("classes_covered")
        rec.description = data.get("description")
        rec.sort_order = data.get("sort_order", rec.sort_order)
        rec.status = data.get("status", rec.status)
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return rec

    @classmethod
    def delete_by_id(cls, id):
        rec = cls.query.filter_by(id=id).first()
        if not rec:
            return False, "Program not found."
        if rec.status == 'deleted':
            return False, "Program is already deleted."
        rec.status = 'deleted'
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return True, None
