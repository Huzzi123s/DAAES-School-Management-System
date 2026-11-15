
<?php

// =====================================
// PAGE SETTINGS
// =====================================

$page_title = $page_title ?? 'Admin Dashboard';
$current_page = $current_page ?? 'dashboard';
$content = $content ?? '';

$base_url = '/DAAES_School_Management';

$current_page = trim($current_page);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>
        | DAAES School
    </title>

    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css?v=2">

    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/dashboard-layout.css?v=3">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>

/* =====================================
   LOGOUT CONFIRMATION POPUP
===================================== */

.logout-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(2, 10, 25, 0.78);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 20px;
}

.logout-modal-overlay.active {
    display: flex;
}

.logout-modal {
    width: 100%;
    max-width: 430px;
    background: linear-gradient(145deg, #ffffff, #f4f6fa);
    border: 1px solid rgba(212, 175, 55, 0.5);
    border-radius: 22px;
    padding: 35px 30px 25px;
    text-align: center;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.35);
    animation: logoutPopup .25s ease;
}

@keyframes logoutPopup {
    from {
        opacity: 0;
        transform: translateY(20px) scale(.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.logout-icon {
    width: 75px;
    height: 75px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: #07182f;
    color: #d4af37;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 29px;
    border: 3px solid #d4af37;
}

.logout-small-title {
    color: #b18b25;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 3px;
}

.logout-modal-content h2 {
    color: #07182f;
    font-size: 26px;
    margin: 10px 0;
    font-weight: 800;
}

.logout-modal-content p {
    color: #647084;
    font-size: 14px;
    line-height: 1.7;
    margin-bottom: 27px;
}

.logout-modal-actions {
    display: flex;
    gap: 12px;
}

.logout-modal-actions button,
.logout-modal-actions a {
    flex: 1;
    min-height: 46px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: .25s;
}

.logout-cancel-btn {
    background: #edf0f5;
    color: #07182f;
    border: 1px solid #dce1e9;
}

.logout-cancel-btn:hover {
    background: #dfe5ee;
}

.logout-confirm-btn {
    background: #07182f;
    color: #d4af37;
    border: 1px solid #d4af37;
}

.logout-confirm-btn:hover {
    background: #102b4d;
    transform: translateY(-2px);
}

.logout-modal-footer {
    margin-top: 23px;
    padding-top: 16px;
    border-top: 1px solid #e2e6ed;
    color: #8993a3;
    font-size: 11px;
}

.logout-modal-footer i {
    color: #b18b25;
    margin-right: 5px;
}

@media(max-width:420px) {
    .logout-modal {
        padding: 28px 18px 20px;
    }

    .logout-modal-actions {
        flex-direction: column;
    }
}

    </style>

</head>

<body class="dashboard-body">

<div class="admin-dashboard">

    <!-- SIDEBAR -->
    <aside class="dashboard-sidebar">

        <!-- SCHOOL LOGO -->
        <div class="dashboard-logo">

            <div class="dashboard-logo-showcase">

                <div class="dashboard-logo-decoration"></div>

                <span class="dashboard-star dashboard-star-one">✦</span>
                <span class="dashboard-star dashboard-star-two">✧</span>
                <span class="dashboard-star dashboard-star-three">✦</span>

                <div class="dashboard-school-logo-box">

                    <img
                        src="<?php echo $base_url; ?>/assets/images/school-logo.jpeg"
                        alt="DAAES School Logo"
                    >

                </div>

                <h2>DAAES SCHOOL</h2>

                <p>Excellence Through Education</p>

                <div class="dashboard-logo-bottom-line"></div>

            </div>

        </div>

        <div class="sidebar-label">MAIN MENU</div>

        <nav class="dashboard-nav sidebar-nav-large">

            <a href="<?php echo $base_url; ?>/admin/dashboard.php"
               class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?php echo $base_url; ?>/student/view_students.php"
               class="<?php echo $current_page === 'students' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>

            <a href="<?php echo $base_url; ?>/teacher/view_teacher.php"
               class="<?php echo $current_page === 'teachers' ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teachers</span>
            </a>

            <a href="<?php echo $base_url; ?>/classes/classes.php"
               class="<?php echo $current_page === 'classes' ? 'active' : ''; ?>">
                <i class="fas fa-school"></i>
                <span>Classes</span>
            </a>

            <a href="<?php echo $base_url; ?>/attendance/qr_scanner.php"
               class="<?php echo $current_page === 'qr_scanner' ? 'active' : ''; ?>">
                <i class="fas fa-qrcode"></i>
                <span>Student QR Scanner</span>
            </a>

            <a href="<?php echo $base_url; ?>/attendance/attendance_report.php"
               class="<?php echo $current_page === 'attendance' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Digital Student Attendance</span>
            </a>

            <a href="<?php echo $base_url; ?>/attendance/manual_attendance.php"
               class="<?php echo $current_page === 'manual_attendance' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i>
                <span>Manual Student Attendance</span>
            </a>

            <a href="<?php echo $base_url; ?>/attendance/teacher_manual_attendance.php"
               class="<?php echo $current_page === 'teacher_attendance' ? 'active' : ''; ?>">
                <i class="fas fa-user-clock"></i>
                <span>Manual Teacher Attendance</span>
            </a>

            <a href="<?php echo $base_url; ?>/attendance/teacher_attendance_report.php"
               class="<?php echo $current_page === 'teacher_attendance_report' ? 'active' : ''; ?>">
                <i class="fas fa-file-excel"></i>
                <span>Digital Teacher Attendance</span>
            </a>

            <a href="<?php echo $base_url; ?>/attendance/teacher_qr_scanner.php"
               class="<?php echo $current_page === 'teacher_qr_scanner' ? 'active' : ''; ?>">
                <i class="fas fa-qrcode"></i>
                <span>Teacher QR Scanner</span>
            </a>

            <!-- LOGOUT -->
            <a href="<?php echo $base_url; ?>/login/logout.php"
               onclick="openLogoutModal(event)">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>

        </nav>

        <!-- ADMIN PROFILE -->
        <div class="sidebar-bottom">

            <div class="admin-mini-profile">

                <div class="admin-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>

                <div>
                    <strong>
                        <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?>
                    </strong>
                    <span>Super Admin</span>
                </div>

            </div>

        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">

        <header class="dashboard-topbar">

            <div>

                <span class="dashboard-welcome">WELCOME BACK!</span>

                <h2>
                    <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>
                </h2>

            </div>

            <div class="dashboard-top-right">

                <div class="dashboard-date">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('d M Y'); ?>
                </div>

                <div class="top-admin">
                    <i class="fas fa-user-circle"></i>
                    <span>Admin</span>
                </div>

            </div>

        </header>

        <section class="dashboard-content">

            <?php echo $content; ?>

            <footer class="dashboard-footer">
                © <?php echo date('Y'); ?>
                DAAES School. All Rights Reserved.
            </footer>

        </section>

    </main>

</div>

<!-- LOGOUT CONFIRMATION POPUP -->
<div class="logout-modal-overlay" id="logoutModal">

    <div class="logout-modal">

        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>

        <div class="logout-modal-content">

            <span class="logout-small-title">DAAES SCHOOL</span>

            <h2>Ready to Leave?</h2>

            <p>
                Are you sure you want to logout from your
                administrator account?
            </p>

        </div>

        <div class="logout-modal-actions">

            <button type="button"
                    class="logout-cancel-btn"
                    onclick="closeLogoutModal()">
                Stay Logged In
            </button>

            <a href="<?php echo $base_url; ?>/login/logout.php"
               class="logout-confirm-btn">
                <i class="fas fa-sign-out-alt"></i>
                Yes, Logout
            </a>

        </div>

        <div class="logout-modal-footer">
            <i class="fas fa-shield-alt"></i>
            Your session will be securely ended.
        </div>

    </div>

</div>

<script>

function openLogoutModal(event) {

    event.preventDefault();

    document.getElementById('logoutModal').classList.add('active');

    document.body.style.overflow = 'hidden';

}

function closeLogoutModal() {

    document.getElementById('logoutModal').classList.remove('active');

    document.body.style.overflow = '';

}

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {
        closeLogoutModal();
    }

});

document.getElementById('logoutModal').addEventListener('click', function(event) {

    if (event.target === this) {
        closeLogoutModal();
    }

});

</script>

</body>
</html>
