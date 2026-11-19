<section>
    <header class="border-b border-blue-100 pb-4 mb-6">
        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            {{ __('Pembaruan Kata Sandi') }}
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            {{ __('Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <!-- CURRENT PASSWORD -->
        <div x-data="{ show: false }">
            <x-input-label for="update_password_current_password" :value="__('Kata Sandi Saat Ini')" class="text-gray-700 font-semibold" />
            <div class="relative mt-1">
                <x-text-input id="update_password_current_password" 
                              name="current_password" 
                              ::type="show ? 'text' : 'password'" 
                              class="block w-full pr-10 focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-xl" 
                              autocomplete="current-password" />
                <button type="button" 
                        @click="show = !show" 
                        aria-label="Tampilkan atau sembunyikan kata sandi"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-blue-600 focus:outline-none transition-colors" 
                        tabindex="-1">
                    <svg x-show="show" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg x-show="!show" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <!-- NEW PASSWORD -->
        <div x-data="{ show: false }">
            <x-input-label for="update_password_password" :value="__('Kata Sandi Baru')" class="text-gray-700 font-semibold" />
            <div class="relative mt-1">
                <x-text-input id="update_password_password" 
                              name="password" 
                              ::type="show ? 'text' : 'password'" 
                              class="block w-full pr-10 focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-xl" 
                              autocomplete="new-password" />
                <button type="button" 
                        @click="show = !show" 
                        aria-label="Tampilkan atau sembunyikan kata sandi"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-blue-600 focus:outline-none transition-colors" 
                        tabindex="-1">
                    <svg x-show="show" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg x-show="!show" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <!-- CONFIRM PASSWORD -->
        <div x-data="{ show: false }">
            <x-input-label for="update_password_password_confirmation" :value="__('Konfirmasi Kata Sandi')" class="text-gray-700 font-semibold" />
            <div class="relative mt-1">
                <x-text-input id="update_password_password_confirmation" 
                              name="password_confirmation" 
                              ::type="show ? 'text' : 'password'" 
                              class="block w-full pr-10 focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-xl" 
                              autocomplete="new-password" />
                <button type="button" 
                        @click="show = !show" 
                        aria-label="Tampilkan atau sembunyikan kata sandi"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-blue-600 focus:outline-none transition-colors" 
                        tabindex="-1">
                    <svg x-show="show" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" x-cloak>
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg x-show="!show" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl shadow-sm hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                {{ __('Perbarui Password') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm font-semibold text-emerald-600 flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('Password berhasil diubah.') }}
                </p>
            @endif
        </div>
    </form>
</section>