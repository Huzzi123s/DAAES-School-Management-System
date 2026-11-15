
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

include '../config/database.php';

date_default_timezone_set('Asia/Karachi');

// =====================================
// CSRF TOKEN
// =====================================

if (empty($_SESSION['student_csrf'])) {
    $_SESSION['student_csrf'] = bin2hex(random_bytes(32));
}

$student_csrf = $_SESSION['student_csrf'];

// =====================================
// PAGE SETTINGS
// =====================================

$page_title = 'Students Management';
$current_page = 'students';

$message = '';
$message_type = '';
$show_modal = false;
$card_id = 0;

$form_class_id = 0;
$form_section_id = 0;

// =====================================
// HELPER
// =====================================

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// =====================================
// SUCCESS / ERROR NOTICES
// =====================================

if (isset($_GET['updated'])) {

    $message = 'Student details updated successfully!';
    $message_type = 'success';

} elseif (isset($_GET['dropout'])) {

    $message = 'Student successfully dropped out!';
    $message_type = 'success';

} elseif (isset($_GET['delete_error'])) {

    $message = 'Unable to dropout student. Related records may exist.';
    $message_type = 'error';

}

if (!empty($_SESSION['student_added_notice'])) {

    $message = 'Student added successfully!';
    $message_type = 'success';

    $card_id = (int)($_SESSION['student_card_id'] ?? 0);

    unset($_SESSION['student_added_notice']);
    unset($_SESSION['student_card_id']);
}

// =====================================
// FETCH CLASSES
// =====================================

$class_result = $conn->query("
    SELECT MIN(id) AS id, MIN(class_name) AS class_name
    FROM classes
    GROUP BY LOWER(TRIM(class_name))
    ORDER BY MIN(id)
");

if (!$class_result) {
    die('Class Error: ' . e($conn->error));
}

$class_list = [];

while ($class = $class_result->fetch_assoc()) {
    $class_list[] = $class;
}

// =====================================
// FETCH SECTIONS
// =====================================

$section_result = $conn->query("
    SELECT
        id,
        class_id,
        UPPER(TRIM(section_name)) AS section_name
    FROM sections
    WHERE UPPER(TRIM(section_name)) IN ('A', 'B')
    ORDER BY class_id, section_name
");

if (!$section_result) {
    die('Section Error: ' . e($conn->error));
}

$section_data = [];

while ($section = $section_result->fetch_assoc()) {
    $section_data[] = $section;
}

// =====================================
// ADD NEW STUDENT
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {

    $show_modal = true;

    $full_name = trim($_POST['full_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');

    $dob = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';

    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['parent_phone'] ?? '');

    $class_id = (int)($_POST['class_id'] ?? 0);
    $section_id = (int)($_POST['section_id'] ?? 0);

    $form_class_id = $class_id;
    $form_section_id = $section_id;

    $admission_date = $_POST['admission_date'] ?? '';
    $academic_year = trim($_POST['academic_year'] ?? '');

    $photo = null;
    $uploaded_file = null;
    $transaction_started = false;

    $upload_dir = __DIR__ . '/../assets/uploads/students/';

    try {

        if (
            $full_name === '' ||
            $father_name === '' ||
            $dob === '' ||
            $gender === '' ||
            $address === '' ||
            $phone === '' ||
            $class_id <= 0 ||
            $section_id <= 0 ||
            $admission_date === '' ||
            $academic_year === ''
        ) {
            throw new Exception('Please fill all required fields.');
        }

        if (!in_array($gender, ['Male', 'Female'], true)) {
            throw new Exception('Please select Male or Female.');
        }

        // VERIFY CLASS AND SECTION

        $check = $conn->prepare("
            SELECT id
            FROM sections
            WHERE id = ?
              AND class_id = ?
              AND UPPER(TRIM(section_name)) IN ('A', 'B')
            LIMIT 1
        ");

        if (!$check) {
            throw new Exception($conn->error);
        }

        $check->bind_param('ii', $section_id, $class_id);
        $check->execute();

        $valid = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$valid) {
            throw new Exception('Invalid class or section selected.');
        }

        // PHOTO UPLOAD

        if (
            isset($_FILES['photo']) &&
            $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Photo upload failed.');
            }

            if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                throw new Exception('Photo must be below 2 MB.');
            }

            $image_info = @getimagesize($_FILES['photo']['tmp_name']);

            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $mime = is_array($image_info)
                ? ($image_info['mime'] ?? '')
                : '';

            if (!$image_info || !isset($allowed_types[$mime])) {
                throw new Exception('Only valid JPG, PNG and WEBP images are allowed.');
            }

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception('Unable to create student upload folder.');
                }
            }

            $file_name = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime];

            $uploaded_file = $upload_dir . $file_name;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploaded_file)) {
                throw new Exception('Unable to save student photo.');
            }

            $photo = $file_name;
        }

        // DATABASE TRANSACTION

        $conn->begin_transaction();
        $transaction_started = true;

        $student_id = 'DAAES-' . date('Y') . '-' .
            strtoupper(bin2hex(random_bytes(4)));

        $qr_token = bin2hex(random_bytes(16));

        $status = 'active';

        // INSERT STUDENT

        $stmt_insert = $conn->prepare("
            INSERT INTO students
            (
                student_id,
                full_name,
                father_name,
                mother_name,
                gender,
                date_of_birth,
                admission_date,
                phone,
                address,
                photo,
                qr_token,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt_insert) {
            throw new Exception($conn->error);
        }

        $stmt_insert->bind_param(
            'ssssssssssss',
            $student_id,
            $full_name,
            $father_name,
            $mother_name,
            $gender,
            $dob,
            $admission_date,
            $phone,
            $address,
            $photo,
            $qr_token,
            $status
        );

        if (!$stmt_insert->execute()) {
            throw new Exception($stmt_insert->error);
        }

        $new_student_id = $conn->insert_id;
        $stmt_insert->close();

        // GENERATE ROLL NUMBER

      // =====================================
// GENERATE CLASS-WISE ROLL NUMBER
// SECTION DOES NOT AFFECT ROLL NUMBER
// =====================================

$count = $conn->prepare("
    SELECT
        MAX(CAST(roll_number AS UNSIGNED)) AS last_roll
    FROM enrollments
    WHERE class_id = ?
      AND academic_year = ?
");

if (!$count) {
    throw new Exception($conn->error);
}

$count->bind_param(
    'is',
    $class_id,
    $academic_year
);

$count->execute();

$count_result = $count->get_result()->fetch_assoc();

$count->close();

$last_roll = (int)($count_result['last_roll'] ?? 0);

$roll_number = str_pad(
    $last_roll + 1,
    3,
    '0',
    STR_PAD_LEFT
);
        // CREATE ENROLLMENT

        $enrollment = $conn->prepare("
            INSERT INTO enrollments
            (
                student_id,
                class_id,
                section_id,
                academic_year,
                roll_number,
                enrollment_date,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$enrollment) {
            throw new Exception($conn->error);
        }

        $enrollment->bind_param(
            'iiissss',
            $new_student_id,
            $class_id,
            $section_id,
            $academic_year,
            $roll_number,
            $admission_date,
            $status
        );

        if (!$enrollment->execute()) {
            throw new Exception($enrollment->error);
        }

        $enrollment->close();

        // COMMIT

        $conn->commit();
        $transaction_started = false;

        $_SESSION['student_added_notice'] = true;
        $_SESSION['student_card_id'] = $new_student_id;

        header('Location: view_students.php');
        exit;

    } catch (Throwable $ex) {

        if ($transaction_started) {
            $conn->rollback();
        }

        if ($uploaded_file && is_file($uploaded_file)) {
            unlink($uploaded_file);
        }

        $message = 'Admission failed: ' . $ex->getMessage();
        $message_type = 'error';
    }
}

// =====================================
// SEARCH AND FILTERS
// =====================================

$search = trim($_GET['search'] ?? '');
$class_filter = (int)($_GET['class_id'] ?? 0);
$section_filter = strtoupper(trim($_GET['section'] ?? ''));

if (!in_array($section_filter, ['', 'A', 'B'], true)) {
    $section_filter = '';
}

// =====================================
// STUDENTS QUERY
// ACTIVE STUDENTS ONLY
// =====================================

$sql = "
    SELECT
        s.*,
        e.class_id,
        e.section_id,
        e.academic_year,
        e.roll_number,
        c.class_name,
        sec.section_name

    FROM students s

    INNER JOIN enrollments e ON e.id = (
        SELECT MAX(e2.id)
        FROM enrollments e2
        WHERE e2.student_id = s.id
    )

    INNER JOIN classes c ON c.id = e.class_id
    INNER JOIN sections sec ON sec.id = e.section_id

    WHERE e.status = 'active'
";

$params = [];
$types = '';

if ($search !== '') {

    $sql .= "
        AND (
            s.full_name LIKE ?
            OR s.student_id LIKE ?
            OR s.father_name LIKE ?
            OR s.phone LIKE ?
        )
    ";

    $term = "%{$search}%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;

    $types .= 'ssss';
}

if ($class_filter > 0) {
    $sql .= ' AND e.class_id = ?';
    $params[] = $class_filter;
    $types .= 'i';
}

if ($section_filter !== '') {
    $sql .= ' AND UPPER(TRIM(sec.section_name)) = ?';
    $params[] = $section_filter;
    $types .= 's';
}

$sql .= ' ORDER BY s.id DESC';

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Student Query Error: ' . e($conn->error));
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$students = $stmt->get_result();


// =====================================
// EXPORT STUDENTS TO EXCEL - REAL XLSX
// =====================================

if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    require_once __DIR__ . '/../vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Student Records');

    // SCHOOL COLORS
    $navy = '17365D';
    $darkNavy = '102943';
    $gold = 'D5A52B';
    $white = 'FFFFFF';
    $lightBlue = 'EAF0F7';
    $lightGray = 'F1F5F9';
    $border = 'DCE3EB';

    $spreadsheet->getProperties()
        ->setCreator('DAAES School')
        ->setTitle('DAAES School - Student Records')
        ->setSubject('Official Student Database')
        ->setDescription('DAAES School Student Records Report');

    // TITLE

    $sheet->mergeCells('A1:N1');

    $sheet->setCellValue('A1', 'DAAES SCHOOL');

    $sheet->getStyle('A1:N1')->applyFromArray([

        'font' => [
            'name' => 'Calibri',
            'bold' => true,
            'size' => 25,
            'color' => ['rgb' => $white]
        ],

        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => $navy]
        ],

        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ]

    ]);

    $sheet->getRowDimension(1)->setRowHeight(52);

    // SUBTITLE

    $sheet->mergeCells('A2:N2');

    $sheet->setCellValue('A2', 'OFFICIAL STUDENT DATABASE');

    $sheet->getStyle('A2:N2')->applyFromArray([

        'font' => [
            'bold' => true,
            'size' => 15,
            'color' => ['rgb' => $navy]
        ],

        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFF4D6']
        ],

        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ]

    ]);

    $sheet->getRowDimension(2)->setRowHeight(34);

    // REPORT INFORMATION

    $total = $students->num_rows;

    $filterDetails = [];

    if ($search !== '') {
        $filterDetails[] = 'Search: ' . $search;
    }

    if ($class_filter > 0) {
        $filterDetails[] = 'Class ID: ' . $class_filter;
    }

    if ($section_filter !== '') {
        $filterDetails[] = 'Section: ' . $section_filter;
    }

    $reportType = empty($filterDetails)
        ? 'All Active Students'
        : implode(' | ', $filterDetails);

    $sheet->mergeCells('A3:N3');

    $sheet->setCellValue(
        'A3',
        'TOTAL STUDENTS: ' . $total .
        '  |  ' . $reportType .
        '  |  GENERATED: ' . date('d M Y, h:i A')
    );

    $sheet->getStyle('A3:N3')->applyFromArray([

        'font' => [
            'bold' => true,
            'size' => 10,
            'color' => ['rgb' => $darkNavy]
        ],

        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => $lightGray]
        ],

        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ]

    ]);

    $sheet->getRowDimension(3)->setRowHeight(32);

    // GOLD SEPARATOR

    $sheet->mergeCells('A4:N4');

    $sheet->getStyle('A4:N4')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB($gold);

    $sheet->getRowDimension(4)->setRowHeight(6);

    // TABLE HEADINGS

    $columns = [
        '#',
        'Student ID',
        'Full Name',
        'Father Name',
        'Mother Name',
        'Gender',
        'Date of Birth',
        'Class',
        'Section',
        'Roll Number',
        'Parent Phone',
        'Address',
        'Admission Date',
        'Academic Year'
    ];

    $sheet->fromArray($columns, null, 'A5');

    $sheet->getStyle('A5:N5')->applyFromArray([

        'font' => [
            'bold' => true,
            'size' => 11,
            'color' => ['rgb' => $white]
        ],

        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => $navy]
        ],

        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],

        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => $gold]
            ]
        ]

    ]);

    $sheet->getRowDimension(5)->setRowHeight(36);

    // STUDENT RECORDS

    $rowNumber = 6;
    $counter = 1;

    while ($student = $students->fetch_assoc()) {

        $row = [

            $counter,
            $student['student_id'],
            $student['full_name'],
            $student['father_name'],
            $student['mother_name'] ?? '-',
            $student['gender'] ?? '-',
            $student['date_of_birth'] ?? '-',
            $student['class_name'] ?? 'Not Assigned',
            $student['section_name'] ?? 'Not Assigned',
            $student['roll_number'] ?? '-',
            $student['phone'] ?? '-',
            $student['address'] ?? '-',
            $student['admission_date'] ?? '-',
            $student['academic_year'] ?? '-'

        ];

        foreach ($row as $index => $value) {

            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);

            $sheet->setCellValueExplicit(
                $column . $rowNumber,
                (string)($value ?? '-'),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

        }

        $rowColor = ($counter % 2 === 0)
            ? $lightBlue
            : $white;

        $sheet->getStyle("A{$rowNumber}:N{$rowNumber}")
            ->applyFromArray([

                'font' => [
                    'name' => 'Calibri',
                    'size' => 10,
                    'color' => ['rgb' => '263445']
                ],

                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $rowColor]
                ],

                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],

                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR,
                        'color' => ['rgb' => $border]
                    ]
                ]

            ]);

        $sheet->getStyle("A{$rowNumber}:B{$rowNumber}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("F{$rowNumber}:K{$rowNumber}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getRowDimension($rowNumber)->setRowHeight(30);

        $counter++;
        $rowNumber++;

    }

    // EMPTY RECORDS

    if ($total === 0) {

        $sheet->mergeCells('A6:N6');

        $sheet->setCellValue('A6', 'NO STUDENT RECORDS FOUND');

        $sheet->getStyle('A6:N6')->applyFromArray([

            'font' => [
                'bold' => true,
                'size' => 13,
                'color' => ['rgb' => '64748B']
            ],

            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => $lightGray]
            ],

            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]

        ]);

        $sheet->getRowDimension(6)->setRowHeight(40);

    }

    // COLUMN WIDTHS

    $widths = [

        'A' => 8,
        'B' => 22,
        'C' => 27,
        'D' => 25,
        'E' => 25,
        'F' => 14,
        'G' => 18,
        'H' => 19,
        'I' => 13,
        'J' => 16,
        'K' => 21,
        'L' => 40,
        'M' => 19,
        'N' => 18

    ];

    foreach ($widths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }

    // FILTER AND FREEZE

    $lastRow = max(5, $rowNumber - 1);

    if ($total > 0) {
        $sheet->setAutoFilter("A5:N{$lastRow}");
    }

    $sheet->freezePane('A6');

    // PRINT SETTINGS

    $sheet->getPageSetup()
        ->setOrientation(
            \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
        )
        ->setPaperSize(
            \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
        )
        ->setFitToWidth(1)
        ->setFitToHeight(0);

    $sheet->getPageSetup()->setFitToPage(true);

    $sheet->getPageMargins()
        ->setTop(0.5)
        ->setBottom(0.5)
        ->setLeft(0.25)
        ->setRight(0.25);

    $sheet->getPageSetup()->setPrintArea("A1:N{$lastRow}");

    // DOWNLOAD XLSX

    $filename = 'DAAES_Students_' . date('Y-m-d_H-i-s') . '.xlsx';

    $stmt->close();
    $conn->close();

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    header(
        'Content-Disposition: attachment; filename="' . $filename . '"'
    );

    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    $writer->save('php://output');

    $spreadsheet->disconnectWorksheets();

    exit;
}

// =====================================
// PAGE CONTENT
// =====================================

ob_start();
?>

<div class="container student-page">

    <div class="dashboard-heading">
        <div>
            <h1>Students Management</h1>
            <p>Manage and view all registered students.</p>
        </div>
    </div>

    <?php if ($message): ?>

        <div class="student-notice <?= e($message_type); ?>" id="studentNotice">

            <?= e($message); ?>

            <?php if ($card_id > 0): ?>
                <br>

                <a
                    href="student_id_card.php?id=<?= $card_id; ?>&clear_notice=1"
                    class="student-card-link"
                >
                    <i class="fas fa-id-card"></i>
                    View Student ID Card
                </a>
            <?php endif; ?>

        </div>

    <?php endif; ?>

    <!-- ADD STUDENT BUTTON -->

    <div class="student-top-actions">

        <button type="button" class="btn btn-primary" id="openStudentModal">
            <i class="fas fa-user-plus"></i>
            Add New Student
        </button>

    </div>

    <!-- SEARCH AND FILTERS -->

    <div class="record-card">

        <div class="record-heading">
            <div>
                <h2>Search Students</h2>
                <p>Search by name, ID, father name or phone.</p>
            </div>
        </div>

        <form method="GET" action="view_students.php" class="student-filter-form">

            <div class="form-grid">

                <div class="form-group">
                    <label>Search Student</label>

                    <input
                        type="text"
                        name="search"
                        placeholder="Name, Student ID, Father Name or Phone"
                        value="<?= e($search); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Filter Class</label>

                    <select name="class_id">
                        <option value="">All Classes</option>

                        <?php foreach ($class_list as $class): ?>
                            <option
                                value="<?= (int)$class['id']; ?>"
                                <?= $class_filter === (int)$class['id'] ? 'selected' : ''; ?>
                            >
                                <?= e($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Filter Section</label>

                    <select name="section">
                        <option value="">All Sections</option>

                        <option value="A" <?= $section_filter === 'A' ? 'selected' : ''; ?>>
                            A
                        </option>

                        <option value="B" <?= $section_filter === 'B' ? 'selected' : ''; ?>>
                            B
                        </option>

                    </select>
                </div>

            </div>

            <div class="student-filter-actions">

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Search / Filter
                </button>

                <a href="view_students.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i>
                    Reset
                </a>

            </div>

        </form>

    </div>

    <!-- STUDENTS TABLE -->

    <div class="record-card">

        <div class="record-heading">
            <div>
                <h2>Students List</h2>
                <p>All active student records.</p>
            </div>

            <div class="student-export-actions">

                <span class="status-badge status-active">
                    <?= (int)$students->num_rows; ?> Students
                </span>

                <a
                    href="view_students.php?<?= e(http_build_query([
                        'search' => $search,
                        'class_id' => $class_filter,
                        'section' => $section_filter,
                        'export' => 'excel'
                    ])); ?>"
                    class="btn student-export-btn"
                >
                    <i class="fas fa-file-excel"></i>
                    Export Excel
                </a>

            </div>
        </div>

        <div class="student-table-container">

            <table class="dashboard-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Photo</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Father Name</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Roll Number</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($students->num_rows > 0): ?>

                    <?php $counter = 1; ?>

                    <?php while ($student = $students->fetch_assoc()): ?>

                        <tr>

                            <td><?= $counter++; ?></td>

                            <td>

                                <?php if (!empty($student['photo'])): ?>

                                    <img
                                        src="../assets/uploads/students/<?= e($student['photo']); ?>"
                                        alt="Student Photo"
                                        class="student-photo"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                    >

                                    <span class="no-student-photo" style="display:none;">
                                        No Photo
                                    </span>

                                <?php else: ?>

                                    <i class="fas fa-user-circle student-default-icon"></i>

                                <?php endif; ?>

                            </td>

                            <td><?= e($student['student_id']); ?></td>

                            <td>
                                <strong><?= e($student['full_name']); ?></strong>
                            </td>

                            <td><?= e($student['father_name']); ?></td>

                            <td><?= e($student['class_name'] ?? 'Not Assigned'); ?></td>

                            <td><?= e($student['section_name'] ?? 'Not Assigned'); ?></td>

                            <td><?= e($student['roll_number'] ?? '-'); ?></td>

                            <td><?= e($student['phone']); ?></td>

                            <td>

                                <div class="student-action-buttons">

                                    <a
                                        href="edit_students.php?id=<?= (int)$student['id']; ?>"
                                        class="btn btn-primary"
                                    >
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>

                                    <a
                                        href="student_id_card.php?id=<?= (int)$student['id']; ?>"
                                        class="btn student-id-card-btn"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <i class="fas fa-id-card"></i>
                                        ID Card
                                    </a>

                                    <button
                                        type="button"
                                        class="btn student-dropout-btn"
                                        data-id="<?= (int)$student['id']; ?>"
                                        data-name="<?= e($student['full_name']); ?>"
                                    >
                                        <i class="fas fa-trash-alt"></i>
                                        Dropout
                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="10" class="empty-record">
                            No active students found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- =====================================
     ADD STUDENT MODAL
===================================== -->

<div class="student-modal-overlay" id="studentModal">

    <div class="student-modal">

        <div class="student-modal-header">

            <div>
                <h2>New Student Admission</h2>
                <p>Enter student personal and admission details.</p>
            </div>

        </div>

        <?php if ($show_modal && $message): ?>

            <div class="student-notice error">
                <?= e($message); ?>
            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
            action="view_students.php"
            id="studentAdmissionForm"
        >

            <input type="hidden" name="add_student" value="1">

            <div class="student-add-form">

                <h3 class="student-section-title">Personal Information</h3>

                <div class="form-group">
                    <label>Student Full Name *</label>
                    <input type="text" name="full_name" placeholder="Enter student name" required>
                </div>

                <div class="form-group">
                    <label>Father Name *</label>
                    <input type="text" name="father_name" placeholder="Enter father name" required>
                </div>

                <div class="form-group">
                    <label>Mother Name</label>
                    <input type="text" name="mother_name" placeholder="Enter mother name">
                </div>

                <div class="form-group">
                    <label>Date of Birth *</label>
                    <input type="date" name="date_of_birth" required>
                </div>

                <div class="form-group gender-group">
                    <label>Gender *</label>

                    <div class="gender-options">

                        <label class="gender-option">
                            <input type="radio" name="gender" value="Male" required>
                            <span>
                                <i class="fas fa-check-circle"></i>
                                Male
                            </span>
                        </label>

                        <label class="gender-option">
                            <input type="radio" name="gender" value="Female" required>
                            <span>
                                <i class="fas fa-check-circle"></i>
                                Female
                            </span>
                        </label>

                    </div>
                </div>

                <div class="form-group">
                    <label>Parent Phone Number *</label>
                    <input type="tel" name="parent_phone" placeholder="Enter contact number" required>
                </div>

                <div class="form-group full-width">
                    <label>Student Address *</label>
                    <textarea name="address" placeholder="Enter complete address" required></textarea>
                </div>

                <h3 class="student-section-title">Admission Information</h3>

                <div class="form-group">
                    <label>Admission Date *</label>
                    <input
                        type="date"
                        name="admission_date"
                        value="<?= date('Y-m-d'); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Academic Year *</label>
                    <input
                        type="text"
                        name="academic_year"
                        value="<?= date('Y') . '-' . (date('Y') + 1); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Select Class *</label>

                    <select name="class_id" id="class_id" required>
                        <option value="">Choose Class</option>

                        <?php foreach ($class_list as $class): ?>
                            <option
                                value="<?= (int)$class['id']; ?>"
                                <?= $form_class_id === (int)$class['id'] ? 'selected' : ''; ?>
                            >
                                <?= e($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group section-group">

                    <label>Select Section *</label>

                    <div class="section-options" id="sectionOptions">

                        <?php foreach ($section_data as $section): ?>

                            <label class="section-option">

                                <input
                                    type="radio"
                                    name="section_id"
                                    value="<?= (int)$section['id']; ?>"
                                    data-class="<?= (int)$section['class_id']; ?>"
                                    <?= $form_section_id === (int)$section['id'] ? 'checked' : ''; ?>
                                    disabled
                                    required
                                >

                                <span>
                                    <i class="fas fa-check-circle"></i>
                                    Section <?= e($section['section_name']); ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                    <small class="section-hint" id="sectionHint">
                        First select a class to see available sections.
                    </small>

                </div>

                <div class="form-group full-width">
                    <label>Student Photo</label>

                    <input
                        type="file"
                        name="photo"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>JPG, PNG or WEBP. Maximum 2 MB.</small>
                </div>

            </div>

            <div class="student-add-actions">

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Add Student
                </button>

                <button type="button" class="btn btn-secondary" id="goBackModal">
                    <i class="fas fa-arrow-left"></i>
                    Go Back
                </button>

            </div>

        </form>

    </div>

</div>

<!-- =====================================
     DROPOUT CONFIRMATION POPUP
===================================== -->

<div class="dropout-overlay" id="dropoutModal">

    <div class="dropout-modal">

        <div class="dropout-icon">
            <i class="fas fa-trash-alt"></i>
        </div>

        <h2>Dropout Student?</h2>

        <p>
            Are you sure you want to dropout
            <strong id="dropoutStudentName"></strong>?
            This action cannot be undone.
        </p>

        <div class="dropout-actions">

            <button type="button" class="dropout-cancel" id="cancelDropout">
                Cancel
            </button>

            <form method="POST" action="delete_students.php" id="dropoutForm">

                <input type="hidden" name="student_id" id="dropoutStudentId">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($student_csrf); ?>"
                >

                <button type="submit" class="dropout-confirm">
                    Yes, Dropout
                </button>

            </form>

        </div>

    </div>

</div>

<style>

/* =====================================
   STUDENT PAGE
===================================== */

.student-page {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.student-page .student-top-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin: 20px 0;
}

.student-page .student-top-actions .btn {
    width: auto;
    white-space: nowrap;
    cursor: pointer;
}

.student-page .student-filter-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.student-page .student-filter-actions .btn {
    width: auto;
    white-space: nowrap;
    min-width: 125px;
}

.student-page .student-table-container {
    width: 100%;
    overflow-x: auto;
    margin-top: 10px;
}

.student-page .dashboard-table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

.student-page .dashboard-table th,
.student-page .dashboard-table td {
    padding: 11px 12px;
    vertical-align: middle;
    white-space: nowrap;
}

.student-page .student-photo {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #d5a52b;
}

.student-page .student-default-icon {
    font-size: 35px;
    color: #9aa3b2;
}

.student-page .student-action-buttons {
    display: flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
}

.student-page .student-action-buttons .btn {
    width: auto;
    padding: 8px 12px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

/* ID CARD BUTTON */

.student-page .student-id-card-btn {
    background: #d5a52b;
    color: #17365d;
    border: 1px solid #d5a52b;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
    font-weight: 600;
}

.student-page .student-id-card-btn:hover {
    background: #bd8e17;
    border-color: #bd8e17;
    color: #fff;
    transform: translateY(-2px);
}

.student-page .empty-record {
    text-align: center;
    padding: 25px;
}

/* =====================================
   EXPORT EXCEL BUTTON
===================================== */

.student-export-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.student-export-btn {
    background: #16834a;
    color: #fff !important;
    border: 1px solid #16834a;
    border-radius: 7px;
    padding: 10px 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap;
    transition: .25s ease;
}

.student-export-btn:hover {
    background: #106639;
    border-color: #106639;
    color: #fff !important;
    transform: translateY(-2px);
}

/* =====================================
   NOTICES
===================================== */

.student-notice {
    padding: 14px 17px;
    border-radius: 8px;
    margin-bottom: 18px;
}

.student-notice.success {
    background: #e7f7ed;
    color: #167342;
    border-left: 4px solid #168b50;
}

.student-notice.error {
    background: #fdeaea;
    color: #a32121;
    border-left: 4px solid #d04444;
}

.student-card-link {
    display: inline-block;
    margin-top: 8px;
    font-weight: 700;
    color: inherit;
    text-decoration: underline;
}

/* =====================================
   ADD STUDENT MODAL
===================================== */

.student-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(3,10,25,.78);
    backdrop-filter: blur(7px);
    padding: 25px 15px;
    overflow-y: auto;
    align-items: flex-start;
    justify-content: center;
    box-sizing: border-box;
}

.student-modal-overlay.active {
    display: flex;
    animation: modalFade .25s ease;
}

.student-modal {
    position: relative;
    background: #fff;
    width: 100%;
    max-width: 850px;
    border-radius: 14px;
    padding: 25px;
    margin: auto;
    box-sizing: border-box;
    box-shadow: 0 15px 50px rgba(0,0,0,0.25);
    animation: modalUp .35s ease;
}

@keyframes modalFade {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes modalUp {
    from {
        transform: translateY(25px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.student-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;
    border-bottom: 1px solid #e6eaf0;
    padding-bottom: 18px;
    margin-bottom: 20px;
}

.student-modal-header h2 {
    margin: 0 0 7px;
    color: #17365d;
}

.student-modal-header p {
    margin: 0;
    color: #687386;
}

/* =====================================
   FORM LAYOUT
===================================== */

.student-add-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.student-add-form .form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.student-add-form .form-group > label {
    font-weight: 600;
    color: #263449;
}

.student-add-form input,
.student-add-form select,
.student-add-form textarea {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d5dbe5;
    border-radius: 7px;
    box-sizing: border-box;
    font: inherit;
}

.student-add-form textarea {
    min-height: 90px;
    resize: vertical;
}

.student-add-form .full-width,
.student-section-title {
    grid-column: 1 / -1;
}

.student-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #17365d;
    padding-bottom: 10px;
    border-bottom: 1px solid #e6eaf0;
    margin: 10px 0 0;
}

/* =====================================
   GENDER CARDS
===================================== */

.gender-options {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.gender-option {
    cursor: pointer;
    flex: 1;
    min-width: 100px;
}

.gender-option input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
}

.gender-option span {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 12px;
    border: 1px solid #d5dbe5;
    border-radius: 8px;
    color: #263449;
    background: #fff;
    transition: 0.2s;
    font-weight: 600;
}

.gender-option input:checked + span {
    background: #17365d;
    color: #fff;
    border-color: #d5a52b;
    box-shadow: 0 0 0 2px rgba(213,165,43,0.2);
}

.gender-option input:focus-visible + span {
    outline: 2px solid #d5a52b;
    outline-offset: 3px;
}

/* =====================================
   SECTION A/B RADIO CARDS
===================================== */

.section-options {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.section-option {
    display: none !important;
    cursor: pointer;
    flex: 1;
    min-width: 110px;
}

.section-option.show {
    display: block !important;
    animation: sectionCardIn .25s ease;
}

.section-option input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
}

.section-option span {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 12px;
    border: 1px solid #d5dbe5;
    border-radius: 8px;
    color: #263449;
    background: #fff;
    transition: .2s ease;
    font-weight: 600;
    cursor: pointer;
}

.section-option input:checked + span {
    background: #17365d;
    color: #fff;
    border-color: #d5a52b;
    box-shadow: 0 0 0 2px rgba(213,165,43,0.2);
}

.section-option input:focus-visible + span {
    outline: 2px solid #d5a52b;
    outline-offset: 3px;
}

.section-hint {
    color: #687386;
    font-size: 12px;
    margin-top: 3px;
}

@keyframes sectionCardIn {
    from {
        opacity: 0;
        transform: translateY(7px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* =====================================
   FORM BUTTONS
===================================== */

.student-add-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 25px;
    padding-top: 18px;
    border-top: 1px solid #e6eaf0;
}

.student-add-actions .btn {
    width: auto;
    cursor: pointer;
}

/* =====================================
   DROPOUT BUTTON
===================================== */

.student-page .student-dropout-btn {
    background: #dc2626;
    color: #fff;
    border: 1px solid #dc2626;
    border-radius: 6px;
    cursor: pointer;
    transition: .2s ease;
}

.student-page .student-dropout-btn:hover {
    background: #b91c1c;
    border-color: #b91c1c;
    transform: translateY(-2px);
}

/* =====================================
   DROPOUT CONFIRMATION POPUP
===================================== */

.dropout-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1000000;
    background: rgba(15, 23, 42, .78);
    backdrop-filter: blur(3px);
    align-items: center;
    justify-content: center;
    padding: 15px;
    box-sizing: border-box;
}

.dropout-overlay.active {
    display: flex;
    animation: dropoutFade .2s ease;
}

.dropout-modal {
    width: 100%;
    max-width: 378px;
    background: #fff;
    border-radius: 15px;
    padding: 27px 25px;
    text-align: center;
    box-sizing: border-box;
    box-shadow: 0 15px 50px rgba(0,0,0,.25);
    animation: dropoutPop .3s cubic-bezier(.2,.8,.2,1);
}

.dropout-icon {
    width: 58px;
    height: 58px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: #fff0f0;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
}

.dropout-modal h2 {
    font-size: 18px;
    font-weight: 700;
    color: #17365d;
    margin: 0 0 9px;
}

.dropout-modal p {
    color: #687386;
    font-size: 14px;
    line-height: 1.65;
    margin: 0 auto 20px;
    overflow-wrap: anywhere;
}

.dropout-modal p strong {
    color: #263449;
    font-weight: 700;
}

.dropout-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
}

.dropout-actions form {
    margin: 0;
}

.dropout-actions button {
    border: none;
    padding: 9px 17px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s ease;
    font-family: inherit;
}

.dropout-cancel {
    background: #edf0f4;
    color: #344256;
}

.dropout-cancel:hover {
    background: #dfe4eb;
}

.dropout-confirm {
    background: #cf2929;
    color: #fff;
}

.dropout-confirm:hover {
    background: #b51e1e;
    transform: translateY(-1px);
}

.dropout-actions button:focus-visible {
    outline: 2px solid #17365d;
    outline-offset: 3px;
}

@keyframes dropoutFade {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes dropoutPop {
    from {
        opacity: 0;
        transform: translateY(15px) scale(.94);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* =====================================
   RESPONSIVE
===================================== */

@media(max-width: 650px) {

    .student-add-form {
        grid-template-columns: 1fr;
    }

    .student-add-form .full-width,
    .student-section-title {
        grid-column: auto;
    }

    .student-modal {
        padding: 18px;
    }

    .student-modal-overlay {
        padding: 10px;
    }

    .student-add-actions {
        flex-direction: column;
    }

    .student-add-actions .btn {
        width: 100%;
    }

}

@media(max-width: 600px) {

    .student-page .student-top-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .student-page .student-top-actions .btn {
        width: 100%;
        box-sizing: border-box;
    }

    .student-page .student-filter-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .student-page .student-filter-actions .btn {
        width: 100%;
        box-sizing: border-box;
    }

    .student-export-actions {
        width: 100%;
        justify-content: space-between;
    }

    .student-export-btn {
        flex: 1;
    }

    .dropout-modal {
        max-width: 360px;
        padding: 24px 18px;
    }

}

</style>

<script>

document.addEventListener('DOMContentLoaded', function () {

    // =====================================
    // ADD STUDENT MODAL
    // =====================================

    const modal = document.getElementById('studentModal');

    const openButton = document.getElementById('openStudentModal');
    const backButton = document.getElementById('goBackModal');

    const classSelect = document.getElementById('class_id');
    const sectionOptions = document.getElementById('sectionOptions');
    const sectionHint = document.getElementById('sectionHint');

    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    openButton.addEventListener('click', openModal);
    backButton.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // =====================================
    // DROPOUT CONFIRMATION MODAL
    // =====================================

    const dropoutModal = document.getElementById('dropoutModal');
    const dropoutName = document.getElementById('dropoutStudentName');
    const dropoutId = document.getElementById('dropoutStudentId');
    const cancelDropout = document.getElementById('cancelDropout');

    const dropoutButtons = document.querySelectorAll('.student-dropout-btn');

    function openDropoutModal(id, name) {

        dropoutId.value = id;
        dropoutName.textContent = name;

        dropoutModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDropoutModal() {

        dropoutModal.classList.remove('active');
        document.body.style.overflow = '';

        dropoutId.value = '';
        dropoutName.textContent = '';
    }

    dropoutButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const studentId = this.dataset.id;
            const studentName = this.dataset.name;

            openDropoutModal(studentId, studentName);

        });

    });

    cancelDropout.addEventListener('click', closeDropoutModal);

    dropoutModal.addEventListener('click', function (event) {

        if (event.target === dropoutModal) {
            closeDropoutModal();
        }

    });

    // =====================================
    // ESCAPE KEY
    // =====================================

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {

            if (dropoutModal.classList.contains('active')) {
                closeDropoutModal();
            } else if (modal.classList.contains('active')) {
                closeModal();
            }

        }

    });

    // =====================================
    // REOPEN ADD MODAL AFTER VALIDATION ERROR
    // =====================================

    <?php if ($show_modal): ?>
        openModal();
    <?php endif; ?>

    // =====================================
    // CLASS AND SECTION CARD FILTER
    // =====================================

    const sectionCards = Array.from(
        sectionOptions.querySelectorAll('.section-option')
    );

    function filterSections() {

        const selectedClass = classSelect.value;

        let availableCount = 0;

        sectionCards.forEach(function (card) {

            const radio = card.querySelector('input[type="radio"]');
            const cardClass = radio.dataset.class;

            if (selectedClass !== '' && cardClass === selectedClass) {

                card.classList.add('show');
                radio.disabled = false;

                availableCount++;

            } else {

                card.classList.remove('show');
                radio.disabled = true;
                radio.checked = false;

            }

        });

        if (selectedClass === '') {

            sectionHint.textContent = 'First select a class to see available sections.';

        } else if (availableCount === 0) {

            sectionHint.textContent = 'No sections available for this class.';

        } else {

            sectionHint.textContent = 'Select one section only.';

        }

    }

    classSelect.addEventListener('change', function () {

        sectionCards.forEach(function (card) {

            const radio = card.querySelector('input[type="radio"]');
            radio.checked = false;

        });

        filterSections();

    });

    filterSections();

    // =====================================
    // AUTO HIDE NOTICE
    // =====================================

    const notice = document.getElementById('studentNotice');

    if (notice) {

        setTimeout(function () {

            notice.style.transition = 'opacity .4s ease, transform .4s ease';
            notice.style.opacity = '0';
            notice.style.transform = 'translateY(-8px)';

            setTimeout(function () {
                notice.style.display = 'none';
            }, 450);

        }, 4000);

    }

});

</script>

<?php

$content = ob_get_clean();

$stmt->close();

include __DIR__ . '/../admin/layouts/admin_layout.php';

?>
