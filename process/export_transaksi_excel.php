<?php
// process/export_transaksi_excel.php

require_once '../vendor/autoload.php'; // Penting: Include autoloader Composer
require_once '../config/conn.php';     // Sesuaikan path ke file koneksi Anda
require_once '../config/function.php'; // Diperlukan untuk base_url() atau hakAkses jika digunakan

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Tambahkan ini

// Pastikan koneksi database tersedia
if (!isset($con) || !$con) {
    die("Error: Koneksi database tidak tersedia.");
}

// Set zona waktu untuk konsistensi
date_default_timezone_set("Asia/Jakarta");

// 1. Ambil data dengan filter (selaraskan dengan logika di views/laporan.php)
$sql = "SELECT * FROM transaksi x JOIN jasa x1 ON x.jasa_id=x1.idjasa";
$conditions = [];

// Filter berdasarkan Tanggal (transaksi_tgl adalah DATE)
if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
    $date_filter = mysqli_real_escape_string($con, $_GET['filter_date']);
    $conditions[] = "x.transaksi_tgl = '{$date_filter}'";
}

// Filter berdasarkan Bulan (transaksi_tgl adalah DATE)
if (isset($_GET['filter_month']) && !empty($_GET['filter_month'])) {
    $month_filter = (int)$_GET['filter_month'];
    $conditions[] = "MONTH(x.transaksi_tgl) = {$month_filter}";
}

// Filter berdasarkan Tahun (transaksi_tgl adalah DATE)
if (isset($_GET['filter_year']) && !empty($_GET['filter_year'])) {
    $year_filter = (int)$_GET['filter_year'];
    $conditions[] = "YEAR(x.transaksi_tgl) = {$year_filter}";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Urutkan data seperti di laporan HTML
$sql .= " ORDER BY x.transaksi_tgl ASC, x.transaksi_no ASC";

$query = mysqli_query($con, $sql);

if (!$query) {
    die("Error dalam mengambil data: " . mysqli_error($con));
}

// Simpan data ke array dan hitung total pendapatan
$data_transaksi = [];
$total_pendapatan = 0;
while ($row = mysqli_fetch_array($query)) {
    $data_transaksi[] = $row;
    // Penting: konversi ke numerik sebelum menjumlahkan jika tipe DB adalah VARCHAR
    $total_pendapatan += (float)str_replace(['Rp. ', '.'], '', $row['transaksi_total_harga']);
}

// 2. Buat objek Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Laporan Transaksi');

// 3. Tambahkan judul laporan
$sheet->setCellValue('A1', 'LAPORAN DATA TRANSAKSI');
$sheet->mergeCells('A1:H1'); // Ubah G menjadi H karena penambahan kolom Harga Satuan
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Dicetak pada: ' . date('d-m-Y H:i:s'));
$sheet->mergeCells('A2:H2'); // Ubah G menjadi H
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(3)->setRowHeight(15); // Spacer row

// 4. Tambahkan header tabel
// Tambahkan 'HARGA SATUAN' ke array headers
$headers = ['NO', 'NO TRX', 'TGL', 'NAMA PELANGGAN', 'JENIS JASA', 'HARGA SATUAN', 'JUMLAH', 'TOTAL HARGA'];
$column_index = 0;
foreach ($headers as $header) {
    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column_index + 1);
    $sheet->setCellValue($column . '4', $header);
    $sheet->getStyle($column . '4')->getFont()->setBold(true);
    $sheet->getStyle($column . '4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
    $sheet->getStyle($column . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $column_index++;
}

// Set lebar kolom (sesuaikan jika perlu)
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15); // Lebar TGL
$sheet->getColumnDimension('D')->setWidth(30);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(15); // Lebar HARGA SATUAN
$sheet->getColumnDimension('G')->setWidth(10); // Lebar JUMLAH
$sheet->getColumnDimension('H')->setWidth(20); // Lebar TOTAL HARGA

// 5. Tambahkan data dari database
$rowNum = 5; // Mulai dari baris ke-5 setelah header

if (!empty($data_transaksi)) {
    $n = 1;
    foreach ($data_transaksi as $row) {
        $sheet->setCellValue('A' . $rowNum, $n++);
        $sheet->setCellValue('B' . $rowNum, $row['transaksi_no']);
        $sheet->setCellValue('C' . $rowNum, date('d-m-Y', strtotime($row['transaksi_tgl']))); // Perbaikan tanggal
        $sheet->setCellValue('D' . $rowNum, $row['transaksi_nama']);
        $sheet->setCellValue('E' . $rowNum, $row['jasa_nama']);

        // Harga Satuan: konversi ke numerik untuk format Excel yang benar
        $harga_satuan_numeric = (float)str_replace(['Rp. ', '.'], '', $row['jasa_harga']);
        $sheet->setCellValue('F' . $rowNum, $harga_satuan_numeric);
        $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('"Rp"#,##0.00'); // Format Rupiah

        // Jumlah: konversi ke numerik
        $jumlah_numeric = (float)str_replace(['Rp. ', '.'], '', $row['transaksi_jumlah']);
        $sheet->setCellValue('G' . $rowNum, $jumlah_numeric);

        // Total Harga: konversi ke numerik
        $total_harga_numeric = (float)str_replace(['Rp. ', '.'], '', $row['transaksi_total_harga']);
        $sheet->setCellValue('H' . $rowNum, $total_harga_numeric);
        $sheet->getStyle('H' . $rowNum)->getNumberFormat()->setFormatCode('"Rp"#,##0.00'); // Format Rupiah

        // Alignments
        $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $rowNum++;
    }

    // Tambahkan baris total pendapatan
    $sheet->setCellValue('A' . $rowNum, 'Total Pendapatan:');
    $sheet->mergeCells('A' . $rowNum . ':G' . $rowNum); // Merge kolom A sampai G (sebelumnya F)
    $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet->setCellValue('H' . $rowNum, $total_pendapatan); // Nilai total pendapatan
    $sheet->getStyle('H' . $rowNum)->getFont()->setBold(true);
    $sheet->getStyle('H' . $rowNum)->getNumberFormat()->setFormatCode('"Rp"#,##0.00'); // Format sebagai Rupiah
    $sheet->getStyle('H' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('A' . $rowNum . ':H' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2'); // Warna latar belakang untuk total

    $rowNum++; // Tambah baris lagi untuk border
} else {
    $sheet->setCellValue('A' . $rowNum, 'Tidak ada data transaksi yang ditemukan.');
    $sheet->mergeCells('A' . $rowNum . ':H' . $rowNum); // Ubah G menjadi H
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A' . $rowNum)->getFont()->setItalic(true);
}

// Tambahkan border ke semua sel data (termasuk baris total jika ada)
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];
// Sesuaikan rentang border agar mencakup baris total juga
$sheet->getStyle('A4:H' . ($rowNum - 1))->applyFromArray($styleArray); // Ubah G menjadi H

// 6. Siapkan Writer dan kirim file ke browser
$writer = new Xlsx($spreadsheet);
$fileName = 'Laporan_Transaksi_' . date('Ymd_His') . '.xlsx'; // Format .xlsx lebih modern

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
