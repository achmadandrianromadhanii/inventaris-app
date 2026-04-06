@extends('layouts.guest')

@section('title', 'Login')
@section('meta_description', 'Login administrator website — Sistem Inventaris Lab RPL SMKN 9 Malang')

@section('content')
    <div class="flex min-h-screen items-center justify-center p-4">
        <div
            class="w-full max-w-xs animate-slide-up rounded-xl border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="space-y-4">
                <div class="text-center">
                    <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo SMKN 9 Malang"
                        class="mx-auto h-16 w-16 object-contain">

                    <h1 class="mt-3 text-base font-bold text-gray-900 dark:text-gray-100">
                        SMKN 9 Malang
                    </h1>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Sistem Inventaris Lab RPL
                    </p>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ showPassword: false, loading: false }"
                    @submit="loading = true">
                    @csrf

                    @if ($errors->any())
                        <div class="animate-slide-up rounded-lg border border-red-200 bg-red-50 px-2.5 py-2 text-xs text-red-600 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-400"
                            aria-live="polite">
                            <div class="flex items-start gap-2">
                                <i class="bi bi-exclamation-circle-fill mt-0.5"></i>

                                <div class="space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="email" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Email
                        </label>

                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                            autocomplete="username" required autofocus
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                            class="block w-full rounded-md border-gray-300 px-2.5 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500">

                        @error('email')
                            <p id="email-error" class="mt-1 text-[11px] text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Password
                        </label>

                        <div class="relative">
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password" required
                                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                class="block w-full rounded-md border-gray-300 px-2.5 py-2 pr-10 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500">

                            <button type="button"
                                class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                                <i class="bi text-sm" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>

                        @error('password')
                            <p id="password-error" class="mt-1 text-[11px] text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button type="submit" :disabled="loading" :class="loading ? 'cursor-not-allowed opacity-70' : ''"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <template x-if="!loading">
                            <span>Masuk</span>
                        </template>

                        <template x-if="loading">
                            <span class="inline-flex items-center gap-2">
                                <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                                <span>Masuk...</span>
                            </span>
                        </template>
                    </button>
                </form>

                <p class="text-center text-[10px] text-gray-400 dark:text-gray-500">
                    © 2025 Website · SMKN 9 Malang
                </p>
            </div>
        </div>
    </div>
@endsection
