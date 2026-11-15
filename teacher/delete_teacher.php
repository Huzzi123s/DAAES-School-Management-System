
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_teacher.php');
    exit;
}

$teacher_id = (int)($_POST['teacher_id'] ?? 0);
$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['teacher_csrf']) ||
    !hash_equals($_SESSION['teacher_csrf'], $csrf_token)
) {
    die('Invalid request. Please go back and try again.');
}

if ($teacher_id <= 0) {
    header('Location: view_teacher.php');
    exit;
}

// Check teacher status
$stmt = $conn->prepare("
    SELECT status FROM teachers WHERE id = ? LIMIT 1
");

$stmt->bind_param("i", $teacher_id);
$stmt->execute();

$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    header('Location: view_teacher.php');
    exit;
}

// Only inactive teachers can be deleted
if ($teacher['status'] !== 'inactive') {
    die('Only inactive teachers can be deleted.');
}

// Delete inactive teacher only
$delete = $conn->prepare("
    DELETE FROM teachers
    WHERE id = ? AND status = 'inactive'
");

$delete->bind_param("i", $teacher_id);

if (!$delete->execute()) {
    $delete->close();
    die('Unable to delete teacher. This teacher may have linked attendance records.');
}

$delete->close();

header('Location: view_teacher.php?deleted=1');
exit;
