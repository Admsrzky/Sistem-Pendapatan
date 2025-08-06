<?php
// Pastikan hak akses sudah dicek di index.php atau config/page.php
// Jika belum, Anda bisa tambahkan: hakAkses(['Admin', 'User']);
// Atau pastikan file ini hanya di-include setelah hak akses divalidasi.
hakAkses(['Admin', 'User']); // Memanggil fungsi hakAkses

// date_default_timezone_set("Asia/Jakarta"); // Sebaiknya diatur secara global di index.php atau config/function.php

// Ambil koneksi database (asumsi $con sudah tersedia dari index.php)
// global $con; // Tidak perlu jika sudah di-include di index.php dan tersedia di scope global.
?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Pendapatan</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <form action="index.php" method="get">
                        <input type="hidden" name="laporan" value="true">
                        <div class="form-group mb-0">
                            <label for="filter_date">Filter Tanggal:</label>
                            <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date"
                                value="<?= isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : ''; ?>">
                        </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label for="filter_month">Filter Bulan:</label>
                        <select name="filter_month" id="filter_month" class="form-control form-control-sm">
                            <option value="">-- Semua Bulan --</option>
                            <?php
                            $months = [
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ];
                            foreach ($months as $num => $name) {
                                $selected = (isset($_GET['filter_month']) && $_GET['filter_month'] == $num) ? 'selected' : '';
                                echo "<option value='{$num}' {$selected}>{$name}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label for="filter_year">Filter Tahun:</label>
                        <select name="filter_year" id="filter_year" class="form-control form-control-sm">
                            <option value="">-- Semua Tahun --</option>
                            <?php
                            $currentYear = date('Y');
                            // Asumsikan data ada dari 2020 hingga tahun sekarang + 1 (untuk proyeksi/input mendatang)
                            for ($year = 2020; $year <= $currentYear + 1; $year++) {
                                $selected = (isset($_GET['filter_year']) && $_GET['filter_year'] == $year) ? 'selected' : '';
                                echo "<option value='{$year}' {$selected}>{$year}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end justify-content-start">
                    <div class="btn-group" role="group" aria-label="Filter Buttons">
                        <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter"></i>
                            Filter</button>
                        </form>
                        <a href="index.php?laporan=true" class="btn btn-secondary btn-sm"><i
                                class="fas fa-sync-alt"></i> Reset</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <?php
                // Ambil semua parameter GET saat ini
                $current_get_params = $_GET;
                // Pastikan parameter 'laporan' ada untuk menjaga konteks
                $current_get_params['laporan'] = true;
                // Bangun query string
                $query_string_export = http_build_query($current_get_params);
                ?>
                <a href="process/export_transaksi_pdf.php?<?= htmlspecialchars($query_string_export); ?>"
                    class="btn btn-danger btn-sm ml-2" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                <a href="process/export_transaksi_excel.php?<?= htmlspecialchars($query_string_export); ?>"
                    class="btn btn-success btn-sm ml-2"><i class="fas fa-file-excel"></i> Excel</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="20">NO</th>
                            <th>NO TRX</th>
                            <th>TGL</th>
                            <th>NAMA PELANGGAN</th>
                            <th>JENIS JASA</th>
                            <th>HARGA SATUAN</th>
                            <th>JUMLAH</th>
                            <th>TOTAL HARGA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $n = 1;
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

                        $sql .= " ORDER BY x.transaksi_tgl ASC, x.transaksi_no ASC"; // Urutkan juga berdasarkan nomor transaksi

                        $query = mysqli_query($con, $sql);

                        // Cek apakah query berhasil dieksekusi
                        if (!$query) {
                            echo "<tr><td colspan='8' class='text-center text-danger'>Error Query Database: " . mysqli_error($con) . "</td></tr>";
                        } elseif (mysqli_num_rows($query) > 0) {
                            while ($row = mysqli_fetch_array($query)):
                        ?>
                                <tr>
                                    <td><?= $n++; ?></td>
                                    <td><?= htmlspecialchars($row['transaksi_no']); ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['transaksi_tgl'])); ?></td>
                                    <td><?= htmlspecialchars($row['transaksi_nama']); ?></td>
                                    <td><?= htmlspecialchars($row['jasa_nama']); ?></td>
                                    <td><?= 'Rp. ' . number_format($row['jasa_harga'], 0, '', '.'); ?></td>
                                    <td><?= htmlspecialchars($row['transaksi_jumlah']); ?></td>
                                    <td><?= 'Rp. ' . number_format($row['transaksi_total_harga'], 0, '', '.'); ?></td>
                                </tr>
                            <?php
                            endwhile;
                        } else {
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data transaksi yang ditemukan dengan filter
                                    yang dipilih.</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <?php
                        // Query untuk menghitung total pendapatan berdasarkan filter yang sama
                        $sql_total = "SELECT SUM(transaksi_total_harga) AS total_pendapatan FROM transaksi x";
                        if (!empty($conditions)) {
                            $sql_total .= " WHERE " . implode(" AND ", $conditions);
                        }
                        $query_total = mysqli_query($con, $sql_total);
                        $total_row = mysqli_fetch_assoc($query_total);
                        $total_pendapatan = $total_row['total_pendapatan'] ?? 0; // Pastikan ada nilai default jika null
                        ?>
                        <tr>
                            <th colspan="7" class="text-right">Total Pendapatan:</th>
                            <th><?= 'Rp. ' . number_format($total_pendapatan, 0, '', '.'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "paging": true, // Aktifkan pagination
            "searching": false, // Matikan searching bawaan DataTables (filter sudah manual)
            "ordering": true, // Aktifkan sorting
            "info": true // Tampilkan informasi halaman
        });
    });
</script>