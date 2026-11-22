<?php
/**
 * Modul Kasir & Karyawan - Sistem Supermarket (CRUD)
 */

$pdo = Database::getSupermarket();

// Handle Form Submissions (Add / Edit / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        $id_kasir = trim($_POST['id_kasir'] ?? '');
        $id_karyawan = trim($_POST['id_karyawan'] ?? '');
        $nama_karyawan = trim($_POST['nama_karyawan'] ?? '');
        $shift = trim($_POST['shift'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $is_edit = !empty($_POST['is_edit']);

        try {
            $pdo->beginTransaction();

            if (empty($id_karyawan)) {
                $id_karyawan = generateNextId('KRY', 'karyawan', 'id_karyawan', $pdo);
            }

            // Simpan / update ke tabel karyawan
            $stmtKryCheck = $pdo->prepare("SELECT COUNT(*) FROM `karyawan` WHERE `id_karyawan` = ?");
            $stmtKryCheck->execute([$id_karyawan]);
            $kryExists = $stmtKryCheck->fetchColumn() > 0;

            if ($kryExists) {
                $stmtKry = $pdo->prepare("UPDATE `karyawan` SET `nama_karyawan` = ?, `no_telepon` = ?, `alamat` = ? WHERE `id_karyawan` = ?");
                $stmtKry->execute([$nama_karyawan, $no_telepon, $alamat, $id_karyawan]);
            } else {
                $stmtKry = $pdo->prepare("INSERT INTO `karyawan` (`id_karyawan`, `nama_karyawan`, `jabatan`, `no_telepon`, `alamat`, `tanggal_masuk`) VALUES (?, ?, 'Kasir', ?, ?, CURDATE())");
                $stmtKry->execute([$id_karyawan, $nama_karyawan, $no_telepon, $alamat]);
            }

            // Simpan / update ke tabel kasir
            if (empty($id_kasir)) {
                $id_kasir = generateNextId('KAS', 'kasir', 'id_kasir', $pdo, 2);
            }

            if ($is_edit) {
                $stmtKas = $pdo->prepare("UPDATE `kasir` SET `shift` = ?, `no_telepon` = ? WHERE `id_kasir` = ?");
                $stmtKas->execute([$shift, $no_telepon, $id_kasir]);
                setFlash('success', "Data Kasir '$nama_karyawan' ($id_kasir) berhasil diperbarui!");
            } else {
                $stmtKas = $pdo->prepare("INSERT INTO `kasir` (`id_kasir`, `id_karyawan`, `shift`, `no_telepon`) VALUES (?, ?, ?, ?)");
                $stmtKas->execute([$id_kasir, $id_karyawan, $shift, $no_telepon]);
                setFlash('success', "Kasir baru '$nama_karyawan' ($id_kasir) berhasil ditambahkan!");
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlash('error', "Gagal menyimpan data kasir: " . $e->getMessage());
        }

        header("Location: index.php?system=supermarket&tab=kasir");
        exit;
    }

    if ($action === 'delete') {
        $id_kasir = $_POST['id_kasir'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM `kasir` WHERE `id_kasir` = ?");
            $stmt->execute([$id_kasir]);
            setFlash('success', "Kasir $id_kasir berhasil dihapus!");
        } catch (PDOException $e) {
            setFlash('error', "Gagal menghapus kasir: Kasir ini sudah memiliki riwayat transaksi!");
        }
        header("Location: index.php?system=supermarket&tab=kasir");
        exit;
    }
}

// Filter & Search
$search = trim($_GET['q'] ?? '');
$query = "
    SELECT k.*, kr.nama_karyawan, kr.jabatan, kr.alamat
    FROM `kasir` k
    LEFT JOIN `karyawan` kr ON k.id_karyawan = kr.id_karyawan
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (k.`id_kasir` LIKE ? OR kr.`nama_karyawan` LIKE ? OR k.`shift` LIKE ? OR k.`no_telepon` LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

$query .= " ORDER BY k.`id_kasir` ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kasirList = $stmt->fetchAll();
?>

<div class="space-y-4">
    <!-- Bar Filter & Tombol Tambah -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" class="flex flex-1 gap-3">
            <input type="hidden" name="system" value="supermarket">
            <input type="hidden" name="tab" value="kasir">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ID kasir, nama karyawan, shift..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">
                Cari
            </button>
            <?php if (!empty($search)): ?>
                <a href="index.php?system=supermarket&tab=kasir" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center">
                    Reset
                </a>
            <?php endif; ?>
        </form>

        <button onclick="openKasirModal()"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center justify-center space-x-2 shadow-md shadow-emerald-600/20">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Kasir Baru</span>
        </button>
    </div>

    <!-- Tabel Kasir -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">ID Kasir</th>
                        <th class="py-3.5 px-4">Nama Kasir / Karyawan</th>
                        <th class="py-3.5 px-4">Shift Tugas</th>
                        <th class="py-3.5 px-4">No. Telepon</th>
                        <th class="py-3.5 px-4">Alamat Domisili</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($kasirList)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-cash-register text-3xl mb-2 text-slate-300"></i>
                                <p>Tidak ada data kasir yang ditemukan.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kasirList as $k): ?>
                            <?php
                            $shiftClass = "bg-blue-50 text-blue-700 border-blue-200";
                            if (stripos($k['shift'], 'Pagi') !== false) $shiftClass = "bg-amber-50 text-amber-700 border-amber-200";
                            if (stripos($k['shift'], 'Malam') !== false) $shiftClass = "bg-purple-50 text-purple-700 border-purple-200";
                            ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-700"><?= htmlspecialchars($k['id_kasir']) ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($k['nama_karyawan'] ?? 'Kasir') ?></div>
                                    <div class="text-xs text-slate-400">ID Karyawan: <?= htmlspecialchars($k['id_karyawan']) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $shiftClass ?>">
                                        <?= htmlspecialchars($k['shift']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600"><?= htmlspecialchars($k['no_telepon']) ?></td>
                                <td class="py-3 px-4 text-xs text-slate-500 max-w-xs truncate"><?= htmlspecialchars($k['alamat'] ?? '-') ?></td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick='editKasir(<?= json_encode($k) ?>)' title="Edit Kasir"
                                            class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white transition flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Hapus kasir <?= htmlspecialchars($k['nama_karyawan']) ?> (<?= $k['id_kasir'] ?>)?');" class="inline">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id_kasir" value="<?= $k['id_kasir'] ?>">
                                            <button type="submit" title="Hapus Kasir"
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

<!-- Modal Tambah / Edit Kasir -->
<div id="kasirModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-slide-down">
        <div class="px-6 py-4 bg-emerald-600 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-cash-register text-lg"></i>
                <h3 id="kasirModalTitle" class="text-base font-bold">Form Data Kasir</h3>
            </div>
            <button onclick="closeKasirModal()" class="text-white/80 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="is_edit" id="kasIsEdit" value="0">
            <input type="hidden" name="id_karyawan" id="kasIdKaryawan" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ID Kasir</label>
                    <input type="text" name="id_kasir" id="kasId" placeholder="Otomatis KAS-xx"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Shift Kerja <span class="text-rose-500">*</span></label>
                    <select name="shift" id="kasShift" required
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white">
                        <option value="Pagi (07:00 - 15:00)">Pagi (07:00 - 15:00)</option>
                        <option value="Siang (15:00 - 23:00)">Siang (15:00 - 23:00)</option>
                        <option value="Malam (23:00 - 07:00)">Malam (23:00 - 07:00)</option>
                        <option value="Fleksibel / Supervisor">Fleksibel / Supervisor</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Kasir / Karyawan <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_karyawan" id="kasNama" required placeholder="Nama lengkap..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / WhatsApp <span class="text-rose-500">*</span></label>
                <input type="text" name="no_telepon" id="kasTelp" required placeholder="08xxxxxxxxxx"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Domisili</label>
                <textarea name="alamat" id="kasAlamat" rows="2" placeholder="Alamat tempat tinggal..."
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeKasirModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-md shadow-emerald-600/20">
                    Simpan Kasir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openKasirModal() {
    document.getElementById('kasirModalTitle').innerText = 'Tambah Kasir Baru';
    document.getElementById('kasIsEdit').value = '0';
    document.getElementById('kasId').value = '';
    document.getElementById('kasId').readOnly = false;
    document.getElementById('kasIdKaryawan').value = '';
    document.getElementById('kasNama').value = '';
    document.getElementById('kasShift').value = 'Pagi (07:00 - 15:00)';
    document.getElementById('kasTelp').value = '';
    document.getElementById('kasAlamat').value = '';
    document.getElementById('kasirModal').classList.remove('hidden');
}

function editKasir(k) {
    document.getElementById('kasirModalTitle').innerText = 'Edit Kasir: ' + (k.nama_karyawan || k.id_kasir);
    document.getElementById('kasIsEdit').value = '1';
    document.getElementById('kasId').value = k.id_kasir;
    document.getElementById('kasId').readOnly = true;
    document.getElementById('kasIdKaryawan').value = k.id_karyawan;
    document.getElementById('kasNama').value = k.nama_karyawan || '';
    document.getElementById('kasShift').value = k.shift;
    document.getElementById('kasTelp').value = k.no_telepon;
    document.getElementById('kasAlamat').value = k.alamat || '';
    document.getElementById('kasirModal').classList.remove('hidden');
}

function closeKasirModal() {
    document.getElementById('kasirModal').classList.add('hidden');
}
</script>
