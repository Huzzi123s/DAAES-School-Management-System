import datetime

from api import db

ENQUIRY_TYPES = ['general', 'admission', 'academics', 'other']
ENQUIRY_STATUSES = ['new', 'in_progress', 'resolved', 'closed']


class Enquiries(db.Model):
    """Messages sent by parents / visitors from the contact form."""
    __tablename__ = 'enquiries'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    email = db.Column(db.String(255))
    phone = db.Column(db.String(30))
    enquiry_type = db.Column(
        db.Enum(*ENQUIRY_TYPES, name='enquiry_type_enum'),
        nullable=False,
        default='general',
    )
    subject = db.Column(db.String(200))
    message = db.Column(db.Text, nullable=False)
    status = db.Column(
        db.Enum(*ENQUIRY_STATUSES, name='enquiry_status_enum'),
        nullable=False,
        default='new',
    )
    admin_notes = db.Column(db.Text)
    ip_address = db.Column(db.String(45))
    created_at = db.Column(db.DateTime, default=datetime.datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.datetime.utcnow, onupdate=datetime.datetime.utcnow)

    @classmethod
    def get_all_records(cls, status=None, enquiry_type=None):
        query = cls.query
        if status:
            query = query.filter_by(status=status)
        if enquiry_type:
            query = query.filter_by(enquiry_type=enquiry_type)
        return query.order_by(cls.created_at.desc(), cls.id.desc()).all()

    @classmethod
    def get_by_id(cls, id):
        return cls.query.filter_by(id=id).first()

    @classmethod
    def count_by_status(cls):
        rows = db.session.query(cls.status, db.func.count(cls.id)).group_by(cls.status).all()
        counts = {status: 0 for status in ENQUIRY_STATUSES}
        counts.update({status: count for status, count in rows})
        counts["total"] = sum(count for _, count in rows)
        return counts

    @classmethod
    def create(cls, data):
        new_rec = cls(
            name=data["name"],
            email=data.get("email"),
            phone=data.get("phone"),
            enquiry_type=data.get("enquiry_type", "general"),
            subject=data.get("subject"),
            message=data["message"],
            ip_address=data.get("ip_address"),
            status="new",
        )
        db.session.add(new_rec)
        db.session.commit()
        return new_rec

    @classmethod
    def update_status(cls, id, data):
        rec = cls.query.filter_by(id=id).first()
        if not rec:
            return None
        rec.status = data["status"]
        if "admin_notes" in data:
            rec.admin_notes = data["admin_notes"]
        rec.updated_at = datetime.datetime.utcnow()
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
