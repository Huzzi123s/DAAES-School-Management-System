
<?php
include '../config/database.php';

$page_title = 'Classes Management';
$current_page = 'classes';

// =====================================
// HELPER
// =====================================

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// =====================================
// AUTOMATICALLY ENSURE A AND B SECTIONS
// =====================================

$classResult = $conn->query("SELECT id FROM classes");

if ($classResult) {

    $checkSection = $conn->prepare(
        "SELECT id FROM sections
         WHERE class_id = ? AND section_name = ?
         LIMIT 1"
    );

    $insertSection = $conn->prepare(
        "INSERT INTO sections
         (class_id, section_name, capacity, status)
         VALUES (?, ?, 0, 'active')"
    );

    while ($classRow = $classResult->fetch_assoc()) {

        $classId = (int)$classRow['id'];

        foreach (['A', 'B'] as $sectionName) {

            $checkSection->bind_param("is", $classId, $sectionName);
            $checkSection->execute();

            if ($checkSection->get_result()->num_rows === 0) {

                $insertSection->bind_param(
                    "is",
                    $classId,
                    $sectionName
                );

                $insertSection->execute();
            }
        }
    }

    $checkSection->close();
    $insertSection->close();
}

// =====================================
// SEARCH
// =====================================

$search = trim($_GET['search'] ?? '');
$searchValue = "%{$search}%";

// =====================================
// NOTIFICATIONS
// =====================================

$notices = [
    'added' => ['success', 'New class added successfully.'],
    'updated' => ['success', 'Class updated successfully.'],
    'deleted' => ['success', 'Class deleted successfully.'],
    'has_students' => ['error', 'This class cannot be deleted because students are enrolled in it.'],
    'delete_failed' => ['error', 'Unable to delete this class. It may be linked to other records.'],
    'duplicate' => ['error', 'A class with this name already exists.'],
    'invalid' => ['error', 'Invalid request.']
];

$notice = $_GET['notice'] ?? '';

ob_start();
?>

<div class="card">

    <h2>
        <i class="fas fa-school"></i>
        Classes Overview
    </h2>

    <p class="page-description">
        Manage school classes, sections and student enrollment.
    </p>

    <?php if (isset($notices[$notice])): ?>

        <div class="message <?php echo e($notices[$notice][0]); ?>">
            <?php echo e($notices[$notice][1]); ?>
        </div>

    <?php endif; ?>

    <!-- ADD CLASS BUTTON -->

    <div class="buttons" style="margin-top:10px; margin-bottom:20px;">

        <a href="add_class.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Class
        </a>

    </div>

    <!-- SEARCH -->

    <form method="GET" class="search-form">

        <input
            type="text"
            name="search"
            class="search"
            placeholder="Search class..."
            value="<?php echo e($search); ?>"
        >

        <button type="submit">
            <i class="fas fa-search"></i> Search
        </button>

        <a href="classes.php" class="btn btn-secondary">
            Reset
        </a>

    </form>

    <!-- CLASSES TABLE -->

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>Class Name</th>
                    <th>Sections</th>
                    <th>Total Students</th>
                    <th>Class Status</th>
                    <th>Section Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php

            // =====================================
            // CORRECTED QUERY
            // ONE ROW PER CLASS
            // =====================================

            $sql = "

                SELECT

                    c.id,
                    c.class_name,
                    c.class_order,
                    c.status AS class_status,

                    COALESCE(sec.section_names, '-') AS section_names,

                    COALESCE(sec.section_statuses, '-') AS section_statuses,

                    COALESCE(st.total_students, 0) AS total_students

                FROM classes c

                LEFT JOIN (

                    SELECT

                        s.class_id,

                        GROUP_CONCAT(
                            DISTINCT s.section_name
                            ORDER BY s.section_name
                            SEPARATOR ', '
                        ) AS section_names,

                        GROUP_CONCAT(
                            DISTINCT CONCAT(
                                'Section ',
                                s.section_name,
                                ': ',
                                UPPER(LEFT(s.status, 1)),
                                LOWER(SUBSTRING(s.status, 2))
                            )
                            ORDER BY s.section_name
                            SEPARATOR ' | '
                        ) AS section_statuses

                    FROM sections s

                    GROUP BY s.class_id

                ) sec ON sec.class_id = c.id

                LEFT JOIN (

                    SELECT

                        e.class_id,

                        COUNT(DISTINCT e.student_id) AS total_students

                    FROM enrollments e

                    INNER JOIN (

                        SELECT

                            student_id,
                            MAX(id) AS latest_id

                        FROM enrollments

                        GROUP BY student_id

                    ) latest ON latest.latest_id = e.id

                    WHERE e.status = 'active'

                    GROUP BY e.class_id

                ) st ON st.class_id = c.id

                WHERE c.class_name LIKE ?
                   OR CAST(c.class_order AS CHAR) LIKE ?

                ORDER BY c.class_order ASC, c.id ASC

            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ss",
                $searchValue,
                $searchValue
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $counter = 1;

            if ($result && $result->num_rows > 0):

                while ($row = $result->fetch_assoc()):

            ?>

                <tr>

                    <td>
                        <?php echo $counter++; ?>
                    </td>

                    <td>
                        <strong>
                            <?php echo e($row['class_name']); ?>
                        </strong>
                    </td>

                    <td>
                        <strong>
                            <?php echo e($row['section_names']); ?>
                        </strong>
                    </td>

                    <td>
                        <?php echo (int)$row['total_students']; ?>
                    </td>

                    <td>
                        <span class="status">
                            <?php echo e(ucfirst($row['class_status'])); ?>
                        </span>
                    </td>

                    <td>
                        <?php echo e($row['section_statuses']); ?>
                    </td>

                    <td class="action-column">

                        <a
                            href="view_sections.php?class_id=<?php echo (int)$row['id']; ?>"
                            class="action-btn"
                        >
                            <i class="fas fa-layer-group"></i>
                            Sections
                        </a>

                        <a
                            href="edit_class.php?id=<?php echo (int)$row['id']; ?>"
                            class="action-btn"
                        >
                            <i class="fas fa-edit"></i>
                            Edit
                        </a>

                        <form
                            action="delete_class.php"
                            method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('Are you sure you want to delete this class?');"
                        >

                            <input
                                type="hidden"
                                name="class_id"
                                value="<?php echo (int)$row['id']; ?>"
                            >

                            <button type="submit" class="action-btn">
                                <i class="fas fa-trash"></i>
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            <?php

                endwhile;

            else:

            ?>

                <tr>
                    <td colspan="7" class="text-center">
                        No classes found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php

$stmt->close();

$content = ob_get_clean();

include __DIR__ . '/../admin/layouts/admin_layout.php';

?>
