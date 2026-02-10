<?php
/**
 * Download Import Template
 * Path: modules/asset/download_template.php
 */

require_once '../../includes/auth.php';
requireAdmin();
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Asset Import Template');

// --- HEADER ---
$headers = [
    'A1' => 'รหัสครุภัณฑ์ (Asset Code) *',
    'B1' => 'ชื่อ (Name) *',
    'C1' => 'ยี่ห้อ (Brand)',
    'D1' => 'รุ่น (Model)',
    'E1' => 'Serial Number',
    'F1' => 'ประเภท (Type) *',
    'G1' => 'สถานที่ (Location)',
    'H1' => 'ผู้ถือครอง (Owner)',
    'I1' => 'สถานะ (Status)',
    'J1' => 'ราคา (Price)',
    'K1' => 'วันที่ซื้อ (Purchase Date)',
    'L1' => 'ประกันหมด (Warranty Expire)'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// สไตล์ Header (สีส้ม)
$headerStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FF6B35'] // สีส้ม
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
    ]
];

$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

// Auto Width
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setWidth(18);
}

// --- ตัวอย่างข้อมูล (แถวที่ 2-3) ---
$examples = [
    ['NB-2024-001', 'MacBook Pro 14"', 'Apple', 'M3 Pro', 'ABC123456', 'Notebook', 'อาคาร A ชั้น 3', '', 'active', '89000', '2024-01-15', '2027-01-15'],
    ['PC-2024-001', 'Dell OptiPlex 7090', 'Dell', 'OptiPlex 7090', 'SN789012', 'Desktop', 'ห้อง IT', '', 'active', '35000', '2024-02-01', '2027-02-01']
];

$row = 2;
foreach ($examples as $example) {
    $col = 'A';
    foreach ($example as $value) {
        $sheet->setCellValue($col . $row, $value);
        $col++;
    }
    $row++;
}

// สไตล์ตัวอย่าง (สีเหลืองอ่อน)
$sheet->getStyle('A2:L3')->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFF9E6']
    ],
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
    ]
]);

// --- คำอธิบาย ---
$sheet->setCellValue('A5', 'คำแนะนำ:');
$sheet->setCellValue('A6', '1. คอลัมน์ที่มี * ต้องกรอกข้อมูล (บังคับ)');
$sheet->setCellValue('A7', '2. ประเภท (Type) ต้องตรงกับข้อมูลในระบบ เช่น: Notebook, Desktop, Printer');
$sheet->setCellValue('A8', '3. สถานะ (Status) ใช้ได้: active, spare, repair, write_off');
$sheet->setCellValue('A9', '4. วันที่ใช้รูปแบบ: YYYY-MM-DD เช่น 2024-12-31');
$sheet->setCellValue('A10', '5. ลบแถวตัวอย่างนี้ออกก่อนนำเข้า');

$sheet->getStyle('A5:A10')->getFont()->setItalic(true)->setSize(9);
$sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11);

// --- Download ---
$filename = 'Asset_Import_Template.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
