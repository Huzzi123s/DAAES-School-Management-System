
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/database.php';

date_default_timezone_set('Asia/Karachi');

// POST request only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_students.php");
    exit;
}

// CSRF verification
$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['student_csrf']) ||
    !hash_equals($_SESSION['student_csrf'], $csrf_token)
) {
    header("Location: view_students.php?delete_error=1");
    exit;
}

// Student ID
$student_id = (int)($_POST['student_id'] ?? 0);

if ($student_id <= 0) {
    header("Location: view_students.php?delete_error=1");
    exit;
}

try {

    $conn->begin_transaction();

    // Check student exists
    $student_stmt = $conn->prepare(
        "SELECT id FROM students WHERE id = ? LIMIT 1"
    );

    if (!$student_stmt) {
        throw new Exception("Student query failed");
    }

    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();

    $student = $student_stmt->get_result()->fetch_assoc();
    $student_stmt->close();

    if (!$student) {
        throw new Exception("Student not found");
    }

    // Get latest active enrollment
    $enrollment_stmt = $conn->prepare("
        SELECT id, class_id, section_id
        FROM enrollments
        WHERE student_id = ?
        AND status = 'active'
        ORDER BY id DESC
        LIMIT 1
    ");

    if (!$enrollment_stmt) {
        throw new Exception("Enrollment query failed");
    }

    $enrollment_stmt->bind_param("i", $student_id);
    $enrollment_stmt->execute();

    $enrollment = $enrollment_stmt->get_result()->fetch_assoc();
    $enrollment_stmt->close();

    if (!$enrollment) {
        throw new Exception("Active enrollment not found");
    }

    $enrollment_id = (int)$enrollment['id'];
    $class_id = (int)$enrollment['class_id'];
    $section_id = (int)$enrollment['section_id'];

    $dropout_date = date('Y-m-d');
    $dropout_reason = '';

    // Save dropout history
    $insert_stmt = $conn->prepare("
        INSERT INTO dropout_records
        (
            student_id,
            class_id,
            section_id,
            enrollment_id,
            dropout_date,
            dropout_reason,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$insert_stmt) {
        throw new Exception("Dropout record query failed");
    }

    $insert_stmt->bind_param(
        "iiiiss",
        $student_id,
        $class_id,
        $section_id,
        $enrollment_id,
        $dropout_date,
        $dropout_reason
    );

    if (!$insert_stmt->execute()) {
        throw new Exception("Unable to save dropout record");
    }

    $insert_stmt->close();

    // Update enrollment status
    $update_stmt = $conn->prepare("
        UPDATE enrollments
        SET status = 'dropout'
        WHERE id = ?
        AND student_id = ?
        AND status = 'active'
    ");

    if (!$update_stmt) {
        throw new Exception("Enrollment update failed");
    }

    $update_stmt->bind_param("ii", $enrollment_id, $student_id);

    if (!$update_stmt->execute() || $update_stmt->affected_rows !== 1) {
        throw new Exception("Unable to update enrollment");
    }

    $update_stmt->close();

    // Complete transaction
    $conn->commit();

    header("Location: view_students.php?dropout=1");
    exit;

} catch (Throwable $e) {

    $conn->rollback();

    error_log("Dropout Error: " . $e->getMessage());

    header("Location: view_students.php?delete_error=1");
    exit;
}
