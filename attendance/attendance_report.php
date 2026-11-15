
<?php
ob_start();

// ==========================================
// DATABASE CONNECTION
// ==========================================

require_once __DIR__ . '/../config/database.php';

$db = $conn ?? $mysqli ?? $con ?? $connection ?? null;

if (!($db instanceof mysqli)) {
    die("Database connection not found. Check config/database.php");
}

// ==========================================
// PAGE SETTINGS
// ==========================================

$page_title = "Attendance Report";
$current_page = "attendance";

$from_date = $_GET['from_date'] ?? date('Y-m-d');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$class_id = $_GET['class_id'] ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
    $from_date = date('Y-m-d');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    $to_date = date('Y-m-d');
}

if ($from_date > $to_date) {
    die("Invalid date range.");
}

// ==========================================
// DETECT CLASS NAME COLUMN
// ==========================================

$classColumns = [];

$columnResult = $db->query("SHOW COLUMNS FROM classes");

if (!$columnResult) {
    die("Unable to read classes table: " . $db->error);
}

while ($column = $columnResult->fetch_assoc()) {
    $classColumns[] = $column['Field'];
}

$classNameColumn = null;

foreach (['class_name', 'name', 'class', 'grade_name'] as $possible) {
    if (in_array($possible, $classColumns, true)) {
        $classNameColumn = $possible;
        break;
    }
}

if (!$classNameColumn) {
    die("Class name column not found.");
}

// ==========================================
// GET CLASSES
// ==========================================

$classes = $db->query("
    SELECT id, `$classNameColumn` AS class_name
    FROM classes
    ORDER BY id ASC
");

if (!$classes) {
    die("Unable to load classes: " . $db->error);
}

// ==========================================
// ATTENDANCE REPORT QUERY
// ==========================================

$sql = "
    SELECT
        s.full_name,
        s.student_id AS student_code,
        e.roll_number,
        c.`$classNameColumn` AS class_name,
        a.attendance_date,
        a.attendance_day,
        a.scan_time,
        a.status,
        a.scan_method

    FROM student_attendance a

    INNER JOIN students s
        ON a.student_id = s.id

    INNER JOIN enrollments e
        ON a.enrollment_id = e.id

    INNER JOIN classes c
        ON e.class_id = c.id

    WHERE a.attendance_date BETWEEN ? AND ?
";

$params = [$from_date, $to_date];
$types = "ss";

if ($class_id !== '' && ctype_digit($class_id)) {
    $sql .= " AND e.class_id = ?";
    $params[] = (int)$class_id;
    $types .= "i";
}

$sql .= "
    ORDER BY
        c.id ASC,
        s.full_name ASC,
        a.attendance_date DESC
";

$stmt = $db->prepare($sql);

if (!$stmt) {
    die("Report query error: " . $db->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();

// ==========================================
// ATTENDANCE SUMMARY
// ==========================================

$records = [];

$present = 0;
$late = 0;
$absent = 0;

while ($row = $result->fetch_assoc()) {

    $records[] = $row;

    if ($row['status'] === 'present') {
        $present++;
    } elseif ($row['status'] === 'late') {
        $late++;
    } elseif ($row['status'] === 'absent') {
        $absent++;
    }
}

$total = $present + $late + $absent;

$percentage = $total > 0
    ? round((($present + $late) / $total) * 100, 2)
    : 0;

$stmt->close();

// ==========================================
// EXCEL EXPORT
// ==========================================

if (isset($_GET['export'])) {

    $tempFile = null;
    $spreadsheet = null;

    try {

        $autoload = __DIR__ . '/../vendor/autoload.php';

        if (!file_exists($autoload)) {
            throw new RuntimeException("Composer autoload file not found.");
        }

        require_once $autoload;

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new RuntimeException("PhpSpreadsheet is not installed.");
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $spreadsheet->getProperties()
            ->setCreator('DAAES School')
            ->setTitle('DAAES Attendance Report')
            ->setSubject('Student Attendance Records');

        // Group records by class
        $groups = [];

        foreach ($records as $record) {

            $className = trim($record['class_name'] ?? 'Unassigned');

            if ($className === '') {
                $className = 'Unassigned';
            }

            $groups[$className][] = $record;
        }

        $headers = [
            'Student Name',
            'Student ID',
            'Roll Number',
            'Class',
            'Attendance Date',
            'Day',
            'Scan Time',
            'Status',
            'Scan Method'
        ];

        $sheetNumber = 0;
        $usedTitles = [];

        // Create class-wise sheets
        foreach ($groups as $className => $classRecords) {

            $sheet = $sheetNumber === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $sheetTitle = preg_replace(
                '/[\\\\\/\?\*\[\]\:]/',
                '',
                $className
            );

            $sheetTitle = trim($sheetTitle);

            if ($sheetTitle === '') {
                $sheetTitle = 'Class';
            }

            $sheetTitle = mb_substr($sheetTitle, 0, 31);

            $baseTitle = $sheetTitle;
            $counter = 1;

            while (isset($usedTitles[strtolower($sheetTitle)])) {

                $suffix = '_' . $counter;

                $sheetTitle = mb_substr(
                    $baseTitle,
                    0,
                    31 - strlen($suffix)
                ) . $suffix;

                $counter++;
            }

            $usedTitles[strtolower($sheetTitle)] = true;

            $sheet->setTitle($sheetTitle);

            // Headings
            $sheet->fromArray($headers, null, 'A1');

            $sheet->getStyle('A1:I1')->applyFromArray([

                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11
                ],

                'fill' => [
                    'fillType' =>
                        \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,

                    'startColor' => [
                        'rgb' => '102B50'
                    ]
                ],

                'alignment' => [
                    'horizontal' =>
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,

                    'vertical' =>
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ]);

            $sheet->getRowDimension(1)->setRowHeight(28);

            // Attendance rows
            $excelRows = [];

            foreach ($classRecords as $record) {

                $excelRows[] = [
                    $record['full_name'] ?? '',
                    $record['student_code'] ?? '',
                    $record['roll_number'] ?? '',
                    $record['class_name'] ?? '',
                    $record['attendance_date'] ?? '',
                    $record['attendance_day'] ?? '',
                    $record['scan_time'] ?? '',
                    ucfirst($record['status'] ?? ''),
                    $record['scan_method'] ?? ''
                ];
            }

            if (!empty($excelRows)) {
                $sheet->fromArray($excelRows, null, 'A2');
            }

            $lastRow = count($excelRows) + 1;

            // Gold heading border
            $sheet->getStyle('A1:I1')
                ->getBorders()
                ->getBottom()
                ->setBorderStyle(
                    \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM
                )
                ->getColor()
                ->setRGB('B99545');

            // Auto column width
            foreach (range('A', 'I') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Alignment
            if ($lastRow >= 2) {

                $sheet->getStyle('A2:I' . $lastRow)
                    ->getAlignment()
                    ->setVertical(
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    );
            }

            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:I' . max(1, $lastRow));

            $sheetNumber++;
        }

        // No records
        if ($sheetNumber === 0) {

            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setTitle('Attendance');

            $sheet->setCellValue(
                'A1',
                'No attendance records found for selected filters.'
            );

            $sheet->getStyle('A1')->getFont()->setBold(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        // Temporary Excel file
        $tempFile = tempnam(sys_get_temp_dir(), 'DAAES_');

        if ($tempFile === false) {
            throw new RuntimeException("Unable to create temporary file.");
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $writer->setPreCalculateFormulas(false);
        $writer->save($tempFile);

        if (!file_exists($tempFile) || filesize($tempFile) === 0) {
            throw new RuntimeException("Excel file generation failed.");
        }

        // Validate XLSX
        $zip = new \ZipArchive();

        $zipResult = $zip->open($tempFile);

        if ($zipResult !== true) {
            throw new RuntimeException("Generated Excel file is invalid.");
        }

        $validXlsx =
            $zip->locateName('[Content_Types].xml') !== false &&
            $zip->locateName('xl/workbook.xml') !== false;

        $zip->close();

        if (!$validXlsx) {
            throw new RuntimeException("Invalid Excel workbook structure.");
        }

        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet, $writer);

        // Clear output buffers before download
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'DAAES_Attendance_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        header(
            'Content-Disposition: attachment; filename="' .
            $filename . '"'
        );

        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        readfile($tempFile);

        unlink($tempFile);
        exit;

    } catch (\Throwable $e) {

        if ($tempFile && file_exists($tempFile)) {
            unlink($tempFile);
        }

        if ($spreadsheet !== null) {
            $spreadsheet->disconnectWorksheets();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        error_log('DAAES Excel Export Error: ' . $e->getMessage());

        http_response_code(500);

        die('Excel export failed. Check your PHP error log for details.');
    }
}

// ==========================================
// REPORT CONTENT
// ==========================================
?>

<style>

.report-container {
    padding: 25px;
}

.report-heading {
    margin-bottom: 25px;
}

.report-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
    margin-bottom: 25px;
    padding: 20px;
    border-radius: 12px;
}

.report-filters label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.report-filters input,
.report-filters select {
    padding: 11px;
    border-radius: 7px;
    border: 1px solid #ccc;
}

.report-btn {
    padding: 11px 18px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
}

.filter-btn {
    background: #102b50;
    color: white;
}

.export-btn {
    background: #b99545;
    color: white;
}

.report-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.report-card {
    padding: 20px;
    border-radius: 12px;
    background: white;
    border: 1px solid #e5e5e5;
}

.report-card h3 {
    font-size: 14px;
    margin-bottom: 12px;
}

.report-card p {
    font-size: 25px;
    font-weight: bold;
}

.report-table-wrapper {
    overflow-x: auto;
    background: white;
    border-radius: 12px;
    padding: 15px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.report-table th,
.report-table td {
    padding: 13px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

.report-table th {
    background: #102b50;
    color: white;
}

.status-present {
    color: #16864b;
    font-weight: bold;
}

.status-late {
    color: #c18419;
    font-weight: bold;
}

.status-absent {
    color: #d33b3b;
    font-weight: bold;
}

.empty-report {
    padding: 30px;
    text-align: center;
}

@media(max-width: 600px) {

    .report-container {
        padding: 12px;
    }

    .report-filters {
        flex-direction: column;
        align-items: stretch;
    }

}

</style>

<div class="report-container">

    <!-- PAGE HEADING -->

    <div class="report-heading">

        <h1>Attendance Report</h1>

        <p>DAAES School - Student Attendance Records</p>

    </div>

    <!-- FILTER SECTION -->

    <form method="GET" class="report-filters">

        <div>
            <label>From Date</label>

            <input
                type="date"
                name="from_date"
                value="<?= htmlspecialchars($from_date) ?>"
            >
        </div>

        <div>
            <label>To Date</label>

            <input
                type="date"
                name="to_date"
                value="<?= htmlspecialchars($to_date) ?>"
            >
        </div>

        <div>
            <label>Select Class</label>

            <select name="class_id">

                <option value="">All Classes</option>

                <?php while ($class = $classes->fetch_assoc()): ?>

                    <option
                        value="<?= (int)$class['id'] ?>"
                        <?= $class_id == $class['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($class['class_name']) ?>
                    </option>

                <?php endwhile; ?>

            </select>
        </div>

        <button type="submit" class="report-btn filter-btn">
            Apply Filter
        </button>

        <a href="attendance_report.php" class="report-btn filter-btn">
            Reset
        </a>

        <button
            type="submit"
            name="export"
            value="1"
            class="report-btn export-btn"
        >
            <i class="fas fa-file-excel"></i>
            Export Excel
        </button>

    </form>

    <!-- SUMMARY CARDS -->

    <div class="report-cards">

        <div class="report-card">
            <h3>Total Records</h3>
            <p><?= $total ?></p>
        </div>

        <div class="report-card">
            <h3>Present</h3>
            <p><?= $present ?></p>
        </div>

        <div class="report-card">
            <h3>Late</h3>
            <p><?= $late ?></p>
        </div>

        <div class="report-card">
            <h3>Absent</h3>
            <p><?= $absent ?></p>
        </div>

        <div class="report-card">
            <h3>Attendance Percentage</h3>
            <p><?= $percentage ?>%</p>
        </div>

    </div>

    <!-- ATTENDANCE TABLE -->

    <div class="report-table-wrapper">

        <h2>Student Attendance Details</h2>

        <table class="report-table">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Roll Number</th>
                    <th>Class</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Scan Time</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($records) > 0): ?>

                    <?php foreach ($records as $index => $record): ?>

                        <tr>

                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= htmlspecialchars($record['full_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['student_code']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['roll_number'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['class_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['attendance_date']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['attendance_day']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['scan_time']) ?>
                            </td>

                            <td class="status-<?= htmlspecialchars($record['status']) ?>">
                                <?= ucfirst(htmlspecialchars($record['status'])) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="empty-report">
                            No attendance records found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php

// ==========================================
// LOAD ADMIN DASHBOARD LAYOUT
// ==========================================

$content = ob_get_clean();
$page_content = $content;

require_once __DIR__ . '/../admin/layouts/admin_layout.php';

?>
