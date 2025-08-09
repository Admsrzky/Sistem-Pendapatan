<?php
$hostname   = "localhost";
$username   = "root";
$password   = "";
$database   = "rekam_jasa";

$con = mysqli_connect($hostname, $username, $password, $database) or die(mysqli_connect_error());

// ====================================================================
// ++ TAMBAHKAN DUA BARIS INI DI BAWAH ++
// ====================================================================

// KUNCI UNTUK ENKRIPSI - JANGAN DIUBAH JIKA SUDAH ADA DATA
define('ENCRYPTION_KEY', 'ganti-dengan-kunci-rahasia-anda-yang-panjang-dan-acak-32-karakter');
define('ENCRYPTION_IV', substr(hash('sha256', 'ganti-dengan-iv-rahasia-yang-berbeda-dari-kunci'), 0, 16));
