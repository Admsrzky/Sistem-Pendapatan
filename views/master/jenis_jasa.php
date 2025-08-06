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
date_default_timezone_set("Asia/Jakarta");

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
            // Mode Tambah
            $('#kelasModal .modal-title').html('Tambah Jenis Jasa'); // Judul modal
            $('[name="idjasa"]').val(""); // Kosongkan idjasa (hidden input)
            $('[name="jasa_nama"]').val(""); // Kosongkan nama jasa
            $('[name="jasa_harga"]').val(""); // Kosongkan harga jasa
            $('[name="tambah"]').show(); // Tampilkan tombol tambah
            $('[name="ubah"]').hide(); // Sembunyikan tombol ubah
        } else {
            // Mode Edit
            $('#kelasModal .modal-title').html('Edit Jenis Jasa'); // Judul modal
            $('[name="tambah"]').hide(); // Sembunyikan tombol tambah
            $('[name="ubah"]').show(); // Tampilkan tombol ubah

            // Mengambil data jasa menggunakan AJAX
            $.ajax({
                type: "POST",
                data: {
                    id: x // ID jasa yang akan diedit dikirimkan
                },
                url: '<?= base_url(); ?>process/jenis_jasa.php', // Path ke script PHP untuk mengambil data
                dataType: 'json', // Harapannya server mengembalikan JSON
                success: function(data) {
                    // Cek jika ada error dari server
                    if (data.error) {
                        alert("Error: " + data.error);
                        console.error("Server Error:", data.error);
                    } else {
                        // Mengisi form modal dengan data yang diterima dari server
                        $('[name="idjasa"]').val(data.idjasa);
                        $('[name="jasa_nama"]').val(data.jasa_nama);
                        // Penting: Pastikan jasa_harga yang diterima sudah bersih dari 'Rp. ' dan titik
                        // Jika ada masking input di frontend, mungkin Anda perlu format ulang di sini
                        $('[name="jasa_harga"]').val(data.jasa_harga);
                        // Contoh jika Anda menggunakan plugin uang (misal: jQuery Mask Plugin)
                        // $('[name="jasa_harga"]').maskMoney('mask', data.jasa_harga);
                    }
                },
                error: function(xhr, status, error) {
                    // Penanganan error AJAX
                    alert("Terjadi kesalahan saat mengambil data: " + status + " " + error);
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
        <h1 class="h3 mb-0 text-gray-800">Jenis Jasa</h1>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="#" class="btn btn-primary btn-icon-split btn-sm" data-toggle="modal" data-target="#kelasModal"
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
                            <th>NAMA JASA</th>
                            <th>HARGA</th>
                            <th width="50">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $n = 1;
                        $query = mysqli_query($con, "SELECT * FROM jasa ORDER BY idjasa DESC") or die(mysqli_error($con));
                        while ($row = mysqli_fetch_array($query)):
                        ?>
                            <tr>
                                <td><?= $n++; ?></td>
                                <td><?= htmlspecialchars($row['jasa_nama']); ?></td>
                                <td><?= 'Rp. ' . number_format($row['jasa_harga'], 0, '', '.'); ?></td>
                                <td>
                                    <a href="#kelasModal" data-toggle="modal" onclick="submit(<?= $row['idjasa']; ?>)"
                                        class="btn btn-sm btn-circle btn-info" data-toggle="tooltip" data-placement="top"
                                        title="Ubah Data"><i class="fas fa-edit"></i></a>
                                    <a href="<?= base_url(); ?>process/jenis_jasa.php?act=<?= encrypt('delete'); ?>&id=<?= encrypt($row['idjasa']); ?>"
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

<div class="modal fade" id="kelasModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="<?= base_url(); ?>process/jenis_jasa.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="jasa_nama">Nama Jasa <span class="text-danger">*</span></label>
                                <input type="hidden" name="idjasa">
                                <input type="text" class="form-control" id="jasa_nama" name="jasa_nama" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="jasa_harga">Harga Jasa <span class="text-danger">*</span></label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp.</span>
                                </div>
                                <input type="text" class="form-control uang" name="jasa_harga"
                                    aria-describedby="jasa_harga" required>
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