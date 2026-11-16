"""Default content taken from the frontend pages. Safe to run more than once."""

from api.models.academicsModels import AcademicPrograms, SchoolClasses, Subjects
from api.models.galleryModel import GalleryItems
from api.models.schoolModels import AdmissionDocuments, SchoolInfo

SCHOOL_INFO = {
    "name": "DAAES School",
    "tagline": "Excellence Through Education",
    "about": (
        "DAAES School is dedicated to providing quality education in a supportive and "
        "inspiring learning environment. We believe every student has the potential to "
        "achieve great things."
    ),
    "vision": (
        "To create a learning environment where students become confident, creative and "
        "successful individuals ready to contribute positively to society."
    ),
    "mission": (
        "To provide meaningful education, encourage curiosity, build strong character and "
        "help every student reach their full potential."
    ),
    "admission_notice": (
        "All admission procedures must be completed physically at the school campus. "
        "Online admission applications and registrations are not available. Parents or "
        "guardians must visit the school office for admission forms and further details."
    ),
}

CLASSES = [
    ("Playgroup", "pre_primary"),
    ("Nursery", "pre_primary"),
    ("Class 1", "primary"),
    ("Class 2", "primary"),
    ("Class 3", "primary"),
    ("Class 4", "primary"),
    ("Class 5", "primary"),
    ("Class 6", "middle"),
    ("Class 7", "middle"),
    ("Class 8", "middle"),
    ("Class 9", "secondary"),
    ("Class 10 (Matric)", "secondary"),
]

SUBJECTS = [
    ("English", "📖"),
    ("Urdu", "📝"),
    ("Islamic Studies", "☪"),
    ("Mathematics", "🔢"),
    ("Computer Studies", "💻"),
    ("Physics", "⚡"),
    ("Chemistry", "🧪"),
    ("Sindhi", "🌿"),
    ("General Knowledge", "🌍"),
    ("Pakistan Studies", "🇵🇰"),
    ("Social Studies", "🗺"),
    ("General Science", "🔬"),
]

PROGRAMS = [
    ("Primary Education", "primary", "Playgroup - Class 5",
     "Developing basic knowledge, reading, writing and essential learning skills."),
    ("Middle Education", "middle", "Class 6 - Class 8",
     "Strengthening academic concepts, independent thinking and problem-solving."),
    ("Secondary Education", "secondary", "Class 9 - Class 10 (Matric)",
     "Preparing students for matriculation examinations and future academic goals."),
]

DOCUMENTS = [
    "Child's Birth Certificate",
    "Child's B-Form",
    "Recent Passport-Size Photographs",
    "Father's CNIC Copy",
    "Mother's CNIC Copy",
]

GALLERY = [
    ("event1.jpg", "School Events", "events", "Special moments and school events."),
    ("event2.jpg", "School Celebrations", "celebrations", "Celebrating special occasions together."),
    ("class1.jpg", "Classroom Activities", "classroom", "Learning through engaging activities."),
    ("sports1.jpg", "Sports Activities", "sports", "Encouraging teamwork and participation."),
    ("campus1.jpg", "School Campus", "campus", "A glimpse of our school environment."),
    ("class2.jpg", "Learning Activities", "classroom", "Creating meaningful learning experiences."),
    ("event3.jpg", "School Functions", "events", "Memorable school gatherings."),
    ("sports2.jpg", "Games & Sports", "sports", "Promoting healthy and active participation."),
    ("celebration1.jpg", "Special Celebrations", "celebrations", "Sharing happiness and special moments."),
    ("campus2.jpg", "Our Learning Space", "campus", "An environment for learning and growth."),
    ("class3.jpg", "Creative Learning", "classroom", "Encouraging creativity and curiosity."),
    ("event4.jpg", "School Memories", "events", "Capturing special moments at DAAES School."),
]


def seed_database():
    added = {"classes": 0, "subjects": 0, "programs": 0, "documents": 0, "gallery": 0}

    # Fill empty school info fields only
    info = SchoolInfo.get_record()
    SchoolInfo.update({k: v for k, v in SCHOOL_INFO.items() if not getattr(info, k)})

    for order, (name, level) in enumerate(CLASSES, start=1):
        if not SchoolClasses.get_by_name(name):
            SchoolClasses.create({"name": name, "level": level, "sort_order": order})
            added["classes"] += 1

    for order, (name, icon) in enumerate(SUBJECTS, start=1):
        if not Subjects.get_by_name(name):
            Subjects.create({"name": name, "icon": icon, "sort_order": order})
            added["subjects"] += 1

    for order, (title, level, classes, description) in enumerate(PROGRAMS, start=1):
        if not AcademicPrograms.get_by_title(title):
            AcademicPrograms.create({"title": title, "level": level, "classes_covered": classes,
                                     "description": description, "sort_order": order})
            added["programs"] += 1

    for order, name in enumerate(DOCUMENTS, start=1):
        if not AdmissionDocuments.get_by_name(name):
            AdmissionDocuments.create({"name": name, "is_required": True, "sort_order": order})
            added["documents"] += 1

    existing_images = {rec.image_path for rec in GalleryItems.get_all_records()}
    for order, (file, title, category, description) in enumerate(GALLERY, start=1):
        path = f"assets/images/gallery/{file}"
        if path not in existing_images:
            GalleryItems.create({"title": title, "category": category, "description": description,
                                 "image_path": path, "sort_order": order})
            added["gallery"] += 1

    return added
