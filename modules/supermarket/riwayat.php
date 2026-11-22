<?php
/**
 * Modul Riwayat Transaksi - Sistem Supermarket
 */

$pdo = Database::getSupermarket();

// Handle Delete
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action_delete_trx'])) {
    $id_trx = $_POST['id_transaksi'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM `transaksi` WHERE `id_transaksi` = ?");
        $stmt->execute([$id_trx]);
        setFlash('success', "Riwayat transaksi $id_trx berhasil dihapus!");
    } catch (PDOException $e) {
        setFlash('error', "Gagal menghapus transaksi: " . $e->getMessage());
    }
    header("Location: index.php?system=supermarket&tab=riwayat");
    exit;
}

// Search
$search = trim($_GET['q'] ?? '');
$query = "
    SELECT t.*, k.shift, kr.nama_karyawan, p.nama_pelanggan
    FROM `transaksi` t
    LEFT JOIN `kasir` k ON t.id_kasir = k.id_kasir
    LEFT JOIN `karyawan` kr ON k.id_karyawan = kr.id_karyawan
    LEFT JOIN `pelanggan` p ON t.id_pelanggan = p.id_pelanggan
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (t.`id_transaksi` LIKE ? OR kr.`nama_karyawan` LIKE ? OR p.`nama_pelanggan` LIKE ? OR t.`metode_pembayaran` LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

$query .= " ORDER BY t.`tanggal_transaksi` DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$trxList = $stmt->fetchAll();
?>

<div class="space-y-4">
    <!-- Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" class="flex flex-1 gap-3">
            <input type="hidden" name="system" value="supermarket">
            <input type="hidden" name="tab" value="riwayat">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nomor TRX, nama kasir, pelanggan, metode bayar..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">
                Cari
            </button>
            <?php if (!empty($search)): ?>
                <a href="index.php?system=supermarket&tab=riwayat" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center">
                    Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Riwayat Transaksi -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">No. Transaksi</th>
                        <th class="py-3.5 px-4">Waktu Transaksi</th>
                        <th class="py-3.5 px-4">Kasir Bertugas</th>
                        <th class="py-3.5 px-4">Pelanggan</th>
                        <th class="py-3.5 px-4">Metode</th>
                        <th class="py-3.5 px-4 text-right">Total Tagihan</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($trxList)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                                <p>Belum ada riwayat transaksi penjualan.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($trxList as $t): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono font-bold text-emerald-800"><?= htmlspecialchars($t['id_transaksi']) ?></td>
                                <td class="py-3 px-4 text-xs font-mono text-slate-600"><?= formatTanggalIndo($t['tanggal_transaksi']) ?></td>
                                <td class="py-3 px-4 font-semibold text-slate-800"><?= htmlspecialchars($t['nama_karyawan'] ?? $t['id_kasir']) ?></td>
                                <td class="py-3 px-4 text-slate-700"><?= htmlspecialchars($t['nama_pelanggan'] ?? 'Pelanggan Umum') ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($t['metode_pembayaran']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700"><?= formatRupiah($t['total_bayar']) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick="viewReceipt('<?= $t['id_transaksi'] ?>')" title="Lihat / Cetak Struk"
                                            class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white transition text-xs font-semibold flex items-center gap-1">
                                            <i class="fa-solid fa-print"></i>
                                            <span>Struk</span>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Hapus riwayat transaksi <?= $t['id_transaksi'] ?>?');" class="inline">
                                            <input type="hidden" name="action_delete_trx" value="1">
                                            <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
                                            <button type="submit" title="Hapus Riwayat"
                                                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
