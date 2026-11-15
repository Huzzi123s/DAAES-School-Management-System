from flask import request

from api.models.galleryModel import GALLERY_CATEGORIES, GalleryItems
from api.services import clean, delete_image, format_date, save_image


def format_gallery_item(rec):
    # Uploaded images are served by this backend, other paths are frontend assets
    image_url = rec.image_path
    if rec.image_path.startswith("uploads/"):
        image_url = request.host_url + rec.image_path

    return {
        "id": rec.id,
        "title": rec.title,
        "description": rec.description,
        "category": rec.category,
        "image_path": rec.image_path,
        "image_url": image_url,
        "sort_order": rec.sort_order,
        "status": rec.status,
        "created_at": format_date(rec.created_at),
        "updated_at": format_date(rec.updated_at)
    }


def create_gallery_item(data):
    title = clean(data.get("title"))
    if not title:
        return {"success": False, "message": "Title is required."}, 400

    image_path, error = save_image(data.get("image"), "gallery")
    if error:
        return {"success": False, "message": error}, 400

    rec = GalleryItems.create({
        "title": title,
        "description": clean(data.get("description")),
        "category": data["category"],
        "image_path": image_path,
        "sort_order": data.get("sort_order") or 0
    })
    return {"success": True, "message": "Gallery item created successfully.", "data": format_gallery_item(rec)}, 200


def get_active_gallery(category=None):
    if category and category not in GALLERY_CATEGORIES:
        return {"success": False, "message": "Invalid category."}, 400

    records = GalleryItems.get_active_records(category=category)
    return {"success": True, "message": "Gallery fetched successfully.",
            "data": [format_gallery_item(rec) for rec in records]}, 200


def get_all_gallery():
    records = GalleryItems.get_all_records()
    return {"success": True, "message": "Gallery fetched successfully.",
            "data": [format_gallery_item(rec) for rec in records]}, 200


def get_gallery_categories():
    counts = GalleryItems.count_by_category()
    result = [
        {"value": key, "label": label, "count": counts.get(key, 0)}
        for key, label in GALLERY_CATEGORIES.items()
    ]
    return {"success": True, "message": "Categories fetched successfully.", "data": result}, 200


def update_gallery_item(item_id, data):
    rec = GalleryItems.get_by_id(item_id)
    if not rec:
        return {"success": False, "message": "Gallery item not found."}, 404

    if "title" in data and data["title"] is not None and not clean(data["title"]):
        return {"success": False, "message": "Title cannot be empty."}, 400

    old_image = None
    image = data.get("image")
    if image and image.filename:
        new_path, error = save_image(image, "gallery")
        if error:
            return {"success": False, "message": error}, 400
        old_image = rec.image_path
        data["image_path"] = new_path

    data["title"] = clean(data.get("title"))
    data["description"] = clean(data.get("description"))
    rec = GalleryItems.update(item_id, data)
    delete_image(old_image)
    return {"success": True, "message": "Gallery item updated successfully.", "data": format_gallery_item(rec)}, 200


def delete_gallery_item(item_id):
    rec = GalleryItems.get_by_id(item_id)
    image_path = rec.image_path if rec else None

    success, error = GalleryItems.delete_by_id(item_id)
    if not success:
        return {"success": False, "message": error}, 404 if "not found" in error else 400

    delete_image(image_path)
    return {"success": True, "message": "Gallery item deleted successfully."}, 200
