<?php
// Tampilkan semua error untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai session di awal
session_start();

// Sertakan file koneksi dan fungsi
include('../config/conn.php');
include('../config/function.php'); // Untuk fungsi encrypt() dan decrypt()

// Pastikan variabel koneksi $con tersedia
if (!isset($con) || !$con) {
    // Jika ini adalah permintaan AJAX, kirim error dalam format JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Koneksi database gagal.']);
    } else {
        // Jika bukan AJAX, set session error dan redirect
        $_SESSION['error'] = 'Koneksi database gagal.';
        header('Location: ../index.php?pengguna');
    }
    exit();
}

// Definisikan URL redirect yang akan digunakan di seluruh file
$redirect_url = '../index.php?pengguna';

// =================================================================
// TANGANI PERMINTAAN GET (UNTUK HAPUS DATA)
// =================================================================
if (isset($_GET['act']) && isset($_GET['id'])) {
    $action = decrypt($_GET['act']);
    $id = decrypt($_GET['id']);

    if ($action == 'delete') {
        $idpengguna = mysqli_real_escape_string($con, $id);

        // Query untuk menghapus pengguna
        $delete_query = mysqli_query($con, "DELETE FROM pengguna WHERE idpengguna = '$idpengguna'");

        if ($delete_query) {
            $_SESSION['success'] = 'Data pengguna berhasil dihapus.';
        } else {
            $_SESSION['error'] = 'Gagal menghapus data pengguna: ' . mysqli_error($con);
        }
        // Redirect kembali ke halaman daftar pengguna
        header('Location: ' . $redirect_url);
        exit();
    }
}

// =================================================================
// TANGANI PERMINTAAN POST (UNTUK TAMBAH, UBAH, DAN AMBIL DATA EDIT)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. LOGIKA UNTUK MENGAMBIL DATA (AJAX UNTUK MODAL EDIT)
    // Kondisi ini berjalan ketika 'id' dikirim, tetapi bukan dari form tambah/ubah
    if (isset($_POST['id']) && !empty($_POST['id']) && !isset($_POST['tambah']) && !isset($_POST['ubah'])) {
        $idpengguna = mysqli_real_escape_string($con, $_POST['id']);

        // Query untuk mengambil data pengguna berdasarkan ID
        $query = mysqli_query($con, "SELECT idpengguna, pengguna_username, pengguna_nama, pengguna_level FROM pengguna WHERE idpengguna = '$idpengguna'");

        header('Content-Type: application/json'); // Set header JSON

        if ($query && mysqli_num_rows($query) > 0) {
            $data = mysqli_fetch_assoc($query);
            echo json_encode($data); // Kirim data sebagai JSON
        } else {
            echo json_encode(['error' => 'Data pengguna tidak ditemukan.']);
        }
        exit(); // Hentikan eksekusi setelah mengirim data JSON
    }

    // 2. LOGIKA UNTUK TAMBAH ATAU UBAH DATA (DARI SUBMIT FORM MODAL)
    if (isset($_POST['tambah']) || isset($_POST['ubah'])) {
        // Amankan semua input
        $nama = mysqli_real_escape_string($con, $_POST['nama']);
        $username = mysqli_real_escape_string($con, $_POST['username']);
        $level = mysqli_real_escape_string($con, $_POST['level']);

        // Jika ini adalah operasi TAMBAH
        if (isset($_POST['tambah'])) {
            $password = $_POST['password'];

            // Validasi: Pastikan username belum ada
            $checkUser = mysqli_query($con, "SELECT pengguna_username FROM pengguna WHERE pengguna_username = '$username'");
            if (mysqli_num_rows($checkUser) > 0) {
                $_SESSION['error'] = 'Username sudah terdaftar. Silakan gunakan username lain.';
                header('Location: ' . $redirect_url);
                exit();
            }

            // Hash password sebelum disimpan
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $insert_query = "INSERT INTO pengguna (pengguna_nama, pengguna_username, pengguna_password, pengguna_level) VALUES ('$nama', '$username', '$hashedPassword', '$level')";

            if (mysqli_query($con, $insert_query)) {
                $_SESSION['success'] = 'Pengguna baru berhasil ditambahkan.';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan pengguna: ' . mysqli_error($con);
            }

            // Jika ini adalah operasi UBAH
        } elseif (isset($_POST['ubah'])) {
            $idpengguna = mysqli_real_escape_string($con, $_POST['id']);
            $password = $_POST['password'];

            // Cek apakah password diisi atau tidak
            if (!empty($password)) {
                // Jika diisi, hash password baru dan update semua
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $update_query = "UPDATE pengguna SET pengguna_nama = '$nama', pengguna_password = '$hashedPassword', pengguna_level = '$level' WHERE idpengguna = '$idpengguna'";
            } else {
                // Jika password kosong, hanya update data selain password
                $update_query = "UPDATE pengguna SET pengguna_nama = '$nama', pengguna_level = '$level' WHERE idpengguna = '$idpengguna'";
            }

            if (mysqli_query($con, $update_query)) {
                $_SESSION['success'] = 'Data pengguna berhasil diubah.';
            } else {
                $_SESSION['error'] = 'Gagal mengubah data pengguna: ' . mysqli_error($con);
            }
        }

        // Redirect kembali setelah proses tambah/ubah selesai
        header('Location: ' . $redirect_url);
        exit();
    }
}

// =================================================================
// TANGANI AKSES TIDAK VALID
// =================================================================
// Blok ini akan berjalan jika file diakses langsung atau dengan metode yang tidak sesuai
$_SESSION['error'] = 'Permintaan tidak valid.';
header('Location: ' . $redirect_url);
exit();
