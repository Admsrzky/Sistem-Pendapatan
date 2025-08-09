<?php
// Sertakan file konfigurasi dan fungsi
include_once('config/conn.php');
include_once('config/function.php');

// Pengecekan hak akses
hakAkses(['Admin', 'User']);

// Ambil pesan notifikasi
$success_message = '';
$error_message = '';
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Transaksi Penjualan</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="?transaksi_form" class="btn btn-primary btn-icon-split btn-sm">
                <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                <span class="text">Tambah Transaksi Baru</span>
            </a>
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
                            <th width="100">AKSI</th>
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
                                <td>
                                    <a href="?transaksi_detail&id=<?= encrypt($row['idtransaksi']); ?>"
                                        class="btn btn-sm btn-circle btn-primary" data-toggle="tooltip" data-placement="top"
                                        title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?transaksi_form&id=<?= encrypt($row['idtransaksi']); ?>"
                                        class="btn btn-sm btn-circle btn-info" data-toggle="tooltip" data-placement="top"
                                        title="Ubah Data">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= base_url(); ?>process/transaksi.php?act=<?= encrypt('delete'); ?>&id=<?= encrypt($row['idtransaksi']); ?>"
                                        class="btn btn-sm btn-circle btn-danger btn-hapus" data-toggle="tooltip"
                                        data-placement="top" title="Hapus Data">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Fungsi konfirmasi hapus (tetap sama)
        $('.btn-hapus').on('click', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data transaksi dan semua item di dalamnya akan dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });

        // Notifikasi (tetap sama)
        <?php if (!empty($success_message)): ?>
            Swal.fire('Berhasil!', '<?= $success_message; ?>', 'success');
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            Swal.fire('Gagal!', '<?= $error_message; ?>', 'error');
        <?php endif; ?>
    });
</script>