
<?php
include '../config/database.php';

$page_title = 'Class Sections';
$current_page = 'classes';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$class_id = (int)($_GET['class_id'] ?? 0);

// =====================================
// GET CLASS
// =====================================

$stmt = $conn->prepare(
    "SELECT * FROM classes WHERE id = ?"
);

$stmt->bind_param("i", $class_id);
$stmt->execute();

$classResult = $stmt->get_result();

if ($classResult->num_rows === 0) {
    die('Class not found.');
}

$class = $classResult->fetch_assoc();

// =====================================
// SECTION LIST WITH STUDENT COUNTS
// =====================================

$sql = "
    SELECT
        s.id,
        s.section_name,
        s.status,

        COALESCE(student_counts.active_students, 0) AS active_students,
        COALESCE(student_counts.dropout_students, 0) AS dropout_students

    FROM sections s

    LEFT JOIN (

        SELECT
            e.class_id,
            e.section_id,

            SUM(
                CASE
                    WHEN e.status = 'active'
                    AND st.status = 'active'
                    THEN 1 ELSE 0
                END
            ) AS active_students,

            SUM(
                CASE
                    WHEN e.status = 'dropout'
                    OR st.status = 'dropout'
                    THEN 1 ELSE 0
                END
            ) AS dropout_students

        FROM enrollments e

        INNER JOIN (
            SELECT student_id, MAX(id) AS latest_id
            FROM enrollments
            GROUP BY student_id
        ) latest ON latest.latest_id = e.id

        INNER JOIN students st
            ON st.id = e.student_id

        GROUP BY e.class_id, e.section_id

    ) student_counts
        ON student_counts.class_id = s.class_id
        AND student_counts.section_id = s.id

    WHERE s.class_id = ?
      AND s.section_name IN ('A', 'B')

    ORDER BY s.section_name
";

$sectionStmt = $conn->prepare($sql);
$sectionStmt->bind_param("i", $class_id);
$sectionStmt->execute();

$sections = $sectionStmt->get_result();

ob_start();
?>

<div class="card">

    <h2>
        <i class="fas fa-layer-group"></i>
        <?php echo e($class['class_name']); ?> - Sections
    </h2>

    <p class="page-description">
        View Active and Dropout student records in each section.
    </p>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>Section Name</th>
                    <th>Active Students</th>
                    <th>Dropout Students</th>
                    <th>Section Status</th>
                    <th>Student Records</th>
                </tr>
            </thead>

            <tbody>

                <?php $i = 1; ?>

                <?php if ($sections->num_rows > 0): ?>

                    <?php while ($section = $sections->fetch_assoc()): ?>

                        <?php
                        $sectionId = (int)$section['id'];

                        $activeLink = "students_list.php?" . http_build_query([
                            'class_id' => $class_id,
                            'section_id' => $sectionId,
                            'student_status' => 'active'
                        ]);

                        $dropoutLink = "students_list.php?" . http_build_query([
                            'class_id' => $class_id,
                            'section_id' => $sectionId,
                            'student_status' => 'dropout'
                        ]);
                        ?>

                        <tr>

                            <td><?php echo $i++; ?></td>

                            <td>
                                <strong>
                                    Section <?php echo e($section['section_name']); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo (int)$section['active_students']; ?>
                            </td>

                            <td>
                                <?php echo (int)$section['dropout_students']; ?>
                            </td>

                            <td>
                                <?php echo e(ucfirst($section['status'])); ?>
                            </td>

                            <td>

                                <div style="display:flex; gap:8px; flex-wrap:wrap;">

                                    <a
                                        href="<?php echo e($activeLink); ?>"
                                        class="btn btn-primary"
                                    >
                                        <i class="fas fa-user-check"></i>
                                        Active Students
                                    </a>

                                    <a
                                        href="<?php echo e($dropoutLink); ?>"
                                        class="btn btn-secondary"
                                    >
                                        <i class="fas fa-user-times"></i>
                                        Dropout Students
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" style="text-align:center;">
                            No sections found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="buttons">

        <a href="classes.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to Classes
        </a>

    </div>

</div>

<?php
$content = ob_get_clean();

include __DIR__ . '/../admin/layouts/admin_layout.php';
?>
