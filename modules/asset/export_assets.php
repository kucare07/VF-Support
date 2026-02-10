<?php
/**
 * Export Assets to XLSX
 * Path: modules/asset/export_xlsx.php
 */

require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/db_connect.php';
require_once '../../vendor/autoload.php'; // Composer autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ดึงข้อมูล Assets
$sql = "SELECT 
            a.asset_code, a.name, a.brand, a.model, a.serial_number,
            t.name as type_name, 
            l.name as location_name,
            u.fullname as owner_name,
            a.status, a.price, a.purchase_date, a.warranty_expire
        FROM assets a 
        LEFT JOIN asset_types t ON a.asset_type_id = t.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN users u ON a.current_user_id = u.id
        ORDER BY a.asset_code ASC";

$assets = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Assets Report');

// --- HEADER ---
$headers = [
    'A1' => 'รหัสครุภัณฑ์',
    'B1' => 'ชื่อ',
    'C1' => 'ยี่ห้อ',
    'D1' => 'รุ่น',
    'E1' => 'Serial Number',
    'F1' => 'ประเภท',
    'G1' => 'สถานที่',
    'H1' => 'ผู้ถือครอง',
    'I1' => 'สถานะ',
    'J1' => 'ราคา',
    'K1' => 'วันที่ซื้อ',
    'L1' => 'ประกันหมด'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// สไตล์ Header
$headerStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
];

$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

// Auto Width
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// --- DATA ---
$row = 2;
foreach ($assets as $asset) {
    $sheet->setCellValue('A' . $row, $asset['asset_code']);
    $sheet->setCellValue('B' . $row, $asset['name']);
    $sheet->setCellValue('C' . $row, $asset['brand']);
    $sheet->setCellValue('D' . $row, $asset['model']);
    $sheet->setCellValue('E' . $row, $asset['serial_number']);
    $sheet->setCellValue('F' . $row, $asset['type_name']);
    $sheet->setCellValue('G' . $row, $asset['location_name']);
    $sheet->setCellValue('H' . $row, $asset['owner_name']);
    $sheet->setCellValue('I' . $row, $asset['status']);
    $sheet->setCellValue('J' . $row, $asset['price']);
    $sheet->setCellValue('K' . $row, $asset['purchase_date']);
    $sheet->setCellValue('L' . $row, $asset['warranty_expire']);
    
    $row++;
}

// ใส่ Border ให้ทั้ง Table
$sheet->getStyle('A1:L' . ($row - 1))->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
    ]
]);

// --- SUMMARY (ข้อมูลสรุป) ---
$summaryRow = $row + 2;
$sheet->setCellValue('A' . $summaryRow, 'สรุป:');
$sheet->setCellValue('B' . $summaryRow, 'จำนวนครุภัณฑ์ทั้งหมด');
$sheet->setCellValue('C' . $summaryRow, count($assets) . ' รายการ');
$sheet->getStyle('A' . $summaryRow . ':C' . $summaryRow)->getFont()->setBold(true);

// --- DOWNLOAD ---
$filename = 'Assets_Report_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
