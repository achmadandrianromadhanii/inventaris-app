@extends('layouts.guest')

@section('title', 'Lupa Password')
@section('meta_description', 'Lupa password administrator website — Sistem Inventaris Lab RPL SMKN 9 Malang')

@section('content')
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm animate-slide-up rounded-2xl border border-gray-100 bg-white p-8 shadow-xl shadow-gray-200/50 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
            <div class="space-y-4">
                <div class="text-center">
                    <img src="{{ asset('images/logo.webp') }}" alt="Logo SMKN 9 Malang"
                        class="mx-auto h-16 w-16 object-contain" width="64" height="64" fetchpriority="high"
                        decoding="async" draggable="false">

                    <h1 class="mt-3 text-base font-bold text-gray-900 dark:text-gray-100">
                        Lupa Password
                    </h1>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Masukkan email Anda untuk reset password.
                    </p>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                @if (session('status'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-400">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="email" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Email
                        </label>

                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                            required autofocus
                            class="block w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500">

                        @error('email')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button type="submit" :disabled="loading" :class="loading ? 'cursor-not-allowed opacity-70' : ''"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 py-2.5 text-sm font-medium text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-700 shadow-md shadow-indigo-500/30">
                        <template x-if="!loading">
                            <span>Kirim Link Reset Password</span>
                        </template>

                        <template x-if="loading">
                            <span class="inline-flex items-center gap-2">
                                <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                                <span>Mengirim...</span>
                            </span>
                        </template>
                    </button>
                    
                    <div class="text-center mt-4">
                        <a href="{{ route('login') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Kembali ke Login
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
