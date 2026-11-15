
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

function teacher_manual_e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function teacher_manual_valid_date($date)
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

function teacher_manual_valid_time($time)
{
    if ($time === '') {
        return true;
    }

    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
        return false;
    }

    return true;
}

// ==========================================
// PAGE SETTINGS
// ==========================================

$page_title = "Manual Teacher Attendance";
$current_page = "teacher_attendance";

$today = date('Y-m-d');

$selectedDate = $_POST['attendance_date']
    ?? $_GET['date']
    ?? $today;

$successMessage = '';
$errorMessage = '';

if (!teacher_manual_valid_date($selectedDate)) {
    $selectedDate = $today;
}

if ($selectedDate > $today) {
    $selectedDate = $today;
}

// ==========================================
// SAVE MANUAL TEACHER ATTENDANCE
// ==========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_attendance'])
) {

    $postedDate = $_POST['attendance_date'] ?? '';

    if (!teacher_manual_valid_date($postedDate)) {

        $errorMessage = "Please select a valid attendance date.";

    } elseif ($postedDate > $today) {

        $errorMessage = "Future date attendance cannot be marked.";

    } elseif (
        !isset($_POST['attendance']) ||
        !is_array($_POST['attendance'])
    ) {

        $errorMessage = "No attendance information received.";

    } else {

        $selectedDate = $postedDate;

        try {

            $db->begin_transaction();

            $attendanceStatuses = [
                'present',
                'absent',
                'late',
                'leave'
            ];

            $day = date('l', strtotime($selectedDate));

            $currentTime = date('H:i:s');

            $savedCount = 0;

            // ==================================
            // VERIFY ACTIVE TEACHER
            // ==================================

            $teacherCheck = $db->prepare("
                SELECT id
                FROM teachers
                WHERE id = ?
                  AND status = 'active'
                LIMIT 1
            ");

            // ==================================
            // CHECK EXISTING ATTENDANCE
            // ==================================

            $existingCheck = $db->prepare("
                SELECT id, status, check_in, check_out
                FROM teacher_attendance
                WHERE teacher_id = ?
                  AND attendance_date = ?
                LIMIT 1
            ");

            // ==================================
            // UPDATE EXISTING ATTENDANCE
            // ==================================

            $updateStmt = $db->prepare("
                UPDATE teacher_attendance
                SET
                    status = ?,
                    check_in = ?,
                    check_out = ?,
                    remarks = 'Manually updated by admin'
                WHERE teacher_id = ?
                  AND attendance_date = ?
            ");

            // ==================================
            // INSERT NEW ATTENDANCE
            // ==================================

            $insertStmt = $db->prepare("
                INSERT INTO teacher_attendance
                (
                    teacher_id,
                    attendance_date,
                    attendance_day,
                    check_in,
                    check_out,
                    status,
                    marked_by,
                    remarks
                )
                VALUES (?, ?, ?, ?, ?, ?, NULL, 'Manually marked by admin')
            ");

            if (
                !$teacherCheck ||
                !$existingCheck ||
                !$updateStmt ||
                !$insertStmt
            ) {
                throw new Exception(
                    "Unable to prepare teacher attendance queries."
                );
            }

            // ==================================
            // PROCESS EACH TEACHER
            // ==================================

            foreach ($_POST['attendance'] as $teacherId => $status) {

                if (!ctype_digit((string)$teacherId)) {
                    continue;
                }

                $teacherId = (int)$teacherId;

                if (
                    $status !== '' &&
                    !in_array($status, $attendanceStatuses, true)
                ) {
                    continue;
                }

                // Check Out input
                $checkoutInput = $_POST['checkout'][$teacherId] ?? '';

                if (!is_string($checkoutInput)) {
                    throw new Exception("Invalid Check Out time.");
                }

                if (!teacher_manual_valid_time($checkoutInput)) {
                    throw new Exception(
                        "Invalid Check Out time for teacher ID " . $teacherId
                    );
                }

                $checkOut = $checkoutInput !== ''
                    ? $checkoutInput . ':00'
                    : null;

                // Verify active teacher
                $teacherCheck->bind_param("i", $teacherId);
                $teacherCheck->execute();

                $teacher = $teacherCheck
                    ->get_result()
                    ->fetch_assoc();

                if (!$teacher) {
                    continue;
                }

                // Check existing attendance
                $existingCheck->bind_param(
                    "is",
                    $teacherId,
                    $selectedDate
                );

                $existingCheck->execute();

                $existing = $existingCheck
                    ->get_result()
                    ->fetch_assoc();

                // ==================================
                // EXISTING RECORD
                // ==================================

                if ($existing) {

                    // Keep old status if No Change selected
                    $finalStatus = $status !== ''
                        ? $status
                        : $existing['status'];

                    // Keep existing Check Out if input is blank
                    if ($checkoutInput === '') {
                        $finalCheckOut = $existing['check_out'];
                    } else {
                        $finalCheckOut = $checkOut;
                    }

                    // Automatically save Check In for Present/Late
                    if (
                        in_array(
                            $finalStatus,
                            ['present', 'late'],
                            true
                        )
                    ) {

                        $finalCheckIn = !empty($existing['check_in'])
                            ? $existing['check_in']
                            : $currentTime;

                    } else {

                        $finalCheckIn = null;
                    }

                    // Nothing changed
                    if (
                        $status === '' &&
                        $checkoutInput === ''
                    ) {
                        continue;
                    }

                    $remarks = 'Manually updated by admin';

                    $updateStmt->bind_param(
                        "sssis",
                        $finalStatus,
                        $finalCheckIn,
                        $finalCheckOut,
                        $teacherId,
                        $selectedDate
                    );

                    if (!$updateStmt->execute()) {
                        throw new Exception(
                            "Unable to update teacher attendance."
                        );
                    }

                } else {

                    // ==================================
                    // NEW RECORD
                    // ==================================

                    // A status must be selected to create a new record
                    if ($status === '') {
                        continue;
                    }

                    // Check In only for Present/Late
                    $finalCheckIn = in_array(
                        $status,
                        ['present', 'late'],
                        true
                    )
                        ? $currentTime
                        : null;

                    $insertStmt->bind_param(
                        "isssss",
                        $teacherId,
                        $selectedDate,
                        $day,
                        $finalCheckIn,
                        $checkOut,
                        $status
                    );

                    if (!$insertStmt->execute()) {
                        throw new Exception(
                            "Unable to insert teacher attendance."
                        );
                    }
                }

                $savedCount++;
            }

            $teacherCheck->close();
            $existingCheck->close();
            $updateStmt->close();
            $insertStmt->close();

            if ($savedCount === 0) {

                $db->rollback();

                $errorMessage = "No attendance was changed. Please select a status or enter a Check Out time.";

            } else {

                $db->commit();

                $successMessage = $savedCount .
                    " teachers' attendance saved successfully.";
            }

        } catch (Throwable $e) {

            $db->rollback();

            error_log(
                "Manual Teacher Attendance Error: " .
                $e->getMessage()
            );

            $errorMessage = "Unable to save teacher attendance. Please try again.";
        }
    }
}

// ==========================================
// GET ALL ACTIVE TEACHERS
// ==========================================

$teachers = [];

$teacherSql = "

    SELECT

        t.id AS database_id,
        t.employee_id,
        t.full_name,
        t.phone,
        t.email,

        a.status AS attendance_status,
        a.check_in,
        a.check_out,
        a.remarks

    FROM teachers t

    LEFT JOIN teacher_attendance a
        ON a.teacher_id = t.id
        AND a.attendance_date = ?

    WHERE t.status = 'active'

    ORDER BY t.full_name ASC
";

$teacherStmt = $db->prepare($teacherSql);

if (!$teacherStmt) {
    die("Teacher query error: " . $db->error);
}

$teacherStmt->bind_param("s", $selectedDate);

$teacherStmt->execute();

$teacherResult = $teacherStmt->get_result();

while ($row = $teacherResult->fetch_assoc()) {
    $teachers[] = $row;
}

$teacherStmt->close();

// ==========================================
// ATTENDANCE COUNTS
// ==========================================

$presentCount = 0;
$absentCount = 0;
$lateCount = 0;
$leaveCount = 0;
$unmarkedCount = 0;

foreach ($teachers as $teacher) {

    switch ($teacher['attendance_status']) {

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

$totalTeachers = count($teachers);

?>

<style>

.teacher-manual-container {
    padding: 25px;
}

.teacher-manual-heading {
    margin-bottom: 25px;
}

.teacher-manual-heading h1 {
    color: #102b50;
    margin-bottom: 8px;
}

.teacher-manual-heading p {
    color: #64748b;
}

.teacher-manual-filters {
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

.teacher-manual-filters label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.teacher-manual-filters input {
    padding: 11px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    background: white;
}

.teacher-manual-btn {
    padding: 11px 20px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
}

.teacher-manual-primary {
    background: #102b50;
    color: white;
}

.teacher-manual-gold {
    background: #b99545;
    color: white;
}

.teacher-manual-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.teacher-manual-card {
    background: white;
    padding: 18px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.teacher-manual-card h3 {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 10px;
}

.teacher-manual-card p {
    font-size: 25px;
    font-weight: bold;
    color: #102b50;
}

.teacher-manual-table-wrapper {
    background: white;
    border-radius: 12px;
    padding: 18px;
    overflow-x: auto;
    border: 1px solid #e5e7eb;
}

.teacher-manual-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

.teacher-manual-table th,
.teacher-manual-table td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.teacher-manual-table th {
    background: #102b50;
    color: white;
}

.teacher-status-select {
    width: 135px;
    padding: 11px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    background: white;
}

.teacher-checkout-input {
    width: 125px;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    background: white;
}

.teacher-manual-alert {
    padding: 13px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.teacher-manual-success {
    background: #dcfce7;
    color: #166534;
}

.teacher-manual-error {
    background: #fee2e2;
    color: #991b1b;
}

.teacher-manual-empty {
    text-align: center;
    padding: 30px;
    color: #64748b;
}

.teacher-manual-save-area {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.teacher-manual-info {
    background: #eff6ff;
    color: #1e40af;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

@media(max-width: 600px) {

    .teacher-manual-container {
        padding: 12px;
    }

    .teacher-manual-filters {
        flex-direction: column;
        align-items: stretch;
    }

    .teacher-manual-filters > div {
        width: 100%;
    }

    .teacher-manual-filters input {
        width: 100%;
    }

}

</style>

<div class="teacher-manual-container">

    <div class="teacher-manual-heading">

        <h1>Manual Teacher Attendance</h1>

        <p>
            DAAES School - Mark and edit teacher attendance manually.
        </p>

    </div>

    <?php if ($successMessage): ?>

        <div class="teacher-manual-alert teacher-manual-success">
            <?= teacher_manual_e($successMessage) ?>
        </div>

    <?php endif; ?>

    <?php if ($errorMessage): ?>

        <div class="teacher-manual-alert teacher-manual-error">
            <?= teacher_manual_e($errorMessage) ?>
        </div>

    <?php endif; ?>

    <!-- DATE FILTER -->

    <form method="GET" class="teacher-manual-filters">

        <div>

            <label>Attendance Date</label>

            <input
                type="date"
                name="date"
                value="<?= teacher_manual_e($selectedDate) ?>"
                max="<?= teacher_manual_e($today) ?>"
                required
            >

        </div>

        <button
            type="submit"
            class="teacher-manual-btn teacher-manual-primary"
        >
            <i class="fas fa-filter"></i>
            Load Teachers
        </button>

    </form>

    <div class="teacher-manual-info">

        <i class="fas fa-info-circle"></i>

        All active teachers are displayed automatically.
        Present/Late mark karne par Check In automatically save hoga.
        Check Out time manually select karein.

    </div>

    <!-- SUMMARY CARDS -->

    <div class="teacher-manual-cards">

        <div class="teacher-manual-card">
            <h3>Total Teachers</h3>
            <p><?= $totalTeachers ?></p>
        </div>

        <div class="teacher-manual-card">
            <h3>Present</h3>
            <p><?= $presentCount ?></p>
        </div>

        <div class="teacher-manual-card">
            <h3>Absent</h3>
            <p><?= $absentCount ?></p>
        </div>

        <div class="teacher-manual-card">
            <h3>Late</h3>
            <p><?= $lateCount ?></p>
        </div>

        <div class="teacher-manual-card">
            <h3>Leave</h3>
            <p><?= $leaveCount ?></p>
        </div>

        <div class="teacher-manual-card">
            <h3>Not Marked</h3>
            <p><?= $unmarkedCount ?></p>
        </div>

    </div>

    <!-- ATTENDANCE FORM -->

    <form method="POST">

        <input
            type="hidden"
            name="attendance_date"
            value="<?= teacher_manual_e($selectedDate) ?>"
        >

        <div class="teacher-manual-table-wrapper">

            <h2>Teacher Attendance List</h2>

            <p>
                Attendance Date:
                <strong><?= teacher_manual_e($selectedDate) ?></strong>
            </p>

            <br>

            <table class="teacher-manual-table">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Teacher Name</th>
                        <th>Employee ID</th>
                        <th>Phone</th>
                        <th>Current Attendance</th>
                        <th>Mark Attendance</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($teachers)): ?>

                        <?php foreach ($teachers as $index => $teacher): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <?= teacher_manual_e($teacher['full_name']) ?>
                                </td>

                                <td>
                                    <?= teacher_manual_e($teacher['employee_id']) ?>
                                </td>

                                <td>
                                    <?= teacher_manual_e($teacher['phone'] ?? '-') ?>
                                </td>

                                <td>
                                    <?php if ($teacher['attendance_status']): ?>
                                        <?= teacher_manual_e(
                                            ucfirst($teacher['attendance_status'])
                                        ) ?>
                                    <?php else: ?>
                                        Not Marked
                                    <?php endif; ?>
                                </td>

                                <td>

                                    <select
                                        name="attendance[<?= (int)$teacher['database_id'] ?>]"
                                        class="teacher-status-select"
                                    >

                                        <option value="">
                                            No Change
                                        </option>

                                        <option
                                            value="present"
                                            <?= $teacher['attendance_status'] === 'present' ? 'selected' : '' ?>
                                        >
                                            Present
                                        </option>

                                        <option
                                            value="absent"
                                            <?= $teacher['attendance_status'] === 'absent' ? 'selected' : '' ?>
                                        >
                                            Absent
                                        </option>

                                        <option
                                            value="late"
                                            <?= $teacher['attendance_status'] === 'late' ? 'selected' : '' ?>
                                        >
                                            Late
                                        </option>

                                        <option
                                            value="leave"
                                            <?= $teacher['attendance_status'] === 'leave' ? 'selected' : '' ?>
                                        >
                                            Leave
                                        </option>

                                    </select>

                                </td>

                                <td>

                                    <?php if (!empty($teacher['check_in'])): ?>

                                        <?= teacher_manual_e(
                                            date(
                                                'h:i A',
                                                strtotime($teacher['check_in'])
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <input
                                        type="time"
                                        name="checkout[<?= (int)$teacher['database_id'] ?>]"
                                        class="teacher-checkout-input"
                                        value="<?= !empty($teacher['check_out']) ? teacher_manual_e(substr($teacher['check_out'], 0, 5)) : '' ?>"
                                    >

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="teacher-manual-empty">
                                No active teachers found.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

            <?php if (!empty($teachers)): ?>

                <div class="teacher-manual-save-area">

                    <button
                        type="submit"
                        name="save_attendance"
                        value="1"
                        class="teacher-manual-btn teacher-manual-gold"
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
