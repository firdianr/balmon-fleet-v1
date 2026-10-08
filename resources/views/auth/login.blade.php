<x-guest-layout>
    {{-- Pembungkus Utama 2 Kartu --}}
    <div class="relative w-full max-w-4xl bg-white shadow-2xl ring-1 ring-slate-900/5 rounded-2xl overflow-hidden border border-slate-100 my-auto">
        <div class="grid grid-cols-1 md:grid-cols-2">

            {{-- CARD KIRI: INFORMASI SISTEM (BALMON FLEET) --}}
            <div class="bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-800 p-8 sm:p-10 text-white flex flex-col justify-between relative overflow-hidden">
                {{-- Elemen Dekorasi Latar Belakang --}}
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -left-10 -top-10 w-40 h-40 bg-blue-400/20 rounded-full blur-xl pointer-events-none"></div>

                <div class="relative z-10">
                    {{-- Logo Balmon --}}
                    <div class="flex items-center gap-3 mb-8">
                        <img src="{{ asset('images/logo-balmon.png') }}" alt="Logo Balmon" class="h-12 w-auto object-contain bg-white/10 p-1.5 rounded-lg backdrop-blur">
                        <span class="font-bold text-lg tracking-wide border-l border-white/30 pl-3">BALMON SEMARANG</span>
                    </div>

                    {{-- Nama & Deskripsi Aplikasi --}}
                    <div>
                        <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur text-xs font-semibold rounded-full mb-3 tracking-wider uppercase text-blue-100">
                            Sistem Informasi
                        </span>
                        <h2 class="text-3xl font-extrabold tracking-tight mb-3 font-['Plus Jakarta Sans'] leading-tight">
                            BALMON FLEET
                        </h2>
                        <p class="text-blue-100 text-sm leading-relaxed">
                            Aplikasi Manajemen Peminjaman Kendaraan Dinas terpadu untuk mempermudah operasional, pencatatan log perjalanan, hingga verifikasi surat tugas.
                        </p>
                    </div>
                </div>

                {{-- Fitur Unggulan Singkat --}}
                <div class="relative z-10 mt-8 pt-6 border-t border-white/15">
                    <ul class="space-y-2.5 text-xs text-blue-100">
                        <li class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500/30 text-white text-[10px]">✓</span>
                            <span>Pengajuan & Persetujuan Online</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500/30 text-white text-[10px]">✓</span>
                            <span>Pencatatan KM & Log BBM Real-time</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500/30 text-white text-[10px]">✓</span>
                            <span>Nota Keberangkatan & Pengembalian Digital</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- CARD KANAN: FORM LOGIN --}}
            <div class="p-8 sm:p-10 bg-white flex flex-col justify-center">
                <div class="text-center sm:text-left mb-6">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Sign in</h1>
                    <p class="mt-1 text-xs text-slate-500">Masuk ke akun Anda untuk mengakses sistem</p>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- EMAIL -->
                    <div class="relative mt-6">
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email') }}"
                               placeholder="Email Address" 
                               required 
                               autofocus 
                               autocomplete="username"
                               class="peer w-full !border-t-0 !border-x-0 !border-b-2 border-slate-300 !rounded-none px-0 py-1.5 placeholder:text-transparent focus:!border-blue-600 focus:!ring-0 focus:outline-none text-slate-900 text-sm transition-colors @error('email') @enderror" />
                        
                        <label for="email" 
                               class="pointer-events-none absolute top-0 left-0 origin-left -translate-y-1/2 transform text-xs text-slate-600 opacity-75 transition-all duration-100 ease-in-out peer-placeholder-shown:top-1/2 peer-placeholder-shown:text-sm peer-placeholder-shown:text-slate-400 peer-focus:top-0 peer-focus:pl-0 peer-focus:text-xs peer-focus:text-blue-600 font-medium">
                            Email Address
                        </label>
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <!-- PASSWORD -->
                    <div class="relative mt-6" x-data="{ showPassword: false }">
                        <input :type="showPassword ? 'text' : 'password'" 
                            name="password" 
                            id="password" 
                            placeholder="Password" 
                            required 
                            autocomplete="current-password"
                            class="peer w-full !border-t-0 !border-x-0 !border-b-2 border-slate-300 !rounded-none px-0 py-1.5 pr-8 placeholder:text-transparent focus:!border-blue-600 focus:!ring-0 focus:outline-none text-slate-900 text-sm transition-colors @error('password') @enderror" />
                        
                        <label for="password" 
                            class="pointer-events-none absolute top-0 left-0 origin-left -translate-y-1/2 transform text-xs text-slate-600 opacity-75 transition-all duration-100 ease-in-out peer-placeholder-shown:top-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-focus:top-0 peer-focus:pl-0 peer-focus:text-xs peer-focus:text-blue-600 font-medium">
                            Password
                        </label>

                        <!-- Tombol Toggle Show/Hide Password -->
                        <button type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 focus:outline-none p-1 transition-colors">
                            <!-- Icon Mata Terbuka (Password Terlihat) -->
                            <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            <!-- Icon Mata Tertutup / Tertutup Garis (Password Tersembunyi) -->
                            <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.682-.813c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-2.163 3.616m-2.122 2.12a8 8 0 01-11.314 0m11.314 0L3 3l18 18" />
                            </svg>
                        </button>

                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <!-- REMEMBER ME & FORGOT PASSWORD -->
                    <div class="mt-6 flex items-center justify-between text-xs">
                        <label for="remember_me" class="inline-flex items-center cursor-pointer">
                            <input id="remember_me" 
                                   type="checkbox" 
                                   name="remember" 
                                   class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4">
                            <span class="ms-2 text-slate-600">Ingat Saya</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" 
                               class="font-semibold text-blue-600 hover:text-blue-700 hover:underline focus:outline-none">
                                Lupa password?
                            </a>
                        @endif
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="mt-8 mb-2">
                        <button type="submit" 
                                class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 py-3 px-4 text-white font-semibold text-sm shadow-md shadow-blue-500/20 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                            Sign in
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</x-guest-layout>