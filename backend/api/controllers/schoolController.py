from api.models.schoolModels import AdmissionDocuments, SchoolInfo
from api.services import clean, format_date, is_valid_email, is_valid_phone


# ============================================
# School Info
# ============================================

def format_school_info(rec):
    return {
        "name": rec.name,
        "tagline": rec.tagline,
        "address": rec.address,
        "phone": rec.phone,
        "email": rec.email,
        "office_hours": rec.office_hours,
        "about": rec.about,
        "vision": rec.vision,
        "mission": rec.mission,
        "admission_notice": rec.admission_notice,
        "updated_at": format_date(rec.updated_at)
    }


def get_school_info():
    rec = SchoolInfo.get_record()
    return {"success": True, "message": "School info fetched successfully.", "data": format_school_info(rec)}, 200


def update_school_info(data):
    update_data = {key: clean(value) for key, value in data.items()}

    if "name" in update_data and not update_data["name"]:
        return {"success": False, "message": "School name cannot be empty."}, 400
    if update_data.get("email") and not is_valid_email(update_data["email"]):
        return {"success": False, "message": "Please enter a valid email address."}, 400
    if update_data.get("phone") and not is_valid_phone(update_data["phone"]):
        return {"success": False, "message": "Please enter a valid phone number."}, 400

    rec = SchoolInfo.update(update_data)
    return {"success": True, "message": "School info updated successfully.", "data": format_school_info(rec)}, 200


# ============================================
# Admission Documents
# ============================================

def format_document(rec):
    return {
        "id": rec.id,
        "name": rec.name,
        "description": rec.description,
        "is_required": rec.is_required,
        "sort_order": rec.sort_order,
        "status": rec.status,
        "created_at": format_date(rec.created_at),
        "updated_at": format_date(rec.updated_at)
    }


def create_document(data):
    name = clean(data.get("name"))
    if not name:
        return {"success": False, "message": "Document name is required."}, 400
    if AdmissionDocuments.get_by_name(name):
        return {"success": False, "message": "This document already exists."}, 409

    data["name"] = name
    data["description"] = clean(data.get("description"))
    rec = AdmissionDocuments.create(data)
    return {"success": True, "message": "Document created successfully.", "data": format_document(rec)}, 200


def get_active_documents():
    records = AdmissionDocuments.get_active_records()
    return {"success": True, "message": "Documents fetched successfully.",
            "data": [format_document(rec) for rec in records]}, 200


def get_all_documents():
    records = AdmissionDocuments.get_all_records()
    return {"success": True, "message": "Documents fetched successfully.",
            "data": [format_document(rec) for rec in records]}, 200


def update_document(document_id, data):
    if not AdmissionDocuments.get_by_id(document_id):
        return {"success": False, "message": "Document not found."}, 404

    name = clean(data.get("name"))
    if not name:
        return {"success": False, "message": "Document name is required."}, 400
    existing = AdmissionDocuments.get_by_name(name)
    if existing and existing.id != document_id:
        return {"success": False, "message": "This document already exists."}, 409

    data["name"] = name
    data["description"] = clean(data.get("description"))
    rec = AdmissionDocuments.update(document_id, data)
    return {"success": True, "message": "Document updated successfully.", "data": format_document(rec)}, 200


def delete_document(document_id):
    success, error = AdmissionDocuments.delete_by_id(document_id)
    if not success:
        return {"success": False, "message": error}, 404 if "not found" in error else 400
    return {"success": True, "message": "Document deleted successfully."}, 200
