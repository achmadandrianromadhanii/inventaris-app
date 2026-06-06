@extends('layouts.app')

@section('title', 'Kelola Unit')
@section('meta_description', 'Kelola unit aset inventaris Shiro.')

@section('content')
    {{-- ============================================================ --}}
    {{-- Variabel ringkasan unit (hitung total, tersedia, dll)        --}}
    {{-- ============================================================ --}}
    @php
        $semuaUnit = $barang->relationLoaded('unitBarang') ? $barang->unitBarang : $barang->unitBarang()->get();

        $totalUnit = $semuaUnit->count();
        $jumlahTersedia = $semuaUnit->where('status', 'tersedia')->count();
        $jumlahDipinjam = $semuaUnit->where('status', 'dipinjam')->count();
        $jumlahRusak = $semuaUnit->where('status', 'rusak')->count();
    @endphp

    {{-- ============================================================ --}}
    {{-- Wrapper utama halaman dengan Global Save Engine (Alpine.js)  --}}
    {{-- Engine ini menghitung form yang diubah & menyimpan massal    --}}
    {{-- ============================================================ --}}
    <div class="space-y-3"
        x-data="{
            dirtyCount: 0,
            saving: false,
            saveProgress: '',
            loadingPage: false,


            hitungDirty() {
                this.dirtyCount = document.querySelectorAll('.unit-card.is-dirty').length;
            },

            async simpanMassal() {
                const dirtyCards = document.querySelectorAll('.unit-card.is-dirty');
                if (dirtyCards.length === 0) return true; // true = tidak ada masalah

                this.saving = true;
                let berhasil = 0;
                let gagal = 0;
                const total = dirtyCards.length;

                for (let i = 0; i < total; i++) {
                    this.saveProgress = `Menyimpan ${i + 1} dari ${total}...`;
                    const form = dirtyCards[i].querySelector('form');
                    if (!form) continue;

                    try {
                        const formData = new FormData(form);
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        if (response.ok) {
                            berhasil++;
                            dirtyCards[i].classList.remove('is-dirty');
                        } else {
                            gagal++;
                        }
                    } catch (_) {
                        gagal++;
                    }
                }

                this.saving = false;
                this.saveProgress = '';
                this.hitungDirty();

                if (gagal !== 0) {
                    alert('Berhasil: ' + berhasil + ', Gagal: ' + gagal + '. Silakan cek ulang.');
                    return false; 
                }
                
                // Berhasil semua, refresh DOM area unit & statistik
                await this.refreshData(window.location.href, false);
                return true; 
            },

            async refreshData(url, ubahUrl = true) {
                this.loadingPage = true;
                try {
                    const fetchUrl = new URL(url);
                    fetchUrl.searchParams.set('_t', Date.now());

                    const res = await fetch(fetchUrl.toString(), { 
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' },
                        cache: 'no-store'
                    });
                    
                    const htmlText = await res.text();
                    const doc = new DOMParser().parseFromString(htmlText, 'text/html');
                    
                    // 1. Update Statistik dengan Animasi CSS Vanilla
                    const newStatsContainer = doc.querySelector('#stats-container');
                    const oldStatsContainer = document.querySelector('#stats-container');
                    if (newStatsContainer && oldStatsContainer) {
                        // Memudar & mengecil sedikit
                        oldStatsContainer.style.transition = 'opacity 0.3s, transform 0.3s';
                        oldStatsContainer.style.opacity = '0.4';
                        oldStatsContainer.style.transform = 'scale(0.98)';
                        
                        setTimeout(() => {
                            // Tukar isi HTML secara langsung (Foolproof & Akurat 100%)
                            oldStatsContainer.innerHTML = newStatsContainer.innerHTML;
                            // Muncul & membesar kembali
                            oldStatsContainer.style.opacity = '1';
                            oldStatsContainer.style.transform = 'scale(1)';
                        }, 300);
                    }

                    // 2. Update Daftar Unit (Termasuk Badge & Kondisi)
                    const newContainer = doc.querySelector('#ajax-container');
                    if (newContainer && this.$refs.ajaxContainer) {
                        this.$refs.ajaxContainer.innerHTML = newContainer.innerHTML;
                        this.hitungDirty(); // Pastikan state kotor di-reset jika elemen baru masuk
                    }
                    
                    if (ubahUrl) {
                        window.history.pushState({}, '', url);
                    }
                } catch (e) {
                    console.error('Gagal memuat ulang data', e);
                } finally {
                    this.loadingPage = false;
                }
            },

            async gantiHalaman(url) {
                if (this.dirtyCount > 0) {
                    const ok = await this.simpanMassal();
                    if (!ok) return; 
                }
                await this.refreshData(url, true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }"
        @unit-changed.window="hitungDirty()"
        @click="if ($event.target.closest('.ajax-pagination a')) {
            $event.preventDefault();
            gantiHalaman($event.target.closest('a').href);
        }">

        {{-- ============================================================ --}}
        {{-- Header halaman & tombol kembali                              --}}
        {{-- ============================================================ --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Kelola Unit
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $barang->nama }} · {{ $barang->kategori?->nama }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('barang.show', $barang) }}"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    Kembali ke Detail
                </a>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Ringkasan statistik unit (Total, Tersedia, Dipinjam, Rusak)  --}}
        {{-- Diupdate otomatis via innerHTML setelah simpan (tanpa reload)--}}
        {{-- CSS transition di style inline agar animasi smooth           --}}
        {{-- ============================================================ --}}
        <div id="stats-container" 
            style="transition: opacity 0.3s ease, transform 0.3s ease;"
            class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Total Unit</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ $totalUnit }}
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Tersedia</p>
                    <p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ $jumlahTersedia }}
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Dipinjam</p>
                    <p class="mt-1 text-sm font-semibold text-amber-600 dark:text-amber-400">
                        {{ $jumlahDipinjam }}
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Rusak</p>
                    <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">
                        {{ $jumlahRusak }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Daftar kartu unit (masing-masing dengan deteksi dirty)      --}}
        {{-- ============================================================ --}}
        @if ($unit->count() > 0)
            <div id="ajax-container" x-ref="ajaxContainer" class="transition-all duration-300" :class="loadingPage ? 'opacity-40 pointer-events-none scale-[0.99]' : 'opacity-100 scale-100'">
                <div class="space-y-3">
                    @foreach ($unit as $item)
                    @php
                        $formKey = 'unit-' . $item->id;
                        $isCurrentForm = old('_form') === $formKey;

                        $nilaiSerial = $isCurrentForm
                            ? old('serial_number', $item->serial_number)
                            : $item->serial_number;
                        $nilaiStatus = $isCurrentForm ? old('status', $item->status) : $item->status;
                        $nilaiCatatan = $isCurrentForm ? old('catatan', $item->catatan) : $item->catatan;
                        $nilaiKondisi = (int) ($isCurrentForm ? old('kondisi', $item->kondisi) : $item->kondisi);
                    @endphp

                    {{-- ================================================ --}}
                    {{-- Kartu unit individual dengan dirty-state tracker  --}}
                    {{-- Class 'unit-card' digunakan oleh Global Save     --}}
                    {{-- ================================================ --}}
                    <div class="unit-card rounded-lg border border-gray-200 bg-white p-3 transition-colors dark:border-gray-700 dark:bg-gray-800"
                        x-data="{
                            kondisi: {{ $nilaiKondisi }},
                            isiCatatan: {{ $nilaiCatatan ? 'true' : 'false' }},

                            get labelKondisi() {
                                if (this.kondisi >= 80) return 'Baik';
                                if (this.kondisi >= 60) return 'Lumayan';
                                if (this.kondisi >= 35) return 'Rusak';
                                return 'Rusak Parah';
                            },

                            get warnaKondisiText() {
                                if (this.kondisi >= 80) return 'text-emerald-600';
                                if (this.kondisi >= 60) return 'text-blue-600';
                                if (this.kondisi >= 35) return 'text-amber-600';
                                return 'text-red-600';
                            },

                            get warnaSlider() {
                                if (this.kondisi >= 80) return 'accent-color: #059669';
                                if (this.kondisi >= 60) return 'accent-color: #2563eb';
                                if (this.kondisi >= 35) return 'accent-color: #f59e0b';
                                return 'accent-color: #ef4444';
                            },

                            get warnaProgress() {
                                if (this.kondisi >= 80) return 'bg-emerald-500';
                                if (this.kondisi >= 60) return 'bg-blue-500';
                                if (this.kondisi >= 35) return 'bg-amber-500';
                                return 'bg-red-500';
                            },

                            {{-- Tandai kartu ini sebagai 'diubah' --}}
                            tandaiDirty(element) {
                                element.closest('.unit-card').classList.add('is-dirty');
                                element.dispatchEvent(new CustomEvent('unit-changed', { bubbles: true }));
                            }
                        }">

                        {{-- Header kartu: nomor unit, badge, progress bar --}}
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $item->nomor_unit }}
                                    </h2>
                                    <x-kondisi-badge :kondisi="$nilaiKondisi" />
                                    <x-status-badge :status="$nilaiStatus" />
                                </div>

                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Serial: {{ $item->serial_number ?: 'Belum diisi' }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-600 dark:text-gray-300" x-text="kondisi + '%'"></span>

                                <div class="w-full max-w-[72px]">
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-1.5 rounded-full transition-all duration-700 ease-out"
                                            :class="warnaProgress" :style="`width: ${kondisi}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Form unit (tanpa tombol submit individual) --}}
                        <form method="POST" action="{{ route('barang.unit.update', [$barang, $item]) }}"
                            class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="_form" value="{{ $formKey }}">

                            {{-- Kolom kiri: Serial, Status, Catatan --}}
                            <div class="space-y-3">
                                <div>
                                    <label for="serial_number_{{ $item->id }}"
                                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Serial Number
                                    </label>
                                    <div class="relative">
                                        <input id="serial_number_{{ $item->id }}" name="serial_number" type="text"
                                            value="{{ $nilaiSerial }}" maxlength="100"
                                            @input="tandaiDirty($el)"
                                            @keydown.enter.prevent="tandaiDirty($el); $el.blur();"
                                            placeholder="Ketik/Scan SN..."
                                            class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 pr-8 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400">
                                            <i class="bi bi-upc-scan text-lg"></i>
                                        </div>
                                    </div>
                                    @if ($isCurrentForm)
                                        @error('serial_number')
                                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>

                                <div>
                                    <label for="status_{{ $item->id }}"
                                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Status Operasional
                                    </label>
                                    <select id="status_{{ $item->id }}" name="status"
                                        @change="tandaiDirty($el)"
                                        class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                        <option value="tersedia" @selected($nilaiStatus === 'tersedia')>Tersedia</option>
                                        <option value="dipinjam" @selected($nilaiStatus === 'dipinjam')>Dipinjam</option>
                                        <option value="rusak" @selected($nilaiStatus === 'rusak')>Rusak</option>
                                        <option value="keluar" @selected($nilaiStatus === 'keluar')>Keluar</option>
                                    </select>
                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Ini adalah status operasional, terpisah dari kondisi fisik.
                                    </p>
                                    @if ($isCurrentForm)
                                        @error('status')
                                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>

                                <div>
                                    <label class="mb-1 flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" x-model="isiCatatan"
                                            @change="tandaiDirty($el)"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900">
                                        <span>Catatan</span>
                                    </label>
                                    <textarea x-cloak x-show="isiCatatan" x-transition
                                        id="catatan_{{ $item->id }}" name="catatan" rows="3"
                                        :disabled="!isiCatatan"
                                        @input="tandaiDirty($el)"
                                        class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ $nilaiCatatan }}</textarea>
                                    @if ($isCurrentForm)
                                        @error('catatan')
                                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>
                            </div>

                            {{-- Kolom kanan: Slider kondisi & Ringkasan aturan --}}
                            <div class="space-y-3">
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-3">
                                        <label for="kondisi_{{ $item->id }}"
                                            class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                            Kondisi Fisik %
                                        </label>
                                        <span class="text-sm font-semibold" :class="warnaKondisiText">
                                            <span x-text="labelKondisi"></span> <span x-text="kondisi + '%'"></span>
                                        </span>
                                    </div>

                                    <input id="kondisi_{{ $item->id }}" name="kondisi" type="range" min="0"
                                        max="100" x-model="kondisi" :style="warnaSlider"
                                        @input="
                                            tandaiDirty($el);
                                            let s = document.getElementById('status_{{ $item->id }}');
                                            if (kondisi <= 34) {
                                                if (s.value !== 'rusak') { s.value = 'rusak'; tandaiDirty(s); }
                                            } else if (kondisi > 34 && s.value === 'rusak') {
                                                s.value = 'tersedia';
                                                tandaiDirty(s);
                                            }
                                        "
                                        class="block w-full">

                                    <div
                                        class="mt-2 flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                                        <span>Rusak Parah 0%</span>
                                        <span>Rusak 35%</span>
                                        <span>Lumayan 60%</span>
                                        <span>Baik 80%</span>
                                    </div>

                                    {{-- Peringatan otomatis jika kondisi rendah --}}
                                    <div x-cloak x-show="kondisi <= 34" x-transition
                                        class="mt-2 rounded-md border border-red-200 bg-red-50 px-2.5 py-2 text-[11px] text-red-600 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-400">
                                        Kondisi ≤34% akan otomatis mengubah status unit menjadi <strong>rusak</strong>.
                                    </div>

                                    @if ($isCurrentForm)
                                        @error('kondisi')
                                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>

                                {{-- Ringkasan aturan (informasi statis) --}}
                                <div
                                    class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-200">
                                        Ringkasan Aturan
                                    </p>
                                    <ul class="mt-2 space-y-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        <li>• Kondisi = kondisi fisik unit (0–100%).</li>
                                        <li>• Status = status operasional unit.</li>
                                        <li>• Jika kondisi ≤34%, status otomatis rusak saat disimpan.</li>
                                    </ul>
                                </div>
                            </div>
                        </form>
                    </div>
                @endforeach
                </div>

                {{-- ============================================================ --}}
                {{-- Pagination bawaan Laravel (di dalam ajax-container)          --}}
                {{-- ============================================================ --}}
                <div class="pt-4 pb-24 ajax-pagination">
                    {{ $unit->links('components.pagination') }}
                </div>
            
            </div> {{-- End Ajax Container --}}

        {{-- ============================================================ --}}
        {{-- TOMBOL GLOBAL SAVE (Selalu terlihat, disabled jika belum ada perubahan) --}}
        {{-- ============================================================ --}}
        <div class="fixed bottom-6 left-1/2 z-40 -translate-x-1/2 transition-all duration-300"
            :class="dirtyCount > 0 ? 'translate-y-0 opacity-100' : 'translate-y-0 opacity-90'">
            
            <button type="button" @click="simpanMassal()" :disabled="saving || dirtyCount === 0"
                :class="(saving || dirtyCount === 0) ? 'bg-gray-400 cursor-not-allowed opacity-90' : 'bg-indigo-600 hover:bg-indigo-700 hover:-translate-y-0.5 shadow-xl shadow-indigo-500/40'"
                class="inline-flex items-center gap-2.5 rounded-full px-7 py-3 text-sm font-semibold text-white transition-all duration-300">
                
                {{-- Ikon & teks saat IDLE --}}
                <template x-if="!saving">
                    <span class="inline-flex items-center gap-2">
                        <i class="bi" :class="dirtyCount > 0 ? 'bi-save2' : 'bi-check-circle'"></i>
                        <span x-text="dirtyCount > 0 ? `Simpan ${dirtyCount} Perubahan` : 'Tidak Ada Perubahan'"></span>
                    </span>
                </template>

                {{-- Ikon & teks saat PROSES SIMPAN berjalan --}}
                <template x-if="saving">
                    <span class="inline-flex items-center gap-2">
                        <i class="bi bi-arrow-repeat animate-spin-smooth text-base"></i>
                        <span x-text="saveProgress"></span>
                    </span>
                </template>
            </button>
        </div>
        @else
            <x-empty-state icon="bi-cpu" title="Belum ada unit" message="Unit aset belum tersedia untuk barang ini." />
        @endif
    </div>
@endsection
