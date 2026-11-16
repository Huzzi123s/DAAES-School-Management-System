from api.models.academicsModels import AcademicPrograms, SchoolClasses, Subjects
from api.models.enquiryModel import Enquiries
from api.models.galleryModel import GalleryItems
from api.models.schoolModels import AdmissionDocuments
from api.models.userModel import Users


def get_dashboard_summary():
    classes = SchoolClasses.get_all_records()

    result = {
        "classes": len(classes),
        "classes_open_for_admission": len(
            [c for c in classes if c.status == "active" and c.admissions_open]
        ),
        "subjects": len(Subjects.get_all_records()),
        "programs": len(AcademicPrograms.get_all_records()),
        "gallery_items": len(GalleryItems.get_all_records()),
        "admission_documents": len(AdmissionDocuments.get_all_records()),
        "users": len(Users.get_all_records()),
        "enquiries": Enquiries.count_by_status()
    }
    return {"success": True, "message": "Dashboard summary fetched successfully.", "data": result}, 200
