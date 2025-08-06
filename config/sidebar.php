<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="?Beranda">
        <div class="sidebar-brand-text mx-2">FotoCopy Maulana</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Beranda (Visible to All) -->
    <li class="nav-item <?= isset($home) ? 'active' : ''; ?>">
        <a class="nav-link" href="?Beranda">
            <i class="fas fa-fw fa-home"></i>
            <span>Beranda</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Menu
    </div>

    <!-- Menu KHUSUS untuk ADMIN -->
    <?php if ($_SESSION['level'] == 'Admin'): ?>
        <li class="nav-item <?= isset($jenis_jasa) ? 'active' : ''; ?>">
            <a class="nav-link" href="?jenis_jasa">
                <i class="fas fa-fw fa-book"></i>
                <span>Jenis Jasa</span>
            </a>
        </li>
        <!-- <li class="nav-item <?= isset($pengguna) ? 'active' : ''; ?>">
            <a class="nav-link" href="?pengguna">
                <i class="fas fa-fw fa-users"></i>
                <span>Pengguna</span>
            </a>
        </li> -->
    <?php endif; ?>

    <!-- Menu untuk ADMIN dan USER -->
    <?php if ($_SESSION['level'] == 'Admin' || $_SESSION['level'] == 'User'): ?>
        <li class="nav-item <?= isset($transaksi) ? 'active' : ''; ?>">
            <a class="nav-link" href="?transaksi">
                <i class="fas fa-fw fa-cash-register"></i>
                <span>Transaksi Penjualan</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Menu Laporan KHUSUS untuk ADMIN -->
    <?php if ($_SESSION['level'] == 'Admin'): ?>
        <li class="nav-item <?= isset($laporan) ? 'active' : ''; ?>">
            <a class="nav-link" href="?laporan">
                <i class="fas fa-fw fa-file-alt"></i> <span>Laporan Pendapatan</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->