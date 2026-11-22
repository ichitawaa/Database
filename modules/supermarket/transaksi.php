<?php
/**
 * Modul Transaksi Penjualan (POS) - Sistem Supermarket
 */

$pdo = Database::getSupermarket();

// Handle Checkout Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['pos_checkout'])) {
    $id_kasir = trim($_POST['id_kasir'] ?? '');
    $id_pelanggan = !empty($_POST['id_pelanggan']) ? trim($_POST['id_pelanggan']) : null;
    $metode_pembayaran = trim($_POST['metode_pembayaran'] ?? 'Tunai');
    $itemsJson = $_POST['cart_items'] ?? '[]';
    $cashGiven = (int)($_POST['cash_given'] ?? 0);

    $items = json_decode($itemsJson, true);

    if (empty($id_kasir)) {
        setFlash('error', "Pilih kasir yang bertugas terlebih dahulu!");
        header("Location: index.php?system=supermarket&tab=pos");
        exit;
    }

    if (empty($items) || !is_array($items)) {
        setFlash('error', "Keranjang belanja masih kosong!");
        header("Location: index.php?system=supermarket&tab=pos");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Validasi ketersediaan stok
        $totalBayar = 0;
        foreach ($items as $item) {
            $stmtProd = $pdo->prepare("SELECT `nama_produk`, `harga_jual`, `stok` FROM `produk` WHERE `id_produk` = ? FOR UPDATE");
            $stmtProd->execute([$item['id_produk']]);
            $prod = $stmtProd->fetch();

            if (!$prod) {
                throw new Exception("Produk " . $item['id_produk'] . " tidak ditemukan!");
            }
            if ($prod['stok'] < $item['jumlah']) {
                throw new Exception("Stok untuk " . $prod['nama_produk'] . " tidak mencukupi! Tersedia: " . $prod['stok'] . ", diminta: " . $item['jumlah']);
            }

            $subtotal = $prod['harga_jual'] * $item['jumlah'];
            $totalBayar += $subtotal;
        }

        // 2. Generate ID Transaksi: TRX-YYYYMMDD-XXXX
        $dateStr = date('Ymd');
        $randomSuffix = rand(1000, 9999);
        $id_transaksi = "TRX-" . $dateStr . "-" . $randomSuffix;
        $tanggal_transaksi = date('Y-m-d H:i:s');

        // 3. Insert ke tabel transaksi
        $stmtTrx = $pdo->prepare("INSERT INTO `transaksi` (`id_transaksi`, `tanggal_transaksi`, `id_kasir`, `id_pelanggan`, `total_bayar`, `metode_pembayaran`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtTrx->execute([$id_transaksi, $tanggal_transaksi, $id_kasir, $id_pelanggan, $totalBayar, $metode_pembayaran]);

        // 4. Insert detail_transaksi & kurangi stok produk
        $stmtDetail = $pdo->prepare("INSERT INTO `detail_transaksi` (`id_transaksi`, `id_produk`, `jumlah`, `subtotal`) VALUES (?, ?, ?, ?)");
        $stmtReduceStock = $pdo->prepare("UPDATE `produk` SET `stok` = `stok` - ? WHERE `id_produk` = ?");

        foreach ($items as $item) {
            $stmtProd = $pdo->prepare("SELECT `harga_jual` FROM `produk` WHERE `id_produk` = ?");
            $stmtProd->execute([$item['id_produk']]);
            $harga = $stmtProd->fetchColumn();
            $sub = $harga * $item['jumlah'];

            $stmtDetail->execute([$id_transaksi, $item['id_produk'], $item['jumlah'], $sub]);
            $stmtReduceStock->execute([$item['jumlah'], $item['id_produk']]);
        }

        // 5. Tambah poin loyalitas pelanggan jika member
        if ($id_pelanggan) {
            $poinReward = floor($totalBayar / 10000);
            if ($poinReward > 0) {
                $stmtPoin = $pdo->prepare("UPDATE `pelanggan` SET `poin_member` = `poin_member` + ? WHERE `id_pelanggan` = ?");
                $stmtPoin->execute([$poinReward, $id_pelanggan]);
            }
        }

        $pdo->commit();
        setFlash('success', "Transaksi $id_transaksi berhasil disimpan!");

        // Redirect dengan trigger struk
        header("Location: index.php?system=supermarket&tab=pos&print_trx=$id_transaksi&cash=$cashGiven");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', "Gagal memproses transaksi: " . $e->getMessage());
        header("Location: index.php?system=supermarket&tab=pos");
        exit;
    }
}

// Data Pendukung untuk Form POS
$kasirList = $pdo->query("
    SELECT k.*, kr.nama_karyawan 
    FROM `kasir` k 
    LEFT JOIN `karyawan` kr ON k.id_karyawan = kr.id_karyawan 
    ORDER BY k.`id_kasir` ASC
")->fetchAll();

$pelangganList = $pdo->query("SELECT * FROM `pelanggan` ORDER BY `nama_pelanggan` ASC")->fetchAll();
$produkList = $pdo->query("SELECT * FROM `produk` WHERE `stok` > 0 ORDER BY `nama_produk` ASC")->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Kolom Kiri: Form Input Transaksi (5 Kolom) -->
    <div class="lg:col-span-5 space-y-4">
        <!-- 1. Identitas Kasir & Pelanggan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                <i class="fa-solid fa-user-tag text-emerald-600"></i>
                <span>1. Identitas Transaksi</span>
            </h3>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kasir Bertugas <span class="text-rose-500">*</span></label>
                <select id="posKasirSelect" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white">
                    <?php foreach ($kasirList as $k): ?>
                        <option value="<?= htmlspecialchars($k['id_kasir']) ?>">
                            <?= htmlspecialchars($k['nama_karyawan'] ?? $k['id_kasir']) ?> (<?= htmlspecialchars($k['shift']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Pelanggan (Member) / Umum</label>
                <select id="posPelangganSelect" onchange="onPelangganChange()" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white">
                    <option value="">-- Pelanggan Umum (Non-Member) --</option>
                    <?php foreach ($pelangganList as $p): ?>
                        <option value="<?= htmlspecialchars($p['id_pelanggan']) ?>" data-poin="<?= $p['poin_member'] ?>">
                            <?= htmlspecialchars($p['nama_pelanggan']) ?> (Poin: <?= $p['poin_member'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="posPelangganInfo" class="text-xs text-slate-500 mt-1 italic">Pelanggan Umum (Tanpa akumulasi poin)</p>
            </div>
        </div>

        <!-- 2. Pemilihan Produk -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                <i class="fa-solid fa-barcode text-emerald-600"></i>
                <span>2. Tambah Produk ke Keranjang</span>
            </h3>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Produk</label>
                <select id="posProdukSelect" onchange="onProdukSelectChange()" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white">
                    <option value="">-- Pilih Produk --</option>
                    <?php foreach ($produkList as $pr): ?>
                        <option value="<?= htmlspecialchars($pr['id_produk']) ?>" data-nama="<?= htmlspecialchars($pr['nama_produk']) ?>" data-harga="<?= $pr['harga_jual'] ?>" data-stok="<?= $pr['stok'] ?>">
                            <?= htmlspecialchars($pr['nama_produk']) ?> - <?= formatRupiah($pr['harga_jual']) ?> (Stok: <?= $pr['stok'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Harga Satuan</label>
                    <input type="text" id="posHargaPreview" readonly value="Rp 0" class="w-full px-3 py-2 text-sm rounded-xl bg-slate-50 border border-slate-200 text-slate-600 font-semibold cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Stok Tersedia</label>
                    <input type="text" id="posStokPreview" readonly value="0 item" class="w-full px-3 py-2 text-sm rounded-xl bg-slate-50 border border-slate-200 text-slate-600 font-semibold cursor-not-allowed">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-1">
                <div class="w-1/3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jumlah (Qty)</label>
                    <input type="number" id="posInputQty" value="1" min="1" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-center">
                </div>
                <div class="w-2/3 flex items-end">
                    <button type="button" onclick="addToCart()" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center justify-center space-x-2 shadow-md shadow-emerald-600/20">
                        <i class="fa-solid fa-cart-plus"></i>
                        <span>Masukan Item</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Keranjang Belanja & Form Checkout (7 Kolom) -->
    <div class="lg:col-span-7 space-y-4">
        <form method="POST" id="checkoutForm" onsubmit="return validateCheckout()" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <input type="hidden" name="pos_checkout" value="1">
            <input type="hidden" name="id_kasir" id="formKasirId" value="">
            <input type="hidden" name="id_pelanggan" id="formPelangganId" value="">
            <input type="hidden" name="cart_items" id="formCartItems" value="[]">

            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-basket-shopping text-emerald-600"></i>
                    <span>Keranjang Belanja</span>
                    <span id="posCartCountBadge" class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">0 Item</span>
                </h3>
                <button type="button" onclick="clearCart()" class="text-xs text-rose-500 hover:text-rose-700 font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-trash-can"></i> Kosongkan
                </button>
            </div>

            <!-- Tabel Item Keranjang -->
            <div class="border border-slate-100 rounded-xl overflow-hidden">
                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 text-slate-500 sticky top-0">
                            <tr>
                                <th class="py-2.5 px-3">Produk</th>
                                <th class="py-2.5 px-3 text-right">Harga</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Subtotal</th>
                                <th class="py-2.5 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="posCartTableBody" class="divide-y divide-slate-100">
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-basket-shopping text-2xl mb-1 text-slate-300"></i>
                                    <p>Keranjang masih kosong</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ringkasan Perhitungan -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-2">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Total Item:</span>
                    <span id="posSummaryTotalQty" class="font-bold">0 pcs</span>
                </div>
                <div class="flex justify-between text-base font-extrabold text-slate-800 pt-2 border-t border-slate-200">
                    <span>Total Tagihan:</span>
                    <span id="posSummaryGrandTotal" class="text-emerald-600 text-xl font-black">Rp 0</span>
                </div>
            </div>

            <!-- Opsi Pembayaran & Uang Tunai -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Metode Pembayaran</label>
                    <select name="metode_pembayaran" id="posPaymentMethod" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white">
                        <option value="Tunai">Tunai (Cash)</option>
                        <option value="QRIS">QRIS / E-Wallet</option>
                        <option value="Debit Mandiri/BCA">Kartu Debit</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nominal Uang Diterima (Rp)</label>
                    <input type="number" name="cash_given" id="posCashGiven" oninput="calculateKembalian()" placeholder="Contoh: 100000"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold">
                </div>
            </div>

            <!-- Kembalian Display -->
            <div id="posChangeContainer" class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-slate-600">
                <span class="text-xs font-semibold">Kembalian:</span>
                <span id="posChangeAmount" class="text-lg font-black text-slate-700">Rp 0</span>
            </div>

            <!-- Tombol Simpan Transaksi -->
            <button type="submit" class="w-full py-3.5 px-4 rounded-xl text-base font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center justify-center space-x-2 shadow-lg shadow-emerald-600/30">
                <i class="fa-solid fa-cash-register text-lg"></i>
                <span>Simpan Transaksi & Cetak Struk</span>
            </button>
        </form>
    </div>
</div>

<script>
let cart = [];

function onPelangganChange() {
    const sel = document.getElementById('posPelangganSelect');
    const info = document.getElementById('posPelangganInfo');
    if (!sel || !info) return;

    if (sel.value) {
        const opt = sel.options[sel.selectedIndex];
        const poin = opt.getAttribute('data-poin') || 0;
        info.innerHTML = `<span class="text-emerald-700 font-semibold"><i class="fa-solid fa-id-card mr-1"></i> Member Aktif | Poin Terkumpul: ${poin} Pts</span>`;
    } else {
        info.innerText = 'Pelanggan Umum (Tanpa akumulasi poin)';
    }
}

function onProdukSelectChange() {
    const sel = document.getElementById('posProdukSelect');
    const opt = sel.options[sel.selectedIndex];
    const hargaPreview = document.getElementById('posHargaPreview');
    const stokPreview = document.getElementById('posStokPreview');
    const qtyInput = document.getElementById('posInputQty');

    if (!sel.value) {
        hargaPreview.value = 'Rp 0';
        stokPreview.value = '0 item';
        qtyInput.max = 0;
        return;
    }

    const harga = parseInt(opt.getAttribute('data-harga')) || 0;
    const stok = parseInt(opt.getAttribute('data-stok')) || 0;

    hargaPreview.value = 'Rp ' + harga.toLocaleString('id-ID');
    stokPreview.value = stok + ' pcs';
    qtyInput.max = stok;
    qtyInput.value = stok > 0 ? 1 : 0;
}

function addToCart() {
    const sel = document.getElementById('posProdukSelect');
    if (!sel.value) {
        alert('Pilih produk terlebih dahulu!');
        return;
    }

    const opt = sel.options[sel.selectedIndex];
    const id = sel.value;
    const nama = opt.getAttribute('data-nama');
    const harga = parseInt(opt.getAttribute('data-harga')) || 0;
    const stok = parseInt(opt.getAttribute('data-stok')) || 0;
    const qty = parseInt(document.getElementById('posInputQty').value) || 1;

    const existing = cart.find(c => c.id_produk === id);
    const curQty = existing ? existing.jumlah : 0;

    if (curQty + qty > stok) {
        alert(`Stok tidak mencukupi! Tersedia: ${stok}, di keranjang sudah: ${curQty}`);
        return;
    }

    if (existing) {
        existing.jumlah += qty;
        existing.subtotal = existing.jumlah * existing.harga_jual;
    } else {
        cart.push({
            id_produk: id,
            nama_produk: nama,
            harga_jual: harga,
            jumlah: qty,
            subtotal: qty * harga
        });
    }

    renderCart();
    document.getElementById('posInputQty').value = 1;
}

function updateCartQty(id, delta) {
    const item = cart.find(c => c.id_produk === id);
    if (!item) return;

    const newQty = item.jumlah + delta;
    if (newQty <= 0) {
        cart = cart.filter(c => c.id_produk !== id);
    } else {
        item.jumlah = newQty;
        item.subtotal = item.jumlah * item.harga_jual;
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(c => c.id_produk !== id);
    renderCart();
}

function clearCart() {
    cart = [];
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('posCartTableBody');
    const countBadge = document.getElementById('posCartCountBadge');
    const totalQtyEl = document.getElementById('posSummaryTotalQty');
    const grandTotalEl = document.getElementById('posSummaryGrandTotal');

    const totalQty = cart.reduce((sum, i) => sum + i.jumlah, 0);
    const grandTotal = cart.reduce((sum, i) => sum + i.subtotal, 0);

    countBadge.innerText = cart.length + ' Item';
    totalQtyEl.innerText = totalQty + ' pcs';
    grandTotalEl.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');

    calculateKembalian();

    if (cart.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="py-8 text-center text-slate-400">
                    <i class="fa-solid fa-basket-shopping text-2xl mb-1 text-slate-300"></i>
                    <p>Keranjang masih kosong</p>
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = cart.map(item => `
        <tr class="hover:bg-slate-50">
            <td class="py-2 px-3 font-semibold text-slate-800">
                <div>${item.nama_produk}</div>
                <div class="text-[10px] text-slate-400 font-mono">${item.id_produk}</div>
            </td>
            <td class="py-2 px-3 text-right font-mono">Rp ${item.harga_jual.toLocaleString('id-ID')}</td>
            <td class="py-2 px-3 text-center">
                <div class="inline-flex items-center space-x-1 border border-slate-200 rounded-lg p-0.5">
                    <button type="button" onclick="updateCartQty('${item.id_produk}', -1)" class="w-5 h-5 rounded hover:bg-slate-200 flex items-center justify-center font-bold text-slate-700">-</button>
                    <span class="w-6 text-center font-bold">${item.jumlah}</span>
                    <button type="button" onclick="updateCartQty('${item.id_produk}', 1)" class="w-5 h-5 rounded hover:bg-slate-200 flex items-center justify-center font-bold text-slate-700">+</button>
                </div>
            </td>
            <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">Rp ${item.subtotal.toLocaleString('id-ID')}</td>
            <td class="py-2 px-3 text-center">
                <button type="button" onclick="removeFromCart('${item.id_produk}')" class="text-rose-500 hover:text-rose-700 text-xs p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function calculateKembalian() {
    const grandTotal = cart.reduce((sum, i) => sum + i.subtotal, 0);
    const cashInput = document.getElementById('posCashGiven');
    const changeAmount = document.getElementById('posChangeAmount');
    const changeContainer = document.getElementById('posChangeContainer');

    const cash = parseInt(cashInput.value) || 0;

    if (cash >= grandTotal && grandTotal > 0) {
        const change = cash - grandTotal;
        changeAmount.innerText = 'Rp ' + change.toLocaleString('id-ID');
        changeContainer.className = 'p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-emerald-800';
    } else if (cash > 0 && cash < grandTotal) {
        const kurang = grandTotal - cash;
        changeAmount.innerText = 'Kurang: Rp ' + kurang.toLocaleString('id-ID');
        changeContainer.className = 'p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between text-rose-800';
    } else {
        changeAmount.innerText = 'Rp 0';
        changeContainer.className = 'p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-slate-600';
    }
}

function validateCheckout() {
    if (cart.length === 0) {
        alert('Keranjang belanja masih kosong!');
        return false;
    }

    const kasirId = document.getElementById('posKasirSelect').value;
    const pelId = document.getElementById('posPelangganSelect').value;
    const grandTotal = cart.reduce((sum, i) => sum + i.subtotal, 0);
    const method = document.getElementById('posPaymentMethod').value;
    const cash = parseInt(document.getElementById('posCashGiven').value) || 0;

    if (method === 'Tunai' && cash < grandTotal) {
        alert(`Uang tunai yang diberikan (Rp ${cash.toLocaleString('id-ID')}) kurang dari total tagihan (Rp ${grandTotal.toLocaleString('id-ID')})!`);
        return false;
    }

    document.getElementById('formKasirId').value = kasirId;
    document.getElementById('formPelangganId').value = pelId;
    document.getElementById('formCartItems').value = JSON.stringify(cart);

    return true;
}
</script>
