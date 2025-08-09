<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include file konfigurasi dan fungsi
include('../config/conn.php');
include('../config/function.php');

// Pastikan koneksi database berhasil
if (!isset($con) || !$con) {
    $_SESSION['error'] = 'Koneksi database gagal diinisialisasi.';
    header('Location: ../index.php?transaksi');
    exit();
}

// URL redirect untuk kembali ke halaman daftar transaksi
$redirect_url = '../index.php?transaksi';


// =========================================================================
// Dipertahankan dari kode Anda: Mengambil idpengguna dari pengguna 'Admin'
// untuk dicatat di setiap transaksi.
// =========================================================================
$pengguna_id_for_transaction = null;
$query_admin_id = mysqli_query($con, "SELECT idpengguna FROM pengguna WHERE pengguna_level = 'Admin' LIMIT 1");

if ($query_admin_id && mysqli_num_rows($query_admin_id) > 0) {
    $admin_data = mysqli_fetch_assoc($query_admin_id);
    $pengguna_id_for_transaction = $admin_data['idpengguna'];
} else {
    // Jika tidak ada admin, proses dihentikan.
    $_SESSION['error'] = 'Pengguna dengan level Admin tidak ditemukan. Harap tambahkan setidaknya satu Admin.';
    header('Location: ' . $redirect_url);
    exit();
}
// =========================================================================


// Handle HAPUS data (Logika ini tetap sama dan valid)
if (isset($_GET['act']) && isset($_GET['id'])) {
    $action = decrypt($_GET['act']);
    $id = decrypt($_GET['id']);

    if ($action == 'delete') {
        $idtransaksi = mysqli_real_escape_string($con, $id);
        // Dengan ON DELETE CASCADE, item-item di `transaksi_items` akan otomatis terhapus.
        $delete_query = mysqli_query($con, "DELETE FROM transaksi WHERE idtransaksi = '$idtransaksi'");

        if ($delete_query) {
            $_SESSION['success'] = 'Data transaksi berhasil dihapus.';
        } else {
            $_SESSION['error'] = 'Gagal menghapus data transaksi: ' . mysqli_error($con);
        }
        header('Location: ' . $redirect_url);
        exit();
    }
}


// Handle SIMPAN dan UBAH data dari form multi-item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act'])) {
    $act = decrypt($_POST['act']);

    // =========================================================================
    // ## LOGIKA BARU UNTUK SIMPAN TRANSAKSI (SAVE) ##
    // =========================================================================
    if ($act == 'save_transaksi') {
        $transaksi_no = mysqli_real_escape_string($con, $_POST['transaksi_no']);
        $transaksi_tgl = mysqli_real_escape_string($con, $_POST['transaksi_tgl']);
        $transaksi_nama = mysqli_real_escape_string($con, $_POST['transaksi_nama']);

        // Memulai Database Transaction untuk memastikan semua query berhasil
        mysqli_begin_transaction($con);

        try {
            // 1. Simpan data ke tabel master `transaksi` dengan total harga sementara 0
            $sql_transaksi = "INSERT INTO transaksi (transaksi_no, transaksi_tgl, transaksi_nama, pengguna_id, transaksi_total_harga) VALUES ('$transaksi_no', '$transaksi_tgl', '$transaksi_nama', '$pengguna_id_for_transaction', 0)";
            $query_transaksi = mysqli_query($con, $sql_transaksi);

            if (!$query_transaksi) {
                throw new Exception("Gagal menyimpan data transaksi utama.");
            }

            $id_transaksi_baru = mysqli_insert_id($con);
            $grand_total = 0;

            // 2. Cek dan proses item-item dari keranjang
            if (!isset($_POST['items']) || empty($_POST['items']['jasa_id'])) {
                throw new Exception("Keranjang tidak boleh kosong.");
            }

            $items = $_POST['items'];
            for ($i = 0; $i < count($items['jasa_id']); $i++) {
                $jasa_id = mysqli_real_escape_string($con, $items['jasa_id'][$i]);
                $harga = mysqli_real_escape_string($con, $items['harga'][$i]);
                $jumlah = mysqli_real_escape_string($con, $items['jumlah'][$i]);
                $subtotal = (float)$harga * (int)$jumlah;
                $grand_total += $subtotal;

                $sql_items = "INSERT INTO transaksi_items (transaksi_id, jasa_id, harga, jumlah, subtotal) VALUES ('$id_transaksi_baru', '$jasa_id', '$harga', '$jumlah', '$subtotal')";
                $query_items = mysqli_query($con, $sql_items);

                if (!$query_items) {
                    throw new Exception("Gagal menyimpan salah satu item jasa.");
                }
            }

            // 3. Update `transaksi_total_harga` di tabel master dengan grand total final
            $sql_update_total = "UPDATE transaksi SET transaksi_total_harga = '$grand_total' WHERE idtransaksi = '$id_transaksi_baru'";
            $query_update_total = mysqli_query($con, $sql_update_total);

            if (!$query_update_total) {
                throw new Exception("Gagal memperbarui total harga transaksi.");
            }

            // Jika semua query berhasil, konfirmasi perubahan
            mysqli_commit($con);
            $_SESSION['success'] = "Transaksi berhasil disimpan.";
        } catch (Exception $e) {
            // Jika ada satu saja error, batalkan semua perubahan
            mysqli_rollback($con);
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }

        header('Location: ' . $redirect_url);
        exit();
    }

    // =========================================================================
    // ## LOGIKA BARU UNTUK UBAH TRANSAKSI (UPDATE) ##
    // =========================================================================
    if ($act == 'update_transaksi') {
        $id_transaksi = decrypt($_POST['idtransaksi']);
        $transaksi_tgl = mysqli_real_escape_string($con, $_POST['transaksi_tgl']);
        $transaksi_nama = mysqli_real_escape_string($con, $_POST['transaksi_nama']);

        mysqli_begin_transaction($con);
        try {
            // 1. Update data master (nama pelanggan & tanggal)
            $sql_update_master = "UPDATE transaksi SET transaksi_tgl='$transaksi_tgl', transaksi_nama='$transaksi_nama' WHERE idtransaksi='$id_transaksi'";
            mysqli_query($con, $sql_update_master);

            // 2. Hapus semua item lama yang terkait dengan transaksi ini
            mysqli_query($con, "DELETE FROM transaksi_items WHERE transaksi_id='$id_transaksi'");

            // 3. Simpan kembali semua item baru dari form (logikanya sama seperti 'save')
            $grand_total = 0;
            if (!isset($_POST['items']) || empty($_POST['items']['jasa_id'])) {
                throw new Exception("Keranjang tidak boleh kosong saat update.");
            }

            $items = $_POST['items'];
            for ($i = 0; $i < count($items['jasa_id']); $i++) {
                $jasa_id = mysqli_real_escape_string($con, $items['jasa_id'][$i]);
                $harga = mysqli_real_escape_string($con, $items['harga'][$i]);
                $jumlah = mysqli_real_escape_string($con, $items['jumlah'][$i]);
                $subtotal = (float)$harga * (int)$jumlah;
                $grand_total += $subtotal;

                $sql_items = "INSERT INTO transaksi_items (transaksi_id, jasa_id, harga, jumlah, subtotal) VALUES ('$id_transaksi', '$jasa_id', '$harga', '$jumlah', '$subtotal')";
                if (!mysqli_query($con, $sql_items)) {
                    throw new Exception("Gagal menyimpan item jasa baru.");
                }
            }

            // 4. Update grand total di tabel master
            mysqli_query($con, "UPDATE transaksi SET transaksi_total_harga = '$grand_total' WHERE idtransaksi = '$id_transaksi'");

            mysqli_commit($con);
            $_SESSION['success'] = "Transaksi berhasil diperbarui.";
        } catch (Exception $e) {
            mysqli_rollback($con);
            $_SESSION['error'] = "Terjadi kesalahan saat update: " . $e->getMessage();
        }

        header('Location: ' . $redirect_url);
        exit();
    }
}

// Fallback jika ada permintaan yang tidak sesuai format
$_SESSION['error'] = 'Permintaan tidak valid.';
header('Location: ' . $redirect_url);
exit();
