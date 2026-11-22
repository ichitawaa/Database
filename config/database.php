<?php
/**
 * Konfigurasi Database MySQL (PDO)
 * Mengelola koneksi ke database 'supermarket' dan 'perpajakan'
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME_SUPERMARKET', 'supermarket');
define('DB_NAME_PERPAJAKAN', 'perpajakan');

class Database {
    private static ?PDO $pdoSupermarket = null;
    private static ?PDO $pdoPerpajakan = null;
    private static ?string $connectionError = null;

    /**
     * Dapatkan koneksi PDO ke database Supermarket
     */
    public static function getSupermarket(): ?PDO {
        if (self::$pdoSupermarket === null) {
            try {
                self::ensureDatabasesAndTables();
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME_SUPERMARKET . ";charset=utf8mb4";
                self::$pdoSupermarket = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                self::$connectionError = $e->getMessage();
                return null;
            }
        }
        return self::$pdoSupermarket;
    }

    /**
     * Dapatkan koneksi PDO ke database Perpajakan
     */
    public static function getPerpajakan(): ?PDO {
        if (self::$pdoPerpajakan === null) {
            try {
                self::ensureDatabasesAndTables();
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME_PERPAJAKAN . ";charset=utf8mb4";
                self::$pdoPerpajakan = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                self::$connectionError = $e->getMessage();
                return null;
            }
        }
        return self::$pdoPerpajakan;
    }

    public static function getError(): ?string {
        return self::$connectionError;
    }

    /**
     * Memastikan database dan tabel sudah ada, serta melakukan auto-seeding jika masih kosong
     */
    public static function ensureDatabasesAndTables(): void {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            $rootPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Buat database jika belum ada
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME_SUPERMARKET . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME_PERPAJAKAN . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");

            // Hubungkan ke Supermarket & buat tabel jika belum ada
            $smPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME_SUPERMARKET . ";charset=utf8mb4", DB_USER, DB_PASS);
            $smPdo->exec("CREATE TABLE IF NOT EXISTS `karyawan` (
                `id_karyawan` varchar(10) NOT NULL PRIMARY KEY,
                `nama_karyawan` varchar(100) NOT NULL,
                `jabatan` varchar(50) NOT NULL,
                `no_telepon` varchar(15) DEFAULT NULL,
                `alamat` text,
                `tanggal_masuk` date NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $smPdo->exec("CREATE TABLE IF NOT EXISTS `kasir` (
                `id_kasir` varchar(10) NOT NULL PRIMARY KEY,
                `id_karyawan` varchar(10) NOT NULL,
                `shift` varchar(50) NOT NULL,
                `no_telepon` varchar(15) NOT NULL,
                FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Pastikan panjang kolom shift cukup
            try { $smPdo->exec("ALTER TABLE `kasir` MODIFY `shift` varchar(50) NOT NULL;"); } catch (Exception $e) {}

            $smPdo->exec("CREATE TABLE IF NOT EXISTS `pelanggan` (
                `id_pelanggan` varchar(10) NOT NULL PRIMARY KEY,
                `nama_pelanggan` varchar(100) NOT NULL,
                `no_telepon` varchar(15) DEFAULT NULL,
                `poin_member` int NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $smPdo->exec("CREATE TABLE IF NOT EXISTS `produk` (
                `id_produk` varchar(10) NOT NULL PRIMARY KEY,
                `nama_produk` varchar(150) NOT NULL,
                `kategori` varchar(50) NOT NULL,
                `harga_jual` int NOT NULL,
                `stok` int NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $smPdo->exec("CREATE TABLE IF NOT EXISTS `transaksi` (
                `id_transaksi` varchar(15) NOT NULL PRIMARY KEY,
                `tanggal_transaksi` datetime NOT NULL,
                `id_kasir` varchar(10) NOT NULL,
                `id_pelanggan` varchar(10) DEFAULT NULL,
                `total_bayar` int NOT NULL,
                `metode_pembayaran` varchar(20) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $smPdo->exec("CREATE TABLE IF NOT EXISTS `detail_transaksi` (
                `id_detail` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `id_transaksi` varchar(15) NOT NULL,
                `id_produk` varchar(10) NOT NULL,
                `jumlah` int NOT NULL,
                `subtotal` int NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Hubungkan ke Perpajakan & buat tabel jika belum ada
            $taxPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME_PERPAJAKAN . ";charset=utf8mb4", DB_USER, DB_PASS);
            $taxPdo->exec("CREATE TABLE IF NOT EXISTS `kategori_pajak` (
                `id_kategori` varchar(10) NOT NULL PRIMARY KEY,
                `nama_kategori` varchar(50) NOT NULL,
                `keterangan` text
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $taxPdo->exec("CREATE TABLE IF NOT EXISTS `jenis_pajak` (
                `id_pajak` varchar(10) NOT NULL PRIMARY KEY,
                `id_kategori` varchar(10) NOT NULL,
                `nama_pajak` varchar(100) NOT NULL,
                `tarif_pajak` varchar(50) NOT NULL,
                FOREIGN KEY (`id_kategori`) REFERENCES `kategori_pajak` (`id_kategori`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $taxPdo->exec("CREATE TABLE IF NOT EXISTS `pengguna` (
                `id_pengguna` varchar(10) NOT NULL PRIMARY KEY,
                `nama_pengguna` varchar(100) NOT NULL,
                `tipe_pengguna` varchar(30) NOT NULL,
                `npwp` varchar(25) UNIQUE DEFAULT NULL,
                `no_telepon` varchar(15) DEFAULT NULL,
                `alamat` text
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $taxPdo->exec("CREATE TABLE IF NOT EXISTS `pembayaran_pajak` (
                `id_pembayaran` varchar(15) NOT NULL PRIMARY KEY,
                `tanggal_bayar` datetime NOT NULL,
                `id_pengguna` varchar(10) NOT NULL,
                `id_pajak` varchar(10) NOT NULL,
                `id_petugas` varchar(10) NOT NULL,
                `jumlah_bayar` int NOT NULL,
                `status_pembayaran` varchar(20) NOT NULL,
                FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`),
                FOREIGN KEY (`id_pajak`) REFERENCES `jenis_pajak` (`id_pajak`),
                FOREIGN KEY (`id_petugas`) REFERENCES `pengguna` (`id_pengguna`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Seed data awal jika tabel produk masih kosong
            self::seedInitialData($smPdo, $taxPdo);

        } catch (PDOException $e) {
            self::$connectionError = $e->getMessage();
        }
    }

    /**
     * Seed data realistis jika database masih kosong
     */
    public static function seedInitialData(PDO $smPdo, PDO $taxPdo): void {
        // Cek produk
        $prodCount = $smPdo->query("SELECT COUNT(*) FROM `produk`")->fetchColumn();
        if ($prodCount == 0) {
            $smPdo->exec("INSERT INTO `karyawan` (`id_karyawan`, `nama_karyawan`, `jabatan`, `no_telepon`, `alamat`, `tanggal_masuk`) VALUES
                ('KRY-001', 'Siti Aminah, S.Kom', 'Kasir Senior', '081234567890', 'Jl. Merdeka No. 12 Jakarta', '2023-01-15'),
                ('KRY-002', 'Dimas Prasetyo', 'Kasir Junior', '082198765432', 'Jl. Sudirman No. 45 Jakarta', '2023-08-20'),
                ('KRY-003', 'Rina Maharani', 'Supervisor Kasir', '085712348765', 'Jl. Gatot Subroto No. 88 Jakarta', '2022-05-10'),
                ('KRY-004', 'Bambang Pamungkas', 'Kasir Shift Malam', '081987654321', 'Jl. Fatmawati No. 15 Jakarta', '2024-02-01')
                ON DUPLICATE KEY UPDATE `nama_karyawan` = VALUES(`nama_karyawan`);");

            $smPdo->exec("INSERT INTO `kasir` (`id_kasir`, `id_karyawan`, `shift`, `no_telepon`) VALUES
                ('KAS-01', 'KRY-001', 'Pagi (07:00 - 15:00)', '081234567890'),
                ('KAS-02', 'KRY-002', 'Siang (15:00 - 23:00)', '082198765432'),
                ('KAS-03', 'KRY-003', 'Fleksibel / Supervisor', '085712348765'),
                ('KAS-04', 'KRY-004', 'Malam (23:00 - 07:00)', '081987654321')
                ON DUPLICATE KEY UPDATE `shift` = VALUES(`shift`);");

            $smPdo->exec("INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `no_telepon`, `poin_member`) VALUES
                ('PLG-001', 'Ahmad Fauzi', '081311223344', 125),
                ('PLG-002', 'Dewi Lestari', '081955667788', 85),
                ('PLG-003', 'Rudi Hermawan', '087812903456', 240),
                ('PLG-004', 'Maya Anggraini', '089678123456', 50),
                ('PLG-005', 'Ir. Hendra Wijaya', '081277889900', 310)
                ON DUPLICATE KEY UPDATE `nama_pelanggan` = VALUES(`nama_pelanggan`);");

            $smPdo->exec("INSERT INTO `produk` (`id_produk`, `nama_produk`, `kategori`, `harga_jual`, `stok`) VALUES
                ('PRD-001', 'Minyak Goreng Bimoli 2 Liter', 'Minyak & Bumbu Dapur', 38500, 45),
                ('PRD-002', 'Beras Pandan Wangi Premium 5 kg', 'Sembako', 74000, 28),
                ('PRD-003', 'Indomie Goreng Spesial 85g', 'Makanan Instan', 3500, 250),
                ('PRD-004', 'Susu Ultra Milk Full Cream 1 Liter', 'Susu & Olahan', 21000, 60),
                ('PRD-005', 'Gula Pasir Gulaku Premium 1 kg', 'Sembako', 17500, 85),
                ('PRD-006', 'Telur Ayam Negeri Fresh 1 kg', 'Bahan Segar', 29500, 22),
                ('PRD-007', 'Sabun Cuci Piring Sunlight 750ml', 'Kebersihan & Rumah', 15500, 40),
                ('PRD-008', 'Kopi Kapal Api Spesial Mix 10s', 'Minuman Kopi & Teh', 16000, 90),
                ('PRD-009', 'Teh Botol Sosro Kotak 250ml', 'Minuman Ringan', 4500, 120),
                ('PRD-010', 'Shampoo Pantene 290ml', 'Perawatan Diri', 42000, 15)
                ON DUPLICATE KEY UPDATE `nama_produk` = VALUES(`nama_produk`);");

            $smPdo->exec("INSERT INTO `transaksi` (`id_transaksi`, `tanggal_transaksi`, `id_kasir`, `id_pelanggan`, `total_bayar`, `metode_pembayaran`) VALUES
                ('TRX-20261101', '2026-11-15 10:25:00', 'KAS-01', 'PLG-001', 116000, 'Tunai'),
                ('TRX-20261102', '2026-11-16 09:15:00', 'KAS-02', 'PLG-003', 148000, 'QRIS')
                ON DUPLICATE KEY UPDATE `total_bayar` = VALUES(`total_bayar`);");

            $smPdo->exec("INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `jumlah`, `subtotal`) VALUES
                (1, 'TRX-20261101', 'PRD-001', 2, 77000),
                (2, 'TRX-20261101', 'PRD-005', 1, 17500),
                (3, 'TRX-20261101', 'PRD-004', 1, 21500),
                (4, 'TRX-20261102', 'PRD-002', 2, 148000)
                ON DUPLICATE KEY UPDATE `subtotal` = VALUES(`subtotal`);");
        }

        // Cek kategori pajak
        $taxCount = $taxPdo->query("SELECT COUNT(*) FROM `kategori_pajak`")->fetchColumn();
        if ($taxCount == 0) {
            $taxPdo->exec("INSERT INTO `kategori_pajak` (`id_kategori`, `nama_kategori`, `keterangan`) VALUES
                ('KAT-01', 'Pajak Penghasilan (PPh)', 'Pajak atas setiap tambahan kemampuan ekonomis yang diterima Wajib Pajak.'),
                ('KAT-02', 'Pajak Kebendaan & Properti', 'Pajak atas penguasaan, pemanfaatan tanah, bumi, dan bangunan.'),
                ('KAT-03', 'Pajak Konsumsi & Pertambahan Nilai', 'Pajak atas transaksi barang dan jasa kena pajak dalam daerah pabean.'),
                ('KAT-04', 'Pajak Lingkungan & Transisi Hijau', 'Pajak atas emisi karbon dan pemanfaatan sumber daya alam.')
                ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);");

            $taxPdo->exec("INSERT INTO `jenis_pajak` (`id_pajak`, `id_kategori`, `nama_pajak`, `tarif_pajak`) VALUES
                ('PJK-01', 'KAT-01', 'PPh Pasal 21 (Gaji & Upah)', '5% s.d. 35% Progresif'),
                ('PJK-02', 'KAT-01', 'PPh Pasal 23 (Jasa & Royalti)', '2% s.d. 15%'),
                ('PJK-03', 'KAT-01', 'PPh Final UMKM (PP 55/2022)', '0.5% Omzet Bruto'),
                ('PJK-04', 'KAT-02', 'PBB-P2 (Pajak Bumi & Bangunan)', '0.1% s.d. 0.2% NJOP'),
                ('PJK-05', 'KAT-03', 'PPN (Pajak Pertambahan Nilai)', '11% - 12%'),
                ('PJK-06', 'KAT-04', 'Pajak Karbon (Carbon Tax)', 'Rp 30 per kg CO2e')
                ON DUPLICATE KEY UPDATE `nama_pajak` = VALUES(`nama_pajak`);");

            $taxPdo->exec("INSERT INTO `pengguna` (`id_pengguna`, `nama_pengguna`, `tipe_pengguna`, `npwp`, `no_telepon`, `alamat`) VALUES
                ('USR-001', 'PT. Sumber Makmur Abadi', 'Perusahaan', '01.345.678.9-012.000', '021-5551234', 'Kawasan Industri Pulogadung, Jakarta Timur'),
                ('USR-002', 'Budi Santoso, S.Kom.', 'Perorangan', '72.891.452.3-411.000', '081288990011', 'Jl. Melati No. 18, Kebayoran Baru, Jakarta Selatan'),
                ('USR-003', 'CV. Berkah Logistik Nusantara', 'Perusahaan', '02.765.432.1-054.000', '021-88992211', 'Jl. Raya Narogong KM 12, Bekasi'),
                ('USR-004', 'dr. Siti Nurhaliza, Sp.A', 'Perorangan', '81.234.567.8-032.000', '081399887766', 'Jl. Cempaka Putih Tengah, Jakarta Pusat'),
                ('USR-005', 'Hendra Gunawan, S.E. (Fiskus)', 'Petugas Pajak', '93.111.222.3-091.000', '081234509876', 'KPP Pratama Kebayoran Baru, Jakarta Selatan'),
                ('USR-006', 'Anita Kusuma, S.Ak. (Verifikator)', 'Petugas Pajak', '94.222.333.4-082.000', '081377665544', 'KPP Pratama Gambir Satu, Jakarta Pusat')
                ON DUPLICATE KEY UPDATE `nama_pengguna` = VALUES(`nama_pengguna`);");

            $taxPdo->exec("INSERT INTO `pembayaran_pajak` (`id_pembayaran`, `tanggal_bayar`, `id_pengguna`, `id_pajak`, `id_petugas`, `jumlah_bayar`, `status_pembayaran`) VALUES
                ('BYR-20261101', '2026-11-10 14:20:00', 'USR-001', 'PJK-05', 'USR-005', 27500000, 'Lunas'),
                ('BYR-20261102', '2026-11-14 11:05:00', 'USR-002', 'PJK-01', 'USR-005', 1850000, 'Lunas'),
                ('BYR-20261103', '2026-11-16 08:45:00', 'USR-003', 'PJK-06', 'USR-006', 6200000, 'Menunggu Verifikasi'),
                ('BYR-20261104', '2026-11-16 10:10:00', 'USR-004', 'PJK-04', 'USR-006', 3450000, 'Tertunda')
                ON DUPLICATE KEY UPDATE `status_pembayaran` = VALUES(`status_pembayaran`);");
        }
    }
}
