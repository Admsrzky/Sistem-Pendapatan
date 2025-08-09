<?php

/**
 * File fungsi utama untuk aplikasi.
 * Pastikan session_start() sudah dipanggil di file index.php sebelum file ini di-include.
 */

// =========================================================================
// PENTING: Kunci Enkripsi
// Tambahkan dua baris ini di file config/conn.php Anda.
// JANGAN UBAH KUNCI INI SETELAH DATA PRODUKSI MASUK, atau data lama tidak akan bisa di-dekripsi.
/*
    define('ENCRYPTION_KEY', 'ganti-dengan-kunci-rahasia-anda-yang-panjang-dan-acak-32-karakter');
    define('ENCRYPTION_IV', substr(hash('sha256', 'ganti-dengan-iv-rahasia-yang-berbeda-dari-kunci'), 0, 16));
*/
// =========================================================================

/**
 * Membuat base URL secara dinamis.
 * @return string URL dasar aplikasi.
 */
function base_url()
{
    // Untuk keandalan, hardcode URL mungkin lebih baik untuk struktur folder Anda
    // return "http://localhost:8081/Sistem%20Tugas%20Akhir/Sistem%20Informasi%20Pendapatan%20Fotocopy/";

    // Versi dinamis
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $path = ($path == DIRECTORY_SEPARATOR) ? '' : $path;
    return $protocol . $host . $path . '/';
}

/**
 * Mengatur timeout sesi pengguna.
 */
function session_timeout()
{
    // lama waktu 30 menit = 1800 detik
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: " . base_url() . "login.php");
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

/**
 * Menghapus format mata uang dari string.
 * @param string $str String yang akan dibersihkan.
 * @return int Angka integer.
 */
function delMask($str)
{
    return (int) preg_replace('/[^0-9-]/', '', $str);
}

/**
 * Memeriksa hak akses pengguna berdasarkan level di session.
 * @param array $allowed_roles Array berisi level yang diizinkan.
 */
function hakAkses(array $allowed_roles)
{
    if (!isset($_SESSION['level'])) {
        header("Location: " . base_url() . "login.php");
        exit();
    }

    if (!in_array($_SESSION['level'], $allowed_roles)) {
        // Redirect ke halaman utama jika akses ditolak
        header("Location: " . base_url());
        exit();
    }
}

/**
 * Membuat nomor transaksi baru secara otomatis.
 * @param mysqli $con Objek koneksi database.
 * @return string Nomor transaksi baru.
 */
function noTransaksi($con)
{
    if (!$con) {
        error_log("noTransaksi() ERROR: Koneksi database tidak tersedia.");
        return "TRX_ERR_DB";
    }

    $query_str = "SELECT transaksi_no FROM transaksi ORDER BY CAST(SUBSTRING(transaksi_no, 4) AS UNSIGNED) DESC LIMIT 1";
    $query = mysqli_query($con, $query_str);

    if (!$query) {
        error_log("noTransaksi() ERROR: Query gagal: " . mysqli_error($con));
        return "TRX_ERR_Q";
    }

    $data = mysqli_fetch_assoc($query);
    $last_no = $data ? $data['transaksi_no'] : null;

    $next_number = $last_no ? ((int) substr($last_no, 3)) + 1 : 1;

    return 'TRX' . sprintf('%05d', $next_number);
}

/**
 * Membuat daftar <option> untuk semua jasa.
 * @param mysqli $con Objek koneksi database.
 * @return string String HTML berisi tag <option>.
 */
function list_jasa($con)
{
    if (!$con) return "";
    $query = mysqli_query($con, "SELECT * FROM jasa ORDER BY jasa_nama ASC");
    if (!$query) return "";

    $opt = "";
    while ($row = mysqli_fetch_array($query)) {
        $harga_format = number_format($row['jasa_harga'], 0, ',', '.');
        // PERHATIKAN PENAMBAHAN data-harga="..."
        $opt .= "<option value=\"" . htmlspecialchars($row['idjasa']) . "\" data-harga=\"" . htmlspecialchars($row['jasa_harga']) . "\">"
            . htmlspecialchars($row['jasa_nama']) . " - Rp " . $harga_format
            . "</option>";
    }
    return $opt;
}

// ---- Blok fungsi list_* lainnya yang sudah diperbaiki ----

function list_guru($con)
{
    if (!$con) return "";
    $query = mysqli_query($con, "SELECT * FROM guru ORDER BY guru_nama ASC");
    if (!$query) return "";
    $opt = "";
    while ($row = mysqli_fetch_array($query)) {
        $opt .= "<option value=\"" . htmlspecialchars($row['idguru']) . "\">" . htmlspecialchars($row['guru_nip']) . " - " . htmlspecialchars($row['guru_nama']) . "</option>";
    }
    return $opt;
}

function list_tapel($con)
{
    if (!$con) return "";
    $query = mysqli_query($con, "SELECT * FROM tahun_pelajaran ORDER BY idtahun_pelajaran DESC");
    if (!$query) return "";
    $opt = "";
    while ($row = mysqli_fetch_array($query)) {
        $opt .= "<option value=\"" . htmlspecialchars($row['idtahun_pelajaran']) . "\">" . htmlspecialchars($row['nama_tapel']) . "/" . htmlspecialchars($row['semester_tapel']) . "</option>";
    }
    return $opt;
}

function list_mapel($con)
{
    if (!$con) return "";
    $mapel = mysqli_query($con, "SELECT * FROM mata_pelajaran x JOIN kelas x1 ON x.kelas_id=x1.idkelas JOIN guru x2 ON x.guru_id=x2.idguru ORDER BY mapel_nama ASC");
    if (!$mapel) return "";
    $opt = "";
    while ($row2 = mysqli_fetch_array($mapel)) {
        $opt .= "<option value=\"" . htmlspecialchars($row2['idmata_pelajaran']) . "\">" . htmlspecialchars($row2['mapel_kode']) . " - " . htmlspecialchars($row2['mapel_nama']) . " - " . htmlspecialchars($row2['kelas_kode']) . "</option>";
    }
    return $opt;
}

function list_mapel_by_guru($con)
{
    if (!$con || !isset($_SESSION['username'])) return "";
    $username = mysqli_real_escape_string($con, $_SESSION['username']);
    $guru_query = mysqli_query($con, "SELECT idguru FROM guru WHERE guru_nip='" . $username . "'");
    if (!$guru_query || mysqli_num_rows($guru_query) == 0) return "";
    $guru = mysqli_fetch_array($guru_query);
    $mapel_query = mysqli_query($con, "SELECT * FROM mata_pelajaran x JOIN kelas x1 ON x.kelas_id=x1.idkelas JOIN guru x2 ON x.guru_id=x2.idguru WHERE x2.idguru='" . $guru['idguru'] . "' ORDER BY mapel_nama ASC");
    if (!$mapel_query) return "";
    $opt = "";
    while ($row2 = mysqli_fetch_array($mapel_query)) {
        $opt .= "<option value=\"" . htmlspecialchars($row2['idmata_pelajaran']) . "\">" . htmlspecialchars($row2['mapel_kode']) . " - " . htmlspecialchars($row2['mapel_nama']) . " - " . htmlspecialchars($row2['kelas_kode']) . "</option>";
    }
    return $opt;
}

/**
 * Mengenkripsi string menggunakan OpenSSL (AMAN).
 * @param string $string Data yang akan dienkripsi.
 * @return string|null String terenkripsi atau null jika gagal.
 */
function encrypt($string)
{
    if (!defined('ENCRYPTION_KEY') || !defined('ENCRYPTION_IV')) {
        error_log('Kunci enkripsi/IV belum didefinisikan!');
        return null;
    }
    $output = openssl_encrypt($string, 'aes-256-cbc', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
    return strtr(base64_encode($output), '+/=', '-_,');
}

/**
 * Mendekripsi string menggunakan OpenSSL (AMAN).
 * @param string $string Data yang akan didekripsi.
 * @return string|null String asli atau null jika gagal.
 */
function decrypt($string)
{
    if (!defined('ENCRYPTION_KEY') || !defined('ENCRYPTION_IV')) {
        error_log('Kunci enkripsi/IV belum didefinisikan!');
        return null;
    }
    $data = base64_decode(strtr($string, '-_,', '+/='));
    return openssl_decrypt($data, 'aes-256-cbc', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}
