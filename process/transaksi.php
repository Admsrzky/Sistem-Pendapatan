<?php
error_reporting(E_ALL); // Tampilkan semua error PHP
ini_set('display_errors', 1); // Pastikan error ditampilkan di browser

session_start();

// Pastikan path ke file koneksi Anda benar!
include('../config/conn.php');
include('../config/function.php'); // Include your function.php for encrypt/decrypt

// Pastikan $con (variabel koneksi) tersedia setelah include conn.php
if (!isset($con) || !$con) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Koneksi database gagal diinisialisasi.']);
    } else {
        $_SESSION['error'] = 'Koneksi database gagal diinisialisasi.';
        header('Location: ../index.php?page=transaksi'); // Sesuaikan redirect
    }
    exit();
}

// Definisikan URL redirect yang konsisten
$redirect_url = '../index.php?transaksi'; // Mengarah ke index.php dengan parameter jenis_jasa

// =========================================================================
// Perubahan di sini: Mengambil idpengguna dari pengguna dengan level 'Admin'
// =========================================================================
$pengguna_id_for_transaction = null;
$query_admin_id = mysqli_query($con, "SELECT idpengguna FROM pengguna WHERE pengguna_level = 'Admin' LIMIT 1");

if ($query_admin_id && mysqli_num_rows($query_admin_id) > 0) {
    $admin_data = mysqli_fetch_assoc($query_admin_id);
    $pengguna_id_for_transaction = $admin_data['idpengguna'];
} else {
    // Jika tidak ada admin ditemukan, berikan pesan error
    $_SESSION['error'] = 'Pengguna dengan level Admin tidak ditemukan untuk mencatat transaksi. Harap tambahkan setidaknya satu Admin.';
    header('Location: ' . $redirect_url);
    exit();
}
// =========================================================================


// Handle GET requests for delete operations
if (isset($_GET['act']) && isset($_GET['id'])) {
    $action = decrypt($_GET['act']);
    $id = decrypt($_GET['id']);

    if ($action == 'delete') {
        $idtransaksi = mysqli_real_escape_string($con, $id);
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

// Handle POST requests for add, update, and data retrieval for edit modal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Logic for fetching data for the edit modal (AJAX request from submit(x) where x is id)
    if (isset($_POST['id']) && !empty($_POST['id']) && !isset($_POST['tambah']) && !isset($_POST['ubah'])) {
        $idtransaksi_ajax = mysqli_real_escape_string($con, $_POST['id']);
        $query_ajax = mysqli_query($con, "SELECT * FROM transaksi x JOIN jasa x1 ON x.jasa_id=x1.idjasa WHERE x.idtransaksi = '$idtransaksi_ajax'");

        if (!$query_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Gagal mengeksekusi query database untuk pengambilan data: ' . mysqli_error($con)]);
            exit();
        }

        if (mysqli_num_rows($query_ajax) > 0) {
            $data_ajax = mysqli_fetch_assoc($query_ajax);
            header('Content-Type: application/json');
            echo json_encode($data_ajax);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Data transaksi tidak ditemukan untuk ID ini.']);
        }
        exit(); // Exit after handling AJAX data retrieval
    }


    // Logic for Add or Update (form submission from the modal)
    if (isset($_POST['tambah']) || isset($_POST['ubah'])) {
        $transaksi_no = mysqli_real_escape_string($con, $_POST['transaksi_no']);
        $transaksi_nama = mysqli_real_escape_string($con, $_POST['transaksi_nama']);
        $jasa_id = mysqli_real_escape_string($con, $_POST['jenis_jasa']);

        $transaksi_jumlah = str_replace(['Rp. ', '.'], '', $_POST['transaksi_jumlah']);
        $transaksi_jumlah = mysqli_real_escape_string($con, $transaksi_jumlah);

        // Validasi input dasar
        if (empty($transaksi_no) || empty($transaksi_nama) || empty($jasa_id) || empty($transaksi_jumlah) || !is_numeric(str_replace(['Rp. ', '.'], '', $_POST['transaksi_jumlah']))) {
            $_SESSION['error'] = "Semua field wajib diisi dan Jumlah harus angka.";
            header('Location: ' . $redirect_url);
            exit();
        }

        // Ambil harga jasa dari database
        $query_jasa_harga = mysqli_query($con, "SELECT jasa_harga FROM jasa WHERE idjasa = '$jasa_id'");
        if (!$query_jasa_harga || mysqli_num_rows($query_jasa_harga) == 0) {
            $_SESSION['error'] = "Jenis jasa tidak ditemukan.";
            header('Location: ' . $redirect_url);
            exit();
        }
        $data_jasa = mysqli_fetch_assoc($query_jasa_harga);
        $harga_satuan = $data_jasa['jasa_harga'];

        $transaksi_jumlah_numeric = (int)str_replace(['Rp. ', '.'], '', $_POST['transaksi_jumlah']);
        $transaksi_total_harga_numeric = $harga_satuan * $transaksi_jumlah_numeric;

        $transaksi_total_harga = mysqli_real_escape_string($con, $transaksi_total_harga_numeric);

        $transaksi_tgl = date('Y-m-d');


        if (isset($_POST['tambah'])) {
            // Add (Insert) operation
            // Menggunakan $pengguna_id_for_transaction yang diambil dari level 'Admin'
            $insert_query = "INSERT INTO transaksi (transaksi_no, transaksi_tgl, transaksi_nama, jasa_id, transaksi_jumlah, transaksi_total_harga, pengguna_id) 
                             VALUES ('$transaksi_no', '$transaksi_tgl', '$transaksi_nama', '$jasa_id', '$transaksi_jumlah', '$transaksi_total_harga', '$pengguna_id_for_transaction')";

            if (mysqli_query($con, $insert_query)) {
                $_SESSION['success'] = 'Data transaksi berhasil ditambahkan.';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan data transaksi: ' . mysqli_error($con) . ' Query: ' . $insert_query;
            }
        } elseif (isset($_POST['ubah'])) {
            // Update operation
            $idtransaksi = mysqli_real_escape_string($con, $_POST['idtransaksi']);

            if (empty($idtransaksi)) {
                $_SESSION['error'] = "ID Transaksi tidak ditemukan untuk pembaruan.";
                header('Location: ' . $redirect_url);
                exit();
            }

            $update_query = "UPDATE transaksi SET 
                               transaksi_no = '$transaksi_no', 
                               transaksi_nama = '$transaksi_nama', 
                               jasa_id = '$jasa_id', 
                               transaksi_jumlah = '$transaksi_jumlah', 
                               transaksi_total_harga = '$transaksi_total_harga',
                               pengguna_id = '$pengguna_id_for_transaction' -- Update pengguna_id dengan ID Admin
                             WHERE idtransaksi = '$idtransaksi'";

            if (mysqli_query($con, $update_query)) {
                $_SESSION['success'] = 'Data transaksi berhasil diubah.';
            } else {
                $_SESSION['error'] = 'Gagal mengubah data transaksi: ' . mysqli_error($con) . ' Query: ' . $update_query;
            }
        }
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Fallback for invalid requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    $_SESSION['error'] = 'Permintaan tidak valid.';
    header('Location: ' . $redirect_url);
    exit();
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Permintaan AJAX tidak valid.']);
    exit();
}
