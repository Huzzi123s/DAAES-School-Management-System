
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Karachi');

include '../config/database.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$embed = isset($_GET['embed']) && $_GET['embed'] === '1';

if (empty($_SESSION['teacher_csrf'])) {
    $_SESSION['teacher_csrf'] = bin2hex(random_bytes(32));
}

$teacher_id = (int)($_GET['id'] ?? $_POST['teacher_id'] ?? 0);

if ($teacher_id <= 0) {
    header("Location: view_teacher.php");
    exit;
}

// QUALIFICATION OPTIONS

$academic_options = [
    'Matric','Intermediate','FA','FSc Pre-Medical',
    'FSc Pre-Engineering','ICS','ICom','DAE',
    'A-Level','Bachelor','BA','BSc','BCom','BBA',
    'BS','BS Computer Science','BS Mathematics',
    'BS English','BS Physics','BS Chemistry','BS Biology',
    'BS Education','BS Psychology','BS Islamic Studies',
    'BS Urdu','BS Economics','BS Zoology','BS Botany',
    'BS Statistics','BS IT','BS Software Engineering',
    'MA','MSc','MCom','MBA','M.Ed','M.Phil','MS','PhD'
];

$specific_options = [
    'B.Ed','M.Ed','ADE','PCT','CT','PTC',
    'Montessori Diploma','Montessori Training',
    'Early Childhood Education',
    'Primary Teaching Certificate',
    'Secondary Teaching Certificate',
    'Special Education Diploma',
    'Diploma in English Language',
    'English Language Teaching','TESOL','TEFL',
    'IELTS Certification','Computer Diploma','DIT',
    'DIT (Diploma in Information Technology)',
    'Graphic Designing','Web Development','Programming',
    'Mathematics Teaching','Science Teaching',
    'Physics Specialization','Chemistry Specialization',
    'Biology Specialization','Urdu Teaching',
    'Islamic Studies','Quran Teaching','Tajweed Certification',
    'Arabic Language','Physical Education','Art and Craft',
    'Music Education','Child Psychology','Classroom Management',
    'Educational Leadership','Other Professional Training'
];

// FETCH TEACHER

$stmt = $conn->prepare("
    SELECT * FROM teachers
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Database error: ' . e($conn->error));
}

$stmt->bind_param("i", $teacher_id);
$stmt->execute();

$teacher = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$teacher) {
    header("Location: view_teacher.php");
    exit;
}

$error_message = '';

// UPDATE TEACHER

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $academic = trim($_POST['academic_qualification'] ?? '');
    $specific = trim($_POST['specific_qualification'] ?? '');

    $joining_date = trim($_POST['joining_date'] ?? '');
    $salary = (float)($_POST['salary'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    $photo_path = $teacher['photo'] ?? null;
    $new_photo_file = '';
    $old_photo_file = '';

    // VALIDATION

    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['teacher_csrf'], $_POST['csrf_token'])
    ) {
        $error_message = 'Invalid form request. Please refresh and try again.';
    }

    elseif (
        $full_name === '' ||
        $father_name === '' ||
        $gender === '' ||
        $date_of_birth === '' ||
        $phone === '' ||
        $email === '' ||
        $address === '' ||
        $academic === '' ||
        $specific === '' ||
        $joining_date === ''
    ) {
        $error_message = 'Please fill in all required fields.';
    }

    elseif (!in_array($gender, ['Male', 'Female'], true)) {
        $error_message = 'Please select Male or Female.';
    }

    elseif (!in_array($academic, $academic_options, true)) {
        $error_message = 'Please select a valid academic qualification.';
    }

    elseif (!in_array($specific, $specific_options, true)) {
        $error_message = 'Please select a valid specific qualification.';
    }

    elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error_message = 'Please select a valid account status.';
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    }

    elseif ($salary < 0) {
        $error_message = 'Salary cannot be negative.';
    }

    // CHECK DUPLICATE EMAIL

    if ($error_message === '') {

        $check = $conn->prepare("
            SELECT id FROM teachers
            WHERE email = ? AND id != ?
            LIMIT 1
        ");

        if (!$check) {
            $error_message = 'Database error: ' . $conn->error;
        } else {

            $check->bind_param("si", $email, $teacher_id);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                $error_message = 'This email is already assigned to another teacher.';
            }

            $check->close();
        }
    }

    // PHOTO UPLOAD

    if (
        $error_message === '' &&
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'Photo upload failed.';
        }

        elseif ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
            $error_message = 'Photo must be below 3 MB.';
        }

        else {

            $image_info = @getimagesize($_FILES['photo']['tmp_name']);

            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $mime = $image_info['mime'] ?? '';

            if (!$image_info || !isset($allowed_types[$mime])) {
                $error_message = 'Only JPG, PNG and WEBP images are allowed.';
            }

            else {

                $upload_dir = __DIR__ . '/../uploads/teachers/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_name = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime];

                $destination = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {

                    $new_photo_file = $destination;

                    $photo_path = 'uploads/teachers/' . $file_name;

                    if (!empty($teacher['photo'])) {

                        $old_photo_file = __DIR__ . '/../' .
                            ltrim(
                                str_replace('\\', '/', $teacher['photo']),
                                '/'
                            );
                    }

                } else {
                    $error_message = 'Unable to save teacher photo.';
                }
            }
        }
    }

    // SAVE CHANGES

    if ($error_message === '') {

        try {

            $conn->begin_transaction();

            $update = $conn->prepare("
                UPDATE teachers SET

                    full_name = ?,
                    father_name = ?,
                    gender = ?,
                    date_of_birth = ?,
                    phone = ?,
                    email = ?,
                    address = ?,

                    qualification = ?,
                    academic_qualification = ?,
                    specific_qualification = ?,

                    joining_date = ?,
                    salary = ?,
                    photo = ?,
                    status = ?

                WHERE id = ?
            ");

            if (!$update) {
                throw new Exception($conn->error);
            }

            $update->bind_param(
                "ssssssssssdsssi",
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
                $status,
                $teacher_id
            );

            if (!$update->execute()) {
                throw new Exception($update->error);
            }

            $update->close();

            // UPDATE LINKED USER

            if (!empty($teacher['user_id'])) {

                $user_id = (int)$teacher['user_id'];

                $user_update = $conn->prepare("
                    UPDATE users
                    SET name = ?, email = ?, phone = ?, status = ?
                    WHERE id = ?
                ");

                if (!$user_update) {
                    throw new Exception($conn->error);
                }

                $user_update->bind_param(
                    "ssssi",
                    $full_name,
                    $email,
                    $phone,
                    $status,
                    $user_id
                );

                if (!$user_update->execute()) {
                    throw new Exception($user_update->error);
                }

                $user_update->close();
            }

            $conn->commit();

            // DELETE OLD PHOTO AFTER SUCCESSFUL UPDATE

            if (
                $new_photo_file !== '' &&
                $old_photo_file !== '' &&
                is_file($old_photo_file)
            ) {
                unlink($old_photo_file);
            }

            // POPUP SUCCESS REDIRECT

            if ($embed) {
                echo '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Updated</title>
                </head>
                <body>
                    <script>
                        window.parent.location.href = "view_teacher.php?updated=1";
                    </script>
                </body>
                </html>';

                exit;
            }

            header("Location: view_teacher.php?updated=1");
            exit;

        } catch (Throwable $ex) {

            $conn->rollback();

            if ($new_photo_file !== '' && is_file($new_photo_file)) {
                unlink($new_photo_file);
            }

            $error_message = 'Update failed: ' . $ex->getMessage();
        }
    }

    // PRESERVE ENTERED VALUES

    $teacher = array_merge($teacher, [
        'full_name' => $full_name,
        'father_name' => $father_name,
        'gender' => $gender,
        'date_of_birth' => $date_of_birth,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'qualification' => $academic,
        'academic_qualification' => $academic,
        'specific_qualification' => $specific,
        'joining_date' => $joining_date,
        'salary' => $salary,
        'photo' => $photo_path,
        'status' => $status
    ]);
}

// CURRENT PHOTO URL

$currentPhotoUrl = '';

if (!empty($teacher['photo'])) {

    $photo = str_replace('\\', '/', $teacher['photo']);
    $photo = ltrim($photo, '/');

    if (strpos($photo, 'uploads/') === 0) {
        $currentPhotoUrl = '../' . $photo;
    } else {
        $currentPhotoUrl = '../uploads/teachers/' . basename($photo);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Teacher</title>

<style>

* {
    box-sizing: border-box;
}

html {
    width:100%;
    min-height:100%;
    height:auto !important;
    overflow-x:hidden !important;
    overflow-y:auto !important;
}

body {
    margin:0;
    padding:25px;
    min-height:100%;
    height:auto !important;
    overflow-x:hidden !important;
    overflow-y:auto !important;
    background:#f4f6fa;
    font-family:Arial, sans-serif;
    color:#182b45;
}

.teacher-form-card {
    width:100%;
    height:auto;
    min-height:0;
    overflow:visible;
    background:white;
    padding:25px;
    border-radius:14px;
    margin-bottom:25px;
}

.form-intro {
    margin-bottom:22px;
}

.form-intro h2 {
    margin:0 0 7px;
    color:#102c50;
}

.form-intro p {
    margin:0;
    color:#718096;
    font-size:14px;
}

.form-grid {
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
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
    background:white;
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

/* RADIO CARDS */

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
    background:white;
    min-width:110px;
    transition:.2s ease;
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

/* PHOTO */

.edit-teacher-photo {
    width:85px;
    height:85px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #d5a52b;
    margin-bottom:8px;
}

.photo-note {
    font-size:12px;
    color:#778397;
}

/* BUTTONS */

.form-buttons {
    display:flex;
    gap:12px;
    margin-top:24px;
    margin-bottom:10px;
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

.back-btn {
    text-decoration:none;
    border-radius:8px;
    padding:13px 22px;
    background:#edf0f5;
    color:#24344c;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
}

.back-btn:hover {
    background:#dfe5ee;
}

/* ERROR MESSAGE */

.form-message {
    background:#fdeaea;
    color:#a32121;
    padding:12px 15px;
    border-radius:8px;
    margin-bottom:18px;
    font-size:14px;
}

/* MOBILE */

@media(max-width:600px) {

    body {
        padding:12px;
    }

    .teacher-form-card {
        padding:17px;
        margin-bottom:15px;
    }

    .form-grid {
        grid-template-columns:1fr;
    }

    .section-heading,
    .full-width {
        grid-column:auto;
    }

    .form-buttons {
        flex-direction:column;
    }

    .form-buttons button,
    .form-buttons a {
        width:100%;
        text-align:center;
    }
}

</style>
</head>

<body>

<div class="teacher-form-card">

    <div class="form-intro">

        <h2>Teacher Information</h2>

        <p>Update teacher details and professional information.</p>

    </div>

    <?php if ($error_message !== ''): ?>

        <div class="form-message">
            <?= e($error_message); ?>
        </div>

    <?php endif; ?>

    <form
        method="POST"
        action="edit_teacher.php?id=<?= $teacher_id; ?><?= $embed ? '&embed=1' : ''; ?>"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="teacher_id"
            value="<?= $teacher_id; ?>"
        >

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
                    value="<?= e($teacher['full_name']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Father Name *</label>

                <input
                    type="text"
                    name="father_name"
                    value="<?= e($teacher['father_name']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Gender *</label>

                <div class="choice-row">

                    <label class="choice-option">
                        <input
                            type="radio"
                            name="gender"
                            value="Male"
                            <?= ($teacher['gender'] ?? '') === 'Male' ? 'checked' : ''; ?>
                            required
                        >
                        Male
                    </label>

                    <label class="choice-option">
                        <input
                            type="radio"
                            name="gender"
                            value="Female"
                            <?= ($teacher['gender'] ?? '') === 'Female' ? 'checked' : ''; ?>
                            required
                        >
                        Female
                    </label>

                </div>
            </div>

            <div class="form-group">
                <label>Date of Birth *</label>

                <input
                    type="date"
                    name="date_of_birth"
                    max="<?= date('Y-m-d'); ?>"
                    value="<?= e($teacher['date_of_birth']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Phone *</label>

                <input
                    type="tel"
                    name="phone"
                    value="<?= e($teacher['phone']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Email Address *</label>

                <input
                    type="email"
                    name="email"
                    value="<?= e($teacher['email']); ?>"
                    required
                >
            </div>

            <div class="form-group full-width">
                <label>Address *</label>

                <textarea name="address" required><?= e($teacher['address']); ?></textarea>
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

                        <option
                            value="<?= e($option); ?>"
                            <?= ($teacher['academic_qualification'] ?? $teacher['qualification'] ?? '') === $option ? 'selected' : ''; ?>
                        >
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

                        <option
                            value="<?= e($option); ?>"
                            <?= ($teacher['specific_qualification'] ?? '') === $option ? 'selected' : ''; ?>
                        >
                            <?= e($option); ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label>Joining Date *</label>

                <input
                    type="date"
                    name="joining_date"
                    value="<?= e($teacher['joining_date']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Monthly Salary</label>

                <input
                    type="number"
                    name="salary"
                    min="0"
                    step="1"
                    value="<?= e($teacher['salary']); ?>"
                >
            </div>

            <div class="form-group">
                <label>Account Status *</label>

                <div class="choice-row">

                    <label class="choice-option">
                        <input
                            type="radio"
                            name="status"
                            value="active"
                            <?= ($teacher['status'] ?? '') === 'active' ? 'checked' : ''; ?>
                            required
                        >
                        Active
                    </label>

                    <label class="choice-option">
                        <input
                            type="radio"
                            name="status"
                            value="inactive"
                            <?= ($teacher['status'] ?? '') === 'inactive' ? 'checked' : ''; ?>
                            required
                        >
                        Inactive
                    </label>

                </div>
            </div>

            <div class="form-group">
                <label>Teacher Photo</label>

                <?php if ($currentPhotoUrl): ?>

                    <img
                        src="<?= e($currentPhotoUrl); ?>"
                        class="edit-teacher-photo"
                        alt="Teacher Photo"
                    >

                <?php else: ?>

                    <span>No photo uploaded.</span>

                <?php endif; ?>

                <input
                    type="file"
                    name="photo"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <span class="photo-note">
                    JPG, PNG or WEBP. Maximum 3 MB.
                </span>
            </div>

        </div>

        <div class="form-buttons">

            <button type="submit" class="submit-btn">
                Update Teacher
            </button>

            <!-- FIXED GO BACK BUTTON -->

            <a
                href="view_teacher.php"
                class="back-btn"
                onclick="
                    if (window.self !== window.top) {

                        if (
                            typeof window.parent.closeEditTeacherModal === 'function'
                        ) {
                            window.parent.closeEditTeacherModal();
                        } else {
                            window.parent.location.href =
                                new URL('view_teacher.php', window.location.href).href;
                        }

                        return false;
                    }
                "
            >
                Go Back
            </a>

        </div>

    </form>

</div>

</body>
</html>
