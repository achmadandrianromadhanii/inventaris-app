@extends('layouts.guest')

@section('title', 'Verifikasi Email')
@section('meta_description', 'Verifikasi email administrator website — Sistem Inventaris Lab RPL SMKN 9 Malang')

@section('content')
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm animate-slide-up rounded-2xl border border-gray-100 bg-white p-8 shadow-xl shadow-gray-200/50 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
            <div class="space-y-4">
                <div class="text-center">
                    <img src="{{ asset('images/logo.webp') }}" alt="Logo SMKN 9 Malang"
                        class="mx-auto h-16 w-16 object-contain" width="64" height="64" fetchpriority="high"
                        decoding="async" draggable="false">

                    <h1 class="mt-3 text-base font-bold text-gray-900 dark:text-gray-100">
                        Verifikasi Email
                    </h1>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Terima kasih telah mendaftar! Sebelum memulai, bisakah Anda memverifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan ke email Anda? Jika Anda tidak menerima email tersebut, kami akan dengan senang hati mengirimkan ulang.
                    </p>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                @if (session('status') == 'verification-link-sent')
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-400">
                        Tautan verifikasi baru telah dikirimkan ke alamat email yang Anda berikan saat registrasi.
                    </div>
                @endif

                <div class="flex flex-col gap-3 mt-4">
                    <form method="POST" action="{{ route('verification.send') }}" x-data="{ loading: false }" @submit="loading = true" class="w-full">
                        @csrf
                        <button type="submit" :disabled="loading" :class="loading ? 'cursor-not-allowed opacity-70' : ''"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 py-2.5 text-sm font-medium text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-700 shadow-md shadow-indigo-500/30">
                            <template x-if="!loading">
                                <span>Kirim Ulang Email Verifikasi</span>
                            </template>

                            <template x-if="loading">
                                <span class="inline-flex items-center gap-2">
                                    <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                                    <span>Mengirim...</span>
                                </span>
                            </template>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="w-full text-center">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-500 transition-colors">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
