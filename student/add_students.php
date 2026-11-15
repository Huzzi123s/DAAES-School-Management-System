
<?php

include '../config/database.php';

// =====================================
// HELPER
// =====================================

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$message = '';
$message_type = '';

$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');

$form_gender = '';
$form_section_id = 0;

// =====================================
// FETCH CLASSES
// =====================================

$classes = [];

$class_result = $conn->query("
    SELECT MIN(id) AS id, MIN(class_name) AS class_name
    FROM classes
    GROUP BY LOWER(TRIM(class_name))
    ORDER BY MIN(id)
");

if (!$class_result) {
    die("Class Error: " . e($conn->error));
}

while ($class = $class_result->fetch_assoc()) {
    $classes[] = $class;
}

// =====================================
// FETCH SECTIONS A AND B
// =====================================

$section_data = [];

$section_result = $conn->query("
    SELECT
        id,
        class_id,
        UPPER(TRIM(section_name)) AS section_name
    FROM sections
    WHERE UPPER(TRIM(section_name)) IN ('A', 'B')
    ORDER BY class_id, section_name
");

if (!$section_result) {
    die("Section Error: " . e($conn->error));
}

while ($section = $section_result->fetch_assoc()) {
    $section_data[] = $section;
}

// =====================================
// ADD NEW STUDENT
// =====================================

if ($is_post) {

    $full_name = trim($_POST['full_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');

    $dob = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';

    $form_gender = $gender;

    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['parent_phone'] ?? '');

    $class_id = (int)($_POST['class_id'] ?? 0);
    $section_id = (int)($_POST['section_id'] ?? 0);

    $form_section_id = $section_id;

    $admission_date = $_POST['admission_date'] ?? '';
    $academic_year = trim($_POST['academic_year'] ?? '');

    $photo = null;
    $uploaded_file = null;
    $transaction_started = false;

    $upload_dir = __DIR__ . '/../assets/uploads/students/';

    try {

        // =================================
        // REQUIRED FIELD VALIDATION
        // =================================

        if (
            $full_name === '' ||
            $father_name === '' ||
            $dob === '' ||
            $gender === '' ||
            $address === '' ||
            $phone === '' ||
            $class_id <= 0 ||
            $section_id <= 0 ||
            $admission_date === '' ||
            $academic_year === ''
        ) {
            throw new Exception("Please fill all required fields.");
        }

        // =================================
        // PHONE NUMBER VALIDATION
        // EXACTLY 11 DIGITS
        // =================================

        if (!preg_match('/^[0-9]{11}$/', $phone)) {
            throw new Exception(
                "Parent contact number must contain exactly 11 digits."
            );
        }

        // =================================
        // GENDER VALIDATION
        // =================================

        if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
            throw new Exception("Invalid gender selected.");
        }

        // =================================
        // VERIFY CLASS AND SECTION
        // =================================

        $check = $conn->prepare("
            SELECT id
            FROM sections
            WHERE id = ?
              AND class_id = ?
              AND UPPER(TRIM(section_name)) IN ('A', 'B')
            LIMIT 1
        ");

        if (!$check) {
            throw new Exception($conn->error);
        }

        $check->bind_param("ii", $section_id, $class_id);

        if (!$check->execute()) {
            throw new Exception($check->error);
        }

        $valid = $check->get_result()->num_rows > 0;

        $check->close();

        if (!$valid) {
            throw new Exception("Invalid class or section selected.");
        }

        // =================================
        // PHOTO UPLOAD
        // =================================

        if (
            isset($_FILES['photo']) &&
            $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Photo upload failed.");
            }

            if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Photo must be below 2 MB.");
            }

            $image_info = @getimagesize($_FILES['photo']['tmp_name']);

            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $mime = $image_info['mime'] ?? '';

            if (!$image_info || !isset($allowed_types[$mime])) {
                throw new Exception(
                    "Only valid JPG, PNG and WEBP images are allowed."
                );
            }

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception(
                        "Unable to create student upload folder."
                    );
                }
            }

            $file_name = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime];

            $uploaded_file = $upload_dir . $file_name;

            if (!move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                $uploaded_file
            )) {
                throw new Exception("Unable to save student photo.");
            }

            $photo = $file_name;
        }

        // =================================
        // DATABASE TRANSACTION
        // =================================

        $conn->begin_transaction();
        $transaction_started = true;

        // =================================
        // AUTOMATIC STUDENT ID
        // =================================

        $student_id = "DAAES-" . date('Y') . "-" .
            strtoupper(bin2hex(random_bytes(4)));

        // =================================
        // AUTOMATIC QR TOKEN
        // =================================

        $qr_token = bin2hex(random_bytes(16));

        $status = 'active';

        // =================================
        // INSERT STUDENT
        // =================================

        $stmt = $conn->prepare("
            INSERT INTO students
            (
                student_id,
                full_name,
                father_name,
                mother_name,
                gender,
                date_of_birth,
                admission_date,
                phone,
                address,
                photo,
                qr_token,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "ssssssssssss",
            $student_id,
            $full_name,
            $father_name,
            $mother_name,
            $gender,
            $dob,
            $admission_date,
            $phone,
            $address,
            $photo,
            $qr_token,
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $new_student_id = $conn->insert_id;

        $stmt->close();

        // =================================
        // GENERATE SECTION-WISE ROLL NUMBER
        // ACTIVE STUDENTS ONLY
        // =================================

        $count = $conn->prepare("
            SELECT
                MAX(CAST(roll_number AS UNSIGNED)) AS last_roll
            FROM enrollments
            WHERE class_id = ?
              AND section_id = ?
              AND academic_year = ?
              AND status = 'active'
        ");

        if (!$count) {
            throw new Exception($conn->error);
        }

        $count->bind_param(
            "iis",
            $class_id,
            $section_id,
            $academic_year
        );

        if (!$count->execute()) {
            throw new Exception($count->error);
        }

        $count_result = $count->get_result()->fetch_assoc();

        $count->close();

        $last_roll = (int)($count_result['last_roll'] ?? 0);

        $roll_number = str_pad(
            $last_roll + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        // =================================
        // CREATE ENROLLMENT
        // =================================

        $enrollment = $conn->prepare("
            INSERT INTO enrollments
            (
                student_id,
                class_id,
                section_id,
                academic_year,
                roll_number,
                enrollment_date,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$enrollment) {
            throw new Exception($conn->error);
        }

        $enrollment->bind_param(
            "iiissss",
            $new_student_id,
            $class_id,
            $section_id,
            $academic_year,
            $roll_number,
            $admission_date,
            $status
        );

        if (!$enrollment->execute()) {
            throw new Exception($enrollment->error);
        }

        $enrollment->close();

        // =================================
        // COMMIT
        // =================================

        $conn->commit();

        $transaction_started = false;

        header("Location: add_students.php?added=1");
        exit;

    } catch (Throwable $ex) {

        if ($transaction_started) {
            $conn->rollback();
        }

        if ($uploaded_file && is_file($uploaded_file)) {
            unlink($uploaded_file);
        }

        $message = "Admission failed: " . $ex->getMessage();

        $message_type = 'error';
    }
}

// =====================================
// SUCCESS MESSAGE
// =====================================

if (isset($_GET['added'])) {
    $message = "Student admitted successfully!";
    $message_type = "success";
}

// =====================================
// SEARCH STUDENTS
// =====================================

$search = trim($_GET['search'] ?? '');

$search_term = "%{$search}%";

$stmt = $conn->prepare("
    SELECT
        s.*,
        e.roll_number,
        e.academic_year,
        c.class_name,
        sec.section_name
    FROM students s

    LEFT JOIN enrollments e ON e.id = (
        SELECT MAX(e2.id)
        FROM enrollments e2
        WHERE e2.student_id = s.id
    )

    LEFT JOIN classes c ON c.id = e.class_id

    LEFT JOIN sections sec ON sec.id = e.section_id

    WHERE s.full_name LIKE ?
       OR s.student_id LIKE ?

    ORDER BY s.id DESC
");

if (!$stmt) {
    die("Student Query Error: " . e($conn->error));
}

$stmt->bind_param("ss", $search_term, $search_term);

$stmt->execute();

$students = $stmt->get_result();

// =====================================
// DASHBOARD SETTINGS
// =====================================

$page_title = 'Add Student';
$current_page = 'students';

ob_start();

?>

<style>

/* =====================================
   HIDE CLOSE BUTTONS
===================================== */

.student-add-page .close,
.student-add-page .close-btn,
.student-add-page .modal-close,
.student-add-page .popup-close,
.student-add-page .close-modal,
.student-add-page [aria-label="Close"] {
    display: none !important;
}

/* =====================================
   STUDENT FORM CARDS
===================================== */

.student-add-card,
.student-list-card {
    background: #ffffff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.06);
    margin-bottom: 25px;
    box-sizing: border-box;
}

.student-add-page {
    width: 100%;
}

/* =====================================
   FORM GRID
===================================== */

.student-add-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.student-add-form .form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 0;
}

.student-add-form label {
    font-weight: 600;
    color: #263449;
}

.student-add-form input:not([type="radio"]):not([type="file"]),
.student-add-form select,
.student-add-form textarea,
.student-search-form input {
    width: 100%;
    min-height: 43px;
    padding: 11px 12px;
    border: 1px solid #d5dbe5;
    border-radius: 7px;
    box-sizing: border-box;
    font: inherit;
}

.student-add-form textarea {
    min-height: 90px;
    resize: vertical;
}

.student-add-form .full-width,
.student-section-title {
    grid-column: 1 / -1;
}

/* =====================================
   SECTION HEADINGS
===================================== */

.student-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #17365d;
    padding-bottom: 10px;
    border-bottom: 1px solid #e6eaf0;
    margin: 10px 0 0;
}

/* =====================================
   GENDER AND SECTION CARDS
===================================== */

.choice-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    width: 100%;
}

.choice-card {
    position: relative;
    min-height: 38px;
    padding: 9px 12px;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    border: 1px solid #d2dbea;
    border-radius: 8px;

    background: #ffffff;
    color: #263b56;

    font-size: 13px;
    font-weight: 600;

    cursor: pointer;
    box-sizing: border-box;

    transition: all .2s ease;
}

.choice-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
    pointer-events: none;
}

.choice-card i {
    font-size: 13px;
    color: #263b56;
    transition: color .2s ease;
}

.choice-card:has(input[type="radio"]:checked) {
    background: #19395f;
    color: #ffffff;
    border-color: #d5a52b;
    box-shadow: 0 2px 7px rgba(25, 57, 95, .13);
}

.choice-card:has(input[type="radio"]:checked) i {
    color: #ffffff;
}

.choice-card:hover {
    border-color: #19395f;
    transform: translateY(-1px);
}

.choice-card:has(input[type="radio"]:focus-visible) {
    outline: 3px solid rgba(25, 57, 95, .25);
    outline-offset: 3px;
}

.choice-card.section-hidden {
    display: none !important;
}

.student-section-message {
    display: none;
    color: #a32121;
    font-size: 12px;
    margin-top: 5px;
}

/* =====================================
   PHOTO
===================================== */

.student-add-form input[type="file"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #d5dbe5;
    border-radius: 7px;
}

/* =====================================
   BUTTONS
===================================== */

.student-add-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 22px;
}

.student-add-actions .btn {
    width: auto;
    min-height: 43px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

/* =====================================
   MESSAGES
===================================== */

.student-message {
    padding: 12px 15px;
    border-radius: 7px;
    margin-bottom: 18px;
}

.student-message.success {
    background: #e7f7ed;
    color: #167342;
}

.student-message.error {
    background: #fdeaea;
    color: #a32121;
}

/* =====================================
   SEARCH
===================================== */

.student-search-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 18px 0;
}

.student-search-form input {
    flex: 1;
    min-width: 200px;
}

/* =====================================
   TABLE
===================================== */

.student-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.student-table-wrap table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

.student-table-wrap th,
.student-table-wrap td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e9edf2;
}

.student-table-wrap th {
    background: #f4f6fa;
    color: #263449;
    font-weight: 700;
}

.student-photo {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.student-status-active {
    color: #168b50;
    font-weight: 700;
}

.student-status-inactive {
    color: #d04444;
    font-weight: 700;
}

/* =====================================
   RESPONSIVE
===================================== */

@media(max-width:650px) {

    .student-add-card,
    .student-list-card {
        padding: 16px;
    }

    .student-add-form {
        grid-template-columns: 1fr;
    }

    .student-add-form .full-width,
    .student-section-title {
        grid-column: auto;
    }

    .student-search-form {
        flex-direction: column;
    }

    .student-search-form input,
    .student-search-form .btn {
        width: 100%;
    }

    .student-add-actions {
        flex-direction: column;
    }

    .student-add-actions .btn {
        width: 100%;
    }
}

</style>

<div class="student-add-page">

    <!-- PAGE HEADING -->

    <div class="dashboard-heading">
        <div>
            <h1>New Student Admission</h1>
            <p>Register students and manage their admission records.</p>
        </div>
    </div>

    <!-- STUDENT ADMISSION FORM -->

    <div class="student-add-card">

        <h2>New Student Admission</h2>

        <p>Enter student personal details and enrollment information.</p>

        <?php if ($message !== ''): ?>

            <div class="student-message <?= e($message_type) ?>">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="student-add-form">

                <h3 class="student-section-title">Personal Information</h3>

                <!-- STUDENT NAME -->

                <div class="form-group">
                    <label>Student Full Name *</label>

                    <input
                        type="text"
                        name="full_name"
                        placeholder="Enter student name"
                        value="<?= e($_POST['full_name'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- FATHER NAME -->

                <div class="form-group">
                    <label>Father Name *</label>

                    <input
                        type="text"
                        name="father_name"
                        placeholder="Enter father name"
                        value="<?= e($_POST['father_name'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- MOTHER NAME -->

                <div class="form-group">
                    <label>Mother Name</label>

                    <input
                        type="text"
                        name="mother_name"
                        placeholder="Enter mother name"
                        value="<?= e($_POST['mother_name'] ?? '') ?>"
                    >
                </div>

                <!-- DATE OF BIRTH -->

                <div class="form-group">
                    <label>Date of Birth *</label>

                    <input
                        type="date"
                        name="date_of_birth"
                        value="<?= e($_POST['date_of_birth'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- GENDER -->

                <div class="form-group">

                    <label>Gender *</label>

                    <div class="choice-options">

                        <label class="choice-card">

                            <input
                                type="radio"
                                name="gender"
                                value="Male"
                                <?= $form_gender === 'Male' ? 'checked' : '' ?>
                                required
                            >

                            <i class="fas fa-check-circle"></i>
                            <span>Male</span>

                        </label>

                        <label class="choice-card">

                            <input
                                type="radio"
                                name="gender"
                                value="Female"
                                <?= $form_gender === 'Female' ? 'checked' : '' ?>
                                required
                            >

                            <i class="fas fa-check-circle"></i>
                            <span>Female</span>

                        </label>

                        <label class="choice-card">

                            <input
                                type="radio"
                                name="gender"
                                value="Other"
                                <?= $form_gender === 'Other' ? 'checked' : '' ?>
                            >

                            <i class="fas fa-check-circle"></i>
                            <span>Other</span>

                        </label>

                    </div>

                </div>

                <!-- PHONE -->

                <div class="form-group">

                    <label>Parent Phone Number *</label>

                    <input
                        type="tel"
                        name="parent_phone"
                        placeholder="03XXXXXXXXX"
                        value="<?= e($_POST['parent_phone'] ?? '') ?>"
                        minlength="11"
                        maxlength="11"
                        pattern="[0-9]{11}"
                        inputmode="numeric"
                        autocomplete="tel"
                        title="Enter exactly 11 digits."
                        required
                    >

                    <small>Contact number must be exactly 11 digits.</small>

                </div>

                <!-- ADDRESS -->

                <div class="form-group full-width">

                    <label>Student Address *</label>

                    <textarea
                        name="address"
                        placeholder="Enter complete address"
                        required
                    ><?= e($_POST['address'] ?? '') ?></textarea>

                </div>

                <!-- ADMISSION INFORMATION -->

                <h3 class="student-section-title">Admission Information</h3>

                <div class="form-group">

                    <label>Admission Date *</label>

                    <input
                        type="date"
                        name="admission_date"
                        value="<?= e($_POST['admission_date'] ?? date('Y-m-d')) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Academic Year *</label>

                    <input
                        type="text"
                        name="academic_year"
                        value="<?= e($_POST['academic_year'] ?? (date('Y') . '-' . (date('Y') + 1)) ) ?>"
                        placeholder="Example: 2026-2027"
                        required
                    >

                </div>

                <!-- CLASS -->

                <div class="form-group">

                    <label>Select Class *</label>

                    <select name="class_id" id="class_id" required>

                        <option value="">Choose Class</option>

                        <?php foreach ($classes as $class): ?>

                            <option
                                value="<?= (int)$class['id'] ?>"
                                <?= ((int)($_POST['class_id'] ?? 0) === (int)$class['id']) ? 'selected' : '' ?>
                            >
                                <?= e($class['class_name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- SECTION -->

                <div class="form-group">

                    <label>Select Section *</label>

                    <div class="choice-options" id="sectionOptions">

                        <?php foreach ($section_data as $section): ?>

                            <label
                                class="choice-card section-choice"
                                data-class="<?= (int)$section['class_id'] ?>"
                            >

                                <input
                                    type="radio"
                                    name="section_id"
                                    value="<?= (int)$section['id'] ?>"
                                    <?= $form_section_id === (int)$section['id'] ? 'checked' : '' ?>
                                    required
                                >

                                <i class="fas fa-check-circle"></i>

                                <span>
                                    Section <?= e($section['section_name']) ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                    <small
                        id="sectionMessage"
                        class="student-section-message"
                    >
                        No sections available for this class.
                    </small>

                    <small id="sectionHint" style="color:#64748b;">
                        Please select a class to view available sections.
                    </small>

                </div>

                <!-- PHOTO -->

                <div class="form-group">

                    <label>Student Photo</label>

                    <input
                        type="file"
                        name="photo"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>JPG, PNG or WEBP. Maximum 2 MB.</small>

                </div>

            </div>

            <!-- ACTIONS -->

            <div class="student-add-actions">

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Add New Student
                </button>

                <a href="view_students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Students
                </a>

            </div>

        </form>

    </div>

    <!-- ALL STUDENTS TABLE -->

    <div class="student-list-card">

        <h2>All Students</h2>

        <p>View and manage registered student records.</p>

        <form method="GET" class="student-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search student name or ID..."
                value="<?= e($search) ?>"
            >

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
                Search
            </button>

            <a href="add_students.php" class="btn btn-secondary">
                Reset
            </a>

        </form>

        <div class="student-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Father Name</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Roll Number</th>
                        <th>Academic Year</th>
                        <th>Admission Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($students && $students->num_rows > 0): ?>

                    <?php while ($student = $students->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <?php if (!empty($student['photo'])): ?>

                                    <img
                                        class="student-photo"
                                        src="../assets/uploads/students/<?= e(basename($student['photo'])) ?>"
                                        alt="Student Photo"
                                    >

                                <?php else: ?>

                                    No Photo

                                <?php endif; ?>

                            </td>

                            <td><?= e($student['student_id']) ?></td>

                            <td><?= e($student['full_name']) ?></td>

                            <td><?= e($student['father_name']) ?></td>

                            <td><?= e($student['class_name'] ?? 'Not Assigned') ?></td>

                            <td><?= e($student['section_name'] ?? 'Not Assigned') ?></td>

                            <td><?= e($student['roll_number'] ?? '-') ?></td>

                            <td><?= e($student['academic_year'] ?? '-') ?></td>

                            <td><?= e($student['admission_date']) ?></td>

                            <td>

                                <?php if ($student['status'] === 'active'): ?>

                                    <span class="student-status-active">Active</span>

                                <?php else: ?>

                                    <span class="student-status-inactive">Inactive</span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <a
                                    href="edit_students.php?id=<?= (int)$student['id'] ?>"
                                    class="btn btn-secondary"
                                >
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="11" style="text-align:center;">
                            No students found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- =====================================
     CLASS AND SECTION FILTER
===================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const classSelect = document.getElementById('class_id');

    const sectionCards = Array.from(
        document.querySelectorAll('.section-choice')
    );

    const sectionMessage = document.getElementById('sectionMessage');

    const sectionHint = document.getElementById('sectionHint');

    if (!classSelect) {
        return;
    }

    function filterSections(clearSelection = false) {

        const selectedClass = classSelect.value;

        let visibleCount = 0;

        sectionCards.forEach(card => {

            const cardClass = card.dataset.class;

            const radio = card.querySelector('input[type="radio"]');

            const isVisible = (
                selectedClass !== '' &&
                cardClass === selectedClass
            );

            card.classList.toggle('section-hidden', !isVisible);

            radio.disabled = !isVisible;

            if (clearSelection && !isVisible) {
                radio.checked = false;
            }

            if (isVisible) {
                visibleCount++;
            }

            radio.required = isVisible;

        });

        sectionHint.style.display = (
            selectedClass === ''
        ) ? 'block' : 'none';

        sectionMessage.style.display = (
            selectedClass !== '' && visibleCount === 0
        ) ? 'block' : 'none';

    }

    classSelect.addEventListener('change', function () {
        filterSections(true);
    });

    filterSections(false);

});

</script>

<?php

$content = ob_get_clean();

$stmt->close();

include __DIR__ . '/../admin/layouts/admin_layout.php';

?>
