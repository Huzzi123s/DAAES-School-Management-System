
<?php
include '../config/database.php';

$page_title = 'Add New Class';
$current_page = 'classes';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $class_name = trim($_POST['class_name'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($class_name === '') {

        $error = 'Please enter class name.';

    } elseif (!in_array($status, ['active', 'inactive'], true)) {

        $error = 'Invalid class status.';

    } else {

        $check = $conn->prepare(
            "SELECT id FROM classes WHERE class_name = ? LIMIT 1"
        );

        $check->bind_param("s", $class_name);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {

            $error = 'This class already exists.';

        } else {

            $conn->begin_transaction();

            try {

                $orderResult = $conn->query(
                    "SELECT COALESCE(MAX(class_order), 0) + 1 AS next_order FROM classes"
                );

                $orderRow = $orderResult->fetch_assoc();
                $class_order = (int)$orderRow['next_order'];

                $insert = $conn->prepare(
                    "INSERT INTO classes (class_name, class_order, status)
                     VALUES (?, ?, ?)"
                );

                $insert->bind_param(
                    "sis",
                    $class_name,
                    $class_order,
                    $status
                );

                $insert->execute();

                $class_id = $conn->insert_id;

                $sectionInsert = $conn->prepare(
                    "INSERT INTO sections
                     (class_id, section_name, capacity, status)
                     VALUES (?, ?, 0, 'active')"
                );

                foreach (['A', 'B'] as $section_name) {

                    $sectionInsert->bind_param(
                        "is",
                        $class_id,
                        $section_name
                    );

                    $sectionInsert->execute();
                }

                $conn->commit();

                header("Location: classes.php?notice=added");
                exit;

            } catch (Throwable $e) {

                $conn->rollback();

                $error = 'Unable to add class. Please check database settings.';
            }
        }

        $check->close();
    }
}

ob_start();
?>

<div class="form-card">

    <h2>
        <i class="fas fa-plus-circle"></i>
        Add New Class
    </h2>

    <p class="page-description">
        Create a new school class with Section A and Section B.
    </p>

    <?php if ($error): ?>
        <div class="message error">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-grid">

            <div class="form-group full-width">

                <label>Class Name <span class="required">*</span></label>

                <input
                    type="text"
                    name="class_name"
                    placeholder="Example: Class 11"
                    value="<?php echo e($_POST['class_name'] ?? ''); ?>"
                    required
                >

            </div>

            <div class="form-group full-width">

                <label>Class Status</label>

                <select name="status" required>

                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>

                </select>

            </div>

            <div class="form-group full-width">

                <label>Sections</label>

                <input type="text" value="A and B" readonly>

                <small>
                    Both sections will be created automatically.
                    There is no student capacity limit.
                </small>

            </div>

        </div>

        <div class="buttons">

            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Save Class
            </button>

            <a href="classes.php" class="btn btn-secondary">
                Cancel
            </a>

        </div>

    </form>

</div>

<?php
$content = ob_get_clean();

include __DIR__ . '/../admin/layouts/admin_layout.php';?>
