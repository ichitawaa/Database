/**
 * Application Controller & UI Logic
 * Mengelola Interaksi Tampilan, Sinkronisasi CRUD, POS Supermarket, dan Administrasi Pajak
 */

class AppController {
  constructor() {
    this.currentSystem = "supermarket"; // 'supermarket' or 'perpajakan'
    this.currentSmTab = "produk";
    this.currentTaxTab = "kategori";
    this.cart = [];
    this.activeExportSystem = "supermarket";

    // Inisialisasi saat DOM siap
    document.addEventListener("DOMContentLoaded", () => {
      this.init();
    });
  }

  init() {
    this.renderStats();
    this.renderAllSupermarketViews();
    this.renderAllPerpajakanViews();
    this.populatePosDropdowns();
    this.populateTaxPaymentDropdowns();

    // Default datetime-local input for tax payment
    const dtInput = document.getElementById("payTanggalBayar");
    if (dtInput) {
      const now = new Date();
      now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
      dtInput.value = now.toISOString().slice(0, 16);
    }
  }

  // ========================================================
  // SYSTEM & TAB SWITCHING
  // ========================================================
  switchSystem(system) {
    this.currentSystem = system;

    const navBtnSm = document.getElementById("navBtnSupermarket");
    const navBtnTax = document.getElementById("navBtnPerpajakan");
    const secSm = document.getElementById("sectionSupermarket");
    const secTax = document.getElementById("sectionPerpajakan");
    const brandIcon = document.getElementById("brandIcon");
    const brandIconContainer = document.getElementById("brandIconContainer");
    const activeBadge = document.getElementById("activeSystemBadge");

    if (system === "supermarket") {
      secSm.classList.remove("hidden");
      secTax.classList.add("hidden");

      navBtnSm.className = "flex items-center space-x-2 px-5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 nav-tab-active-supermarket";
      navBtnTax.className = "flex items-center space-x-2 px-5 py-2 rounded-xl text-sm font-semibold text-slate-300 hover:text-white transition-all duration-200";

      brandIcon.className = "fa-solid fa-cart-shopping text-lg";
      brandIconContainer.className = "w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-lg transition-all duration-300";
      
      activeBadge.textContent = "Supermarket Active";
      activeBadge.className = "text-xs px-2 py-0.5 rounded-full font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30";

      this.renderAllSupermarketViews();
    } else {
      secSm.classList.add("hidden");
      secTax.classList.remove("hidden");

      navBtnTax.className = "flex items-center space-x-2 px-5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 nav-tab-active-perpajakan";
      navBtnSm.className = "flex items-center space-x-2 px-5 py-2 rounded-xl text-sm font-semibold text-slate-300 hover:text-white transition-all duration-200";

      brandIcon.className = "fa-solid fa-landmark text-lg";
      brandIconContainer.className = "w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg transition-all duration-300";

      activeBadge.textContent = "Perpajakan Active";
      activeBadge.className = "text-xs px-2 py-0.5 rounded-full font-semibold bg-blue-500/20 text-blue-400 border border-blue-500/30";

      this.renderAllPerpajakanViews();
    }

    this.renderStats();
  }

  setSupermarketTab(tabName) {
    this.currentSmTab = tabName;
    const tabs = ["produk", "kasir", "pelanggan", "pos", "riwayat"];

    tabs.forEach(t => {
      const btn = document.getElementById("smTab" + t.charAt(0).toUpperCase() + t.slice(1));
      const content = document.getElementById("smContent" + t.charAt(0).toUpperCase() + t.slice(1));
      if (!btn || !content) return;

      if (t === tabName) {
        content.classList.remove("hidden");
        btn.className = "flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition subtab-active-supermarket";
      } else {
        content.classList.add("hidden");
        btn.className = "flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:text-emerald-700 hover:bg-slate-50 transition";
      }
    });

    if (tabName === "pos") {
      this.populatePosDropdowns();
      this.renderCartTable();
    } else if (tabName === "riwayat") {
      this.renderRiwayatTable();
    }
  }

  setPerpajakanTab(tabName) {
    this.currentTaxTab = tabName;
    const tabs = ["kategori", "jenis", "pengguna", "pembayaran"];

    tabs.forEach(t => {
      const btn = document.getElementById("taxTab" + t.charAt(0).toUpperCase() + t.slice(1));
      const content = document.getElementById("taxContent" + t.charAt(0).toUpperCase() + t.slice(1));
      if (!btn || !content) return;

      if (t === tabName) {
        content.classList.remove("hidden");
        btn.className = "flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition subtab-active-perpajakan";
      } else {
        content.classList.add("hidden");
        btn.className = "flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:text-blue-700 hover:bg-slate-50 transition";
      }
    });

    if (tabName === "pembayaran") {
      this.populateTaxPaymentDropdowns();
      this.renderTaxPembayaranTable();
    }
  }

  // ========================================================
  // STAT CARDS
  // ========================================================
  renderStats() {
    const smData = window.store.getSupermarketData();
    const taxData = window.store.getPerpajakanData();

    // Supermarket stats
    const statSmProd = document.getElementById("statSupermarketProduk");
    const statSmKas = document.getElementById("statSupermarketKasir");
    const statSmPel = document.getElementById("statSupermarketPelanggan");
    const statSmTrx = document.getElementById("statSupermarketTransaksi");

    if (statSmProd) statSmProd.textContent = (smData.produk || []).length;
    if (statSmKas) statSmKas.textContent = (smData.kasir || []).length;
    if (statSmPel) statSmPel.textContent = (smData.pelanggan || []).length;
    if (statSmTrx) statSmTrx.textContent = (smData.transaksi || []).length;

    // Tax stats
    const statTaxKat = document.getElementById("statTaxKategori");
    const statTaxJen = document.getElementById("statTaxJenis");
    const statTaxUsr = document.getElementById("statTaxPengguna");
    const statTaxBayar = document.getElementById("statTaxTotalBayar");

    if (statTaxKat) statTaxKat.textContent = (taxData.kategori_pajak || []).length;
    if (statTaxJen) statTaxJen.textContent = (taxData.jenis_pajak || []).length;
    if (statTaxUsr) statTaxUsr.textContent = (taxData.pengguna || []).length;

    if (statTaxBayar) {
      const totalTax = (taxData.pembayaran_pajak || [])
        .filter(p => p.status_pembayaran === "Lunas")
        .reduce((sum, p) => sum + (Number(p.jumlah_bayar) || 0), 0);
      statTaxBayar.textContent = this.formatRupiah(totalTax);
    }
  }

  // ========================================================
  // SISTEM SUPERMARKET: PRODUK CRUD
  // ========================================================
  renderAllSupermarketViews() {
    this.renderProdukCategoryFilter();
    this.renderProdukTable();
    this.renderKasirTable();
    this.renderPelangganTable();
    this.renderRiwayatTable();
  }

  renderProdukCategoryFilter() {
    const filterSelect = document.getElementById("smProdukCategoryFilter");
    if (!filterSelect) return;
    const currentVal = filterSelect.value;
    const categories = window.store.getProdukCategories();

    let options = '<option value="">Semua Kategori</option>';
    categories.forEach(cat => {
      options += `<option value="${cat}" ${cat === currentVal ? "selected" : ""}>${cat}</option>`;
    });
    filterSelect.innerHTML = options;
  }

  renderProdukTable() {
    const searchVal = document.getElementById("smProdukSearch")?.value || "";
    const catVal = document.getElementById("smProdukCategoryFilter")?.value || "";
    const list = window.store.getProdukList(searchVal, catVal);
    const tbody = document.getElementById("smProdukTableBody");
    if (!tbody) return;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-box-open text-3xl mb-2 text-slate-300"></i>
            <p>Tidak ada data produk yang cocok</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => {
      let stockBadge = "";
      if (item.stok > 20) {
        stockBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Tersedia (${item.stok})</span>`;
      } else if (item.stok > 0) {
        stockBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Menipis (${item.stok})</span>`;
      } else {
        stockBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Habis</span>`;
      }

      return `
        <tr class="hover:bg-slate-50/80 transition">
          <td class="py-3 px-4 font-mono font-bold text-slate-700">${item.id_produk}</td>
          <td class="py-3 px-4 font-semibold text-slate-900">${item.nama_produk}</td>
          <td class="py-3 px-4">
            <span class="inline-block px-2.5 py-0.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
              ${item.kategori}
            </span>
          </td>
          <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">${this.formatRupiah(item.harga_jual)}</td>
          <td class="py-3 px-4 text-center font-bold">${item.stok}</td>
          <td class="py-3 px-4 text-center">${stockBadge}</td>
          <td class="py-3 px-4 text-center">
            <div class="flex items-center justify-center space-x-1.5">
              <button onclick="app.openProdukModal('${item.id_produk}')" title="Edit Produk"
                class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button onclick="app.deleteProdukConfirm('${item.id_produk}', '${item.nama_produk.replace(/'/g, "\\'")}')" title="Hapus Produk"
                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }).join("");
  }

  openProdukModal(id = null) {
    const isEdit = Boolean(id);
    const prod = isEdit ? window.store.getProdukById(id) : null;

    const fieldsHtml = `
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Produk (ID)</label>
          <input type="text" name="id_produk" value="${prod?.id_produk || ''}" ${isEdit ? 'readonly class="w-full px-3 py-2 text-sm rounded-xl bg-slate-100 border border-slate-200 text-slate-500 font-mono"' : 'placeholder="Otomatis (contoh: PRD-011)" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono"'}>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Produk <span class="text-rose-500">*</span></label>
          <input type="text" name="kategori" required value="${prod?.kategori || ''}" placeholder="Contoh: Sembako, Minuman..."
            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk <span class="text-rose-500">*</span></label>
        <input type="text" name="nama_produk" required value="${prod?.nama_produk || ''}" placeholder="Nama lengkap produk..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Harga Jual (Rp) <span class="text-rose-500">*</span></label>
          <input type="number" name="harga_jual" required min="100" value="${prod?.harga_jual || ''}" placeholder="Contoh: 25000"
            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Stok <span class="text-rose-500">*</span></label>
          <input type="number" name="stok" required min="0" value="${prod !== null ? prod.stok : 10}" placeholder="Contoh: 50"
            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
        </div>
      </div>
    `;

    this.showCrudModal({
      title: isEdit ? `Edit Produk: ${prod.nama_produk}` : "Tambah Produk Baru",
      icon: "fa-solid fa-box-open",
      themeColor: "emerald",
      entity: "produk",
      isEdit,
      fieldsHtml
    });
  }

  deleteProdukConfirm(id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus produk "${name}" (${id})?`)) {
      window.store.deleteProduk(id);
      this.toast(`Produk ${name} berhasil dihapus!`, "success");
      this.renderProdukTable();
      this.renderProdukCategoryFilter();
      this.renderStats();
    }
  }

  // ========================================================
  // SISTEM SUPERMARKET: KASIR CRUD
  // ========================================================
  renderKasirTable() {
    const searchVal = document.getElementById("smKasirSearch")?.value || "";
    const list = window.store.getKasirList(searchVal);
    const tbody = document.getElementById("smKasirTableBody");
    if (!tbody) return;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-user-slash text-3xl mb-2 text-slate-300"></i>
            <p>Tidak ada data kasir yang cocok</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => {
      let shiftColor = "bg-blue-50 text-blue-700 border-blue-200";
      if (item.shift.includes("Pagi")) shiftColor = "bg-amber-50 text-amber-700 border-amber-200";
      if (item.shift.includes("Malam")) shiftColor = "bg-purple-50 text-purple-700 border-purple-200";

      return `
        <tr class="hover:bg-slate-50/80 transition">
          <td class="py-3 px-4 font-mono font-bold text-slate-700">${item.id_kasir}</td>
          <td class="py-3 px-4">
            <div class="font-bold text-slate-900">${item.nama_karyawan}</div>
            <div class="text-xs text-slate-400">ID Karyawan: ${item.id_karyawan}</div>
          </td>
          <td class="py-3 px-4">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border ${shiftColor}">
              ${item.shift}
            </span>
          </td>
          <td class="py-3 px-4 font-mono text-slate-600">${item.no_telepon}</td>
          <td class="py-3 px-4 text-xs text-slate-500 max-w-xs truncate">${item.alamat}</td>
          <td class="py-3 px-4 text-center">
            <div class="flex items-center justify-center space-x-1.5">
              <button onclick="app.openKasirModal('${item.id_kasir}')" title="Edit Kasir"
                class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button onclick="app.deleteKasirConfirm('${item.id_kasir}', '${item.nama_karyawan.replace(/'/g, "\\'")}')" title="Hapus Kasir"
                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }).join("");
  }

  openKasirModal(id = null) {
    const isEdit = Boolean(id);
    const kasir = isEdit ? window.store.getKasirById(id) : null;

    const fieldsHtml = `
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">ID Kasir</label>
          <input type="text" name="id_kasir" value="${kasir?.id_kasir || ''}" ${isEdit ? 'readonly class="w-full px-3 py-2 text-sm rounded-xl bg-slate-100 border border-slate-200 text-slate-500 font-mono"' : 'placeholder="Otomatis (contoh: KAS-05)" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono"'}>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Shift Kerja <span class="text-rose-500">*</span></label>
          <select name="shift" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white">
            <option value="Pagi (07:00 - 15:00)" ${kasir?.shift?.includes("Pagi") ? "selected" : ""}>Pagi (07:00 - 15:00)</option>
            <option value="Siang (15:00 - 23:00)" ${kasir?.shift?.includes("Siang") ? "selected" : ""}>Siang (15:00 - 23:00)</option>
            <option value="Malam (23:00 - 07:00)" ${kasir?.shift?.includes("Malam") ? "selected" : ""}>Malam (23:00 - 07:00)</option>
            <option value="Fleksibel / Supervisor" ${kasir?.shift?.includes("Fleksibel") ? "selected" : ""}>Fleksibel / Supervisor</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Kasir / Karyawan <span class="text-rose-500">*</span></label>
        <input type="text" name="nama_karyawan" required value="${kasir?.nama_karyawan || ''}" placeholder="Nama lengkap..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / WhatsApp <span class="text-rose-500">*</span></label>
          <input type="text" name="no_telepon" required value="${kasir?.no_telepon || ''}" placeholder="08xxxxxxxxxx"
            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">ID Karyawan</label>
          <input type="text" name="id_karyawan" value="${kasir?.id_karyawan || ''}" placeholder="Otomatis terhubung"
            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-mono">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Domisili</label>
        <textarea name="alamat" rows="2" placeholder="Alamat tempat tinggal..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">${kasir?.alamat || ''}</textarea>
      </div>
    `;

    this.showCrudModal({
      title: isEdit ? `Edit Kasir: ${kasir.nama_karyawan}` : "Tambah Kasir Baru",
      icon: "fa-solid fa-cash-register",
      themeColor: "emerald",
      entity: "kasir",
      isEdit,
      fieldsHtml
    });
  }

  deleteKasirConfirm(id, name) {
    if (confirm(`Hapus data kasir "${name}" (${id})?`)) {
      window.store.deleteKasir(id);
      this.toast(`Data kasir ${name} berhasil dihapus!`, "success");
      this.renderKasirTable();
      this.renderStats();
    }
  }

  // ========================================================
  // SISTEM SUPERMARKET: PELANGGAN CRUD
  // ========================================================
  renderPelangganTable() {
    const searchVal = document.getElementById("smPelangganSearch")?.value || "";
    const list = window.store.getPelangganList(searchVal);
    const tbody = document.getElementById("smPelangganTableBody");
    if (!tbody) return;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-users-slash text-3xl mb-2 text-slate-300"></i>
            <p>Tidak ada data pelanggan yang cocok</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => {
      let tierBadge = "";
      if (item.poin_member >= 200) {
        tierBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-crown text-amber-500 mr-1"></i>Platinum</span>`;
      } else if (item.poin_member >= 100) {
        tierBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-50 text-yellow-800 border border-yellow-200"><i class="fa-solid fa-medal text-yellow-500 mr-1"></i>Gold</span>`;
      } else {
        tierBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Silver Member</span>`;
      }

      return `
        <tr class="hover:bg-slate-50/80 transition">
          <td class="py-3 px-4 font-mono font-bold text-slate-700">${item.id_pelanggan}</td>
          <td class="py-3 px-4 font-bold text-slate-900">${item.nama_pelanggan}</td>
          <td class="py-3 px-4 font-mono text-slate-600">${item.no_telepon || "-"}</td>
          <td class="py-3 px-4 text-center font-mono font-black text-emerald-700">${item.poin_member} Pts</td>
          <td class="py-3 px-4 text-center">${tierBadge}</td>
          <td class="py-3 px-4 text-center">
            <div class="flex items-center justify-center space-x-1.5">
              <button onclick="app.openPelangganModal('${item.id_pelanggan}')" title="Edit Pelanggan"
                class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button onclick="app.deletePelangganConfirm('${item.id_pelanggan}', '${item.nama_pelanggan.replace(/'/g, "\\'")}')" title="Hapus Pelanggan"
                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }).join("");
  }

  openPelangganModal(id = null) {
    const isEdit = Boolean(id);
    const pel = isEdit ? window.store.getPelangganById(id) : null;

    const fieldsHtml = `
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">ID Pelanggan</label>
          <input type="text" name="id_pelanggan" value="${pel?.id_pelanggan || ''}" ${isEdit ? 'readonly class="w-full px-3 py-2 text-sm rounded-xl bg-slate-100 border border-slate-200 text-slate-500 font-mono"' : 'placeholder="Otomatis (contoh: PLG-006)" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono"'}>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Poin Member</label>
          <input type="number" name="poin_member" min="0" value="${pel !== null ? pel.poin_member : 0}"
            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Pelanggan <span class="text-rose-500">*</span></label>
        <input type="text" name="nama_pelanggan" required value="${pel?.nama_pelanggan || ''}" placeholder="Nama member..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / WhatsApp <span class="text-rose-500">*</span></label>
        <input type="text" name="no_telepon" required value="${pel?.no_telepon || ''}" placeholder="08xxxxxxxxxx"
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
      </div>
    `;

    this.showCrudModal({
      title: isEdit ? `Edit Member: ${pel.nama_pelanggan}` : "Tambah Member Baru",
      icon: "fa-solid fa-user-plus",
      themeColor: "emerald",
      entity: "pelanggan",
      isEdit,
      fieldsHtml
    });
  }

  deletePelangganConfirm(id, name) {
    if (confirm(`Hapus data pelanggan "${name}" (${id})?`)) {
      window.store.deletePelanggan(id);
      this.toast(`Pelanggan ${name} berhasil dihapus!`, "success");
      this.renderPelangganTable();
      this.renderStats();
    }
  }

  // ========================================================
  // SISTEM SUPERMARKET: POS (TRANSAKSI PENJUALAN)
  // ========================================================
  populatePosDropdowns() {
    // Populate Kasir
    const kasirSelect = document.getElementById("posKasirSelect");
    if (kasirSelect) {
      const kasirList = window.store.getKasirList();
      kasirSelect.innerHTML = kasirList.map(k => `
        <option value="${k.id_kasir}">${k.nama_karyawan} (${k.shift})</option>
      `).join("");
    }

    // Populate Pelanggan
    const pelSelect = document.getElementById("posPelangganSelect");
    if (pelSelect) {
      const pelList = window.store.getPelangganList();
      let html = '<option value="">-- Pelanggan Umum (Non-Member) --</option>';
      pelList.forEach(p => {
        html += `<option value="${p.id_pelanggan}">${p.nama_pelanggan} (Poin: ${p.poin_member})</option>`;
      });
      pelSelect.innerHTML = html;
    }

    // Populate Produk
    const prodSelect = document.getElementById("posProdukSelect");
    if (prodSelect) {
      const prodList = window.store.getProdukList();
      let html = '<option value="">-- Pilih Produk --</option>';
      prodList.forEach(p => {
        html += `<option value="${p.id_produk}" data-price="${p.harga_jual}" data-stock="${p.stok}">
          ${p.nama_produk} - ${this.formatRupiah(p.harga_jual)} (Stok: ${p.stok})
        </option>`;
      });
      prodSelect.innerHTML = html;
      this.onPosProdukSelectChange();
    }
  }

  onPosPelangganChange() {
    const sel = document.getElementById("posPelangganSelect");
    const info = document.getElementById("posPelangganInfo");
    if (!sel || !info) return;

    if (sel.value) {
      const pel = window.store.getPelangganById(sel.value);
      if (pel) {
        info.innerHTML = `<span class="text-emerald-700 font-semibold"><i class="fa-solid fa-id-card mr-1"></i> Member Aktif: ${pel.nama_pelanggan} | Poin Saat Ini: ${pel.poin_member} pts</span>`;
        return;
      }
    }
    info.textContent = "Pelanggan Umum (Tanpa akumulasi poin member)";
  }

  onPosProdukSelectChange() {
    const sel = document.getElementById("posProdukSelect");
    const hargaEl = document.getElementById("posHargaPreview");
    const stokEl = document.getElementById("posStokPreview");
    const qtyInput = document.getElementById("posInputQty");

    if (!sel || !sel.value) {
      if (hargaEl) hargaEl.value = "Rp 0";
      if (stokEl) stokEl.value = "0 item";
      if (qtyInput) qtyInput.max = 0;
      return;
    }

    const opt = sel.options[sel.selectedIndex];
    const price = Number(opt.getAttribute("data-price")) || 0;
    const stock = Number(opt.getAttribute("data-stock")) || 0;

    if (hargaEl) hargaEl.value = this.formatRupiah(price);
    if (stokEl) {
      stokEl.value = `${stock} pcs`;
      stokEl.className = stock > 5 
        ? "w-full px-3 py-2 text-sm rounded-xl bg-slate-50 border border-slate-200 text-emerald-700 font-bold"
        : "w-full px-3 py-2 text-sm rounded-xl bg-rose-50 border border-rose-200 text-rose-700 font-bold";
    }

    if (qtyInput) {
      qtyInput.max = stock;
      qtyInput.value = stock > 0 ? 1 : 0;
    }
  }

  addItemToCart() {
    const prodSelect = document.getElementById("posProdukSelect");
    const qtyInput = document.getElementById("posInputQty");
    if (!prodSelect || !prodSelect.value) {
      this.toast("Pilih produk terlebih dahulu!", "warning");
      return;
    }

    const id_produk = prodSelect.value;
    const prod = window.store.getProdukById(id_produk);
    if (!prod) return;

    const qty = parseInt(qtyInput?.value || "1", 10);
    if (isNaN(qty) || qty <= 0) {
      this.toast("Jumlah kuantitas tidak valid!", "warning");
      return;
    }

    // Periksa apakah item sudah ada di keranjang
    const existing = this.cart.find(c => c.id_produk === id_produk);
    const currentCartQty = existing ? existing.jumlah : 0;
    const totalRequestedQty = currentCartQty + qty;

    if (totalRequestedQty > prod.stok) {
      this.toast(`Stok tidak mencukupi! Tersedia ${prod.stok}, di keranjang sudah ada ${currentCartQty}`, "error");
      return;
    }

    if (existing) {
      existing.jumlah += qty;
      existing.subtotal = existing.jumlah * existing.harga_jual;
    } else {
      this.cart.push({
        id_produk: prod.id_produk,
        nama_produk: prod.nama_produk,
        harga_jual: prod.harga_jual,
        jumlah: qty,
        subtotal: qty * prod.harga_jual
      });
    }

    this.toast(`"${prod.nama_produk}" dimasukkan ke keranjang`, "success");
    this.renderCartTable();

    // Reset qty input
    if (qtyInput) qtyInput.value = 1;
  }

  updateCartQty(id_produk, delta) {
    const item = this.cart.find(c => c.id_produk === id_produk);
    if (!item) return;

    const prod = window.store.getProdukById(id_produk);
    const newQty = item.jumlah + delta;

    if (newQty <= 0) {
      this.removeCartItem(id_produk);
      return;
    }

    if (prod && newQty > prod.stok) {
      this.toast(`Stok maksimum hanya ${prod.stok}!`, "warning");
      return;
    }

    item.jumlah = newQty;
    item.subtotal = item.jumlah * item.harga_jual;
    this.renderCartTable();
  }

  removeCartItem(id_produk) {
    this.cart = this.cart.filter(c => c.id_produk !== id_produk);
    this.renderCartTable();
  }

  clearCart() {
    if (this.cart.length === 0) return;
    this.cart = [];
    this.renderCartTable();
    this.toast("Keranjang belanja dikosongkan", "info");
  }

  renderCartTable() {
    const tbody = document.getElementById("posCartTableBody");
    const countBadge = document.getElementById("posCartItemCount");
    const totalItemsEl = document.getElementById("posSummaryTotalItems");
    const grandTotalEl = document.getElementById("posSummaryGrandTotal");

    const totalQty = this.cart.reduce((sum, i) => sum + i.jumlah, 0);
    const grandTotal = this.cart.reduce((sum, i) => sum + i.subtotal, 0);

    if (countBadge) countBadge.textContent = `${this.cart.length} Item`;
    if (totalItemsEl) totalItemsEl.textContent = `${totalQty} pcs`;
    if (grandTotalEl) grandTotalEl.textContent = this.formatRupiah(grandTotal);

    this.calculateChange();

    if (!tbody) return;

    if (this.cart.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="5" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-basket-shopping text-2xl mb-1 text-slate-300"></i>
            <p>Keranjang masih kosong</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = this.cart.map(item => `
      <tr class="hover:bg-slate-50">
        <td class="py-2 px-3 font-semibold text-slate-800">
          <div>${item.nama_produk}</div>
          <div class="text-[10px] text-slate-400 font-mono">${item.id_produk}</div>
        </td>
        <td class="py-2 px-3 text-right font-mono">${this.formatRupiah(item.harga_jual)}</td>
        <td class="py-2 px-3 text-center">
          <div class="inline-flex items-center space-x-1 border border-slate-200 rounded-lg p-0.5">
            <button onclick="app.updateCartQty('${item.id_produk}', -1)" class="w-5 h-5 rounded hover:bg-slate-200 flex items-center justify-center font-bold text-slate-700">-</button>
            <span class="w-6 text-center font-bold">${item.jumlah}</span>
            <button onclick="app.updateCartQty('${item.id_produk}', 1)" class="w-5 h-5 rounded hover:bg-slate-200 flex items-center justify-center font-bold text-slate-700">+</button>
          </div>
        </td>
        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">${this.formatRupiah(item.subtotal)}</td>
        <td class="py-2 px-3 text-center">
          <button onclick="app.removeCartItem('${item.id_produk}')" title="Hapus Item"
            class="text-rose-500 hover:text-rose-700 text-xs p-1">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </td>
      </tr>
    `).join("");
  }

  calculateChange() {
    const grandTotal = this.cart.reduce((sum, i) => sum + i.subtotal, 0);
    const cashInput = document.getElementById("posCashGiven");
    const changeAmountEl = document.getElementById("posChangeAmount");
    const changeContainer = document.getElementById("posChangeContainer");

    if (!cashInput || !changeAmountEl) return;
    const cash = Number(cashInput.value) || 0;

    if (cash >= grandTotal && grandTotal > 0) {
      const change = cash - grandTotal;
      changeAmountEl.textContent = this.formatRupiah(change);
      if (changeContainer) {
        changeContainer.className = "p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-emerald-800";
      }
    } else if (cash > 0 && cash < grandTotal) {
      const def = grandTotal - cash;
      changeAmountEl.textContent = `Kurang: ${this.formatRupiah(def)}`;
      if (changeContainer) {
        changeContainer.className = "p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between text-rose-800";
      }
    } else {
      changeAmountEl.textContent = "Rp 0";
      if (changeContainer) {
        changeContainer.className = "p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-slate-600";
      }
    }
  }

  processCheckout() {
    if (this.cart.length === 0) {
      this.toast("Keranjang belanja masih kosong!", "warning");
      return;
    }

    const kasirSelect = document.getElementById("posKasirSelect");
    const pelSelect = document.getElementById("posPelangganSelect");
    const paymentMethodSelect = document.getElementById("posPaymentMethod");
    const cashInput = document.getElementById("posCashGiven");

    const id_kasir = kasirSelect?.value;
    const id_pelanggan = pelSelect?.value || null;
    const metode_pembayaran = paymentMethodSelect?.value || "Tunai";

    const grandTotal = this.cart.reduce((sum, i) => sum + i.subtotal, 0);
    const cash = Number(cashInput?.value || 0);

    if (metode_pembayaran === "Tunai" && cash < grandTotal) {
      this.toast(`Nominal uang tunai kurang dari total belanja (${this.formatRupiah(grandTotal)})!`, "error");
      return;
    }

    try {
      const result = window.store.createTransaksi({
        id_kasir,
        id_pelanggan,
        metode_pembayaran,
        items: this.cart,
        catatan: `Dibayar dengan ${metode_pembayaran}`
      });

      this.toast(`Transaksi ${result.transaksi.id_transaksi} Berhasil Disimpan!`, "success");

      // Buka modal cetak struk
      this.showReceiptModal(result.transaksi.id_transaksi, cash);

      // Bersihkan keranjang dan perbarui tampilan
      this.cart = [];
      if (cashInput) cashInput.value = "";
      this.renderCartTable();
      this.renderProdukTable();
      this.renderPelangganTable();
      this.populatePosDropdowns();
      this.renderRiwayatTable();
      this.renderStats();

    } catch (err) {
      this.toast(err.message, "error");
    }
  }

  // ========================================================
  // SISTEM SUPERMARKET: RIWAYAT & STRUK KASIR
  // ========================================================
  renderRiwayatTable() {
    const searchVal = document.getElementById("smRiwayatSearch")?.value || "";
    const list = window.store.getTransaksiList(searchVal);
    const tbody = document.getElementById("smRiwayatTableBody");
    if (!tbody) return;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
            <p>Belum ada riwayat transaksi</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => `
      <tr class="hover:bg-slate-50/80 transition">
        <td class="py-3 px-4 font-mono font-bold text-emerald-800">${item.id_transaksi}</td>
        <td class="py-3 px-4 text-xs text-slate-600 font-mono">${item.tanggal_transaksi}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${item.nama_kasir}</td>
        <td class="py-3 px-4 text-slate-700">${item.nama_pelanggan}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
            ${item.metode_pembayaran}
          </span>
        </td>
        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">${this.formatRupiah(item.total_bayar)}</td>
        <td class="py-3 px-4 text-center">
          <div class="flex items-center justify-center space-x-1.5">
            <button onclick="app.showReceiptModal('${item.id_transaksi}')" title="Lihat / Cetak Struk"
              class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white transition text-xs font-semibold flex items-center gap-1">
              <i class="fa-solid fa-print"></i>
              <span>Struk</span>
            </button>
            <button onclick="app.deleteTransaksiConfirm('${item.id_transaksi}')" title="Hapus Riwayat"
              class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </div>
        </td>
      </tr>
    `).join("");
  }

  showReceiptModal(id_transaksi, cashGiven = null) {
    const detail = window.store.getTransaksiDetail(id_transaksi);
    if (!detail) {
      this.toast("Data transaksi tidak ditemukan!", "error");
      return;
    }

    const modal = document.getElementById("receiptModal");
    const content = document.getElementById("receiptContent");
    if (!modal || !content) return;

    const trx = detail.transaksi;
    const kasir = detail.kasir;
    const pelanggan = detail.pelanggan;
    const items = detail.items;

    const cash = cashGiven !== null ? cashGiven : trx.total_bayar;
    const change = Math.max(0, cash - trx.total_bayar);

    content.innerHTML = `
      <div class="text-center pb-3 border-b border-dashed border-slate-300">
        <h2 class="text-base font-black tracking-widest text-slate-900">SUPERMARKET INDAH</h2>
        <p class="text-[11px] text-slate-500">Jl. Jenderal Sudirman No. 88, Jakarta</p>
        <p class="text-[10px] text-slate-400">Telp: 021-555-8989 | NPWP: 01.345.678.9-012.000</p>
      </div>

      <div class="py-2.5 border-b border-dashed border-slate-300 space-y-1 text-[11px]">
        <div class="flex justify-between">
          <span>No. Struk:</span>
          <span class="font-bold">${trx.id_transaksi}</span>
        </div>
        <div class="flex justify-between">
          <span>Tanggal:</span>
          <span>${trx.tanggal_transaksi}</span>
        </div>
        <div class="flex justify-between">
          <span>Kasir:</span>
          <span>${kasir.nama_karyawan}</span>
        </div>
        <div class="flex justify-between">
          <span>Pelanggan:</span>
          <span>${pelanggan.nama_pelanggan}</span>
        </div>
      </div>

      <!-- Item List -->
      <div class="py-2.5 border-b border-dashed border-slate-300 space-y-1.5 text-[11px]">
        ${items.map(item => `
          <div>
            <div class="font-bold text-slate-800">${item.nama_produk}</div>
            <div class="flex justify-between text-slate-600">
              <span>${item.jumlah} x ${this.formatRupiah(item.harga_jual)}</span>
              <span class="font-bold">${this.formatRupiah(item.subtotal)}</span>
            </div>
          </div>
        `).join("")}
      </div>

      <!-- Total & Payment -->
      <div class="py-2.5 space-y-1 text-[11px]">
        <div class="flex justify-between font-bold text-slate-900 text-xs">
          <span>TOTAL:</span>
          <span>${this.formatRupiah(trx.total_bayar)}</span>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>Metode:</span>
          <span>${trx.metode_pembayaran}</span>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>Bayar:</span>
          <span>${this.formatRupiah(cash)}</span>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>Kembalian:</span>
          <span class="font-bold text-emerald-700">${this.formatRupiah(change)}</span>
        </div>
      </div>

      <div class="pt-3 border-t border-dashed border-slate-300 text-center text-[10px] text-slate-400 space-y-1">
        <p class="font-semibold text-slate-600">Terima Kasih Telah Berbelanja!</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar kembali.</p>
        <p class="font-mono pt-1 tracking-widest text-[9px] text-slate-300">*** ${trx.id_transaksi} ***</p>
      </div>
    `;

    modal.classList.remove("hidden");
  }

  closeReceiptModal() {
    document.getElementById("receiptModal")?.classList.add("hidden");
  }

  deleteTransaksiConfirm(id) {
    if (confirm(`Hapus catatan transaksi "${id}"?`)) {
      window.store.deleteTransaksi(id);
      this.toast(`Transaksi ${id} dihapus!`, "success");
      this.renderRiwayatTable();
      this.renderStats();
    }
  }

  // ========================================================
  // SISTEM PERPAJAKAN: KATEGORI PAJAK CRUD
  // ========================================================
  renderAllPerpajakanViews() {
    this.renderTaxKategoriTable();
    this.renderTaxJenisCategoryFilter();
    this.renderTaxJenisTable();
    this.renderTaxPenggunaTable();
    this.renderTaxPembayaranTable();
  }

  renderTaxKategoriTable() {
    const searchVal = document.getElementById("taxKategoriSearch")?.value || "";
    const list = window.store.getKategoriPajakList(searchVal);
    const tbody = document.getElementById("taxKategoriTableBody");
    if (!tbody) return;

    const allJenis = window.store.getJenisPajakList();

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="5" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300"></i>
            <p>Tidak ada kategori pajak yang cocok</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => {
      const countJenis = allJenis.filter(j => j.id_kategori === item.id_kategori).length;
      return `
        <tr class="hover:bg-slate-50/80 transition">
          <td class="py-3 px-4 font-mono font-bold text-blue-900">${item.id_kategori}</td>
          <td class="py-3 px-4 font-bold text-slate-900">${item.nama_kategori}</td>
          <td class="py-3 px-4 text-xs text-slate-600 max-w-sm">${item.keterangan || "-"}</td>
          <td class="py-3 px-4 text-center">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
              ${countJenis} Jenis
            </span>
          </td>
          <td class="py-3 px-4 text-center">
            <div class="flex items-center justify-center space-x-1.5">
              <button onclick="app.openTaxKategoriModal('${item.id_kategori}')" title="Edit Kategori"
                class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button onclick="app.deleteTaxKategoriConfirm('${item.id_kategori}', '${item.nama_kategori.replace(/'/g, "\\'")}')" title="Hapus Kategori"
                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }).join("");
  }

  openTaxKategoriModal(id = null) {
    const isEdit = Boolean(id);
    const kat = isEdit ? window.store.getKategoriPajakById(id) : null;

    const fieldsHtml = `
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kategori Pajak</label>
        <input type="text" name="id_kategori" value="${kat?.id_kategori || ''}" ${isEdit ? 'readonly class="w-full px-3 py-2 text-sm rounded-xl bg-slate-100 border border-slate-200 text-slate-500 font-mono"' : 'placeholder="Otomatis (contoh: KAT-05)" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono"'}>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori Pajak <span class="text-rose-500">*</span></label>
        <input type="text" name="nama_kategori" required value="${kat?.nama_kategori || ''}" placeholder="Contoh: Pajak Daerah, Pajak Karbon..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Dasar Regulasi</label>
        <textarea name="keterangan" rows="3" placeholder="Penjelasan objek pajak atau undang-undang terkait..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">${kat?.keterangan || ''}</textarea>
      </div>
    `;

    this.showCrudModal({
      title: isEdit ? `Edit Kategori: ${kat.nama_kategori}` : "Tambah Kategori Pajak Baru",
      icon: "fa-solid fa-folder-tree",
      themeColor: "blue",
      entity: "kategori_pajak",
      isEdit,
      fieldsHtml
    });
  }

  deleteTaxKategoriConfirm(id, name) {
    if (confirm(`Hapus kategori pajak "${name}" (${id})?`)) {
      try {
        window.store.deleteKategoriPajak(id);
        this.toast(`Kategori ${name} berhasil dihapus!`, "success");
        this.renderTaxKategoriTable();
        this.renderTaxJenisCategoryFilter();
        this.renderStats();
      } catch (err) {
        this.toast(err.message, "error");
      }
    }
  }

  // ========================================================
  // SISTEM PERPAJAKAN: JENIS PAJAK CRUD
  // ========================================================
  renderTaxJenisCategoryFilter() {
    const filterSelect = document.getElementById("taxJenisCategoryFilter");
    if (!filterSelect) return;
    const currentVal = filterSelect.value;
    const categories = window.store.getKategoriPajakList();

    let options = '<option value="">Semua Kategori</option>';
    categories.forEach(c => {
      options += `<option value="${c.id_kategori}" ${c.id_kategori === currentVal ? "selected" : ""}>${c.nama_kategori}</option>`;
    });
    filterSelect.innerHTML = options;
  }

  renderTaxJenisTable() {
    const searchVal = document.getElementById("taxJenisSearch")?.value || "";
    const catVal = document.getElementById("taxJenisCategoryFilter")?.value || "";
    const list = window.store.getJenisPajakList(searchVal, catVal);
    const tbody = document.getElementById("taxJenisTableBody");
    if (!tbody) return;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="5" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-scale-unbalanced text-3xl mb-2 text-slate-300"></i>
            <p>Tidak ada jenis pajak yang cocok</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => `
      <tr class="hover:bg-slate-50/80 transition">
        <td class="py-3 px-4 font-mono font-bold text-blue-900">${item.id_pajak}</td>
        <td class="py-3 px-4 font-bold text-slate-900">${item.nama_pajak}</td>
        <td class="py-3 px-4">
          <span class="inline-block px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
            ${item.nama_kategori}
          </span>
        </td>
        <td class="py-3 px-4 font-semibold text-blue-700">${item.tarif_pajak}</td>
        <td class="py-3 px-4 text-center">
          <div class="flex items-center justify-center space-x-1.5">
            <button onclick="app.openTaxJenisModal('${item.id_pajak}')" title="Edit Jenis Pajak"
              class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white transition flex items-center justify-center text-xs">
              <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button onclick="app.deleteTaxJenisConfirm('${item.id_pajak}', '${item.nama_pajak.replace(/'/g, "\\'")}')" title="Hapus Jenis Pajak"
              class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </div>
        </td>
      </tr>
    `).join("");
  }

  openTaxJenisModal(id = null) {
    const isEdit = Boolean(id);
    const jns = isEdit ? window.store.getJenisPajakById(id) : null;
    const categories = window.store.getKategoriPajakList();

    const katOptions = categories.map(k => `
      <option value="${k.id_kategori}" ${jns?.id_kategori === k.id_kategori ? "selected" : ""}>
        ${k.nama_kategori} (${k.id_kategori})
      </option>
    `).join("");

    const fieldsHtml = `
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">ID Jenis Pajak</label>
          <input type="text" name="id_pajak" value="${jns?.id_pajak || ''}" ${isEdit ? 'readonly class="w-full px-3 py-2 text-sm rounded-xl bg-slate-100 border border-slate-200 text-slate-500 font-mono"' : 'placeholder="Otomatis (contoh: PJK-07)" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono"'}>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Pajak <span class="text-rose-500">*</span></label>
          <select name="id_kategori" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
            ${katOptions}
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Jenis Pajak <span class="text-rose-500">*</span></label>
        <input type="text" name="nama_pajak" required value="${jns?.nama_pajak || ''}" placeholder="Contoh: PPh Pasal 21, Pajak Karbon..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tarif Pajak Resmi <span class="text-rose-500">*</span></label>
        <input type="text" name="tarif_pajak" required value="${jns?.tarif_pajak || ''}" placeholder="Contoh: 11%, 0.5% Omzet, Rp 30 per kg..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-medium">
      </div>
    `;

    this.showCrudModal({
      title: isEdit ? `Edit Jenis Pajak: ${jns.nama_pajak}` : "Tambah Jenis Pajak Baru",
      icon: "fa-solid fa-scale-balanced",
      themeColor: "blue",
      entity: "jenis_pajak",
      isEdit,
      fieldsHtml
    });
  }

  deleteTaxJenisConfirm(id, name) {
    if (confirm(`Hapus jenis pajak "${name}" (${id})?`)) {
      try {
        window.store.deleteJenisPajak(id);
        this.toast(`Jenis pajak ${name} berhasil dihapus!`, "success");
        this.renderTaxJenisTable();
        this.renderStats();
      } catch (err) {
        this.toast(err.message, "error");
      }
    }
  }

  // ========================================================
  // SISTEM PERPAJAKAN: PENGGUNA / WAJIB PAJAK CRUD
  // ========================================================
  renderTaxPenggunaTable() {
    const searchVal = document.getElementById("taxPenggunaSearch")?.value || "";
    const tipeVal = document.getElementById("taxPenggunaTypeFilter")?.value || "";
    const list = window.store.getPenggunaList(searchVal, tipeVal);
    const tbody = document.getElementById("taxPenggunaTableBody");
    if (!tbody) return;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-user-xmark text-3xl mb-2 text-slate-300"></i>
            <p>Tidak ada data pengguna yang cocok</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => {
      let typeBadge = "";
      if (item.tipe_pengguna === "Perusahaan") {
        typeBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200"><i class="fa-solid fa-building mr-1"></i>Perusahaan</span>`;
      } else if (item.tipe_pengguna === "Petugas Pajak") {
        typeBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200"><i class="fa-solid fa-user-tie mr-1"></i>Petugas Pajak</span>`;
      } else {
        typeBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200"><i class="fa-solid fa-user mr-1"></i>Perorangan</span>`;
      }

      return `
        <tr class="hover:bg-slate-50/80 transition">
          <td class="py-3 px-4 font-mono font-bold text-blue-900">${item.id_pengguna}</td>
          <td class="py-3 px-4 font-bold text-slate-900">${item.nama_pengguna}</td>
          <td class="py-3 px-4">${typeBadge}</td>
          <td class="py-3 px-4 font-mono font-semibold text-slate-800">${item.npwp || "-"}</td>
          <td class="py-3 px-4 font-mono text-slate-600">${item.no_telepon || "-"}</td>
          <td class="py-3 px-4 text-xs text-slate-500 max-w-xs truncate">${item.alamat || "-"}</td>
          <td class="py-3 px-4 text-center">
            <div class="flex items-center justify-center space-x-1.5">
              <button onclick="app.openTaxPenggunaModal('${item.id_pengguna}')" title="Edit Pengguna"
                class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button onclick="app.deleteTaxPenggunaConfirm('${item.id_pengguna}', '${item.nama_pengguna.replace(/'/g, "\\'")}')" title="Hapus Pengguna"
                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }).join("");
  }

  openTaxPenggunaModal(id = null) {
    const isEdit = Boolean(id);
    const usr = isEdit ? window.store.getPenggunaById(id) : null;

    const fieldsHtml = `
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">ID Pengguna</label>
          <input type="text" name="id_pengguna" value="${usr?.id_pengguna || ''}" ${isEdit ? 'readonly class="w-full px-3 py-2 text-sm rounded-xl bg-slate-100 border border-slate-200 text-slate-500 font-mono"' : 'placeholder="Otomatis (contoh: USR-007)" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono"'}>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Pengguna <span class="text-rose-500">*</span></label>
          <select name="tipe_pengguna" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white font-semibold">
            <option value="Perorangan" ${usr?.tipe_pengguna === "Perorangan" ? "selected" : ""}>Perorangan (Wajib Pajak)</option>
            <option value="Perusahaan" ${usr?.tipe_pengguna === "Perusahaan" ? "selected" : ""}>Perusahaan (Badan Usaha)</option>
            <option value="Petugas Pajak" ${usr?.tipe_pengguna === "Petugas Pajak" ? "selected" : ""}>Petugas Pajak (Fiskus / Verifikator)</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap / Nama Entitas <span class="text-rose-500">*</span></label>
        <input type="text" name="nama_pengguna" required value="${usr?.nama_pengguna || ''}" placeholder="Contoh: PT. Maju Berkah / Budi Santoso"
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Pokok Wajib Pajak (NPWP) <span class="text-rose-500">*</span></label>
          <input type="text" name="npwp" required value="${usr?.npwp || ''}" placeholder="00.000.000.0-000.000"
            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono font-bold">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / Kontak</label>
          <input type="text" name="no_telepon" value="${usr?.no_telepon || ''}" placeholder="08xxxxxxxxxx / 021-xxxxxx"
            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
        <textarea name="alamat" rows="2" placeholder="Alamat domisili atau kantor..."
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">${usr?.alamat || ''}</textarea>
      </div>
    `;

    this.showCrudModal({
      title: isEdit ? `Edit Pengguna: ${usr.nama_pengguna}` : "Tambah Wajib Pajak / Petugas",
      icon: "fa-solid fa-address-card",
      themeColor: "blue",
      entity: "pengguna",
      isEdit,
      fieldsHtml
    });
  }

  deleteTaxPenggunaConfirm(id, name) {
    if (confirm(`Hapus data pengguna "${name}" (${id})?`)) {
      try {
        window.store.deletePengguna(id);
        this.toast(`Pengguna ${name} berhasil dihapus!`, "success");
        this.renderTaxPenggunaTable();
        this.populateTaxPaymentDropdowns();
        this.renderStats();
      } catch (err) {
        this.toast(err.message, "error");
      }
    }
  }

  // ========================================================
  // SISTEM PERPAJAKAN: PEMBAYARAN PAJAK
  // ========================================================
  populateTaxPaymentDropdowns() {
    // Populate Wajib Pajak (Hanya Perusahaan dan Perorangan)
    const wpSelect = document.getElementById("payWajibPajakSelect");
    if (wpSelect) {
      const wpList = window.store.getWajibPajakList();
      wpSelect.innerHTML = wpList.map(w => `
        <option value="${w.id_pengguna}" data-npwp="${w.npwp}">
          [${w.tipe_pengguna}] ${w.nama_pengguna} - NPWP: ${w.npwp}
        </option>
      `).join("");
      this.onTaxWpChange();
    }

    // Populate Jenis Pajak
    const pjkSelect = document.getElementById("payJenisPajakSelect");
    if (pjkSelect) {
      const pjkList = window.store.getJenisPajakList();
      pjkSelect.innerHTML = pjkList.map(j => `
        <option value="${j.id_pajak}" data-tarif="${j.tarif_pajak}">
          ${j.nama_pajak} (${j.nama_kategori})
        </option>
      `).join("");
      this.onTaxJenisChange();
    }

    // Populate Petugas Verifikator (Hanya Petugas Pajak)
    const petSelect = document.getElementById("payPetugasSelect");
    if (petSelect) {
      const petList = window.store.getPetugasPajakList();
      petSelect.innerHTML = petList.map(p => `
        <option value="${p.id_pengguna}">
          ${p.nama_pengguna} (NPWP: ${p.npwp})
        </option>
      `).join("");
    }
  }

  onTaxWpChange() {
    const sel = document.getElementById("payWajibPajakSelect");
    const info = document.getElementById("payWpNpwpInfo");
    if (!sel || !info) return;

    const opt = sel.options[sel.selectedIndex];
    const npwp = opt?.getAttribute("data-npwp") || "-";
    info.textContent = `Nomor Pokok Wajib Pajak: ${npwp}`;
  }

  onTaxJenisChange() {
    const sel = document.getElementById("payJenisPajakSelect");
    const info = document.getElementById("payTarifInfo");
    if (!sel || !info) return;

    const opt = sel.options[sel.selectedIndex];
    const tarif = opt?.getAttribute("data-tarif") || "-";
    info.textContent = `Ketentuan Tarif: ${tarif}`;
  }

  handleNewTaxPayment(e) {
    e.preventDefault();

    const id_pengguna = document.getElementById("payWajibPajakSelect")?.value;
    const id_pajak = document.getElementById("payJenisPajakSelect")?.value;
    const id_petugas = document.getElementById("payPetugasSelect")?.value;
    const jumlah_bayar = Number(document.getElementById("payJumlahBayar")?.value) || 0;
    const status_pembayaran = document.getElementById("payStatusSelect")?.value || "Lunas";
    const tanggalInput = document.getElementById("payTanggalBayar")?.value;

    let tanggal_bayar = "";
    if (tanggalInput) {
      tanggal_bayar = tanggalInput.replace("T", " ") + ":00";
    }

    if (!id_pengguna || !id_pajak || !id_petugas || jumlah_bayar <= 0) {
      this.toast("Lengkapi seluruh field pembayaran dengan benar!", "error");
      return;
    }

    try {
      const saved = window.store.savePembayaranPajak({
        id_pengguna,
        id_pajak,
        id_petugas,
        jumlah_bayar,
        status_pembayaran,
        tanggal_bayar
      });

      this.toast(`Pembayaran Pajak ${saved.id_pembayaran} berhasil dicatat!`, "success");

      // Reset form
      document.getElementById("payJumlahBayar").value = "";

      // Refresh table & stats
      this.renderTaxPembayaranTable();
      this.renderStats();

      // Show official tax receipt
      this.showTaxReceiptModal(saved.id_pembayaran);

    } catch (err) {
      this.toast(err.message, "error");
    }
  }

  renderTaxPembayaranTable() {
    const searchVal = document.getElementById("taxBayarSearch")?.value || "";
    const statusVal = document.getElementById("taxBayarStatusFilter")?.value || "";
    const list = window.store.getPembayaranPajakList(searchVal, statusVal);
    const tbody = document.getElementById("taxBayarTableBody");
    if (!tbody) return;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-file-circle-xmark text-3xl mb-2 text-slate-300"></i>
            <p>Tidak ada data pembayaran pajak yang cocok</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => {
      let statusBadge = "";
      if (item.status_pembayaran === "Lunas") {
        statusBadge = `
          <button onclick="app.togglePaymentStatus('${item.id_pembayaran}', 'Menunggu Verifikasi')" title="Klik untuk ubah status"
            class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition flex items-center gap-1 mx-auto">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>Lunas</span>
          </button>`;
      } else if (item.status_pembayaran === "Menunggu Verifikasi") {
        statusBadge = `
          <button onclick="app.togglePaymentStatus('${item.id_pembayaran}', 'Lunas')" title="Klik untuk validasi Lunas"
            class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 hover:bg-emerald-50 text-amber-800 hover:text-emerald-800 border border-amber-300 hover:border-emerald-300 transition flex items-center gap-1 mx-auto">
            <i class="fa-solid fa-clock text-amber-600"></i>
            <span>Verifikasi</span>
          </button>`;
      } else {
        statusBadge = `
          <button onclick="app.togglePaymentStatus('${item.id_pembayaran}', 'Lunas')" title="Klik untuk validasi Lunas"
            class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-300 transition flex items-center gap-1 mx-auto">
            <i class="fa-solid fa-hourglass-half text-slate-500"></i>
            <span>${item.status_pembayaran}</span>
          </button>`;
      }

      return `
        <tr class="hover:bg-slate-50/80 transition">
          <td class="py-3 px-4 font-mono font-bold text-blue-900">${item.id_pembayaran}</td>
          <td class="py-3 px-4 text-xs font-mono text-slate-600">${item.tanggal_bayar}</td>
          <td class="py-3 px-4">
            <div class="font-bold text-slate-900">${item.nama_pengguna}</div>
            <div class="text-xs font-mono text-slate-500">NPWP: ${item.npwp_pengguna}</div>
          </td>
          <td class="py-3 px-4">
            <div class="font-semibold text-blue-900">${item.nama_pajak}</div>
            <div class="text-[11px] text-slate-500">Tarif: ${item.tarif_pajak}</div>
          </td>
          <td class="py-3 px-4 text-xs font-medium text-slate-700">${item.nama_petugas}</td>
          <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700 text-sm">${this.formatRupiah(item.jumlah_bayar)}</td>
          <td class="py-3 px-4 text-center">${statusBadge}</td>
          <td class="py-3 px-4 text-center">
            <div class="flex items-center justify-center space-x-1.5">
              <button onclick="app.showTaxReceiptModal('${item.id_pembayaran}')" title="Cetak Bukti Penerimaan Elektronik (BPE)"
                class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white transition text-xs font-semibold flex items-center gap-1">
                <i class="fa-solid fa-certificate"></i>
                <span>BPE</span>
              </button>
              <button onclick="app.deleteTaxPembayaranConfirm('${item.id_pembayaran}')" title="Hapus Data Pembayaran"
                class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition flex items-center justify-center text-xs">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }).join("");
  }

  togglePaymentStatus(id_pembayaran, newStatus) {
    window.store.updateStatusPembayaranPajak(id_pembayaran, newStatus);
    this.toast(`Status ${id_pembayaran} diubah menjadi "${newStatus}"`, "info");
    this.renderTaxPembayaranTable();
    this.renderStats();
  }

  deleteTaxPembayaranConfirm(id) {
    if (confirm(`Hapus catatan pembayaran pajak "${id}"?`)) {
      window.store.deletePembayaranPajak(id);
      this.toast(`Catatan pembayaran ${id} berhasil dihapus!`, "success");
      this.renderTaxPembayaranTable();
      this.renderStats();
    }
  }

  showTaxReceiptModal(id_pembayaran) {
    const pay = window.store.getPembayaranPajakById(id_pembayaran);
    if (!pay) {
      this.toast("Data pembayaran pajak tidak ditemukan!", "error");
      return;
    }

    const modal = document.getElementById("taxReceiptModal");
    const content = document.getElementById("taxReceiptContent");
    if (!modal || !content) return;

    // Generate random NTPN (Nomor Transaksi Penerimaan Negara)
    const ntpn = "NTPN-" + id_pembayaran.replace(/[^0-9]/g, "") + String(Math.floor(1000 + Math.random() * 9000));
    const ntb = "NTB-987" + String(Math.floor(100000 + Math.random() * 900000));
    const terbilangStr = this.terbilang(pay.jumlah_bayar) + " Rupiah";

    content.innerHTML = `
      <!-- Header Dokumen Resmi -->
      <div class="border-b-2 border-slate-900 pb-3 text-center">
        <div class="flex items-center justify-center space-x-3 mb-1">
          <div class="w-10 h-10 rounded-full bg-blue-900 text-amber-400 flex items-center justify-center text-xl font-bold shadow">
            <i class="fa-solid fa-landmark"></i>
          </div>
          <div>
            <h2 class="text-xs font-bold tracking-widest text-slate-800 uppercase">KEMENTERIAN KEUANGAN REPUBLIK INDONESIA</h2>
            <h3 class="text-sm font-extrabold text-blue-950 uppercase tracking-wider">DIREKTORAT JENDERAL PAJAK</h3>
          </div>
        </div>
        <p class="text-[10px] text-slate-500">Sistem Informasi Penerimaan Pajak Elektronik Terpadu</p>
      </div>

      <!-- Judul Sertifikat -->
      <div class="py-3 text-center">
        <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest underline decoration-2 decoration-blue-900">
          BUKTI PENERIMAAN ELEKTRONIK (BPE)
        </h4>
        <p class="text-[11px] font-mono text-slate-600 mt-0.5">Surat Setoran Pajak / NTPN: <span class="font-bold text-blue-900">${ntpn}</span></p>
      </div>

      <!-- Tabel Rincian Wajib Pajak & Objek Pajak -->
      <div class="space-y-2 text-xs border border-slate-200 rounded-xl p-4 bg-slate-50/50">
        <div class="grid grid-cols-3 gap-2 py-1 border-b border-slate-200">
          <span class="text-slate-500 font-semibold">Nomor Transaksi Bank (NTB)</span>
          <span class="col-span-2 font-mono font-bold text-slate-800">${ntb}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1 border-b border-slate-200">
          <span class="text-slate-500 font-semibold">Tanggal & Waktu Setor</span>
          <span class="col-span-2 font-mono text-slate-800">${pay.tanggal_bayar}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1 border-b border-slate-200">
          <span class="text-slate-500 font-semibold">Nama Wajib Pajak</span>
          <span class="col-span-2 font-bold text-slate-900 uppercase">${pay.nama_pengguna} (${pay.tipe_pengguna})</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1 border-b border-slate-200">
          <span class="text-slate-500 font-semibold">NPWP Wajib Pajak</span>
          <span class="col-span-2 font-mono font-bold text-blue-900">${pay.npwp_pengguna}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1 border-b border-slate-200">
          <span class="text-slate-500 font-semibold">Jenis Setoran Pajak</span>
          <span class="col-span-2 font-bold text-slate-800">${pay.nama_pajak} (Tarif: ${pay.tarif_pajak})</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1 border-b border-slate-200">
          <span class="text-slate-500 font-semibold">Petugas Fiskus Verifikator</span>
          <span class="col-span-2 font-semibold text-slate-800">${pay.nama_petugas}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1.5 items-center">
          <span class="text-slate-600 font-bold">Jumlah Pembayaran</span>
          <span class="col-span-2 font-mono text-base font-black text-emerald-800">${this.formatRupiah(pay.jumlah_bayar)}</span>
        </div>
        <div class="grid grid-cols-3 gap-2 py-1 text-[11px] bg-slate-100 p-2 rounded-lg">
          <span class="text-slate-500 font-medium italic">Terbilang:</span>
          <span class="col-span-2 font-bold italic text-slate-800 capitalize">${terbilangStr}</span>
        </div>
      </div>

      <!-- Tanda Pengesahan Elektronik -->
      <div class="pt-4 flex items-center justify-between text-xs">
        <div class="text-center p-2 border border-slate-200 rounded-lg bg-white shadow-sm inline-block">
          <div class="w-16 h-16 bg-slate-100 flex items-center justify-center rounded border border-dashed border-slate-300 mx-auto">
            <i class="fa-solid fa-qrcode text-3xl text-slate-800"></i>
          </div>
          <span class="text-[9px] font-mono text-slate-400 block mt-1">VERIFIKASI DJP</span>
        </div>

        <div class="text-right space-y-1">
          <div class="inline-block px-3 py-1 rounded-full text-xs font-black ${pay.status_pembayaran === 'Lunas' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'}">
            STATUS: ${pay.status_pembayaran.toUpperCase()}
          </div>
          <p class="text-[10px] text-slate-500">Dicetak melalui Sistem Basis Data Terintegrasi</p>
          <p class="text-[10px] text-slate-400 font-mono">ID Validasi: ${pay.id_pembayaran}</p>
        </div>
      </div>
    `;

    modal.classList.remove("hidden");
  }

  closeTaxReceiptModal() {
    document.getElementById("taxReceiptModal")?.classList.add("hidden");
  }

  // ========================================================
  // DYNAMIC CRUD MODAL HANDLER
  // ========================================================
  showCrudModal({ title, icon, themeColor = "emerald", entity, isEdit, fieldsHtml }) {
    this.currentModalEntity = entity;
    this.currentModalMode = isEdit ? "edit" : "create";

    const modal = document.getElementById("crudModal");
    const header = document.getElementById("crudModalHeader");
    const titleEl = document.getElementById("crudModalTitle");
    const iconEl = document.getElementById("crudModalIcon");
    const fieldsContainer = document.getElementById("crudFormFields");
    const submitBtn = document.getElementById("crudSubmitBtn");

    if (titleEl) titleEl.textContent = title;
    if (iconEl) iconEl.className = `${icon} text-lg`;
    if (fieldsContainer) fieldsContainer.innerHTML = fieldsHtml;

    if (themeColor === "emerald") {
      header.className = "px-6 py-4 bg-emerald-600 text-white flex items-center justify-between";
      submitBtn.className = "px-5 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-md shadow-emerald-600/20";
    } else {
      header.className = "px-6 py-4 bg-blue-600 text-white flex items-center justify-between";
      submitBtn.className = "px-5 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-md shadow-blue-600/20";
    }

    modal.classList.remove("hidden");
  }

  closeCrudModal() {
    document.getElementById("crudModal")?.classList.add("hidden");
  }

  handleCrudSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const dataObj = {};

    formData.forEach((val, key) => {
      dataObj[key] = val.trim();
    });

    try {
      switch (this.currentModalEntity) {
        case "produk":
          dataObj.harga_jual = Number(dataObj.harga_jual) || 0;
          dataObj.stok = Number(dataObj.stok) || 0;
          window.store.saveProduk(dataObj);
          this.toast(`Produk "${dataObj.nama_produk}" berhasil disimpan!`, "success");
          this.renderProdukTable();
          this.renderProdukCategoryFilter();
          this.populatePosDropdowns();
          break;

        case "kasir":
          window.store.saveKasir(dataObj);
          this.toast(`Data Kasir "${dataObj.nama_karyawan}" berhasil disimpan!`, "success");
          this.renderKasirTable();
          this.populatePosDropdowns();
          break;

        case "pelanggan":
          dataObj.poin_member = Number(dataObj.poin_member) || 0;
          window.store.savePelanggan(dataObj);
          this.toast(`Pelanggan "${dataObj.nama_pelanggan}" berhasil disimpan!`, "success");
          this.renderPelangganTable();
          this.populatePosDropdowns();
          break;

        case "kategori_pajak":
          window.store.saveKategoriPajak(dataObj);
          this.toast(`Kategori Pajak "${dataObj.nama_kategori}" berhasil disimpan!`, "success");
          this.renderTaxKategoriTable();
          this.renderTaxJenisCategoryFilter();
          break;

        case "jenis_pajak":
          window.store.saveJenisPajak(dataObj);
          this.toast(`Jenis Pajak "${dataObj.nama_pajak}" berhasil disimpan!`, "success");
          this.renderTaxJenisTable();
          this.populateTaxPaymentDropdowns();
          break;

        case "pengguna":
          window.store.savePengguna(dataObj);
          this.toast(`Pengguna "${dataObj.nama_pengguna}" berhasil disimpan!`, "success");
          this.renderTaxPenggunaTable();
          this.populateTaxPaymentDropdowns();
          break;
      }

      this.closeCrudModal();
      this.renderStats();

    } catch (err) {
      this.toast(err.message, "error");
    }
  }

  // ========================================================
  // EXPORT SQL MODAL
  // ========================================================
  showExportModal() {
    this.activeExportSystem = this.currentSystem;
    this.updateExportSqlView();
    document.getElementById("exportModal")?.classList.remove("hidden");
  }

  switchExportTab(system) {
    this.activeExportSystem = system;
    const tabSm = document.getElementById("exportTabSm");
    const tabTax = document.getElementById("exportTabTax");

    if (system === "supermarket") {
      tabSm.className = "px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800";
      tabTax.className = "px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-blue-100";
    } else {
      tabTax.className = "px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-100 text-blue-800";
      tabSm.className = "px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-emerald-100";
    }

    this.updateExportSqlView();
  }

  updateExportSqlView() {
    const textarea = document.getElementById("exportSqlTextarea");
    if (!textarea) return;
    const sql = window.store.generateSQL(this.activeExportSystem);
    textarea.value = sql;
  }

  copyExportSQL() {
    const textarea = document.getElementById("exportSqlTextarea");
    if (!textarea) return;
    textarea.select();
    navigator.clipboard.writeText(textarea.value);
    this.toast("Skrip SQL berhasil disalin ke clipboard!", "success");
  }

  downloadSQLFile() {
    const textarea = document.getElementById("exportSqlTextarea");
    if (!textarea) return;
    const content = textarea.value;
    const filename = `${this.activeExportSystem}_dump_${new Date().toISOString().slice(0, 10)}.sql`;

    const blob = new Blob([content], { type: "text/plain;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    this.toast(`File ${filename} berhasil diunduh!`, "success");
  }

  closeExportModal() {
    document.getElementById("exportModal")?.classList.add("hidden");
  }

  resetDataConfirm() {
    if (confirm("Apakah Anda yakin ingin me-reset seluruh data kedua sistem ke data awal? Perubahan lokal akan hilang.")) {
      window.store.resetAllData();
      this.toast("Seluruh basis data telah di-reset ke nilai awal!", "info");
      this.renderAllSupermarketViews();
      this.renderAllPerpajakanViews();
      this.populatePosDropdowns();
      this.populateTaxPaymentDropdowns();
      this.renderStats();
    }
  }

  // ========================================================
  // UTILITIES & HELPERS
  // ========================================================
  formatRupiah(num) {
    const val = Number(num) || 0;
    return "Rp " + val.toLocaleString("id-ID");
  }

  terbilang(angka) {
    const bilangan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    let n = Math.floor(Math.abs(angka));

    if (n < 12) return bilangan[n];
    if (n < 20) return this.terbilang(n - 10) + " Belas";
    if (n < 100) return this.terbilang(Math.floor(n / 10)) + " Puluh " + this.terbilang(n % 10);
    if (n < 200) return "Seratus " + this.terbilang(n - 100);
    if (n < 1000) return this.terbilang(Math.floor(n / 100)) + " Ratus " + this.terbilang(n % 100);
    if (n < 2000) return "Seribu " + this.terbilang(n - 1000);
    if (n < 1000000) return this.terbilang(Math.floor(n / 1000)) + " Ribu " + this.terbilang(n % 1000);
    if (n < 1000000000) return this.terbilang(Math.floor(n / 1000000)) + " Juta " + this.terbilang(n % 1000000);
    if (n < 1000000000000) return this.terbilang(Math.floor(n / 1000000000)) + " Miliar " + this.terbilang(n % 1000000000);
    return String(n);
  }

  toast(message, type = "info") {
    const container = document.getElementById("toastContainer");
    if (!container) return;

    const toastEl = document.createElement("div");
    let bgClass = "bg-slate-900 text-white border-slate-700";
    let icon = "fa-solid fa-circle-info text-blue-400";

    if (type === "success") {
      bgClass = "bg-emerald-950 text-emerald-100 border-emerald-700";
      icon = "fa-solid fa-circle-check text-emerald-400";
    } else if (type === "error") {
      bgClass = "bg-rose-950 text-rose-100 border-rose-700";
      icon = "fa-solid fa-triangle-exclamation text-rose-400";
    } else if (type === "warning") {
      bgClass = "bg-amber-950 text-amber-100 border-amber-700";
      icon = "fa-solid fa-triangle-exclamation text-amber-400";
    }

    toastEl.className = `flex items-center space-x-3 px-4 py-3 rounded-2xl shadow-xl border text-xs font-semibold pointer-events-auto transition-all duration-300 transform translate-y-2 opacity-0 ${bgClass}`;
    toastEl.innerHTML = `
      <i class="${icon} text-base flex-shrink-0"></i>
      <span class="flex-1">${message}</span>
    `;

    container.appendChild(toastEl);

    // Animate in
    setTimeout(() => {
      toastEl.classList.remove("translate-y-2", "opacity-0");
    }, 10);

    // Animate out & remove
    setTimeout(() => {
      toastEl.classList.add("translate-y-2", "opacity-0");
      setTimeout(() => {
        toastEl.remove();
      }, 300);
    }, 3500);
  }
}

// Global App Instance
window.app = new AppController();
