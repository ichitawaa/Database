<?php
/**
 * Layout Header & Top Navigation
 */

$flash = getFlash();

// Hitung data statistik langsung dari MySQL
$smPdo = Database::getSupermarket();
$taxPdo = Database::getPerpajakan();

$statProd = $smPdo ? $smPdo->query("SELECT COUNT(*) FROM `produk`")->fetchColumn() : 0;
$statKasir = $smPdo ? $smPdo->query("SELECT COUNT(*) FROM `kasir`")->fetchColumn() : 0;
$statPelanggan = $smPdo ? $smPdo->query("SELECT COUNT(*) FROM `pelanggan`")->fetchColumn() : 0;
$statTrx = $smPdo ? $smPdo->query("SELECT COUNT(*) FROM `transaksi`")->fetchColumn() : 0;

$statKat = $taxPdo ? $taxPdo->query("SELECT COUNT(*) FROM `kategori_pajak`")->fetchColumn() : 0;
$statJenis = $taxPdo ? $taxPdo->query("SELECT COUNT(*) FROM `jenis_pajak`")->fetchColumn() : 0;
$statPengguna = $taxPdo ? $taxPdo->query("SELECT COUNT(*) FROM `pengguna`")->fetchColumn() : 0;
$statTotalPajak = $taxPdo ? $taxPdo->query("SELECT SUM(`jumlah_bayar`) FROM `pembayaran_pajak` WHERE `status_pembayaran` = 'Lunas'")->fetchColumn() : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Database Supermarket & Perpajakan (PHP + MySQL)</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            emerald: '#059669',
                            blue: '#1d4ed8',
                            slate: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-slate-900 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- TOP HEADER NAVBAR -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between py-3 gap-3">
                
                <!-- Brand Title -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl <?= $system === 'supermarket' ? 'bg-gradient-to-tr from-emerald-600 to-teal-400' : 'bg-gradient-to-tr from-blue-600 to-indigo-500' ?> flex items-center justify-center text-white shadow-lg transition-all duration-300">
                        <i class="fa-solid <?= $system === 'supermarket' ? 'fa-cart-shopping' : 'fa-landmark' ?> text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-lg font-bold text-white tracking-tight">DATASYNC PHP</h1>
                            <span class="text-xs px-2 py-0.5 rounded-full font-semibold <?= $system === 'supermarket' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-blue-500/20 text-blue-400 border border-blue-500/30' ?>">
                                <?= $system === 'supermarket' ? 'Supermarket Active' : 'Perpajakan Active' ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">PHP 8.2 & MySQL (PDO) Relational Multi-System</p>
                    </div>
                </div>

                <!-- Nav Switcher (Supermarket vs Perpajakan) -->
                <div class="flex items-center bg-slate-800 p-1.5 rounded-2xl border border-slate-700/60 shadow-inner">
                    <a href="index.php?system=supermarket" 
                        class="flex items-center space-x-2 px-5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 <?= $system === 'supermarket' ? 'nav-tab-active-supermarket' : 'text-slate-300 hover:text-white' ?>">
                        <i class="fa-solid fa-store"></i>
                        <span>Sistem Supermarket</span>
                    </a>
                    
                    <a href="index.php?system=perpajakan" 
                        class="flex items-center space-x-2 px-5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 <?= $system === 'perpajakan' ? 'nav-tab-active-perpajakan' : 'text-slate-300 hover:text-white' ?>">
                        <i class="fa-solid fa-building-columns"></i>
                        <span>Sistem Perpajakan</span>
                    </a>
                </div>

                <!-- Global Action Tools -->
                <div class="flex items-center space-x-2">
                    <a href="export_sql.php?system=<?= $system ?>&download=1" title="Unduh File SQL Dump"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition flex items-center space-x-1.5 shadow-sm">
                        <i class="fa-solid fa-download text-emerald-400"></i>
                        <span class="hidden sm:inline">Export SQL</span>
                    </a>
                    <a href="export_sql.php?system=<?= $system ?>" target="_blank" title="Lihat SQL Script"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition flex items-center space-x-1.5 shadow-sm">
                        <i class="fa-solid fa-file-code text-amber-400"></i>
                        <span class="hidden sm:inline">Raw SQL</span>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- MAIN BODY -->
    <main class="flex-1 bg-slate-100 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Notification Message -->
            <?php if ($flash): ?>
                <?php
                $alertBg = 'bg-blue-50 border-blue-200 text-blue-800';
                $alertIcon = 'fa-circle-info text-blue-500';
                if ($flash['type'] === 'success') {
                    $alertBg = 'bg-emerald-50 border-emerald-300 text-emerald-900';
                    $alertIcon = 'fa-circle-check text-emerald-600';
                } elseif ($flash['type'] === 'error') {
                    $alertBg = 'bg-rose-50 border-rose-300 text-rose-900';
                    $alertIcon = 'fa-triangle-exclamation text-rose-600';
                }
                ?>
                <div class="p-4 rounded-2xl border flex items-center justify-between shadow-sm <?= $alertBg ?> animate-fade-in">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid <?= $alertIcon ?> text-xl flex-shrink-0"></i>
                        <span class="text-sm font-semibold"><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 text-lg">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($system === 'supermarket'): ?>
                <!-- KPI Banner: Supermarket -->
                <div class="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 rounded-3xl p-6 text-white shadow-xl border border-emerald-800/40 relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 text-emerald-800/20 text-9xl pointer-events-none select-none">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-medium border border-emerald-500/30 mb-2">
                                <i class="fa-solid fa-database text-emerald-400"></i> MySQL Database: supermarket
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Sistem Manajemen Supermarket (PHP)</h2>
                            <p class="text-slate-300 text-sm mt-1 max-w-xl">
                                Kelola master produk, data kasir shift & karyawan, data member pelanggan, dan transaksi kasir POS secara terintegrasi via PHP & MySQL.
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-center">
                                <p class="text-xs text-emerald-200">Total Produk</p>
                                <p class="text-xl font-bold mt-0.5"><?= $statProd ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-center">
                                <p class="text-xs text-emerald-200">Kasir Aktif</p>
                                <p class="text-xl font-bold mt-0.5"><?= $statKasir ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-center">
                                <p class="text-xs text-emerald-200">Member Pelanggan</p>
                                <p class="text-xl font-bold mt-0.5"><?= $statPelanggan ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-center">
                                <p class="text-xs text-emerald-200">Total Transaksi</p>
                                <p class="text-xl font-bold mt-0.5"><?= $statTrx ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sub-Navigation Tabs: Supermarket -->
                <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-200 flex flex-wrap gap-2">
                    <a href="index.php?system=supermarket&tab=produk" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'produk' ? 'subtab-active-supermarket' : 'text-slate-600 hover:text-emerald-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <span>Master Produk</span>
                    </a>
                    <a href="index.php?system=supermarket&tab=kasir" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'kasir' ? 'subtab-active-supermarket' : 'text-slate-600 hover:text-emerald-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-cash-register"></i>
                        <span>Data Kasir</span>
                    </a>
                    <a href="index.php?system=supermarket&tab=pelanggan" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'pelanggan' ? 'subtab-active-supermarket' : 'text-slate-600 hover:text-emerald-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-users"></i>
                        <span>Data Pelanggan</span>
                    </a>
                    <a href="index.php?system=supermarket&tab=pos" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'pos' ? 'subtab-active-supermarket' : 'text-slate-600 hover:text-emerald-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>Transaksi Penjualan (POS)</span>
                    </a>
                    <a href="index.php?system=supermarket&tab=riwayat" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'riwayat' ? 'subtab-active-supermarket' : 'text-slate-600 hover:text-emerald-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Riwayat Transaksi</span>
                    </a>
                </div>

            <?php else: ?>
                <!-- KPI Banner: Perpajakan -->
                <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-3xl p-6 text-white shadow-xl border border-blue-800/40 relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 text-blue-800/20 text-9xl pointer-events-none select-none">
                        <i class="fa-solid fa-landmark"></i>
                    </div>
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-medium border border-blue-500/30 mb-2">
                                <i class="fa-solid fa-database text-blue-400"></i> MySQL Database: perpajakan
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Sistem Administrasi & Pembayaran Pajak (PHP)</h2>
                            <p class="text-slate-300 text-sm mt-1 max-w-xl">
                                Kelola master kategori & jenis pajak (PPh, PBB, Pajak Karbon, PPN), data Wajib Pajak (NPWP), dan validasi setoran pajak resmi via PHP & MySQL.
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-center">
                                <p class="text-xs text-blue-200">Kategori Pajak</p>
                                <p class="text-xl font-bold mt-0.5"><?= $statKat ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-center">
                                <p class="text-xs text-blue-200">Jenis Pajak</p>
                                <p class="text-xl font-bold mt-0.5"><?= $statJenis ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-center">
                                <p class="text-xs text-blue-200">Wajib Pajak (WP)</p>
                                <p class="text-xl font-bold mt-0.5"><?= $statPengguna ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-center">
                                <p class="text-xs text-blue-200">Setoran Pajak Lunas</p>
                                <p class="text-base sm:text-lg font-bold mt-0.5 truncate text-amber-300"><?= formatRupiah($statTotalPajak) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sub-Navigation Tabs: Perpajakan -->
                <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-200 flex flex-wrap gap-2">
                    <a href="index.php?system=perpajakan&tab=kategori" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'kategori' ? 'subtab-active-perpajakan' : 'text-slate-600 hover:text-blue-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-folder-tree"></i>
                        <span>Kategori Pajak</span>
                    </a>
                    <a href="index.php?system=perpajakan&tab=jenis" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'jenis' ? 'subtab-active-perpajakan' : 'text-slate-600 hover:text-blue-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-scale-balanced"></i>
                        <span>Jenis Pajak & Tarif</span>
                    </a>
                    <a href="index.php?system=perpajakan&tab=pengguna" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'pengguna' ? 'subtab-active-perpajakan' : 'text-slate-600 hover:text-blue-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-address-card"></i>
                        <span>Wajib Pajak & Petugas (NPWP)</span>
                    </a>
                    <a href="index.php?system=perpajakan&tab=pembayaran" 
                        class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition <?= $tab === 'pembayaran' ? 'subtab-active-perpajakan' : 'text-slate-600 hover:text-blue-700 hover:bg-slate-50' ?>">
                        <i class="fa-solid fa-money-check-dollar"></i>
                        <span>Pembayaran Pajak</span>
                    </a>
                </div>
            <?php endif; ?>
