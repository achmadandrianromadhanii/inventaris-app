@props(['name', 'title' => null, 'maxWidth' => 'max-w-lg', 'closeOnOverlay' => true])

<div x-cloak x-show="{{ $name }}" x-transition.opacity.duration.300ms class="fixed inset-0 z-[90] overflow-y-auto"
    aria-modal="true" role="dialog" @keydown.escape.window="{{ $name }} = false">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
        @if ($closeOnOverlay) @click="{{ $name }} = false" @endif></div>

    <!-- Container: Center on all devices -->
    <div class="relative flex min-h-screen items-center justify-center p-4">
        
        <!-- Modal -->
        <div x-show="{{ $name }}" 
            x-transition:enter="transition-transform ease-out duration-200"
            x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95" 
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition-transform ease-in duration-150" 
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            class="w-full {{ $maxWidth }} rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-gray-800"
            @click.stop>

            @if ($title || isset($header))
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700/50">
                    <div class="min-w-0">
                        @isset($header)
                            {{ $header }}
                        @else
                            <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                {{ $title }}
                            </h3>
                        @endisset
                    </div>

                    <button type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-transparent text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:bg-transparent dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                        @click="{{ $name }} = false" aria-label="Tutup modal" title="Tutup">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            @endif

            <div class="p-4">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-700/50">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
