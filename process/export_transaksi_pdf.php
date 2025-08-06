<?php
// process/export_transaksi_pdf.php

require_once '../vendor/autoload.php'; // Penting: Include autoloader Composer
require_once '../config/conn.php';     // Sesuaikan path ke file koneksi Anda
require_once '../config/function.php'; // Diperlukan untuk base_url() atau hakAkses jika digunakan

use Dompdf\Dompdf;
use Dompdf\Options;

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

// Simpan hasil query ke array dan hitung total pendapatan
$data_transaksi = [];
$total_pendapatan = 0;
while ($row = mysqli_fetch_array($query)) {
    $data_transaksi[] = $row;
    // Penting: konversi ke numerik sebelum menjumlahkan jika tipe DB adalah VARCHAR
    $total_pendapatan += (float)str_replace(['Rp. ', '.'], '', $row['transaksi_total_harga']);
}

// 2. Buat konten HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center; }
        h1 { text-align: center; font-size: 18pt; margin-bottom: 5px; }
        h2 { text-align: center; font-size: 12pt; margin-bottom: 20px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer-total { font-weight: bold; background-color: #e6f2ff; } /* Gaya untuk baris total */
    </style>
</head>
<body>
    <h1>Laporan Data Transaksi</h1>
    <h2>Dicetak pada: ' . date('d-m-Y H:i:s') . '</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 15%;">NO TRX</th>
                <th style="width: 10%;">TGL</th> <th style="width: 20%;">NAMA PELANGGAN</th>
                <th style="width: 15%;">JENIS JASA</th>
                <th style="width: 10%;">HARGA SATUAN</th> <th style="width: 5%;">JUMLAH</th>
                <th style="width: 20%;">TOTAL HARGA</th>
            </tr>
        </thead>
        <tbody>';

$n = 1;
if (!empty($data_transaksi)) {
    foreach ($data_transaksi as $row) {
        $html .= '<tr>
            <td class="text-center">' . $n++ . '</td>
            <td class="text-center">' . htmlspecialchars($row['transaksi_no']) . '</td>
            <td class="text-center">' . date('d-m-Y', strtotime($row['transaksi_tgl'])) . '</td> <td>' . htmlspecialchars($row['transaksi_nama']) . '</td>
            <td>' . htmlspecialchars($row['jasa_nama']) . '</td>
            <td class="text-right">' . 'Rp. ' . number_format((float)$row['jasa_harga'], 0, ',', '.') . '</td> <td class="text-center">' . htmlspecialchars($row['transaksi_jumlah']) . '</td>
            <td class="text-right">' . 'Rp. ' . number_format((float)$row['transaksi_total_harga'], 0, ',', '.') . '</td>
        </tr>';
    }
    // Tambahkan baris total pendapatan
    $html .= '<tr class="footer-total">
        <td colspan="7" class="text-right">Total Pendapatan:</td> <td class="text-right">' . 'Rp. ' . number_format($total_pendapatan, 0, ',', '.') . '</td>
    </tr>';
} else {
    $html .= '<tr><td colspan="8" class="text-center">Tidak ada data transaksi yang ditemukan.</td></tr>'; // Colspan disesuaikan
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// 3. Inisialisasi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false); // Biasanya false untuk keamanan dan performa unless you need external resources

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape'); // Atur ukuran kertas

$dompdf->render();

// Output PDF ke browser
$dompdf->stream('Laporan_Transaksi_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
exit;
