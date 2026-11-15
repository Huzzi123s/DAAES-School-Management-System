
<?php

// ==========================================
// STUDENT QR ATTENDANCE PROCESS
// ==========================================

date_default_timezone_set('Asia/Karachi');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/database.php';

// ==========================================
// DATABASE CONNECTION
// ==========================================

$db = $conn ?? $mysqli ?? $con ?? $connection ?? null;

function sendResponse($success, $message, $student = null, $status = null, $scanTime = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'student' => $student,
        'attendance_status' => $status,
        'scan_time' => $scanTime
    ]);
    exit;
}

if (!($db instanceof mysqli)) {
    sendResponse(false, 'Database connection not found.');
}

// ==========================================
// REQUEST VALIDATION
// ==========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method.');
}

$qr_token = trim($_POST['qr_token'] ?? '');

if ($qr_token === '') {
    sendResponse(false, 'QR token is missing.');
}

// ==========================================
// CURRENT DATE AND TIME
// ==========================================

$today = date('Y-m-d');
$currentTime = date('H:i:s');
$day = date('l');

// Attendance timings
$presentCutoff = '08:00:00';
$attendanceClose = '13:00:00';

// ==========================================
// MAIN PROCESS
// ==========================================

try {

    // ======================================
    // FIND ACTIVE STUDENT
    // ======================================

    $sql = "
        SELECT
            s.id AS database_id,
            s.student_id,
            s.full_name,
            s.father_name,
            s.qr_token,
            s.status AS student_status,

            e.id AS enrollment_id,
            e.class_id,
            e.roll_number,

            c.class_name

        FROM students s

        INNER JOIN enrollments e
            ON e.student_id = s.id

        INNER JOIN classes c
            ON c.id = e.class_id

        WHERE s.qr_token = ?
          AND s.status = 'active'
          AND e.status = 'active'
          AND c.status = 'active'

        LIMIT 1
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception($db->error);
    }

    $stmt->bind_param("s", $qr_token);
    $stmt->execute();

    $student = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$student) {
        sendResponse(
            false,
            'Student not found or has no active enrollment.'
        );
    }

    // ======================================
    // STUDENT DETAILS
    // ======================================

    $studentDetails = [
        'student_id' => $student['student_id'],
        'full_name' => $student['full_name'],
        'father_name' => $student['father_name'],
        'class_name' => $student['class_name'],
        'roll_number' => $student['roll_number']
    ];

    // ======================================
    // CHECK ATTENDANCE TIME
    // ======================================

    if ($currentTime > $attendanceClose) {

        sendResponse(
            false,
            'Attendance scanning is closed. After 1:00 PM only admin can manually edit attendance.',
            $studentDetails,
            null,
            $currentTime
        );
    }

    // ======================================
    // DETERMINE ATTENDANCE STATUS
    // ======================================

    if ($currentTime <= $presentCutoff) {

        $attendanceStatus = 'present';

    } else {

        $attendanceStatus = 'late';

    }

    // ======================================
    // CHECK EXISTING ATTENDANCE
    // ======================================

    $checkSql = "
        SELECT
            id,
            status,
            scan_method,
            scan_time

        FROM student_attendance

        WHERE student_id = ?
          AND attendance_date = ?

        LIMIT 1
    ";

    $checkStmt = $db->prepare($checkSql);

    if (!$checkStmt) {
        throw new Exception($db->error);
    }

    $studentDatabaseId = (int)$student['database_id'];

    $checkStmt->bind_param(
        "is",
        $studentDatabaseId,
        $today
    );

    $checkStmt->execute();

    $existing = $checkStmt->get_result()->fetch_assoc();

    $checkStmt->close();

    if ($existing) {

        $existingStatus = $existing['status'];

        $existingTime = $existing['scan_time'];

        sendResponse(
            false,
            'Attendance already marked today as ' .
                ucfirst($existingStatus) . '.',
            $studentDetails,
            $existingStatus,
            $existingTime
        );
    }

    // ======================================
    // SAVE QR ATTENDANCE
    // ======================================

    $insertSql = "
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

        VALUES (?, ?, ?, ?, ?, ?, 'qr', 'QR scan')
    ";

    $insertStmt = $db->prepare($insertSql);

    if (!$insertStmt) {
        throw new Exception($db->error);
    }

    $enrollmentId = (int)$student['enrollment_id'];

    $insertStmt->bind_param(
        "iissss",
        $studentDatabaseId,
        $enrollmentId,
        $today,
        $day,
        $currentTime,
        $attendanceStatus
    );

    $insertStmt->execute();

    $insertStmt->close();

    // ======================================
    // SUCCESS RESPONSE
    // ======================================

    $message = ($attendanceStatus === 'present')
        ? 'Attendance marked successfully - Present.'
        : 'Attendance marked successfully - Late.';

    sendResponse(
        true,
        $message,
        $studentDetails,
        $attendanceStatus,
        $currentTime
    );

} catch (Throwable $e) {

    error_log(
        'QR Attendance Error: ' . $e->getMessage()
    );

    sendResponse(
        false,
        'Unable to process attendance. Please try again.'
    );

}
