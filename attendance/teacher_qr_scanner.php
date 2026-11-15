
<?php
require_once __DIR__ . '/../config/database.php';

$page_title = "Teacher QR Attendance Scanner";
$current_page = "teacher_qr_scanner";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Teacher QR Scanner | DAAES School</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        .qr-page, .qr-page * {
            box-sizing: border-box;
        }

        .qr-page {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .qr-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 11px 19px;
            margin-bottom: 20px;
            background: #d4af37;
            color: #08111f;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 800;
            transition: .3s;
        }

        .qr-back-btn:hover {
            background: #f0cf63;
            transform: translateY(-2px);
        }

        .qr-heading {
            margin-bottom: 25px;
        }

        .qr-heading h1 {
            color: #04152f;
            font-size: 28px;
            margin: 10px 0;
        }

        .qr-heading p {
            color: #78869a;
        }

        .qr-status {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 20px;
            background: rgba(212,175,55,.15);
            color: #b18a20;
            font-size: 12px;
            font-weight: 700;
        }

        .qr-layout {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 22px;
        }

        .qr-panel {
            min-width: 0;
            background: #0c1728;
            border: 1px solid rgba(212,175,55,.3);
            border-radius: 15px;
            padding: 25px;
            color: #fff;
        }

        .qr-panel h2 {
            color: #d4af37;
            margin-bottom: 20px;
            font-size: 19px;
        }

        #qr-reader {
            width: 100%;
            overflow: hidden;
            border-radius: 12px;
            background: white;
        }

        #qr-reader__dashboard_section_csr button {
            background: #d4af37 !important;
            color: #08111f !important;
            border: none !important;
            padding: 10px 15px !important;
            border-radius: 7px !important;
        }

        .qr-btn {
            width: 100%;
            min-height: 45px;
            padding: 12px 18px;
            margin-top: 13px;
            border: none;
            border-radius: 8px;
            background: #d4af37;
            color: #08111f;
            font-weight: 800;
            cursor: pointer;
        }

        .qr-btn:hover {
            background: #f0cf63;
        }

        .qr-btn.stop {
            background: #334155;
            color: white;
        }

        .qr-result {
            min-height: 200px;
            padding: 18px;
            border-radius: 10px;
            background: #101f33;
            border: 1px solid rgba(212,175,55,.2);
        }

        .qr-result p {
            margin: 13px 0;
            word-break: break-word;
            font-size: 13px;
        }

        .qr-result strong {
            color: #d4af37;
        }

        .qr-message {
            padding: 12px;
            margin-top: 15px;
            border-radius: 8px;
            display: none;
            line-height: 1.5;
            font-size: 13px;
        }

        .qr-message.success {
            display: block;
            background: rgba(22,163,74,.15);
            color: #4ade80;
        }

        .qr-message.error {
            display: block;
            background: rgba(220,38,38,.15);
            color: #f87171;
        }

        .qr-message.info {
            display: block;
            background: rgba(212,175,55,.12);
            color: #f0cf63;
        }

        @media(max-width:768px) {
            .qr-layout {
                grid-template-columns: 1fr;
            }

            .qr-page {
                padding: 12px;
                margin: 15px auto;
            }

            .qr-panel {
                padding: 17px;
            }

            .qr-heading h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<div class="qr-page">

    <!-- BACK TO DASHBOARD -->
    <a href="/DAAES_School_Management/admin/dashboard.php"
       class="qr-back-btn">
        <i class="fas fa-arrow-left"></i>
        Go Back to Dashboard
    </a>

    <div class="qr-heading">

        <span class="qr-status">DAAES SCHOOL MANAGEMENT</span>

        <h1>Teacher QR Attendance</h1>

        <p>Scan teacher QR codes to mark daily attendance.</p>

    </div>

    <div class="qr-layout">

        <!-- CAMERA SCANNER -->
        <div class="qr-panel">

            <h2>📷 Scan Teacher QR Code</h2>

            <div id="qr-reader"></div>

            <button type="button" class="qr-btn" id="startScanner">
                Start Camera Scanner
            </button>

            <button type="button" class="qr-btn stop"
                    id="stopScanner" style="display:none;">
                Stop Camera
            </button>

            <div id="scannerMessage" class="qr-message info">
                Camera is ready. Click Start Camera Scanner.
            </div>

        </div>

        <!-- TEACHER DETAILS -->
        <div class="qr-panel">

            <h2>Teacher Attendance Details</h2>

            <div class="qr-result" id="teacherResult">

                <p><strong>Teacher Name:</strong> Waiting for scan...</p>
                <p><strong>Employee ID:</strong> --</p>
                <p><strong>Attendance:</strong> --</p>
                <p><strong>Check In:</strong> --</p>
                <p><strong>Attendance Date:</strong> --</p>

            </div>

            <div id="attendanceMessage" class="qr-message"></div>

        </div>

    </div>

</div>

<script>
let html5QrCode = null;
let scannerRunning = false;
let processingScan = false;

const startButton = document.getElementById('startScanner');
const stopButton = document.getElementById('stopScanner');

const scannerMessage = document.getElementById('scannerMessage');
const attendanceMessage = document.getElementById('attendanceMessage');
const teacherResult = document.getElementById('teacherResult');

function showScannerMessage(message, type = 'info') {
    scannerMessage.className = 'qr-message ' + type;
    scannerMessage.textContent = message;
}

function showAttendanceMessage(message, type = 'info') {
    attendanceMessage.className = 'qr-message ' + type;
    attendanceMessage.textContent = message;
}

function escapeHTML(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function extractToken(decodedText) {
    decodedText = decodedText.trim();

    try {
        const data = JSON.parse(decodedText);

        if (data.qr_token) return String(data.qr_token).trim();
        if (data.token) return String(data.token).trim();

    } catch (error) {}

    try {
        const url = new URL(decodedText);

        const token =
            url.searchParams.get('qr_token') ||
            url.searchParams.get('token');

        if (token) return token.trim();

    } catch (error) {}

    return decodedText;
}

async function startScanner() {
    if (scannerRunning || processingScan) return;

    try {
        if (!window.Html5Qrcode) {
            throw new Error('QR Scanner library could not load.');
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qr-reader');
        }

        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            async (decodedText) => {
                if (processingScan) return;

                processingScan = true;
                await stopScanner();

                await sendAttendance(extractToken(decodedText));
            },
            () => {}
        );

        scannerRunning = true;

        startButton.style.display = 'none';
        stopButton.style.display = 'block';

        showScannerMessage(
            'Camera started. Place teacher QR code inside the scanning area.',
            'success'
        );

    } catch (error) {
        scannerRunning = false;
        processingScan = false;

        showScannerMessage('Camera error: ' + error.message, 'error');
    }
}

async function stopScanner() {
    if (!html5QrCode || !scannerRunning) return;

    try {
        await html5QrCode.stop();

        scannerRunning = false;

        startButton.style.display = 'block';
        stopButton.style.display = 'none';

        showScannerMessage('Camera stopped.', 'info');

    } catch (error) {
        console.error(error);
    }
}

function displayTeacher(teacher, attendanceStatus, scanTime, attendanceDate) {
    teacherResult.innerHTML = `
        <p><strong>Teacher Name:</strong>
            ${escapeHTML(teacher.full_name)}</p>

        <p><strong>Employee ID:</strong>
            ${escapeHTML(teacher.employee_id)}</p>

        <p><strong>Attendance:</strong>
            ${escapeHTML(attendanceStatus)}</p>

        <p><strong>Check In:</strong>
            ${escapeHTML(scanTime)}</p>

        <p><strong>Attendance Date:</strong>
            ${escapeHTML(attendanceDate)}</p>
    `;
}

async function sendAttendance(token) {
    try {
        if (!token) throw new Error('Invalid teacher QR code.');

        teacherResult.innerHTML = '<p>Verifying teacher QR code...</p>';

        showAttendanceMessage('Checking teacher information...', 'info');

        const formData = new URLSearchParams();
        formData.append('qr_token', token);

        const response = await fetch('teacher_process_scan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        });

        const data = await response.json();

        if (data.teacher) {
            displayTeacher(
                data.teacher,
                data.attendance_status || data.teacher.attendance || '--',
                data.scan_time || data.teacher.scan_time || '--',
                data.attendance_date || new Date().toLocaleDateString('en-GB')
            );
        }

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || 'Teacher attendance could not be marked.'
            );
        }

        showAttendanceMessage(
            data.message || 'Teacher attendance marked successfully!',
            'success'
        );

    } catch (error) {
        showAttendanceMessage(error.message, 'error');

    } finally {
        processingScan = false;
    }
}

startButton.addEventListener('click', startScanner);
stopButton.addEventListener('click', stopScanner);
</script>

</body>
</html>
