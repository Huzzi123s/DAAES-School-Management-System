from flask import request
from flask_restx import Resource, abort

from api import academics_ns
from api.payloads.academicsPayloads import *
from api.services import read_token_required, write_token_required, delete_token_required
from api.controllers.academicsController import *

import traceback


# ============================================
# Class Routes
# ============================================

@academics_ns.route("/classes/create")
@academics_ns.doc("Create Class")
class CreateClass(Resource):
    @academics_ns.doc("Create Class")
    @academics_ns.expect(create_class_payload)
    @academics_ns.response(200, "Class created successfully.")
    @academics_ns.response(400, "Validation Error")
    @academics_ns.doc(security=["Authorization"])
    @write_token_required
    def post(self):
        """Create a new class"""
        data = academics_ns.payload
        try:
            result, status_code = create_class(data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/classes/all")
@academics_ns.doc("Get Classes")
class GetActiveClasses(Resource):
    @academics_ns.doc("Get Classes", params={"level": "pre_primary / primary / middle / secondary (optional)"})
    @academics_ns.response(200, "Classes fetched successfully.")
    def get(self):
        """Get active classes (public - for the website)"""
        try:
            result, status_code = get_active_classes(level=request.args.get("level"))
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/classes/admin/all")
@academics_ns.doc("Get All Classes (Admin)")
class GetAllClasses(Resource):
    @academics_ns.doc("Get All Classes (Admin)")
    @academics_ns.response(200, "Classes fetched successfully.")
    @academics_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get active and inactive classes"""
        try:
            result, status_code = get_all_classes()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/classes/<int:class_id>")
@academics_ns.doc("Get Class")
class GetClass(Resource):
    @academics_ns.doc("Get Class")
    @academics_ns.response(200, "Class fetched successfully.")
    @academics_ns.response(404, "Class not found.")
    def get(self, class_id):
        """Get a class by ID"""
        try:
            result, status_code = get_class_by_id(class_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/classes/update/<int:class_id>")
@academics_ns.doc("Update Class")
class UpdateClass(Resource):
    @academics_ns.doc("Update Class")
    @academics_ns.expect(update_class_payload)
    @academics_ns.response(200, "Class updated successfully.")
    @academics_ns.response(404, "Class not found.")
    @academics_ns.doc(security=["Authorization"])
    @write_token_required
    def put(self, class_id):
        """Update a class"""
        data = academics_ns.payload
        try:
            result, status_code = update_class(class_id, data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/classes/delete/<int:class_id>")
@academics_ns.doc("Delete Class")
class DeleteClass(Resource):
    @academics_ns.doc("Delete Class")
    @academics_ns.response(200, "Class deleted successfully.")
    @academics_ns.response(404, "Class not found.")
    @academics_ns.doc(security=["Authorization"])
    @delete_token_required
    def delete(self, class_id):
        """Delete (soft) a class"""
        try:
            result, status_code = delete_class(class_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


# ============================================
# Subject Routes
# ============================================

@academics_ns.route("/subjects/create")
@academics_ns.doc("Create Subject")
class CreateSubject(Resource):
    @academics_ns.doc("Create Subject")
    @academics_ns.expect(create_subject_payload)
    @academics_ns.response(200, "Subject created successfully.")
    @academics_ns.response(400, "Validation Error")
    @academics_ns.doc(security=["Authorization"])
    @write_token_required
    def post(self):
        """Create a new subject"""
        data = academics_ns.payload
        try:
            result, status_code = create_subject(data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/subjects/all")
@academics_ns.doc("Get Subjects")
class GetActiveSubjects(Resource):
    @academics_ns.doc("Get Subjects")
    @academics_ns.response(200, "Subjects fetched successfully.")
    def get(self):
        """Get active subjects (public - for the website)"""
        try:
            result, status_code = get_active_subjects()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/subjects/admin/all")
@academics_ns.doc("Get All Subjects (Admin)")
class GetAllSubjects(Resource):
    @academics_ns.doc("Get All Subjects (Admin)")
    @academics_ns.response(200, "Subjects fetched successfully.")
    @academics_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get active and inactive subjects"""
        try:
            result, status_code = get_all_subjects()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/subjects/update/<int:subject_id>")
@academics_ns.doc("Update Subject")
class UpdateSubject(Resource):
    @academics_ns.doc("Update Subject")
    @academics_ns.expect(update_subject_payload)
    @academics_ns.response(200, "Subject updated successfully.")
    @academics_ns.response(404, "Subject not found.")
    @academics_ns.doc(security=["Authorization"])
    @write_token_required
    def put(self, subject_id):
        """Update a subject"""
        data = academics_ns.payload
        try:
            result, status_code = update_subject(subject_id, data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/subjects/delete/<int:subject_id>")
@academics_ns.doc("Delete Subject")
class DeleteSubject(Resource):
    @academics_ns.doc("Delete Subject")
    @academics_ns.response(200, "Subject deleted successfully.")
    @academics_ns.response(404, "Subject not found.")
    @academics_ns.doc(security=["Authorization"])
    @delete_token_required
    def delete(self, subject_id):
        """Delete (soft) a subject"""
        try:
            result, status_code = delete_subject(subject_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


# ============================================
# Program Routes
# ============================================

@academics_ns.route("/programs/create")
@academics_ns.doc("Create Program")
class CreateProgram(Resource):
    @academics_ns.doc("Create Program")
    @academics_ns.expect(create_program_payload)
    @academics_ns.response(200, "Program created successfully.")
    @academics_ns.response(400, "Validation Error")
    @academics_ns.doc(security=["Authorization"])
    @write_token_required
    def post(self):
        """Create a new academic program"""
        data = academics_ns.payload
        try:
            result, status_code = create_program(data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/programs/all")
@academics_ns.doc("Get Programs")
class GetActivePrograms(Resource):
    @academics_ns.doc("Get Programs")
    @academics_ns.response(200, "Programs fetched successfully.")
    def get(self):
        """Get active academic programs (public - for the website)"""
        try:
            result, status_code = get_active_programs()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/programs/admin/all")
@academics_ns.doc("Get All Programs (Admin)")
class GetAllPrograms(Resource):
    @academics_ns.doc("Get All Programs (Admin)")
    @academics_ns.response(200, "Programs fetched successfully.")
    @academics_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get active and inactive academic programs"""
        try:
            result, status_code = get_all_programs()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/programs/update/<int:program_id>")
@academics_ns.doc("Update Program")
class UpdateProgram(Resource):
    @academics_ns.doc("Update Program")
    @academics_ns.expect(update_program_payload)
    @academics_ns.response(200, "Program updated successfully.")
    @academics_ns.response(404, "Program not found.")
    @academics_ns.doc(security=["Authorization"])
    @write_token_required
    def put(self, program_id):
        """Update an academic program"""
        data = academics_ns.payload
        try:
            result, status_code = update_program(program_id, data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@academics_ns.route("/programs/delete/<int:program_id>")
@academics_ns.doc("Delete Program")
class DeleteProgram(Resource):
    @academics_ns.doc("Delete Program")
    @academics_ns.response(200, "Program deleted successfully.")
    @academics_ns.response(404, "Program not found.")
    @academics_ns.doc(security=["Authorization"])
    @delete_token_required
    def delete(self, program_id):
        """Delete (soft) an academic program"""
        try:
            result, status_code = delete_program(program_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})
