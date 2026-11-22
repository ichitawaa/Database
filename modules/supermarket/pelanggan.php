<?php
/**
 * Modul Pelanggan & Membership - Sistem Supermarket (CRUD)
 */

$pdo = Database::getSupermarket();

// Handle Form Submissions (Add / Edit / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $id_pelanggan = trim($_POST['id_pelanggan'] ?? '');
        $nama_pelanggan = trim($_POST['nama_pelanggan'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $poin_member = (int)($_POST['poin_member'] ?? 0);
        $is_edit = !empty($_POST['is_edit']);

        if (empty($id_pelanggan)) {
            $id_pelanggan = generateNextId('PLG', 'pelanggan', 'id_pelanggan', $pdo);
        }

        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE `pelanggan` SET `nama_pelanggan` = ?, `no_telepon` = ?, `poin_member` = ? WHERE `id_pelanggan` = ?");
                $stmt->execute([$nama_pelanggan, $no_telepon, $poin_member, $id_pelanggan]);
                setFlash('success', "Pelanggan '$nama_pelanggan' ($id_pelanggan) berhasil diperbarui!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `no_telepon`, `poin_member`) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_pelanggan, $nama_pelanggan, $no_telepon, $poin_member]);
                setFlash('success', "Member baru '$nama_pelanggan' ($id_pelanggan) berhasil didaftarkan!");
            }
        } catch (PDOException $e) {
            setFlash('error', "Gagal menyimpan data pelanggan: " . $e->getMessage());
        }

        header("Location: index.php?system=supermarket&tab=pelanggan");
        exit;
    }

    if ($action === 'delete') {
        $id_pelanggan = $_POST['id_pelanggan'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM `pelanggan` WHERE `id_pelanggan` = ?");
            $stmt->execute([$id_pelanggan]);
            setFlash('success', "Pelanggan $id_pelanggan berhasil dihapus!");
        } catch (PDOException $e) {
            setFlash('error', "Gagal menghapus pelanggan: Pelanggan ini sudah memiliki histori transaksi!");
        }
        header("Location: index.php?system=supermarket&tab=pelanggan");
        exit;
    }
}

// Filter & Search
$search = trim($_GET['q'] ?? '');
$query = "SELECT * FROM `pelanggan` WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (`id_pelanggan` LIKE ? OR `nama_pelanggan` LIKE ? OR `no_telepon` LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$query .= " ORDER BY `id_pelanggan` ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pelangganList = $stmt->fetchAll();
?>

<div class="space-y-4">
    <!-- Bar Filter & Tombol Tambah -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" class="flex flex-1 gap-3">
            <input type="hidden" name="system" value="supermarket">
            <input type="hidden" name="tab" value="pelanggan">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ID member, nama, no. telepon..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">
                Cari
            </button>
            <?php if (!empty($search)): ?>
                <a href="index.php?system=supermarket&tab=pelanggan" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center">
                    Reset
                </a>
            <?php endif; ?>
        </form>

        <button onclick="openPelangganModal()"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center justify-center space-x-2 shadow-md shadow-emerald-600/20">
            <i class="fa-solid fa-user-plus"></i>
            <span>Tambah Member Baru</span>
        </button>
    </div>

    <!-- Tabel Pelanggan -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">ID Pelanggan</th>
                        <th class="py-3.5 px-4">Nama Pelanggan</th>
                        <th class="py-3.5 px-4">No. Telepon</th>
                        <th class="py-3.5 px-4 text-center">Poin Member</th>
                        <th class="py-3.5 px-4 text-center">Tingkat Member</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($pelangganList)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-users-slash text-3xl mb-2 text-slate-300"></i>
                                <p>Tidak ada data pelanggan yang cocok.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pelangganList as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-700"><?= htmlspecialchars($p['id_pelanggan']) ?></td>
                                <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($p['nama_pelanggan']) ?></td>
                                <td class="py-3 px-4 font-mono text-slate-600"><?= htmlspecialchars($p['no_telepon'] ?? '-') ?></td>
                                <td class="py-3 px-4 text-center font-mono font-black text-emerald-700"><?= $p['poin_member'] ?> Pts</td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($p['poin_member'] >= 200): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                            <i class="fa-solid fa-crown text-amber-500 mr-1"></i>Platinum
                                        </span>
                                    <?php elseif ($p['poin_member'] >= 100): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-50 text-yellow-800 border border-yellow-200">
                                            <i class="fa-solid fa-medal text-yellow-500 mr-1"></i>Gold
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            Silver Member
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick='editPelanggan(<?= json_encode($p) ?>)' title="Edit Member"
                                            class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white transition flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Hapus pelanggan <?= htmlspecialchars($p['nama_pelanggan']) ?> (<?= $p['id_pelanggan'] ?>)?');" class="inline">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_pelanggan" value="<?= $p['id_pelanggan'] ?>">
                                            <button type="submit" title="Hapus Member"
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

<!-- Modal Tambah / Edit Pelanggan -->
<div id="pelangganModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-slide-down">
        <div class="px-6 py-4 bg-emerald-600 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-users text-lg"></i>
                <h3 id="pelangganModalTitle" class="text-base font-bold">Form Data Member</h3>
            </div>
            <button onclick="closePelangganModal()" class="text-white/80 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="is_edit" id="pelIsEdit" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ID Pelanggan</label>
                    <input type="text" name="id_pelanggan" id="pelId" placeholder="Otomatis PLG-xxx"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Poin Member</label>
                    <input type="number" name="poin_member" id="pelPoin" min="0" value="0"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Pelanggan <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_pelanggan" id="pelNama" required placeholder="Nama member..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / WhatsApp <span class="text-rose-500">*</span></label>
                <input type="text" name="no_telepon" id="pelTelp" required placeholder="08xxxxxxxxxx"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closePelangganModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-md shadow-emerald-600/20">
                    Simpan Member
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPelangganModal() {
    document.getElementById('pelangganModalTitle').innerText = 'Tambah Member Baru';
    document.getElementById('pelIsEdit').value = '0';
    document.getElementById('pelId').value = '';
    document.getElementById('pelId').readOnly = false;
    document.getElementById('pelNama').value = '';
    document.getElementById('pelTelp').value = '';
    document.getElementById('pelPoin').value = '0';
    document.getElementById('pelangganModal').classList.remove('hidden');
}

function editPelanggan(p) {
    document.getElementById('pelangganModalTitle').innerText = 'Edit Member: ' + p.nama_pelanggan;
    document.getElementById('pelIsEdit').value = '1';
    document.getElementById('pelId').value = p.id_pelanggan;
    document.getElementById('pelId').readOnly = true;
    document.getElementById('pelNama').value = p.nama_pelanggan;
    document.getElementById('pelTelp').value = p.no_telepon || '';
    document.getElementById('pelPoin').value = p.poin_member;
    document.getElementById('pelangganModal').classList.remove('hidden');
}

function closePelangganModal() {
    document.getElementById('pelangganModal').classList.add('hidden');
}
</script>
