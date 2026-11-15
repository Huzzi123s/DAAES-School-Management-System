
<?php

header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Asia/Karachi');

require_once __DIR__ . '/../config/database.php';

$db = $conn ?? $mysqli ?? $con ?? $connection ?? null;


function sendResponse(
    $success,
    $message,
    $teacher = null,
    $attendanceStatus = null,
    $scanTime = null
) {

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'teacher' => $teacher,
        'attendance_status' => $attendanceStatus,
        'scan_time' => $scanTime
    ]);

    exit;
}


// POST only

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method.');
}


// Database connection

if (!($db instanceof mysqli)) {
    sendResponse(false, 'Database connection not found.');
}


// Get QR token

$qr_token = trim($_POST['qr_token'] ?? '');

if ($qr_token === '') {
    sendResponse(false, 'Teacher QR token is missing.');
}


try {

    // Find teacher

    $stmt = $db->prepare("
        SELECT
            id,
            employee_id,
            full_name
        FROM teachers
        WHERE qr_token = ?
          AND status = 'active'
        LIMIT 1
    ");

    $stmt->bind_param("s", $qr_token);

    $stmt->execute();

    $teacher = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$teacher) {

        sendResponse(
            false,
            'Teacher not found or inactive.'
        );

    }


    $teacherId = (int)$teacher['id'];

    $today = date('Y-m-d');

    $currentTime = date('H:i:s');

    $day = date('l');


    // 9:00 AM cutoff

    $attendanceStatus = ($currentTime > '09:00:00')
        ? 'late'
        : 'present';


    // Check existing attendance

    $check = $db->prepare("
        SELECT
            id,
            status,
            check_in
        FROM teacher_attendance
        WHERE teacher_id = ?
          AND attendance_date = ?
        LIMIT 1
    ");

    $check->bind_param(
        "is",
        $teacherId,
        $today
    );

    $check->execute();

    $existing = $check->get_result()->fetch_assoc();

    $check->close();


    $teacherDetails = [
        'employee_id' => $teacher['employee_id'],
        'full_name' => $teacher['full_name'],
        'attendance' => $existing['status'] ?? $attendanceStatus,
        'scan_time' => $existing['check_in'] ?? $currentTime
    ];


    // Duplicate scan

    if ($existing) {

        sendResponse(
            false,
            'Teacher attendance already marked for today.',
            $teacherDetails,
            $existing['status'],
            $existing['check_in']
        );

    }


    // Insert attendance

    $insert = $db->prepare("
        INSERT INTO teacher_attendance
        (
            teacher_id,
            attendance_date,
            attendance_day,
            check_in,
            status,
            remarks
        )
        VALUES (?, ?, ?, ?, ?, 'QR scan')
    ");

    $insert->bind_param(
        "issss",
        $teacherId,
        $today,
        $day,
        $currentTime,
        $attendanceStatus
    );


    try {

        $insert->execute();

    } catch (mysqli_sql_exception $e) {

        // Another scan may have inserted the record simultaneously.

        if ($e->getCode() == 1062) {

            $insert->close();

            $check = $db->prepare("
                SELECT status, check_in
                FROM teacher_attendance
                WHERE teacher_id = ?
                  AND attendance_date = ?
                LIMIT 1
            ");

            $check->bind_param("is", $teacherId, $today);
            $check->execute();

            $duplicate = $check->get_result()->fetch_assoc();

            $check->close();

            if ($duplicate) {

                $teacherDetails['attendance'] = $duplicate['status'];
                $teacherDetails['scan_time'] = $duplicate['check_in'];

                sendResponse(
                    false,
                    'Teacher attendance already marked for today.',
                    $teacherDetails,
                    $duplicate['status'],
                    $duplicate['check_in']
                );

            }

        }

        throw $e;

    }

    $insert->close();


    // Success

    $teacherDetails['attendance'] = $attendanceStatus;
    $teacherDetails['scan_time'] = $currentTime;


    sendResponse(
        true,
        $attendanceStatus === 'late'
            ? 'Teacher attendance marked successfully - Late.'
            : 'Teacher attendance marked successfully - Present.',
        $teacherDetails,
        $attendanceStatus,
        $currentTime
    );


} catch (Throwable $e) {

    error_log(
        'Teacher QR Attendance Error: ' . $e->getMessage()
    );

    sendResponse(
        false,
        'Server error while marking teacher attendance.'
    );

}
