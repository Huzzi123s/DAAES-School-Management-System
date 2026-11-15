
<?php

// ==========================================
// SESSION AND CACHE SETTINGS
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ==========================================
// CLEAR STUDENT ADMISSION NOTICE
// ==========================================

if (
    isset($_GET['clear_notice']) &&
    $_GET['clear_notice'] === '1'
) {
    unset($_SESSION['student_added_notice']);
    unset($_SESSION['student_card_id']);
}

// ==========================================
// DATABASE AND QR LIBRARY
// ==========================================

include '../config/database.php';

require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;

// ==========================================
// HELPER
// ==========================================

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ==========================================
// GET STUDENT ID
// ==========================================

$student_db_id = (int)($_GET['id'] ?? 0);

if ($student_db_id <= 0) {
    die("Invalid Student ID.");
}

// ==========================================
// FETCH STUDENT DETAILS
// ==========================================

$stmt = $conn->prepare("
    SELECT *
    FROM students
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database query error: " . e($conn->error));
}

$stmt->bind_param("i", $student_db_id);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$student) {
    die("Student record not found.");
}

// ==========================================
// GENERATE QR TOKEN IF MISSING
// ==========================================

if (empty($student['qr_token'])) {

    $student['qr_token'] = bin2hex(random_bytes(16));

    $update = $conn->prepare("
        UPDATE students
        SET qr_token = ?
        WHERE id = ?
    ");

    if (!$update) {
        die("Unable to update student QR token.");
    }

    $update->bind_param(
        "si",
        $student['qr_token'],
        $student_db_id
    );

    if (!$update->execute()) {
        die("Unable to save student QR token.");
    }

    $update->close();
}

// ==========================================
// GENERATE QR CODE
// ==========================================

$qr_error = '';
$qr_data_uri = '';

try {

    $qrcode = new QRCode();

    $qr_data_uri = $qrcode->render($student['qr_token']);

    if (
        empty($qr_data_uri) ||
        strpos($qr_data_uri, 'data:image/') !== 0
    ) {
        throw new Exception(
            "QR code could not be generated. Please check your QR package version."
        );
    }

} catch (Throwable $ex) {

    $qr_error = $ex->getMessage();
}

// ==========================================
// IMAGE PATHS
// ==========================================

$logo = '../assets/images/school-logo.jpeg';

$photo = '';

if (!empty($student['photo'])) {

    $photo_name = basename($student['photo']);

    $photo_file = __DIR__ . '/../assets/uploads/students/' . $photo_name;

    if (is_file($photo_file)) {
        $photo = '../assets/uploads/students/' . rawurlencode($photo_name);
    }
}

// ==========================================
// SCHOOL DETAILS
// ==========================================

$school_name = 'Dr Aftab Abro Education System';

$school_address = 'Plot A-1, Near Rehmat Masjid, Mehran Town Extension, Allahwala Town, Korangi';

$school_phone = '0336-3875074';

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student ID Card - <?= e($student['full_name']) ?></title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #eef1f6;
    font-family: Arial, sans-serif;
    padding: 30px;
    color: #17243b;
}

.page-heading {
    text-align: center;
    margin-bottom: 25px;
}

.page-heading h1 {
    color: #10264a;
    font-size: 25px;
    margin-bottom: 8px;
}

.page-heading p {
    color: #68758a;
}

.card-container {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 30px;
    margin: 25px auto;
}

.id-card {
    width: 340px;
    height: 214px;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 25px rgba(0,0,0,0.13);
    border: 1px solid #d8dce4;
}

/* FRONT CARD */

.front-header {
    height: 65px;
    background: #10264a;
    color: white;
    display: flex;
    align-items: center;
    padding: 8px 12px;
    gap: 10px;
    border-bottom: 4px solid #c9a448;
}

.school-logo {
    width: 47px;
    height: 47px;
    object-fit: contain;
    background: white;
    border-radius: 50%;
    padding: 3px;
}

.school-heading {
    flex: 1;
    text-align: center;
}

.school-heading h2 {
    font-size: 13px;
    line-height: 1.4;
    color: #fff;
}

.school-heading p {
    font-size: 8px;
    color: #e8c875;
    letter-spacing: 1px;
    margin-top: 3px;
}

.front-body {
    display: flex;
    padding: 12px 11px;
    gap: 12px;
}

.student-photo-box {
    width: 91px;
    height: 111px;
    border: 3px solid #c9a448;
    border-radius: 7px;
    overflow: hidden;
    background: #edf0f5;
    flex-shrink: 0;
}

.student-photo-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-photo {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 11px;
    text-align: center;
}

.student-details {
    flex: 1;
    min-width: 0;
}

.student-details h3 {
    color: #10264a;
    font-size: 14px;
    text-transform: uppercase;
    margin-bottom: 8px;
    border-bottom: 1px solid #c9a448;
    padding-bottom: 5px;
    overflow-wrap: anywhere;
}

.detail-row {
    display: flex;
    margin-bottom: 7px;
    font-size: 9px;
    line-height: 1.3;
}

.detail-row strong {
    width: 70px;
    color: #10264a;
    flex-shrink: 0;
}

.detail-row span {
    color: #45536a;
    overflow-wrap: anywhere;
}

.front-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 13px;
    background: #10264a;
    border-top: 3px solid #c9a448;
}

/* BACK CARD */

.back-header {
    height: 56px;
    background: #10264a;
    border-bottom: 4px solid #c9a448;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    color: white;
    padding: 5px;
}

.back-header img {
    width: 38px;
    height: 38px;
    object-fit: contain;
    background: white;
    border-radius: 50%;
    padding: 2px;
}

.back-header h2 {
    font-size: 13px;
    text-align: center;
}

.back-content {
    padding: 11px 13px;
    display: flex;
    gap: 10px;
}

.back-info {
    flex: 1;
    min-width: 0;
}

.back-info h3 {
    font-size: 11px;
    color: #10264a;
    margin-bottom: 7px;
    border-bottom: 1px solid #c9a448;
    padding-bottom: 4px;
}

.back-info p {
    font-size: 9px;
    line-height: 1.5;
    color: #45536a;
    margin-bottom: 6px;
}

.back-info strong {
    color: #10264a;
}

.qr-box {
    width: 95px;
    flex-shrink: 0;
    text-align: center;
}

.qr-box img {
    display: block;
    width: 85px;
    height: 85px;
    object-fit: contain;
    border: 2px solid #c9a448;
    padding: 3px;
    border-radius: 5px;
    background: white;
}

.qr-box p {
    font-size: 8px;
    font-weight: bold;
    color: #10264a;
    margin-top: 3px;
}

.qr-error {
    font-size: 8px;
    color: #b91c1c;
    overflow-wrap: anywhere;
}

.back-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 13px;
    width: 100%;
    background: #10264a;
    border-top: 3px solid #c9a448;
}

/* BUTTONS */

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin: 30px auto;
    flex-wrap: wrap;
}

.action-buttons button,
.action-buttons a {
    padding: 12px 22px;
    border: none;
    border-radius: 7px;
    background: #10264a;
    color: white;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

.action-buttons .print-btn {
    background: #c9a448;
    color: #10264a;
}

.action-buttons button:hover,
.action-buttons a:hover {
    opacity: 0.85;
}

@media(max-width: 500px) {

    body {
        padding: 15px 5px;
    }

    .id-card {
        max-width: 96vw;
    }
}

/* PRINT */

@page {
    size: A4 landscape;
    margin: 15mm;
}

@media print {

    body {
        background: white;
        padding: 0;
    }

    .page-heading,
    .action-buttons {
        display: none !important;
    }

    .card-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12mm;
        margin: 0;
    }

    .id-card {
        box-shadow: none;
        border: 1px solid #aaa;
        flex-shrink: 0;
        page-break-inside: avoid;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

</style>

</head>

<body>

<div class="page-heading">

    <h1>DAAES Student ID Card</h1>

    <p>Student Identity Card Preview</p>

</div>

<div class="card-container">

    <!-- FRONT SIDE -->

    <div class="id-card">

        <div class="front-header">

            <?php if (is_file(__DIR__ . '/../assets/images/school-logo.jpeg')): ?>

                <img
                    src="<?= e($logo) ?>"
                    class="school-logo"
                    alt="School Logo"
                >

            <?php endif; ?>

            <div class="school-heading">

                <h2><?= e($school_name) ?></h2>

                <p>STUDENT IDENTITY CARD</p>

            </div>

        </div>

        <div class="front-body">

            <div class="student-photo-box">

                <?php if ($photo): ?>

                    <img src="<?= e($photo) ?>" alt="Student Photo">

                <?php else: ?>

                    <div class="no-photo">
                        NO PHOTO
                    </div>

                <?php endif; ?>

            </div>

            <div class="student-details">

                <h3><?= e($student['full_name']) ?></h3>

                <div class="detail-row">
                    <strong>Student ID:</strong>
                    <span><?= e($student['student_id']) ?></span>
                </div>

                <div class="detail-row">
                    <strong>Father:</strong>
                    <span><?= e($student['father_name']) ?></span>
                </div>

                <div class="detail-row">
                    <strong>DOB:</strong>
                    <span>
                        <?= !empty($student['date_of_birth'])
                            ? date('d M Y', strtotime($student['date_of_birth']))
                            : '-' ?>
                    </span>
                </div>

                <div class="detail-row">
                    <strong>Gender:</strong>
                    <span><?= e($student['gender']) ?></span>
                </div>

                <div class="detail-row">
                    <strong>Phone:</strong>
                    <span><?= e($student['phone']) ?></span>
                </div>

            </div>

        </div>

        <div class="front-footer"></div>

    </div>

    <!-- BACK SIDE -->

    <div class="id-card">

        <div class="back-header">

            <?php if (is_file(__DIR__ . '/../assets/images/school-logo.jpeg')): ?>

                <img src="<?= e($logo) ?>" alt="School Logo">

            <?php endif; ?>

            <h2><?= e($school_name) ?></h2>

        </div>

        <div class="back-content">

            <div class="back-info">

                <h3>IMPORTANT INFORMATION</h3>

                <p>
                    <strong>1.</strong>
                    This card is the property of DAAES School.
                </p>

                <p>
                    <strong>2.</strong>
                    Students should carry this card while attending school.
                </p>

                <p>
                    <strong>3.</strong>
                    If found, please return this card to the school.
                </p>

                <h3>SCHOOL CONTACT</h3>

                <p>
                    <strong>Phone:</strong>
                    <?= e($school_phone) ?>
                </p>

                <p>
                    <strong>Address:</strong>
                    <?= e($school_address) ?>
                </p>

            </div>

            <!-- QR CODE -->

            <div class="qr-box">

                <?php if (!empty($qr_data_uri)): ?>

                    <img
                        src="<?= e($qr_data_uri) ?>"
                        alt="Student QR Code"
                    >

                    <p>STUDENT QR CODE</p>

                <?php else: ?>

                    <p class="qr-error">QR GENERATION ERROR</p>

                    <small class="qr-error">
                        <?= e($qr_error) ?>
                    </small>

                <?php endif; ?>

            </div>

        </div>

        <div class="back-footer"></div>

    </div>

</div>

<!-- ACTION BUTTONS -->

<div class="action-buttons">

    <button class="print-btn" onclick="window.print()">
        🖨 Print ID Card
    </button>

    <a href="view_students.php">
        Back to Students
    </a>

</div>

</body>
</html>
