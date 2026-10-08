<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ __('Pengaturan Profil') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- Header Card / Profile Summary Banner --}}
            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-800 rounded-2xl shadow-lg p-6 sm:p-8 text-white relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
                <div class="relative z-10 flex flex-col sm:flex-row items-center gap-6">
                    <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-3xl font-bold border-2 border-white/30 text-white shadow-inner">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="text-center sm:text-left space-y-1">
                        <h1 class="text-2xl font-extrabold tracking-wide">{{ Auth::user()->name }}</h1>
                        <p class="text-blue-100 text-sm flex items-center justify-center sm:justify-start gap-2">
                            <span>NIP: {{ Auth::user()->nip ?? '-' }}</span>
                            <span>•</span>
                            <span class="bg-white/20 px-2.5 py-0.5 rounded-full text-xs font-medium">{{ Auth::user()->department ?? 'Seksi / Departemen' }}</span>
                        </p>
                        <p class="text-xs text-blue-200 mt-1">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Form Sections Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Profile Information Card --}}
                <div class="p-6 sm:p-8 bg-white shadow-sm border border-blue-50 sm:rounded-2xl hover:shadow-md transition-shadow">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                {{-- Update Password Card --}}
                <div class="p-6 sm:p-8 bg-white shadow-sm border border-blue-50 sm:rounded-2xl hover:shadow-md transition-shadow">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>