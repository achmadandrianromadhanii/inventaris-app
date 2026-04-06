@extends('layouts.app')

@section('title', 'Dashboard')
@section('meta_description', 'Dashboard Shiro — ringkasan inventaris, kondisi barang, transaksi, dan peminjaman.')

@php
    $cards = [
        [
            'label' => 'Total Barang',
            'value' => $kpi['total_barang'],
            'border' => 'border-l-blue-500',
            'iconWrap' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
            'icon' => 'bi-box-seam',
        ],
        [
            'label' => 'Tersedia',
            'value' => $kpi['barang_tersedia'],
            'border' => 'border-l-emerald-500',
            'iconWrap' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400',
            'icon' => 'bi-check-circle',
        ],
        [
            'label' => 'Dipinjam',
            'value' => $kpi['barang_dipinjam'],
            'border' => 'border-l-amber-500',
            'iconWrap' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400',
            'icon' => 'bi-people',
        ],
        [
            'label' => 'Rusak',
            'value' => $kpi['barang_rusak'],
            'border' => 'border-l-red-400',
            'iconWrap' => 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400',
            'icon' => 'bi-exclamation-triangle',
        ],
        [
            'label' => 'Total Kategori',
            'value' => $kpi['total_kategori'],
            'border' => 'border-l-violet-500',
            'iconWrap' => 'bg-violet-50 text-violet-600 dark:bg-violet-900/20 dark:text-violet-400',
            'icon' => 'bi-tags',
        ],
    ];
@endphp

@section('content')
    <div id="dashboard-root" class="space-y-3">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($cards as $card)
                <section
                    class="rounded-lg border border-gray-200 border-l-4 {{ $card['border'] }} bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $card['label'] }}
                            </p>

                            <p class="mt-1 text-xl font-bold text-gray-800 dark:text-gray-100" x-data="{
                                value: 0,
                                target: {{ (int) $card['value'] }},
                                started: false,
                                start() {
                                    if (this.started) return;
                                    this.started = true;
                            
                                    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                            
                                    if (reduceMotion) {
                                        this.value = this.target;
                                        return;
                                    }
                            
                                    const duration = 900;
                                    const startTime = performance.now();
                                    const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
                            
                                    const animate = (currentTime) => {
                                        const elapsed = currentTime - startTime;
                                        const progress = Math.min(elapsed / duration, 1);
                            
                                        this.value = Math.round(this.target * easeOutCubic(progress));
                            
                                        if (progress < 1) {
                                            requestAnimationFrame(animate);
                                            return;
                                        }
                            
                                        this.value = this.target;
                                    };
                            
                                    requestAnimationFrame(animate);
                                }
                            }"
                                x-init="start()" x-text="value"></p>
                        </div>

                        <div class="rounded-md p-1.5 {{ $card['iconWrap'] }}">
                            <i class="bi {{ $card['icon'] }} text-lg"></i>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Peminjaman 6 Bulan
                    </h2>
                </div>

                <div class="h-[160px] w-full lg:h-[200px]">
                    <canvas id="lineChart" class="h-full w-full"></canvas>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Kondisi Barang
                    </h2>
                </div>

                <div class="h-[160px] w-full lg:h-[200px]">
                    <canvas id="donutChart" class="h-full w-full"></canvas>
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    Barang per Kategori
                </h2>
            </div>

            <div class="h-[150px] w-full lg:h-[180px]">
                <canvas id="barChart" class="h-full w-full"></canvas>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    Aktivitas Terbaru
                </h2>

                <a href="{{ url('/laporan') }}"
                    class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                    Lihat Laporan →
                </a>
            </div>

            @if ($aktivitasTerbaru->isEmpty())
                <x-empty-state icon="bi-inbox" title="Belum ada aktivitas"
                    message="Aktivitas terbaru akan muncul di sini setelah transaksi dan peminjaman mulai digunakan." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Tanggal
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Jenis
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Barang
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Keterangan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($aktivitasTerbaru as $aktivitas)
                                @php
                                    $badgeClass = match ($aktivitas['jenis']) {
                                        'masuk'
                                            => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400',
                                        'keluar'
                                            => 'bg-gray-100 text-gray-600 ring-gray-400/20 dark:bg-gray-700 dark:text-gray-300',
                                        'dipinjam'
                                            => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-400',
                                        'kembali'
                                            => 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-900/20 dark:text-teal-400',
                                        default
                                            => 'bg-gray-100 text-gray-600 ring-gray-400/20 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                        {{ $aktivitas['tanggal_label'] }}
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <span
                                            class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $badgeClass }}">
                                            {{ ucfirst($aktivitas['jenis']) }}
                                        </span>
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                                        {{ $aktivitas['barang'] }}
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $aktivitas['keterangan'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        (function() {
            const lineLabels = @json($lineChart['labels']);
            const lineData = @json($lineChart['data']);

            const donutData = [
                {{ (int) $kondisiChart['baik'] }},
                {{ (int) $kondisiChart['lumayan'] }},
                {{ (int) $kondisiChart['rusak'] }},
                {{ (int) $kondisiChart['rusak_parah'] }},
            ];

            const barLabels = @json($barChart['labels']);
            const barData = @json($barChart['data']);

            const initDashboardCharts = () => {
                const root = document.getElementById('dashboard-root');

                if (!root || typeof Chart === 'undefined') {
                    return;
                }

                if (Array.isArray(window.chartInstances)) {
                    window.chartInstances.forEach((instance) => {
                        if (instance && typeof instance.destroy === 'function') {
                            instance.destroy();
                        }
                    });
                }

                window.chartInstances = [];

                const isDark = () => document.documentElement.classList.contains('dark');
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                const gridColor = () => (isDark() ? '#374151' : '#e5e7eb');
                const tickColor = () => (isDark() ? '#9ca3af' : '#6b7280');

                const chartAnimation = reduceMotion ?
                    false :
                    {
                        duration: 600,
                        easing: 'easeOutQuart',
                    };

                const commonLegendLabel = {
                    font: {
                        size: 11,
                    },
                    color: () => tickColor(),
                };

                const commonTicks = {
                    color: () => tickColor(),
                    font: {
                        size: 11,
                    },
                };

                const buildLineChart = () => {
                    const canvas = document.getElementById('lineChart');

                    if (!canvas) {
                        return;
                    }

                    const existing = Chart.getChart(canvas);

                    if (existing) {
                        existing.destroy();
                    }

                    const chart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: lineLabels,
                            datasets: [{
                                label: 'Peminjaman',
                                data: lineData,
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                                tension: 0.35,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 3,
                                pointHoverRadius: 4,
                            }, ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: chartAnimation,
                            plugins: {
                                legend: {
                                    display: false,
                                    labels: commonLegendLabel,
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                },
                            },
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: () => gridColor(),
                                    },
                                    ticks: commonTicks,
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: () => gridColor(),
                                    },
                                    ticks: {
                                        ...commonTicks,
                                        precision: 0,
                                    },
                                },
                            },
                        },
                    });

                    window.chartInstances.push(chart);
                };

                const buildDonutChart = () => {
                    const canvas = document.getElementById('donutChart');

                    if (!canvas) {
                        return;
                    }

                    const existing = Chart.getChart(canvas);

                    if (existing) {
                        existing.destroy();
                    }

                    const chart = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: [
                                'Baik (80-100%)',
                                'Lumayan (60-79%)',
                                'Rusak (35-59%)',
                                'Rusak Parah (≤34%)',
                            ],
                            datasets: [{
                                data: donutData,
                                backgroundColor: ['#059669', '#2563eb', '#f59e0b', '#ef4444'],
                                borderWidth: 0,
                                hoverOffset: reduceMotion ? 0 : 6,
                            }, ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '64%',
                            animation: chartAnimation,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        ...commonLegendLabel,
                                        boxWidth: 10,
                                        boxHeight: 10,
                                        padding: 12,
                                    },
                                },
                            },
                        },
                    });

                    window.chartInstances.push(chart);
                };

                const buildBarChart = () => {
                    const canvas = document.getElementById('barChart');

                    if (!canvas) {
                        return;
                    }

                    const existing = Chart.getChart(canvas);

                    if (existing) {
                        existing.destroy();
                    }

                    const chart = new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: barLabels,
                            datasets: [{
                                label: 'Jumlah Barang',
                                data: barData,
                                backgroundColor: '#2563eb',
                                borderRadius: 6,
                                maxBarThickness: 36,
                            }, ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: chartAnimation,
                            plugins: {
                                legend: {
                                    display: false,
                                    labels: commonLegendLabel,
                                },
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false,
                                    },
                                    ticks: commonTicks,
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: () => gridColor(),
                                    },
                                    ticks: {
                                        ...commonTicks,
                                        precision: 0,
                                    },
                                },
                            },
                        },
                    });

                    window.chartInstances.push(chart);
                };

                buildLineChart();
                buildDonutChart();
                buildBarChart();
            };

            const bootWhenReady = () => {
                if (typeof Chart === 'undefined') {
                    window.setTimeout(bootWhenReady, 80);
                    return;
                }

                initDashboardCharts();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootWhenReady, {
                    once: true
                });
            } else {
                bootWhenReady();
            }
        })();
    </script>
@endpush
