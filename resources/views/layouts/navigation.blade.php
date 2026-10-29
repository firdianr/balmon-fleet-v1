<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        {{-- LOGO GAMBAR BARU --}}
                        <img src="{{ asset('images/logo-komdigi.png') }}" alt="Logo Balmon" class="block h-9 w-auto object-contain">
                        
                        {{-- NAMA SISTEM --}}
                        <span class="font-bold text-lg text-gray-800 tracking-wide">
                            BALMON <span class="text-indigo-600">FLEET</span>
                        </span>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    {{-- LAYER 1: INFORMAL REQUEST / DRAFT --}}
                    <x-nav-link :href="route('draft-bookings.index')" :active="request()->routeIs('draft-bookings.*')">
                        {{ __('Permohonan Armada') }}
                    </x-nav-link>

                    {{-- LAYER 2: SOP PEMINJAMAN DENGAN SURAT TUGAS --}}
                    <x-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                        {{ __('Peminjaman Dinas') }}
                    </x-nav-link>

                    {{-- DROPDOWN KALENDER (ARMADA & DRIVER) --}}
                    <div class="hidden sm:flex h-full sm:-my-px" x-data="{ dropdownOpen: false }">
                        <div class="relative h-full">
                            <button @click="dropdownOpen = !dropdownOpen" 
                                    @click.away="dropdownOpen = false" 
                                    class="inline-flex items-center h-full px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out gap-1.5 focus:outline-none {{ request()->routeIs('calendar.*') || request()->routeIs('drivers.calendar') ? 'border-indigo-500 text-gray-900 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <span>{{ __('Jadwal Operasional') }}</span>
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="dropdownOpen" 
                                x-transition
                                class="absolute left-0 mt-2 w-52 rounded-2xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 overflow-hidden py-1" 
                                x-cloak>
                                <a href="{{ route('calendar.index') }}" 
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6l2 4m-8-4v8m0-8V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v9h2m8 0H9m4 0h2m4 0h2v-4m0 0h-5m3.5 5.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-10 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
                                    </svg>
                                    <span>Jadwal Armada / Mobil</span>
                                </a>
                                <a href="{{ route('drivers.calendar') }}" 
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Jadwal & Status Driver</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    @can('manage-vehicles')
                        <x-nav-link :href="route('admin_app.vehicles.index')" :active="request()->routeIs('admin_app.vehicles.*')">
                            {{ __('Kelola Armada') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('vehicles.index')" :active="request()->routeIs('vehicles.*')">
                            {{ __('Daftar Armada') }}
                        </x-nav-link>
                    @endcan

                    @if(auth()->user()->isAdminApp())
                        <x-nav-link :href="route('admin_app.users.index')" :active="request()->routeIs('admin_app.users.*')">
                            {{ __('Kelola Pengguna') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown (Desktop) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger Button (Mobile Toggle) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile Menu Container) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-200 bg-white">
        <!-- Navigation Links -->
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            {{-- LAYER 1: INFORMAL REQUEST / DRAFT --}}
            <x-responsive-nav-link :href="route('draft-bookings.index')" :active="request()->routeIs('draft-bookings.*')">
                {{ __('Permohonan Armada') }}
            </x-responsive-nav-link>

            {{-- LAYER 2: SOP PEMINJAMAN DENGAN SURAT TUGAS --}}
            <x-responsive-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                {{ __('Peminjaman Dinas') }}
            </x-responsive-nav-link>

            {{-- RESPONSIVE SUBMENU KALENDER --}}
            <div class="border-t border-b border-gray-100 my-1 py-1">
                <span class="px-4 text-[10px] font-bold text-gray-400 uppercase tracking-wider block my-1">Jadwal Operasional</span>
                <x-responsive-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.index')" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6l2 4m-8-4v8m0-8V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v9h2m8 0H9m4 0h2m4 0h2v-4m0 0h-5m3.5 5.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-10 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
                    </svg>
                    <span>{{ __('Kalender Armada') }}</span>
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('drivers.calendar')" :active="request()->routeIs('drivers.calendar')" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ __('Kalender & Status Driver') }}</span>
                </x-responsive-nav-link>
            </div>

            @can('manage-vehicles')
                <x-responsive-nav-link :href="route('admin_app.vehicles.index')" :active="request()->routeIs('admin_app.vehicles.*')">
                    {{ __('Kelola Armada') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('vehicles.index')" :active="request()->routeIs('vehicles.*')">
                    {{ __('Daftar Armada') }}
                </x-responsive-nav-link>
            @endcan

            @if(auth()->user()->isAdminApp())
                <x-responsive-nav-link :href="route('admin_app.users.index')" :active="request()->routeIs('admin_app.users.*')">
                    {{ __('Kelola Pengguna') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>