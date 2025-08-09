<?php
// Pastikan file ini hanya bisa diakses setelah login dan punya hak
include_once('config/conn.php');
include_once('config/function.php');
hakAkses(['Admin', 'User']);

// Cek apakah ID transaksi ada di URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID Transaksi tidak ditemukan.";
    header("Location: ?transaksi");
    exit();
}

$id_transaksi = decrypt($_GET['id']);

// Ambil data header/master transaksi
$query_transaksi = mysqli_query($con, "SELECT t.*, p.pengguna_nama 
                                      FROM transaksi t 
                                      LEFT JOIN pengguna p ON t.pengguna_id = p.idpengguna 
                                      WHERE t.idtransaksi = '$id_transaksi'");
$data_transaksi = mysqli_fetch_assoc($query_transaksi);

// Jika transaksi tidak ditemukan, kembalikan ke halaman daftar
if (!$data_transaksi) {
    $_SESSION['error'] = "Data transaksi tidak valid.";
    header("Location: ?transaksi");
    exit();
}

// Ambil data detail/item-item transaksi
$query_items = mysqli_query($con, "SELECT ti.*, j.jasa_nama 
                                   FROM transaksi_items ti 
                                   JOIN jasa j ON ti.jasa_id = j.idjasa 
                                   WHERE ti.transaksi_id = '$id_transaksi'");
$data_items = [];
while ($item = mysqli_fetch_assoc($query_items)) {
    $data_items[] = $item;
}
?>

<style>
    @media print {

        /* Sembunyikan semua elemen yang tidak perlu dicetak */
        body,
        .navbar,
        .sidebar,
        .card-header,
        .card-footer,
        .breadcrumb,
        .d-print-none {
            visibility: hidden;
            display: none !important;
        }

        /* Tampilkan hanya area yang ingin dicetak */
        .card.printable-area,
        .card.printable-area * {
            visibility: visible;
        }

        /* Atur posisi dan layout area cetak */
        .card.printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            box-shadow: none !important;
        }

        .table {
            font-size: 12px;
        }

        h3 {
            font-size: 16px;
        }
    }
</style>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 d-print-none">
        <h1 class="h3 mb-0 text-gray-800">Detail Transaksi</h1>
        <div>
            <a href="?transaksi" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Cetak
                Struk</button>
        </div>
    </div>

    <div class="card shadow mb-4 printable-area">
        <div class="card-header py-3">
            <h3 class="m-0 font-weight-bold text-primary">Struk Transaksi:
                <?= htmlspecialchars($data_transaksi['transaksi_no']); ?></h3>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="150"><strong>No. Transaksi</strong></td>
                            <td width="10">:</td>
                            <td><?= htmlspecialchars($data_transaksi['transaksi_no']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal</strong></td>
                            <td>:</td>
                            <td><?= date('d F Y, H:i', strtotime($data_transaksi['transaksi_tgl'])); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="150"><strong>Nama Pelanggan</strong></td>
                            <td width="10">:</td>
                            <td><?= htmlspecialchars($data_transaksi['transaksi_nama']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Kasir</strong></td>
                            <td>:</td>
                            <td><?= htmlspecialchars($data_transaksi['pengguna_nama']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <h5 class="font-weight-bold">Rincian Item:</h5>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="20">NO</th>
                            <th>NAMA JASA</th>
                            <th class="text-right">HARGA SATUAN</th>
                            <th class="text-center">JUMLAH</th>
                            <th class="text-right">SUBTOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n = 1;
                        foreach ($data_items as $item): ?>
                            <tr>
                                <td class="text-center"><?= $n++; ?></td>
                                <td><?= htmlspecialchars($item['jasa_nama']); ?></td>
                                <td class="text-right"><?= 'Rp ' . number_format($item['harga'], 0, ',', '.'); ?></td>
                                <td class="text-center"><?= $item['jumlah']; ?></td>
                                <td class="text-right"><?= 'Rp ' . number_format($item['subtotal'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">GRAND TOTAL</th>
                            <th class="text-right bg-gray-200">
                                <?= 'Rp ' . number_format($data_transaksi['transaksi_total_harga'], 0, ',', '.'); ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>