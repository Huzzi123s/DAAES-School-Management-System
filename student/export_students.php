
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/database.php';
require_once '../vendor/autoload.php';

date_default_timezone_set('Asia/Karachi');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

// =====================================
// GET FILTERS
// =====================================

$search = trim($_GET['search'] ?? '');
$class_filter = (int)($_GET['class_id'] ?? 0);
$section_filter = strtoupper(trim($_GET['section'] ?? ''));

if (!in_array($section_filter, ['', 'A', 'B'], true)) {
    $section_filter = '';
}

// =====================================
// FETCH STUDENTS
// =====================================

$sql = "
    SELECT
        s.student_id,
        s.full_name,
        s.father_name,
        s.mother_name,
        s.gender,
        s.date_of_birth,
        s.admission_date,
        s.phone,
        s.address,
        s.status,
        s.created_at,

        e.class_id,
        e.section_id,
        e.academic_year,
        e.roll_number,

        c.class_name,
        sec.section_name

    FROM students s

    LEFT JOIN enrollments e ON e.id = (
        SELECT MAX(e2.id)
        FROM enrollments e2
        WHERE e2.student_id = s.id
    )

    LEFT JOIN classes c ON c.id = e.class_id
    LEFT JOIN sections sec ON sec.id = e.section_id

    WHERE 1=1
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

    $sql .= " AND e.class_id = ?";

    $params[] = $class_filter;
    $types .= 'i';
}

if ($section_filter !== '') {

    $sql .= " AND UPPER(TRIM(sec.section_name)) = ?";

    $params[] = $section_filter;
    $types .= 's';
}

$sql .= " ORDER BY s.id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Export Query Error: ' . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$total_students = $result->num_rows;

// =====================================
// REPORT DETAILS
// =====================================

$filter_details = [];

if ($search !== '') {
    $filter_details[] = 'Search: ' . $search;
}

if ($class_filter > 0) {

    $class_stmt = $conn->prepare("
        SELECT class_name
        FROM classes
        WHERE id = ?
        LIMIT 1
    ");

    $class_stmt->bind_param('i', $class_filter);
    $class_stmt->execute();

    $class_data = $class_stmt->get_result()->fetch_assoc();

    $filter_details[] = 'Class: ' .
        ($class_data['class_name'] ?? 'Selected Class');

    $class_stmt->close();
}

if ($section_filter !== '') {
    $filter_details[] = 'Section: ' . $section_filter;
}

$report_type = empty($filter_details)
    ? 'All Students'
    : implode(' | ', $filter_details);

$generated_date = date('d M Y, h:i A');

// =====================================
// CREATE EXCEL WORKBOOK
// =====================================

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Student Records');

// =====================================
// SCHOOL COLORS
// =====================================

$navy = '17365D';
$darkNavy = '102943';
$gold = 'D5A52B';
$lightGold = 'FFF4D6';
$white = 'FFFFFF';
$lightBlue = 'EAF0F7';
$lightGray = 'F1F5F9';
$borderColor = 'DCE3EB';
$textColor = '263445';

// =====================================
// WORKBOOK INFORMATION
// =====================================

$spreadsheet->getProperties()
    ->setCreator('DAAES School')
    ->setLastModifiedBy('DAAES School')
    ->setTitle('DAAES School Student Records')
    ->setSubject('Official Student Database')
    ->setDescription('DAAES School Official Student Records Report');

// =====================================
// MAIN TITLE
// =====================================

$sheet->mergeCells('A1:P1');

$sheet->setCellValue('A1', 'DAAES SCHOOL');

$sheet->getStyle('A1:P1')->applyFromArray([

    'font' => [
        'name' => 'Calibri',
        'bold' => true,
        'size' => 24,
        'color' => ['rgb' => $white]
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $navy]
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]

]);

$sheet->getRowDimension(1)->setRowHeight(48);

// =====================================
// SUBTITLE
// =====================================

$sheet->mergeCells('A2:P2');

$sheet->setCellValue('A2', 'OFFICIAL STUDENT DATABASE');

$sheet->getStyle('A2:P2')->applyFromArray([

    'font' => [
        'name' => 'Calibri',
        'bold' => true,
        'size' => 15,
        'color' => ['rgb' => $navy]
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $lightGold]
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]

]);

$sheet->getRowDimension(2)->setRowHeight(32);

// =====================================
// REPORT INFORMATION
// =====================================

$sheet->mergeCells('A3:P3');

$sheet->setCellValue(
    'A3',
    'STUDENT RECORDS REPORT  |  TOTAL STUDENTS: ' .
    $total_students .
    '  |  ' . $report_type .
    '  |  GENERATED: ' . $generated_date
);

$sheet->getStyle('A3:P3')->applyFromArray([

    'font' => [
        'name' => 'Calibri',
        'bold' => true,
        'size' => 10,
        'color' => ['rgb' => $textColor]
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $lightGray]
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ]

]);

$sheet->getRowDimension(3)->setRowHeight(34);

// =====================================
// GOLD SEPARATOR
// =====================================

$sheet->mergeCells('A4:P4');

$sheet->getStyle('A4:P4')->applyFromArray([

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $gold]
    ]

]);

$sheet->getRowDimension(4)->setRowHeight(5);

// =====================================
// COLUMN HEADINGS
// =====================================

$columns = [
    '#',
    'Student ID',
    'Full Name',
    'Father Name',
    'Mother Name',
    'Gender',
    'Date of Birth',
    'Admission Date',
    'Class',
    'Section',
    'Roll Number',
    'Academic Year',
    'Parent Phone',
    'Address',
    'Status',
    'Registered On'
];

$sheet->fromArray($columns, null, 'A5');

$sheet->getStyle('A5:P5')->applyFromArray([

    'font' => [
        'name' => 'Calibri',
        'bold' => true,
        'size' => 11,
        'color' => ['rgb' => $white]
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $navy]
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],

    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => $gold]
        ]
    ]

]);

$sheet->getRowDimension(5)->setRowHeight(36);

// =====================================
// STUDENT DATA
// =====================================

$counter = 1;
$rowNumber = 6;

while ($student = $result->fetch_assoc()) {

    $row = [

        $counter,

        $student['student_id'],

        $student['full_name'],

        $student['father_name'],

        $student['mother_name'] ?: '-',

        $student['gender'] ?: '-',

        $student['date_of_birth'] ?: '-',

        $student['admission_date'] ?: '-',

        $student['class_name'] ?: 'Not Assigned',

        $student['section_name'] ?: 'Not Assigned',

        $student['roll_number'] ?: '-',

        $student['academic_year'] ?: '-',

        $student['phone'] ?: '-',

        $student['address'] ?: '-',

        ucfirst($student['status'] ?: 'active'),

        $student['created_at'] ?: '-'

    ];

    $sheet->fromArray($row, null, 'A' . $rowNumber);

    // Alternate row colors

    $rowColor = ($counter % 2 === 0)
        ? $lightBlue
        : $white;

    $sheet->getStyle("A{$rowNumber}:P{$rowNumber}")
        ->applyFromArray([

            'font' => [
                'name' => 'Calibri',
                'size' => 10,
                'color' => ['rgb' => $textColor]
            ],

            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $rowColor]
            ],

            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],

            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_HAIR,
                    'color' => ['rgb' => $borderColor]
                ]
            ]

        ]);

    // Center selected columns

    $sheet->getStyle("A{$rowNumber}:B{$rowNumber}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->getStyle("F{$rowNumber}:L{$rowNumber}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->getStyle("O{$rowNumber}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Keep IDs and phone numbers as text

    $sheet->setCellValueExplicit(
        'B' . $rowNumber,
        (string)$student['student_id'],
        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
    );

    $sheet->setCellValueExplicit(
        'M' . $rowNumber,
        (string)($student['phone'] ?? '-'),
        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
    );

    // Status styling

    $status = strtolower($student['status'] ?: 'active');

    if ($status === 'active') {

        $sheet->getStyle("O{$rowNumber}")->applyFromArray([

            'font' => [
                'bold' => true,
                'color' => ['rgb' => '16803C']
            ]

        ]);

    } else {

        $sheet->getStyle("O{$rowNumber}")->applyFromArray([

            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'C0392B']
            ]

        ]);

    }

    $sheet->getRowDimension($rowNumber)->setRowHeight(29);

    $counter++;
    $rowNumber++;
}

// =====================================
// EMPTY RECORD MESSAGE
// =====================================

if ($total_students === 0) {

    $sheet->mergeCells('A6:P6');

    $sheet->setCellValue(
        'A6',
        'NO STUDENT RECORDS FOUND FOR THE SELECTED FILTERS'
    );

    $sheet->getStyle('A6:P6')->applyFromArray([

        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['rgb' => '64748B']
        ],

        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $lightGray]
        ],

        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]

    ]);

    $sheet->getRowDimension(6)->setRowHeight(42);

}

// =====================================
// COLUMN WIDTHS
// =====================================

$widths = [

    'A' => 7,
    'B' => 19,
    'C' => 25,
    'D' => 25,
    'E' => 25,
    'F' => 13,
    'G' => 17,
    'H' => 18,
    'I' => 17,
    'J' => 12,
    'K' => 15,
    'L' => 17,
    'M' => 20,
    'N' => 38,
    'O' => 15,
    'P' => 23

];

foreach ($widths as $column => $width) {

    $sheet->getColumnDimension($column)->setWidth($width);

}

// =====================================
// FILTER
// =====================================

$lastRow = max(5, $rowNumber - 1);

if ($total_students > 0) {

    $sheet->setAutoFilter("A5:P{$lastRow}");

}

// =====================================
// FREEZE HEADER
// =====================================

$sheet->freezePane('A6');

// =====================================
// PRINT SETTINGS
// =====================================

$sheet->getPageSetup()
    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(PageSetup::PAPERSIZE_A4)
    ->setFitToWidth(1)
    ->setFitToHeight(0);

$sheet->getPageSetup()->setFitToPage(true);

$sheet->getPageMargins()
    ->setTop(0.5)
    ->setBottom(0.5)
    ->setLeft(0.25)
    ->setRight(0.25);

$sheet->getPageSetup()->setPrintArea("A1:P{$lastRow}");

// =====================================
// ACTIVE CELL
// =====================================

$sheet->setSelectedCell('A1');

// =====================================
// DOWNLOAD XLSX
// =====================================

$filename = 'DAAES_Student_Report_' . date('Ymd_His') . '.xlsx';

// Close database resources

$stmt->close();
$conn->close();

// Clean previous output

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

$spreadsheet->disconnectWorksheets();

exit;

?>
