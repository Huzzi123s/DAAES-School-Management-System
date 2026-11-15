
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Karachi');

// =====================================
// DATABASE CONNECTION
// =====================================

require_once __DIR__ . '/../config/database.php';

// =====================================
// PHP SPREADSHEET AUTOLOAD
// =====================================

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die("Composer vendor/autoload.php not found. Please install PhpSpreadsheet first.");
}

require_once $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// =====================================
// HELPER
// =====================================

function excelSafe($value) {
    return trim((string)($value ?? ''));
}

// =====================================
// SEARCH
// =====================================

$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $stmt = $conn->prepare("
        SELECT *
        FROM teachers
        WHERE full_name LIKE ?
        OR employee_id LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
        ORDER BY id DESC
    ");

    if (!$stmt) {
        die("Database error: " . htmlspecialchars($conn->error));
    }

    $keyword = "%{$search}%";

    $stmt->bind_param(
        "ssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query("
        SELECT *
        FROM teachers
        ORDER BY id DESC
    ");

    if (!$result) {
        die("Database error: " . htmlspecialchars($conn->error));
    }
}

// =====================================
// CREATE EXCEL WORKBOOK
// =====================================

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Teachers');

// =====================================
// SCHOOL REPORT TITLE
// =====================================

$sheet->mergeCells('A1:I1');

$sheet->setCellValue(
    'A1',
    'DAAES SCHOOL - TEACHER RECORDS'
);

$sheet->mergeCells('A2:I2');

$sheet->setCellValue(
    'A2',
    'Official Teacher Management Report'
);

$sheet->mergeCells('A3:I3');

$sheet->setCellValue(
    'A3',
    'Generated On: ' . date('d-M-Y h:i A')
);

// =====================================
// COLUMN HEADINGS
// =====================================

$headers = [
    'S.No',
    'Teacher ID',
    'Teacher Name',
    'Email Address',
    'Phone Number',
    'Qualification',
    'Joining Date',
    'Status',
    'Record ID'
];

$sheet->fromArray($headers, NULL, 'A5');

// =====================================
// TITLE DESIGN
// =====================================

$sheet->getStyle('A1:I1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 18,
        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '102C50'
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

$sheet->getStyle('A2:I2')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 12,
        'color' => [
            'rgb' => 'D5A52B'
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ]
]);

$sheet->getStyle('A3:I3')->applyFromArray([
    'font' => [
        'italic' => true,
        'size' => 10,
        'color' => [
            'rgb' => '555555'
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ]
]);

$sheet->getRowDimension(1)->setRowHeight(35);
$sheet->getRowDimension(2)->setRowHeight(25);
$sheet->getRowDimension(3)->setRowHeight(22);

// =====================================
// HEADER DESIGN
// =====================================

$sheet->getStyle('A5:I5')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => [
            'rgb' => 'FFFFFF'
        ],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '173F68'
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => [
                'rgb' => 'D5A52B'
            ]
        ]
    ]
]);

$sheet->getRowDimension(5)->setRowHeight(30);

// =====================================
// INSERT TEACHER RECORDS
// =====================================

$row = 6;
$count = 1;

while ($teacher = $result->fetch_assoc()) {

    $qualification = $teacher['academic_qualification']
        ?? $teacher['qualification']
        ?? '';

    $data = [
        $count,
        excelSafe($teacher['employee_id'] ?? ''),
        excelSafe($teacher['full_name'] ?? ''),
        excelSafe($teacher['email'] ?? ''),
        excelSafe($teacher['phone'] ?? ''),
        excelSafe($qualification),
        excelSafe($teacher['joining_date'] ?? ''),
        ucfirst(excelSafe($teacher['status'] ?? '')),
        (int)($teacher['id'] ?? 0)
    ];

    foreach ($data as $columnIndex => $value) {

        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            $columnIndex + 1
        );

        // Keep text values as text (especially IDs and phone numbers).
        $cellType = is_int($value)
            ? DataType::TYPE_NUMERIC
            : DataType::TYPE_STRING;

        $sheet->setCellValueExplicit(
            $column . $row,
            (string)$value,
            $cellType
        );
    }

    // Alternating row background

    if ($count % 2 === 0) {

        $sheet->getStyle("A{$row}:I{$row}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('EDF3F9');

    }

    // Status colors

    $status = strtolower($teacher['status'] ?? '');

    if ($status === 'active') {

        $sheet->getStyle("H{$row}")
            ->getFont()
            ->getColor()
            ->setRGB('168B50');

    } else {

        $sheet->getStyle("H{$row}")
            ->getFont()
            ->getColor()
            ->setRGB('D04444');

    }

    $sheet->getStyle("A{$row}:I{$row}")
        ->getAlignment()
        ->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->getStyle("A{$row}:I{$row}")
        ->getBorders()
        ->getBottom()
        ->setBorderStyle(Border::BORDER_THIN)
        ->getColor()
        ->setRGB('DCE3EB');

    $sheet->getRowDimension($row)->setRowHeight(25);

    $row++;
    $count++;
}

// =====================================
// COLUMN WIDTHS
// =====================================

$widths = [
    'A' => 9,
    'B' => 19,
    'C' => 27,
    'D' => 32,
    'E' => 20,
    'F' => 25,
    'G' => 18,
    'H' => 15,
    'I' => 13
];

foreach ($widths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

// =====================================
// GENERAL ALIGNMENT
// =====================================

$sheet->getStyle("A6:I" . max(6, $row - 1))
    ->getAlignment()
    ->setVertical(Alignment::VERTICAL_CENTER);

// =====================================
// AUTO FILTER
// =====================================

$lastRow = max(5, $row - 1);

$sheet->setAutoFilter("A5:I{$lastRow}");

// Freeze heading rows

$sheet->freezePane('A6');

// =====================================
// PAGE SETTINGS
// =====================================

$sheet->getPageSetup()
    ->setOrientation(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
    );

$sheet->getPageSetup()
    ->setPaperSize(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
    );

$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 5);

// =====================================
// DOWNLOAD EXCEL
// =====================================

$filename = 'DAAES_Teacher_Report_' . date('Y-m-d_H-i-s') . '.xlsx';

if (isset($stmt)) {
    $stmt->close();
}

$conn->close();

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

$spreadsheet->disconnectWorksheets();

exit;

?>
