@props([
    'name',
    'title' => 'Konfirmasi',
    'message' => 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    'confirmText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'confirmClass' => 'bg-red-500 hover:bg-red-600 text-white',
    'icon' => 'bi-exclamation-triangle-fill',
])

<div x-cloak x-show="{{ $name }}" x-transition.opacity.duration.300ms class="fixed inset-0 z-[95] overflow-y-auto"
    aria-modal="true" role="dialog" @keydown.escape.window="{{ $name }} = false">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="{{ $name }} = false"></div>

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
            class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-gray-800"
            @click.stop>

            <div class="p-4">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                        <i class="bi {{ $icon }} text-base"></i>
                    </div>

                    <div class="min-w-0 pt-0">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ $title }}
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                            {{ $message }}
                        </p>
                    </div>
                </div>

                @if (trim($slot) !== '')
                    <div class="mt-4">
                        {{ $slot }}
                    </div>
                @endif

                <!-- Footer / Action Buttons -->
                <div class="mt-4 flex flex-row justify-end gap-2">
                    <button type="button"
                        class="inline-flex w-auto items-center justify-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs text-slate-700 transition-colors hover:bg-slate-200 dark:bg-gray-700 dark:text-slate-200 dark:hover:bg-gray-600"
                        @click="{{ $name }} = false">
                        {{ $cancelText }}
                    </button>

                    @isset($footer)
                        {{ $footer }}
                    @else
                        <button type="button"
                            class="inline-flex w-auto items-center justify-center rounded-lg px-3 py-1.5 text-xs transition-colors {{ $confirmClass }}">
                            {{ $confirmText }}
                        </button>
                    @endisset
                </div>
            </div>
        </div>
    </div>
</div>
