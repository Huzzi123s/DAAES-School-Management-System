from flask_restx import fields

from api import enquiry_ns
from api.models.enquiryModel import ENQUIRY_STATUSES, ENQUIRY_TYPES

# Enquiry Payloads
create_enquiry_payload = enquiry_ns.model('CreateEnquiry', {
    'name': fields.String(required=True, description='Parent / visitor name'),
    'email': fields.String(required=False, description='Email (email or phone is required)'),
    'phone': fields.String(required=False, description='Phone (email or phone is required)'),
    'enquiry_type': fields.String(required=False, enum=ENQUIRY_TYPES, default='general', description='Enquiry type'),
    'subject': fields.String(required=False, description='Subject'),
    'message': fields.String(required=True, description='Message')
})

update_enquiry_status_payload = enquiry_ns.model('UpdateEnquiryStatus', {
    'status': fields.String(required=True, enum=ENQUIRY_STATUSES, description='Enquiry status'),
    'admin_notes': fields.String(required=False, description='Notes for staff')
})
