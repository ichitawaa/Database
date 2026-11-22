# Aplikasi Web Manajemen Database: Supermarket & Perpajakan (PHP + MySQL di Laragon)

Aplikasi web interaktif lengkap (**Full CRUD & Transaksi POS**) berbasis **PHP 8.2 & MySQL (Native PDO)** dengan antarmuka modern menggunakan **Tailwind CSS**. Aplikasi ini mengintegrasikan dua sistem database relasional sekaligus dalam satu project:
1. **Sistem Supermarket** (Terhubung ke database `supermarket`)
2. **Sistem Perpajakan** (Terhubung ke database `perpajakan`)

---

## ⚡ Penggunaan di Laragon

Project ini sudah disalin langsung ke direktori web **Laragon** Anda di:
📂 `C:\laragon\www\supermarket-perpajakan\`

### Langkah Menjalankan:
1. Buka aplikasi **Laragon**.
2. Klik tombol **Start All** (memulai Apache/Nginx dan MySQL).
3. Buka browser Anda dan akses salah satu alamat berikut:
   * **`http://localhost/supermarket-perpajakan`**
   * atau jika virtual host aktif: **`http://supermarket-perpajakan.test`**

### Konfigurasi Database di Laragon (Otomatis):
* **Host**: `127.0.0.1`
* **Port**: `3306`
* **Username**: `root`
* **Password**: *(kosong - standar bawaan Laragon)*
* **Database**: `supermarket` & `perpajakan` *(sudah otomatis dibuat dan diisi sample data)*

---

## 🌟 Fitur & Modul Aplikasi

### 1. Sistem Supermarket (Tema Emerald Green)
* **Master Produk (CRUD)**:
  * Tambah, edit, dan hapus produk (`id_produk`, `nama_produk`, `kategori`, `harga_jual`, `stok`).
  * Filter kategori produk dan live search.
  * Indikator status stok visual (*Tersedia*, *Menipis $\le 20$*, *Habis*).
* **Data Kasir & Karyawan (CRUD)**:
  * Pengelolaan data kasir (`id_kasir`, relasi `id_karyawan`, `shift`, `no_telepon`, `alamat`).
* **Data Pelanggan & Membership (CRUD)**:
  * Pengelolaan member (`id_pelanggan`, `nama_pelanggan`, `no_telepon`, `poin_member`).
  * Tingkatan membership otomatis (*Silver*, *Gold*, *Platinum*).
* **Halaman Transaksi Penjualan (POS)**:
  * Pilih kasir yang bertugas dan pilih pelanggan (member atau non-member).
  * Pilih produk dari database dengan validasi stok real-time.
  * Keranjang belanja dinamis (subtotal & total tagihan otomatis).
  * Input nominal uang tunai dan hitung kembalian otomatis.
  * Berbagai opsi pembayaran (*Tunai*, *QRIS*, *Debit*, *Transfer*).
  * Pengurangan stok produk otomatis dan reward poin member yang tersimpan secara atomik (`PDO Transaction`).
  * **Cetak Struk Belanja Kasir**: Struk belanja format kasir (*thermal receipt*) siap cetak.
* **Riwayat Transaksi**:
  * Rekap transaksi penjualan lengkap dengan rincian item dan opsi cetak ulang struk.

---

### 2. Sistem Perpajakan (Tema Royal Blue)
* **Kategori Pajak (CRUD)**:
  * Pengelolaan kategori (`id_kategori`, `nama_kategori`, `keterangan` regulasi).
  * Penghitung otomatis jumlah jenis pajak yang terdaftar di bawah setiap kategori.
* **Jenis Pajak & Tarif (CRUD)**:
  * Master jenis pajak (PPh 21, PPh 23, PPh Final UMKM, PBB-P2, PPN 11%, Pajak Karbon).
  * Relasi foreign key ke tabel Kategori Pajak.
  * Informasi ketentuan tarif standar resmi.
* **Pengguna / Wajib Pajak (CRUD)**:
  * Klasifikasi pengguna:
    1. **Perusahaan** (Badan Usaha)
    2. **Perorangan** (Orang Pribadi)
    3. **Petugas Pajak** (Fiskus / Verifikator Resmi)
  * Validasi nomor NPWP unik.
* **Halaman Pembayaran Pajak**:
  * Form input setoran pajak yang menghubungkan:
    * Wajib Pajak pembayar (otomatis menampilkan NPWP)
    * Jenis pajak yang disetorkan (otomatis menampilkan tarif)
    * Petugas verifikator yang berwenang
    * Nominal setoran pajak dan status (*Lunas*, *Menunggu Verifikasi*, *Tertunda*)
  * Quick action toggle status pembayaran langsung di tabel.
  * **Cetak Bukti Penerimaan Elektronik (BPE)**: Lembaran BPE resmi DJP lengkap dengan NTPN, NTB, tanda pengesahan elektronik, dan konversi nominal terbilang dalam bahasa Indonesia.

---

### 3. Fitur Tambahan
* **Export SQL Dump (`export_sql.php`)**:
  * Tombol di navigasi atas untuk langsung mengunduh file `.sql` data terbaru untuk dimasukkan ke HeidiSQL / phpMyAdmin di Laragon.
* **Auto-Seeding & Database Check**:
  * Saat aplikasi pertama kali dibuka, PHP secara otomatis membuat tabel jika belum ada dan mengisi data awal realistis Indonesia.
* **Notifikasi Flash Message**:
  * Notifikasi hijau/merah di bagian atas untuk setiap aksi tambah, edit, hapus, maupun checkout.

---

## 📁 Struktur Berkas Proyek di Laragon

```
C:\laragon\www\supermarket-perpajakan\
├── config/
│   ├── database.php        # Konfigurasi PDO MySQL & Auto-seeder data
│   └── helper.php          # Fungsi helper (Rupiah, Terbilang, Flash Message, Sanitasi)
├── api/
│   └── handler.php         # Endpoint JSON API untuk detail struk kasir & bukti BPE
├── includes/
│   ├── header.php          # Layout header & navigasi sistem/tab
│   └── footer.php          # Layout footer & modal struk/BPE
├── modules/
│   ├── supermarket/
│   │   ├── produk.php      # CRUD Master Produk
│   │   ├── kasir.php       # CRUD Data Kasir
│   │   ├── pelanggan.php   # CRUD Data Pelanggan Member
│   │   ├── transaksi.php   # Halaman Transaksi Kasir POS
│   │   └── riwayat.php     # Riwayat Transaksi Penjualan
│   └── perpajakan/
│       ├── kategori.php    # CRUD Kategori Pajak
│       ├── jenis.php       # CRUD Jenis Pajak & Tarif
│       ├── pengguna.php    # CRUD Wajib Pajak & Petugas (NPWP)
│       └── pembayaran.php  # Form Pembayaran & Histori Setoran Pajak
├── index.php               # File utama / Router aplikasi
├── export_sql.php          # Script generator ekspor SQL dump
├── css/
│   └── style.css           # Styling kustom & format cetak
├── perpajakan.sql          # Skema database MySQL perpajakan
├── supermarket (1).sql     # Skema database MySQL supermarket
└── README.md               # Dokumentasi proyek
```
