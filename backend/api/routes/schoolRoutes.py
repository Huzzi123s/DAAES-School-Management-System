from flask_restx import Resource, abort

from api import school_ns
from api.payloads.schoolPayloads import *
from api.services import read_token_required, write_token_required, delete_token_required
from api.controllers.schoolController import *

import traceback


# ============================================
# School Info Routes
# ============================================

@school_ns.route("/info")
@school_ns.doc("Get School Info")
class GetSchoolInfo(Resource):
    @school_ns.doc("Get School Info")
    @school_ns.response(200, "School info fetched successfully.")
    def get(self):
        """Get school name, address, phone, email, vision, mission ... (public)"""
        try:
            result, status_code = get_school_info()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@school_ns.route("/info/update")
@school_ns.doc("Update School Info")
class UpdateSchoolInfo(Resource):
    @school_ns.doc("Update School Info")
    @school_ns.expect(update_school_info_payload)
    @school_ns.response(200, "School info updated successfully.")
    @school_ns.response(400, "Validation Error")
    @school_ns.doc(security=["Authorization"])
    @write_token_required
    def put(self):
        """Update school details (send only the fields you want to change)"""
        data = school_ns.payload
        try:
            result, status_code = update_school_info(data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


# ============================================
# Admission Document Routes
# ============================================

@school_ns.route("/documents/create")
@school_ns.doc("Create Admission Document")
class CreateDocument(Resource):
    @school_ns.doc("Create Admission Document")
    @school_ns.expect(create_document_payload)
    @school_ns.response(200, "Document created successfully.")
    @school_ns.response(400, "Validation Error")
    @school_ns.doc(security=["Authorization"])
    @write_token_required
    def post(self):
        """Create a new admission document"""
        data = school_ns.payload
        try:
            result, status_code = create_document(data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@school_ns.route("/documents/all")
@school_ns.doc("Get Admission Documents")
class GetActiveDocuments(Resource):
    @school_ns.doc("Get Admission Documents")
    @school_ns.response(200, "Documents fetched successfully.")
    def get(self):
        """Get active admission documents (public - for the website)"""
        try:
            result, status_code = get_active_documents()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@school_ns.route("/documents/admin/all")
@school_ns.doc("Get All Admission Documents (Admin)")
class GetAllDocuments(Resource):
    @school_ns.doc("Get All Admission Documents (Admin)")
    @school_ns.response(200, "Documents fetched successfully.")
    @school_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get active and inactive admission documents"""
        try:
            result, status_code = get_all_documents()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@school_ns.route("/documents/update/<int:document_id>")
@school_ns.doc("Update Admission Document")
class UpdateDocument(Resource):
    @school_ns.doc("Update Admission Document")
    @school_ns.expect(update_document_payload)
    @school_ns.response(200, "Document updated successfully.")
    @school_ns.response(404, "Document not found.")
    @school_ns.doc(security=["Authorization"])
    @write_token_required
    def put(self, document_id):
        """Update an admission document"""
        data = school_ns.payload
        try:
            result, status_code = update_document(document_id, data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@school_ns.route("/documents/delete/<int:document_id>")
@school_ns.doc("Delete Admission Document")
class DeleteDocument(Resource):
    @school_ns.doc("Delete Admission Document")
    @school_ns.response(200, "Document deleted successfully.")
    @school_ns.response(404, "Document not found.")
    @school_ns.doc(security=["Authorization"])
    @delete_token_required
    def delete(self, document_id):
        """Delete (soft) an admission document"""
        try:
            result, status_code = delete_document(document_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})
