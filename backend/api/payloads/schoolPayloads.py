from flask_restx import fields

from api import school_ns

# School Info Payloads
update_school_info_payload = school_ns.model('UpdateSchoolInfo', {
    'name': fields.String(required=False, description='School name'),
    'tagline': fields.String(required=False, description='Tagline'),
    'address': fields.String(required=False, description='Address'),
    'phone': fields.String(required=False, description='Phone number'),
    'email': fields.String(required=False, description='Email address'),
    'office_hours': fields.String(required=False, description='Office hours'),
    'about': fields.String(required=False, description='About the school'),
    'vision': fields.String(required=False, description='Vision'),
    'mission': fields.String(required=False, description='Mission'),
    'admission_notice': fields.String(required=False, description='Admission notice')
})

# Admission Document Payloads
create_document_payload = school_ns.model('CreateAdmissionDocument', {
    'name': fields.String(required=True, description="Document name (e.g. Child's B-Form)"),
    'description': fields.String(required=False, description='Description'),
    'is_required': fields.Boolean(required=False, default=True, description='Is this document required'),
    'sort_order': fields.Integer(required=False, default=0, description='Display order')
})

update_document_payload = school_ns.model('UpdateAdmissionDocument', {
    'name': fields.String(required=True, description='Document name'),
    'description': fields.String(required=False, description='Description'),
    'is_required': fields.Boolean(required=False, description='Is this document required'),
    'sort_order': fields.Integer(required=False, description='Display order'),
    'status': fields.String(required=False, enum=['active', 'inactive'], description='Show / hide on website')
})
