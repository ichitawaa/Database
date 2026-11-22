<?php
/**
 * Main Controller & View Router
 * Aplikasi CRUD Multi-Database: Sistem Supermarket & Sistem Perpajakan (PHP + MySQL)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

// Tentukan sistem yang aktif
$system = $_GET['system'] ?? 'supermarket';
if (!in_array($system, ['supermarket', 'perpajakan'])) {
    $system = 'supermarket';
}

// Tentukan tab modul yang aktif
if ($system === 'supermarket') {
    $tab = $_GET['tab'] ?? 'produk';
    if (!in_array($tab, ['produk', 'kasir', 'pelanggan', 'pos', 'riwayat'])) {
        $tab = 'produk';
    }
} else {
    $tab = $_GET['tab'] ?? 'kategori';
    if (!in_array($tab, ['kategori', 'jenis', 'pengguna', 'pembayaran'])) {
        $tab = 'kategori';
    }
}

// Periksa koneksi database
$dbError = null;
if ($system === 'supermarket') {
    $pdo = Database::getSupermarket();
    if (!$pdo) {
        $dbError = Database::getError();
    }
} else {
    $pdo = Database::getPerpajakan();
    if (!$pdo) {
        $dbError = Database::getError();
    }
}

// Tampilkan halaman error database jika MySQL tidak berjalan
if ($dbError) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-6">
        <div class="max-w-lg w-full bg-slate-800 border border-rose-500/30 rounded-3xl p-8 shadow-2xl text-center space-y-5">
            <div class="w-16 h-16 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-3xl mx-auto border border-rose-500/30">
                ⚠️
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">Gagal Terhubung ke MySQL Database</h1>
                <p class="text-sm text-slate-400 mt-2">
                    Pastikan service <strong>MySQL di XAMPP Control Panel</strong> sudah dalam status <strong>Started / Running</strong> pada port 3306.
                </p>
            </div>
            <div class="p-4 bg-slate-950/60 rounded-xl text-left font-mono text-xs text-rose-300 border border-slate-700 overflow-x-auto">
                <?= htmlspecialchars($dbError) ?>
            </div>
            <div class="pt-2">
                <a href="index.php" class="inline-block px-6 py-2.5 rounded-xl font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition text-sm">
                    Coba Hubungkan Kembali
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Muat Header
require_once __DIR__ . '/includes/header.php';

// Muat Modul Tampilan Sesuai Tab
if ($system === 'supermarket') {
    switch ($tab) {
        case 'produk':
            require_once __DIR__ . '/modules/supermarket/produk.php';
            break;
        case 'kasir':
            require_once __DIR__ . '/modules/supermarket/kasir.php';
            break;
        case 'pelanggan':
            require_once __DIR__ . '/modules/supermarket/pelanggan.php';
            break;
        case 'pos':
            require_once __DIR__ . '/modules/supermarket/transaksi.php';
            break;
        case 'riwayat':
            require_once __DIR__ . '/modules/supermarket/riwayat.php';
            break;
    }
} else {
    switch ($tab) {
        case 'kategori':
            require_once __DIR__ . '/modules/perpajakan/kategori.php';
            break;
        case 'jenis':
            require_once __DIR__ . '/modules/perpajakan/jenis.php';
            break;
        case 'pengguna':
            require_once __DIR__ . '/modules/perpajakan/pengguna.php';
            break;
        case 'pembayaran':
            require_once __DIR__ . '/modules/perpajakan/pembayaran.php';
            break;
    }
}

// Muat Footer
require_once __DIR__ . '/includes/footer.php';
