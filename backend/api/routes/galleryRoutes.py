from flask import request
from flask_restx import Resource, abort

from api import gallery_ns
from api.payloads.galleryPayloads import *
from api.services import read_token_required, write_token_required, delete_token_required
from api.controllers.galleryController import *

import traceback


# ============================================
# Gallery Routes
# ============================================

@gallery_ns.route("/create")
@gallery_ns.doc("Create Gallery Item")
class CreateGalleryItem(Resource):
    @gallery_ns.doc("Create Gallery Item")
    @gallery_ns.expect(create_gallery_payload)
    @gallery_ns.response(200, "Gallery item created successfully.")
    @gallery_ns.response(400, "Validation Error")
    @gallery_ns.doc(security=["Authorization"])
    @write_token_required
    def post(self):
        """Upload a new gallery image (form-data)"""
        data = create_gallery_payload.parse_args()
        try:
            result, status_code = create_gallery_item(data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@gallery_ns.route("/all")
@gallery_ns.doc("Get Gallery")
class GetActiveGallery(Resource):
    @gallery_ns.doc("Get Gallery", params={"category": "events / celebrations / classroom / sports / campus (optional)"})
    @gallery_ns.response(200, "Gallery fetched successfully.")
    def get(self):
        """Get active gallery items (public - for the website)"""
        try:
            result, status_code = get_active_gallery(category=request.args.get("category"))
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@gallery_ns.route("/admin/all")
@gallery_ns.doc("Get All Gallery Items (Admin)")
class GetAllGallery(Resource):
    @gallery_ns.doc("Get All Gallery Items (Admin)")
    @gallery_ns.response(200, "Gallery fetched successfully.")
    @gallery_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get active and inactive gallery items"""
        try:
            result, status_code = get_all_gallery()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@gallery_ns.route("/categories")
@gallery_ns.doc("Get Gallery Categories")
class GetGalleryCategories(Resource):
    @gallery_ns.doc("Get Gallery Categories")
    @gallery_ns.response(200, "Categories fetched successfully.")
    def get(self):
        """Get gallery categories with image counts (public)"""
        try:
            result, status_code = get_gallery_categories()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@gallery_ns.route("/update/<int:item_id>")
@gallery_ns.doc("Update Gallery Item")
class UpdateGalleryItem(Resource):
    @gallery_ns.doc("Update Gallery Item")
    @gallery_ns.expect(update_gallery_payload)
    @gallery_ns.response(200, "Gallery item updated successfully.")
    @gallery_ns.response(404, "Gallery item not found.")
    @gallery_ns.doc(security=["Authorization"])
    @write_token_required
    def put(self, item_id):
        """Update a gallery item (form-data, image optional)"""
        data = update_gallery_payload.parse_args()
        try:
            result, status_code = update_gallery_item(item_id, data)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})


@gallery_ns.route("/delete/<int:item_id>")
@gallery_ns.doc("Delete Gallery Item")
class DeleteGalleryItem(Resource):
    @gallery_ns.doc("Delete Gallery Item")
    @gallery_ns.response(200, "Gallery item deleted successfully.")
    @gallery_ns.response(404, "Gallery item not found.")
    @gallery_ns.doc(security=["Authorization"])
    @delete_token_required
    def delete(self, item_id):
        """Delete (soft) a gallery item and remove its uploaded image"""
        try:
            result, status_code = delete_gallery_item(item_id)
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})
