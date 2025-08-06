<?php
session_start(); // Pastikan ini adalah baris paling atas

// Sertakan koneksi database
include('config/conn.php');

// Definisikan base_url. Lebih baik ini didefinisikan secara global di file config/function.php
// atau di file bootstrap/initiation utama jika ada.
// Jika Anda sudah memiliki base_url() di config/function.php, Anda bisa menghapus ini
// dan gunakan fungsi base_url() langsung. Untuk saat ini, saya biarkan agar berfungsi.
$base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
$base_url .= "://" . $_SERVER['HTTP_HOST'];
$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);

// Jika pengguna sudah login, arahkan ke halaman utama
if (isset($_SESSION['iduser'])) {
    header("Location: " . $base_url . "index.php"); // Arahkan ke halaman utama aplikasi Anda
    exit();
}

// Proses Login
if (isset($_POST['cek_login'])) {
    $username = trim($_POST['username']); // Trim whitespace
    $password = trim($_POST['password']); // Trim whitespace

    // Gunakan mysqli_real_escape_string untuk mencegah SQL Injection
    $username = mysqli_real_escape_string($con, $username);
    // Password tidak perlu di-escape sebelum password_verify karena tidak langsung ke query

    if (empty($username) || empty($password)) { // Perbaiki validasi kosong
        $_SESSION['error'] = 'Harap isi username dan password.';
    } else {
        $query_user = "SELECT idpengguna, pengguna_username, pengguna_password, pengguna_nama, pengguna_level FROM pengguna WHERE pengguna_username='$username'";
        $result_user = mysqli_query($con, $query_user);

        if (!$result_user) {
            // Error query database
            $_SESSION['error'] = 'Terjadi kesalahan database: ' . mysqli_error($con);
        } elseif (mysqli_num_rows($result_user) > 0) {
            $data = mysqli_fetch_assoc($result_user); // Gunakan mysqli_fetch_assoc untuk akses yang lebih jelas

            // Verifikasi password dengan password_verify()
            if (password_verify($password, $data['pengguna_password'])) {
                // Login Berhasil
                $_SESSION['iduser'] = $data['idpengguna'];
                $_SESSION['username'] = $data['pengguna_username'];
                $_SESSION['fullname'] = $data['pengguna_nama'];
                $_SESSION['level'] = $data['pengguna_level'];
                $_SESSION['LAST_ACTIVITY'] = time(); // Inisialisasi terakhir aktivitas untuk timeout

                // Redirect ke halaman utama setelah login sukses
                header("Location: " . $base_url . "index.php");
                exit();
            } else {
                // Password salah
                $_SESSION['error'] = 'Password Anda salah.';
            }
        } else {
            // Username tidak terdaftar
            $_SESSION['error'] = 'Username tidak terdaftar.';
        }
    }
    // Jika ada error, redirect kembali ke halaman login (agar SweetAlert muncul)
    header("Location: " . $base_url . "login.php"); // Redirect ke diri sendiri
    exit();
}

// Ambil pesan success/error dari session jika ada untuk ditampilkan oleh SweetAlert
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
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SIPUFM - Login</title>
    <link href="<?= $base_url; ?>assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="<?= $base_url; ?>assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="<?= $base_url; ?>assets/vendor/sweet-alert/sweetalert2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-info">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-5 d-none d-lg-block text-center">
                                <img src="<?= $base_url; ?>assets/img/login.png" width="420" height="400">
                            </div>
                            <div class="col-lg-7">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4" style="font-size: 30px;">Sistem Informasi
                                            Pendapatan Usaha Toko Maulana
                                        </h1>
                                    </div>
                                    <form class="user" method="post" action="">
                                        <div class="form-group">
                                            <input type="text" name="username" class="form-control form-control-user"
                                                value="" placeholder="Masukkan username" autofocus>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password"
                                                class="form-control form-control-user" value=""
                                                placeholder="Masukkan password">
                                        </div>
                                        <hr>
                                        <button type="submit" class="btn btn-primary btn-user btn-block"
                                            name="cek_login">Login</button>
                                    </form>
                                    <br>
                                    <hr>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script src="<?= $base_url; ?>assets/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $base_url; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="<?= $base_url; ?>assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= $base_url; ?>assets/vendor/sweet-alert/sweetalert2.all.min.js"></script>

    <script src="<?= $base_url; ?>assets/js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function() {
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

</body>

</html>