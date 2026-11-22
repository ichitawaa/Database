<?php
/**
 * Modul Produk - Sistem Supermarket (CRUD)
 */

$pdo = Database::getSupermarket();

// Handle Form Submissions (Add / Edit / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $id_produk = trim($_POST['id_produk'] ?? '');
        $nama_produk = trim($_POST['nama_produk'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $harga_jual = (int)($_POST['harga_jual'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);
        $is_edit = !empty($_POST['is_edit']);

        if (empty($id_produk)) {
            $id_produk = generateNextId('PRD', 'produk', 'id_produk', $pdo);
        }

        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE `produk` SET `nama_produk` = ?, `kategori` = ?, `harga_jual` = ?, `stok` = ? WHERE `id_produk` = ?");
                $stmt->execute([$nama_produk, $kategori, $harga_jual, $stok, $id_produk]);
                setFlash('success', "Produk '$nama_produk' ($id_produk) berhasil diperbarui!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO `produk` (`id_produk`, `nama_produk`, `kategori`, `harga_jual`, `stok`) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_produk, $nama_produk, $kategori, $harga_jual, $stok]);
                setFlash('success', "Produk baru '$nama_produk' ($id_produk) berhasil ditambahkan!");
            }
        } catch (PDOException $e) {
            setFlash('error', "Gagal menyimpan produk: " . $e->getMessage());
        }
        header("Location: index.php?system=supermarket&tab=produk");
        exit;
    }

    if ($action === 'delete') {
        $id_produk = $_POST['id_produk'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM `produk` WHERE `id_produk` = ?");
            $stmt->execute([$id_produk]);
            setFlash('success', "Produk $id_produk berhasil dihapus!");
        } catch (PDOException $e) {
            setFlash('error', "Gagal menghapus produk: Produk ini mungkin sudah tercatat dalam riwayat transaksi!");
        }
        header("Location: index.php?system=supermarket&tab=produk");
        exit;
    }
}

// Filter & Search
$search = trim($_GET['q'] ?? '');
$filterKat = trim($_GET['kat'] ?? '');

$query = "SELECT * FROM `produk` WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (`id_produk` LIKE ? OR `nama_produk` LIKE ? OR `kategori` LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($filterKat)) {
    $query .= " AND `kategori` = ?";
    $params[] = $filterKat;
}

$query .= " ORDER BY `id_produk` ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$produkList = $stmt->fetchAll();

// Daftar kategori unik untuk dropdown filter
$kategoriList = $pdo->query("SELECT DISTINCT `kategori` FROM `produk` ORDER BY `kategori` ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="space-y-4">
    <!-- Bar Filter & Tombol Tambah -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" class="flex flex-1 flex-col sm:flex-row gap-3">
            <input type="hidden" name="system" value="supermarket">
            <input type="hidden" name="tab" value="produk">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari kode PRD, nama produk, kategori..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>
            <select name="kat" onchange="this.form.submit()"
                class="px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-700 bg-white">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategoriList as $k): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= $filterKat === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">
                Cari
            </button>
            <?php if (!empty($search) || !empty($filterKat)): ?>
                <a href="index.php?system=supermarket&tab=produk" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center">
                    Reset
                </a>
            <?php endif; ?>
        </form>

        <button onclick="openProdukModal()"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center justify-center space-x-2 shadow-md shadow-emerald-600/20">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Produk Baru</span>
        </button>
    </div>

    <!-- Tabel Produk -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Kode Produk</th>
                        <th class="py-3.5 px-4">Nama Produk</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4 text-right">Harga Jual</th>
                        <th class="py-3.5 px-4 text-center">Stok</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($produkList)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-box-open text-3xl mb-2 text-slate-300"></i>
                                <p>Tidak ada produk yang cocok dengan pencarian.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produkList as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-700"><?= htmlspecialchars($p['id_produk']) ?></td>
                                <td class="py-3 px-4 font-semibold text-slate-900"><?= htmlspecialchars($p['nama_produk']) ?></td>
                                <td class="py-3 px-4">
                                    <span class="inline-block px-2.5 py-0.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($p['kategori']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700"><?= formatRupiah($p['harga_jual']) ?></td>
                                <td class="py-3 px-4 text-center font-bold"><?= $p['stok'] ?></td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($p['stok'] > 20): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Tersedia (<?= $p['stok'] ?>)</span>
                                    <?php elseif ($p['stok'] > 0): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Menipis (<?= $p['stok'] ?>)</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick='editProduk(<?= json_encode($p) ?>)' title="Edit Produk"
                                            class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white transition flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Hapus produk <?= htmlspecialchars($p['nama_produk']) ?> (<?= $p['id_produk'] ?>)?');" class="inline">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
                                            <button type="submit" title="Hapus Produk"
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

<!-- Modal Tambah / Edit Produk -->
<div id="produkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-slide-down">
        <div class="px-6 py-4 bg-emerald-600 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-box-open text-lg"></i>
                <h3 id="produkModalTitle" class="text-base font-bold">Form Data Produk</h3>
            </div>
            <button onclick="closeProdukModal()" class="text-white/80 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="is_edit" id="prodIsEdit" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Produk (ID)</label>
                    <input type="text" name="id_produk" id="prodId" placeholder="Otomatis PRD-xxx"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Produk <span class="text-rose-500">*</span></label>
                    <input type="text" name="kategori" id="prodKategori" required placeholder="Contoh: Sembako, Minuman..."
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_produk" id="prodNama" required placeholder="Nama lengkap produk..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Harga Jual (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" name="harga_jual" id="prodHarga" required min="100" placeholder="Contoh: 25000"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Stok <span class="text-rose-500">*</span></label>
                    <input type="number" name="stok" id="prodStok" required min="0" placeholder="Contoh: 50"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeProdukModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-md shadow-emerald-600/20">
                    Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openProdukModal() {
    document.getElementById('produkModalTitle').innerText = 'Tambah Produk Baru';
    document.getElementById('prodIsEdit').value = '0';
    document.getElementById('prodId').value = '';
    document.getElementById('prodId').readOnly = false;
    document.getElementById('prodNama').value = '';
    document.getElementById('prodKategori').value = '';
    document.getElementById('prodHarga').value = '';
    document.getElementById('prodStok').value = '10';
    document.getElementById('produkModal').classList.remove('hidden');
}

function editProduk(p) {
    document.getElementById('produkModalTitle').innerText = 'Edit Produk: ' + p.nama_produk;
    document.getElementById('prodIsEdit').value = '1';
    document.getElementById('prodId').value = p.id_produk;
    document.getElementById('prodId').readOnly = true;
    document.getElementById('prodNama').value = p.nama_produk;
    document.getElementById('prodKategori').value = p.kategori;
    document.getElementById('prodHarga').value = p.harga_jual;
    document.getElementById('prodStok').value = p.stok;
    document.getElementById('produkModal').classList.remove('hidden');
}

function closeProdukModal() {
    document.getElementById('produkModal').classList.add('hidden');
}
</script>
