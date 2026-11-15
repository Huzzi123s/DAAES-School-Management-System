
<?php

// =====================================
// ADMIN AUTHENTICATION
// =====================================

require_once __DIR__ . '/../login/admin_auth.php';

// =====================================
// DATABASE CONNECTION
// =====================================

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Karachi');

$db = $conn ?? null;

if (!($db instanceof mysqli)) {
    die('Database connection not found.');
}

// =====================================
// DASHBOARD STATISTICS
// =====================================

function getCount($conn, $sql) {

    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return (int) $row['total'];
    }

    return 0;
}

$total_students = getCount($db, "
    SELECT COUNT(*) AS total FROM students
");

$active_students = getCount($db, "
    SELECT COUNT(*) AS total
    FROM students
    WHERE LOWER(TRIM(status)) = 'active'
");

$total_teachers = getCount($db, "
    SELECT COUNT(*) AS total FROM teachers
");

$active_teachers = getCount($db, "
    SELECT COUNT(*) AS total
    FROM teachers
    WHERE LOWER(TRIM(status)) = 'active'
");

$total_classes = getCount($db, "
    SELECT COUNT(*) AS total FROM classes
");

$total_enrollments = getCount($db, "
    SELECT COUNT(*) AS total FROM enrollments
");

// =====================================
// RECENT STUDENTS
// =====================================

$recent_students = $db->query("
    SELECT student_id, full_name, admission_date, status
    FROM students
    ORDER BY created_at DESC
    LIMIT 5
");

// =====================================
// RECENT TEACHERS
// =====================================

$recent_teachers = $db->query("
    SELECT employee_id, full_name, qualification, joining_date, status
    FROM teachers
    ORDER BY created_at DESC
    LIMIT 5
");

// =====================================
// PAGE SETTINGS
// =====================================

$page_title = 'Admin Dashboard';
$current_page = 'dashboard';

ob_start();

?>

<style>

/* ==========================================
   DAAES - MODERN PREMIUM DASHBOARD
========================================== */

.modern-dashboard {
    width: 100%;
    color: #172b4d;
    padding-bottom: 25px;
}

/* WELCOME BANNER */

.welcome-banner {
    position: relative;
    overflow: hidden;
    background: linear-gradient(115deg, #102744 0%, #1b3d63 65%, #284c70 100%);
    border-radius: 20px;
    padding: 35px 38px;
    margin-bottom: 32px;
    color: #fff;
    min-height: 215px;
    display: flex;
    align-items: center;
    box-shadow: 0 12px 30px rgba(16,39,68,.13);
}

.welcome-content {
    position: relative;
    z-index: 2;
    max-width: 650px;
}

.welcome-tag {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: rgba(255,255,255,.10);
    color: #e8c778;
    padding: 8px 13px;
    border-radius: 30px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.8px;
    margin-bottom: 17px;
}

.welcome-banner h1 {
    color: #fff;
    font-size: clamp(23px, 3vw, 33px);
    font-weight: 800;
    letter-spacing: -.8px;
    margin: 0 0 11px;
}

.welcome-banner p {
    color: #c5d2e1;
    font-size: 13px;
    line-height: 1.8;
    margin: 0 0 17px;
    max-width: 520px;
}

.welcome-date {
    color: #e8c778;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 9px;
}

.welcome-decoration {
    position: absolute;
    right: 65px;
    top: 50%;
    transform: translateY(-50%);
    width: 175px;
    height: 175px;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.welcome-decoration::before {
    content: "";
    position: absolute;
    inset: 15px;
    border: 1px solid rgba(232,199,120,.3);
    border-radius: 50%;
}

.welcome-decoration i {
    font-size: 75px;
    color: #e8c778;
    opacity: .9;
}

/* SECTION HEADINGS */

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 19px;
}

.section-subtitle {
    display: block;
    color: #b38b36;
    font-size: 9px;
    letter-spacing: 2px;
    font-weight: 800;
    margin-bottom: 6px;
}

.section-heading h2 {
    color: #142b4a;
    font-size: 22px;
    font-weight: 800;
    margin: 0;
}

.live-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #e9edf3;
    border-radius: 30px;
    padding: 9px 13px;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
}

.live-indicator span {
    width: 7px;
    height: 7px;
    background: #27ad78;
    border-radius: 50%;
    box-shadow: 0 0 0 4px rgba(39,173,120,.12);
}

/* STAT CARDS */

.modern-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 18px;
    margin-bottom: 34px;
}

.modern-stat-card {
    background: #fff;
    border: 1px solid #e9edf3;
    border-radius: 17px;
    padding: 21px;
    min-width: 0;
    position: relative;
    overflow: hidden;
    transition: .3s ease;
    box-shadow: 0 5px 20px rgba(20,43,74,.035);
}

.modern-stat-card:hover {
    transform: translateY(-6px);
    border-color: #d4deeb;
    box-shadow: 0 14px 28px rgba(20,43,74,.09);
}

.modern-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.modern-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 19px;
}

.students-icon {
    background: #eaf1ff;
    color: #3b70c5;
}

.teachers-icon {
    background: #fff4df;
    color: #b78a32;
}

.classes-icon {
    background: #f0eaff;
    color: #7958c7;
}

.enrollment-icon {
    background: #e5f7f2;
    color: #299878;
}

.modern-stat-label {
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: #a0aabd;
}

.modern-stat-card h3 {
    color: #142b4a;
    font-size: 33px;
    font-weight: 800;
    margin: 0 0 6px;
    letter-spacing: -.8px;
}

.modern-stat-card > p {
    color: #8995a7;
    font-size: 11px;
    margin: 0;
}

.modern-stat-footer {
    border-top: 1px solid #edf0f5;
    margin-top: 18px;
    padding-top: 14px;
    color: #65758c;
    font-size: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.footer-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
}

.green-dot { background: #27ad78; }
.purple-dot { background: #8968d2; }
.gold-dot { background: #d5a63e; }

/* RECENT RECORDS */

.records-section-heading {
    margin-bottom: 18px;
}

.modern-records {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.modern-record-card {
    background: #fff;
    border: 1px solid #e8edf3;
    border-radius: 17px;
    padding: 21px;
    min-width: 0;
    box-shadow: 0 5px 20px rgba(20,43,74,.035);
}

.modern-record-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 21px;
}

.modern-record-title {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.record-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    font-size: 17px;
    flex-shrink: 0;
}

.student-record-icon {
    background: #eaf1ff;
    color: #3b70c5;
}

.teacher-record-icon {
    background: #fff4df;
    color: #b78a32;
}

.modern-record-title h3 {
    color: #1c3453;
    font-size: 14px;
    font-weight: 800;
    margin: 0 0 5px;
}

.modern-record-title p {
    color: #a0aabd;
    font-size: 10px;
    margin: 0;
}

.modern-view-all {
    text-decoration: none;
    color: #b38b36;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
    transition: .2s;
}

.modern-view-all:hover {
    color: #18395f;
}

.modern-view-all i {
    margin-left: 4px;
    font-size: 9px;
}

/* TABLES */

.modern-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 490px;
}

.modern-table thead th {
    background: #f5f7fa;
    color: #8996a9;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .7px;
    padding: 13px 11px;
    text-align: left;
    white-space: nowrap;
}

.modern-table thead th:first-child {
    border-radius: 9px 0 0 9px;
}

.modern-table thead th:last-child {
    border-radius: 0 9px 9px 0;
}

.modern-table tbody td {
    padding: 15px 11px;
    border-bottom: 1px solid #f0f2f6;
    color: #748196;
    font-size: 10px;
    white-space: nowrap;
}

.modern-table tbody tr:last-child td {
    border-bottom: none;
}

.modern-table tbody tr {
    transition: background .2s;
}

.modern-table tbody tr:hover {
    background: #f9fbfd;
}

.record-id {
    color: #345b91;
    font-size: 10px;
    font-weight: 800;
}

.record-person {
    display: flex;
    align-items: center;
    gap: 9px;
}

.record-person strong {
    color: #253b59;
    font-size: 10px;
    font-weight: 700;
}

.record-avatar {
    height: 30px;
    width: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 11px;
}

.student-avatar {
    background: #eaf1ff;
    color: #3b70c5;
}

.teacher-avatar {
    background: #fff4df;
    color: #b78a32;
}

/* STATUS BADGES */

.modern-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 9px;
    border-radius: 30px;
    font-size: 9px;
    font-weight: 800;
}

.modern-status span {
    height: 6px;
    width: 6px;
    border-radius: 50%;
}

.active-status {
    color: #20835e;
    background: #e9f8f0;
}

.active-status span {
    background: #27ad78;
}

.other-status {
    color: #aa762b;
    background: #fff4df;
}

.other-status span {
    background: #d5a63e;
}

.modern-empty {
    text-align: center !important;
    color: #a0aabd !important;
    padding: 28px !important;
}

.modern-empty i {
    margin-right: 7px;
}

/* BOTTOM SUMMARY */

.dashboard-bottom {
    background: linear-gradient(110deg,#142b4a,#24466c);
    border-radius: 17px;
    padding: 22px 25px;
    display: flex;
    align-items: center;
    gap: 17px;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 22px rgba(20,43,74,.10);
}

.bottom-icon {
    height: 48px;
    width: 48px;
    border-radius: 13px;
    background: rgba(255,255,255,.12);
    color: #e9c778;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 19px;
    flex-shrink: 0;
}

.bottom-text {
    flex: 1;
}

.bottom-text h3 {
    font-size: 15px;
    color: #fff;
    font-weight: 800;
    margin: 0 0 5px;
}

.bottom-text p {
    font-size: 11px;
    color: #c3d0df;
    margin: 0;
}

.bottom-total {
    display: flex;
    flex-direction: column;
    text-align: right;
    gap: 4px;
}

.bottom-total span {
    color: #e8c778;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1px;
}

.bottom-total strong {
    color: #fff;
    font-size: 28px;
    font-weight: 800;
}

/* RESPONSIVE */

@media(max-width:1200px) {
    .modern-stats {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }

    .modern-records {
        grid-template-columns: 1fr;
    }
}

@media(max-width:768px) {
    .welcome-banner {
        padding: 27px 22px;
        min-height: 190px;
    }

    .welcome-decoration {
        right: -65px;
        opacity: .25;
    }

    .modern-record-card {
        padding: 15px;
    }

    .section-heading h2 {
        font-size: 19px;
    }
}

@media(max-width:480px) {
    .modern-stats {
        grid-template-columns: 1fr;
    }

    .live-indicator {
        display: none;
    }

    .welcome-banner h1 {
        font-size: 23px;
    }

    .welcome-banner p {
        font-size: 11px;
    }

    .dashboard-bottom {
        flex-wrap: wrap;
        padding: 18px;
    }

    .bottom-total {
        width: 100%;
        text-align: left;
        padding-left: 65px;
    }

    .modern-record-header {
        align-items: flex-start;
    }
}

</style>

<div class="modern-dashboard">

    <!-- WELCOME SECTION -->

    <section class="welcome-banner">

        <div class="welcome-content">

            <span class="welcome-tag">
                <i class="fas fa-graduation-cap"></i>
                DAAES SCHOOL
            </span>

            <h1>Welcome to Your Dashboard</h1>

            <p>
                Manage your school, monitor performance and stay updated
                with everything happening around your institution.
            </p>

            <div class="welcome-date">
                <i class="far fa-calendar-alt"></i>
                <?php echo date('l, d F Y'); ?>
            </div>

        </div>

        <div class="welcome-decoration">
            <i class="fas fa-school"></i>
        </div>

    </section>

    <!-- OVERVIEW HEADING -->

    <div class="section-heading">

        <div>
            <span class="section-subtitle">SCHOOL ANALYTICS</span>
            <h2>Overview</h2>
        </div>

        <span class="live-indicator">
            <span></span> Dashboard Overview
        </span>

    </div>

    <!-- STATISTICS -->

    <section class="modern-stats">

        <div class="modern-stat-card">

            <div class="modern-stat-top">

                <div class="modern-stat-icon students-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>

                <span class="modern-stat-label">STUDENTS</span>

            </div>

            <h3><?php echo number_format($total_students); ?></h3>
            <p>Total Registered Students</p>

            <div class="modern-stat-footer">
                <span class="footer-dot green-dot"></span>
                <?php echo number_format($active_students); ?> Active Students
            </div>

        </div>

        <div class="modern-stat-card">

            <div class="modern-stat-top">

                <div class="modern-stat-icon teachers-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>

                <span class="modern-stat-label">TEACHERS</span>

            </div>

            <h3><?php echo number_format($total_teachers); ?></h3>
            <p>Total Registered Teachers</p>

            <div class="modern-stat-footer">
                <span class="footer-dot green-dot"></span>
                <?php echo number_format($active_teachers); ?> Active Teachers
            </div>

        </div>

        <div class="modern-stat-card">

            <div class="modern-stat-top">

                <div class="modern-stat-icon classes-icon">
                    <i class="fas fa-school"></i>
                </div>

                <span class="modern-stat-label">CLASSES</span>

            </div>

            <h3><?php echo number_format($total_classes); ?></h3>
            <p>Total School Classes</p>

            <div class="modern-stat-footer">
                <span class="footer-dot purple-dot"></span>
                Registered Classes
            </div>

        </div>

        <div class="modern-stat-card">

            <div class="modern-stat-top">

                <div class="modern-stat-icon enrollment-icon">
                    <i class="fas fa-book-open"></i>
                </div>

                <span class="modern-stat-label">ENROLLMENTS</span>

            </div>

            <h3><?php echo number_format($total_enrollments); ?></h3>
            <p>Total Student Enrollments</p>

            <div class="modern-stat-footer">
                <span class="footer-dot gold-dot"></span>
                Class Registrations
            </div>

        </div>

    </section>

    <!-- RECENT RECORDS -->

    <div class="section-heading records-section-heading">

        <div>
            <span class="section-subtitle">LATEST UPDATES</span>
            <h2>Recent Records</h2>
        </div>

    </div>

    <section class="modern-records">

        <!-- STUDENTS -->

        <div class="modern-record-card">

            <div class="modern-record-header">

                <div class="modern-record-title">

                    <div class="record-icon student-record-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>

                    <div>
                        <h3>Recent Admissions</h3>
                        <p>Recently registered students</p>
                    </div>

                </div>

                <a href="/DAAES_School_Management/student/view_students.php"
                   class="modern-view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>

            </div>

            <div class="modern-table-wrapper">

                <table class="modern-table">

                    <thead>
                        <tr>
                            <th>STUDENT ID</th>
                            <th>STUDENT NAME</th>
                            <th>ADMISSION DATE</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($recent_students && $recent_students->num_rows > 0): ?>

                        <?php while ($student = $recent_students->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <span class="record-id">
                                        <?php echo htmlspecialchars($student['student_id'] ?? ''); ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="record-person">

                                        <div class="record-avatar student-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>

                                        <strong>
                                            <?php echo htmlspecialchars($student['full_name'] ?? ''); ?>
                                        </strong>

                                    </div>

                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student['admission_date'] ?? ''); ?>
                                </td>

                                <td>

                                    <span class="modern-status <?php echo strtolower(trim($student['status'] ?? '')) === 'active' ? 'active-status' : 'other-status'; ?>">

                                        <span></span>

                                        <?php echo htmlspecialchars(ucfirst($student['status'] ?? '')); ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="modern-empty">
                                <i class="fas fa-folder-open"></i>
                                No student records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- TEACHERS -->

        <div class="modern-record-card">

            <div class="modern-record-header">

                <div class="modern-record-title">

                    <div class="record-icon teacher-record-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>

                    <div>
                        <h3>Recent Teachers</h3>
                        <p>Recently registered teachers</p>
                    </div>

                </div>

                <a href="/DAAES_School_Management/teacher/view_teacher.php"
                   class="modern-view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>

            </div>

            <div class="modern-table-wrapper">

                <table class="modern-table">

                    <thead>
                        <tr>
                            <th>EMPLOYEE ID</th>
                            <th>TEACHER NAME</th>
                            <th>QUALIFICATION</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($recent_teachers && $recent_teachers->num_rows > 0): ?>

                        <?php while ($teacher = $recent_teachers->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <span class="record-id">
                                        <?php echo htmlspecialchars($teacher['employee_id'] ?? ''); ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="record-person">

                                        <div class="record-avatar teacher-avatar">
                                            <i class="fas fa-user-tie"></i>
                                        </div>

                                        <strong>
                                            <?php echo htmlspecialchars($teacher['full_name'] ?? ''); ?>
                                        </strong>

                                    </div>

                                </td>

                                <td>
                                    <?php echo htmlspecialchars($teacher['qualification'] ?? ''); ?>
                                </td>

                                <td>

                                    <span class="modern-status <?php echo strtolower(trim($teacher['status'] ?? '')) === 'active' ? 'active-status' : 'other-status'; ?>">

                                        <span></span>

                                        <?php echo htmlspecialchars(ucfirst($teacher['status'] ?? '')); ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="modern-empty">
                                <i class="fas fa-folder-open"></i>
                                No teacher records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

    <!-- BOTTOM HIGHLIGHT -->

    <section class="dashboard-bottom">

        <div class="bottom-icon">
            <i class="fas fa-chart-line"></i>
        </div>

        <div class="bottom-text">
            <h3>School at a Glance</h3>
            <p>Your school information, registrations and records in one place.</p>
        </div>

        <div class="bottom-total">
            <span>TOTAL ENROLLMENTS</span>
            <strong><?php echo number_format($total_enrollments); ?></strong>
        </div>

    </section>

</div>

<?php

// =====================================
// LOAD ADMIN LAYOUT
// =====================================

$content = ob_get_clean();

require __DIR__ . '/layouts/admin_layout.php';

?>
