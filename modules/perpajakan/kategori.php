<?php
/**
 * Modul Kategori Pajak - Sistem Perpajakan (CRUD)
 */

$pdo = Database::getPerpajakan();

// Handle Form Submissions (Add / Edit / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $id_kategori = trim($_POST['id_kategori'] ?? '');
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $is_edit = !empty($_POST['is_edit']);

        if (empty($id_kategori)) {
            $id_kategori = generateNextId('KAT', 'kategori_pajak', 'id_kategori', $pdo, 2);
        }

        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE `kategori_pajak` SET `nama_kategori` = ?, `keterangan` = ? WHERE `id_kategori` = ?");
                $stmt->execute([$nama_kategori, $keterangan, $id_kategori]);
                setFlash('success', "Kategori pajak '$nama_kategori' ($id_kategori) berhasil diperbarui!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO `kategori_pajak` (`id_kategori`, `nama_kategori`, `keterangan`) VALUES (?, ?, ?)");
                $stmt->execute([$id_kategori, $nama_kategori, $keterangan]);
                setFlash('success', "Kategori pajak '$nama_kategori' ($id_kategori) berhasil ditambahkan!");
            }
        } catch (PDOException $e) {
            setFlash('error', "Gagal menyimpan kategori pajak: " . $e->getMessage());
        }

        header("Location: index.php?system=perpajakan&tab=kategori");
        exit;
    }

    if ($action === 'delete') {
        $id_kategori = $_POST['id_kategori'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM `kategori_pajak` WHERE `id_kategori` = ?");
            $stmt->execute([$id_kategori]);
            setFlash('success', "Kategori pajak $id_kategori berhasil dihapus!");
        } catch (PDOException $e) {
            setFlash('error', "Kategori pajak ini tidak dapat dihapus karena masih digunakan oleh Jenis Pajak yang aktif!");
        }
        header("Location: index.php?system=perpajakan&tab=kategori");
        exit;
    }
}

// Search
$search = trim($_GET['q'] ?? '');
$query = "
    SELECT k.*, COUNT(j.`id_pajak`) AS total_jenis
    FROM `kategori_pajak` k
    LEFT JOIN `jenis_pajak` j ON k.id_kategori = j.id_kategori
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (k.`id_kategori` LIKE ? OR k.`nama_kategori` LIKE ? OR k.`keterangan` LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$query .= " GROUP BY k.`id_kategori` ORDER BY k.`id_kategori` ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kategoriList = $stmt->fetchAll();
?>

<div class="space-y-4">
    <!-- Bar Filter & Tombol Tambah -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" class="flex flex-1 gap-3">
            <input type="hidden" name="system" value="perpajakan">
            <input type="hidden" name="tab" value="kategori">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari kode kategori, nama kategori, keterangan..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">
                Cari
            </button>
            <?php if (!empty($search)): ?>
                <a href="index.php?system=perpajakan&tab=kategori" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center">
                    Reset
                </a>
            <?php endif; ?>
        </form>

        <button onclick="openTaxKategoriModal()"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center justify-center space-x-2 shadow-md shadow-blue-600/20">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Kategori Baru</span>
        </button>
    </div>

    <!-- Tabel Kategori Pajak -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">ID Kategori</th>
                        <th class="py-3.5 px-4">Nama Kategori</th>
                        <th class="py-3.5 px-4">Keterangan Regulasi</th>
                        <th class="py-3.5 px-4 text-center">Jumlah Jenis Pajak</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($kategoriList)): ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300"></i>
                                <p>Tidak ada kategori pajak yang ditemukan.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kategoriList as $k): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($k['id_kategori']) ?></td>
                                <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($k['nama_kategori']) ?></td>
                                <td class="py-3 px-4 text-xs text-slate-600 max-w-sm"><?= htmlspecialchars($k['keterangan'] ?? '-') ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <?= $k['total_jenis'] ?> Jenis
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick='editTaxKategori(<?= json_encode($k) ?>)' title="Edit Kategori"
                                            class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white transition flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Hapus kategori <?= htmlspecialchars($k['nama_kategori']) ?> (<?= $k['id_kategori'] ?>)?');" class="inline">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_kategori" value="<?= $k['id_kategori'] ?>">
                                            <button type="submit" title="Hapus Kategori"
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

<!-- Modal Tambah / Edit Kategori -->
<div id="taxKategoriModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-slide-down">
        <div class="px-6 py-4 bg-blue-600 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-folder-tree text-lg"></i>
                <h3 id="taxKatModalTitle" class="text-base font-bold">Form Kategori Pajak</h3>
            </div>
            <button onclick="closeTaxKategoriModal()" class="text-white/80 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="is_edit" id="taxKatIsEdit" value="0">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kategori Pajak</label>
                <input type="text" name="id_kategori" id="taxKatId" placeholder="Otomatis KAT-xx"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori Pajak <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_kategori" id="taxKatNama" required placeholder="Contoh: Pajak Penghasilan, Pajak Lingkungan..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Dasar Regulasi</label>
                <textarea name="keterangan" id="taxKatKet" rows="3" placeholder="Penjelasan objek pajak atau undang-undang terkait..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeTaxKategoriModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-md shadow-blue-600/20">
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openTaxKategoriModal() {
    document.getElementById('taxKatModalTitle').innerText = 'Tambah Kategori Pajak Baru';
    document.getElementById('taxKatIsEdit').value = '0';
    document.getElementById('taxKatId').value = '';
    document.getElementById('taxKatId').readOnly = false;
    document.getElementById('taxKatNama').value = '';
    document.getElementById('taxKatKet').value = '';
    document.getElementById('taxKategoriModal').classList.remove('hidden');
}

function editTaxKategori(k) {
    document.getElementById('taxKatModalTitle').innerText = 'Edit Kategori: ' + k.nama_kategori;
    document.getElementById('taxKatIsEdit').value = '1';
    document.getElementById('taxKatId').value = k.id_kategori;
    document.getElementById('taxKatId').readOnly = true;
    document.getElementById('taxKatNama').value = k.nama_kategori;
    document.getElementById('taxKatKet').value = k.keterangan || '';
    document.getElementById('taxKategoriModal').classList.remove('hidden');
}

function closeTaxKategoriModal() {
    document.getElementById('taxKategoriModal').classList.add('hidden');
}
</script>
