<?php
/**
 * Modul Pengguna / Wajib Pajak - Sistem Perpajakan (CRUD)
 */

$pdo = Database::getPerpajakan();

// Handle Form Submissions (Add / Edit / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $id_pengguna = trim($_POST['id_pengguna'] ?? '');
        $nama_pengguna = trim($_POST['nama_pengguna'] ?? '');
        $tipe_pengguna = trim($_POST['tipe_pengguna'] ?? 'Perorangan');
        $npwp = trim($_POST['npwp'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $is_edit = !empty($_POST['is_edit']);

        if (empty($id_pengguna)) {
            $id_pengguna = generateNextId('USR', 'pengguna', 'id_pengguna', $pdo);
        }

        try {
            // Cek duplikasi NPWP jika diisi
            if (!empty($npwp)) {
                $checkStmt = $pdo->prepare("SELECT `id_pengguna`, `nama_pengguna` FROM `pengguna` WHERE `npwp` = ? AND `id_pengguna` != ?");
                $checkStmt->execute([$npwp, $id_pengguna]);
                $dup = $checkStmt->fetch();
                if ($dup) {
                    throw new Exception("Nomor NPWP $npwp sudah terdaftar atas nama " . $dup['nama_pengguna'] . " (" . $dup['id_pengguna'] . ")!");
                }
            }

            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE `pengguna` SET `nama_pengguna` = ?, `tipe_pengguna` = ?, `npwp` = ?, `no_telepon` = ?, `alamat` = ? WHERE `id_pengguna` = ?");
                $stmt->execute([$nama_pengguna, $tipe_pengguna, $npwp, $no_telepon, $alamat, $id_pengguna]);
                setFlash('success', "Data pengguna '$nama_pengguna' ($id_pengguna) berhasil diperbarui!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO `pengguna` (`id_pengguna`, `nama_pengguna`, `tipe_pengguna`, `npwp`, `no_telepon`, `alamat`) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_pengguna, $nama_pengguna, $tipe_pengguna, $npwp, $no_telepon, $alamat]);
                setFlash('success', "Wajib Pajak / Petugas baru '$nama_pengguna' ($id_pengguna) berhasil ditambahkan!");
            }
        } catch (Exception $e) {
            setFlash('error', "Gagal menyimpan pengguna: " . $e->getMessage());
        }

        header("Location: index.php?system=perpajakan&tab=pengguna");
        exit;
    }

    if ($action === 'delete') {
        $id_pengguna = $_POST['id_pengguna'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM `pengguna` WHERE `id_pengguna` = ?");
            $stmt->execute([$id_pengguna]);
            setFlash('success', "Pengguna $id_pengguna berhasil dihapus!");
        } catch (PDOException $e) {
            setFlash('error', "Pengguna ini tidak dapat dihapus karena memiliki riwayat pembayaran / verifikasi pajak!");
        }
        header("Location: index.php?system=perpajakan&tab=pengguna");
        exit;
    }
}

// Filter & Search
$search = trim($_GET['q'] ?? '');
$filterTipe = trim($_GET['tipe'] ?? '');

$query = "SELECT * FROM `pengguna` WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (`id_pengguna` LIKE ? OR `nama_pengguna` LIKE ? OR `npwp` LIKE ? OR `alamat` LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

if (!empty($filterTipe)) {
    $query .= " AND `tipe_pengguna` = ?";
    $params[] = $filterTipe;
}

$query .= " ORDER BY `id_pengguna` ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$penggunaList = $stmt->fetchAll();
?>

<div class="space-y-4">
    <!-- Bar Filter & Tombol Tambah -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" class="flex flex-1 flex-col sm:flex-row gap-3">
            <input type="hidden" name="system" value="perpajakan">
            <input type="hidden" name="tab" value="pengguna">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama pengguna, NPWP, alamat..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <select name="tipe" onchange="this.form.submit()"
                class="px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-700 bg-white">
                <option value="">Semua Tipe Pengguna</option>
                <option value="Perusahaan" <?= $filterTipe === 'Perusahaan' ? 'selected' : '' ?>>Perusahaan (Badan Usaha)</option>
                <option value="Perorangan" <?= $filterTipe === 'Perorangan' ? 'selected' : '' ?>>Perorangan (Orang Pribadi)</option>
                <option value="Petugas Pajak" <?= $filterTipe === 'Petugas Pajak' ? 'selected' : '' ?>>Petugas Pajak (Fiskus)</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">
                Cari
            </button>
            <?php if (!empty($search) || !empty($filterTipe)): ?>
                <a href="index.php?system=perpajakan&tab=pengguna" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center">
                    Reset
                </a>
            <?php endif; ?>
        </form>

        <button onclick="openTaxPenggunaModal()"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center justify-center space-x-2 shadow-md shadow-blue-600/20">
            <i class="fa-solid fa-user-plus"></i>
            <span>Tambah Wajib Pajak / Petugas</span>
        </button>
    </div>

    <!-- Tabel Pengguna / Wajib Pajak -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">ID User</th>
                        <th class="py-3.5 px-4">Nama Pengguna</th>
                        <th class="py-3.5 px-4">Tipe Akun</th>
                        <th class="py-3.5 px-4">Nomor Pokok Wajib Pajak (NPWP)</th>
                        <th class="py-3.5 px-4">Kontak Telepon</th>
                        <th class="py-3.5 px-4">Alamat Domisili</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($penggunaList)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-user-slash text-3xl mb-2 text-slate-300"></i>
                                <p>Tidak ada data pengguna yang ditemukan.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($penggunaList as $u): ?>
                            <?php
                            $typeBadge = '<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200"><i class="fa-solid fa-user mr-1"></i>Perorangan</span>';
                            if ($u['tipe_pengguna'] === 'Perusahaan') {
                                $typeBadge = '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200"><i class="fa-solid fa-building mr-1"></i>Perusahaan</span>';
                            } elseif ($u['tipe_pengguna'] === 'Petugas Pajak') {
                                $typeBadge = '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200"><i class="fa-solid fa-user-tie mr-1"></i>Petugas Pajak</span>';
                            }
                            ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($u['id_pengguna']) ?></td>
                                <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($u['nama_pengguna']) ?></td>
                                <td class="py-3 px-4"><?= $typeBadge ?></td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-800"><?= htmlspecialchars($u['npwp'] ?? '-') ?></td>
                                <td class="py-3 px-4 font-mono text-slate-600"><?= htmlspecialchars($u['no_telepon'] ?? '-') ?></td>
                                <td class="py-3 px-4 text-xs text-slate-500 max-w-xs truncate"><?= htmlspecialchars($u['alamat'] ?? '-') ?></td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick='editTaxPengguna(<?= json_encode($u) ?>)' title="Edit Pengguna"
                                            class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white transition flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Hapus pengguna <?= htmlspecialchars($u['nama_pengguna']) ?> (<?= $u['id_pengguna'] ?>)?');" class="inline">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_pengguna" value="<?= $u['id_pengguna'] ?>">
                                            <button type="submit" title="Hapus Pengguna"
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

<!-- Modal Tambah / Edit Pengguna -->
<div id="taxPenggunaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-slide-down">
        <div class="px-6 py-4 bg-blue-600 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-address-card text-lg"></i>
                <h3 id="taxUsrModalTitle" class="text-base font-bold">Form Wajib Pajak / Petugas</h3>
            </div>
            <button onclick="closeTaxPenggunaModal()" class="text-white/80 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="is_edit" id="taxUsrIsEdit" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ID Pengguna</label>
                    <input type="text" name="id_pengguna" id="taxUsrId" placeholder="Otomatis USR-xxx"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Pengguna <span class="text-rose-500">*</span></label>
                    <select name="tipe_pengguna" id="taxUsrTipe" required
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white font-semibold">
                        <option value="Perorangan">Perorangan (Wajib Pajak)</option>
                        <option value="Perusahaan">Perusahaan (Badan Usaha)</option>
                        <option value="Petugas Pajak">Petugas Pajak (Fiskus / Verifikator)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap / Nama Entitas <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_pengguna" id="taxUsrNama" required placeholder="Contoh: PT. Maju Berkah / Budi Santoso"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Pokok Wajib Pajak (NPWP) <span class="text-rose-500">*</span></label>
                    <input type="text" name="npwp" id="taxUsrNpwp" required placeholder="00.000.000.0-000.000"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / Kontak</label>
                    <input type="text" name="no_telepon" id="taxUsrTelp" placeholder="08xxxxxxxxxx / 021-xxxxxx"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap Domisili / Kantor</label>
                <textarea name="alamat" id="taxUsrAlamat" rows="2" placeholder="Alamat tempat tinggal atau kantor..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeTaxPenggunaModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-md shadow-blue-600/20">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openTaxPenggunaModal() {
    document.getElementById('taxUsrModalTitle').innerText = 'Tambah Wajib Pajak / Petugas';
    document.getElementById('taxUsrIsEdit').value = '0';
    document.getElementById('taxUsrId').value = '';
    document.getElementById('taxUsrId').readOnly = false;
    document.getElementById('taxUsrNama').value = '';
    document.getElementById('taxUsrTipe').value = 'Perorangan';
    document.getElementById('taxUsrNpwp').value = '';
    document.getElementById('taxUsrTelp').value = '';
    document.getElementById('taxUsrAlamat').value = '';
    document.getElementById('taxPenggunaModal').classList.remove('hidden');
}

function editTaxPengguna(u) {
    document.getElementById('taxUsrModalTitle').innerText = 'Edit Pengguna: ' + u.nama_pengguna;
    document.getElementById('taxUsrIsEdit').value = '1';
    document.getElementById('taxUsrId').value = u.id_pengguna;
    document.getElementById('taxUsrId').readOnly = true;
    document.getElementById('taxUsrNama').value = u.nama_pengguna;
    document.getElementById('taxUsrTipe').value = u.tipe_pengguna;
    document.getElementById('taxUsrNpwp').value = u.npwp || '';
    document.getElementById('taxUsrTelp').value = u.no_telepon || '';
    document.getElementById('taxUsrAlamat').value = u.alamat || '';
    document.getElementById('taxPenggunaModal').classList.remove('hidden');
}

function closeTaxPenggunaModal() {
    document.getElementById('taxPenggunaModal').classList.add('hidden');
}
</script>
