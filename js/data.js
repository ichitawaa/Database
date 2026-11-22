/**
 * Data Awal / Seed Data untuk Sistem Supermarket dan Sistem Perpajakan
 * Sesuai dengan skema database supermarket.sql dan perpajakan.sql
 */

const INITIAL_DATA = {
  supermarket: {
    karyawan: [
      {
        id_karyawan: "KRY-001",
        nama_karyawan: "Siti Aminah, S.Kom",
        jabatan: "Kasir Senior",
        no_telepon: "081234567890",
        alamat: "Jl. Merdeka No. 12, Gambir, Jakarta Pusat",
        tanggal_masuk: "2023-01-15"
      },
      {
        id_karyawan: "KRY-002",
        nama_karyawan: "Dimas Prasetyo",
        jabatan: "Kasir Junior",
        no_telepon: "082198765432",
        alamat: "Jl. Sudirman No. 45, Kebayoran Baru, Jakarta Selatan",
        tanggal_masuk: "2023-08-20"
      },
      {
        id_karyawan: "KRY-003",
        nama_karyawan: "Rina Maharani",
        jabatan: "Supervisor Kasir",
        no_telepon: "085712348765",
        alamat: "Jl. Gatot Subroto No. 88, Menteng, Jakarta Pusat",
        tanggal_masuk: "2022-05-10"
      },
      {
        id_karyawan: "KRY-004",
        nama_karyawan: "Bambang Pamungkas",
        jabatan: "Kasir Shift Malam",
        no_telepon: "081987654321",
        alamat: "Jl. Fatmawati No. 15, Cilandak, Jakarta Selatan",
        tanggal_masuk: "2024-02-01"
      }
    ],
    kasir: [
      {
        id_kasir: "KAS-01",
        id_karyawan: "KRY-001",
        shift: "Pagi (07:00 - 15:00)",
        no_telepon: "081234567890"
      },
      {
        id_kasir: "KAS-02",
        id_karyawan: "KRY-002",
        shift: "Siang (15:00 - 23:00)",
        no_telepon: "082198765432"
      },
      {
        id_kasir: "KAS-03",
        id_karyawan: "KRY-003",
        shift: "Fleksibel / Supervisor",
        no_telepon: "085712348765"
      },
      {
        id_kasir: "KAS-04",
        id_karyawan: "KRY-004",
        shift: "Malam (23:00 - 07:00)",
        no_telepon: "081987654321"
      }
    ],
    pelanggan: [
      {
        id_pelanggan: "PLG-001",
        nama_pelanggan: "Ahmad Fauzi",
        no_telepon: "081311223344",
        poin_member: 125
      },
      {
        id_pelanggan: "PLG-002",
        nama_pelanggan: "Dewi Lestari",
        no_telepon: "081955667788",
        poin_member: 85
      },
      {
        id_pelanggan: "PLG-003",
        nama_pelanggan: "Rudi Hermawan",
        no_telepon: "087812903456",
        poin_member: 240
      },
      {
        id_pelanggan: "PLG-004",
        nama_pelanggan: "Maya Anggraini",
        no_telepon: "089678123456",
        poin_member: 50
      },
      {
        id_pelanggan: "PLG-005",
        nama_pelanggan: "Ir. Hendra Wijaya",
        no_telepon: "081277889900",
        poin_member: 310
      }
    ],
    produk: [
      {
        id_produk: "PRD-001",
        nama_produk: "Minyak Goreng Bimoli 2 Liter",
        kategori: "Minyak & Bumbu Dapur",
        harga_jual: 38500,
        stok: 45
      },
      {
        id_produk: "PRD-002",
        nama_produk: "Beras Pandan Wangi Premium 5 kg",
        kategori: "Sembako",
        harga_jual: 74000,
        stok: 28
      },
      {
        id_produk: "PRD-003",
        nama_produk: "Indomie Goreng Spesial 85g",
        kategori: "Makanan Instan",
        harga_jual: 3500,
        stok: 250
      },
      {
        id_produk: "PRD-004",
        nama_produk: "Susu Ultra Milk Full Cream 1 Liter",
        kategori: "Susu & Olahan",
        harga_jual: 21000,
        stok: 60
      },
      {
        id_produk: "PRD-005",
        nama_produk: "Gula Pasir Gulaku Premium 1 kg",
        kategori: "Sembako",
        harga_jual: 17500,
        stok: 85
      },
      {
        id_produk: "PRD-006",
        nama_produk: "Telur Ayam Negeri Fresh 1 kg",
        kategori: "Bahan Segar",
        harga_jual: 29500,
        stok: 22
      },
      {
        id_produk: "PRD-007",
        nama_produk: "Sabun Cuci Piring Sunlight Jeruk Nipis 750ml",
        kategori: "Kebersihan & Rumah",
        harga_jual: 15500,
        stok: 40
      },
      {
        id_produk: "PRD-008",
        nama_produk: "Kopi Kapal Api Spesial Mix (Isi 10 Sachet)",
        kategori: "Minuman Kopi & Teh",
        harga_jual: 16000,
        stok: 90
      },
      {
        id_produk: "PRD-009",
        nama_produk: "Teh Botol Sosro Kotak 250ml",
        kategori: "Minuman Ringan",
        harga_jual: 4500,
        stok: 120
      },
      {
        id_produk: "PRD-010",
        nama_produk: "Shampoo Pantene Anti Ketombe 290ml",
        kategori: "Perawatan Diri",
        harga_jual: 42000,
        stok: 15
      }
    ],
    transaksi: [
      {
        id_transaksi: "TRX-20261101",
        tanggal_transaksi: "2026-11-15 10:25:00",
        id_kasir: "KAS-01",
        id_pelanggan: "PLG-001",
        total_bayar: 116000,
        metode_pembayaran: "Tunai",
        catatan: "Pelanggan membayar pas"
      },
      {
        id_transaksi: "TRX-20261102",
        tanggal_transaksi: "2026-11-16 09:15:00",
        id_kasir: "KAS-02",
        id_pelanggan: "PLG-003",
        total_bayar: 148000,
        metode_pembayaran: "QRIS",
        catatan: "Scan QRIS Merchant BCA"
      }
    ],
    detail_transaksi: [
      {
        id_detail: 1,
        id_transaksi: "TRX-20261101",
        id_produk: "PRD-001",
        jumlah: 2,
        subtotal: 77000
      },
      {
        id_detail: 2,
        id_transaksi: "TRX-20261101",
        id_produk: "PRD-005",
        jumlah: 1,
        subtotal: 17500
      },
      {
        id_detail: 3,
        id_transaksi: "TRX-20261101",
        id_produk: "PRD-004",
        jumlah: 1,
        subtotal: 21500
      },
      {
        id_detail: 4,
        id_transaksi: "TRX-20261102",
        id_produk: "PRD-002",
        jumlah: 2,
        subtotal: 148000
      }
    ]
  },

  perpajakan: {
    kategori_pajak: [
      {
        id_kategori: "KAT-01",
        nama_kategori: "Pajak Penghasilan (PPh)",
        keterangan: "Pajak yang dikenakan atas setiap tambahan kemampuan ekonomis yang diterima Wajib Pajak."
      },
      {
        id_kategori: "KAT-02",
        nama_kategori: "Pajak Kebendaan & Properti",
        keterangan: "Pajak atas kepemilikan, penguasaan, dan pemanfaatan tanah, bumi, dan bangunan."
      },
      {
        id_kategori: "KAT-03",
        nama_kategori: "Pajak Konsumsi & Pertambahan Nilai",
        keterangan: "Pajak atas transaksi penyerahan barang kena pajak dan jasa kena pajak dalam daerah pabean."
      },
      {
        id_kategori: "KAT-04",
        nama_kategori: "Pajak Lingkungan & Transisi Hijau",
        keterangan: "Pajak atas aktivitas yang menimbulkan emisi karbon atau dampak terhadap lingkungan hidup."
      }
    ],
    jenis_pajak: [
      {
        id_pajak: "PJK-01",
        id_kategori: "KAT-01",
        nama_pajak: "PPh Pasal 21 (Gaji & Upah)",
        tarif_pajak: "5% s.d. 35% Progresif"
      },
      {
        id_pajak: "PJK-02",
        id_kategori: "KAT-01",
        nama_pajak: "PPh Pasal 23 (Jasa & Royalti)",
        tarif_pajak: "2% s.d. 15%"
      },
      {
        id_pajak: "PJK-03",
        id_kategori: "KAT-01",
        nama_pajak: "PPh Final UMKM (PP 55/2022)",
        tarif_pajak: "0.5% dari Omzet Bruto"
      },
      {
        id_pajak: "PJK-04",
        id_kategori: "KAT-02",
        nama_pajak: "PBB-P2 (Pajak Bumi & Bangunan)",
        tarif_pajak: "0.1% s.d. 0.2% NJOP"
      },
      {
        id_pajak: "PJK-05",
        id_kategori: "KAT-03",
        nama_pajak: "PPN (Pajak Pertambahan Nilai)",
        tarif_pajak: "11% - 12%"
      },
      {
        id_pajak: "PJK-06",
        id_kategori: "KAT-04",
        nama_pajak: "Pajak Karbon (Carbon Tax)",
        tarif_pajak: "Rp 30 per kg CO2e"
      }
    ],
    pengguna: [
      {
        id_pengguna: "USR-001",
        nama_pengguna: "PT. Sumber Makmur Abadi",
        tipe_pengguna: "Perusahaan",
        npwp: "01.345.678.9-012.000",
        no_telepon: "021-5551234",
        alamat: "Kawasan Industri Pulogadung Blok B No. 4, Jakarta Timur"
      },
      {
        id_pengguna: "USR-002",
        nama_pengguna: "Budi Santoso, S.Kom.",
        tipe_pengguna: "Perorangan",
        npwp: "72.891.452.3-411.000",
        no_telepon: "081288990011",
        alamat: "Jl. Melati No. 18, Kebayoran Baru, Jakarta Selatan"
      },
      {
        id_pengguna: "USR-003",
        nama_pengguna: "CV. Berkah Logistik Nusantara",
        tipe_pengguna: "Perusahaan",
        npwp: "02.765.432.1-054.000",
        no_telepon: "021-88992211",
        alamat: "Jl. Raya Narogong KM 12, Rawalumbu, Bekasi"
      },
      {
        id_pengguna: "USR-004",
        nama_pengguna: "dr. Siti Nurhaliza, Sp.A",
        tipe_pengguna: "Perorangan",
        npwp: "81.234.567.8-032.000",
        no_telepon: "081399887766",
        alamat: "Jl. Cempaka Putih Tengah No. 5A, Jakarta Pusat"
      },
      {
        id_pengguna: "USR-005",
        nama_pengguna: "Hendra Gunawan, S.E. (Fiskus)",
        tipe_pengguna: "Petugas Pajak",
        npwp: "93.111.222.3-091.000",
        no_telepon: "081234509876",
        alamat: "KPP Pratama Kebayoran Baru, Jl. Radio Dalam, Jakarta Selatan"
      },
      {
        id_pengguna: "USR-006",
        nama_pengguna: "Anita Kusuma, S.Ak. (Verifikator)",
        tipe_pengguna: "Petugas Pajak",
        npwp: "94.222.333.4-082.000",
        no_telepon: "081377665544",
        alamat: "KPP Pratama Gambir Satu, Jl. Batu Tulis No. 2, Jakarta Pusat"
      }
    ],
    pembayaran_pajak: [
      {
        id_pembayaran: "BYR-20261101",
        tanggal_bayar: "2026-11-10 14:20:00",
        id_pengguna: "USR-001",
        id_pajak: "PJK-05",
        id_petugas: "USR-005",
        jumlah_bayar: 27500000,
        status_pembayaran: "Lunas"
      },
      {
        id_pembayaran: "BYR-20261102",
        tanggal_bayar: "2026-11-14 11:05:00",
        id_pengguna: "USR-002",
        id_pajak: "PJK-01",
        id_petugas: "USR-005",
        jumlah_bayar: 1850000,
        status_pembayaran: "Lunas"
      },
      {
        id_pembayaran: "BYR-20261103",
        tanggal_bayar: "2026-11-16 08:45:00",
        id_pengguna: "USR-003",
        id_pajak: "PJK-06",
        id_petugas: "USR-006",
        jumlah_bayar: 6200000,
        status_pembayaran: "Menunggu Verifikasi"
      },
      {
        id_pembayaran: "BYR-20261104",
        tanggal_bayar: "2026-11-16 10:10:00",
        id_pengguna: "USR-004",
        id_pajak: "PJK-04",
        id_petugas: "USR-006",
        jumlah_bayar: 3450000,
        status_pembayaran: "Tertunda"
      }
    ]
  }
};
