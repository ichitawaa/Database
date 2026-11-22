<?php
/**
 * Modul Pembayaran Pajak - Sistem Perpajakan (CRUD & Transaksi)
 */

$pdo = Database::getPerpajakan();

// Handle Form Submissions (Create / Status Update / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create_payment') {
        $id_pengguna = trim($_POST['id_pengguna'] ?? '');
        $id_pajak = trim($_POST['id_pajak'] ?? '');
        $id_petugas = trim($_POST['id_petugas'] ?? '');
        $jumlah_bayar = (int)($_POST['jumlah_bayar'] ?? 0);
        $status_pembayaran = trim($_POST['status_pembayaran'] ?? 'Lunas');
        $tanggal_bayar = trim($_POST['tanggal_bayar'] ?? '');

        if (empty($tanggal_bayar)) {
            $tanggal_bayar = date('Y-m-d H:i:s');
        } else {
            $tanggal_bayar = str_replace('T', ' ', $tanggal_bayar) . (strlen($tanggal_bayar) === 16 ? ':00' : '');
        }

        $id_pembayaran = 'BYR-' . date('Ymd') . rand(100, 999);

        try {
            $stmt = $pdo->prepare("INSERT INTO `pembayaran_pajak` (`id_pembayaran`, `tanggal_bayar`, `id_pengguna`, `id_pajak`, `id_petugas`, `jumlah_bayar`, `status_pembayaran`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_pembayaran, $tanggal_bayar, $id_pengguna, $id_pajak, $id_petugas, $jumlah_bayar, $status_pembayaran]);

            setFlash('success', "Setoran Pajak $id_pembayaran sebesar " . formatRupiah($jumlah_bayar) . " berhasil disimpan!");
            header("Location: index.php?system=perpajakan&tab=pembayaran&print_tax=$id_pembayaran");
            exit;
        } catch (PDOException $e) {
            setFlash('error', "Gagal mencatat pembayaran pajak: " . $e->getMessage());
            header("Location: index.php?system=perpajakan&tab=pembayaran");
            exit;
        }
    }

    if ($action === 'toggle_status') {
        $id_pembayaran = $_POST['id_pembayaran'] ?? '';
        $new_status = $_POST['new_status'] ?? 'Lunas';
        try {
            $stmt = $pdo->prepare("UPDATE `pembayaran_pajak` SET `status_pembayaran` = ? WHERE `id_pembayaran` = ?");
            $stmt->execute([$new_status, $id_pembayaran]);
            setFlash('info', "Status pembayaran $id_pembayaran diubah menjadi '$new_status'");
        } catch (PDOException $e) {
            setFlash('error', "Gagal mengubah status: " . $e->getMessage());
        }
        header("Location: index.php?system=perpajakan&tab=pembayaran");
        exit;
    }

    if ($action === 'delete') {
        $id_pembayaran = $_POST['id_pembayaran'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM `pembayaran_pajak` WHERE `id_pembayaran` = ?");
            $stmt->execute([$id_pembayaran]);
            setFlash('success', "Catatan pembayaran $id_pembayaran berhasil dihapus!");
        } catch (PDOException $e) {
            setFlash('error', "Gagal menghapus pembayaran: " . $e->getMessage());
        }
        header("Location: index.php?system=perpajakan&tab=pembayaran");
        exit;
    }
}

// Data Wajib Pajak (Hanya Perusahaan & Perorangan)
$wpList = $pdo->query("SELECT * FROM `pengguna` WHERE `tipe_pengguna` IN ('Perusahaan', 'Perorangan') ORDER BY `nama_pengguna` ASC")->fetchAll();

// Data Petugas Pajak
$petugasList = $pdo->query("SELECT * FROM `pengguna` WHERE `tipe_pengguna` = 'Petugas Pajak' ORDER BY `nama_pengguna` ASC")->fetchAll();

// Data Jenis Pajak
$jenisList = $pdo->query("SELECT j.*, k.nama_kategori FROM `jenis_pajak` j LEFT JOIN `kategori_pajak` k ON j.id_kategori = k.id_kategori ORDER BY j.`nama_pajak` ASC")->fetchAll();

// Search & Status Filter
$search = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$query = "
    SELECT p.*, 
           u.nama_pengguna, u.tipe_pengguna, u.npwp AS npwp_pengguna,
           j.nama_pajak, j.tarif_pajak,
           pt.nama_pengguna AS nama_petugas
    FROM `pembayaran_pajak` p
    LEFT JOIN `pengguna` u ON p.id_pengguna = u.id_pengguna
    LEFT JOIN `jenis_pajak` j ON p.id_pajak = j.id_pajak
    LEFT JOIN `pengguna` pt ON p.id_petugas = pt.id_pengguna
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.`id_pembayaran` LIKE ? OR u.`nama_pengguna` LIKE ? OR u.`npwp` LIKE ? OR j.`nama_pajak` LIKE ? OR pt.`nama_pengguna` LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

if (!empty($filterStatus)) {
    $query .= " AND p.`status_pembayaran` = ?";
    $params[] = $filterStatus;
}

$query .= " ORDER BY p.`tanggal_bayar` DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pembayaranList = $stmt->fetchAll();
?>

<div class="space-y-6">
    <!-- Form Input Pembayaran Pajak Baru -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar text-blue-600"></i>
                    <span>Input Setoran / Pembayaran Pajak Baru</span>
                </h3>
                <p class="text-xs text-slate-500">Hubungkan data Wajib Pajak, Jenis Pajak, Petugas Verifikator, dan Nominal Setoran Pajak</p>
            </div>
        </div>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <input type="hidden" name="form_action" value="create_payment">

            <!-- Pilih Wajib Pajak -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Wajib Pajak (WP) <span class="text-rose-500">*</span></label>
                <select name="id_pengguna" id="taxWpSelect" onchange="onTaxWpSelectChange()" required
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                    <?php foreach ($wpList as $w): ?>
                        <option value="<?= htmlspecialchars($w['id_pengguna']) ?>" data-npwp="<?= htmlspecialchars($w['npwp'] ?? '-') ?>">
                            [<?= htmlspecialchars($w['tipe_pengguna']) ?>] <?= htmlspecialchars($w['nama_pengguna']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="taxWpInfo" class="text-xs text-slate-500 mt-1 truncate font-mono">NPWP: -</p>
            </div>

            <!-- Pilih Jenis Pajak -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Pajak <span class="text-rose-500">*</span></label>
                <select name="id_pajak" id="taxPajakSelect" onchange="onTaxPajakSelectChange()" required
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                    <?php foreach ($jenisList as $j): ?>
                        <option value="<?= htmlspecialchars($j['id_pajak']) ?>" data-tarif="<?= htmlspecialchars($j['tarif_pajak']) ?>">
                            <?= htmlspecialchars($j['nama_pajak']) ?> (<?= htmlspecialchars($j['nama_kategori']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="taxTarifInfo" class="text-xs text-blue-600 font-medium mt-1 truncate">Tarif: -</p>
            </div>

            <!-- Pilih Petugas Verifikator -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Petugas Fiskus Verifikator <span class="text-rose-500">*</span></label>
                <select name="id_petugas" required
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                    <?php foreach ($petugasList as $pt): ?>
                        <option value="<?= htmlspecialchars($pt['id_pengguna']) ?>">
                            <?= htmlspecialchars($pt['nama_pengguna']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-slate-400 mt-1">Petugas DJP terdaftar</p>
            </div>

            <!-- Jumlah Bayar -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Bayar Pajak (Rp) <span class="text-rose-500">*</span></label>
                <input type="number" name="jumlah_bayar" required min="1000" placeholder="Contoh: 2500000"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-bold text-slate-800">
            </div>

            <!-- Baris Bawah: Status & Tanggal -->
            <div class="lg:col-span-3 flex flex-col sm:flex-row items-center gap-3">
                <div class="w-full sm:w-1/2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Pembayaran</label>
                    <select name="status_pembayaran"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white font-semibold">
                        <option value="Lunas" class="text-emerald-700">Lunas (Tervalidasi)</option>
                        <option value="Menunggu Verifikasi" class="text-amber-700">Menunggu Verifikasi</option>
                        <option value="Tertunda" class="text-slate-700">Tertunda (Pending)</option>
                    </select>
                </div>
                <div class="w-full sm:w-1/2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal & Waktu Setor</label>
                    <input type="datetime-local" name="tanggal_bayar" id="taxTanggalInput"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                </div>
            </div>

            <div class="lg:col-span-1 flex items-end">
                <button type="submit"
                    class="w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center justify-center space-x-2 shadow-md shadow-blue-600/20">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Simpan Pembayaran</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Pembayaran Pajak -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <form method="GET" class="flex flex-1 flex-col sm:flex-row gap-3">
                <input type="hidden" name="system" value="perpajakan">
                <input type="hidden" name="tab" value="pembayaran">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ID setoran, nama WP, NPWP, jenis pajak..."
                        class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
                <select name="status" onchange="this.form.submit()"
                    class="px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-700 bg-white">
                    <option value="">Semua Status</option>
                    <option value="Lunas" <?= $filterStatus === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                    <option value="Menunggu Verifikasi" <?= $filterStatus === 'Menunggu Verifikasi' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                    <option value="Tertunda" <?= $filterStatus === 'Tertunda' ? 'selected' : '' ?>>Tertunda</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">
                    Cari
                </button>
                <?php if (!empty($search) || !empty($filterStatus)): ?>
                    <a href="index.php?system=perpajakan&tab=pembayaran" class="px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">ID Pembayaran</th>
                            <th class="py-3.5 px-4">Tanggal Setor</th>
                            <th class="py-3.5 px-4">Wajib Pajak & NPWP</th>
                            <th class="py-3.5 px-4">Jenis Pajak</th>
                            <th class="py-3.5 px-4">Petugas Verifikator</th>
                            <th class="py-3.5 px-4 text-right">Jumlah Bayar</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($pembayaranList)): ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-file-circle-xmark text-3xl mb-2 text-slate-300"></i>
                                    <p>Tidak ada data pembayaran pajak yang cocok.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pembayaranList as $p): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-blue-900"><?= htmlspecialchars($p['id_pembayaran']) ?></td>
                                    <td class="py-3 px-4 text-xs font-mono text-slate-600"><?= formatTanggalIndo($p['tanggal_bayar']) ?></td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($p['nama_pengguna']) ?></div>
                                        <div class="text-xs font-mono text-slate-500">NPWP: <?= htmlspecialchars($p['npwp_pengguna'] ?? '-') ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-blue-900"><?= htmlspecialchars($p['nama_pajak']) ?></div>
                                        <div class="text-[11px] text-slate-500">Tarif: <?= htmlspecialchars($p['tarif_pajak']) ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-xs font-medium text-slate-700"><?= htmlspecialchars($p['nama_petugas']) ?></td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700 text-sm"><?= formatRupiah($p['jumlah_bayar']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="form_action" value="toggle_status">
                                            <input type="hidden" name="id_pembayaran" value="<?= $p['id_pembayaran'] ?>">
                                            <?php if ($p['status_pembayaran'] === 'Lunas'): ?>
                                                <input type="hidden" name="new_status" value="Menunggu Verifikasi">
                                                <button type="submit" title="Klik untuk ubah ke Menunggu Verifikasi"
                                                    class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition flex items-center gap-1 mx-auto">
                                                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                                    <span>Lunas</span>
                                                </button>
                                            <?php elseif ($p['status_pembayaran'] === 'Menunggu Verifikasi'): ?>
                                                <input type="hidden" name="new_status" value="Lunas">
                                                <button type="submit" title="Klik untuk validasi Lunas"
                                                    class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 hover:bg-emerald-50 text-amber-800 hover:text-emerald-800 border border-amber-300 hover:border-emerald-300 transition flex items-center gap-1 mx-auto">
                                                    <i class="fa-solid fa-clock text-amber-600"></i>
                                                    <span>Verifikasi</span>
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_status" value="Lunas">
                                                <button type="submit" title="Klik untuk validasi Lunas"
                                                    class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-300 transition flex items-center gap-1 mx-auto">
                                                    <i class="fa-solid fa-hourglass-half text-slate-500"></i>
                                                    <span><?= htmlspecialchars($p['status_pembayaran']) ?></span>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center space-x-1.5">
                                            <button onclick="viewTaxReceipt('<?= $p['id_pembayaran'] ?>')" title="Cetak Bukti Penerimaan Elektronik (BPE)"
                                                class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white transition text-xs font-semibold flex items-center gap-1">
                                                <i class="fa-solid fa-certificate"></i>
                                                <span>BPE</span>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Hapus data pembayaran pajak <?= $p['id_pembayaran'] ?>?');" class="inline">
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="id_pembayaran" value="<?= $p['id_pembayaran'] ?>">
                                                <button type="submit" title="Hapus Data"
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
</div>

<script>
function onTaxWpSelectChange() {
    const sel = document.getElementById('taxWpSelect');
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('taxWpInfo');
    if (opt && info) {
        info.innerText = 'NPWP: ' + (opt.getAttribute('data-npwp') || '-');
    }
}

function onTaxPajakSelectChange() {
    const sel = document.getElementById('taxPajakSelect');
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('taxTarifInfo');
    if (opt && info) {
        info.innerText = 'Ketentuan Tarif: ' + (opt.getAttribute('data-tarif') || '-');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    onTaxWpSelectChange();
    onTaxPajakSelectChange();
    const dtInput = document.getElementById('taxTanggalInput');
    if (dtInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dtInput.value = now.toISOString().slice(0, 16);
    }
});
</script>
