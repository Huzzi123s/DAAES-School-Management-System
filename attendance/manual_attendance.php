
<?php
ob_start();

date_default_timezone_set('Asia/Karachi');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// DATABASE CONNECTION
// ==========================================

require_once __DIR__ . '/../config/database.php';

$db = $conn ?? $mysqli ?? $con ?? $connection ?? null;

if (!($db instanceof mysqli)) {
    die("Database connection not found.");
}

$db->set_charset("utf8mb4");

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function manual_e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function manual_valid_date($date)
{
    if (
        !is_string($date) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    ) {
        return false;
    }

    $parts = explode('-', $date);

    return checkdate(
        (int)$parts[1],
        (int)$parts[2],
        (int)$parts[0]
    );
}

// ==========================================
// PAGE SETTINGS
// ==========================================

$page_title = "Manual Student Attendance";
$current_page = "manual_attendance";

$today = date('Y-m-d');

$selectedDate = $_POST['attendance_date']
    ?? $_GET['date']
    ?? $today;

$class_id = $_POST['class_id']
    ?? $_GET['class_id']
    ?? '';

$successMessage = '';
$errorMessage = '';

if (!manual_valid_date($selectedDate)) {
    $selectedDate = $today;
}

if ($selectedDate > $today) {
    $selectedDate = $today;
}

if (
    $class_id !== '' &&
    !ctype_digit((string)$class_id)
) {
    $class_id = '';
}

$filterClassId = ($class_id === '')
    ? 0
    : (int)$class_id;

// ==========================================
// GET ACTIVE CLASSES
// ==========================================

$classes = [];

$classResult = $db->query("
    SELECT id, class_name
    FROM classes
    WHERE status = 'active'
    ORDER BY class_order ASC, id ASC
");

if (!$classResult) {
    die("Unable to load classes: " . $db->error);
}

while ($row = $classResult->fetch_assoc()) {
    $classes[] = $row;
}

// ==========================================
// VERIFY SELECTED CLASS
// ==========================================

if ($filterClassId > 0) {

    $classCheck = $db->prepare("
        SELECT id
        FROM classes
        WHERE id = ?
          AND status = 'active'
        LIMIT 1
    ");

    if (!$classCheck) {
        die("Class verification error.");
    }

    $classCheck->bind_param("i", $filterClassId);
    $classCheck->execute();

    $validClass = $classCheck->get_result()->fetch_assoc();

    $classCheck->close();

    if (!$validClass) {
        $class_id = '';
        $filterClassId = 0;
    }
}

// ==========================================
// SAVE MANUAL ATTENDANCE
// ==========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_attendance'])
) {

    $postedDate = $_POST['attendance_date'] ?? '';
    $postedClass = $_POST['class_id'] ?? '';

    if (!manual_valid_date($postedDate)) {

        $errorMessage = "Please select a valid attendance date.";

    } elseif ($postedDate > $today) {

        $errorMessage = "Future date attendance cannot be marked.";

    } elseif (
        $postedClass !== '' &&
        !ctype_digit((string)$postedClass)
    ) {

        $errorMessage = "Invalid class selected.";

    } elseif (
        !isset($_POST['attendance']) ||
        !is_array($_POST['attendance'])
    ) {

        $errorMessage = "No attendance information received.";

    } else {

        $selectedDate = $postedDate;
        $class_id = $postedClass;

        $filterClassId = ($class_id === '')
            ? 0
            : (int)$class_id;

        try {

            $db->begin_transaction();

            $attendanceStatuses = [
                'present',
                'absent',
                'late',
                'leave'
            ];

            $day = date(
                'l',
                strtotime($selectedDate)
            );

            $currentTime = date('H:i:s');

            $savedCount = 0;

            // ==================================
            // VERIFY ACTIVE STUDENT AND ENROLLMENT
            // ==================================

            $studentCheck = $db->prepare("
                SELECT
                    s.id AS student_id,
                    e.id AS enrollment_id

                FROM students s

                INNER JOIN enrollments e
                    ON e.student_id = s.id

                INNER JOIN classes c
                    ON c.id = e.class_id

                WHERE s.id = ?
                  AND s.status = 'active'
                  AND e.status = 'active'
                  AND c.status = 'active'
                  AND (? = 0 OR e.class_id = ?)

                LIMIT 1
            ");

            // ==================================
            // CHECK EXISTING ATTENDANCE
            // ==================================

            $existingCheck = $db->prepare("
                SELECT id

                FROM student_attendance

                WHERE student_id = ?
                  AND attendance_date = ?

                LIMIT 1
            ");

            // ==================================
            // UPDATE EXISTING ATTENDANCE
            // ==================================

            $updateStmt = $db->prepare("
                UPDATE student_attendance

                SET
                    enrollment_id = ?,
                    status = ?,
                    scan_method = 'manual',
                    remarks = 'Manually updated by admin'

                WHERE student_id = ?
                  AND attendance_date = ?
            ");

            // ==================================
            // INSERT NEW ATTENDANCE
            // ==================================

            $insertStmt = $db->prepare("
                INSERT INTO student_attendance
                (
                    student_id,
                    enrollment_id,
                    attendance_date,
                    attendance_day,
                    scan_time,
                    status,
                    scan_method,
                    remarks
                )

                VALUES (
                    ?, ?, ?, ?, ?, ?,
                    'manual',
                    'Manually marked by admin'
                )
            ");

            if (
                !$studentCheck ||
                !$existingCheck ||
                !$updateStmt ||
                !$insertStmt
            ) {
                throw new Exception(
                    "Unable to prepare attendance queries."
                );
            }

            // ==================================
            // PROCESS EACH STUDENT
            // ==================================

            foreach ($_POST['attendance'] as $studentId => $status) {

                if (!ctype_digit((string)$studentId)) {
                    continue;
                }

                $studentId = (int)$studentId;

                // Blank status means no change
                if ($status === '') {
                    continue;
                }

                if (!in_array(
                    $status,
                    $attendanceStatuses,
                    true
                )) {
                    continue;
                }

                // Verify active student
                $studentCheck->bind_param(
                    "iii",
                    $studentId,
                    $filterClassId,
                    $filterClassId
                );

                $studentCheck->execute();

                $student = $studentCheck
                    ->get_result()
                    ->fetch_assoc();

                if (!$student) {
                    continue;
                }

                $enrollmentId = (int)$student['enrollment_id'];

                // Check existing record
                $existingCheck->bind_param(
                    "is",
                    $studentId,
                    $selectedDate
                );

                $existingCheck->execute();

                $existing = $existingCheck
                    ->get_result()
                    ->fetch_assoc();

                if ($existing) {

                    // Update QR or manual attendance
                    $updateStmt->bind_param(
                        "isis",
                        $enrollmentId,
                        $status,
                        $studentId,
                        $selectedDate
                    );

                    if (!$updateStmt->execute()) {
                        throw new Exception(
                            "Unable to update attendance."
                        );
                    }

                } else {

                    // Insert new manual attendance
                    $insertStmt->bind_param(
                        "iissss",
                        $studentId,
                        $enrollmentId,
                        $selectedDate,
                        $day,
                        $currentTime,
                        $status
                    );

                    if (!$insertStmt->execute()) {
                        throw new Exception(
                            "Unable to insert attendance."
                        );
                    }
                }

                $savedCount++;
            }

            $studentCheck->close();
            $existingCheck->close();
            $updateStmt->close();
            $insertStmt->close();

            if ($savedCount === 0) {

                $db->rollback();

                $errorMessage = "No attendance was changed. Please select at least one status.";

            } else {

                $db->commit();

                $successMessage = $savedCount .
                    " students' attendance saved successfully.";
            }

        } catch (Throwable $e) {

            $db->rollback();

            error_log(
                "Manual Attendance Error: " .
                $e->getMessage()
            );

            $errorMessage = "Unable to save attendance. Please try again.";
        }
    }
}

// ==========================================
// GET ALL ACTIVE STUDENTS
// CLASS FILTER IS OPTIONAL
// ==========================================

$students = [];

$studentSql = "

    SELECT

        s.id AS database_id,
        s.student_id,
        s.full_name,

        e.id AS enrollment_id,
        e.roll_number,

        c.id AS class_id,
        c.class_name,

        a.status AS attendance_status,
        a.scan_method,
        a.scan_time

    FROM students s

    INNER JOIN enrollments e
        ON e.student_id = s.id

    INNER JOIN classes c
        ON c.id = e.class_id

    LEFT JOIN student_attendance a
        ON a.student_id = s.id
        AND a.attendance_date = ?

    WHERE s.status = 'active'
      AND e.status = 'active'
      AND c.status = 'active'
      AND (? = 0 OR e.class_id = ?)

    ORDER BY
        c.class_order ASC,
        c.class_name ASC,
        e.roll_number ASC,
        s.full_name ASC
";

$studentStmt = $db->prepare($studentSql);

if (!$studentStmt) {
    die("Student query error: " . $db->error);
}

$studentStmt->bind_param(
    "sii",
    $selectedDate,
    $filterClassId,
    $filterClassId
);

$studentStmt->execute();

$studentResult = $studentStmt->get_result();

while ($row = $studentResult->fetch_assoc()) {
    $students[] = $row;
}

$studentStmt->close();

// ==========================================
// ATTENDANCE COUNTS
// ==========================================

$presentCount = 0;
$absentCount = 0;
$lateCount = 0;
$leaveCount = 0;
$unmarkedCount = 0;

foreach ($students as $student) {

    switch ($student['attendance_status']) {

        case 'present':
            $presentCount++;
            break;

        case 'absent':
            $absentCount++;
            break;

        case 'late':
            $lateCount++;
            break;

        case 'leave':
            $leaveCount++;
            break;

        default:
            $unmarkedCount++;
            break;
    }
}

$totalStudents = count($students);

?>

<style>

.manual-container {
    padding: 25px;
}

.manual-heading {
    margin-bottom: 25px;
}

.manual-heading h1 {
    color: #102b50;
    margin-bottom: 8px;
}

.manual-heading p {
    color: #64748b;
}

.manual-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.manual-filters label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.manual-filters input,
.manual-filters select,
.status-select {
    padding: 11px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    background: white;
}

.manual-btn {
    padding: 11px 20px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
}

.manual-primary {
    background: #102b50;
    color: white;
}

.manual-gold {
    background: #b99545;
    color: white;
}

.manual-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.manual-card {
    background: white;
    padding: 18px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.manual-card h3 {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 10px;
}

.manual-card p {
    font-size: 25px;
    font-weight: bold;
    color: #102b50;
}

.manual-table-wrapper {
    background: white;
    border-radius: 12px;
    padding: 18px;
    overflow-x: auto;
    border: 1px solid #e5e7eb;
}

.manual-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.manual-table th,
.manual-table td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.manual-table th {
    background: #102b50;
    color: white;
}

.status-select {
    width: 135px;
}

.manual-alert {
    padding: 13px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.manual-success {
    background: #dcfce7;
    color: #166534;
}

.manual-error {
    background: #fee2e2;
    color: #991b1b;
}

.manual-empty {
    text-align: center;
    padding: 30px;
    color: #64748b;
}

.manual-save-area {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.manual-info {
    background: #eff6ff;
    color: #1e40af;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

@media(max-width: 600px) {

    .manual-container {
        padding: 12px;
    }

    .manual-filters {
        flex-direction: column;
        align-items: stretch;
    }

    .manual-filters > div {
        width: 100%;
    }

    .manual-filters input,
    .manual-filters select {
        width: 100%;
    }

}

</style>

<div class="manual-container">

    <!-- PAGE HEADING -->

    <div class="manual-heading">

        <h1>Manual Student Attendance</h1>

        <p>
            DAAES School - Mark and edit student attendance manually.
        </p>

    </div>

    <!-- SUCCESS MESSAGE -->

    <?php if ($successMessage): ?>

        <div class="manual-alert manual-success">
            <?= manual_e($successMessage) ?>
        </div>

    <?php endif; ?>

    <!-- ERROR MESSAGE -->

    <?php if ($errorMessage): ?>

        <div class="manual-alert manual-error">
            <?= manual_e($errorMessage) ?>
        </div>

    <?php endif; ?>

    <!-- FILTERS -->

    <form method="GET" class="manual-filters">

        <div>

            <label>Select Class</label>

            <select name="class_id">

                <option value="">
                    All Classes
                </option>

                <?php foreach ($classes as $class): ?>

                    <option
                        value="<?= (int)$class['id'] ?>"
                        <?= (string)$class_id === (string)$class['id'] ? 'selected' : '' ?>
                    >
                        <?= manual_e($class['class_name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div>

            <label>Attendance Date</label>

            <input
                type="date"
                name="date"
                value="<?= manual_e($selectedDate) ?>"
                max="<?= manual_e($today) ?>"
                required
            >

        </div>

        <button
            type="submit"
            class="manual-btn manual-primary"
        >
            <i class="fas fa-filter"></i>
            Load Students
        </button>

    </form>

    <div class="manual-info">

        <i class="fas fa-info-circle"></i>

        All active students are displayed by default.
        Select a class above to filter the list.

    </div>

    <!-- SUMMARY CARDS -->

    <div class="manual-cards">

        <div class="manual-card">
            <h3>Total Students</h3>
            <p><?= $totalStudents ?></p>
        </div>

        <div class="manual-card">
            <h3>Present</h3>
            <p><?= $presentCount ?></p>
        </div>

        <div class="manual-card">
            <h3>Absent</h3>
            <p><?= $absentCount ?></p>
        </div>

        <div class="manual-card">
            <h3>Late</h3>
            <p><?= $lateCount ?></p>
        </div>

        <div class="manual-card">
            <h3>Leave</h3>
            <p><?= $leaveCount ?></p>
        </div>

        <div class="manual-card">
            <h3>Not Marked</h3>
            <p><?= $unmarkedCount ?></p>
        </div>

    </div>

    <!-- ATTENDANCE FORM -->

    <form method="POST">

        <input
            type="hidden"
            name="class_id"
            value="<?= manual_e($class_id) ?>"
        >

        <input
            type="hidden"
            name="attendance_date"
            value="<?= manual_e($selectedDate) ?>"
        >

        <div class="manual-table-wrapper">

            <h2>Student Attendance List</h2>

            <p>
                Attendance Date:
                <strong><?= manual_e($selectedDate) ?></strong>
            </p>

            <br>

            <table class="manual-table">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Class</th>
                        <th>Roll Number</th>
                        <th>Current Attendance</th>
                        <th>Mark Attendance</th>
                        <th>Source</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($students)): ?>

                        <?php foreach ($students as $index => $student): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <?= manual_e($student['full_name']) ?>
                                </td>

                                <td>
                                    <?= manual_e($student['student_id']) ?>
                                </td>

                                <td>
                                    <?= manual_e($student['class_name']) ?>
                                </td>

                                <td>
                                    <?= manual_e($student['roll_number'] ?? '-') ?>
                                </td>

                                <td>

                                    <?php if ($student['attendance_status']): ?>

                                        <?= manual_e(
                                            ucfirst($student['attendance_status'])
                                        ) ?>

                                    <?php else: ?>

                                        <span>Not Marked</span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <select
                                        name="attendance[<?= (int)$student['database_id'] ?>]"
                                        class="status-select"
                                    >

                                        <option value="">
                                            No Change
                                        </option>

                                        <option
                                            value="present"
                                            <?= $student['attendance_status'] === 'present' ? 'selected' : '' ?>
                                        >
                                            Present
                                        </option>

                                        <option
                                            value="absent"
                                            <?= $student['attendance_status'] === 'absent' ? 'selected' : '' ?>
                                        >
                                            Absent
                                        </option>

                                        <option
                                            value="late"
                                            <?= $student['attendance_status'] === 'late' ? 'selected' : '' ?>
                                        >
                                            Late
                                        </option>

                                        <option
                                            value="leave"
                                            <?= $student['attendance_status'] === 'leave' ? 'selected' : '' ?>
                                        >
                                            Leave
                                        </option>

                                    </select>

                                </td>

                                <td>
                                    <?= manual_e(
                                        $student['scan_method'] ?? '-'
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="manual-empty">

                                No active students found.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

            <?php if (!empty($students)): ?>

                <div class="manual-save-area">

                    <button
                        type="submit"
                        name="save_attendance"
                        value="1"
                        class="manual-btn manual-gold"
                    >

                        <i class="fas fa-save"></i>

                        Save Attendance

                    </button>

                </div>

            <?php endif; ?>

        </div>

    </form>

</div>

<?php

// ==========================================
// ADMIN DASHBOARD LAYOUT
// ==========================================

$content = ob_get_clean();

$page_content = $content;

require_once __DIR__ . '/../admin/layouts/admin_layout.php';

?>
