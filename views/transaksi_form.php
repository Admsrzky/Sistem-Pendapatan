<?php
// Pastikan file ini hanya bisa diakses setelah login dan punya hak
include_once('config/conn.php');
include_once('config/function.php');
hakAkses(['Admin', 'User']);

// Logika untuk mode EDIT
$is_edit = false;
$data_transaksi = null;
$data_items = [];
if (isset($_GET['id'])) {
    $is_edit = true;
    $id_transaksi = decrypt($_GET['id']);
    // Ambil data header transaksi
    $query_transaksi = mysqli_query($con, "SELECT * FROM transaksi WHERE idtransaksi = '$id_transaksi'");
    $data_transaksi = mysqli_fetch_assoc($query_transaksi);
    // Ambil data item-itemnya
    // PERBAIKAN 1: Mengubah kondisi JOIN dari ti.idjasa menjadi ti.jasa_id
    $query_items = mysqli_query($con, "SELECT ti.*, j.jasa_nama FROM transaksi_items ti JOIN jasa j ON ti.jasa_id = j.idjasa WHERE ti.transaksi_id = '$id_transaksi'");
    while ($item = mysqli_fetch_assoc($query_items)) {
        $data_items[] = $item;
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $is_edit ? 'Edit' : 'Tambah' ?> Transaksi Penjualan</h1>
        <a href="?transaksi" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <div class="card shadow mb-4">
        <form id="formTransaksi" action="<?= base_url(); ?>process/transaksi.php" method="POST">
            <input type="hidden" name="act"
                value="<?= $is_edit ? encrypt('update_transaksi') : encrypt('save_transaksi'); ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="idtransaksi" value="<?= encrypt($data_transaksi['idtransaksi']); ?>">
            <?php endif; ?>

            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Informasi Pelanggan</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>No. Transaksi</label>
                        <input type="text" name="transaksi_no" class="form-control" readonly
                            value="<?= $is_edit ? $data_transaksi['transaksi_no'] : noTransaksi($con); ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Tanggal Transaksi</label>
                        <input type="date" name="transaksi_tgl" class="form-control"
                            value="<?= $is_edit ? $data_transaksi['transaksi_tgl'] : date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="transaksi_nama" class="form-control" required
                            value="<?= $is_edit ? htmlspecialchars($data_transaksi['transaksi_nama']) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="card-header py-3 mt-3">
                <h6 class="m-0 font-weight-bold text-primary">Item Jasa</h6>
            </div>
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label>Pilih Jasa</label>
                        <select id="pilih_jasa" class="form-control select2" style="width:100%;">
                            <option value="">-- Pilih Jasa --</option>
                            <?= list_jasa($con); ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Harga Satuan</label>
                        <input type="text" id="harga_jasa" class="form-control" readonly placeholder="Otomatis">
                    </div>
                    <div class="col-md-2">
                        <label>Jumlah</label>
                        <input type="number" id="jumlah_item" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btn_add_item" class="btn btn-primary btn-block"><i
                                class="fas fa-plus"></i> Tambah</button>
                    </div>
                </div>

                <hr>
                <h6 class="font-weight-bold">Keranjang Jasa</h6>
                <div class="table-responsive">
                    <table class="table table-bordered" id="keranjang_jasa">
                        <thead>
                            <tr>
                                <th>Nama Jasa</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                                <th width="50">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($is_edit): foreach ($data_items as $item): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[jasa_id][]" value="<?= $item['jasa_id']; ?>">
                                            <input type="hidden" name="items[harga][]" value="<?= $item['harga']; ?>">
                                            <input type="hidden" name="items[jumlah][]" value="<?= $item['jumlah']; ?>">
                                            <?= htmlspecialchars($item['jasa_nama']); ?>
                                        </td>
                                        <td><?= 'Rp ' . number_format($item['harga'], 0, ',', '.'); ?></td>
                                        <td><?= $item['jumlah']; ?></td>
                                        <td><?= 'Rp ' . number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                        <td><button type="button" class="btn btn-danger btn-sm btn-hapus-item"><i
                                                    class="fas fa-trash"></i></button></td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">GRAND TOTAL</th>
                                <th id="grand_total" colspan="2">Rp 0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Pesan ini akan muncul di Console jika jQuery berjalan dengan benar.
        console.log('Halaman siap. Script transaksi_form.php dijalankan.');

        // Inisialisasi Select2
        $('#pilih_jasa').select2({
            placeholder: '-- Pilih Jasa --',
            allowClear: true
        });

        // =================================================================
        // DIUBAH TOTAL: Menggunakan event khusus dari Select2 ('select2:select')
        // =================================================================
        $('#pilih_jasa').on('select2:select', function(e) {
            // Mengambil elemen <option> yang asli dari data event
            var element = e.params.data.element;
            // Mengambil harga dari atribut 'data-harga'
            var harga = $(element).data('harga');

            // Debugging: Tampilkan harga yang didapat di Console
            console.log('Jasa dipilih. Harga dari atribut data-harga:', harga);

            // Masukkan harga ke field input
            $('#harga_jasa').val(harga);
        });

        // Event saat pilihan jasa dihapus (tombol 'x' diklik)
        $('#pilih_jasa').on('select2:unselect', function(e) {
            $('#harga_jasa').val('');
            console.log('Pilihan jasa dibersihkan.');
        });


        // Event untuk tombol "Tambah Item"
        $('#btn_add_item').on('click', function() {
            var jasa_id = $('#pilih_jasa').val();
            var jasa_nama = $('#pilih_jasa option:selected').text();
            var harga = $('#harga_jasa').val();
            var jumlah = $('#jumlah_item').val();

            // Debugging: Tampilkan semua nilai sebelum validasi
            console.log('Tombol "+ Tambah" diklik. Data yang akan divalidasi:', {
                jasa_id: jasa_id,
                jasa_nama: jasa_nama,
                harga: harga,
                jumlah: jumlah
            });

            if (!jasa_id || !harga || !jumlah || parseInt(jumlah) < 1) {
                console.log('Validasi GAGAL. Data tidak lengkap.');
                Swal.fire('Peringatan', 'Pastikan Anda sudah memilih jasa dan mengisi jumlah dengan benar.',
                    'warning');
                return;
            }

            console.log('Validasi BERHASIL. Menambahkan item ke keranjang.');

            var subtotal = parseFloat(harga) * parseInt(jumlah);
            var subtotal_formatted = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(subtotal);
            var harga_formatted = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(harga);

            var newRow = `
            <tr>
                <td>
                    <input type="hidden" name="items[jasa_id][]" value="${jasa_id}">
                    <input type="hidden" name="items[harga][]" value="${harga}">
                    <input type="hidden" name="items[jumlah][]" value="${jumlah}">
                    ${jasa_nama}
                </td>
                <td>${harga_formatted}</td>
                <td>${jumlah}</td>
                <td>${subtotal_formatted}</td>
                <td><button type="button" class="btn btn-danger btn-sm btn-hapus-item"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
            $('#keranjang_jasa tbody').append(newRow);

            $('#pilih_jasa').val(null).trigger('change'); // Cara reset Select2 yang benar
            $('#harga_jasa').val('');
            $('#jumlah_item').val(1);

            updateGrandTotal();
        });

        // Sisa fungsi lainnya tetap sama
        $('#keranjang_jasa').on('click', '.btn-hapus-item', function() {
            $(this).closest('tr').remove();
            updateGrandTotal();
        });

        function updateGrandTotal() {
            var total = 0;
            $('#keranjang_jasa tbody tr').each(function() {
                var harga = parseFloat($(this).find('input[name="items[harga][]"]').val());
                var jumlah = parseInt($(this).find('input[name="items[jumlah][]"]').val());
                total += harga * jumlah;
            });
            var total_formatted = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(total);
            $('#grand_total').text(total_formatted);
        }

        updateGrandTotal();

        $('#formTransaksi').on('submit', function(e) {
            if ($('#keranjang_jasa tbody tr').length === 0) {
                e.preventDefault();
                Swal.fire('Error',
                    'Keranjang tidak boleh kosong. Silakan tambahkan minimal satu item jasa.', 'error');
            }
        });
    });
</script>