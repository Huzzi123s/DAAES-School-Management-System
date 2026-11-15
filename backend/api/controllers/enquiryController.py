from api.models.enquiryModel import ENQUIRY_STATUSES, ENQUIRY_TYPES, Enquiries
from api.services import clean, format_date, is_valid_email, is_valid_phone


def format_enquiry(rec):
    return {
        "id": rec.id,
        "name": rec.name,
        "email": rec.email,
        "phone": rec.phone,
        "enquiry_type": rec.enquiry_type,
        "subject": rec.subject,
        "message": rec.message,
        "status": rec.status,
        "admin_notes": rec.admin_notes,
        "ip_address": rec.ip_address,
        "created_at": format_date(rec.created_at),
        "updated_at": format_date(rec.updated_at)
    }


def create_enquiry(data, ip_address=None):
    name = clean(data.get("name"))
    email = clean(data.get("email"))
    phone = clean(data.get("phone"))
    message = clean(data.get("message"))

    if not name or len(name) < 2:
        return {"success": False, "message": "Please enter your name."}, 400
    if not email and not phone:
        return {"success": False, "message": "Please provide an email address or phone number."}, 400
    if email and not is_valid_email(email):
        return {"success": False, "message": "Please enter a valid email address."}, 400
    if phone and not is_valid_phone(phone):
        return {"success": False, "message": "Please enter a valid phone number."}, 400
    if not message or len(message) < 10:
        return {"success": False, "message": "Message must be at least 10 characters."}, 400

    rec = Enquiries.create({
        "name": name,
        "email": email.lower() if email else None,
        "phone": phone,
        "enquiry_type": data.get("enquiry_type", "general"),
        "subject": clean(data.get("subject")),
        "message": message,
        "ip_address": ip_address
    })
    return {
        "success": True,
        "message": "Thank you! Your enquiry has been received. Our office will contact you soon.",
        "data": {"id": rec.id, "name": rec.name, "status": rec.status}
    }, 200


def get_all_enquiries(data):
    status = data.get("status")
    enquiry_type = data.get("enquiry_type")

    if status and status not in ENQUIRY_STATUSES:
        return {"success": False, "message": "Invalid status."}, 400
    if enquiry_type and enquiry_type not in ENQUIRY_TYPES:
        return {"success": False, "message": "Invalid enquiry type."}, 400

    records = Enquiries.get_all_records(status=status, enquiry_type=enquiry_type)
    return {"success": True, "message": "Enquiries fetched successfully.",
            "data": [format_enquiry(rec) for rec in records]}, 200


def get_enquiry_by_id(enquiry_id):
    rec = Enquiries.get_by_id(enquiry_id)
    if not rec:
        return {"success": False, "message": "Enquiry not found."}, 404
    return {"success": True, "message": "Enquiry fetched successfully.", "data": format_enquiry(rec)}, 200


def update_enquiry_status(enquiry_id, data):
    if "admin_notes" in data:
        data["admin_notes"] = clean(data["admin_notes"])

    rec = Enquiries.update_status(enquiry_id, data)
    if not rec:
        return {"success": False, "message": "Enquiry not found."}, 404
    return {"success": True, "message": "Enquiry updated successfully.", "data": format_enquiry(rec)}, 200


def get_enquiry_stats():
    return {"success": True, "message": "Enquiry stats fetched successfully.",
            "data": Enquiries.count_by_status()}, 200


def delete_enquiry(enquiry_id):
    if not Enquiries.delete_by_id(enquiry_id):
        return {"success": False, "message": "Enquiry not found."}, 404
    return {"success": True, "message": "Enquiry deleted successfully."}, 200
