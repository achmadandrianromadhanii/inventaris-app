@props([
    'icon' => 'bi-inbox',
    'title' => 'Belum ada data',
    'message' => 'Data yang Anda cari belum tersedia.',
])

<div
    {{ $attributes->merge([
        'class' =>
            'rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-8 text-center dark:border-gray-700 dark:bg-gray-800/50',
    ]) }}>
    <div
        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-500 dark:bg-indigo-900/20 dark:text-indigo-400">
        <i class="bi {{ $icon }} text-lg"></i>
    </div>

    <h3 class="mt-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
        {{ $title }}
    </h3>

    <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-gray-500 dark:text-gray-400">
        {{ $message }}
    </p>

    @if (trim($slot) !== '')
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
