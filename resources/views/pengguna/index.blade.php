@extends('layouts.app')

@section('title', 'Pengguna')
@section('meta_description', 'Kelola akun administrator Shiro.')

@section('content')
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Pengguna
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Kelola akun admin yang dapat mengakses sistem.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-400">
                    {{ $pengguna->total() }} pengguna
                </span>

                <button type="button"
                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
                    @click="$dispatch('open-tambah-pengguna')">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Pengguna</span>
                </button>
            </div>
        </div>

        @if ($pengguna->count() > 0)
            <div
                class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 lg:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    #
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Nama
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Email
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Peran
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Aktivitas
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Dibuat
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-right text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pengguna as $item)
                                @php
                                    $isSelf = (int) auth()->id() === (int) $item->id;
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $pengguna->firstItem() + $loop->index }}
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->nama, 0, 1)) }}
                                            </div>

                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                                    {{ $item->nama }}
                                                </p>

                                                @if ($isSelf)
                                                    <p class="text-[11px] text-blue-600 dark:text-blue-400">
                                                        Akun aktif saat ini
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                        {{ $item->email }}
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-400">
                                            Administrator
                                        </span>
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $item->transaksi_count }} transaksi · {{ $item->peminjaman_count }} peminjaman
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $item->created_at?->format('d M Y') }}
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <div class="flex justify-end gap-1">
                                            <button type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-sm text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30"
                                                @click="$dispatch('open-edit-pengguna-{{ $item->id }}')"
                                                title="Edit pengguna" aria-label="Edit pengguna">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <button type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sky-50 text-sm text-sky-600 hover:bg-sky-100 dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30"
                                                @click="$dispatch('open-reset-password-{{ $item->id }}')"
                                                title="Reset password" aria-label="Reset password">
                                                <i class="bi bi-key"></i>
                                            </button>

                                            <button type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-sm text-red-600 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                                @click="$dispatch('open-hapus-pengguna-{{ $item->id }}')"
                                                title="Hapus pengguna" aria-label="Hapus pengguna"
                                                @disabled($isSelf)>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-3 lg:hidden">
                @foreach ($pengguna as $item)
                    @php
                        $isSelf = (int) auth()->id() === (int) $item->id;
                    @endphp

                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->nama, 0, 1)) }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            {{ $item->nama }}
                                        </p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $item->email }}
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->transaksi_count }} transaksi · {{ $item->peminjaman_count }} peminjaman
                                </p>

                                @if ($isSelf)
                                    <p class="mt-1 text-[11px] text-blue-600 dark:text-blue-400">
                                        Akun aktif saat ini
                                    </p>
                                @endif
                            </div>

                            <span
                                class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-400">
                                Admin
                            </span>
                        </div>

                        <div class="mt-3 flex gap-1">
                            <button type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-sm text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30"
                                @click="$dispatch('open-edit-pengguna-{{ $item->id }}')" title="Edit pengguna"
                                aria-label="Edit pengguna">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <button type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sky-50 text-sm text-sky-600 hover:bg-sky-100 dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30"
                                @click="$dispatch('open-reset-password-{{ $item->id }}')" title="Reset password"
                                aria-label="Reset password">
                                <i class="bi bi-key"></i>
                            </button>

                            <button type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-sm text-red-600 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                @click="$dispatch('open-hapus-pengguna-{{ $item->id }}')" title="Hapus pengguna"
                                aria-label="Hapus pengguna" @disabled($isSelf)>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-1">
                {{ $pengguna->appends(request()->query())->links('components.pagination') }}
            </div>
        @else
            <x-empty-state icon="bi-people" title="Belum ada pengguna"
                message="Tambahkan akun administrator untuk mulai mengelola sistem.">
                <button type="button"
                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
                    @click="$dispatch('open-tambah-pengguna')">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Pengguna</span>
                </button>
            </x-empty-state>
        @endif
    </div>

    <div x-data="{ open: @js(old('_form') === 'tambah-pengguna') }" @open-tambah-pengguna.window="open = true">
        <x-modal1 name="open" title="Tambah Pengguna" max-width="max-w-lg">
            <form method="POST" action="{{ route('pengguna.store') }}" class="space-y-3" x-data="{ loading: false }"
                @submit="loading = true">
                @csrf
                <input type="hidden" name="_form" value="tambah-pengguna">

                <div>
                    <label for="nama" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Nama
                    </label>
                    <input id="nama" name="nama" type="text"
                        value="{{ old('_form') === 'tambah-pengguna' ? old('nama') : '' }}" autocomplete="name"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    @if (old('_form') === 'tambah-pengguna')
                        @error('nama')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label for="email" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Email
                    </label>
                    <input id="email" name="email" type="email"
                        value="{{ old('_form') === 'tambah-pengguna' ? old('email') : '' }}" autocomplete="email"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    @if (old('_form') === 'tambah-pengguna')
                        @error('email')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label for="password" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Password
                    </label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    @if (old('_form') === 'tambah-pengguna')
                        @error('password')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label for="password_confirmation"
                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Konfirmasi Password
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        @click="open = false" :disabled="loading">
                        Batal
                    </button>

                    <button type="submit" :disabled="loading" :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700">
                        <span x-show="!loading">Simpan</span>
                        <span x-show="loading" class="inline-flex items-center gap-2">
                            <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                            <span>Menyimpan...</span>
                        </span>
                    </button>
                </div>
            </form>
        </x-modal1>
    </div>

    @foreach ($pengguna as $item)
        @php
            $isSelf = (int) auth()->id() === (int) $item->id;
        @endphp

        <div x-data="{ open: @js(old('_form') === 'edit-pengguna-' . $item->id) }" @open-edit-pengguna-{{ $item->id }}.window="open = true">
            <x-modal1 name="open" title="Edit Pengguna" max-width="max-w-lg">
                <form method="POST" action="{{ route('pengguna.update', $item) }}" class="space-y-3"
                    x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form" value="edit-pengguna-{{ $item->id }}">

                    <div>
                        <label for="nama-{{ $item->id }}"
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Nama
                        </label>
                        <input id="nama-{{ $item->id }}" name="nama" type="text"
                            value="{{ old('_form') === 'edit-pengguna-' . $item->id ? old('nama') : $item->nama }}"
                            autocomplete="name"
                            class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @if (old('_form') === 'edit-pengguna-' . $item->id)
                            @error('nama')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label for="email-{{ $item->id }}"
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Email
                        </label>
                        <input id="email-{{ $item->id }}" name="email" type="email"
                            value="{{ old('_form') === 'edit-pengguna-' . $item->id ? old('email') : $item->email }}"
                            autocomplete="email"
                            class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @if (old('_form') === 'edit-pengguna-' . $item->id)
                            @error('email')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button"
                            class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            @click="open = false" :disabled="loading">
                            Batal
                        </button>

                        <button type="submit" :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                            class="inline-flex items-center gap-2 rounded-md bg-amber-500 px-3 py-1.5 text-xs text-white hover:bg-amber-600">
                            <span x-show="!loading">Perbarui</span>
                            <span x-show="loading" class="inline-flex items-center gap-2">
                                <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </x-modal1>
        </div>

        <div x-data="{ open: @js(old('_form') === 'reset-password-' . $item->id) }" @open-reset-password-{{ $item->id }}.window="open = true">
            <x-modal1 name="open" title="Reset Password" max-width="max-w-lg">
                <form method="POST" action="{{ route('pengguna.reset-password', $item) }}" class="space-y-3"
                    x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form" value="reset-password-{{ $item->id }}">

                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-200">
                            Reset password untuk <strong>{{ $item->nama }}</strong>.
                        </p>
                    </div>

                    <div>
                        <label for="reset-password-{{ $item->id }}"
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Password Baru
                        </label>
                        <input id="reset-password-{{ $item->id }}" name="password" type="password"
                            autocomplete="new-password"
                            class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @if (old('_form') === 'reset-password-' . $item->id)
                            @error('password')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label for="reset-password-confirmation-{{ $item->id }}"
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Konfirmasi Password Baru
                        </label>
                        <input id="reset-password-confirmation-{{ $item->id }}" name="password_confirmation"
                            type="password" autocomplete="new-password"
                            class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button"
                            class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            @click="open = false" :disabled="loading">
                            Batal
                        </button>

                        <button type="submit" :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                            class="inline-flex items-center gap-2 rounded-md bg-sky-600 px-3 py-1.5 text-xs text-white hover:bg-sky-700">
                            <span x-show="!loading">Simpan Password</span>
                            <span x-show="loading" class="inline-flex items-center gap-2">
                                <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </x-modal1>
        </div>

        <div x-data="{ open: false }" @open-hapus-pengguna-{{ $item->id }}.window="open = true">
            <x-confirm-modal name="open" title="Hapus Pengguna"
                message="Pengguna '{{ $item->nama }}' akan dihapus. Jika pengguna ini memiliki riwayat transaksi, sistem akan menolak proses hapus."
                confirm-text="Ya, Hapus">
                <x-slot:footer>
                    <button type="button"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        @click="open = false">
                        Batal
                    </button>

                    <form method="POST" action="{{ route('pengguna.destroy', $item) }}">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="rounded-md bg-red-500 px-3 py-1.5 text-xs text-white hover:bg-red-600"
                            @disabled($isSelf)>
                            Ya, Hapus
                        </button>
                    </form>
                </x-slot:footer>
            </x-confirm-modal>
        </div>
    @endforeach
@endsection
