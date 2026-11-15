from flask_restx import Resource, abort

from api import dashboard_ns
from api.services import read_token_required
from api.controllers.dashboardController import *

import traceback


# ============================================
# Dashboard Routes
# ============================================

@dashboard_ns.route("/summary")
@dashboard_ns.doc("Get Dashboard Summary")
class GetDashboardSummary(Resource):
    @dashboard_ns.doc("Get Dashboard Summary")
    @dashboard_ns.response(200, "Dashboard summary fetched successfully.")
    @dashboard_ns.doc(security=["Authorization"])
    @read_token_required
    def get(self):
        """Get counts of classes, subjects, gallery, users and enquiries"""
        try:
            result, status_code = get_dashboard_summary()
            return result, status_code
        except Exception as e:
            print(e)
            traceback.print_exc()
            abort(400, {"success": False, "message": f"Error: {str(e)}"})
