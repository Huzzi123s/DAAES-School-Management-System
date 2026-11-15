<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/database.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['teacher_csrf'])) {
    $_SESSION['teacher_csrf'] = bin2hex(random_bytes(32));
}

$success_message = '';

if (isset($_GET['added'])) {
    $success_message = 'Teacher added successfully!';
} elseif (isset($_GET['updated'])) {
    $success_message = 'Teacher details updated successfully!';
} elseif (isset($_GET['deleted'])) {
    $success_message = 'Inactive teacher deleted successfully!';
}

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT * FROM teachers
        WHERE full_name LIKE ?
        OR employee_id LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
        ORDER BY id DESC
    ");

    if (!$stmt) {
        die('Database error: ' . e($conn->error));
    }

    $keyword = "%{$search}%";

    $stmt->bind_param("ssss", $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $teachers = $stmt->get_result();
} else {
    $teachers = $conn->query("
        SELECT * FROM teachers
        ORDER BY id DESC
    ");
}

if (!$teachers) {
    die('Database error: ' . e($conn->error));
}

function teacherPhotoUrl($photo) {
    if (empty($photo)) {
        return '';
    }

    $photo = str_replace('\\', '/', trim($photo));
    $photo = ltrim($photo, '/');

    if (strpos($photo, 'uploads/') === 0) {
        return '/DAAES_School_Management/' . $photo;
    }

    return '/DAAES_School_Management/uploads/teachers/' . basename($photo);
}

$page_title = 'All Teachers';
$current_page = 'teachers';

ob_start();

?>

<div class="dashboard-heading">
    <div>
        <h1>Teacher Management</h1>
        <p>View, search and manage all registered teachers.</p>
    </div>
</div>

<style>
.teacher-actions,
.teacher-search-form {
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
    margin:20px 0;
}

.teacher-search-form input {
    flex:1;
    min-width:220px;
}

.teacher-actions .btn,
.teacher-search-form .btn {
    width:auto;
    white-space:nowrap;
}

.teacher-table-container {
    width:100%;
    overflow-x:auto;
}

.teacher-table-container table {
    width:100%;
    min-width:1100px;
}

.teacher-photo {
    width:48px;
    height:48px;
    object-fit:cover;
    border-radius:50%;
    border:2px solid #d5a52b;
}

.teacher-actions .btn-export {
    background:#198754 !important;
    color:#ffffff !important;
    border:1px solid #198754 !important;
    border-radius:6px;
    transition:all .25s ease;
}

.teacher-actions .btn-export:hover {
    background:#146c43 !important;
    border-color:#146c43 !important;
    color:#ffffff !important;
    transform:translateY(-2px);
    box-shadow:0 5px 12px rgba(25,135,84,.25);
}

.teacher-actions .btn-export i {
    color:#ffffff;
}

.teacher-status-active {
    color:#168b50;
    font-weight:700;
}

.teacher-status-inactive {
    color:#d04444;
    font-weight:700;
}

.action-buttons {
    display:flex;
    align-items:center;
    gap:7px;
}

.action-buttons .btn {
    width:auto;
    white-space:nowrap;
    padding:8px 12px;
}

.action-buttons form {
    margin:0;
}

.btn-delete {
    background:#c62828;
    color:white;
    border:0;
    border-radius:6px;
    padding:9px 12px;
    cursor:pointer;
    font-weight:600;
}

.btn-delete:hover {
    background:#a51f1f;
}

.teacher-success-message {
    padding:13px 16px;
    margin:15px 0;
    border-radius:8px;
    background:#e7f7ed;
    color:#176b38;
    border:1px solid #b8e5c7;
    font-weight:600;
}

/* ADD / EDIT MODALS */

.teacher-modal {
    display:none;
    position:fixed;
    inset:0;
    z-index:99999;
    background:rgba(3,10,25,.78);
    backdrop-filter:blur(7px);
    align-items:center;
    justify-content:center;
    padding:20px;
    box-sizing:border-box;
}

.teacher-modal.show {
    display:flex;
    animation:modalFade .25s ease;
}

.teacher-modal-box {
    position:relative;
    width:100%;
    max-width:850px;
    height:90vh;
    max-height:900px;
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 25px 80px rgba(0,0,0,.35);
    animation:modalUp .35s ease;
    display:flex;
    flex-direction:column;
    min-height:0;
}

.teacher-modal-header {
    position:relative;
    flex-shrink:0;
    min-height:85px;
    box-sizing:border-box;
    background:linear-gradient(120deg,#071a35,#123d68);
    color:#fff;
    padding:17px 22px;
    display:flex;
    align-items:center;
    justify-content:flex-start;
}

.teacher-modal-header > div {
    min-width:0;
}

.teacher-modal-header h2 {
    margin:0;
    font-size:20px;
}

.teacher-modal-header p {
    margin:5px 0 0;
    color:#d6e1ef;
    font-size:13px;
}

/* IFRAME SCROLL FIX */

.teacher-modal iframe {
    display:block;
    width:100%;
    height:100%;
    flex:1 1 auto;
    min-height:0;
    border:0;
    background:#f4f6fa;
}

/* DELETE MODAL */

.delete-modal {
    display:none;
    position:fixed;
    inset:0;
    z-index:100000;
    background:rgba(3,10,25,.75);
    align-items:center;
    justify-content:center;
    padding:20px;
    box-sizing:border-box;
}

.delete-modal.show {
    display:flex;
    animation:modalFade .2s ease;
}

.delete-modal-box {
    background:#fff;
    width:100%;
    max-width:420px;
    padding:30px;
    border-radius:16px;
    text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
    animation:modalUp .25s ease;
    box-sizing:border-box;
}

.delete-icon {
    width:65px;
    height:65px;
    border-radius:50%;
    background:#fff0f0;
    color:#c62828;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:27px;
    margin:0 auto 15px;
}

.delete-modal-box h3 {
    color:#142b49;
    margin:0 0 9px;
}

.delete-modal-box p {
    color:#687386;
    line-height:1.6;
}

.delete-modal-actions {
    display:flex;
    justify-content:center;
    gap:10px;
    margin-top:22px;
}

.delete-modal-actions button {
    border:0;
    padding:11px 20px;
    border-radius:7px;
    cursor:pointer;
    font-weight:600;
}

.cancel-delete {
    background:#edf0f5;
    color:#24344c;
}

.confirm-delete {
    background:#c62828;
    color:white;
}

@keyframes modalFade {
    from { opacity:0; }
    to { opacity:1; }
}

@keyframes modalUp {
    from {
        transform:translateY(25px);
        opacity:0;
    }
    to {
        transform:translateY(0);
        opacity:1;
    }
}

@media(max-width:600px) {
    .teacher-actions,
    .teacher-search-form {
        flex-direction:column;
        align-items:stretch;
    }

    .teacher-actions .btn,
    .teacher-search-form .btn,
    .teacher-search-form input {
        width:100%;
        box-sizing:border-box;
    }

    .teacher-modal {
        padding:8px;
    }

    .teacher-modal-box {
        height:96vh;
        border-radius:12px;
    }

    .teacher-modal-header {
        padding:15px;
        min-height:75px;
    }

    .teacher-modal-header h2 {
        font-size:17px;
    }

    .teacher-modal-header p {
        font-size:11px;
    }

    .delete-modal-box {
        padding:22px;
    }
}
</style>

<?php if ($success_message): ?>
    <div class="teacher-success-message">
        <i class="fas fa-check-circle"></i>
        <?= e($success_message); ?>
    </div>
<?php endif; ?>

<div class="record-card">

    <div class="record-heading">
        <div>
            <h2>All Teachers</h2>
            <p>Complete list of registered teachers</p>
        </div>

        <span><?= (int)$teachers->num_rows; ?> Teachers</span>
    </div>

    <div class="teacher-actions">

        <button type="button" class="btn btn-primary" onclick="openTeacherModal()">
            <i class="fas fa-user-plus"></i>
            Add New Teacher
        </button>

        <a
            href="export_teacher.php?<?= e(http_build_query(['search' => $search])); ?>"
            class="btn btn-export"
        >
            <i class="fas fa-file-excel"></i>
            Export Excel
        </a>

    </div>

    <form method="GET" action="view_teacher.php" class="teacher-search-form">

        <input
            type="text"
            name="search"
            placeholder="Search by name, Teacher ID, email or phone..."
            value="<?= e($search); ?>"
        >

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Search
        </button>

        <a href="view_teacher.php" class="btn btn-secondary">
            <i class="fas fa-redo"></i> Reset
        </a>

    </form>

    <div class="teacher-table-container">

        <table class="data-table">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Photo</th>
                    <th>Teacher ID</th>
                    <th>Teacher Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Qualification</th>
                    <th>Joining Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($teachers->num_rows > 0): ?>

                <?php $count = 1; ?>

                <?php while ($teacher = $teachers->fetch_assoc()): ?>

                    <?php
                    $photoUrl = teacherPhotoUrl($teacher['photo'] ?? '');
                    ?>

                    <tr>
                        <td><?= $count++; ?></td>

                        <td>
                            <?php if ($photoUrl): ?>
                                <img
                                    src="<?= e($photoUrl); ?>"
                                    class="teacher-photo"
                                    alt="Teacher Photo"
                                >
                            <?php else: ?>
                                No Photo
                            <?php endif; ?>
                        </td>

                        <td><?= e($teacher['employee_id']); ?></td>

                        <td>
                            <strong><?= e($teacher['full_name']); ?></strong>
                        </td>

                        <td><?= e($teacher['email']); ?></td>

                        <td><?= e($teacher['phone']); ?></td>

                        <td>
                            <?= e(
                                $teacher['academic_qualification']
                                ?? $teacher['qualification']
                                ?? ''
                            ); ?>
                        </td>

                        <td><?= e($teacher['joining_date']); ?></td>

                        <td>
                            <?php if ($teacher['status'] === 'active'): ?>
                                <span class="teacher-status-active">Active</span>
                            <?php else: ?>
                                <span class="teacher-status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="action-buttons">

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    onclick="openEditTeacherModal(<?= (int)$teacher['id']; ?>)"
                                >
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>

                                <a
                                    href="teacher_id_card.php?id=<?= (int)$teacher['id']; ?>"
                                    class="btn btn-primary"
                                    target="_blank"
                                >
                                    <i class="fas fa-id-card"></i>
                                    ID Card
                                </a>

                                <?php if ($teacher['status'] === 'inactive'): ?>

                                    <form
                                        method="POST"
                                        action="delete_teacher.php"
                                        onsubmit="return prepareDelete(event, this, <?= htmlspecialchars(json_encode($teacher['full_name']), ENT_QUOTES, 'UTF-8'); ?>)"
                                    >

                                        <input
                                            type="hidden"
                                            name="teacher_id"
                                            value="<?= (int)$teacher['id']; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['teacher_csrf']); ?>"
                                        >

                                        <button type="submit" class="btn-delete">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="10" style="text-align:center;padding:25px;">
                        <i class="fas fa-users"></i>
                        <p>No teachers found.</p>
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<!-- ADD TEACHER POPUP -->

<div class="teacher-modal" id="teacherModal">

    <div class="teacher-modal-box">

        <div class="teacher-modal-header">
            <div>
                <h2>
                    <i class="fas fa-user-plus"></i>
                    Add New Teacher
                </h2>
                <p>Register a new teacher in DAAES School</p>
            </div>

            <button
                type="button"
                class="teacher-modal-close"
                onclick="closeTeacherModal()"
                aria-label="Close"
                style="display:none;"
            >&times;</button>
        </div>

        <iframe
            src="add_teacher.php?embed=1"
            id="teacherFormFrame"
            title="Add Teacher Form"
        ></iframe>

    </div>
</div>

<!-- EDIT TEACHER POPUP -->

<div class="teacher-modal" id="editTeacherModal">

    <div class="teacher-modal-box">

        <div class="teacher-modal-header">
            <div>
                <h2>
                    <i class="fas fa-user-edit"></i>
                    Edit Teacher
                </h2>
                <p>Update teacher information in DAAES School</p>
            </div>

            <!-- Edit popup ka top cross button intentionally removed -->
        </div>

        <iframe
            src="about:blank"
            id="editTeacherFrame"
            title="Edit Teacher Form"
            scrolling="yes"
        ></iframe>

    </div>
</div>

<!-- DELETE CONFIRMATION POPUP -->

<div class="delete-modal" id="deleteModal">

    <div class="delete-modal-box">

        <div class="delete-icon">
            <i class="fas fa-trash-alt"></i>
        </div>

        <h3>Delete Teacher?</h3>

        <p id="deleteTeacherText">
            Are you sure you want to delete this inactive teacher?
        </p>

        <div class="delete-modal-actions">

            <button type="button" class="cancel-delete" onclick="closeDeleteModal()">
                Cancel
            </button>

            <button type="button" class="confirm-delete" onclick="confirmDelete()">
                Yes, Delete
            </button>

        </div>
    </div>
</div>

<script>
let deleteForm = null;

function openTeacherModal() {
    document.getElementById('teacherModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeTeacherModal() {
    document.getElementById('teacherModal').classList.remove('show');
    document.body.style.overflow = '';
}

function openEditTeacherModal(id) {
    const modal = document.getElementById('editTeacherModal');
    const frame = document.getElementById('editTeacherFrame');

    frame.src = 'edit_teacher.php?id=' + encodeURIComponent(id) + '&embed=1';

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditTeacherModal() {
    const modal = document.getElementById('editTeacherModal');
    const frame = document.getElementById('editTeacherFrame');

    modal.classList.remove('show');
    frame.src = 'about:blank';

    document.body.style.overflow = '';
}

function prepareDelete(event, form, teacherName) {
    event.preventDefault();

    deleteForm = form;

    document.getElementById('deleteTeacherText').textContent =
        'Are you sure you want to delete ' + teacherName +
        '? This action cannot be undone.';

    document.getElementById('deleteModal').classList.add('show');

    return false;
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    deleteForm = null;
}

function confirmDelete() {
    if (deleteForm) {
        deleteForm.submit();
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeTeacherModal();
        closeEditTeacherModal();
        closeDeleteModal();
    }
});

document.getElementById('teacherModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeTeacherModal();
    }
});

document.getElementById('editTeacherModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeEditTeacherModal();
    }
});

document.getElementById('deleteModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include '../admin/layouts/admin_layout.php';
?>