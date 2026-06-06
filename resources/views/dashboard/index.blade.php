@extends('layouts.app')

@section('title', 'Dashboard')
@section('meta_description', 'Dashboard Shiro — ringkasan inventaris, kondisi barang, transaksi, dan peminjaman.')

@php
$chartJson = json_encode([
    'lineLabels' => $lineChart['labels'],
    'lineData' => $lineChart['data'],
    'donutData' => [
        (int) $kondisiChart['baik'],
        (int) $kondisiChart['lumayan'],
        (int) $kondisiChart['rusak'],
        (int) $kondisiChart['rusak_parah'],
    ],
    'barLabels' => $barChart['labels'],
    'barData' => $barChart['data'],
]);

$cards = [
    [
        'label' => 'Total Barang',
        'value' => $kpi['total_barang'],
        'iconWrap' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        'icon' => 'bi-box-seam',
    ],
    [
        'label' => 'Tersedia',
        'value' => $kpi['barang_tersedia'],
        'iconWrap' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
        'icon' => 'bi-check-circle',
    ],
    [
        'label' => 'Dipinjam',
        'value' => $kpi['barang_dipinjam'],
        'iconWrap' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
        'icon' => 'bi-people',
    ],
    [
        'label' => 'Rusak',
        'value' => $kpi['barang_rusak'],
        'iconWrap' => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400',
        'icon' => 'bi-exclamation-triangle',
    ],
    [
        'label' => 'Total Kategori',
        'value' => $kpi['total_kategori'],
        'iconWrap' => 'bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400',
        'icon' => 'bi-tags',
    ],
];
@endphp

@section('content')
<div id="dashboard-root" class="space-y-4">
    <!-- Top Stats Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($cards as $card)
        <section class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-[0_4px_20px_-4px_rgba(6,81,237,0.08)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_8px_30px_-4px_rgba(6,81,237,0.12)] dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">
                        {{ $card['label'] }}
                    </p>

                    <p class="mt-2 text-3xl font-heading font-extrabold tracking-tight text-gray-800 dark:text-gray-100">
                        {{ $card['value'] }}
                    </p>
                </div>

                <div class="rounded-full p-2.5 transition-colors {{ $card['iconWrap'] }}">
                    <i class="bi {{ $card['icon'] }} text-xl"></i>
                </div>
            </div>
        </section>
        @endforeach
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Tren Peminjaman -->
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-heading font-bold text-gray-800 dark:text-gray-100">
                    Tren Peminjaman
                </h2>
                <select id="filterPeminjaman" class="cursor-pointer rounded-lg border-gray-200 bg-gray-50 py-1.5 pl-3 pr-8 text-xs font-medium text-gray-700 hover:bg-gray-100 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <option value="bulanan" selected>Bulanan</option>
                    <option value="tahunan">Tahunan</option>
                </select>
            </div>
            <div class="h-[280px] w-full" id="chart-peminjaman"></div>
        </section>

        <!-- Kondisi Barang -->
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-heading font-bold text-gray-800 dark:text-gray-100">
                    Kondisi Barang
                </h2>
            </div>
            <div class="h-[280px] w-full" id="chart-kondisi"></div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Kategori Chart -->
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-heading font-bold text-gray-800 dark:text-gray-100">
                    Distribusi Kategori
                </h2>
            </div>
            <div class="h-[250px] w-full" id="chart-kategori"></div>
        </section>

        <!-- Aktivitas Terbaru -->
        <section class="rounded-2xl border border-gray-100 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800 flex flex-col">
            <div class="border-b border-gray-100 dark:border-gray-700 px-5 py-4 flex items-center justify-between">
                <h2 class="text-base font-heading font-bold text-gray-800 dark:text-gray-100">
                    Aktivitas Terbaru
                </h2>
                <a href="{{ url('/laporan') }}"
                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                    Lihat Laporan →
                </a>
            </div>

            @if ($aktivitasTerbaru->isEmpty())
            <div class="p-5 flex-1 flex items-center justify-center">
                <x-empty-state icon="bi-inbox" title="Belum ada aktivitas" message="Aktivitas terbaru akan muncul di sini." />
            </div>
            @else
            <div class="flex-1 overflow-x-auto" x-data="{ page: 1, totalPages: {{ max(1, ceil(count($aktivitasTerbaru) / 5)) }} }">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr>
                            <th class="border-b border-gray-100 bg-gray-50/50 px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Waktu
                            </th>
                            <th class="border-b border-gray-100 bg-gray-50/50 px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Status
                            </th>
                            <th class="border-b border-gray-100 bg-gray-50/50 px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Barang
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($aktivitasTerbaru as $index => $aktivitas)
                        @php
                        $pageIndex = floor($index / 5) + 1;
                        $badgeClass = match ($aktivitas['jenis']) {
                            'masuk' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
                            'keluar' => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400',
                            'dipinjam' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                            'kembali' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
                            default => 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                        };
                        @endphp
                        <tr x-show="page === {{ $pageIndex }}" class="group transition-colors hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10" x-cloak>
                            <td class="border-b border-gray-50 px-5 py-3 text-xs text-gray-500 dark:border-gray-700/50 dark:text-gray-400">
                                {{ $aktivitas['tanggal_label'] }}
                            </td>
                            <td class="border-b border-gray-50 px-5 py-3 dark:border-gray-700/50">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wide {{ $badgeClass }}">
                                    {{ ucfirst($aktivitas['jenis']) }}
                                </span>
                            </td>
                            <td class="border-b border-gray-50 px-5 py-3 dark:border-gray-700/50">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $aktivitas['barang'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $aktivitas['keterangan'] }}</p>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                {{-- Custom Alpine Pagination (Client Side, Matches Laravel Style) --}}
                <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 dark:border-gray-700 dark:bg-gray-800 rounded-b-xl">
                    <!-- Mobile View -->
                    <div class="flex flex-1 justify-between sm:hidden">
                        <button type="button" @click="page > 1 ? page-- : null" :disabled="page === 1"
                            :class="page === 1 ? 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                            class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs transition-colors dark:border-gray-700">
                            Sebelumnya
                        </button>
                        <button type="button" @click="page < totalPages ? page++ : null" :disabled="page === totalPages"
                            :class="page === totalPages ? 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                            class="ml-2 inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs transition-colors dark:border-gray-700">
                            Berikutnya
                        </button>
                    </div>

                    <!-- Desktop View -->
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Menampilkan
                                <span class="font-medium text-gray-700 dark:text-gray-200" x-text="((page - 1) * 5) + 1"></span>
                                sampai
                                <span class="font-medium text-gray-700 dark:text-gray-200" x-text="Math.min(page * 5, {{ count($aktivitasTerbaru) }})"></span>
                                dari
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ count($aktivitasTerbaru) }}</span>
                                data
                            </p>
                        </div>

                        <div>
                            <span class="inline-flex items-center gap-1">
                                <!-- Prev Button -->
                                <button type="button" @click="page > 1 ? page-- : null" :disabled="page === 1"
                                    :class="page === 1 ? 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 px-2 text-xs transition-colors dark:border-gray-700"
                                    aria-label="Halaman sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </button>

                                <!-- Page Numbers -->
                                <template x-for="p in {{ json_encode(range(1, max(1, (int) ceil(count($aktivitasTerbaru) / 5)))) }}" :key="p">
                                    <button type="button" @click="page = p"
                                        :class="page === p ? 'bg-indigo-600 text-white font-medium border-transparent shadow-sm shadow-indigo-500/30' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 border-gray-200 dark:border-gray-700'"
                                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border px-2 text-xs transition-colors"
                                        x-text="p">
                                    </button>
                                </template>

                                <!-- Next Button -->
                                <button type="button" @click="page < totalPages ? page++ : null" :disabled="page === totalPages"
                                    :class="page === totalPages ? 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 px-2 text-xs transition-colors dark:border-gray-700"
                                    aria-label="Halaman berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
{{-- Data dirender statis di awal dari backend untuk performa maksimal dan mencegah layout shift --}}
<script id="chart-data" type="application/json">{!! $chartJson !!}</script>

<script type="module">
    const chartData = JSON.parse(document.getElementById('chart-data').textContent);
    const lineLabels = chartData.lineLabels;
    const lineData = chartData.lineData;
    const donutData = chartData.donutData;
    const barLabels = chartData.barLabels;
    const barData = chartData.barData;

    // Lazy load ApexCharts via Vite untuk Lighthouse Performance 100% (No render blocking)
    const ApexCharts = await window.loadApexCharts();

    // Turbo Drive Lifecycle (Mencegah instance duplikat dan memory leak saat navigasi pindah halaman)
    window.chartInstances = window.chartInstances || [];
    window.chartInstances.forEach(c => c && typeof c.destroy === 'function' && c.destroy());
    window.chartInstances = [];

    const isDark = () => document.documentElement.classList.contains('dark');
    const tickColor = () => isDark() ? '#9ca3af' : '#6b7280';
    const gridColor = () => isDark() ? '#374151' : '#f3f4f6';

    // 1. Peminjaman Area Chart (Inisialisasi langsung dengan data PHP)
    let peminjamanOptions = {
        series: [{ name: 'Peminjaman', data: lineData }],
        chart: { 
            type: 'area', 
            height: 280, 
            toolbar: { show: false }, 
            zoom: { enabled: false },
            fontFamily: 'inherit',
            animations: { enabled: true, easing: 'easeinout', speed: 800 }
        },
        colors: ['#4f46e5'],
        fill: { 
            type: 'gradient', 
            gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 100] } 
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: { 
            type: 'category',
            categories: lineLabels, 
            axisBorder: { show: false }, 
            axisTicks: { show: false }, 
            labels: { style: { colors: tickColor(), fontSize: '11px' } },
            crosshairs: { stroke: { color: '#4f46e5', dashArray: 4 } }
        },
        yaxis: { 
            labels: { style: { colors: tickColor(), fontSize: '11px' }, formatter: (v) => Math.round(v) } 
        },
        grid: { 
            borderColor: gridColor(), 
            strokeDashArray: 4, 
            xaxis: { lines: { show: false } },
            padding: { top: 0, right: 0, bottom: 0, left: 10 }
        },
        tooltip: { theme: isDark() ? 'dark' : 'light' }
    };
    let peminjamanChart = new ApexCharts(document.querySelector("#chart-peminjaman"), peminjamanOptions);
    peminjamanChart.render();
    window.chartInstances.push(peminjamanChart);
    
    document.getElementById('filterPeminjaman').addEventListener('change', async function(e) {
        try {
            const res = await fetch(`{{ route('dashboard.chart.peminjaman') }}?filter=${e.target.value}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if (!res.ok) throw new Error('Network error');
            const data = await res.json();
            
            // Update label dan data sekaligus secara asinkron tanpa memberatkan UI
            peminjamanChart.updateOptions({
                xaxis: { 
                    type: 'category',
                    categories: data.labels 
                },
                series: [{ 
                    name: 'Peminjaman', 
                    data: data.data 
                }]
            }, true, true, true);
        } catch (error) {
            console.error("Gagal sinkronisasi data chart:", error);
        }
    });

    // 2. Kondisi Barang (Radial Donut)
    let donutOptions = {
        series: donutData,
        labels: ['Baik', 'Lumayan', 'Rusak', 'Rusak Parah'],
        chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
        colors: ['#10b981', '#f59e0b', '#f97316', '#e11d48'],
        plotOptions: { 
            pie: { 
                donut: { 
                    size: '75%', 
                    labels: { 
                        show: true, 
                        name: { show: true, fontSize: '12px', color: tickColor() }, 
                        value: { show: true, fontSize: '24px', fontWeight: 800, color: isDark() ? '#f3f4f6' : '#1f2937' }, 
                        total: { show: true, showAlways: true, label: 'Total Unit', color: tickColor() } 
                    } 
                } 
            } 
        },
        dataLabels: { enabled: false },
        legend: { position: 'right', offsetY: 40, labels: { colors: tickColor() }, markers: { radius: 12 } },
        stroke: { show: true, colors: isDark() ? ['#1f2937'] : ['#ffffff'], width: 2 },
        tooltip: { theme: isDark() ? 'dark' : 'light' }
    };
    let donutChart = new ApexCharts(document.querySelector("#chart-kondisi"), donutOptions);
    donutChart.render();
    window.chartInstances.push(donutChart);

    // 3. Distribusi Kategori (Horizontal Bar)
    let barOptions = {
        series: [{ name: 'Jumlah Barang', data: barData }],
        chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
        plotOptions: { 
            bar: { horizontal: true, borderRadius: 4, dataLabels: { position: 'top' }, barHeight: '60%' } 
        },
        colors: ['#6366f1'],
        dataLabels: { 
            enabled: true, offsetX: 20, style: { fontSize: '10px', colors: [tickColor()] } 
        },
        xaxis: { 
            categories: barLabels, 
            labels: { style: { colors: tickColor(), fontSize: '11px' } }, 
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { labels: { style: { colors: tickColor(), fontSize: '11px' } } },
        grid: { borderColor: gridColor(), strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        tooltip: { theme: isDark() ? 'dark' : 'light' }
    };
    let barChart = new ApexCharts(document.querySelector("#chart-kategori"), barOptions);
    barChart.render();
    window.chartInstances.push(barChart);

    // Pusher WebSockets
    var pusherKey = @js(config('broadcasting.connections.pusher.key', ''));
    if (pusherKey) {
        try {
            var { Echo: EchoClass, Pusher } = await window.loadEcho();
            var echo = new EchoClass({
                broadcaster: 'pusher',
                key: pusherKey,
                cluster: @js(config('broadcasting.connections.pusher.options.cluster', 'mt1')),
                forceTLS: true,
                Pusher: Pusher
            });
            var toast = function(msg) {
                var el = document.createElement('div');
                el.className = 'fixed bottom-4 right-4 z-50 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-medium text-white shadow-xl transition-all duration-300 transform translate-y-0 opacity-100';
                el.textContent = msg;
                document.body.appendChild(el);
                setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(10px)'; setTimeout(() => el.remove(), 300); }, 3000);
            };
            echo.channel('inventaris').listen('.inventaris.updated', (e) => toast(e.pesan || 'Inventaris diperbarui'));
            echo.channel('peminjaman').listen('.peminjaman.updated', (e) => toast(e.pesan || 'Peminjaman diperbarui'));
        } catch (_) {}
    }

    // Observer for Dark Mode Toggle (Live update chart themes)
    if (window.chartThemeObserver) {
        window.chartThemeObserver.disconnect();
    }
    
    window.chartThemeObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                // Cek apakah chart masih ada di DOM (mencegah error saat pindah halaman)
                if (!document.getElementById('chart-peminjaman')) {
                    window.chartThemeObserver.disconnect();
                    return;
                }
                
                const dark = isDark();
                const theme = { mode: dark ? 'dark' : 'light' };
                const tc = tickColor();
                const gc = gridColor();
                
                const updateXaxis = { labels: { style: { colors: tc } } };
                const updateYaxis = { labels: { style: { colors: tc } } };
                const updateGrid = { borderColor: gc };

                try {
                    peminjamanChart.updateOptions({ theme: theme, xaxis: updateXaxis, yaxis: updateYaxis, grid: updateGrid });
                    donutChart.updateOptions({ theme: theme, legend: { labels: { colors: tc } }, plotOptions: { pie: { donut: { labels: { name: { color: tc }, total: { color: tc }, value: { color: dark ? '#f3f4f6' : '#1f2937' } } } } }, stroke: { colors: dark ? ['#1f2937'] : ['#ffffff'] } });
                    barChart.updateOptions({ theme: theme, xaxis: updateXaxis, yaxis: updateYaxis, grid: updateGrid, dataLabels: { style: { colors: [tc] } } });
                } catch (error) {
                    console.warn('Gagal mengupdate tema chart:', error);
                }
            }
        });
    });
    window.chartThemeObserver.observe(document.documentElement, { attributes: true });
</script>
@endpush