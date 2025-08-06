<?php
// PASTIKAN session_start() sudah dipanggil di bagian paling atas file index.php atau file yang memuat function.php ini.

// Fungsi ini seharusnya tidak perlu 'include conn.php' jika $base_url sudah global atau didefinisikan di luar.
// Jika $base_url belum global, Anda harus mendefinisikannya sebelum memanggil session_timeout().
// Misalnya, di index.php:
// $base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);
// Kemudian: session_timeout($base_url); atau ubah fungsi ini untuk memanggil base_url()
function session_timeout()
{
    // Inisialisasi base_url jika belum ada (alternatif, lebih baik definisikan global di index.php)
    // global $base_url; // uncomment jika $base_url adalah global variable
    if (!function_exists('base_url')) { // Fallback jika base_url() belum didefinisikan
        function base_url()
        {
            $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
            $url .= "://" . $_SERVER['HTTP_HOST'];
            $url .= str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
            return $url;
        }
    }

    //lama waktu 30 menit = 1800
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
        session_unset();
        session_destroy();
        // Pastikan redirect menggunakan base_url() yang benar
        header("Location: " . base_url() . "login.php");
        exit(); // Penting: hentikan eksekusi setelah redirect
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

function delMask($str)
{
    // Lebih robust untuk menghapus semua karakter non-digit kecuali tanda minus di awal
    return (int) preg_replace('/[^0-9-]/', '', $str);
}

function hakAkses(array $a)
{
    // Pastikan session 'level' sudah ada sebelum diakses
    if (!isset($_SESSION['level'])) {
        // Jika level tidak ada, anggap tidak punya akses dan redirect ke login atau halaman default
        echo '<script>window.location = "' . base_url() . 'login.php";</script>';
        exit();
    }

    $akses = $_SESSION['level'];
    if (!in_array($akses, $a)) {
        echo '<script>window.location = "?#";</script>'; // Atau redirect ke halaman unauthorized
        exit(); // Penting: hentikan eksekusi setelah redirect
    }
}

// =========================================================================
// Perbaikan Fungsi noTransaksi()
// PENTING: Fungsi ini memerlukan variabel koneksi $con yang sudah terinisialisasi
// di scope global (misalnya dari include 'config/conn.php' di index.php).
// =========================================================================
function noTransaksi()
{
    global $con; // Gunakan variabel koneksi global

    // Pastikan koneksi $con sudah ada dan valid
    if (!$con) {
        // Handle error: koneksi database tidak tersedia
        // Dalam lingkungan produksi, lebih baik log error daripada menampilkan langsung
        error_log("noTransaksi() ERROR: Database connection (global \$con) is not available.");
        return "ERROR_TRX_GEN"; // Atau nilai default yang menunjukkan kegagalan
    }

    // Ambil nomor transaksi terakhir dari database
    // Gunakan CAST dan SUBSTRING untuk pengurutan numerik yang akurat
    $query = mysqli_query($con, "SELECT transaksi_no FROM transaksi ORDER BY CAST(SUBSTRING(transaksi_no, 4) AS UNSIGNED) DESC LIMIT 1");

    if (!$query) {
        error_log("noTransaksi() ERROR: Query failed: " . mysqli_error($con));
        return "ERROR_TRX_GEN";
    }

    $data = mysqli_fetch_assoc($query); // Gunakan mysqli_fetch_assoc lebih modern

    $last_no = $data ? $data['transaksi_no'] : null;

    if ($last_no) {
        // Ambil bagian angka dari nomor transaksi terakhir (setelah 'TRX')
        // Pastikan untuk menangani kasus di mana string mungkin lebih pendek dari yang diharapkan
        $numeric_part = substr($last_no, 3);
        $last_number = (int) $numeric_part; // Konversi ke integer
        $next_number = $last_number + 1;
    } else {
        // Jika belum ada transaksi, mulai dari 1
        $next_number = 1;
    }

    // Format angka menjadi 5 digit dengan leading zeros
    // Contoh: 1 menjadi 00001, 12 menjadi 00012
    $formatted_number = sprintf('%05d', $next_number);

    // Gabungkan dengan prefix 'TRX'
    return 'TRX' . $formatted_number;
}

// =========================================================================
// Fungsi list_* - perbaiki agar tidak meng-include conn.php berulang-ulang
// =========================================================================

function list_jasa()
{
    global $con; // Gunakan variabel koneksi global
    if (!$con) {
        error_log("list_jasa() ERROR: Database connection is not available.");
        return "";
    }

    $query = mysqli_query($con, "SELECT * FROM jasa ORDER BY jasa_nama ASC");
    if (!$query) {
        error_log("list_jasa() ERROR: Query failed: " . mysqli_error($con));
        return "";
    }

    $opt = "";
    while ($row = mysqli_fetch_array($query)) {
        $opt .= "<option value=\"" . htmlspecialchars($row['idjasa']) . "\">" . htmlspecialchars($row['jasa_nama']) . " - Rp. " . number_format($row['jasa_harga'], 0, '', '.') . "</option>";
    }
    return $opt;
}

function list_guru()
{
    global $con; // Gunakan variabel koneksi global
    if (!$con) {
        error_log("list_guru() ERROR: Database connection is not available.");
        return "";
    }

    $query = mysqli_query($con, "SELECT * FROM guru ORDER BY guru_nama ASC");
    if (!$query) {
        error_log("list_guru() ERROR: Query failed: " . mysqli_error($con));
        return "";
    }

    $opt = "";
    while ($row = mysqli_fetch_array($query)) {
        $opt .= "<option value=\"" . htmlspecialchars($row['idguru']) . "\">" . htmlspecialchars($row['guru_nip']) . " - " . htmlspecialchars($row['guru_nama']) . "</option>";
    }
    return $opt;
}

function list_tapel()
{
    global $con; // Gunakan variabel koneksi global
    if (!$con) {
        error_log("list_tapel() ERROR: Database connection is not available.");
        return "";
    }

    $query = mysqli_query($con, "SELECT * FROM tahun_pelajaran ORDER BY idtahun_pelajaran DESC");
    if (!$query) {
        error_log("list_tapel() ERROR: Query failed: " . mysqli_error($con));
        return "";
    }

    $opt = "";
    while ($row = mysqli_fetch_array($query)) {
        $opt .= "<option value=\"" . htmlspecialchars($row['idtahun_pelajaran']) . "\">" . htmlspecialchars($row['nama_tapel']) . "/" . htmlspecialchars($row['semester_tapel']) . "</option>";
    }
    return $opt;
}

function list_mapel()
{
    global $con; // Gunakan variabel koneksi global
    if (!$con) {
        error_log("list_mapel() ERROR: Database connection is not available.");
        return "";
    }

    $mapel = mysqli_query($con, "SELECT * FROM mata_pelajaran x JOIN kelas x1 ON x.kelas_id=x1.idkelas JOIN guru x2 ON x.guru_id=x2.idguru ORDER BY mapel_nama ASC");
    if (!$mapel) {
        error_log("list_mapel() ERROR: Query failed: " . mysqli_error($con));
        return "";
    }

    $opt = "";
    while ($row2 = mysqli_fetch_array($mapel)) {
        $opt .= "<option value=\"" . htmlspecialchars($row2['idmata_pelajaran']) . "\">" . htmlspecialchars($row2['mapel_kode']) . " - " . htmlspecialchars($row2['mapel_nama']) . " - " . htmlspecialchars($row2['kelas_kode']) . "</option>";
    }
    return $opt;
}

function list_mapel_by_guru()
{
    global $con; // Gunakan variabel koneksi global
    if (!$con) {
        error_log("list_mapel_by_guru() ERROR: Database connection is not available.");
        return "";
    }
    if (!isset($_SESSION['username'])) {
        return "";
    } // Pastikan username di sesi ada

    $username = mysqli_real_escape_string($con, $_SESSION['username']);
    $guru_query = mysqli_query($con, "SELECT idguru FROM guru WHERE guru_nip='" . $username . "'");

    if (!$guru_query) {
        error_log("list_mapel_by_guru() ERROR: Guru query failed: " . mysqli_error($con));
        return "";
    }
    if (mysqli_num_rows($guru_query) == 0) {
        return "";
    } // Tidak ada guru dengan NIP ini

    $guru = mysqli_fetch_array($guru_query);

    $mapel_query = mysqli_query($con, "SELECT * FROM mata_pelajaran x JOIN kelas x1 ON x.kelas_id=x1.idkelas JOIN guru x2 ON x.guru_id=x2.idguru WHERE x2.idguru='" . $guru['idguru'] . "' ORDER BY mapel_nama ASC");

    if (!$mapel_query) {
        error_log("list_mapel_by_guru() ERROR: Mapel query failed: " . mysqli_error($con));
        return "";
    }

    $opt = "";
    while ($row2 = mysqli_fetch_array($mapel_query)) {
        $opt .= "<option value=\"" . htmlspecialchars($row2['idmata_pelajaran']) . "\">" . htmlspecialchars($row2['mapel_kode']) . " - " . htmlspecialchars($row2['mapel_nama']) . " - " . htmlspecialchars($row2['kelas_kode']) . "</option>";
    }
    return $opt;
}

function encrypt($str)
{
    return base64_encode($str);
}

function decrypt($str)
{
    return base64_decode($str);
}

function base_url()
{
    $base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    $base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
    return $base_url;
}
