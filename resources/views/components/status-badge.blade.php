@props([
    'status' => 'tersedia',
])

@php
    $statusKey = strtolower(trim((string) $status));

    $defaultClass = 'bg-gray-100 text-gray-600 ring-gray-400/20 dark:bg-gray-700 dark:text-gray-300';

    $map = [
        'tersedia' => [
            'label' => 'Tersedia',
            'class' =>
                'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400',
        ],
        'dipinjam' => [
            'label' => 'Dipinjam',
            'class' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-400',
        ],
        'rusak' => [
            'label' => 'Rusak',
            'class' => 'bg-red-50 text-red-600 ring-red-500/20 dark:bg-red-900/20 dark:text-red-400',
        ],
        'keluar' => [
            'label' => 'Keluar',
            'class' => $defaultClass,
        ],
        'dikembalikan' => [
            'label' => 'Dikembalikan',
            'class' =>
                'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400',
        ],
        'aktif' => [
            'label' => 'Aktif',
            'class' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-400',
        ],
        'selesai' => [
            'label' => 'Selesai',
            'class' => $defaultClass,
        ],
        'nonaktif' => [
            'label' => 'Nonaktif',
            'class' => $defaultClass,
        ],
    ];

    $config = $map[$statusKey] ?? [
        'label' => ucfirst(str_replace('_', ' ', $statusKey)),
        'class' => $defaultClass,
    ];
@endphp

<span
    {{ $attributes->merge([
        'class' => "inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium leading-none ring-1 {$config['class']}",
    ]) }}>
    {{ $config['label'] }}
</span>
