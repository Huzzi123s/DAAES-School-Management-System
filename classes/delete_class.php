
<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: classes.php?notice=invalid");
    exit;
}

$class_id = (int)($_POST['class_id'] ?? 0);

if ($class_id <= 0) {
    header("Location: classes.php?notice=invalid");
    exit;
}

// Check class exists
$checkClass = $conn->prepare(
    "SELECT id FROM classes WHERE id = ?"
);

$checkClass->bind_param("i", $class_id);
$checkClass->execute();

if ($checkClass->get_result()->num_rows === 0) {
    header("Location: classes.php?notice=invalid");
    exit;
}

// Check existing enrollments
$checkEnrollment = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM enrollments
     WHERE class_id = ?"
);

$checkEnrollment->bind_param("i", $class_id);
$checkEnrollment->execute();

$enrollmentResult = $checkEnrollment->get_result();
$enrollmentRow = $enrollmentResult->fetch_assoc();

if ((int)$enrollmentRow['total'] > 0) {

    header("Location: classes.php?notice=has_students");
    exit;
}

// Delete class and sections safely
$conn->begin_transaction();

try {

    $deleteSections = $conn->prepare(
        "DELETE FROM sections WHERE class_id = ?"
    );

    $deleteSections->bind_param("i", $class_id);
    $deleteSections->execute();

    $deleteClass = $conn->prepare(
        "DELETE FROM classes WHERE id = ?"
    );

    $deleteClass->bind_param("i", $class_id);
    $deleteClass->execute();

    if ($deleteClass->affected_rows !== 1) {
        throw new Exception("Class deletion failed.");
    }

    $conn->commit();

    header("Location: classes.php?notice=deleted");
    exit;

} catch (Throwable $e) {

    $conn->rollback();

    header("Location: classes.php?notice=delete_failed");
    exit;
}
?>
