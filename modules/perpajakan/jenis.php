<?php
/**
 * Modul Jenis Pajak & Tarif - Sistem Perpajakan (CRUD)
 */

$pdo = Database::getPerpajakan();

// Handle Form Submissions (Add / Edit / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $id_pajak = trim($_POST['id_pajak'] ?? '');
        $id_kategori = trim($_POST['id_kategori'] ?? '');
        $nama_pajak = trim($_POST['nama_pajak'] ?? '');
        $tarif_pajak = trim($_POST['tarif_pajak'] ?? '');
        $is_edit = !empty($_POST['is_edit']);

        if (empty($id_pajak)) {
            $id_pajak = generateNextId('PJK', 'jenis_pajak', 'id_pajak', $pdo, 2);
        }

        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE `jenis_pajak` SET `id_kategori` = ?, `nama_pajak` = ?, `tarif_pajak` = ? WHERE `id_pajak` = ?");
                $stmt->execute([$id_kategori, $nama_pajak, $tarif_pajak, $id_pajak]);
                setFlash('success', "Jenis pajak '$nama_pajak' ($id_pajak) berhasil diperbarui!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO `jenis_pajak` (`id_pajak`, `id_kategori`, `nama_pajak`, `tarif_pajak`) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_pajak, $id_kategori, $nama_pajak, $tarif_pajak]);
                setFlash('success', "Jenis pajak baru '$nama_pajak' ($id_pajak) berhasil ditambahkan!");
            }
        } catch (PDOException $e) {
            setFlash('error', "Gagal menyimpan jenis pajak: " . $e->getMessage());
        }

        header("Location: index.php?system=perpajakan&tab=jenis");
        exit;
    }

    if ($action === 'delete') {
        $id_pajak = $_POST['id_pajak'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM `jenis_pajak` WHERE `id_pajak` = ?");
            $stmt->execute([$id_pajak]);
            setFlash('success', "Jenis pajak $id_pajak berhasil dihapus!");
        } catch (PDOException $e) {
            setFlash('error', "Jenis pajak ini tidak dapat dihapus karena sudah tercatat dalam riwayat pembayaran pajak!");
        }
        header("Location: index.php?system=perpajakan&tab=jenis");
        exit;
    }
}

// Filter & Search
$search = trim($_GET['q'] ?? '');
$filterKat = trim($_GET['kat'] ?? '');

$query = "
    SELECT j.*, k.nama_kategori
    FROM `jenis_pajak` j
    LEFT JOIN `kategori_pajak` k ON j.id_kategori = k.id_kategori
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (j.`id_pajak` LIKE ? OR j.`nama_pajak` LIKE ? OR j.`tarif_pajak` LIKE ? OR k.`nama_kategori` LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

if (!empty($filterKat)) {
    $query .= " AND j.`id_kategori` = ?";
    $params[] = $filterKat;
}

$query .= " ORDER BY j.`id_pajak` ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jenisList = $stmt->fetchAll();

// Dropdown Kategori Pajak
$kategoriList = $pdo->query("SELECT * FROM `kategori_pajak` ORDER BY `nama_kategori` ASC")->fetchAll();
?>

<div class="space-y-4">
    <!-- Bar Filter & Tombol Tambah -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" class="flex flex-1 flex-col sm:flex-row gap-3">
            <input type="hidden" name="system" value="perpajakan">
            <input type="hidden" name="tab" value="jenis">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ID pajak, nama pajak, tarif, kategori..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <select name="kat" onchange="this.form.submit()"
                class="px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-700 bg-white">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategoriList as $k): ?>
                    <option value="<?= htmlspecialchars($k['id_kategori']) ?>" <?= $filterKat === $k['id_kategori'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">
                Cari
            </button>
            <?php if (!empty($search) || !empty($filterKat)): ?>
                <a href="index.php?system=perpajakan&tab=jenis" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center">
                    Reset
                </a>
            <?php endif; ?>
        </form>

        <button onclick="openTaxJenisModal()"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center justify-center space-x-2 shadow-md shadow-blue-600/20">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Jenis Pajak</span>
        </button>
    </div>

    <!-- Tabel Jenis Pajak -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">ID Pajak</th>
                        <th class="py-3.5 px-4">Nama Jenis Pajak</th>
                        <th class="py-3.5 px-4">Kategori Pajak</th>
                        <th class="py-3.5 px-4">Tarif Pajak Resmi</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($jenisList)): ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-scale-unbalanced text-3xl mb-2 text-slate-300"></i>
                                <p>Tidak ada jenis pajak yang ditemukan.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jenisList as $j): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($j['id_pajak']) ?></td>
                                <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($j['nama_pajak']) ?></td>
                                <td class="py-3 px-4">
                                    <span class="inline-block px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($j['nama_kategori'] ?? $j['id_kategori']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-blue-700"><?= htmlspecialchars($j['tarif_pajak']) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick='editTaxJenis(<?= json_encode($j) ?>)' title="Edit Jenis Pajak"
                                            class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white transition flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Hapus jenis pajak <?= htmlspecialchars($j['nama_pajak']) ?> (<?= $j['id_pajak'] ?>)?');" class="inline">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_pajak" value="<?= $j['id_pajak'] ?>">
                                            <button type="submit" title="Hapus Jenis Pajak"
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

<!-- Modal Tambah / Edit Jenis Pajak -->
<div id="taxJenisModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-slide-down">
        <div class="px-6 py-4 bg-blue-600 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-scale-balanced text-lg"></i>
                <h3 id="taxJenisModalTitle" class="text-base font-bold">Form Jenis Pajak</h3>
            </div>
            <button onclick="closeTaxJenisModal()" class="text-white/80 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="is_edit" id="taxJenisIsEdit" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ID Jenis Pajak</label>
                    <input type="text" name="id_pajak" id="taxJenisId" placeholder="Otomatis PJK-xx"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Pajak <span class="text-rose-500">*</span></label>
                    <select name="id_kategori" id="taxJenisKategori" required
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                        <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= htmlspecialchars($k['id_kategori']) ?>">
                                <?= htmlspecialchars($k['nama_kategori']) ?> (<?= htmlspecialchars($k['id_kategori']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Jenis Pajak <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_pajak" id="taxJenisNama" required placeholder="Contoh: PPh Pasal 21, Pajak Karbon..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tarif Pajak Resmi <span class="text-rose-500">*</span></label>
                <input type="text" name="tarif_pajak" id="taxJenisTarif" required placeholder="Contoh: 11%, 0.5% Omzet, Rp 30 per kg..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-medium">
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeTaxJenisModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-md shadow-blue-600/20">
                    Simpan Jenis Pajak
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openTaxJenisModal() {
    document.getElementById('taxJenisModalTitle').innerText = 'Tambah Jenis Pajak Baru';
    document.getElementById('taxJenisIsEdit').value = '0';
    document.getElementById('taxJenisId').value = '';
    document.getElementById('taxJenisId').readOnly = false;
    document.getElementById('taxJenisNama').value = '';
    document.getElementById('taxJenisTarif').value = '';
    document.getElementById('taxJenisModal').classList.remove('hidden');
}

function editTaxJenis(j) {
    document.getElementById('taxJenisModalTitle').innerText = 'Edit Jenis Pajak: ' + j.nama_pajak;
    document.getElementById('taxJenisIsEdit').value = '1';
    document.getElementById('taxJenisId').value = j.id_pajak;
    document.getElementById('taxJenisId').readOnly = true;
    document.getElementById('taxJenisKategori').value = j.id_kategori;
    document.getElementById('taxJenisNama').value = j.nama_pajak;
    document.getElementById('taxJenisTarif').value = j.tarif_pajak;
    document.getElementById('taxJenisModal').classList.remove('hidden');
}

function closeTaxJenisModal() {
    document.getElementById('taxJenisModal').classList.add('hidden');
}
</script>
