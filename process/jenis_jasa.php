<?php
error_reporting(E_ALL); // Tampilkan semua error PHP
ini_set('display_errors', 1); // Pastikan error ditampilkan di browser

session_start();

// Pastikan path ke file koneksi Anda benar!
include('../config/conn.php');
include('../config/function.php'); // Include your function.php for encrypt/decrypt

// Pastikan $con (variabel koneksi) tersedia setelah include conn.php
if (!isset($con) || !$con) {
    // If it's an AJAX request, send JSON error. Otherwise, redirect or show a user-friendly message.
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Koneksi database gagal diinisialisasi.']);
    } else {
        // For non-AJAX requests, you might want to redirect or display an error page
        $_SESSION['error'] = 'Koneksi database gagal diinisialisasi.';
        // Mengarahkan ke index.php dengan parameter page=jenis_jasa
        header('Location: ../index.php?page=?jenis_jasa');
    }
    exit();
}

// Definisikan URL redirect yang konsisten
// Mengarahkan ke index.php yang akan memuat views/master/jenis_jasa.php
$redirect_url = '../index.php?jenis_jasa'; // Mengarah ke index.php dengan parameter jenis_jasa

// Handle GET requests for delete operations (from the table 'hapus' button)
if (isset($_GET['act']) && isset($_GET['id'])) {
    $action = decrypt($_GET['act']); // Assuming 'decrypt' function is in function.php
    $id = decrypt($_GET['id']); // Assuming 'decrypt' function is in function.php

    if ($action == 'delete') {
        $idjasa = mysqli_real_escape_string($con, $id);
        $delete_query = mysqli_query($con, "DELETE FROM jasa WHERE idjasa = '$idjasa'");

        if ($delete_query) {
            $_SESSION['success'] = 'Data jasa berhasil dihapus.';
        } else {
            $_SESSION['error'] = 'Gagal menghapus data jasa: ' . mysqli_error($con);
        }
        // Redirect kembali ke halaman daftar setelah hapus
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Handle POST requests for add, update, and data retrieval for edit modal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logic for fetching data for the edit modal (AJAX request from submit(x) where x is id)
    if (isset($_POST['id']) && !empty($_POST['id']) && !isset($_POST['tambah']) && !isset($_POST['ubah'])) {
        // Ini adalah permintaan AJAX untuk mendapatkan data edit
        $idjasa = mysqli_real_escape_string($con, $_POST['id']);
        $query = mysqli_query($con, "SELECT idjasa, jasa_nama, jasa_harga FROM jasa WHERE idjasa = '$idjasa'");

        if (!$query) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Gagal mengeksekusi query database: ' . mysqli_error($con)]);
            exit();
        }

        if (mysqli_num_rows($query) > 0) {
            $data = mysqli_fetch_assoc($query);
            header('Content-Type: application/json');
            echo json_encode($data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Data jasa tidak ditemukan untuk ID ini.']);
        }
        exit(); // Exit after handling AJAX data retrieval
    }

    // Logic for Add or Update (form submission from the modal)
    // Check if the 'tambah' or 'ubah' button was clicked
    if (isset($_POST['tambah']) || isset($_POST['ubah'])) {
        $jasa_nama = mysqli_real_escape_string($con, $_POST['jasa_nama']);
        // Remove "Rp. " and dots from jasa_harga before inserting/updating
        $jasa_harga = str_replace(['Rp. ', '.'], '', $_POST['jasa_harga']);
        $jasa_harga = mysqli_real_escape_string($con, $jasa_harga); // Sanitize the numeric value

        if (isset($_POST['tambah'])) {
            // Add (Insert) operation
            $insert_query = "INSERT INTO jasa (jasa_nama, jasa_harga) VALUES ('$jasa_nama', '$jasa_harga')";
            if (mysqli_query($con, $insert_query)) {
                $_SESSION['success'] = 'Data jasa berhasil ditambahkan.';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan data jasa: ' . mysqli_error($con);
            }
        } elseif (isset($_POST['ubah'])) {
            // Update operation
            $idjasa = mysqli_real_escape_string($con, $_POST['idjasa']); // Get the hidden ID for update
            $update_query = "UPDATE jasa SET jasa_nama = '$jasa_nama', jasa_harga = '$jasa_harga' WHERE idjasa = '$idjasa'";
            if (mysqli_query($con, $update_query)) {
                $_SESSION['success'] = 'Data jasa berhasil diubah.';
            } else {
                $_SESSION['error'] = 'Gagal mengubah data jasa: ' . mysqli_error($con);
            }
        }
        // Redirect back to the main page after add/update
        header('Location: ' . $redirect_url);
        exit();
    }
}

// This else block should only be reached if the request is not handled by any of the above
// For example, if it's a direct access to the process file without any parameters.
// If this is reached, it implies an invalid request to the process file.
// Atau, jika ini bukan permintaan AJAX dan bukan POST/GET yang valid
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    $_SESSION['error'] = 'Permintaan tidak valid.';
    header('Location: ' . $redirect_url);
    exit();
} else {
    // Jika ini adalah AJAX tapi tidak ada parameter POST['id'] yang valid,
    // mungkin ada masalah dengan permintaan AJAX
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Permintaan AJAX tidak valid.']);
    exit();
}
