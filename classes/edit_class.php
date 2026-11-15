
<?php
include '../config/database.php';

$page_title = 'Edit Class';
$current_page = 'classes';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Class not found.');
}

$class = $result->fetch_assoc();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $class_name = trim($_POST['class_name'] ?? '');
    $class_order = (int)($_POST['class_order'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($class_name === '' || $class_order < 1) {

        $error = 'Please enter valid class details.';

    } elseif (!in_array($status, ['active', 'inactive'], true)) {

        $error = 'Invalid class status.';

    } else {

        $check = $conn->prepare(
            "SELECT id FROM classes
             WHERE class_name = ? AND id != ?
             LIMIT 1"
        );

        $check->bind_param("si", $class_name, $id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {

            $error = 'Another class already has this name.';

        } else {

            $update = $conn->prepare(
                "UPDATE classes
                 SET class_name = ?, class_order = ?, status = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "sisi",
                $class_name,
                $class_order,
                $status,
                $id
            );

            if ($update->execute()) {

                header("Location: classes.php?notice=updated");
                exit;

            } else {

                $error = 'Unable to update class.';
            }

            $update->close();
        }

        $check->close();
    }
}

ob_start();
?>

<div class="form-card">

    <h2>
        <i class="fas fa-edit"></i>
        Edit Class
    </h2>

    <p class="page-description">
        Update class information.
    </p>

    <?php if ($error): ?>
        <div class="message error">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-grid">

            <div class="form-group full-width">

                <label>Class Name</label>

                <input
                    type="text"
                    name="class_name"
                    value="<?php echo e($_POST['class_name'] ?? $class['class_name']); ?>"
                    required
                >

            </div>

            <div class="form-group full-width">

                <label>Class Order</label>

                <input
                    type="number"
                    name="class_order"
                    min="1"
                    value="<?php echo e($_POST['class_order'] ?? $class['class_order']); ?>"
                    required
                >

            </div>

            <div class="form-group full-width">

                <label>Status</label>

                <select name="status" required>

                    <option value="active"
                        <?php echo (($_POST['status'] ?? $class['status']) === 'active') ? 'selected' : ''; ?>>
                        Active
                    </option>

                    <option value="inactive"
                        <?php echo (($_POST['status'] ?? $class['status']) === 'inactive') ? 'selected' : ''; ?>>
                        Inactive
                    </option>

                </select>

            </div>

        </div>

        <div class="buttons">

            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Update Class
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
