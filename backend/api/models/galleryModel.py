import datetime

from api import db

GALLERY_CATEGORIES = {
    'events': 'School Events',
    'celebrations': 'Celebrations',
    'classroom': 'Classroom',
    'sports': 'Sports',
    'campus': 'Campus',
}


class GalleryItems(db.Model):
    __tablename__ = 'gallery_items'

    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(150), nullable=False)
    description = db.Column(db.String(500))
    category = db.Column(
        db.Enum(*GALLERY_CATEGORIES.keys(), name='gallery_category_enum'),
        nullable=False,
    )
    # "uploads/gallery/<file>" for uploaded images,
    # or a frontend path like "assets/images/gallery/event1.jpg"
    image_path = db.Column(db.String(500), nullable=False)
    sort_order = db.Column(db.Integer, nullable=False, default=0)
    status = db.Column(
        db.Enum('active', 'inactive', 'deleted', name='gallery_status_enum'),
        nullable=False,
        default='active',
    )
    created_at = db.Column(db.DateTime, default=datetime.datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.datetime.utcnow, onupdate=datetime.datetime.utcnow)

    @classmethod
    def get_all_records(cls):
        return cls.query.filter(cls.status != 'deleted').order_by(cls.sort_order, cls.id).all()

    @classmethod
    def get_active_records(cls, category=None):
        query = cls.query.filter_by(status='active')
        if category:
            query = query.filter_by(category=category)
        return query.order_by(cls.sort_order, cls.id).all()

    @classmethod
    def get_by_id(cls, id):
        return cls.query.filter(cls.id == id, cls.status != 'deleted').first()

    @classmethod
    def count_by_category(cls):
        rows = db.session.query(cls.category, db.func.count(cls.id)).filter(
            cls.status == 'active'
        ).group_by(cls.category).all()
        return {category: count for category, count in rows}

    @classmethod
    def create(cls, data):
        new_rec = cls(
            title=data["title"],
            description=data.get("description"),
            category=data["category"],
            image_path=data["image_path"],
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
        for field in ['title', 'description', 'category', 'image_path', 'sort_order', 'status']:
            if data.get(field) is not None:
                setattr(rec, field, data[field])
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return rec

    @classmethod
    def delete_by_id(cls, id):
        rec = cls.query.filter_by(id=id).first()
        if not rec:
            return False, "Gallery item not found."
        if rec.status == 'deleted':
            return False, "Gallery item is already deleted."
        rec.status = 'deleted'
        rec.updated_at = datetime.datetime.utcnow()
        db.session.commit()
        return True, None
