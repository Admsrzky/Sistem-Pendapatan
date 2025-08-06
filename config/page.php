<?php
if (isset($_GET['backup_app'])) {
    include('proses/backup_app.php');
} else if (isset($_GET['backup_db'])) {
    include('proses/backup_db.php');
} else if (isset($_GET['jenis_jasa'])) {
    $master = $jenis_jasa = true;
    $views = 'views/master/jenis_jasa.php';
} else if (isset($_GET['pengguna'])) {
    $master = $pengguna = true;
    $views = 'views/master/pengguna.php';
} else if (isset($_GET['transaksi'])) {
    $transaksi = true;
    $views = 'views/transaksi.php';
} else if (isset($_GET['laporan'])) { // THIS IS THE NEW BLOCK TO ADD
    $laporan = true; // This sets the variable to make the 'Laporan' sidebar link active
    $views = 'views/laporan.php'; // This tells the system to load your 'laporan.php' file
} else {
    $home = true;
    $views = 'views/home.php';
}
