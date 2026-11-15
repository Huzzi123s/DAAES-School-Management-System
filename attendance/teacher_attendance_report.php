
<?php
ob_start();

date_default_timezone_set('Asia/Karachi');

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

$page_title = "Digital Teacher Attendance";
$current_page = "teacher_attendance_report";

$from_date = $_GET['from_date'] ?? date('Y-m-d');
$to_date   = $_GET['to_date'] ?? date('Y-m-d');

if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)
) {
    die("Invalid date format.");
}

if (
    !checkdate((int)substr($from_date, 5, 2), (int)substr($from_date, 8, 2), (int)substr($from_date, 0, 4)) ||
    !checkdate((int)substr($to_date, 5, 2), (int)substr($to_date, 8, 2), (int)substr($to_date, 0, 4))
) {
    die("Invalid date.");
}

if ($from_date > $to_date) {
    die("From Date cannot be greater than To Date.");
}

// ==========================================
// ATTENDANCE REPORT QUERY
// ==========================================

$sql = "
    SELECT
        t.full_name,
        t.employee_id,
        a.attendance_date,
        a.attendance_day,
        a.check_in,
        a.check_out,
        a.status,
        a.remarks
    FROM teacher_attendance a
    INNER JOIN teachers t
        ON a.teacher_id = t.id
    WHERE a.attendance_date BETWEEN ? AND ?
    ORDER BY a.attendance_date DESC, t.full_name ASC
";

$stmt = $db->prepare($sql);

if (!$stmt) {
    error_log("Teacher report query error: " . $db->error);
    die("Unable to load teacher attendance report. Please check database columns.");
}

$stmt->bind_param("ss", $from_date, $to_date);
$stmt->execute();

$result = $stmt->get_result();

// ==========================================
// RECORDS AND SUMMARY
// ==========================================

$records = [];

$present = 0;
$late = 0;
$absent = 0;
$leave = 0;

while ($row = $result->fetch_assoc()) {

    $records[] = $row;

    $status = strtolower(trim($row['status'] ?? ''));

    if ($status === 'present') {
        $present++;
    } elseif ($status === 'late') {
        $late++;
    } elseif ($status === 'absent') {
        $absent++;
    } elseif ($status === 'leave') {
        $leave++;
    }
}

$total = count($records);

$workingTotal = $present + $late + $absent;

$percentage = $workingTotal > 0
    ? round((($present + $late) / $workingTotal) * 100, 2)
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
            throw new RuntimeException("Composer autoload not found.");
        }

        require_once $autoload;

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new RuntimeException("PhpSpreadsheet is not installed.");
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $spreadsheet->getProperties()
            ->setCreator('DAAES School')
            ->setTitle('Teacher Attendance Report')
            ->setSubject('Teacher Attendance Records');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Teacher Attendance');

        $headers = [
            'Teacher Name',
            'Employee ID',
            'Attendance Date',
            'Day',
            'Check In',
            'Check Out',
            'Status',
            'Remarks'
        ];

        $sheet->fromArray($headers, null, 'A1');

        $sheet->getStyle('A1:H1')->applyFromArray([

            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],

            'fill' => [
                'fillType' =>
                    \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '102B50']
            ],

            'alignment' => [
                'horizontal' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        $excelRows = [];

        foreach ($records as $record) {

            $excelRows[] = [
                $record['full_name'] ?? '',
                $record['employee_id'] ?? '',
                $record['attendance_date'] ?? '',
                $record['attendance_day'] ?? '',
                $record['check_in'] ?? '',
                $record['check_out'] ?? '',
                ucfirst($record['status'] ?? ''),
                $record['remarks'] ?? ''
            ];
        }

        if (!empty($excelRows)) {
            $sheet->fromArray($excelRows, null, 'A2');
        }

        $lastRow = count($excelRows) + 1;

        $sheet->getStyle('A1:H1')
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM
            )
            ->getColor()
            ->setRGB('B99545');

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if ($lastRow >= 2) {
            $sheet->getStyle('A2:H' . $lastRow)
                ->getAlignment()
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H' . max(1, $lastRow));

        $tempFile = tempnam(sys_get_temp_dir(), 'DAAES_TEACHER_');

        if ($tempFile === false) {
            throw new RuntimeException("Unable to create temporary file.");
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $writer->setPreCalculateFormulas(false);
        $writer->save($tempFile);

        if (!file_exists($tempFile) || filesize($tempFile) === 0) {
            throw new RuntimeException("Excel generation failed.");
        }

        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet, $writer);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'DAAES_Teacher_Attendance_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');

        readfile($tempFile);

        unlink($tempFile);
        exit;

    } catch (Throwable $e) {

        if ($tempFile && file_exists($tempFile)) {
            unlink($tempFile);
        }

        if ($spreadsheet !== null) {
            $spreadsheet->disconnectWorksheets();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        error_log('Teacher Excel Export Error: ' . $e->getMessage());

        http_response_code(500);
        die('Teacher Excel export failed. Please check PHP error log.');
    }
}

// ==========================================
// REPORT CONTENT
// ==========================================
?>

<style>

.teacher-report-container {
    padding: 25px;
    color: #102B50;
}

.teacher-report-heading {
    margin-bottom: 25px;
}

.teacher-report-heading h1 {
    font-size: 27px;
    color: #102B50;
    margin-bottom: 8px;
}

.teacher-report-heading p {
    color: #777;
    margin: 0;
}

.teacher-report-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
    margin-bottom: 25px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
}

.teacher-report-filters label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #102B50;
}

.teacher-report-filters input {
    padding: 11px;
    border-radius: 7px;
    border: 1px solid #ccc;
    min-width: 160px;
}

.teacher-report-btn {
    padding: 11px 18px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
}

.teacher-filter-btn {
    background: #102B50;
    color: white;
}

.teacher-export-btn {
    background: #B99545;
    color: white;
}

.teacher-report-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.teacher-report-card {
    padding: 20px;
    border-radius: 12px;
    background: white;
    border: 1px solid #e5e5e5;
    border-left: 4px solid #B99545;
    box-shadow: 0 3px 12px rgba(0,0,0,0.04);
}

.teacher-report-card h3 {
    font-size: 14px;
    margin-bottom: 12px;
    color: #666;
}

.teacher-report-card p {
    font-size: 25px;
    font-weight: bold;
    color: #102B50;
    margin: 0;
}

.teacher-report-table-wrapper {
    overflow-x: auto;
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e5e5e5;
}

.teacher-report-table-wrapper h2 {
    color: #102B50;
    margin-bottom: 18px;
    font-size: 20px;
}

.teacher-report-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

.teacher-report-table th,
.teacher-report-table td {
    padding: 13px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

.teacher-report-table th {
    background: #102B50;
    color: white;
}

.teacher-status-present {
    color: #16864b;
    font-weight: bold;
}

.teacher-status-late {
    color: #c18419;
    font-weight: bold;
}

.teacher-status-absent {
    color: #d33b3b;
    font-weight: bold;
}

.teacher-status-leave {
    color: #3976b9;
    font-weight: bold;
}

.teacher-empty-report {
    padding: 30px;
    text-align: center;
    color: #777;
}

@media(max-width: 600px) {

    .teacher-report-container {
        padding: 12px;
    }

    .teacher-report-filters {
        flex-direction: column;
        align-items: stretch;
    }

    .teacher-report-filters > div,
    .teacher-report-filters input {
        width: 100%;
    }

    .teacher-report-btn {
        text-align: center;
    }
}

</style>

<div class="teacher-report-container">

    <div class="teacher-report-heading">
        <h1>
            <i class="fas fa-chalkboard-teacher"></i>
            Digital Teacher Attendance
        </h1>

        <p>DAAES School - Teacher Attendance Records</p>
    </div>

    <!-- DATE FILTER -->

    <form method="GET" action="teacher_attendance_report.php"
          class="teacher-report-filters">

        <div>
            <label>From Date</label>
            <input
                type="date"
                name="from_date"
                value="<?= htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label>To Date</label>
            <input
                type="date"
                name="to_date"
                value="<?= htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <button type="submit"
                class="teacher-report-btn teacher-filter-btn">
            <i class="fas fa-filter"></i>
            Apply Filter
        </button>

        <a href="teacher_attendance_report.php"
           class="teacher-report-btn teacher-filter-btn">
            <i class="fas fa-undo"></i>
            Reset
        </a>

        <button type="submit"
                name="export"
                value="1"
                class="teacher-report-btn teacher-export-btn">
            <i class="fas fa-file-excel"></i>
            Export Excel
        </button>

    </form>

    <!-- SUMMARY CARDS -->

    <div class="teacher-report-cards">

        <div class="teacher-report-card">
            <h3>Total Records</h3>
            <p><?= $total ?></p>
        </div>

        <div class="teacher-report-card">
            <h3>Present</h3>
            <p><?= $present ?></p>
        </div>

        <div class="teacher-report-card">
            <h3>Late</h3>
            <p><?= $late ?></p>
        </div>

        <div class="teacher-report-card">
            <h3>Absent</h3>
            <p><?= $absent ?></p>
        </div>

        <div class="teacher-report-card">
            <h3>Leave</h3>
            <p><?= $leave ?></p>
        </div>

        <div class="teacher-report-card">
            <h3>Attendance Percentage</h3>
            <p><?= $percentage ?>%</p>
        </div>

    </div>

    <!-- ATTENDANCE TABLE -->

    <div class="teacher-report-table-wrapper">

        <h2>
            <i class="fas fa-list-alt"></i>
            Teacher Attendance Details
        </h2>

        <table class="teacher-report-table">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Teacher Name</th>
                    <th>Employee ID</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($records)): ?>

                    <?php foreach ($records as $index => $record): ?>

                        <?php
                        $statusClass = 'teacher-status-' .
                            preg_replace(
                                '/[^a-z]/',
                                '',
                                strtolower($record['status'] ?? '')
                            );
                        ?>

                        <tr>

                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= htmlspecialchars($record['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['employee_id'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['attendance_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['attendance_day'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['check_in'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['check_out'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td class="<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(ucfirst($record['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($record['remarks'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="teacher-empty-report">
                            <i class="fas fa-info-circle"></i>
                            No teacher attendance records found for this date range.
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

require_once __DIR__ . '/../admin/layouts/admin_layout.php';

?>
