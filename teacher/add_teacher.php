
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Karachi');

include '../config/database.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['teacher_csrf'])) {
    $_SESSION['teacher_csrf'] = bin2hex(random_bytes(32));
}

$embed = isset($_GET['embed']) && $_GET['embed'] === '1';
$message = '';

$today = date('Y-m-d');

$academic_options = [
    'Matric',
    'Intermediate',
    'FA',
    'FSc Pre-Medical',
    'FSc Pre-Engineering',
    'ICS',
    'ICom',
    'DAE',
    'A-Level',
    'Bachelor',
    'BA',
    'BSc',
    'BCom',
    'BBA',
    'BS',
    'BS Computer Science',
    'BS Mathematics',
    'BS English',
    'BS Physics',
    'BS Chemistry',
    'BS Biology',
    'BS Education',
    'BS Psychology',
    'BS Islamic Studies',
    'BS Urdu',
    'BS Economics',
    'BS Zoology',
    'BS Botany',
    'BS Statistics',
    'BS IT',
    'BS Software Engineering',
    'MA',
    'MSc',
    'MCom',
    'MBA',
    'M.Ed',
    'M.Phil',
    'MS',
    'PhD'
];

$specific_options = [
    'B.Ed',
    'M.Ed',
    'ADE',
    'PCT',
    'CT',
    'PTC',
    'Montessori Diploma',
    'Montessori Training',
    'Early Childhood Education',
    'Primary Teaching Certificate',
    'Secondary Teaching Certificate',
    'Special Education Diploma',
    'Diploma in English Language',
    'English Language Teaching',
    'TESOL',
    'TEFL',
    'IELTS Certification',
    'Computer Diploma',
    'DIT',
    'DIT (Diploma in Information Technology)',
    'Graphic Designing',
    'Web Development',
    'Programming',
    'Mathematics Teaching',
    'Science Teaching',
    'Physics Specialization',
    'Chemistry Specialization',
    'Biology Specialization',
    'Urdu Teaching',
    'Islamic Studies',
    'Quran Teaching',
    'Tajweed Certification',
    'Arabic Language',
    'Physical Education',
    'Art and Craft',
    'Music Education',
    'Child Psychology',
    'Classroom Management',
    'Educational Leadership',
    'Other Professional Training'
];

// =====================================
// ADD TEACHER
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $uploaded_file = null;
    $transaction_started = false;

    try {

        // CSRF VALIDATION

        if (
            !isset($_POST['csrf_token']) ||
            !hash_equals($_SESSION['teacher_csrf'], $_POST['csrf_token'])
        ) {
            throw new Exception('Invalid form request. Please refresh and try again.');
        }

        $full_name = trim($_POST['full_name'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $academic = trim($_POST['academic_qualification'] ?? '');
        $specific = trim($_POST['specific_qualification'] ?? '');

        // JOINING DATE AUTOMATICALLY TODAY
        $joining_date = date('Y-m-d');

        $salary = (float)($_POST['salary'] ?? 0);
        $status = $_POST['status'] ?? '';

        // REQUIRED FIELDS

        if (
            $full_name === '' ||
            $father_name === '' ||
            $gender === '' ||
            $date_of_birth === '' ||
            $phone === '' ||
            $email === '' ||
            $address === '' ||
            $academic === '' ||
            $specific === ''
        ) {
            throw new Exception('Please fill all required fields.');
        }

        if (!in_array($gender, ['Male', 'Female'], true)) {
            throw new Exception('Please select Male or Female.');
        }

        if (!in_array($academic, $academic_options, true)) {
            throw new Exception('Please select a valid academic qualification.');
        }

        if (!in_array($specific, $specific_options, true)) {
            throw new Exception('Please select a valid specific qualification.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new Exception('Please select account status.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if ($salary < 0) {
            throw new Exception('Salary cannot be negative.');
        }

        // CHECK DUPLICATE EMAIL

        $check = $conn->prepare("
            SELECT id FROM teachers
            WHERE email = ?
            LIMIT 1
        ");

        $check->bind_param("s", $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $check->close();
            throw new Exception('This teacher email is already registered.');
        }

        $check->close();

        // PHOTO UPLOAD

        $photo_path = null;

        if (
            isset($_FILES['photo']) &&
            $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Photo upload failed.');
            }

            if ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
                throw new Exception('Photo must be below 3 MB.');
            }

            $image_info = @getimagesize($_FILES['photo']['tmp_name']);

            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $mime = $image_info['mime'] ?? '';

            if (!$image_info || !isset($allowed_types[$mime])) {
                throw new Exception('Only JPG, PNG and WEBP images are allowed.');
            }

            $upload_dir = __DIR__ . '/../uploads/teachers/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_name = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime];

            $uploaded_file = $upload_dir . $file_name;

            if (!move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                $uploaded_file
            )) {
                throw new Exception('Unable to save teacher photo.');
            }

            $photo_path = 'uploads/teachers/' . $file_name;
        }

        // GENERATE UNIQUE QR TOKEN

        $qr_token = bin2hex(random_bytes(32));

        $temp_employee_id = 'TEMP-' . bin2hex(random_bytes(8));

        // DATABASE TRANSACTION

        $conn->begin_transaction();
        $transaction_started = true;

        $insert = $conn->prepare("
            INSERT INTO teachers (
                user_id,
                employee_id,
                qr_token,
                full_name,
                father_name,
                gender,
                date_of_birth,
                phone,
                email,
                address,
                qualification,
                academic_qualification,
                specific_qualification,
                joining_date,
                salary,
                photo,
                status
            )
            VALUES (
                NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        if (!$insert) {
            throw new Exception($conn->error);
        }

        $insert->bind_param(
            "sssssssssssssdss",
            $temp_employee_id,
            $qr_token,
            $full_name,
            $father_name,
            $gender,
            $date_of_birth,
            $phone,
            $email,
            $address,
            $academic,
            $academic,
            $specific,
            $joining_date,
            $salary,
            $photo_path,
            $status
        );

        if (!$insert->execute()) {
            throw new Exception($insert->error);
        }

        $teacher_id = $conn->insert_id;

        $insert->close();

        // GENERATE FINAL TEACHER ID

        $employee_id = 'TCH-' . str_pad(
            $teacher_id,
            5,
            '0',
            STR_PAD_LEFT
        );

        $update = $conn->prepare("
            UPDATE teachers
            SET employee_id = ?
            WHERE id = ?
        ");

        if (!$update) {
            throw new Exception($conn->error);
        }

        $update->bind_param("si", $employee_id, $teacher_id);

        if (!$update->execute()) {
            throw new Exception($update->error);
        }

        $update->close();

        // COMMIT

        $conn->commit();
        $transaction_started = false;

        // SUCCESS: RETURN TO TEACHER LISTING

        if ($embed) {

            echo '<!DOCTYPE html>
            <html>
            <head><meta charset="UTF-8"></head>
            <body>
            <script>
                window.parent.location.href = "view_teacher.php?added=1";
            </script>
            </body>
            </html>';

            exit;
        }

        header("Location: view_teacher.php?added=1");
        exit;

    } catch (Throwable $ex) {

        if ($transaction_started) {
            $conn->rollback();
        }

        if ($uploaded_file && is_file($uploaded_file)) {
            unlink($uploaded_file);
        }

        $message = $ex->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Teacher</title>

<style>

* {
    box-sizing:border-box;
}

body {
    margin:0;
    padding:25px;
    font-family:Arial,sans-serif;
    background:#f4f6fa;
    color:#182b45;
}

.teacher-form-card {
    width:100%;
    max-width:100%;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:14px;
}

.form-intro {
    margin-bottom:22px;
}

.form-intro h2 {
    margin:0 0 7px;
    color:#102c50;
}

.form-intro p {
    color:#718096;
    margin:0;
    font-size:14px;
}

.form-grid {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:17px;
}

.section-heading {
    grid-column:1/-1;
    color:#153c68;
    font-size:15px;
    font-weight:800;
    border-bottom:2px solid #e7edf4;
    padding-bottom:10px;
    margin-top:10px;
}

.form-group {
    display:flex;
    flex-direction:column;
    gap:8px;
    min-width:0;
}

.form-group label {
    font-size:13px;
    font-weight:700;
    color:#263b56;
}

.form-group input,
.form-group select,
.form-group textarea {
    width:100%;
    min-width:0;
    padding:12px;
    border:1px solid #d8e0eb;
    border-radius:8px;
    background:#fff;
    font:inherit;
    outline:none;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color:#c49a36;
    box-shadow:0 0 0 3px rgba(196,154,54,.13);
}

.form-group textarea {
    min-height:85px;
    resize:vertical;
}

.full-width {
    grid-column:1/-1;
}

/* JOINING DATE */

.date-auto {
    background:#f0f3f8 !important;
    color:#52647c;
    cursor:not-allowed;
}

/* GENDER AND STATUS */

.choice-row {
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.choice-option {
    display:flex;
    align-items:center;
    gap:9px;
    padding:11px 17px;
    border:1px solid #d9e0e9;
    border-radius:8px;
    cursor:pointer;
    background:#fff;
    min-width:110px;
}

.choice-option input {
    width:17px;
    height:17px;
    accent-color:#bd922f;
    margin:0;
}

.choice-option:has(input:checked) {
    border-color:#c49a36;
    background:#fffaf0;
    color:#17365d;
    font-weight:700;
}

.form-buttons {
    display:flex;
    gap:12px;
    margin-top:24px;
    flex-wrap:wrap;
}

.submit-btn {
    border:0;
    border-radius:8px;
    padding:13px 22px;
    background:linear-gradient(120deg,#102c50,#1d527e);
    color:white;
    font-weight:700;
    cursor:pointer;
}

.submit-btn:hover {
    background:linear-gradient(120deg,#c09532,#a77b1e);
}

.form-message {
    padding:12px 15px;
    border-radius:8px;
    margin-bottom:18px;
    font-size:14px;
}

.form-message.error {
    background:#fdeaea;
    color:#a32121;
}

.photo-note {
    font-size:12px;
    color:#778397;
}

@media(max-width:600px) {

    body {
        padding:12px;
    }

    .teacher-form-card {
        padding:17px;
    }

    .form-grid {
        grid-template-columns:1fr;
    }

    .section-heading,
    .full-width {
        grid-column:auto;
    }

    .form-buttons button {
        width:100%;
    }
}

</style>
</head>

<body>

<div class="teacher-form-card">

    <div class="form-intro">

        <h2>Teacher Information</h2>

        <p>
            Fill in the details below to register a new teacher.
        </p>

    </div>

    <?php if ($message !== ''): ?>

        <div class="form-message error">
            <?= e($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['teacher_csrf']); ?>"
        >

        <div class="form-grid">

            <div class="section-heading">
                Personal Information
            </div>

            <div class="form-group">

                <label>Full Name *</label>

                <input
                    type="text"
                    name="full_name"
                    required
                >

            </div>

            <div class="form-group">

                <label>Father Name *</label>

                <input
                    type="text"
                    name="father_name"
                    required
                >

            </div>

            <div class="form-group">

                <label>Gender *</label>

                <div class="choice-row">

                    <label class="choice-option">
                        <input type="radio" name="gender" value="Male" required>
                        Male
                    </label>

                    <label class="choice-option">
                        <input type="radio" name="gender" value="Female" required>
                        Female
                    </label>

                </div>

            </div>

            <div class="form-group">

                <label>Date of Birth *</label>

                <input
                    type="date"
                    name="date_of_birth"
                    max="<?= e($today); ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label>Phone *</label>

                <input
                    type="tel"
                    name="phone"
                    required
                >

            </div>

            <div class="form-group">

                <label>Email Address *</label>

                <input
                    type="email"
                    name="email"
                    required
                >

            </div>

            <div class="form-group full-width">

                <label>Address *</label>

                <textarea
                    name="address"
                    required
                ></textarea>

            </div>

            <div class="section-heading">
                Professional Information
            </div>

            <div class="form-group">

                <label>Academic Qualification *</label>

                <select name="academic_qualification" required>

                    <option value="">
                        Select Academic Qualification
                    </option>

                    <?php foreach ($academic_options as $option): ?>

                        <option value="<?= e($option); ?>">
                            <?= e($option); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Specific Qualification *</label>

                <select name="specific_qualification" required>

                    <option value="">
                        Select Specific Qualification
                    </option>

                    <?php foreach ($specific_options as $option): ?>

                        <option value="<?= e($option); ?>">
                            <?= e($option); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Joining Date</label>

                <input
                    type="date"
                    class="date-auto"
                    value="<?= e($today); ?>"
                    readonly
                >

                <small style="color:#778397;">
                    Automatically set to today's date.
                </small>

            </div>

            <div class="form-group">

                <label>Monthly Salary</label>

                <input
                    type="number"
                    name="salary"
                    min="0"
                    step="1"
                    value="0"
                >

            </div>

            <div class="form-group">

                <label>Teacher Photo</label>

                <input
                    type="file"
                    name="photo"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <span class="photo-note">
                    JPG, PNG or WEBP. Maximum 3 MB.
                </span>

            </div>

            <div class="form-group">

                <label>Account Status *</label>

                <div class="choice-row">

                    <label class="choice-option">
                        <input
                            type="radio"
                            name="status"
                            value="active"
                            checked
                            required
                        >
                        Active
                    </label>

                    <label class="choice-option">
                        <input
                            type="radio"
                            name="status"
                            value="inactive"
                            required
                        >
                        Inactive
                    </label>

                </div>

            </div>

        </div>

        <div class="form-buttons">

            <button type="submit" class="submit-btn">
                <i class="fas fa-user-plus"></i>
                Add Teacher
            </button>

        </div>

    </form>

</div>

</body>
</html>
