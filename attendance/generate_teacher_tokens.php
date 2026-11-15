<?php
require_once __DIR__ . '/../config/database.php';

$db = $conn ?? $mysqli ?? $con ?? $connection ?? null;

if (!($db instanceof mysqli)) {
    die("Database connection not found.");
}

$result = $db->query("
    SELECT id
    FROM teachers
    WHERE qr_token IS NULL OR qr_token = ''
");

$update = $db->prepare("
    UPDATE teachers
    SET qr_token = ?
    WHERE id = ?
");

$count = 0;

while ($teacher = $result->fetch_assoc()) {
    $token = bin2hex(random_bytes(24));
    $id = (int)$teacher['id'];

    $update->bind_param("si", $token, $id);
    $update->execute();

    $count++;
}

$update->close();

echo "QR tokens generated for {$count} teachers.";