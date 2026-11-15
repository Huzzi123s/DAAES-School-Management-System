
<?php
ob_start();

date_default_timezone_set('Asia/Karachi');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;

$db = $conn ?? $mysqli ?? $con ?? $connection ?? null;

if (!($db instanceof mysqli)) {
    die("Database connection not found.");
}

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid teacher ID.');
}

$stmt = $db->prepare("
    SELECT *
    FROM teachers
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to fetch teacher record.");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$teacher = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$teacher) {
    die('Teacher record not found.');
}

// ==========================================
// QR TOKEN
// ==========================================

$qr_data = trim($teacher['qr_token'] ?? '');

if ($qr_data === '') {
    die('QR Token missing. Please generate teacher QR tokens first.');
}

// ==========================================
// GENERATE QR LOCALLY - SAME AS STUDENT CARD
// ==========================================

$qr_data_uri = '';
$qr_error = '';

try {
    $qrcode = new QRCode();
    $qr_data_uri = $qrcode->render($qr_data);

    if (
        empty($qr_data_uri) ||
        strpos($qr_data_uri, 'data:image/') !== 0
    ) {
        throw new Exception("QR code could not be generated.");
    }

} catch (Throwable $ex) {
    $qr_error = $ex->getMessage();
}

// ==========================================
// IMAGE PATHS
// ==========================================

$photo = '';

if (!empty($teacher['photo'])) {
    $photo = '../' . ltrim($teacher['photo'], '/');
}

$logo = '../assets/images/school-logo.jpeg';

$academic = $teacher['academic_qualification']
    ?? $teacher['qualification']
    ?? '';

$specific = $teacher['specific_qualification'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Teacher ID Card - <?= e($teacher['full_name']); ?></title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px;
    background: #edf1f6;
    font-family: Arial, sans-serif;
    color: #172c48;
}

.page-heading {
    text-align: center;
    margin-bottom: 25px;
}

.page-heading h2 {
    color: #102c50;
    margin: 0 0 8px;
}

.page-heading p {
    color: #738095;
    margin: 0;
}

.card-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 20px auto;
}

.teacher-id-card {
    width: 340px;
    height: 570px;
    position: relative;
    overflow: hidden;
    background: #fff;
    border-radius: 17px;
    border: 2px solid #c49a36;
    box-shadow: 0 15px 45px rgba(12,35,64,.2);
}

.card-top {
    height: 135px;
    background: linear-gradient(135deg,#071a35,#174675);
    color: #fff;
    text-align: center;
    padding: 13px 12px 0;
    position: relative;
}

.card-top:after {
    content: "";
    position: absolute;
    bottom: -22px;
    left: -5%;
    width: 110%;
    height: 45px;
    background: #fff;
    border-top: 4px solid #c49a36;
    border-radius: 50%;
}

.school-logo {
    width: 48px;
    height: 48px;
    object-fit: contain;
    background: #fff;
    border-radius: 50%;
    padding: 4px;
    border: 2px solid #d4ad54;
}

.school-name {
    font-size: 19px;
    font-weight: 900;
    letter-spacing: 1.5px;
    margin-top: 5px;
}

.school-subtitle {
    font-size: 9px;
    letter-spacing: 2px;
    color: #e6d19a;
    margin-top: 3px;
}

.teacher-photo-box {
    position: relative;
    z-index: 2;
    margin: -2px auto 0;
    width: 98px;
    height: 98px;
    border-radius: 50%;
    padding: 5px;
    background: #fff;
    border: 3px solid #c49a36;
    box-shadow: 0 5px 18px rgba(0,0,0,.16);
}

.teacher-photo-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.photo-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9eef5;
    color: #153d69;
    font-size: 30px;
}

.teacher-name {
    text-align: center;
    font-size: 18px;
    font-weight: 900;
    color: #102c50;
    margin: 8px 12px 3px;
    text-transform: uppercase;
}

.teacher-designation {
    text-align: center;
    color: #b38a2d;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.gold-line {
    height: 2px;
    width: 75%;
    background: linear-gradient(90deg,transparent,#c49a36,transparent);
    margin: 9px auto;
}

.card-details {
    padding: 0 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    padding: 5px 0;
    border-bottom: 1px solid #edf0f4;
    font-size: 10px;
}

.detail-label {
    color: #718096;
    font-weight: 700;
}

.detail-value {
    color: #17365d;
    font-weight: 800;
    text-align: right;
    max-width: 175px;
    overflow-wrap: anywhere;
}

.employee-id {
    text-align: center;
    margin: 9px auto 0;
    background: #f7f1e3;
    color: #8a671b;
    border: 1px solid #e4ce91;
    border-radius: 6px;
    padding: 6px;
    width: 75%;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 1px;
}

.card-bottom {
    position: absolute;
    bottom: 0;
    width: 100%;
    height: 96px;
    background: #0b2444;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 15px;
    color: #fff;
}

.card-bottom img {
    width: 82px;
    height: 82px;
    object-fit: contain;
    background: #fff;
    padding: 3px;
    border-radius: 5px;
    image-rendering: pixelated;
}

.card-bottom-text {
    text-align: right;
    font-size: 9px;
    line-height: 1.5;
    color: #e4d19e;
    letter-spacing: .5px;
}

.card-bottom-text strong {
    color: #fff;
    display: block;
    font-size: 11px;
}

.qr-error {
    color: #fff;
    font-size: 10px;
    max-width: 90px;
    overflow-wrap: anywhere;
}

.print-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin: 25px auto;
}

.print-actions button,
.print-actions a {
    border: 0;
    border-radius: 8px;
    padding: 12px 20px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

.print-button {
    color: #fff;
    background: #123e68;
}

.back-button {
    color: #17365d;
    background: #fff;
    border: 1px solid #d8e0eb !important;
}

@media(max-width:450px) {
    body {
        padding: 15px 5px;
    }

    .print-actions {
        flex-wrap: wrap;
    }
}

@media print {

    @page {
        size: 90mm 151mm;
        margin: 0;
    }

    html, body {
        width: 90mm;
        height: 151mm;
        margin: 0;
        padding: 0;
        background: #fff;
    }

    .page-heading,
    .print-actions {
        display: none !important;
    }

    .card-wrapper {
        margin: 0;
        padding: 0;
        width: 90mm;
        height: 151mm;
    }

    .teacher-id-card {
        width: 90mm;
        height: 151mm;
        border-radius: 0;
        box-shadow: none;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

</style>
</head>

<body>

<div class="page-heading">
    <h2>Teacher ID Card Generated</h2>
    <p>Your teacher's unique identity card is ready.</p>
</div>

<div class="card-wrapper">

    <div class="teacher-id-card">

        <div class="card-top">

            <img
                src="<?= e($logo); ?>"
                class="school-logo"
                alt="DAAES School Logo"
            >

            <div class="school-name">DAAES SCHOOL</div>

            <div class="school-subtitle">
                TEACHER IDENTITY CARD
            </div>

        </div>

        <div class="teacher-photo-box">

            <?php if ($photo): ?>

                <img src="<?= e($photo); ?>" alt="Teacher Photo">

            <?php else: ?>

                <div class="photo-placeholder">T</div>

            <?php endif; ?>

        </div>

        <div class="teacher-name">
            <?= e($teacher['full_name']); ?>
        </div>

        <div class="teacher-designation">
            TEACHING STAFF
        </div>

        <div class="gold-line"></div>

        <div class="card-details">

            <div class="detail-row">
                <span class="detail-label">Father Name</span>
                <span class="detail-value"><?= e($teacher['father_name']); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Gender</span>
                <span class="detail-value"><?= e($teacher['gender']); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Academic Qualification</span>
                <span class="detail-value"><?= e($academic); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Specific Qualification</span>
                <span class="detail-value"><?= e($specific); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value"><?= e($teacher['phone']); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Joining Date</span>
                <span class="detail-value"><?= e($teacher['joining_date']); ?></span>
            </div>

        </div>

        <div class="employee-id">
            <?= e($teacher['employee_id']); ?>
        </div>

        <div class="card-bottom">

            <?php if ($qr_data_uri): ?>

                <img
                    src="<?= e($qr_data_uri); ?>"
                    alt="Teacher QR Code"
                >

            <?php else: ?>

                <div class="qr-error">
                    QR ERROR: <?= e($qr_error); ?>
                </div>

            <?php endif; ?>

            <div class="card-bottom-text">
                <strong>OFFICIAL STAFF CARD</strong>
                PROPERTY OF DAAES SCHOOL
                <br>
                <?= strtoupper(e($teacher['status'])); ?>
            </div>

        </div>

    </div>

</div>

<div class="print-actions">

    <button class="print-button" onclick="window.print()">
        Print ID Card
    </button>

    <a href="view_teacher.php" class="back-button">
        Back to Teachers
    </a>

</div>

</body>
</html>

<?php
ob_end_flush();
?>
