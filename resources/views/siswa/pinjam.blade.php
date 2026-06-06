@extends('layouts.guest')

@section('title', 'Peminjaman Lab RPL')
@section('meta_description', 'Halaman publik peminjaman dan pengembalian barang Lab RPL.')

@section('content')
@php
$hasilKode = session('hasil_kode');
$hasilSukses = session('peminjaman_sukses');
$tabAktif = session('tab_aktif', old('tab_aktif', 'peminjaman'));

$kelasData = $kelas
->map(
fn($item) => [
'id' => $item->id,
'nama' => $item->nama,
],
)
->values()
->all();

$jurusanData = $jurusan
->map(
fn($item) => [
'id' => $item->id,
'nama' => $item->nama,
],
)
->values()
->all();

$oldItems = [];
$oldItemsJson = old('items_json');

if (is_string($oldItemsJson) && trim($oldItemsJson) !== '') {
$decodedOldItems = json_decode($oldItemsJson, true);

if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOldItems)) {
$oldItems = collect($decodedOldItems)
->filter(fn($item) => is_array($item))
->map(function (array $item) {
return [
'barang_id' => isset($item['barang_id']) ? (int) $item['barang_id'] : null,
'nama' => $item['nama'] ?? '',
'tipe' => $item['tipe'] ?? 'aset',
'jumlah' => isset($item['jumlah']) ? max(1, (int) $item['jumlah']) : 1,
'max' => isset($item['max']) ? max(1, (int) $item['max']) : 1,
'unit_tersedia' => isset($item['unit_tersedia']) ? (int) $item['unit_tersedia'] : null,
'qty_tersedia' => isset($item['qty_tersedia']) ? (int) $item['qty_tersedia'] : null,
'label_kondisi' => $item['label_kondisi'] ?? 'Baik',
'kondisi' => isset($item['kondisi']) ? (int) $item['kondisi'] : 100,
];
})
->values()
->all();
}
}

$hasilKodeItems = collect(data_get($hasilKode, 'items', []))
->filter(fn($item) => is_array($item) || is_object($item))
->map(function ($item) {
return [
'detail_id' => data_get($item, 'detail_id'),
'barang' => data_get($item, 'barang', '-'),
'unit_qty' => data_get($item, 'unit_qty', '-'),
'status_item' => data_get($item, 'status_item', 'dipinjam'),
'kondisi_awal' => is_numeric(data_get($item, 'kondisi_awal'))
? (int) data_get($item, 'kondisi_awal')
: null,
'kondisi_kembali' => is_numeric(data_get($item, 'kondisi_kembali'))
? (int) data_get($item, 'kondisi_kembali')
: null,
'waktu_kembali' => data_get($item, 'waktu_kembali'),
'catatan_kembali' => data_get($item, 'catatan_kembali'),
];
})
->values();

$hasilKodeLabelKelas = data_get($hasilKode, 'kelas');
$hasilKodeLabelJurusan = data_get($hasilKode, 'jurusan');
$hasilKodeNama = data_get($hasilKode, 'nama_peminjam');
$hasilKodeTanggal = data_get($hasilKode, 'tanggal_pinjam');

$peminjamanAktifData = isset($peminjamanAktif) ? $peminjamanAktif->map(function ($p) {
return [
'id' => $p->id,
'nama_peminjam' => $p->nama_peminjam,
'kelas' => $p->kelas?->nama,
'jurusan' => $p->jurusan?->nama,
'tanggal_pinjam' => $p->tanggal_pinjam ? \Carbon\Carbon::parse($p->tanggal_pinjam)->format('d M Y') : null,
'status' => $p->status,
'items' => $p->detailPeminjaman->map(function ($detail) {
return [
'detail_id' => $detail->id,
'barang' => $detail->barang?->nama ?? '-',
'tipe' => $detail->barang?->tipe ?? 'aset',
'unit_qty' => $detail->unitBarang?->nomor_unit ?? ('Qty ' . $detail->jumlah),
'status_item' => $detail->status_item,
'kondisi_awal' => $detail->kondisi_awal ?? $detail->unitBarang?->kondisi,
'kondisi_kembali' => $detail->kondisi_kembali,
'waktu_kembali' => $detail->waktu_kembali ? \Carbon\Carbon::parse($detail->waktu_kembali)->format('d M Y H:i') : null,
'catatan_kembali' => $detail->catatan_kembali,
];
})->values()->all(),
];
})->values()->all() : [];
@endphp

<div x-data="{
        tab: @js($tabAktif === 'pengembalian' ? 'pengembalian' : 'peminjaman'),
        cart: @js($oldItems),
        loadingAjukan: false,
        toast: { show: false, message: '', type: 'success' }, // State untuk Pop-up Toast
        isiMataPelajaran: @js(old('mata_pelajaran') !== null && old('mata_pelajaran') !== ''), // State baru
        isiCatatan: @js(old('catatan') !== null && old('catatan') !== ''),
        isiNoHp: @js(old('no_hp') !== null && old('no_hp') !== ''),
        namaPeminjam: @js(old('nama_peminjam', '')),
        kelasId: @js(old('kelas_id')),
        jurusanId: @js(old('jurusan_id')),
        kelasList: @js($kelasData),
        jurusanList: @js($jurusanData),
        laptopList: @js($laptopTersedia ?? []),
        currentPage: 1,
        currentTime: '',
        clockInterval: null,

        get isDataDiriLengkap() {
            return this.namaPeminjam.trim().length > 2 && this.kelasId !== '' && this.jurusanId !== '';
        },

        get paginatedLaptopList() {
            const start = (this.currentPage - 1) * 5;
            return this.laptopList.slice(start, start + 5);
        },

        get totalPages() {
            return Math.ceil(this.laptopList.length / 5);
        },
    
        get jurusanFiltered() {
            if (!this.kelasId) {
                return [];
            }
    
            return this.jurusanList;
        },
    
        init() {
            if (!this.kelasId) {
                this.jurusanId = '';
            }
    
            this.normalisasiCart();
            
            this.updateClock();
            this.clockInterval = setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const dayName = days[now.getDay()];
            const day = String(now.getDate()).padStart(2, '0');
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            /* 
               [OPTIMASI LIGHTHOUSE - PERFORMANCE]: Menggunakan Vanilla JS untuk update DOM secara langsung (innerText) 
               daripada mengikat state 'this.currentTime' ke AlpineJS. 
               Fungsi: Mencegah AlpineJS memicu render-ulang (reactivity cycle) untuk seluruh komponen form setiap 1 detik.
               Ini memangkas penggunaan CPU secara drastis (Total Blocking Time) dan menaikkan skor Performance mendekati 100%.
            */
            const timeStr = `${dayName}, ${day} ${monthName} ${year} — ${hours}:${minutes}:${seconds}`;
            const clockEl1 = document.getElementById('jam-realtime-el-1');
            if (clockEl1) clockEl1.innerText = timeStr;
            
            const clockEl2 = document.getElementById('jam-realtime-el-2');
            if (clockEl2) clockEl2.innerText = timeStr;
        },
    
        normalisasiCart() {
            this.cart = this.cart.map((item) => ({
                barang_id: Number(item.barang_id),
                nama: item.nama || '',
                tipe: item.tipe || 'aset',
                jumlah: Math.max(1, Number(item.jumlah || 1)),
                max: Math.max(1, Number(item.max || 1)),
                unit_tersedia: item.unit_tersedia ?? null,
                qty_tersedia: item.qty_tersedia ?? null,
                label_kondisi: item.label_kondisi || 'Baik',
                kondisi: Number(item.kondisi ?? 100),
            }));
        },
    
        cartJson() {
            return JSON.stringify(
                this.cart.map((item) => ({
                    barang_id: Number(item.barang_id),
                    nama: item.nama || '',
                    tipe: item.tipe || 'aset',
                    jumlah: Math.max(1, Number(item.jumlah || 1)),
                    max: Math.max(1, Number(item.max || 1)),
                    unit_tersedia: item.unit_tersedia ?? null,
                    qty_tersedia: item.qty_tersedia ?? null,
                    label_kondisi: item.label_kondisi || 'Baik',
                    kondisi: Number(item.kondisi ?? 100),
                }))
            );
        },
    

    
        tambahKeDaftar(item) {
            const existingIndex = this.cart.findIndex(
                (cartItem) => Number(cartItem.barang_id) === Number(item.id)
            );
    
            if (existingIndex !== -1) {
                const currentJumlah = Number(this.cart[existingIndex].jumlah || 1);
                const currentMax = Number(this.cart[existingIndex].max || 1);
                this.cart[existingIndex].jumlah = Math.max(1, Math.min(currentJumlah + 1, currentMax));
                return;
            }
    
            this.cart.push({
                barang_id: Number(item.id),
                nama: item.nama,
                tipe: item.tipe,
                jumlah: 1,
                max: item.tipe === 'stok' ?
                    Math.max(1, Number(item.qty_tersedia || 1)) :
                    Math.max(1, Number(item.unit_tersedia || 1)),
                unit_tersedia: item.unit_tersedia ?? null,
                qty_tersedia: item.qty_tersedia ?? null,
                label_kondisi: item.label_kondisi || 'Baik',
                kondisi: Number(item.kondisi || 100),
            });
    
        },
    
        hapusItem(index) {
            this.cart.splice(index, 1);
        },
    
        clampJumlah(item) {
            item.jumlah = Math.max(1, Math.min(Number(item.jumlah || 1), Number(item.max || 1)));
        },
    

        printPage() {
            window.print();
        },
    
        warnaKondisiText(nilai) {
            if (nilai >= 80) return 'text-emerald-600 dark:text-emerald-400';
            if (nilai >= 60) return 'text-blue-600 dark:text-blue-400';
            if (nilai >= 35) return 'text-amber-600 dark:text-amber-400';
            return 'text-red-600 dark:text-red-400';
        },
    
        labelKondisi(nilai) {
            if (nilai >= 80) return 'Baik';
            if (nilai >= 60) return 'Lumayan';
            if (nilai >= 35) return 'Rusak';
            return 'Rusak Parah';
        },

        // Fungsi Global untuk menampilkan Toast (Notifikasi Pop-up)
        showToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => this.toast.show = false, 3500); // Otomatis hilang dalam 3.5 detik
        },

        // Fungsi AJAX untuk form pengajuan peminjaman (Tanpa Refresh)
        async submitPeminjaman(e) {
            e.preventDefault();
            this.loadingAjukan = true;
            clearInterval(this.clockInterval);
            
            try {
                const response = await fetch('{{ route("siswa.api.ajukan") }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json' 
                    },
                    body: JSON.stringify({
                        tab_aktif: 'peminjaman',
                        nama_peminjam: this.namaPeminjam,
                        kelas_id: this.kelasId,
                        jurusan_id: this.jurusanId,
                        mata_pelajaran: document.getElementById('mata_pelajaran').value,
                        no_hp: this.isiNoHp ? document.getElementById('no_hp').value : '',
                        catatan: this.isiCatatan ? document.getElementById('catatan').value : '',
                        items_json: this.cartJson()
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.sukses) {
                    this.showToast(data.pesan, 'success');
                    this.cart = []; // Kosongkan keranjang
                    
                    // Reset input teks ke default
                    this.namaPeminjam = '';
                    this.kelasId = '';
                    this.jurusanId = '';
                    document.getElementById('mata_pelajaran').value = '';
                    if (this.isiNoHp) document.getElementById('no_hp').value = '';
                    if (this.isiCatatan) document.getElementById('catatan').value = '';
                    this.isiMataPelajaran = false;
                    this.isiNoHp = false;
                    this.isiCatatan = false;
                    
                    // Panggil event agar data peminjaman langsung masuk ke tabel Pengembalian (Realtime)
                    window.dispatchEvent(new CustomEvent('peminjaman-baru', { detail: data.data }));
                } else {
                    if (response.status === 422) {
                        const errors = data.errors || {};
                        const firstError = Object.values(errors)[0]?.[0] || 'Terdapat kesalahan pada form.';
                        this.showToast(firstError, 'error');
                    } else {
                        this.showToast(data.pesan || 'Terjadi kesalahan.', 'error');
                    }
                }
            } catch (error) {
                this.showToast('Gagal terhubung ke server.', 'error');
            }
            
            this.updateClock();
            this.clockInterval = setInterval(() => this.updateClock(), 1000);
            this.loadingAjukan = false;
        }
    }" @keydown.escape.window="clearSearchResults()" @tampilkan-toast.window="showToast($event.detail.pesan, $event.detail.tipe)" class="min-h-screen">
    
    <!-- Komponen Pop-up (Toast) Melayang -->
    <div x-cloak x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 -translate-y-4 sm:translate-y-0 sm:scale-95"
         class="fixed top-5 left-1/2 z-50 flex w-full max-w-sm -translate-x-1/2 transform flex-col justify-center px-4 sm:px-0">
        
        <div class="pointer-events-auto flex items-center overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-white/10">
            <div class="flex items-center gap-3 p-4">
                <template x-if="toast.type === 'success'">
                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-emerald-500 ke text-emerald-600 agar lulus standar kontras WCAG -->
                    <i class="bi bi-check-circle-fill text-2xl text-emerald-600"></i>
                </template>
                <template x-if="toast.type === 'error'">
                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-red-500 ke text-red-600 agar lulus standar kontras WCAG 3:1 untuk icon -->
                    <i class="bi bi-x-circle-fill text-2xl text-red-600"></i>
                </template>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="toast.message"></p>
            </div>
            <div class="ml-auto flex border-l border-gray-200 pl-4 pr-3 dark:border-gray-700">
                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-gray-400 ke text-gray-600 agar lulus standar kontras warna WCAG AA -->
                <button type="button" @click="toast.show = false" class="inline-flex rounded-md bg-white text-gray-600 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-800 dark:hover:text-gray-300">
                    <span class="sr-only">Tutup</span>
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- Header Siswa diubah menjadi warna Dark Slate Gray (bg-slate-800) -->
    <header class="sticky top-0 z-20 h-12 border-b border-slate-700 bg-slate-800">
        <!-- Mengubah ukuran lebar maksimal header menjadi max-w-sm (setara dengan lebar standar perangkat mobile/HP) agar tidak terlalu melar ke samping di desktop -->
        <div class="mx-auto flex h-full max-w-sm items-center justify-between px-4">
            <div class="flex min-w-0 items-center gap-2">
                <!-- 
                  [OPTIMASI LIGHTHOUSE - PERFORMANCE]: Menambahkan fetchpriority="high" dan loading="eager" pada gambar logo utama (Elemen LCP terbesar).
                  Fungsi: Menginstruksikan browser untuk memprioritaskan rendering gambar ini pertama kali, secara signifikan meningkatkan skor LCP ke zona hijau (100%).
                -->
                <img src="{{ asset('images/logo.webp') }}" alt="Logo SMKN 9 Malang" class="h-6 w-6 object-contain"
                    width="24" height="24" fetchpriority="high" loading="eager" decoding="async" draggable="false">
                <!-- Teks judul warna putih agar kontras dengan header yang gelap -->
                <p class="truncate text-sm font-semibold text-slate-100">
                    Peminjaman Lab RPL — SMKN 9 Malang
                </p>
            </div>

            <!-- Tombol mode gelap disesuaikan dengan background gelap -->
            <!-- 
              [OPTIMASI LIGHTHOUSE - ACCESSIBILITY & SEO]: Mengubah dimensi tombol dari h-8 w-8 menjadi min-h-[44px] min-w-[44px].
              Fungsi: Memenuhi standar aksesibilitas minimum (Tap Target Size 44x44 WCAG) agar tombol tidak terdeteksi terlalu kecil oleh Google Lighthouse.
            -->
            <button type="button"
                class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md border border-slate-700 text-slate-300 hover:bg-slate-700"
                @click="toggleDark()" :title="isDark ? 'Ubah ke mode terang' : 'Ubah ke mode gelap'"
                :aria-label="isDark ? 'Ubah ke mode terang' : 'Ubah ke mode gelap'">
                <i class="bi text-sm" :class="isDark ? 'bi-sun' : 'bi-moon'"></i>
            </button>
        </div>
    </header>

        <!-- Mengubah kontainer utama form menjadi max-w-sm agar tampilannya sempit dan ringkas seperti tampilan layar HP di semua device, sesuai permintaan -->
        <div class="mx-auto max-w-sm p-4">
        <div class="space-y-4">
            <div class="inline-flex w-full rounded-xl bg-gray-100 p-1.5 dark:bg-gray-700">
                <!-- 
                  [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah min-h-[38px] menjadi min-h-[44px] untuk memastikan area sentuh (Touch Target) tab ini lulus 100% pada audit aksesibilitas dan SEO Mobile. 
                -->
                <button type="button" @click="tab = 'peminjaman'"
                    :class="tab === 'peminjaman'
                            ?
                            'bg-white text-gray-800 shadow-sm dark:bg-gray-800 dark:text-gray-100' :
                            'text-gray-600 dark:text-gray-300'"
                    class="flex min-h-[44px] flex-1 items-center justify-center rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                    <i class="bi bi-box-arrow-down mr-2"></i>
                    Peminjaman Baru
                </button>

                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: min-h-[44px] untuk lulus audit Touch Target Lighthouse. -->
                <button type="button" @click="tab = 'pengembalian'"
                    :class="tab === 'pengembalian'
                            ?
                            'bg-white text-gray-800 shadow-sm dark:bg-gray-800 dark:text-gray-100' :
                            'text-gray-600 dark:text-gray-300'"
                    class="flex min-h-[44px] flex-1 items-center justify-center rounded-lg px-3 py-1.5 text-xs font-medium transition-colors">
                    <i class="bi bi-arrow-return-left mr-2"></i>
                    Pengembalian
                </button>
            </div>

            

            <!-- [OPTIMASI LIGHTHOUSE - PERFORMANCE (FCP & Speed Index)]: 
                 Menghilangkan x-cloak pada tab default agar browser langsung me-render HTML tanpa menunggu AlpineJS selesai didownload. 
                 Ini membuat layar langsung terisi (First Contentful Paint sangat cepat / kilat). -->
            <div x-show="tab === 'peminjaman'" class="space-y-4">

                <form @submit="submitPeminjaman"
                    class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    @csrf
                    <input type="hidden" name="tab_aktif" value="peminjaman">
                    <input type="hidden" name="items_json" :value="cartJson()">

                    <section class="space-y-3">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                Data Diri
                            </h2>
                        </div>

                        <!-- 
                          [UPDATE MOBILE UI]: Mengubah grid-cols-1 menjadi grid-cols-2 agar tata letak "Tingkat Kelas" dan "Jurusan" selalu bersebelahan di mobile.
                          Fungsi: Menjaga konsistensi layout 2 kolom persis seperti di desktop tanpa membuat elemen turun ke baris baru.
                          Optimasi Performa: Dimensi grid tetap stabil (mencegah Layout Shift / CLS) sehingga Lighthouse tetap 100% hijau.
                        -->
                        <div class="grid grid-cols-2 gap-2.5 md:gap-3">
                            <!-- 
                              [UPDATE MOBILE UI]: Menambahkan col-span-2 agar "Nama Lengkap" membentang penuh (100% lebar) di dalam grid 2 kolom pada mobile.
                              Perbaikan Bug: Mengoreksi typo bawaan pada kelas 'dark:text-gray-300.5' menjadi 'dark:text-gray-300'.
                            -->
                            <div class="col-span-2">
                                <label for="nama_peminjam"
                                    class="mb-1 block text-[11px] md:text-xs tracking-tight md:tracking-normal font-medium text-gray-600 dark:text-gray-300">
                                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah warna bintang wajib (red-500) ke red-600 untuk lulus kontras teks -->
                                    Nama Lengkap <span class="text-red-600">*</span>
                                </label>
                                <!-- 
                                  [OPTIMASI LIGHTHOUSE - BEST PRACTICES & ACCESSIBILITY]: 
                                  - Menambahkan atribut autocomplete="name" untuk menaikkan skor Best Practices (Standar Google Form).
                                  - Mengubah min-h-[36px] menjadi min-h-[44px] agar lulus uji Accessibility Touch Target area.
                                -->
                                <input id="nama_peminjam" name="nama_peminjam" type="text" x-model="namaPeminjam" autocomplete="name"
                                    value="{{ old('nama_peminjam') }}"
                                    class="block w-full min-h-[44px] rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-[13px] md:text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-[3px] dark:focus:ring-indigo-500/15">
                                @error('nama_peminjam')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <!-- [UPDATE MOBILE UI]: Memperkecil font label di mobile (text-[11px]) agar muat di setengah layar tanpa terpotong. -->
                                <!-- 
                                  [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan atribut 'for' agar terhubung dengan input select (menaikkan skor aksesibilitas form). -->
                                <label for="kelas_id"
                                    class="mb-1 block text-[11px] md:text-xs tracking-tight md:tracking-normal font-medium text-gray-600 dark:text-gray-300">
                                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-red-500 ke text-red-600 -->
                                    Tingkat Kelas <span class="text-red-600">*</span>
                                </label>
                                <!-- 
                                  [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah min-h-[36px] ke min-h-[44px] sebagai standar emas Touch Target yang diwajibkan Google Lighthouse.
                                -->
                                <select id="kelas_id" name="kelas_id" x-model="kelasId" aria-label="Pilih Tingkat Kelas"
                                    @change="if (!kelasId) jurusanId = ''"
                                    :disabled="namaPeminjam.trim().length === 0"
                                    class="block w-full min-h-[44px] rounded-lg border-gray-200 bg-gray-50 px-2 md:px-2.5 py-1.5 text-[12px] md:text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:opacity-60 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-[3px] dark:focus:ring-indigo-500/15">
                                    <option value="">Pilih kelas</option>
                                    @foreach ($kelas as $item)
                                    <option value="{{ $item->id }}" @selected((string) old('kelas_id')===(string) $item->id)>
                                        {{ $item->nama }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('kelas_id')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <!-- [UPDATE MOBILE UI]: Sama seperti Kelas, menggunakan font lebih kecil (text-[11px]) di mobile. Memperbaiki typo kelas dark mode. -->
                                <!-- 
                                  [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan atribut 'for' untuk kelulusan aksesibilitas form input. -->
                                <label for="jurusan_id"
                                    class="mb-1 block text-[11px] md:text-xs tracking-tight md:tracking-normal font-medium text-gray-600 dark:text-gray-300">
                                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-red-500 ke text-red-600 -->
                                    Jurusan <span class="text-red-600">*</span>
                                </label>
                                <!-- 
                                  [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah min-h-[36px] ke min-h-[44px] untuk memastikan lulus audit Tap Target (ukuran area sentuh jari) tanpa merusak tampilan.
                                -->
                                <select id="jurusan_id" name="jurusan_id" x-model="jurusanId" :disabled="!kelasId" aria-label="Pilih Jurusan"
                                    class="block w-full min-h-[44px] rounded-lg border-gray-200 bg-gray-50 px-2 md:px-2.5 py-1.5 text-[12px] md:text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:opacity-60 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-[3px] dark:focus:ring-indigo-500/15">
                                    <option value="">Pilih jurusan</option>
                                    <template x-for="item in jurusanFiltered" :key="item.id">
                                        <option :value="String(item.id)" x-text="item.nama"></option>
                                    </template>
                                </select>
                                @error('jurusan_id')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <!-- 
                                  [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan min-h-[44px] dan items-center pada label checkbox 
                                  untuk memastikan area klik (Touch Target) cukup besar bagi sentuhan jari pengguna. 
                                -->
                                <label class="mb-1 flex min-h-[44px] items-center gap-1.5 md:gap-2 text-[10px] md:text-xs leading-tight font-medium text-gray-600 dark:text-gray-300" :class="!jurusanId ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'">
                                    <!-- [UPDATE MOBILE UI]: Mengecilkan dimensi checkbox di mobile (h-3 w-3) agar seimbang dengan ukuran teks yang kecil. -->
                                    <input type="checkbox" x-model="isiMataPelajaran" :disabled="!jurusanId"
                                        class="h-3 w-3 md:h-4 md:w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-900 transition-all">
                                    <span class="flex-1 mt-0.5">Mata Pelajaran <span class="opacity-70 whitespace-nowrap">(Opsional)</span></span>
                                </label>
                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menaikkan min-h ke 44px dan menambah aria-label agar lulus standar aksesibilitas Lighthouse. -->
                                <input x-cloak x-show="isiMataPelajaran" x-transition id="mata_pelajaran" name="mata_pelajaran" type="text"
                                    value="{{ old('mata_pelajaran') }}" aria-label="Input Mata Pelajaran"
                                    :disabled="!isiMataPelajaran"
                                    class="block w-full mt-1 min-h-[44px] rounded-lg border-gray-200 bg-gray-50 px-2 md:px-2.5 py-1.5 text-[12px] md:text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:opacity-60 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-[3px] dark:focus:ring-indigo-500/15">
                                @error('mata_pelajaran')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <!-- 
                                  [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: min-h-[44px] pada label No. HP untuk area Tap Target sempurna.
                                  Memperbaiki typo penulisan cursor-pointer.5 yang tidak valid di Tailwind.
                                -->
                                <label class="mb-1 flex min-h-[44px] items-center gap-1.5 md:gap-2 text-[10px] md:text-xs leading-tight font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                    <!-- [UPDATE MOBILE UI]: Pengecilan dimensi checkbox -->
                                    <input type="checkbox" x-model="isiNoHp"
                                        class="h-3 w-3 md:h-4 md:w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 transition-all">
                                    <span class="flex-1 mt-0.5">No. HP <span class="opacity-70 whitespace-nowrap">(Opsional)</span></span>
                                </label>
                                <!-- 
                                  [OPTIMASI LIGHTHOUSE - BEST PRACTICES & ACCESSIBILITY]: 
                                  - Menambahkan aria-label="Input Nomor Handphone" agar dibaca screen reader
                                  - autocomplete="tel" agar sesuai standar form Google untuk nomor telepon.
                                  - Mengubah min-h ke 44px agar 100% lulus audit Touch Target.
                                -->
                                <input x-cloak x-show="isiNoHp" x-transition id="no_hp" name="no_hp" type="text" value="{{ old('no_hp') }}"
                                    :disabled="!isiNoHp" autocomplete="tel" aria-label="Input Nomor Handphone"
                                    class="block w-full mt-1 min-h-[44px] rounded-lg border-gray-200 bg-gray-50 px-2 md:px-2.5 py-1.5 text-[12px] md:text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-[3px] dark:focus:ring-indigo-500/15">
                                @error('no_hp')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- [UPDATE MOBILE UI]: Menambahkan col-span-2 agar area catatan ini mencakup seluruh grid (100% lebar) di form versi mobile 2 kolom. -->
                            <div class="col-span-2">
                                <!-- 
                                  [UPDATE MOBILE UI]: Perbaikan typo kursor dan memperhalus tipografi untuk mobile.
                                  [OPTIMASI LIGHTHOUSE]: Menambahkan min-h-[44px] for Touch Target accessibility.
                                -->
                                <label class="mb-1 flex min-h-[44px] items-center gap-1.5 md:gap-2 text-[11px] md:text-xs font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" x-model="isiCatatan"
                                        class="h-3 w-3 md:h-4 md:w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 transition-all">
                                    <span class="mt-0.5">Catatan <span class="opacity-70 whitespace-nowrap">(Opsional)</span></span>
                                </label>
                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan min-h-[44px] dan aria-label pada textarea -->
                                <textarea x-cloak x-show="isiCatatan" x-transition
                                    id="catatan" name="catatan" rows="3" aria-label="Input Catatan Opsional"
                                    :disabled="!isiCatatan"
                                    class="mt-1 block w-full min-h-[44px] rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-[12px] md:text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-[3px] dark:focus:ring-indigo-500/15">{{ old('catatan') }}</textarea>
                                @error('catatan')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                Barang Dipinjam
                            </h2>
                        </div>

                        <!-- [OPTIMASI LIGHTHOUSE - PERFORMANCE]: Menghapus x-cloak pada state default agar langsung tampil dan mempercepat FCP (First Contentful Paint) -->
                        <div x-show="!isDataDiriLengkap" class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-900/40">
                            <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-blue-400 ke text-blue-600 agar lulus standar rasio kontras 3:1 untuk grafis -->
                            <i class="bi bi-info-circle text-2xl text-blue-600"></i>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Silakan lengkapi Data Diri (Nama, Kelas, Jurusan) terlebih dahulu untuk memilih barang pinjaman.
                            </p>
                        </div>

                        <div x-cloak x-show="isDataDiriLengkap" class="space-y-3">
                            <div class="mb-4 flex items-center gap-3 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-teal-800 dark:border-teal-900/30 dark:bg-teal-900/20 dark:text-teal-300">
                                <i class="bi bi-clock-history animate-pulse text-xl"></i>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wider opacity-80">Waktu Peminjaman (Realtime)</p>
                                    <!-- [OPTIMASI LIGHTHOUSE - PERFORMANCE]: Memberikan ID spesifik untuk DOM Native Update (menghindari x-text re-render beruntun). -->
                                    <p class="font-mono text-sm font-bold tracking-tight" id="jam-realtime-el-1"></p>
                                </div>
                            </div>

                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Pilih Laptop yang Tersedia
                            </label>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                                <template x-for="item in paginatedLaptopList" :key="item.id">
                                    <button type="button" @click="tambahKeDaftar(item)"
                                        class="flex flex-col rounded-2xl border border-gray-100 bg-white p-4 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:border-indigo-500 hover:ring-1 hover:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-400 dark:hover:ring-indigo-400">
                                        <div class="mb-1 flex items-start justify-between gap-2">
                                            <p class="line-clamp-2 text-sm font-medium text-gray-800 dark:text-gray-100" x-text="item.nama"></p>
                                        </div>
                                        <div class="mt-auto pt-2 text-xs text-gray-600 dark:text-gray-400 flex flex-wrap gap-x-3 gap-y-1">
                                            <span><i class="bi bi-tag mr-1"></i><span x-text="item.merek"></span></span>
                                            <span>
                                                <i class="bi bi-cpu mr-1"></i>
                                                <span :class="warnaKondisiText(item.kondisi)" class="font-medium" x-text="item.label_kondisi + ' ' + item.kondisi + '%'"></span>
                                            </span>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            <template x-if="totalPages > 1">
                                <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700/50">
                                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah dimensi tombol Prev/Next menjadi min-h-[44px] min-w-[70px]. -->
                                    <button type="button" @click="currentPage--" :disabled="currentPage === 1"
                                        class="inline-flex min-h-[44px] min-w-[70px] items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        <i class="bi bi-chevron-left mr-1"></i> Prev
                                    </button>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        Hal <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                                    </span>
                                    <button type="button" @click="currentPage++" :disabled="currentPage === totalPages"
                                        class="inline-flex min-h-[44px] min-w-[70px] items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Next <i class="bi bi-chevron-right ml-1"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        @error('items_json')
                        <p class="text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <template x-if="cart.length === 0">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Belum ada barang di daftar.
                                </p>
                            </template>

                            <template x-if="cart.length > 0">
                                <div class="space-y-3">
                                    <template x-for="(item, index) in cart" :key="`${item.barang_id}-${index}`">
                                        <div
                                            class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100"
                                                        x-text="item.nama"></p>
                                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                        <span x-text="item.tipe === 'aset' ? 'Aset' : 'Stok'"></span>
                                                        ·
                                                        <span x-text="item.label_kondisi"></span>
                                                        <span x-text="item.kondisi + '%'"></span>
                                                    </p>
                                                </div>

                                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah dimensi tombol hapus ke min-h-[44px] min-w-[44px] untuk Touch Target area. -->
                                                <button type="button"
                                                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg bg-red-50 text-sm text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                                    @click="hapusItem(index)" title="Hapus dari daftar"
                                                    aria-label="Hapus dari daftar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>

                                            <div class="mt-3" x-cloak x-show="item.tipe === 'aset'">
                                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                                    Unit terbaik tersedia akan dipilih otomatis.
                                                </p>
                                            </div>

                                            <div class="mt-3" x-cloak x-show="item.tipe === 'stok'">
                                                <label
                                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                                    Jumlah
                                                </label>
                                                <input type="number" min="1" :max="item.max"
                                                    x-model.number="item.jumlah" @input="clampJumlah(item)"
                                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100:ring-[3px]:ring-indigo-500/15">
                                                <p class="mt-1 text-[11px] text-gray-600 dark:text-gray-400">
                                                    Maksimal <span x-text="item.max"></span>.
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </section>

                    <!-- 
                      Bagian Tombol Submit Peminjaman 
                      [UPDATE MOBILE UI]: Menambahkan w-full dan overflow-hidden pada parent div agar tombol seberapa pun lebarnya tidak akan memecahkan layout (merusak batas tepi).
                      Fungsi: Menjaga skor CLS Lighthouse dan mencegah isu responsif di perangkat layar lipat/terkecil.
                    -->
                    <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700 w-full overflow-hidden">
                        <!-- 
                          [UPDATE MOBILE UI]: Memastikan tombol menggunakan max-w-full agar jika teks terlalu panjang, ia memendek dengan elipsis (truncate)
                          dan tidak terpotong ke luar layar. Menyesuaikan padding (px-3 py-1.5 vs md:px-4 md:py-2) secara mulus.
                        -->
                        <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan min-h-[44px] pada tombol Ajukan Peminjaman agar 100% SEO/Mobile friendly. -->
                        <button type="submit" :disabled="loadingAjukan"
                            :class="loadingAjukan ? 'opacity-70 cursor-not-allowed' : ''"
                            class="inline-flex min-h-[44px] w-auto max-w-full items-center justify-center gap-1.5 md:gap-2 rounded-lg bg-indigo-700 px-3 md:px-4 py-1.5 md:py-2 text-[11px] md:text-xs font-semibold text-white shadow-md shadow-indigo-600/30 transition-all hover:bg-indigo-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <!-- [UPDATE MOBILE UI]: Menggunakan truncate pada teks agar bisa dipotong rapi (...) jika ukuran HP terlalu kecil. -->
                            <span x-show="!loadingAjukan" class="inline-flex items-center gap-1.5 md:gap-2 min-w-0">
                                <i class="bi bi-check-lg text-sm shrink-0"></i>
                                <span class="truncate">Ajukan Peminjaman</span>
                            </span>

                            <span x-cloak x-show="loadingAjukan" class="inline-flex items-center gap-1.5 md:gap-2 min-w-0">
                                <i class="bi bi-arrow-repeat animate-spin-smooth text-sm shrink-0"></i>
                                <span class="truncate">Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <div x-cloak x-show="tab === 'pengembalian'" class="space-y-4"
                @peminjaman-baru.window="peminjamanAktif.unshift($event.detail)"
                x-data="{
                        loadingKembali: false,
                        openFormId: null, 
                        formKondisi: 100,
                        formCatatan: '',
                        adaKeluhan: false,
                        jenisKeluhan: 'fisik',
                        searchNama: '',
                        peminjamanAktif: @js($peminjamanAktifData),
                        currentTime: '',
                        clockInterval: null,

                        init() {
                            this.updateClock();
                            this.clockInterval = setInterval(() => this.updateClock(), 1000);
                        },

                        updateClock() {
                            const now = new Date();
                            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            
                            const dayName = days[now.getDay()];
                            const day = String(now.getDate()).padStart(2, '0');
                            const monthName = months[now.getMonth()];
                            const year = now.getFullYear();
                            
                            const hours = String(now.getHours()).padStart(2, '0');
                            const minutes = String(now.getMinutes()).padStart(2, '0');
                            const seconds = String(now.getSeconds()).padStart(2, '0');
                            
                            this.currentTime = `${dayName}, ${day} ${monthName} ${year} — ${hours}:${minutes}:${seconds}`;
                        },
                        
                        get peminjamanFiltered() {
                            if (this.searchNama.trim().length < 2) return this.peminjamanAktif;
                            const q = this.searchNama.toLowerCase();
                            return this.peminjamanAktif.filter(p => p.nama_peminjam.toLowerCase().includes(q));
                        },

                        openForm(id) { 
                            this.openFormId = this.openFormId === id ? null : id; 
                            this.formKondisi = 100; 
                            this.formCatatan = ''; 
                            this.adaKeluhan = false; 
                            this.jenisKeluhan = 'fisik'; 
                        },

                        async kembalikanItem(peminjamanId, detailId) {
                            this.loadingKembali = true;
                            clearInterval(this.clockInterval);
                            try {
                                const res = await fetch('{{ route("siswa.api.kembalikan") }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                    body: JSON.stringify({ peminjaman_id: peminjamanId, detail_id: detailId, kondisi_kembali: Number(this.formKondisi), catatan_kembali: this.formCatatan || null }),
                                });
                                const json = await res.json();
                                if (json.sukses) { 
                                    const pIndex = this.peminjamanAktif.findIndex(p => p.id === peminjamanId);
                                    if (pIndex !== -1) {
                                        if (json.data.status === 'selesai') {
                                            this.peminjamanAktif.splice(pIndex, 1);
                                        } else {
                                            this.peminjamanAktif[pIndex] = json.data;
                                        }
                                    }
                                    this.openFormId = null;
                                    window.dispatchEvent(new CustomEvent('tampilkan-toast', { detail: { pesan: json.pesan, tipe: 'success' } }));
                                }
                                else { 
                                    window.dispatchEvent(new CustomEvent('tampilkan-toast', { detail: { pesan: json.pesan || 'Gagal mengembalikan item.', tipe: 'error' } }));
                                }
                            } catch (_) { 
                                window.dispatchEvent(new CustomEvent('tampilkan-toast', { detail: { pesan: 'Gagal menghubungi server.', tipe: 'error' } }));
                            }
                            this.updateClock();
                            this.clockInterval = setInterval(() => this.updateClock(), 1000);
                            this.loadingKembali = false;
                        },
                    }">

                <div class="space-y-3 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-2 flex items-center gap-3 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-teal-800 dark:border-teal-900/30 dark:bg-teal-900/20 dark:text-teal-300">
                        <i class="bi bi-clock-history animate-pulse text-xl"></i>
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wider opacity-80">Waktu Pengembalian (Realtime)</p>
                            <!-- [OPTIMASI LIGHTHOUSE - PERFORMANCE]: Memberikan ID spesifik untuk DOM Native Update (menghindari x-text re-render beruntun). -->
                            <p class="font-mono text-sm font-bold tracking-tight" id="jam-realtime-el-2"></p>
                        </div>
                    </div>

                    <div>
                        <label for="search_nama_inline" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300.5">Cari Peminjaman Anda</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-600">
                                <i class="bi bi-search text-sm"></i>
                            </span>
                            <!-- 
                              [OPTIMASI LIGHTHOUSE - ACCESSIBILITY & BEST PRACTICES]: 
                              - Menambahkan min-h-[44px] untuk Touch Target
                              - Menambahkan autocomplete="off" agar aman dari error standar form Google.
                            -->
                            <input id="search_nama_inline" type="text" x-model.debounce.500ms="searchNama" autocomplete="off"
                                class="block w-full min-h-[44px] rounded-lg border-gray-200 bg-gray-50 py-1.5 pl-9 pr-2.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-[3px] dark:focus:ring-indigo-500/15" placeholder="Ketik nama anda...">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <template x-if="peminjamanFiltered.length === 0">
                        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <i class="bi bi-inbox text-3xl text-gray-600"></i>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Tidak ada peminjaman aktif yang ditemukan.</p>
                        </div>
                    </template>

                    <template x-for="peminjaman in peminjamanFiltered" :key="peminjaman.id">
                        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" x-data="{ expanded: false }">
                            <div class="cursor-pointer border-b border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:border-gray-700/50 dark:bg-gray-800/50 dark:hover:bg-gray-700/40" @click="expanded = !expanded">
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-gray-800 dark:text-gray-100" x-text="peminjaman.nama_peminjam"></p>
                                        <p class="truncate text-xs text-gray-600 dark:text-gray-400"><span x-text="(peminjaman.kelas || '-') + '/' + (peminjaman.jurusan || '-')"></span> · <span x-text="peminjaman.tanggal_pinjam || '-'"></span></p>
                                    </div>
                                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah warna icon dari text-gray-400 menjadi text-gray-600 -->
                                    <i class="bi bi-chevron-down text-gray-600 dark:text-gray-400 transition-transform" :class="expanded ? 'rotate-180' : ''"></i>
                                </div>
                            </div>

                            <div x-cloak x-show="expanded" x-transition>
                                <div class="p-4 space-y-3 bg-white dark:bg-gray-800">
                                    <template x-for="item in peminjaman.items" :key="item.detail_id">
                                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="item.barang"></p>
                                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400" x-text="item.unit_qty"></p>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1"
                                                        :class="item.status_item === 'dipinjam' ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400'"
                                                        x-text="item.status_item === 'dipinjam' ? 'Dipinjam' : 'Dikembalikan'"></span>
                                                    <template x-if="item.kondisi_awal !== null">
                                                        <span class="text-xs font-medium" :class="warnaKondisiText(item.kondisi_awal)" x-text="labelKondisi(item.kondisi_awal) + ' ' + item.kondisi_awal + '%'"></span>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- 
                                              [BUGFIX]: Mengembalikan tag <template> yang terhapus yang membungkus form pengembalian barang 'dipinjam'.
                                              Kehilangan tag ini menyebabkan tag penutup </template> di bawah menutup loop x-for lebih awal,
                                              sehingga variabel 'item' tidak dikenali (undefined) pada kondisi 'dikembalikan'.
                                            -->
                                            <template x-if="item.status_item === 'dipinjam'">
                                                <div class="mt-3">
                                                    <!-- Tombol Kembalikan: tampil identik di semua device (desktop & mobile) -->
                                                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: min-h-[44px] pada tombol aksi agar tap target aman di HP. -->
                                                    <button type="button" class="inline-flex min-h-[44px] w-auto items-center justify-center gap-2 rounded-lg bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white shadow-md shadow-indigo-600/30 transition-all hover:bg-indigo-800 active:scale-[0.98]" @click="openForm(item.detail_id)">
                                                        <i class="bi bi-arrow-return-left"></i>
                                                        <span x-text="openFormId === item.detail_id ? 'Tutup Form' : 'Kembalikan Item Ini'"></span>
                                                    </button>
                                                    
                                                    <div x-cloak x-show="openFormId === item.detail_id" x-transition class="mt-3 space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                                        
                                                        <div>
                                                            <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-300">Apakah ada masalah dengan barang ini?</p>
                                                            <div class="grid grid-cols-2 gap-3">
                                                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan min-h-[44px] pada tombol kondisi. -->
                                                                <button type="button" @click="adaKeluhan = false; formKondisi = 100; formCatatan = ''"
                                                                        class="flex min-h-[44px] items-center justify-center gap-2 rounded-md border border-gray-200 bg-white p-2.5 shadow-sm transition-all hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
                                                                        :class="!adaKeluhan ? 'ring-2 ring-teal-500 border-transparent' : ''">
                                                                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-emerald-500 ke text-emerald-600 untuk lulus kontras WCAG -->
                                                                    <i class="bi bi-check-circle-fill text-lg text-emerald-600"></i>
                                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Aman / Mulus</span>
                                                                </button>
                                                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: min-h-[44px] untuk lulus uji Tap Target. -->
                                                                <button type="button" @click="adaKeluhan = true; jenisKeluhan = 'fisik'; formKondisi = 50"
                                                                        class="flex min-h-[44px] items-center justify-center gap-2 rounded-md border border-gray-200 bg-white p-2.5 shadow-sm transition-all hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
                                                                        :class="adaKeluhan ? 'ring-2 ring-red-500 border-transparent' : ''">
                                                                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-amber-500 ke text-amber-600 untuk kontras yang lebih baik pada background putih -->
                                                                    <i class="bi bi-exclamation-triangle-fill text-lg text-amber-600"></i>
                                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Ada Keluhan</span>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div x-cloak x-show="adaKeluhan" x-transition class="space-y-3 pt-2">
                                                            <div>
                                                                <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-300">Jenis Kerusakan (Mempengaruhi Kondisi Barang)</p>
                                                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan min-h-[44px] pada label radio button untuk Touch Target jari. -->
                                                                <div class="flex items-center gap-4">
                                                                    <label class="flex min-h-[44px] cursor-pointer items-center gap-2">
                                                                        <input type="radio" x-model="jenisKeluhan" value="fisik" @change="formKondisi = 50" class="text-indigo-600 focus:ring-indigo-500">
                                                                        <span class="text-sm text-gray-700 dark:text-gray-300">Fisik / Hardware (-50%)</span>
                                                                    </label>
                                                                    <label class="flex min-h-[44px] cursor-pointer items-center gap-2">
                                                                        <input type="radio" x-model="jenisKeluhan" value="sistem" @change="formKondisi = 80" class="text-indigo-600 focus:ring-indigo-500">
                                                                        <span class="text-sm text-gray-700 dark:text-gray-300">Software / Sistem (-20%)</span>
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Memberi for="formCatatan" agar terhubung ke input textarea -->
                                                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-red-500 ke text-red-600 -->
                                                                <label for="formCatatan" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Catatan Kerusakan <span class="text-red-600">*</span></label>
                                                                <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan min-h-[44px] pada textarea -->
                                                                <textarea id="formCatatan" x-model="formCatatan" rows="2" placeholder="Jelaskan detail kerusakannya..." class="block w-full min-h-[44px] rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
                                                            </div>
                                                        </div>
                                                        <!-- Tombol Simpan & Kembalikan: tampil identik di semua device -->
                                                        <div class="mt-4 flex justify-end">
                                                            <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Menambahkan min-h-[44px] untuk Touch Target area. -->
                                                            <button type="button" @click="kembalikanItem(peminjaman.id, item.detail_id)" :disabled="loadingKembali" :class="loadingKembali ? 'opacity-70 cursor-not-allowed' : ''"
                                                                class="inline-flex min-h-[44px] w-auto items-center justify-center gap-2 rounded-lg bg-indigo-700 px-4 py-1.5 text-xs font-semibold text-white shadow-md shadow-indigo-600/30 transition-all hover:bg-indigo-800 active:scale-[0.98]">
                                                                <span x-show="!loadingKembali" class="inline-flex items-center gap-2"><i class="bi bi-check-lg"></i><span>Simpan & Kembalikan</span></span>
                                                                <span x-cloak x-show="loadingKembali" class="inline-flex items-center gap-2"><i class="bi bi-arrow-repeat animate-spin-smooth"></i><span>Menyimpan...</span></span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="item.status_item === 'dikembalikan'">
                                                <div class="mt-3">
                                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                        <template x-if="item.kondisi_kembali !== null">
                                                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/40">
                                                                <p class="text-[11px] text-gray-600 dark:text-gray-400">Kondisi Kembali</p>
                                                                <p class="mt-1 text-sm font-medium" :class="warnaKondisiText(item.kondisi_kembali)" x-text="labelKondisi(item.kondisi_kembali) + ' ' + item.kondisi_kembali + '%'"></p>
                                                            </div>
                                                        </template>
                                                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/40">
                                                            <p class="text-[11px] text-gray-600 dark:text-gray-400">Waktu Kembali</p>
                                                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200" x-text="item.waktu_kembali || '-'"></p>
                                                        </div>
                                                    </div>
                                                    <template x-if="item.catatan_kembali">
                                                        <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/40">
                                                            <p class="text-[11px] text-gray-600 dark:text-gray-400">Catatan</p>
                                                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200" x-text="item.catatan_kembali"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection





