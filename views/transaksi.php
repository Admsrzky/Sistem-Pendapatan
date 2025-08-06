<?php
// Pastikan hakAkses dan base_url terdefinisi di file konfigurasi Anda
// Misalnya, jika Anda menggunakan framework sederhana atau file function.php
// Pastikan hakAkses(['Admin', 'User']) sudah didefinisikan dengan benar
// Contoh placeholder (jika belum ada di function.php):
if (!function_exists('hakAkses')) {
    function hakAkses($roles)
    {
        // Logika untuk memeriksa hak akses pengguna
        // Contoh sederhana:
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
            // header('Location: unauthorized.php'); // Atau halaman lain
            // exit();
        }
    }
}

// Pastikan base_url() didefinisikan dengan benar
// Contoh placeholder (jika belum ada di function.php):
if (!function_exists('base_url')) {
    function base_url()
    {
        // Ganti dengan base URL aplikasi Anda
        return "http://localhost:8081/rekam_jasa/";
    }
}

// Sertakan koneksi database Anda
include_once('config/conn.php'); // Sesuaikan path
include_once('config/function.php'); // Sesuaikan path, untuk encrypt/decrypt, delMask, dll.

// Set zona waktu ke Jakarta untuk konsistensi dengan tampilan
// date_default_timezone_set("Asia/Jakarta"); // Sebaiknya diatur global di file utama (index.php) atau config/function.php

// Ambil pesan success/error dari session jika ada (setelah proses CRUD)
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
<?php hakAkses(['Admin', 'User']); ?>

<script>
    // Fungsi submit untuk menambah atau mengedit data
    function submit(x) {
        if (x == 'add') {
            $('[name="transaksi_no"]').val("<?= noTransaksi(); ?>");
            $('[name="jenis_jasa"]').val("").trigger('change');
            $('[name="transaksi_nama"]').val("");
            $('[name="transaksi_jumlah"]').val("");
            $('#transaksiModal .modal-title').html('Tambah Transaksi');
            $('[name="transaksi_no"]').prop('readonly', false);
            $('[name="ubah"]').hide();
            $('[name="tambah"]').show();
        } else {
            $('#transaksiModal .modal-title').html('Edit Transaksi');
            $('[name="transaksi_no"]').prop('readonly', true);
            $('[name="tambah"]').hide();
            $('[name="ubah"]').show();

            $.ajax({
                type: "POST",
                data: {
                    id: x
                },
                url: '<?= base_url(); ?>process/transaksi.php', // Menggunakan process/transaksi.php untuk AJAX ambil data
                dataType: 'json',
                success: function(data) {
                    // Cek jika ada error dari server
                    if (data.error) {
                        Swal.fire('Error!', data.error, 'error');
                        console.error("Server Error:", data.error);
                    } else {
                        $('[name="idtransaksi"]').val(data.idtransaksi);
                        $('[name="transaksi_no"]').val(data.transaksi_no);
                        $('[name="transaksi_nama"]').val(data.transaksi_nama);
                        $('[name="jenis_jasa"]').val(data.jasa_id).trigger('change');
                        // Jika transaksi_jumlah adalah string dengan format uang, mungkin perlu delMask
                        $('[name="transaksi_jumlah"]').val(data.transaksi_jumlah);
                        // Contoh jika Anda menggunakan plugin uang (misal: jQuery Mask Plugin)
                        // $('[name="transaksi_jumlah"]').maskMoney('mask', data.transaksi_jumlah);
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Terjadi Kesalahan!', 'Gagal mengambil data: ' + status + " " + error, 'error');
                    console.error("AJAX Error:", status, error, xhr);
                }
            });
        }
    }

    // Fungsi untuk konfirmasi hapus (menggunakan SweetAlert2)
    $(document).ready(function() {
        $('.btn-hapus').on('click', function(e) {
            e.preventDefault(); // Mencegah tindakan default dari link
            var href = $(this).attr('href'); // Ambil URL hapus

            Swal.fire({ // Menggunakan SweetAlert2
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href; // Lanjutkan ke URL hapus jika dikonfirmasi
                }
            });
        });

        // Tampilkan notifikasi SweetAlert2 jika ada pesan success/error dari session
        <?php if (!empty($success_message)): ?>
            Swal.fire(
                'Berhasil!',
                '<?= $success_message; ?>',
                'success'
            )
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            Swal.fire(
                'Gagal!',
                '<?= $error_message; ?>',
                'error'
            )
        <?php endif; ?>
    });
</script>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Transaksi Penjualan</h1>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="#" class="btn btn-primary btn-icon-split btn-sm" data-toggle="modal" data-target="#transaksiModal"
                onclick="submit('add')">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Tambah</span>
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="20">NO</th>
                            <th>NO TRX</th>
                            <th>TGL & JAM</th>
                            <th>NAMA PELANGGAN</th>
                            <th>JENIS JASA</th>
                            <th>HARGA SATUAN</th>
                            <th>JUMLAH</th>
                            <th>TOTAL HARGA</th>
                            <th width="50">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $n = 1;
                        $query = mysqli_query($con, "SELECT * FROM transaksi x JOIN jasa x1 ON x.jasa_id=x1.idjasa ORDER BY x.transaksi_no ASC") or die(mysqli_error($con));
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
                                <td>
                                    <a href="#transaksiModal" data-toggle="modal"
                                        onclick="submit(<?= $row['idtransaksi']; ?>)" class="btn btn-sm btn-circle btn-info"
                                        data-toggle="tooltip" data-placement="top" title="Ubah Data"><i
                                            class="fas fa-edit"></i></a>
                                    <a href="<?= base_url(); ?>process/transaksi.php?act=<?= encrypt('delete'); ?>&id=<?= encrypt($row['idtransaksi']); ?>"
                                        class="btn btn-sm btn-circle btn-danger btn-hapus" data-toggle="tooltip"
                                        data-placement="top" title="Hapus Data"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
                                    value="<?= noTransaksi(); ?>" required>
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
                                    <?= list_jasa(); ?>
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