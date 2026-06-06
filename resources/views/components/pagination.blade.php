{{-- Always show pagination to inform user of data count --}}
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                    class="ml-2 inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Berikutnya
                </a>
            @else
                <span
                    class="ml-2 inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                    Berikutnya
                </span>
            @endif
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Menampilkan
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->firstItem() }}</span>
                    sampai
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->lastItem() }}</span>
                    dari
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $paginator->total() }}</span>
                    data
                </p>
            </div>

            <div>
                <span class="inline-flex items-center gap-1">
                    @if ($paginator->onFirstPage())
                        <span
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 px-2 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                            <i class="bi bi-chevron-left"></i>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            aria-label="Halaman sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span
                                class="inline-flex h-8 min-w-8 items-center justify-center px-1 text-xs text-gray-400 dark:text-gray-500">
                                {{ $element }}
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page === $paginator->currentPage())
                                    <span aria-current="page"
                                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-indigo-600 px-2 text-xs font-medium text-white shadow-sm shadow-indigo-500/30">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}"
                                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                        aria-label="Ke halaman {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            aria-label="Halaman berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    @else
                        <span
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 px-2 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
