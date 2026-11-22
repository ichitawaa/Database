<?php
/**
 * Central JSON API Handler for AJAX Requests
 * Mendukung pencarian, data receipt, validasi stok, dan toggle status
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$smPdo = Database::getSupermarket();
$taxPdo = Database::getPerpajakan();

if (!$smPdo || !$taxPdo) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . Database::getError()]);
    exit;
}

try {
    switch ($action) {
        // Ambil info produk berdasarkan ID (untuk POS)
        case 'get_produk':
            $id = $_GET['id'] ?? '';
            $stmt = $smPdo->prepare("SELECT * FROM `produk` WHERE `id_produk` = ?");
            $stmt->execute([$id]);
            $prod = $stmt->fetch();
            if ($prod) {
                echo json_encode(['success' => true, 'data' => $prod]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            }
            break;

        // Ambil detail struk belanja transaksi
        case 'get_transaksi_detail':
            $id = $_GET['id'] ?? '';
            $stmt = $smPdo->prepare("
                SELECT t.*, k.shift, kr.nama_karyawan, p.nama_pelanggan, p.poin_member
                FROM `transaksi` t
                LEFT JOIN `kasir` k ON t.id_kasir = k.id_kasir
                LEFT JOIN `karyawan` kr ON k.id_karyawan = kr.id_karyawan
                LEFT JOIN `pelanggan` p ON t.id_pelanggan = p.id_pelanggan
                WHERE t.id_transaksi = ?
            ");
            $stmt->execute([$id]);
            $trx = $stmt->fetch();

            if (!$trx) {
                echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
                exit;
            }

            $stmtItems = $smPdo->prepare("
                SELECT d.*, pr.nama_produk, pr.harga_jual
                FROM `detail_transaksi` d
                LEFT JOIN `produk` pr ON d.id_produk = pr.id_produk
                WHERE d.id_transaksi = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll();

            echo json_encode([
                'success' => true,
                'transaksi' => $trx,
                'items' => $items
            ]);
            break;

        // Ambil detail BPE Bukti Pembayaran Pajak
        case 'get_tax_detail':
            $id = $_GET['id'] ?? '';
            $stmt = $taxPdo->prepare("
                SELECT p.*, 
                       u.nama_pengguna, u.tipe_pengguna, u.npwp AS npwp_pengguna, u.alamat AS alamat_pengguna,
                       j.nama_pajak, j.tarif_pajak,
                       pt.nama_pengguna AS nama_petugas
                FROM `pembayaran_pajak` p
                LEFT JOIN `pengguna` u ON p.id_pengguna = u.id_pengguna
                LEFT JOIN `jenis_pajak` j ON p.id_pajak = j.id_pajak
                LEFT JOIN `pengguna` pt ON p.id_petugas = pt.id_pengguna
                WHERE p.id_pembayaran = ?
            ");
            $stmt->execute([$id]);
            $pay = $stmt->fetch();

            if ($pay) {
                $pay['terbilang'] = terbilang($pay['jumlah_bayar']) . ' Rupiah';
                $pay['jumlah_formatted'] = formatRupiah($pay['jumlah_bayar']);
                echo json_encode(['success' => true, 'data' => $pay]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data pembayaran pajak tidak ditemukan']);
            }
            break;

        // Toggle status pembayaran pajak cepat
        case 'toggle_tax_status':
            $id = $_POST['id'] ?? '';
            $newStatus = $_POST['status'] ?? 'Lunas';
            $stmt = $taxPdo->prepare("UPDATE `pembayaran_pajak` SET `status_pembayaran` = ? WHERE `id_pembayaran` = ?");
            $stmt->execute([$newStatus, $id]);
            echo json_encode(['success' => true, 'new_status' => $newStatus]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Aksi API tidak valid']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
