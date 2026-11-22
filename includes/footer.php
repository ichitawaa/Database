<?php
/**
 * Layout Footer & Modals Cetak Dokumen
 */
?>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-900 border-t border-slate-800 py-6 text-slate-400 text-xs text-center">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <i class="fa-brands fa-php text-indigo-400 text-base"></i>
                <span>Sistem Database Relasional Multi-Entitas (PHP 8.2 & MySQL Native PDO)</span>
            </div>
            <p>Terhubung langsung ke database: <code class="text-emerald-400">supermarket</code> & <code class="text-blue-400">perpajakan</code></p>
        </div>
    </footer>

    <!-- ======================================================== -->
    <!-- MODAL: STRUK KASIR THERMAL RECEIPT                       -->
    <!-- ======================================================== -->
    <div id="receiptModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden animate-fade-in">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 animate-slide-down">
            <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-receipt text-emerald-400"></i>
                    <h3 class="text-sm font-bold">Struk Belanja Kasir</h3>
                </div>
                <button onclick="closeReceiptModal()" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 max-h-[80vh] overflow-y-auto">
                <div id="printableArea">
                    <div id="receiptContent" class="thermal-receipt text-xs">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between no-print">
                <button onclick="closeReceiptModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                    Tutup
                </button>
                <button onclick="window.print()" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center space-x-1.5 shadow-md shadow-emerald-600/20">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak Struk</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: BUKTI PENERIMAAN ELEKTRONIK (BPE) DJP             -->
    <!-- ======================================================== -->
    <div id="taxReceiptModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden animate-fade-in">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200 animate-slide-down">
            <div class="px-5 py-3.5 bg-blue-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-certificate text-amber-400"></i>
                    <h3 class="text-sm font-bold">Bukti Penerimaan Elektronik (BPE) DJP</h3>
                </div>
                <button onclick="closeTaxReceiptModal()" class="text-blue-200 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 max-h-[80vh] overflow-y-auto">
                <div id="printableTaxArea">
                    <div id="taxReceiptContent" class="tax-bpe-receipt">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between no-print">
                <button onclick="closeTaxReceiptModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                    Tutup
                </button>
                <button onclick="window.print()" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center space-x-1.5 shadow-md shadow-blue-600/20">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak Bukti Pajak</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Script Global Pembantu -->
    <script>
    function viewReceipt(id_trx, cash = null) {
        fetch('api/handler.php?action=get_transaksi_detail&id=' + encodeURIComponent(id_trx))
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    alert(res.message);
                    return;
                }

                const trx = res.transaksi;
                const items = res.items;
                const total = parseInt(trx.total_bayar) || 0;
                const bayar = cash !== null && cash > 0 ? cash : total;
                const kembalian = Math.max(0, bayar - total);

                const html = `
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
                            <span>Waktu:</span>
                            <span>${trx.tanggal_transaksi}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Kasir:</span>
                            <span>${trx.nama_karyawan || trx.id_kasir} (${trx.shift || '-'})</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Pelanggan:</span>
                            <span>${trx.nama_pelanggan || 'Pelanggan Umum'}</span>
                        </div>
                    </div>

                    <!-- Item List -->
                    <div class="py-2.5 border-b border-dashed border-slate-300 space-y-1.5 text-[11px]">
                        ${items.map(i => `
                            <div>
                                <div class="font-bold text-slate-800">${i.nama_produk}</div>
                                <div class="flex justify-between text-slate-600">
                                    <span>${i.jumlah} x Rp ${parseInt(i.harga_jual).toLocaleString('id-ID')}</span>
                                    <span class="font-bold">Rp ${parseInt(i.subtotal).toLocaleString('id-ID')}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>

                    <!-- Ringkasan Bayar -->
                    <div class="py-2.5 space-y-1 text-[11px]">
                        <div class="flex justify-between font-bold text-slate-900 text-xs">
                            <span>TOTAL:</span>
                            <span>Rp ${total.toLocaleString('id-ID')}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Metode:</span>
                            <span>${trx.metode_pembayaran}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Bayar:</span>
                            <span>Rp ${bayar.toLocaleString('id-ID')}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Kembalian:</span>
                            <span class="font-bold text-emerald-700">Rp ${kembalian.toLocaleString('id-ID')}</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-dashed border-slate-300 text-center text-[10px] text-slate-400 space-y-1">
                        <p class="font-semibold text-slate-600">Terima Kasih Telah Berbelanja!</p>
                        <p>Barang yang sudah dibeli tidak dapat ditukar kembali.</p>
                        <p class="font-mono pt-1 tracking-widest text-[9px] text-slate-300">*** ${trx.id_transaksi} ***</p>
                    </div>
                `;

                document.getElementById('receiptContent').innerHTML = html;
                document.getElementById('receiptModal').classList.remove('hidden');
            });
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').classList.add('hidden');
    }

    function viewTaxReceipt(id_pay) {
        fetch('api/handler.php?action=get_tax_detail&id=' + encodeURIComponent(id_pay))
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    alert(res.message);
                    return;
                }

                const pay = res.data;
                const ntpn = 'NTPN-' + pay.id_pembayaran.replace(/[^0-9]/g, '') + Math.floor(1000 + Math.random() * 9000);
                const ntb = 'NTB-987' + Math.floor(100000 + Math.random() * 900000);

                const html = `
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

                    <div class="py-3 text-center">
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest underline decoration-2 decoration-blue-900">
                            BUKTI PENERIMAAN ELEKTRONIK (BPE)
                        </h4>
                        <p class="text-[11px] font-mono text-slate-600 mt-0.5">Surat Setoran Pajak / NTPN: <span class="font-bold text-blue-900">${ntpn}</span></p>
                    </div>

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
                            <span class="col-span-2 font-mono font-bold text-blue-900">${pay.npwp_pengguna || '-'}</span>
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
                            <span class="col-span-2 font-mono text-base font-black text-emerald-800">${pay.jumlah_formatted}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 py-1 text-[11px] bg-slate-100 p-2 rounded-lg">
                            <span class="text-slate-500 font-medium italic">Terbilang:</span>
                            <span class="col-span-2 font-bold italic text-slate-800 capitalize">${pay.terbilang}</span>
                        </div>
                    </div>

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
                            <p class="text-[10px] text-slate-500">Dicetak melalui Sistem PHP & MySQL Terpadu</p>
                            <p class="text-[10px] text-slate-400 font-mono">ID Validasi: ${pay.id_pembayaran}</p>
                        </div>
                    </div>
                `;

                document.getElementById('taxReceiptContent').innerHTML = html;
                document.getElementById('taxReceiptModal').classList.remove('hidden');
            });
    }

    function closeTaxReceiptModal() {
        document.getElementById('taxReceiptModal').classList.add('hidden');
    }

    // Auto-open modal jika ada parameter print_trx atau print_tax di URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const printTrx = urlParams.get('print_trx');
        const cash = urlParams.get('cash') ? parseInt(urlParams.get('cash')) : null;
        if (printTrx) {
            viewReceipt(printTrx, cash);
        }

        const printTax = urlParams.get('print_tax');
        if (printTax) {
            viewTaxReceipt(printTax);
        }
    });
    </script>
</body>
</html>
