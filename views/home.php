<?php
hakAkses(['Admin', 'User']);
date_default_timezone_set("Asia/Jakarta");

// Assuming $con is your database connection variable, adjust path if necessary
// include_once 'config/koneksi.php'; // Example include for database connection

// Calculate Total Income
$queryTotal = mysqli_query($con, "SELECT SUM(transaksi_total_harga) AS total_income FROM transaksi") or die(mysqli_error($con));
$dataTotal = mysqli_fetch_assoc($queryTotal);
$totalIncome = $dataTotal['total_income'] ?: 0; // Set to 0 if null

// Calculate Weekly Income (last 7 days)
$sevenDaysAgo = strtotime('-7 days');
// Convert PHP timestamp to MySQL DATETIME format for comparison
$sevenDaysAgoFormatted = date('Y-m-d H:i:s', $sevenDaysAgo);
$queryWeekly = mysqli_query($con, "SELECT SUM(transaksi_total_harga) AS weekly_income FROM transaksi WHERE transaksi_tgl >= '$sevenDaysAgoFormatted'") or die(mysqli_error($con));
$dataWeekly = mysqli_fetch_assoc($queryWeekly);
$weeklyIncome = $dataWeekly['weekly_income'] ?: 0; // Set to 0 if null

// Calculate Monthly Income (current month)
$firstDayOfMonth = strtotime(date('Y-m-01'));
// Convert PHP timestamp to MySQL DATETIME format for comparison
$firstDayOfMonthFormatted = date('Y-m-d H:i:s', $firstDayOfMonth);
$queryMonthly = mysqli_query($con, "SELECT SUM(transaksi_total_harga) AS monthly_income FROM transaksi WHERE transaksi_tgl >= '$firstDayOfMonthFormatted'") or die(mysqli_error($con));
$dataMonthly = mysqli_fetch_assoc($queryMonthly);
$monthlyIncome = $dataMonthly['monthly_income'] ?: 0; // Set to 0 if null

?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Beranda</h1>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Pendapatan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= 'Rp. ' . number_format($totalIncome, 0, '', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Pendapatan Mingguan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= 'Rp. ' . number_format($weeklyIncome, 0, '', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pendapatan Bulanan
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= 'Rp. ' . number_format($monthlyIncome, 0, '', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ringkasan Pendapatan</h6>
                </div>
                <div class="card-body">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Transaksi Terbaru</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="20">NO</th>
                                    <th>NO TRX</th>
                                    <th>TANGGAL</th>
                                    <th>NAMA PELANGGAN</th>
                                    <th>TOTAL HARGA</th>
                                    <th>KASIR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $n = 1;
                                // Query hanya dari tabel transaksi, di-join dengan pengguna untuk nama kasir
                                $query = mysqli_query($con, "SELECT t.*, p.pengguna_nama FROM transaksi t LEFT JOIN pengguna p ON t.pengguna_id = p.idpengguna ORDER BY t.idtransaksi DESC") or die(mysqli_error($con));
                                while ($row = mysqli_fetch_array($query)):
                                ?>
                                    <tr>
                                        <td><?= $n++; ?></td>
                                        <td><?= htmlspecialchars($row['transaksi_no']); ?></td>
                                        <td><?= date('d-m-Y', strtotime($row['transaksi_tgl'])); ?></td>
                                        <td><?= htmlspecialchars($row['transaksi_nama']); ?></td>
                                        <td><?= 'Rp. ' . number_format($row['transaksi_total_harga'], 0, ',', '.'); ?></td>
                                        <td><?= htmlspecialchars($row['pengguna_nama']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="transaksiModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="<?= base_url(); ?>process/transaksi.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="transaksi_no">Nomor Transaksi <span class="text-danger">*</span></label>
                                <input type="hidden" name="idtransaksi">
                                <input type="text" class="form-control" id="transaksi_no" name="transaksi_no"
                                    value="<?= noTransaksi($con); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="transaksi_nama">Nama Pelanggan <span class="text-danger">*</span></label>
                                <input name="transaksi_nama" id="transaksi_nama" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <div class="form-group">
                                <label for="jenis_jasa">Jenis Jasa <span class="text-danger">*</span></label>
                                <select name="jenis_jasa" id="jenis_jasa" class="form-control select2"
                                    style="width:100%;" required>
                                    <option value="">-- Pilih Jenis Jasa --</option>
                                    <?= list_jasa($con); ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="transaksi_jumlah">Jumlah <span class="text-danger">*</span></label>
                                <input name="transaksi_jumlah" id="transaksi_jumlah" class="form-control uang" required>
                            </div>
                        </div>
                    </div>
                    <hr class="sidebar-divider">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal"><i class="fas fa-times"></i>
                        Batal</button>
                    <button class="btn btn-primary float-right" type="submit" name="tambah"><i class="fas fa-save"></i>
                        Tambah</button>
                    <button class="btn btn-primary float-right" type="submit" name="ubah"><i class="fas fa-save"></i>
                        Ubah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Get the canvas element for the chart
    var ctx = document.getElementById('incomeChart').getContext('2d');

    // Data from PHP, ensuring they are treated as numbers
    var totalIncome = parseFloat(<?= $totalIncome; ?>);
    var weeklyIncome = parseFloat(<?= $weeklyIncome; ?>);
    var monthlyIncome = parseFloat(<?= $monthlyIncome; ?>);

    // Create the chart
    var incomeChart = new Chart(ctx, {
        type: 'bar', // You can change this to 'line', 'pie', etc.
        data: {
            labels: ['Total Pendapatan', 'Pendapatan Mingguan', 'Pendapatan Bulanan'],
            datasets: [{
                label: 'Pendapatan (IDR)',
                data: [totalIncome, weeklyIncome, monthlyIncome],
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)', // Primary color for Total
                    'rgba(28, 200, 138, 0.8)', // Success color for Weekly
                    'rgba(54, 185, 204, 0.8)' // Info color for Monthly
                ],
                borderColor: [
                    'rgba(78, 115, 223, 1)',
                    'rgba(28, 200, 138, 1)',
                    'rgba(54, 185, 204, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Allows you to control the chart size better
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return 'Rp. ' + value.toLocaleString('id-ID'); // Format as Indonesian Rupiah
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp. ' + context.parsed.y.toLocaleString('id-ID');
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>