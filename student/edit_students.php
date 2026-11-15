
<?php

include '../config/database.php';

// =====================================
// PAGE SETTINGS
// =====================================

$page_title = 'Edit Student';
$current_page = 'students';

$message = '';
$message_type = '';

$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');

// GET par koi Gender ya Section selected nahi hoga.
// POST par manually selected values preserve hongi.
$form_gender = '';
$form_section_id = 0;

// =====================================
// ESCAPE FUNCTION
// =====================================

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// =====================================
// GET STUDENT ID
// =====================================

$id = (int)($_GET['id'] ?? $_POST['student_id'] ?? 0);

if ($id <= 0) {
    header("Location: view_students.php");
    exit;
}

// =====================================
// FETCH STUDENT FUNCTION
// =====================================

function fetchStudent($conn, $id)
{
    $stmt = $conn->prepare("
        SELECT
            s.*,
            e.id AS enrollment_id,
            e.class_id,
            e.section_id,
            e.academic_year,
            e.roll_number,
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
        WHERE s.id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        die("Student Query Error: " . e($conn->error));
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $student = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $student;
}

// =====================================
// FETCH STUDENT
// =====================================

$student = fetchStudent($conn, $id);

if (!$student) {
    header("Location: view_students.php");
    exit;
}

// =====================================
// FETCH CLASSES
// =====================================

$class_result = $conn->query("
    SELECT MIN(id) AS id, MIN(class_name) AS class_name
    FROM classes
    GROUP BY LOWER(TRIM(class_name))
    ORDER BY MIN(id)
");

if (!$class_result) {
    die("Class Error: " . e($conn->error));
}

$class_list = [];

while ($class = $class_result->fetch_assoc()) {
    $class_list[] = $class;
}

// =====================================
// FETCH SECTIONS A AND B
// =====================================

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

$section_list = [];

while ($section = $section_result->fetch_assoc()) {
    $section_list[] = $section;
}

// =====================================
// UPDATE STUDENT
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

    $photo = $student['photo'] ?? '';
    $new_photo = null;

    $upload_dir = __DIR__ . '/../assets/uploads/students/';

    // =================================
    // VALIDATION
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

        $message = "Please fill all required fields!";
        $message_type = "error";

    } elseif (!in_array($gender, ['Male', 'Female'], true)) {

        $message = "Invalid gender selected!";
        $message_type = "error";

    } else {

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
            die("Section Verification Error: " . e($conn->error));
        }

        $check->bind_param("ii", $section_id, $class_id);
        $check->execute();

        $valid_section = $check->get_result()->num_rows > 0;

        $check->close();

        if (!$valid_section) {
            $message = "Invalid class or section selected!";
            $message_type = "error";
        }

        // =================================
        // PHOTO UPLOAD
        // =================================

        if (
            empty($message) &&
            isset($_FILES['photo']) &&
            $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {

                $message = "There was an error uploading the photo.";
                $message_type = "error";

            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {

                $message = "Photo size must be less than 2 MB.";
                $message_type = "error";

            } else {

                $image_info = @getimagesize($_FILES['photo']['tmp_name']);
                $mime = $image_info['mime'] ?? '';

                $allowed_types = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (!$image_info || !isset($allowed_types[$mime])) {

                    $message = "Only JPG, PNG and WEBP images are allowed.";
                    $message_type = "error";

                } else {

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $new_photo = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime];

                    if (move_uploaded_file(
                        $_FILES['photo']['tmp_name'],
                        $upload_dir . $new_photo
                    )) {

                        $photo = $new_photo;

                    } else {

                        $new_photo = null;
                        $message = "Photo upload failed!";
                        $message_type = "error";
                    }
                }
            }
        }

        // =================================
        // SAVE STUDENT DETAILS
        // =================================

        if (empty($message)) {

            $transaction_started = false;

            try {

                $conn->begin_transaction();
                $transaction_started = true;

                // UPDATE STUDENT TABLE

                $update = $conn->prepare("
                    UPDATE students SET
                        full_name = ?,
                        father_name = ?,
                        mother_name = ?,
                        gender = ?,
                        date_of_birth = ?,
                        admission_date = ?,
                        phone = ?,
                        address = ?,
                        photo = ?
                    WHERE id = ?
                ");

                if (!$update) {
                    throw new Exception($conn->error);
                }

                $update->bind_param(
                    "sssssssssi",
                    $full_name,
                    $father_name,
                    $mother_name,
                    $gender,
                    $dob,
                    $admission_date,
                    $phone,
                    $address,
                    $photo,
                    $id
                );

                if (!$update->execute()) {
                    throw new Exception($update->error);
                }

                $update->close();

                // =================================
                // UPDATE EXISTING ENROLLMENT
                // =================================

                if (!empty($student['enrollment_id'])) {

                    $enrollment_id = (int)$student['enrollment_id'];

                    $enrollment = $conn->prepare("
                        UPDATE enrollments SET
                            class_id = ?,
                            section_id = ?,
                            academic_year = ?,
                            enrollment_date = ?
                        WHERE id = ?
                    ");

                    if (!$enrollment) {
                        throw new Exception($conn->error);
                    }

                    $enrollment->bind_param(
                        "iissi",
                        $class_id,
                        $section_id,
                        $academic_year,
                        $admission_date,
                        $enrollment_id
                    );

                } else {

                    // =================================
                    // CREATE NEW ENROLLMENT
                    // =================================

                    $count = $conn->prepare("
                        SELECT COUNT(*) AS total
                        FROM enrollments
                        WHERE class_id = ?
                          AND section_id = ?
                          AND academic_year = ?
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

                    $count->execute();

                    $total = (int)$count->get_result()->fetch_assoc()['total'];

                    $count->close();

                    $roll_number = str_pad(
                        $total + 1,
                        3,
                        "0",
                        STR_PAD_LEFT
                    );

                    $status = "active";

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
                        $id,
                        $class_id,
                        $section_id,
                        $academic_year,
                        $roll_number,
                        $admission_date,
                        $status
                    );
                }

                if (!$enrollment->execute()) {
                    throw new Exception($enrollment->error);
                }

                $enrollment->close();

                // =================================
                // COMMIT
                // =================================

                $conn->commit();
                $transaction_started = false;

                // =================================
                // DELETE OLD PHOTO AFTER SUCCESS
                // =================================

                if (
                    $new_photo !== null &&
                    !empty($student['photo']) &&
                    is_file($upload_dir . basename($student['photo']))
                ) {
                    unlink($upload_dir . basename($student['photo']));
                }

                // =================================
                // REDIRECT
                // =================================

                header("Location: view_students.php?updated=1");
                exit;

            } catch (Throwable $ex) {

                if ($transaction_started) {
                    $conn->rollback();
                }

                // Remove newly uploaded photo if update failed

                if (
                    $new_photo !== null &&
                    is_file($upload_dir . $new_photo)
                ) {
                    unlink($upload_dir . $new_photo);
                }

                $message = "Update failed: " . $ex->getMessage();
                $message_type = "error";
            }
        }
    }

    // =================================
    // KEEP ENTERED VALUES AFTER ERROR
    // =================================

    $student = array_merge($student, [
        'full_name' => $full_name,
        'father_name' => $father_name,
        'mother_name' => $mother_name,
        'gender' => $gender,
        'date_of_birth' => $dob,
        'admission_date' => $admission_date,
        'phone' => $phone,
        'address' => $address,
        'class_id' => $class_id,
        'section_id' => $section_id,
        'academic_year' => $academic_year,
        'photo' => $photo
    ]);
}

// =====================================
// START PAGE CONTENT
// =====================================

ob_start();

?>

<style>

/* =====================================
   EDIT STUDENT POPUP OVERLAY
===================================== */

.student-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;

    background: rgba(3, 10, 25, .78);
    backdrop-filter: blur(7px);
    -webkit-backdrop-filter: blur(7px);

    padding: 25px 15px;
    overflow-y: auto;

    align-items: flex-start;
    justify-content: center;

    box-sizing: border-box;
}

.student-modal-overlay.active {
    display: flex;
    animation: modalFade .25s ease;
}

/* =====================================
   MODAL BOX
===================================== */

.student-modal {
    position: relative;
    background: #ffffff;

    width: 100%;
    max-width: 850px;

    border-radius: 14px;
    padding: 25px;

    margin: auto;
    box-sizing: border-box;

    box-shadow: 0 15px 50px rgba(0, 0, 0, .25);

    animation: modalUp .35s ease;
}

/* =====================================
   ANIMATIONS
===================================== */

@keyframes modalFade {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes modalUp {
    from {
        transform: translateY(25px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* =====================================
   PAGE AND FORM
===================================== */

.edit-student-page {
    width: 100%;
}

.edit-student-card {
    background: #ffffff;
    width: 100%;
    box-sizing: border-box;
}

.edit-student-card .record-heading {
    margin-bottom: 22px;
}

.edit-student-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.edit-student-form .form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 0;
}

.edit-student-form label {
    font-weight: 600;
}

.edit-student-form input:not([type="radio"]):not([type="file"]),
.edit-student-form select,
.edit-student-form textarea {
    width: 100%;
    min-height: 43px;
    padding: 11px 12px;

    border: 1px solid #d5dbe5;
    border-radius: 7px;

    box-sizing: border-box;
    font-family: inherit;
}

.edit-student-form textarea {
    min-height: 90px;
    resize: vertical;
}

.edit-student-form .full-width {
    grid-column: 1 / -1;
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

/* SELECTED CARD */

.choice-card:has(input[type="radio"]:checked) {
    background: #19395f;
    color: #ffffff;
    border-color: #d5a52b;
    box-shadow: 0 2px 7px rgba(25, 57, 95, .13);
}

.choice-card:has(input[type="radio"]:checked) i {
    color: #ffffff;
}

/* HOVER */

.choice-card:hover {
    border-color: #19395f;
    transform: translateY(-1px);
}

/* KEYBOARD FOCUS */

.choice-card:has(input[type="radio"]:focus-visible) {
    outline: 3px solid rgba(25, 57, 95, .25);
    outline-offset: 3px;
}

/* HIDDEN SECTION CARDS */

.choice-card.section-hidden {
    display: none;
}

/* =====================================
   PHOTO
===================================== */

.edit-student-photo {
    width: 90px;
    height: 90px;

    object-fit: cover;
    border-radius: 50%;

    border: 2px solid #d5a52b;
    margin: 5px 0 10px;
}

/* =====================================
   ERROR MESSAGE
===================================== */

.edit-student-error {
    padding: 13px 16px;
    margin-bottom: 18px;

    border-radius: 7px;

    background: #fdeaea;
    color: #a32121;

    border: 1px solid #f3caca;
}

/* =====================================
   BUTTONS
===================================== */

.edit-student-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
}

.edit-student-actions .btn {
    width: auto;
    min-height: 43px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    text-decoration: none;
}

/* =====================================
   RESPONSIVE
===================================== */

@media (max-width: 650px) {

    .student-modal-overlay {
        padding: 12px 8px;
    }

    .student-modal {
        padding: 16px;
        border-radius: 12px;
    }

    .edit-student-form {
        grid-template-columns: 1fr;
    }

    .edit-student-form .full-width {
        grid-column: auto;
    }

    .edit-student-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .edit-student-actions .btn {
        width: 100%;
    }
}

</style>

<!-- =====================================
     EDIT STUDENT POPUP
===================================== -->

<div class="student-modal-overlay active" id="editStudentModal">

    <div class="student-modal">

        <div class="dashboard-heading">
            <div>
                <h1>Edit Student</h1>
                <p>Update student information and enrollment details.</p>
            </div>
        </div>

        <div class="edit-student-page">

            <div class="edit-student-card">

                <div class="record-heading">
                    <div>
                        <h2>Edit Student Information</h2>

                        <p>
                            Student ID:
                            <strong><?= e($student['student_id']); ?></strong>
                        </p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>

                    <div class="edit-student-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= e($message); ?>
                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    action="edit_students.php?id=<?= (int)$id; ?>"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="student_id"
                        value="<?= (int)$id; ?>"
                    >

                    <div class="edit-student-form">

                        <!-- STUDENT NAME -->

                        <div class="form-group">

                            <label>Student Full Name *</label>

                            <input
                                type="text"
                                name="full_name"
                                value="<?= e($student['full_name']); ?>"
                                required
                            >

                        </div>

                        <!-- FATHER NAME -->

                        <div class="form-group">

                            <label>Father Name *</label>

                            <input
                                type="text"
                                name="father_name"
                                value="<?= e($student['father_name']); ?>"
                                required
                            >

                        </div>

                        <!-- MOTHER NAME -->

                        <div class="form-group">

                            <label>Mother Name</label>

                            <input
                                type="text"
                                name="mother_name"
                                value="<?= e($student['mother_name'] ?? ''); ?>"
                            >

                        </div>

                        <!-- DATE OF BIRTH -->

                        <div class="form-group">

                            <label>Date of Birth *</label>

                            <input
                                type="date"
                                name="date_of_birth"
                                value="<?= e($student['date_of_birth']); ?>"
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
                                        <?= $form_gender === 'Male' ? 'checked' : ''; ?>
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
                                        <?= $form_gender === 'Female' ? 'checked' : ''; ?>
                                        required
                                    >

                                    <i class="fas fa-check-circle"></i>
                                    <span>Female</span>

                                </label>

                             

                            </div>

                        </div>

                        <!-- PARENT PHONE -->

                        <div class="form-group">

                            <label>Parent Phone Number *</label>

                            <input
                                type="tel"
                                name="parent_phone"
                                value="<?= e($student['phone']); ?>"
                                required
                            >

                        </div>

                        <!-- ADMISSION DATE -->

                        <div class="form-group">

                            <label>Admission Date *</label>

                            <input
                                type="date"
                                name="admission_date"
                                value="<?= e($student['admission_date']); ?>"
                                required
                            >

                        </div>

                        <!-- ACADEMIC YEAR -->

                        <div class="form-group">

                            <label>Academic Year *</label>

                            <input
                                type="text"
                                name="academic_year"
                                value="<?= e($student['academic_year'] ?? ''); ?>"
                                placeholder="Example: 2026-2027"
                                required
                            >

                        </div>

                        <!-- CLASS -->

                        <div class="form-group">

                            <label>Select Class *</label>

                            <select
                                name="class_id"
                                id="class_id"
                                required
                            >

                                <option value="">Choose Class</option>

                                <?php foreach ($class_list as $class): ?>

                                    <option
                                        value="<?= (int)$class['id']; ?>"
                                        <?= ((int)($student['class_id'] ?? 0) === (int)$class['id']) ? 'selected' : ''; ?>
                                    >
                                        <?= e($class['class_name']); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <!-- SECTION -->

                        <div class="form-group">

                            <label>Select Section *</label>

                            <div class="choice-options" id="sectionOptions">

                                <?php foreach ($section_list as $section): ?>

                                    <label
                                        class="choice-card section-choice"
                                        data-class="<?= (int)$section['class_id']; ?>"
                                    >

                                        <input
                                            type="radio"
                                            name="section_id"
                                            value="<?= (int)$section['id']; ?>"
                                            <?= $form_section_id === (int)$section['id'] ? 'checked' : ''; ?>
                                            required
                                        >

                                        <i class="fas fa-check-circle"></i>

                                        <span>
                                            Section <?= e($section['section_name']); ?>
                                        </span>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                            <small
                                id="sectionMessage"
                                style="display:none; color:#a32121;"
                            >
                                No sections available for this class.
                            </small>

                        </div>

                        <!-- CURRENT PHOTO -->

                        <div class="form-group full-width">

                            <label>Current Student Photo</label>

                            <?php if (!empty($student['photo'])): ?>

                                <img
                                    class="edit-student-photo"
                                    src="../assets/uploads/students/<?= e(basename($student['photo'])); ?>"
                                    alt="Student Photo"
                                    onerror="this.style.display='none';"
                                >

                            <?php else: ?>

                                <p>No photo uploaded.</p>

                            <?php endif; ?>

                            <label>Change Student Photo (Optional)</label>

                            <input
                                type="file"
                                name="photo"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <small>
                                Allowed: JPG, PNG, WEBP. Maximum size: 2 MB.
                            </small>

                        </div>

                        <!-- ADDRESS -->

                        <div class="form-group full-width">

                            <label>Student Address *</label>

                            <textarea
                                name="address"
                                required
                            ><?= e($student['address']); ?></textarea>

                        </div>

                    </div>

                    <!-- BUTTONS -->

                    <div class="edit-student-actions">

                        <button type="submit" class="btn btn-primary">

                            <i class="fas fa-save"></i>
                            Update Student

                        </button>

                        <a href="view_students.php" class="btn btn-secondary">

                            <i class="fas fa-arrow-left"></i>
                            Back to Students

                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

<script>

// =====================================
// CLASS AND SECTION CARD FILTER
// =====================================

const classSelect = document.getElementById('class_id');

const sectionCards = Array.from(
    document.querySelectorAll('.section-choice')
);

const sectionMessage = document.getElementById('sectionMessage');

// =====================================
// FILTER SECTION CARDS
// =====================================

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

        // Class change par purani section selection clear
        if (clearSelection && !isVisible) {
            radio.checked = false;
        }

        if (isVisible) {
            visibleCount++;
        }

        radio.required = isVisible;

    });

    // No section available message

    sectionMessage.style.display = (
        selectedClass !== '' && visibleCount === 0
    ) ? 'block' : 'none';

}

// =====================================
// CLASS CHANGE EVENT
// =====================================

classSelect.addEventListener('change', function() {

    filterSections(true);

});

// =====================================
// INITIAL FILTER
// =====================================

// Existing class show hogi,
// lekin Gender aur Section automatically select nahi honge.

filterSections(false);

</script>

<?php

$content = ob_get_clean();

include __DIR__ . '/../admin/layouts/admin_layout.php';

?>
