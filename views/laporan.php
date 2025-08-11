<?php
hakAkses(['Admin', 'User']);
?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Pendapatan</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form action="" method="get">
                <input type="hidden" name="laporan_pendapatan" value="true">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label for="filter_date">Filter Tanggal:</label>
                            <input type="text" class="form-control form-control-sm" id="filter_date" name="filter_date"
                                placeholder="yyyy-mm-dd"
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
                                for ($year = 2020; $year <= $currentYear + 1; $year++) {
                                    $selected = (isset($_GET['filter_year']) && $_GET['filter_year'] == $year) ? 'selected' : '';
                                    echo "<option value='{$year}' {$selected}>{$year}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-start">
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i>
                                Filter</button>
                            <a href="?laporan_pendapatan=true" class="btn btn-secondary btn-sm"><i
                                    class="fas fa-sync-alt"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <?php
                $query_string_export = http_build_query($_GET);
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
                        $total_pendapatan = 0;
                        $conditions = [];

                        $sql = "SELECT
                                    t.transaksi_no,
                                    t.transaksi_tgl,
                                    t.transaksi_nama,
                                    j.jasa_nama,
                                    ti.harga AS harga_satuan,
                                    ti.jumlah,
                                    ti.subtotal
                                FROM
                                    transaksi_items ti
                                JOIN
                                    transaksi t ON ti.transaksi_id = t.idtransaksi
                                JOIN
                                    jasa j ON ti.jasa_id = j.idjasa";

                        // --- (Bagian filter tidak perlu diubah) ---
                        if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
                            $date_filter = mysqli_real_escape_string($con, $_GET['filter_date']);
                            $conditions[] = "t.transaksi_tgl = '{$date_filter}'";
                        }
                        if (isset($_GET['filter_month']) && !empty($_GET['filter_month'])) {
                            $month_filter = (int)$_GET['filter_month'];
                            $conditions[] = "MONTH(t.transaksi_tgl) = {$month_filter}";
                        }
                        if (isset($_GET['filter_year']) && !empty($_GET['filter_year'])) {
                            $year_filter = (int)$_GET['filter_year'];
                            $conditions[] = "YEAR(t.transaksi_tgl) = {$year_filter}";
                        }
                        if (!empty($conditions)) {
                            $sql .= " WHERE " . implode(" AND ", $conditions);
                        }
                        $sql .= " ORDER BY t.transaksi_tgl ASC, t.transaksi_no ASC";
                        // --- (Batas akhir bagian filter) ---

                        $query = mysqli_query($con, $sql);

                        if (!$query) {
                            echo "<tr><td colspan='8' class='text-center text-danger'>Error Query Database: " . mysqli_error($con) . "</td></tr>";
                        } elseif (mysqli_num_rows($query) > 0) {
                            while ($row = mysqli_fetch_assoc($query)):
                                $total_pendapatan += $row['subtotal'];
                        ?>
                                <tr>
                                    <td><?= $n++; ?></td>
                                    <td><?= htmlspecialchars($row['transaksi_no']); ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['transaksi_tgl'])); ?></td>
                                    <td><?= htmlspecialchars($row['transaksi_nama']); ?></td>
                                    <td><?= htmlspecialchars($row['jasa_nama']); ?></td>
                                    <td><?= 'Rp. ' . number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                                    <td><?= htmlspecialchars($row['jumlah']); ?></td>
                                    <td><?= 'Rp. ' . number_format($row['subtotal'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php
                            endwhile;
                        } else {
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data transaksi yang ditemukan.</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <?php
                        ?>
                        <tr>
                            <th colspan="7" class="text-right">Total Pendapatan:</th>
                            <th><?= 'Rp. ' . number_format($total_pendapatan, 0, ',', '.'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Inisialisasi Datepicker jika Anda menggunakannya
        // $('#filter_date').datepicker({ format: 'yyyy-mm-dd', ... });

        $('#dataTable').DataTable({
            "paging": true,
            "searching": false,
            "ordering": true,
            "info": true,
            "order": []
        });
    });
</script>