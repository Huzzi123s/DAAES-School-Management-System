import datetime

from api import db


class SchoolInfo(db.Model):
    """Single row with the school details shown on the website."""
    __tablename__ = 'school_info'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(150), nullable=False, default='DAAES School')
    tagline = db.Column(db.String(255))
    address = db.Column(db.String(255))
    phone = db.Column(db.String(50))
    email = db.Column(db.String(255))
    office_hours = db.Column(db.String(255))
    about = db.Column(db.Text)
    vision = db.Column(db.Text)
    mission = db.Column(db.Text)
    admission_notice = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.datetime.utcnow, onupdate=datetime.datetime.utcnow)

    @classmethod
    def get_record(cls):
        """Returns the school info row, creating it the first time."""
        rec = cls.query.first()
        if not rec:
            rec = cls(name='DAAES School')
            db.session.add(rec)
            db.session.commit()
        return rec

    @classmethod
    def update(cls, data):
        rec = cls.get_record()
        for field in ['name', 'tagline', 'address', 'phone', 'email', 'office_hours',
                      'about', 'vision', 'mission', 'admission_notice']:
            if field in data:
                setattr(rec, field, data[field])
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return rec


class AdmissionDocuments(db.Model):
    """Documents parents must bring for admission (B-Form, CNIC copy ...)."""
    __tablename__ = 'admission_documents'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(150), nullable=False)
    description = db.Column(db.String(500))
    is_required = db.Column(db.Boolean, nullable=False, default=True)
    sort_order = db.Column(db.Integer, nullable=False, default=0)
    status = db.Column(
        db.Enum('active', 'inactive', 'deleted', name='document_status_enum'),
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
            description=data.get("description"),
            is_required=data.get("is_required", True),
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
        rec.description = data.get("description")
        rec.is_required = data.get("is_required", rec.is_required)
        rec.sort_order = data.get("sort_order", rec.sort_order)
        rec.status = data.get("status", rec.status)
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return rec

    @classmethod
    def delete_by_id(cls, id):
        rec = cls.query.filter_by(id=id).first()
        if not rec:
            return False, "Document not found."
        if rec.status == 'deleted':
            return False, "Document is already deleted."
        rec.status = 'deleted'
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return True, None
