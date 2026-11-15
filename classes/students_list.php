
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/database.php';

$page_title = 'Section Students';
$current_page = 'classes';

date_default_timezone_set('Asia/Karachi');

// =====================================
// HELPER
// =====================================

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// =====================================
// GET PARAMETERS
// =====================================

$class_id = (int)($_GET['class_id'] ?? 0);
$section_id = (int)($_GET['section_id'] ?? 0);

$search = trim($_GET['search'] ?? '');

$student_status = $_GET['student_status'] ?? 'active';

if (!in_array($student_status, ['active', 'dropout'], true)) {
    $student_status = 'active';
}

// =====================================
// VALIDATE CLASS AND SECTION
// =====================================

$info = $conn->prepare("
    SELECT c.class_name, s.section_name
    FROM classes c
    INNER JOIN sections s ON s.class_id = c.id
    WHERE c.id = ?
      AND s.id = ?
      AND s.section_name IN ('A', 'B')
");

if (!$info) {
    die('Section query error: ' . e($conn->error));
}

$info->bind_param("ii", $class_id, $section_id);
$info->execute();

$infoResult = $info->get_result();

if ($infoResult->num_rows === 0) {
    die('Invalid class or section.');
}

$sectionInfo = $infoResult->fetch_assoc();
$info->close();

// =====================================
// STUDENTS QUERY
// =====================================

$sql = "
    SELECT
        st.id,
        st.student_id,
        st.full_name,
        st.father_name,
        st.phone,

        e.id AS enrollment_id,
        e.roll_number,
        e.enrollment_date,
        e.status AS enrollment_status,

        dr.dropout_date

    FROM enrollments e

    INNER JOIN students st
        ON st.id = e.student_id

    LEFT JOIN dropout_records dr
        ON dr.enrollment_id = e.id

    WHERE e.class_id = ?
      AND e.section_id = ?
";

// =====================================
// STATUS FILTER
// =====================================

if ($student_status === 'active') {

    $sql .= "
        AND e.status = 'active'
        AND dr.id IS NULL
    ";

} else {

    $sql .= "
        AND (
            e.status = 'dropout'
            OR dr.id IS NOT NULL
        )
    ";
}

// =====================================
// SEARCH FILTER
// =====================================

if ($search !== '') {

    $sql .= "
        AND (
            st.full_name LIKE ?
            OR st.student_id LIKE ?
            OR e.roll_number LIKE ?
        )
    ";
}

// =====================================
// ORDER
// =====================================

$sql .= "
    ORDER BY e.roll_number ASC, st.full_name ASC
";

// =====================================
// PREPARE
// =====================================

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Students query error: ' . e($conn->error));
}

// =====================================
// BIND PARAMETERS
// =====================================

if ($search !== '') {

    $searchValue = "%{$search}%";

    $stmt->bind_param(
        "iisss",
        $class_id,
        $section_id,
        $searchValue,
        $searchValue,
        $searchValue
    );

} else {

    $stmt->bind_param(
        "ii",
        $class_id,
        $section_id
    );
}

// =====================================
// EXECUTE
// =====================================

if (!$stmt->execute()) {
    die('Query execution failed: ' . e($stmt->error));
}

$result = $stmt->get_result();
$total_students = $result->num_rows;

// =====================================
// PAGE CONTENT
// =====================================

ob_start();

?>

<style>
/* =====================================
   STUDENT SEARCH BAR
===================================== */

.student-search-form {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    max-width: 100%;
    flex-wrap: wrap;
    margin: 20px 0;
    box-sizing: border-box;
}

.student-search-form input[type="text"] {
    flex: 1 1 250px;
    min-width: 0;
    width: auto;
    height: 42px;
    padding: 10px 14px;
    box-sizing: border-box;
}

.student-search-form button,
.student-search-form a {
    flex: 0 0 auto;
    height: 42px;
    min-width: 85px;
    padding: 0 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    box-sizing: border-box;
    white-space: nowrap;
    text-decoration: none;
}

.student-search-form button {
    cursor: pointer;
}

@media (max-width: 600px) {
    .student-search-form input[type="text"] {
        flex-basis: 100%;
        width: 100%;
    }
}

/* =====================================
   STATUS BADGES
===================================== */

.status-active,
.status-dropout {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 13px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.status-active {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
}

.status-dropout {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}
</style>

<div class="card">

    <h2>
        <i class="fas fa-users"></i>

        <?php echo e($sectionInfo['class_name']); ?>

        - Section <?php echo e($sectionInfo['section_name']); ?>
    </h2>

    <p class="page-description">
        <?php if ($student_status === 'active'): ?>
            Active students enrolled in this section.
        <?php else: ?>
            Dropout student records in this section.
        <?php endif; ?>
    </p>

    <h3 class="section-title">
        Total <?php echo ucfirst(e($student_status)); ?> Students:
        <?php echo $total_students; ?>
    </h3>

    <!-- STATUS TABS -->

    <div class="buttons">

        <a
            href="students_list.php?class_id=<?php echo $class_id; ?>&section_id=<?php echo $section_id; ?>&student_status=active"
            class="btn <?php echo $student_status === 'active' ? 'btn-primary' : 'btn-secondary'; ?>"
        >
            <i class="fas fa-user-check"></i>
            Active Students
        </a>

        <a
            href="students_list.php?class_id=<?php echo $class_id; ?>&section_id=<?php echo $section_id; ?>&student_status=dropout"
            class="btn <?php echo $student_status === 'dropout' ? 'btn-primary' : 'btn-secondary'; ?>"
        >
            <i class="fas fa-user-times"></i>
            Dropout Students
        </a>

    </div>

    <!-- SEARCH FORM -->

    <form method="GET" class="student-search-form">

        <input
            type="hidden"
            name="class_id"
            value="<?php echo $class_id; ?>"
        >

        <input
            type="hidden"
            name="section_id"
            value="<?php echo $section_id; ?>"
        >

        <input
            type="hidden"
            name="student_status"
            value="<?php echo e($student_status); ?>"
        >

        <input
            type="text"
            name="search"
            placeholder="Search student name, ID or roll number..."
            value="<?php echo e($search); ?>"
        >

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i>
            Search
        </button>

        <a
            href="students_list.php?class_id=<?php echo $class_id; ?>&section_id=<?php echo $section_id; ?>&student_status=<?php echo e($student_status); ?>"
            class="btn btn-secondary"
        >
            <i class="fas fa-sync-alt"></i>
            Reset
        </a>

    </form>

    <!-- STUDENTS TABLE -->

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Roll Number</th>
                    <th>Student Name</th>
                    <th>Father Name</th>
                    <th>Phone</th>
                    <th>Admission Date</th>
                    <th>Status</th>

                    <?php if ($student_status === 'dropout'): ?>
                        <th>Dropout Date</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php $i = 1; ?>

                    <?php while ($student = $result->fetch_assoc()): ?>

                        <tr>

                            <td><?php echo $i++; ?></td>

                            <td>
                                <?php echo e($student['student_id']); ?>
                            </td>

                            <td>
                                <?php echo e($student['roll_number']); ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo e($student['full_name']); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo e($student['father_name']); ?>
                            </td>

                            <td>
                                <?php echo e($student['phone']); ?>
                            </td>

                            <td>
                                <?php echo e($student['enrollment_date']); ?>
                            </td>

                            <td>

                                <?php if ($student_status === 'active'): ?>

                                    <span class="status-active">
                                        <i class="fas fa-check-circle"></i>
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status-dropout">
                                        <i class="fas fa-times-circle"></i>
                                        Dropout
                                    </span>

                                <?php endif; ?>

                            </td>

                            <?php if ($student_status === 'dropout'): ?>

                                <td>
                                    <?php echo e($student['dropout_date']); ?>
                                </td>

                            <?php endif; ?>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td
                            colspan="<?php echo ($student_status === 'dropout') ? 9 : 8; ?>"
                            class="text-center"
                        >
                            No <?php echo e($student_status); ?>
                            students found in this section.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <!-- BACK BUTTON -->

    <div class="buttons">

        <a
            href="view_sections.php?class_id=<?php echo $class_id; ?>"
            class="btn btn-secondary"
        >
            <i class="fas fa-arrow-left"></i>
            Back to Sections
        </a>

    </div>

</div>

<?php

$content = ob_get_clean();

$stmt->close();

include __DIR__ . '/../admin/layouts/admin_layout.php';

?>
