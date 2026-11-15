from flask import request
from flask_restx import Resource, abort

from api import enquiry_ns
from api.payloads.enquiryPayloads import *
from api.services import read_token_required, write_token_required, delete_token_required
from api.controllers.enquiryController import *

import traceback


# ============================================
# Enquiry Routes
# ============================================

@enquiry_ns.route("/create")
@enquiry_ns.doc("Create Enquiry")
class CreateEnquiry(Resource):
    @enquiry_ns.doc("Create Enquiry")
    @enquiry_ns.expect(create_enquiry_payload)
    @enquiry_ns.response(200, "Enquiry received.")
    @enquiry_ns.response(400, "Validation Error")
    def post(self):
        """Submit a contact / admission enquiry (public - website contact form)"""
        data = enquiry_ns.payload
        try:
            result, status_code = create_enquiry(data, ip_address=request.remote_addr)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@enquiry_ns.route("/all")
@enquiry_ns.doc("Get All Enquiries")
class GetAllEnquiries(Resource):
    @enquiry_ns.doc("Get All Enquiries", params={
        "status": "new / in_progress / resolved / closed (optional)",
        "enquiry_type": "general / admission / academics / other (optional)"
    })
    @enquiry_ns.response(200, "Enquiries fetched successfully.")
    @enquiry_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get all enquiries (newest first)"""
        try:
            result, status_code = get_all_enquiries(request.args.to_dict())
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@enquiry_ns.route("/stats")
@enquiry_ns.doc("Get Enquiry Stats")
class GetEnquiryStats(Resource):
    @enquiry_ns.doc("Get Enquiry Stats")
    @enquiry_ns.response(200, "Enquiry stats fetched successfully.")
    @enquiry_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get enquiry count per status"""
        try:
            result, status_code = get_enquiry_stats()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@enquiry_ns.route("/<int:enquiry_id>")
@enquiry_ns.doc("Get Enquiry")
class GetEnquiry(Resource):
    @enquiry_ns.doc("Get Enquiry")
    @enquiry_ns.response(200, "Enquiry fetched successfully.")
    @enquiry_ns.response(404, "Enquiry not found.")
    @enquiry_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self, enquiry_id):
        """Get an enquiry by ID"""
        try:
            result, status_code = get_enquiry_by_id(enquiry_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@enquiry_ns.route("/update-status/<int:enquiry_id>")
@enquiry_ns.doc("Update Enquiry Status")
class UpdateEnquiryStatus(Resource):
    @enquiry_ns.doc("Update Enquiry Status")
    @enquiry_ns.expect(update_enquiry_status_payload)
    @enquiry_ns.response(200, "Enquiry updated successfully.")
    @enquiry_ns.response(404, "Enquiry not found.")
    @enquiry_ns.doc(security=["Authorization"])
    @write_token_required
    def put(self, enquiry_id):
        """Update an enquiry's status and staff notes"""
        data = enquiry_ns.payload
        try:
            result, status_code = update_enquiry_status(enquiry_id, data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@enquiry_ns.route("/delete/<int:enquiry_id>")
@enquiry_ns.doc("Delete Enquiry")
class DeleteEnquiry(Resource):
    @enquiry_ns.doc("Delete Enquiry")
    @enquiry_ns.response(200, "Enquiry deleted successfully.")
    @enquiry_ns.response(404, "Enquiry not found.")
    @enquiry_ns.doc(security=["Authorization"])
    @delete_token_required
    def delete(self, enquiry_id):
        """Delete an enquiry"""
        try:
            result, status_code = delete_enquiry(enquiry_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})
