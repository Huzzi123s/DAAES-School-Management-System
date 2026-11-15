from flask_restx import fields

from api import academics_ns
from api.models.academicsModels import CLASS_LEVELS

# Class Payloads
create_class_payload = academics_ns.model('CreateClass', {
    'name': fields.String(required=True, description='Class name (e.g. Class 1)'),
    'level': fields.String(required=True, enum=CLASS_LEVELS, description='Class level'),
    'description': fields.String(required=False, description='Description'),
    'admissions_open': fields.Boolean(required=False, default=True, description='Admissions open for this class'),
    'sort_order': fields.Integer(required=False, default=0, description='Display order')
})

update_class_payload = academics_ns.model('UpdateClass', {
    'name': fields.String(required=True, description='Class name'),
    'level': fields.String(required=True, enum=CLASS_LEVELS, description='Class level'),
    'description': fields.String(required=False, description='Description'),
    'admissions_open': fields.Boolean(required=False, description='Admissions open for this class'),
    'sort_order': fields.Integer(required=False, description='Display order'),
    'status': fields.String(required=False, enum=['active', 'inactive'], description='Show / hide on website')
})

# Subject Payloads
create_subject_payload = academics_ns.model('CreateSubject', {
    'name': fields.String(required=True, description='Subject name'),
    'icon': fields.String(required=False, description='Icon / emoji'),
    'description': fields.String(required=False, description='Description'),
    'sort_order': fields.Integer(required=False, default=0, description='Display order')
})

update_subject_payload = academics_ns.model('UpdateSubject', {
    'name': fields.String(required=True, description='Subject name'),
    'icon': fields.String(required=False, description='Icon / emoji'),
    'description': fields.String(required=False, description='Description'),
    'sort_order': fields.Integer(required=False, description='Display order'),
    'status': fields.String(required=False, enum=['active', 'inactive'], description='Show / hide on website')
})

# Program Payloads
create_program_payload = academics_ns.model('CreateProgram', {
    'title': fields.String(required=True, description='Program title (e.g. Primary Education)'),
    'level': fields.String(required=True, enum=CLASS_LEVELS, description='Program level'),
    'classes_covered': fields.String(required=False, description='Classes covered (e.g. Class 1 - Class 5)'),
    'description': fields.String(required=False, description='Description'),
    'sort_order': fields.Integer(required=False, default=0, description='Display order')
})

update_program_payload = academics_ns.model('UpdateProgram', {
    'title': fields.String(required=True, description='Program title'),
    'level': fields.String(required=True, enum=CLASS_LEVELS, description='Program level'),
    'classes_covered': fields.String(required=False, description='Classes covered'),
    'description': fields.String(required=False, description='Description'),
    'sort_order': fields.Integer(required=False, description='Display order'),
    'status': fields.String(required=False, enum=['active', 'inactive'], description='Show / hide on website')
})
