from api.models.academicsModels import AcademicPrograms, SchoolClasses, Subjects
from api.services import clean, format_date


def _delete_response(success, error, message):
    if not success:
        return {"success": False, "message": error}, 404 if "not found" in error else 400
    return {"success": True, "message": message}, 200


# ============================================
# Classes
# ============================================

def format_class(rec):
    return {
        "id": rec.id,
        "name": rec.name,
        "level": rec.level,
        "description": rec.description,
        "admissions_open": rec.admissions_open,
        "sort_order": rec.sort_order,
        "status": rec.status,
        "created_at": format_date(rec.created_at),
        "updated_at": format_date(rec.updated_at)
    }


def create_class(data):
    name = clean(data.get("name"))
    if not name:
        return {"success": False, "message": "Class name is required."}, 400
    if SchoolClasses.get_by_name(name):
        return {"success": False, "message": "A class with this name already exists."}, 409

    data["name"] = name
    data["description"] = clean(data.get("description"))
    rec = SchoolClasses.create(data)
    return {"success": True, "message": "Class created successfully.", "data": format_class(rec)}, 200


def get_active_classes(level=None):
    records = SchoolClasses.get_active_records(level=level)
    return {"success": True, "message": "Classes fetched successfully.",
            "data": [format_class(rec) for rec in records]}, 200


def get_all_classes():
    records = SchoolClasses.get_all_records()
    return {"success": True, "message": "Classes fetched successfully.",
            "data": [format_class(rec) for rec in records]}, 200


def get_class_by_id(class_id):
    rec = SchoolClasses.get_by_id(class_id)
    if not rec:
        return {"success": False, "message": "Class not found."}, 404
    return {"success": True, "message": "Class fetched successfully.", "data": format_class(rec)}, 200


def update_class(class_id, data):
    if not SchoolClasses.get_by_id(class_id):
        return {"success": False, "message": "Class not found."}, 404

    name = clean(data.get("name"))
    if not name:
        return {"success": False, "message": "Class name is required."}, 400
    existing = SchoolClasses.get_by_name(name)
    if existing and existing.id != class_id:
        return {"success": False, "message": "A class with this name already exists."}, 409

    data["name"] = name
    data["description"] = clean(data.get("description"))
    rec = SchoolClasses.update(class_id, data)
    return {"success": True, "message": "Class updated successfully.", "data": format_class(rec)}, 200


def delete_class(class_id):
    success, error = SchoolClasses.delete_by_id(class_id)
    return _delete_response(success, error, "Class deleted successfully.")


# ============================================
# Subjects
# ============================================

def format_subject(rec):
    return {
        "id": rec.id,
        "name": rec.name,
        "icon": rec.icon,
        "description": rec.description,
        "sort_order": rec.sort_order,
        "status": rec.status,
        "created_at": format_date(rec.created_at),
        "updated_at": format_date(rec.updated_at)
    }


def create_subject(data):
    name = clean(data.get("name"))
    if not name:
        return {"success": False, "message": "Subject name is required."}, 400
    if Subjects.get_by_name(name):
        return {"success": False, "message": "A subject with this name already exists."}, 409

    data["name"] = name
    data["icon"] = clean(data.get("icon"))
    data["description"] = clean(data.get("description"))
    rec = Subjects.create(data)
    return {"success": True, "message": "Subject created successfully.", "data": format_subject(rec)}, 200


def get_active_subjects():
    records = Subjects.get_active_records()
    return {"success": True, "message": "Subjects fetched successfully.",
            "data": [format_subject(rec) for rec in records]}, 200


def get_all_subjects():
    records = Subjects.get_all_records()
    return {"success": True, "message": "Subjects fetched successfully.",
            "data": [format_subject(rec) for rec in records]}, 200


def update_subject(subject_id, data):
    if not Subjects.get_by_id(subject_id):
        return {"success": False, "message": "Subject not found."}, 404

    name = clean(data.get("name"))
    if not name:
        return {"success": False, "message": "Subject name is required."}, 400
    existing = Subjects.get_by_name(name)
    if existing and existing.id != subject_id:
        return {"success": False, "message": "A subject with this name already exists."}, 409

    data["name"] = name
    data["icon"] = clean(data.get("icon"))
    data["description"] = clean(data.get("description"))
    rec = Subjects.update(subject_id, data)
    return {"success": True, "message": "Subject updated successfully.", "data": format_subject(rec)}, 200


def delete_subject(subject_id):
    success, error = Subjects.delete_by_id(subject_id)
    return _delete_response(success, error, "Subject deleted successfully.")


# ============================================
# Academic Programs
# ============================================

def format_program(rec):
    return {
        "id": rec.id,
        "title": rec.title,
        "level": rec.level,
        "classes_covered": rec.classes_covered,
        "description": rec.description,
        "sort_order": rec.sort_order,
        "status": rec.status,
        "created_at": format_date(rec.created_at),
        "updated_at": format_date(rec.updated_at)
    }


def create_program(data):
    title = clean(data.get("title"))
    if not title:
        return {"success": False, "message": "Program title is required."}, 400
    if AcademicPrograms.get_by_title(title):
        return {"success": False, "message": "A program with this title already exists."}, 409

    data["title"] = title
    data["classes_covered"] = clean(data.get("classes_covered"))
    data["description"] = clean(data.get("description"))
    rec = AcademicPrograms.create(data)
    return {"success": True, "message": "Program created successfully.", "data": format_program(rec)}, 200


def get_active_programs():
    records = AcademicPrograms.get_active_records()
    return {"success": True, "message": "Programs fetched successfully.",
            "data": [format_program(rec) for rec in records]}, 200


def get_all_programs():
    records = AcademicPrograms.get_all_records()
    return {"success": True, "message": "Programs fetched successfully.",
            "data": [format_program(rec) for rec in records]}, 200


def update_program(program_id, data):
    if not AcademicPrograms.get_by_id(program_id):
        return {"success": False, "message": "Program not found."}, 404

    title = clean(data.get("title"))
    if not title:
        return {"success": False, "message": "Program title is required."}, 400
    existing = AcademicPrograms.get_by_title(title)
    if existing and existing.id != program_id:
        return {"success": False, "message": "A program with this title already exists."}, 409

    data["title"] = title
    data["classes_covered"] = clean(data.get("classes_covered"))
    data["description"] = clean(data.get("description"))
    rec = AcademicPrograms.update(program_id, data)
    return {"success": True, "message": "Program updated successfully.", "data": format_program(rec)}, 200


def delete_program(program_id):
    success, error = AcademicPrograms.delete_by_id(program_id)
    return _delete_response(success, error, "Program deleted successfully.")
