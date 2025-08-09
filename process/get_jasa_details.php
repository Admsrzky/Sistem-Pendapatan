<?php
header('Content-Type: application/json');
include_once('../config/conn.php'); // Sesuaikan path

if (isset($_POST['id'])) {
    $id_jasa = mysqli_real_escape_string($con, $_POST['id']);
    $query = mysqli_query($con, "SELECT jasa_harga FROM jasa WHERE idjasa = '$id_jasa'");
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Jasa tidak ditemukan']);
    }
} else {
    echo json_encode(['error' => 'ID Jasa tidak dikirim']);
}
