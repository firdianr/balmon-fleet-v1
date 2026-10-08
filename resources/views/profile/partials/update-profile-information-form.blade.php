<section>
    <header class="border-b border-blue-100 pb-4 mb-6">
        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ __('Informasi Profil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            {{ __("Perbarui informasi profil akun dan alamat email Anda.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        {{-- NAMA --}}
        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" class="text-gray-700 font-semibold" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-xl" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- NIP (READONLY) --}}
        <div>
            <x-input-label for="nip" :value="__('NIP (Nomor Induk Pegawai)')" class="text-gray-700 font-semibold" />
            <x-text-input id="nip" name="nip" type="text" class="mt-1 block w-full bg-slate-100 text-gray-500 border-gray-200 cursor-not-allowed rounded-xl" :value="old('nip', $user->nip ?? '-')" readonly />
            <p class="mt-1 text-xs text-blue-600 font-medium">* NIP dikelola oleh Administrator.</p>
        </div>

        {{-- EMAIL --}}
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-gray-700 font-semibold" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-xl" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                    <p class="text-sm text-amber-800">
                        {{ __('Alamat email Anda belum diverifikasi.') }}

                        <button form="send-verification" class="underline text-sm text-blue-600 hover:text-blue-800 font-medium focus:outline-none">
                            {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-emerald-600">
                            {{ __('Link verifikasi baru telah dikirim ke alamat email Anda.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- TELEPON & DEPARTEMEN --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="phone" :value="__('Nomor Telepon')" class="text-gray-700 font-semibold" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-xl" :value="old('phone', $user->phone)" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-input-label for="department" :value="__('Seksi / Departemen')" class="text-gray-700 font-semibold" />
                <x-text-input id="department" name="department" type="text" class="mt-1 block w-full bg-slate-100 text-gray-500 border-gray-200 cursor-not-allowed rounded-xl" :value="old('department', $user->department)" readonly />
                <x-input-error class="mt-2" :messages="$errors->get('department')" />
                <p class="mt-1 text-xs text-blue-600 font-medium">* Dikelola Administrator.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl shadow-sm hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                {{ __('Simpan Perubahan') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm font-semibold text-emerald-600 flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('Berhasil disimpan.') }}
                </p>
            @endif
        </div>
    </form>
</section>