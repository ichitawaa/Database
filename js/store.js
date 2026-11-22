/**
 * Data Store Layer & Business Logic
 * Mengelola LocalStorage, Operasi CRUD, Validasi, dan Sinkronisasi Stok
 */

class DataStore {
  constructor() {
    this.STORAGE_KEY_SUPERMARKET = "db_supermarket_v1";
    this.STORAGE_KEY_PERPAJAKAN = "db_perpajakan_v1";
    this.init();
  }

  init() {
    if (!localStorage.getItem(this.STORAGE_KEY_SUPERMARKET)) {
      localStorage.setItem(this.STORAGE_KEY_SUPERMARKET, JSON.stringify(INITIAL_DATA.supermarket));
    }
    if (!localStorage.getItem(this.STORAGE_KEY_PERPAJAKAN)) {
      localStorage.setItem(this.STORAGE_KEY_PERPAJAKAN, JSON.stringify(INITIAL_DATA.perpajakan));
    }
  }

  // --- RAW STORAGE ACCESS ---
  getSupermarketData() {
    try {
      return JSON.parse(localStorage.getItem(this.STORAGE_KEY_SUPERMARKET)) || INITIAL_DATA.supermarket;
    } catch (e) {
      console.error("Error parsing supermarket data:", e);
      return INITIAL_DATA.supermarket;
    }
  }

  saveSupermarketData(data) {
    localStorage.setItem(this.STORAGE_KEY_SUPERMARKET, JSON.stringify(data));
  }

  getPerpajakanData() {
    try {
      return JSON.parse(localStorage.getItem(this.STORAGE_KEY_PERPAJAKAN)) || INITIAL_DATA.perpajakan;
    } catch (e) {
      console.error("Error parsing perpajakan data:", e);
      return INITIAL_DATA.perpajakan;
    }
  }

  savePerpajakanData(data) {
    localStorage.setItem(this.STORAGE_KEY_PERPAJAKAN, JSON.stringify(data));
  }

  resetAllData() {
    localStorage.setItem(this.STORAGE_KEY_SUPERMARKET, JSON.stringify(INITIAL_DATA.supermarket));
    localStorage.setItem(this.STORAGE_KEY_PERPAJAKAN, JSON.stringify(INITIAL_DATA.perpajakan));
  }

  // ==========================================
  // SISTEM SUPERMARKET: PRODUK (CRUD)
  // ==========================================
  getProdukList(filterKeyword = "", categoryFilter = "") {
    const data = this.getSupermarketData();
    let list = data.produk || [];

    if (categoryFilter) {
      list = list.filter(p => p.kategori === categoryFilter);
    }

    if (filterKeyword.trim()) {
      const q = filterKeyword.toLowerCase().trim();
      list = list.filter(p => 
        p.id_produk.toLowerCase().includes(q) ||
        p.nama_produk.toLowerCase().includes(q) ||
        p.kategori.toLowerCase().includes(q)
      );
    }
    return list;
  }

  getProdukById(id) {
    const data = this.getSupermarketData();
    return (data.produk || []).find(p => p.id_produk === id) || null;
  }

  saveProduk(produkData) {
    const data = this.getSupermarketData();
    data.produk = data.produk || [];

    const isEdit = data.produk.some(p => p.id_produk === produkData.id_produk);
    if (isEdit) {
      data.produk = data.produk.map(p => (p.id_produk === produkData.id_produk ? { ...produkData } : p));
    } else {
      // Auto generate ID if not supplied
      if (!produkData.id_produk) {
        const nextNum = data.produk.length + 1;
        produkData.id_produk = "PRD-" + String(nextNum).padStart(3, "0");
      }
      data.produk.push(produkData);
    }

    this.saveSupermarketData(data);
    return produkData;
  }

  deleteProduk(id) {
    const data = this.getSupermarketData();
    data.produk = (data.produk || []).filter(p => p.id_produk !== id);
    this.saveSupermarketData(data);
    return true;
  }

  getProdukCategories() {
    const data = this.getSupermarketData();
    const categories = new Set((data.produk || []).map(p => p.kategori));
    return Array.from(categories);
  }

  // ==========================================
  // SISTEM SUPERMARKET: KARYAWAN & KASIR (CRUD)
  // ==========================================
  getKaryawanList() {
    const data = this.getSupermarketData();
    return data.karyawan || [];
  }

  getKasirList(filterKeyword = "") {
    const data = this.getSupermarketData();
    const karyawanMap = new Map((data.karyawan || []).map(k => [k.id_karyawan, k]));

    let list = (data.kasir || []).map(k => {
      const kry = karyawanMap.get(k.id_karyawan) || {};
      return {
        ...k,
        nama_karyawan: kry.nama_karyawan || "Kasir (" + k.id_kasir + ")",
        jabatan: kry.jabatan || "Kasir",
        alamat: kry.alamat || "-"
      };
    });

    if (filterKeyword.trim()) {
      const q = filterKeyword.toLowerCase().trim();
      list = list.filter(k => 
        k.id_kasir.toLowerCase().includes(q) ||
        k.nama_karyawan.toLowerCase().includes(q) ||
        k.shift.toLowerCase().includes(q) ||
        k.no_telepon.includes(q)
      );
    }
    return list;
  }

  getKasirById(id) {
    const list = this.getKasirList();
    return list.find(k => k.id_kasir === id) || null;
  }

  saveKasir(kasirData) {
    const data = this.getSupermarketData();
    data.kasir = data.kasir || [];
    data.karyawan = data.karyawan || [];

    // Jika membuat karyawan baru secara simultan atau update nama karyawan
    if (kasirData.nama_karyawan) {
      let kry = data.karyawan.find(k => k.id_karyawan === kasirData.id_karyawan);
      if (kry) {
        kry.nama_karyawan = kasirData.nama_karyawan;
        kry.no_telepon = kasirData.no_telepon || kry.no_telepon;
        kry.alamat = kasirData.alamat || kry.alamat;
      } else {
        const nextKryId = "KRY-" + String(data.karyawan.length + 1).padStart(3, "0");
        kasirData.id_karyawan = kasirData.id_karyawan || nextKryId;
        data.karyawan.push({
          id_karyawan: kasirData.id_karyawan,
          nama_karyawan: kasirData.nama_karyawan,
          jabatan: kasirData.jabatan || "Kasir",
          no_telepon: kasirData.no_telepon || "-",
          alamat: kasirData.alamat || "-",
          tanggal_masuk: new Date().toISOString().split("T")[0]
        });
      }
    }

    const payload = {
      id_kasir: kasirData.id_kasir,
      id_karyawan: kasirData.id_karyawan,
      shift: kasirData.shift,
      no_telepon: kasirData.no_telepon
    };

    const isEdit = data.kasir.some(k => k.id_kasir === kasirData.id_kasir);
    if (isEdit) {
      data.kasir = data.kasir.map(k => (k.id_kasir === kasirData.id_kasir ? payload : k));
    } else {
      if (!payload.id_kasir) {
        const nextNum = data.kasir.length + 1;
        payload.id_kasir = "KAS-" + String(nextNum).padStart(2, "0");
      }
      data.kasir.push(payload);
    }

    this.saveSupermarketData(data);
    return payload;
  }

  deleteKasir(id) {
    const data = this.getSupermarketData();
    data.kasir = (data.kasir || []).filter(k => k.id_kasir !== id);
    this.saveSupermarketData(data);
    return true;
  }

  // ==========================================
  // SISTEM SUPERMARKET: PELANGGAN (CRUD)
  // ==========================================
  getPelangganList(filterKeyword = "") {
    const data = this.getSupermarketData();
    let list = data.pelanggan || [];

    if (filterKeyword.trim()) {
      const q = filterKeyword.toLowerCase().trim();
      list = list.filter(p => 
        p.id_pelanggan.toLowerCase().includes(q) ||
        p.nama_pelanggan.toLowerCase().includes(q) ||
        (p.no_telepon && p.no_telepon.includes(q))
      );
    }
    return list;
  }

  getPelangganById(id) {
    const data = this.getSupermarketData();
    return (data.pelanggan || []).find(p => p.id_pelanggan === id) || null;
  }

  savePelanggan(pelangganData) {
    const data = this.getSupermarketData();
    data.pelanggan = data.pelanggan || [];

    const isEdit = data.pelanggan.some(p => p.id_pelanggan === pelangganData.id_pelanggan);
    if (isEdit) {
      data.pelanggan = data.pelanggan.map(p => (p.id_pelanggan === pelangganData.id_pelanggan ? { ...pelangganData } : p));
    } else {
      if (!pelangganData.id_pelanggan) {
        const nextNum = data.pelanggan.length + 1;
        pelangganData.id_pelanggan = "PLG-" + String(nextNum).padStart(3, "0");
      }
      data.pelanggan.push(pelangganData);
    }

    this.saveSupermarketData(data);
    return pelangganData;
  }

  deletePelanggan(id) {
    const data = this.getSupermarketData();
    data.pelanggan = (data.pelanggan || []).filter(p => p.id_pelanggan !== id);
    this.saveSupermarketData(data);
    return true;
  }

  // ==========================================
  // SISTEM SUPERMARKET: TRANSAKSI PENJUALAN
  // ==========================================
  createTransaksi({ id_kasir, id_pelanggan, metode_pembayaran, items, catatan = "" }) {
    if (!items || items.length === 0) {
      throw new Error("Keranjang belanja masih kosong!");
    }

    const data = this.getSupermarketData();
    data.transaksi = data.transaksi || [];
    data.detail_transaksi = data.detail_transaksi || [];
    data.produk = data.produk || [];
    data.pelanggan = data.pelanggan || [];

    // Validasi stok produk
    for (const item of items) {
      const prod = data.produk.find(p => p.id_produk === item.id_produk);
      if (!prod) {
        throw new Error(`Produk dengan ID ${item.id_produk} tidak ditemukan!`);
      }
      if (prod.stok < item.jumlah) {
        throw new Error(`Stok untuk ${prod.nama_produk} tidak mencukupi! Tersedia: ${prod.stok}, diminta: ${item.jumlah}`);
      }
    }

    // Buat ID Transaksi: TRX-YYYYMMDD-XXXX
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
    const randomSuffix = String(Math.floor(1000 + Math.random() * 9000));
    const id_transaksi = `TRX-${dateStr}-${randomSuffix}`;
    const tanggal_transaksi = now.toISOString().replace("T", " ").substring(0, 19);

    // Hitung total dan kurangi stok
    let total_bayar = 0;
    const detailList = [];

    items.forEach((item, idx) => {
      const prod = data.produk.find(p => p.id_produk === item.id_produk);
      const subtotal = item.harga_jual * item.jumlah;
      total_bayar += subtotal;

      // Kurangi stok
      prod.stok -= item.jumlah;

      const detailRecord = {
        id_detail: (data.detail_transaksi.length > 0 ? Math.max(...data.detail_transaksi.map(d => d.id_detail || 0)) : 0) + idx + 1,
        id_transaksi: id_transaksi,
        id_produk: item.id_produk,
        jumlah: item.jumlah,
        subtotal: subtotal
      };
      detailList.push(detailRecord);
      data.detail_transaksi.push(detailRecord);
    });

    // Tambah poin member untuk pelanggan jika ada (1 poin per Rp 10.000)
    let poinDitambah = 0;
    if (id_pelanggan) {
      const cust = data.pelanggan.find(p => p.id_pelanggan === id_pelanggan);
      if (cust) {
        poinDitambah = Math.floor(total_bayar / 10000);
        cust.poin_member = (cust.poin_member || 0) + poinDitambah;
      }
    }

    const newTransaksi = {
      id_transaksi,
      tanggal_transaksi,
      id_kasir,
      id_pelanggan: id_pelanggan || null,
      total_bayar,
      metode_pembayaran: metode_pembayaran || "Tunai",
      catatan
    };

    data.transaksi.unshift(newTransaksi);
    this.saveSupermarketData(data);

    return {
      transaksi: newTransaksi,
      detail: detailList,
      poinDitambah
    };
  }

  getTransaksiList(filterKeyword = "") {
    const data = this.getSupermarketData();
    const kasirMap = new Map(this.getKasirList().map(k => [k.id_kasir, k]));
    const pelangganMap = new Map((data.pelanggan || []).map(p => [p.id_pelanggan, p]));

    let list = (data.transaksi || []).map(t => {
      const k = kasirMap.get(t.id_kasir) || {};
      const p = pelangganMap.get(t.id_pelanggan) || {};
      return {
        ...t,
        nama_kasir: k.nama_karyawan || t.id_kasir,
        nama_pelanggan: p.nama_pelanggan || (t.id_pelanggan ? t.id_pelanggan : "Pelanggan Umum (Non-Member)")
      };
    });

    if (filterKeyword.trim()) {
      const q = filterKeyword.toLowerCase().trim();
      list = list.filter(t => 
        t.id_transaksi.toLowerCase().includes(q) ||
        t.nama_kasir.toLowerCase().includes(q) ||
        t.nama_pelanggan.toLowerCase().includes(q) ||
        t.metode_pembayaran.toLowerCase().includes(q)
      );
    }
    return list;
  }

  getTransaksiDetail(id_transaksi) {
    const data = this.getSupermarketData();
    const trx = (data.transaksi || []).find(t => t.id_transaksi === id_transaksi);
    if (!trx) return null;

    const kasir = this.getKasirList().find(k => k.id_kasir === trx.id_kasir);
    const pelanggan = (data.pelanggan || []).find(p => p.id_pelanggan === trx.id_pelanggan);
    const prodMap = new Map((data.produk || []).map(p => [p.id_produk, p]));

    const details = (data.detail_transaksi || [])
      .filter(d => d.id_transaksi === id_transaksi)
      .map(d => {
        const prod = prodMap.get(d.id_produk) || {};
        return {
          ...d,
          nama_produk: prod.nama_produk || d.id_produk,
          harga_jual: prod.harga_jual || Math.round(d.subtotal / (d.jumlah || 1))
        };
      });

    return {
      transaksi: trx,
      kasir: kasir || { nama_karyawan: trx.id_kasir },
      pelanggan: pelanggan || { nama_pelanggan: "Pelanggan Umum (Non-Member)" },
      items: details
    };
  }

  deleteTransaksi(id_transaksi) {
    const data = this.getSupermarketData();
    data.transaksi = (data.transaksi || []).filter(t => t.id_transaksi !== id_transaksi);
    data.detail_transaksi = (data.detail_transaksi || []).filter(d => d.id_transaksi !== id_transaksi);
    this.saveSupermarketData(data);
    return true;
  }

  // ==========================================
  // SISTEM PERPAJAKAN: KATEGORI PAJAK (CRUD)
  // ==========================================
  getKategoriPajakList(filterKeyword = "") {
    const data = this.getPerpajakanData();
    let list = data.kategori_pajak || [];

    if (filterKeyword.trim()) {
      const q = filterKeyword.toLowerCase().trim();
      list = list.filter(k => 
        k.id_kategori.toLowerCase().includes(q) ||
        k.nama_kategori.toLowerCase().includes(q) ||
        (k.keterangan && k.keterangan.toLowerCase().includes(q))
      );
    }
    return list;
  }

  getKategoriPajakById(id) {
    const data = this.getPerpajakanData();
    return (data.kategori_pajak || []).find(k => k.id_kategori === id) || null;
  }

  saveKategoriPajak(katData) {
    const data = this.getPerpajakanData();
    data.kategori_pajak = data.kategori_pajak || [];

    const isEdit = data.kategori_pajak.some(k => k.id_kategori === katData.id_kategori);
    if (isEdit) {
      data.kategori_pajak = data.kategori_pajak.map(k => (k.id_kategori === katData.id_kategori ? { ...katData } : k));
    } else {
      if (!katData.id_kategori) {
        const nextNum = data.kategori_pajak.length + 1;
        katData.id_kategori = "KAT-" + String(nextNum).padStart(2, "0");
      }
      data.kategori_pajak.push(katData);
    }

    this.savePerpajakanData(data);
    return katData;
  }

  deleteKategoriPajak(id) {
    const data = this.getPerpajakanData();
    // Cek apakah digunakan oleh jenis pajak
    const usedByJenis = (data.jenis_pajak || []).some(j => j.id_kategori === id);
    if (usedByJenis) {
      throw new Error("Kategori ini tidak dapat dihapus karena masih digunakan oleh salah satu Jenis Pajak!");
    }
    data.kategori_pajak = (data.kategori_pajak || []).filter(k => k.id_kategori !== id);
    this.savePerpajakanData(data);
    return true;
  }

  // ==========================================
  // SISTEM PERPAJAKAN: JENIS PAJAK (CRUD)
  // ==========================================
  getJenisPajakList(filterKeyword = "", categoryId = "") {
    const data = this.getPerpajakanData();
    const katMap = new Map((data.kategori_pajak || []).map(k => [k.id_kategori, k]));

    let list = (data.jenis_pajak || []).map(j => {
      const kat = katMap.get(j.id_kategori) || {};
      return {
        ...j,
        nama_kategori: kat.nama_kategori || j.id_kategori
      };
    });

    if (categoryId) {
      list = list.filter(j => j.id_kategori === categoryId);
    }

    if (filterKeyword.trim()) {
      const q = filterKeyword.toLowerCase().trim();
      list = list.filter(j => 
        j.id_pajak.toLowerCase().includes(q) ||
        j.nama_pajak.toLowerCase().includes(q) ||
        j.tarif_pajak.toLowerCase().includes(q) ||
        j.nama_kategori.toLowerCase().includes(q)
      );
    }
    return list;
  }

  getJenisPajakById(id) {
    const data = this.getPerpajakanData();
    return (data.jenis_pajak || []).find(j => j.id_pajak === id) || null;
  }

  saveJenisPajak(jenisData) {
    const data = this.getPerpajakanData();
    data.jenis_pajak = data.jenis_pajak || [];

    const isEdit = data.jenis_pajak.some(j => j.id_pajak === jenisData.id_pajak);
    if (isEdit) {
      data.jenis_pajak = data.jenis_pajak.map(j => (j.id_pajak === jenisData.id_pajak ? { ...jenisData } : j));
    } else {
      if (!jenisData.id_pajak) {
        const nextNum = data.jenis_pajak.length + 1;
        jenisData.id_pajak = "PJK-" + String(nextNum).padStart(2, "0");
      }
      data.jenis_pajak.push(jenisData);
    }

    this.savePerpajakanData(data);
    return jenisData;
  }

  deleteJenisPajak(id) {
    const data = this.getPerpajakanData();
    // Cek apakah ada pembayaran terkait
    const usedByPayment = (data.pembayaran_pajak || []).some(p => p.id_pajak === id);
    if (usedByPayment) {
      throw new Error("Jenis pajak ini tidak dapat dihapus karena tercatat dalam histori Pembayaran Pajak!");
    }
    data.jenis_pajak = (data.jenis_pajak || []).filter(j => j.id_pajak !== id);
    this.savePerpajakanData(data);
    return true;
  }

  // ==========================================
  // SISTEM PERPAJAKAN: PENGGUNA / WAJIB PAJAK (CRUD)
  // ==========================================
  getPenggunaList(filterKeyword = "", tipeFilter = "") {
    const data = this.getPerpajakanData();
    let list = data.pengguna || [];

    if (tipeFilter) {
      list = list.filter(p => p.tipe_pengguna === tipeFilter);
    }

    if (filterKeyword.trim()) {
      const q = filterKeyword.toLowerCase().trim();
      list = list.filter(p => 
        p.id_pengguna.toLowerCase().includes(q) ||
        p.nama_pengguna.toLowerCase().includes(q) ||
        p.tipe_pengguna.toLowerCase().includes(q) ||
        (p.npwp && p.npwp.toLowerCase().includes(q))
      );
    }
    return list;
  }

  getPenggunaById(id) {
    const data = this.getPerpajakanData();
    return (data.pengguna || []).find(p => p.id_pengguna === id) || null;
  }

  getPetugasPajakList() {
    return this.getPenggunaList("", "Petugas Pajak");
  }

  getWajibPajakList() {
    const data = this.getPerpajakanData();
    return (data.pengguna || []).filter(p => p.tipe_pengguna === "Perusahaan" || p.tipe_pengguna === "Perorangan");
  }

  savePengguna(penggunaData) {
    const data = this.getPerpajakanData();
    data.pengguna = data.pengguna || [];

    // Validasi keunikan NPWP
    if (penggunaData.npwp) {
      const duplicate = data.pengguna.find(p => p.npwp === penggunaData.npwp && p.id_pengguna !== penggunaData.id_pengguna);
      if (duplicate) {
        throw new Error(`Nomor NPWP ${penggunaData.npwp} sudah terdaftar atas nama ${duplicate.nama_pengguna}!`);
      }
    }

    const isEdit = data.pengguna.some(p => p.id_pengguna === penggunaData.id_pengguna);
    if (isEdit) {
      data.pengguna = data.pengguna.map(p => (p.id_pengguna === penggunaData.id_pengguna ? { ...penggunaData } : p));
    } else {
      if (!penggunaData.id_pengguna) {
        const nextNum = data.pengguna.length + 1;
        penggunaData.id_pengguna = "USR-" + String(nextNum).padStart(3, "0");
      }
      data.pengguna.push(penggunaData);
    }

    this.savePerpajakanData(data);
    return penggunaData;
  }

  deletePengguna(id) {
    const data = this.getPerpajakanData();
    const usedAsWP = (data.pembayaran_pajak || []).some(p => p.id_pengguna === id);
    const usedAsPetugas = (data.pembayaran_pajak || []).some(p => p.id_petugas === id);
    if (usedAsWP || usedAsPetugas) {
      throw new Error("Pengguna ini tidak dapat dihapus karena memiliki riwayat transaksi/verifikasi pada Pembayaran Pajak!");
    }
    data.pengguna = (data.pengguna || []).filter(p => p.id_pengguna !== id);
    this.savePerpajakanData(data);
    return true;
  }

  // ==========================================
  // SISTEM PERPAJAKAN: PEMBAYARAN PAJAK
  // ==========================================
  getPembayaranPajakList(filterKeyword = "", statusFilter = "") {
    const data = this.getPerpajakanData();
    const userMap = new Map((data.pengguna || []).map(u => [u.id_pengguna, u]));
    const pjkMap = new Map((data.jenis_pajak || []).map(j => [j.id_pajak, j]));

    let list = (data.pembayaran_pajak || []).map(p => {
      const wp = userMap.get(p.id_pengguna) || {};
      const pajak = pjkMap.get(p.id_pajak) || {};
      const petugas = userMap.get(p.id_petugas) || {};

      return {
        ...p,
        nama_pengguna: wp.nama_pengguna || p.id_pengguna,
        npwp_pengguna: wp.npwp || "-",
        tipe_pengguna: wp.tipe_pengguna || "-",
        nama_pajak: pajak.nama_pajak || p.id_pajak,
        tarif_pajak: pajak.tarif_pajak || "-",
        nama_petugas: petugas.nama_pengguna || p.id_petugas
      };
    });

    if (statusFilter) {
      list = list.filter(p => p.status_pembayaran === statusFilter);
    }

    if (filterKeyword.trim()) {
      const q = filterKeyword.toLowerCase().trim();
      list = list.filter(p => 
        p.id_pembayaran.toLowerCase().includes(q) ||
        p.nama_pengguna.toLowerCase().includes(q) ||
        p.npwp_pengguna.toLowerCase().includes(q) ||
        p.nama_pajak.toLowerCase().includes(q) ||
        p.nama_petugas.toLowerCase().includes(q) ||
        p.status_pembayaran.toLowerCase().includes(q)
      );
    }

    return list;
  }

  getPembayaranPajakById(id) {
    const list = this.getPembayaranPajakList();
    return list.find(p => p.id_pembayaran === id) || null;
  }

  savePembayaranPajak(bayarData) {
    const data = this.getPerpajakanData();
    data.pembayaran_pajak = data.pembayaran_pajak || [];

    const isEdit = data.pembayaran_pajak.some(p => p.id_pembayaran === bayarData.id_pembayaran);
    if (isEdit) {
      data.pembayaran_pajak = data.pembayaran_pajak.map(p => 
        p.id_pembayaran === bayarData.id_pembayaran ? { ...p, ...bayarData } : p
      );
    } else {
      if (!bayarData.id_pembayaran) {
        const now = new Date();
        const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
        const rand = String(Math.floor(100 + Math.random() * 900));
        bayarData.id_pembayaran = `BYR-${dateStr}${rand}`;
      }
      if (!bayarData.tanggal_bayar) {
        bayarData.tanggal_bayar = new Date().toISOString().replace("T", " ").substring(0, 19);
      }
      data.pembayaran_pajak.unshift(bayarData);
    }

    this.savePerpajakanData(data);
    return bayarData;
  }

  updateStatusPembayaranPajak(id_pembayaran, newStatus) {
    const data = this.getPerpajakanData();
    data.pembayaran_pajak = (data.pembayaran_pajak || []).map(p => {
      if (p.id_pembayaran === id_pembayaran) {
        return { ...p, status_pembayaran: newStatus };
      }
      return p;
    });
    this.savePerpajakanData(data);
    return true;
  }

  deletePembayaranPajak(id) {
    const data = this.getPerpajakanData();
    data.pembayaran_pajak = (data.pembayaran_pajak || []).filter(p => p.id_pembayaran !== id);
    this.savePerpajakanData(data);
    return true;
  }

  // ==========================================
  // EXPORT TO SQL (MySQL / phpMyAdmin format)
  // ==========================================
  generateSQL(system = "supermarket") {
    if (system === "supermarket") {
      const data = this.getSupermarketData();
      let sql = `-- ==========================================\n`;
      sql += `-- EXPORT DATA DUMP: SISTEM SUPERMARKET\n`;
      sql += `-- Tanggal: ${new Date().toLocaleString("id-ID")}\n`;
      sql += `-- ==========================================\n\n`;

      // Produk
      if (data.produk && data.produk.length > 0) {
        sql += `-- Data Produk\n`;
        sql += `INSERT INTO \`produk\` (\`id_produk\`, \`nama_produk\`, \`kategori\`, \`harga_jual\`, \`stok\`) VALUES\n`;
        const pLines = data.produk.map(p => 
          `('${p.id_produk}', '${p.nama_produk.replace(/'/g, "\\'")}', '${p.kategori.replace(/'/g, "\\'")}', ${p.harga_jual}, ${p.stok})`
        );
        sql += pLines.join(",\n") + ";\n\n";
      }

      // Karyawan
      if (data.karyawan && data.karyawan.length > 0) {
        sql += `-- Data Karyawan\n`;
        sql += `INSERT INTO \`karyawan\` (\`id_karyawan\`, \`nama_karyawan\`, \`jabatan\`, \`no_telepon\`, \`alamat\`, \`tanggal_masuk\`) VALUES\n`;
        const kLines = data.karyawan.map(k => 
          `('${k.id_karyawan}', '${k.nama_karyawan.replace(/'/g, "\\'")}', '${k.jabatan}', '${k.no_telepon}', '${k.alamat.replace(/'/g, "\\'")}', '${k.tanggal_masuk}')`
        );
        sql += kLines.join(",\n") + ";\n\n";
      }

      // Kasir
      if (data.kasir && data.kasir.length > 0) {
        sql += `-- Data Kasir\n`;
        sql += `INSERT INTO \`kasir\` (\`id_kasir\`, \`id_karyawan\`, \`shift\`, \`no_telepon\`) VALUES\n`;
        const kasLines = data.kasir.map(k => 
          `('${k.id_kasir}', '${k.id_karyawan}', '${k.shift}', '${k.no_telepon}')`
        );
        sql += kasLines.join(",\n") + ";\n\n";
      }

      // Pelanggan
      if (data.pelanggan && data.pelanggan.length > 0) {
        sql += `-- Data Pelanggan\n`;
        sql += `INSERT INTO \`pelanggan\` (\`id_pelanggan\`, \`nama_pelanggan\`, \`no_telepon\`, \`poin_member\`) VALUES\n`;
        const pelLines = data.pelanggan.map(p => 
          `('${p.id_pelanggan}', '${p.nama_pelanggan.replace(/'/g, "\\'")}', '${p.no_telepon}', ${p.poin_member})`
        );
        sql += pelLines.join(",\n") + ";\n\n";
      }

      // Transaksi
      if (data.transaksi && data.transaksi.length > 0) {
        sql += `-- Data Transaksi\n`;
        sql += `INSERT INTO \`transaksi\` (\`id_transaksi\`, \`tanggal_transaksi\`, \`id_kasir\`, \`id_pelanggan\`, \`total_bayar\`, \`metode_pembayaran\`) VALUES\n`;
        const tLines = data.transaksi.map(t => 
          `('${t.id_transaksi}', '${t.tanggal_transaksi}', '${t.id_kasir}', ${t.id_pelanggan ? `'${t.id_pelanggan}'` : "NULL"}, ${t.total_bayar}, '${t.metode_pembayaran}')`
        );
        sql += tLines.join(",\n") + ";\n\n";
      }

      // Detail Transaksi
      if (data.detail_transaksi && data.detail_transaksi.length > 0) {
        sql += `-- Data Detail Transaksi\n`;
        sql += `INSERT INTO \`detail_transaksi\` (\`id_detail\`, \`id_transaksi\`, \`id_produk\`, \`jumlah\`, \`subtotal\`) VALUES\n`;
        const dtLines = data.detail_transaksi.map(d => 
          `(${d.id_detail}, '${d.id_transaksi}', '${d.id_produk}', ${d.jumlah}, ${d.subtotal})`
        );
        sql += dtLines.join(",\n") + ";\n\n";
      }

      return sql;
    } else {
      const data = this.getPerpajakanData();
      let sql = `-- ==========================================\n`;
      sql += `-- EXPORT DATA DUMP: SISTEM PERPAJAKAN\n`;
      sql += `-- Tanggal: ${new Date().toLocaleString("id-ID")}\n`;
      sql += `-- ==========================================\n\n`;

      // Kategori Pajak
      if (data.kategori_pajak && data.kategori_pajak.length > 0) {
        sql += `-- Data Kategori Pajak\n`;
        sql += `INSERT INTO \`kategori_pajak\` (\`id_kategori\`, \`nama_kategori\`, \`keterangan\`) VALUES\n`;
        const katLines = data.kategori_pajak.map(k => 
          `('${k.id_kategori}', '${k.nama_kategori.replace(/'/g, "\\'")}', '${(k.keterangan || "").replace(/'/g, "\\'")}')`
        );
        sql += katLines.join(",\n") + ";\n\n";
      }

      // Jenis Pajak
      if (data.jenis_pajak && data.jenis_pajak.length > 0) {
        sql += `-- Data Jenis Pajak\n`;
        sql += `INSERT INTO \`jenis_pajak\` (\`id_pajak\`, \`id_kategori\`, \`nama_pajak\`, \`tarif_pajak\`) VALUES\n`;
        const jLines = data.jenis_pajak.map(j => 
          `('${j.id_pajak}', '${j.id_kategori}', '${j.nama_pajak.replace(/'/g, "\\'")}', '${j.tarif_pajak}')`
        );
        sql += jLines.join(",\n") + ";\n\n";
      }

      // Pengguna
      if (data.pengguna && data.pengguna.length > 0) {
        sql += `-- Data Pengguna / Wajib Pajak\n`;
        sql += `INSERT INTO \`pengguna\` (\`id_pengguna\`, \`nama_pengguna\`, \`tipe_pengguna\`, \`npwp\`, \`no_telepon\`, \`alamat\`) VALUES\n`;
        const uLines = data.pengguna.map(u => 
          `('${u.id_pengguna}', '${u.nama_pengguna.replace(/'/g, "\\'")}', '${u.tipe_pengguna}', '${u.npwp}', '${u.no_telepon}', '${(u.alamat || "").replace(/'/g, "\\'")}')`
        );
        sql += uLines.join(",\n") + ";\n\n";
      }

      // Pembayaran Pajak
      if (data.pembayaran_pajak && data.pembayaran_pajak.length > 0) {
        sql += `-- Data Pembayaran Pajak\n`;
        sql += `INSERT INTO \`pembayaran_pajak\` (\`id_pembayaran\`, \`tanggal_bayar\`, \`id_pengguna\`, \`id_pajak\`, \`id_petugas\`, \`jumlah_bayar\`, \`status_pembayaran\`) VALUES\n`;
        const payLines = data.pembayaran_pajak.map(p => 
          `('${p.id_pembayaran}', '${p.tanggal_bayar}', '${p.id_pengguna}', '${p.id_pajak}', '${p.id_petugas}', ${p.jumlah_bayar}, '${p.status_pembayaran}')`
        );
        sql += payLines.join(",\n") + ";\n\n";
      }

      return sql;
    }
  }
}

// Global Store Instance
window.store = new DataStore();
