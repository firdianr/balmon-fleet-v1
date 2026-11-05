<div x-data="{ 
        mobileSidebarOpen: false, 
        calendarOpen: {{ request()->routeIs('calendar.*') || request()->routeIs('drivers.calendar') ? 'true' : 'false' }} 
     }">

    {{-- BACKDROP MOBILE --}}
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs z-40 sm:hidden" 
         @click="mobileSidebarOpen = false"
         x-cloak></div>

    {{-- SIDEBAR MOBILE (SLIDE-OVER DRAWER) --}}
    <aside x-show="mobileSidebarOpen"
           x-transition:enter="transition ease-in-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in-out duration-300 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 w-72 bg-white border-r border-gray-200 z-50 flex flex-col justify-between sm:hidden shadow-2xl"
           x-cloak>
        
        <div class="flex flex-col h-full overflow-y-auto">
            {{-- HEADER MOBILE SIDEBAR --}}
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-komdigi.png') }}" 
                        alt="Logo Balmon" 
                        width="140" 
                        height="40" 
                        decoding="async"
                        class="h-8 w-auto object-contain">
                    <span class="font-bold text-base text-gray-800 tracking-wide">
                        BALMON <span class="text-indigo-600">FLEET</span>
                    </span>
                </a>
                <button type="button" @click="mobileSidebarOpen = false" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100" aria-label="Tutup Menu Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- NAV MENU MOBILE --}}
            <div class="px-3 py-4 space-y-6 flex-1">
                <div class="space-y-1">
                    <span class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Operasional</span>
                    
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('draft-bookings.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('draft-bookings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Permohonan Armada</span>
                    </a>

                    <a href="{{ route('bookings.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('bookings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span>Peminjaman Dinas</span>
                    </a>
                </div>

                {{-- ACCORDION JADWAL --}}
                <div class="space-y-1">
                    <span class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Jadwal Operasional</span>

                    <a href="{{ route('calendar.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('calendar.index') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Kalender Armada</span>
                    </a>

                    <a href="{{ route('drivers.calendar') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('drivers.calendar') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Jadwal & Status Driver</span>
                    </a>
                </div>

                {{-- MASTER DATA --}}
                <div class="space-y-1">
                    @can('manage-vehicles')
                        <span class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Master Data</span>
                        <a href="{{ route('admin_app.vehicles.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('admin_app.vehicles.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2m14 0a2 2 0 11-4 0 2 2 0 014 0zM7 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Kelola Armada</span>
                        </a>

                        <!-- MENU BARU: KELOLA MASTER CHECKLIST -->
                        <a href="{{ route('master-checklists.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('master-checklists.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <span>Kelola Master Checklist</span>
                        </a>
                    @else
                        <span class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Katalog</span>
                        <a href="{{ route('vehicles.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('vehicles.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2m14 0a2 2 0 11-4 0 2 2 0 014 0zM7 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Daftar Armada</span>
                        </a>
                    @endcan

                    @if(auth()->user()->isAdminApp())
                        <a href="{{ route('admin_app.users.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('admin_app.users.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span>Kelola Pengguna</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- USER FOOTER MOBILE --}}
            <div class="p-4 border-t border-gray-200 bg-gray-50/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5 truncate">
                        <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-gray-500 truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Log Out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- SIDEBAR DESKTOP (FIXED LEFT) --}}
    <aside class="hidden sm:flex sm:flex-col sm:w-64 sm:fixed sm:inset-y-0 sm:border-r sm:border-gray-200 sm:bg-white sm:z-30 justify-between">
        <div class="flex flex-col h-full overflow-y-auto">
            {{-- HEADER LOGO DESKTOP --}}
            <div class="px-6 h-16 border-b border-gray-100 flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-komdigi.png') }}" 
                        alt="Logo Balmon" 
                        width="140" 
                        height="40" 
                        decoding="async"
                        class="h-8 w-auto object-contain">
                    <span class="font-bold text-lg text-gray-800 tracking-wide">
                        BALMON <span class="text-indigo-600">FLEET</span>
                    </span>
                </a>
            </div>

            {{-- NAV LINKS DESKTOP --}}
            <div class="px-3 py-5 space-y-6 flex-1">
                {{-- SECTION OPERASIONAL --}}
                <div class="space-y-1">
                    <span class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-2">Operasional</span>

                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700 shadow-2xs' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('draft-bookings.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('draft-bookings.*') ? 'bg-indigo-50 text-indigo-700 shadow-2xs' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Permohonan Armada</span>
                    </a>

                    <a href="{{ route('bookings.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('bookings.*') ? 'bg-indigo-50 text-indigo-700 shadow-2xs' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span>Peminjaman Dinas</span>
                    </a>
                </div>

                {{-- SECTION ACCORDION JADWAL --}}
                <div class="space-y-1">
                    <span class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-2">Penjadwalan</span>

                    <button type="button" 
                            @click="calendarOpen = !calendarOpen" 
                            class="w-full flex items-center justify-between px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('calendar.*') || request()->routeIs('drivers.calendar') ? 'text-indigo-700 bg-indigo-50/60' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Jadwal Operasional</span>
                        </div>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': calendarOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="calendarOpen" x-transition class="pl-7 space-y-1 pt-1">
                        <a href="{{ route('calendar.index') }}" 
                           class="flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg transition {{ request()->routeIs('calendar.index') ? 'text-indigo-700 font-bold bg-indigo-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('calendar.index') ? 'bg-indigo-600' : 'bg-gray-300' }}"></span>
                            <span>Kalender Armada</span>
                        </a>
                        <a href="{{ route('drivers.calendar') }}" 
                           class="flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg transition {{ request()->routeIs('drivers.calendar') ? 'text-indigo-700 font-bold bg-indigo-50' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('drivers.calendar') ? 'bg-indigo-600' : 'bg-gray-300' }}"></span>
                            <span>Jadwal & Status Driver</span>
                        </a>
                    </div>
                </div>

                {{-- SECTION MASTER DATA --}}
                <div class="space-y-1">
                    @can('manage-vehicles')
                        <span class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-2">Master Data</span>
                        <a href="{{ route('admin_app.vehicles.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('admin_app.vehicles.*') ? 'bg-indigo-50 text-indigo-700 shadow-2xs' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2m14 0a2 2 0 11-4 0 2 2 0 014 0zM7 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Kelola Armada</span>
                        </a>

                        <!-- MENU BARU: KELOLA MASTER CHECKLIST -->
                        <a href="{{ route('master-checklists.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('master-checklists.*') ? 'bg-indigo-50 text-indigo-700 shadow-2xs' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <span>Kelola Master Checklist</span>
                        </a>
                    @else
                        <span class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-2">Katalog</span>
                        <a href="{{ route('vehicles.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('vehicles.*') ? 'bg-indigo-50 text-indigo-700 shadow-2xs' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2m14 0a2 2 0 11-4 0 2 2 0 014 0zM7 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Daftar Armada</span>
                        </a>
                    @endcan

                    @if(auth()->user()->isAdminApp())
                        <a href="{{ route('admin_app.users.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold rounded-xl transition {{ request()->routeIs('admin_app.users.*') ? 'bg-indigo-50 text-indigo-700 shadow-2xs' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span>Kelola Pengguna</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- USER FOOTER DESKTOP --}}
            <div class="p-4 border-t border-gray-100 bg-gray-50/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 truncate">
                        <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-gray-500 truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Log Out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- TOP BAR (DESKTOP & MOBILE HEADER) --}}
    <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-md border-b border-gray-200 sm:ms-64">
        <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Mobile Toggle & Brand -->
            <div class="flex items-center gap-3 sm:hidden">
                <button type="button" @click="mobileSidebarOpen = true" class="p-2 rounded-xl text-gray-600 hover:bg-gray-100 transition" aria-label="Buka Menu Navigasi">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="font-bold text-base text-gray-800">BALMON <span class="text-indigo-600">FLEET</span></span>
            </div>

            <!-- Desktop Sub-header Status -->
            <div class="hidden sm:flex items-center gap-2 text-xs text-gray-500 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Sistem Informasi - Manajemen Peminjaman Kendaraan Dinas</span>
            </div>

            <!-- Profile Menu Dropdown -->
            <div class="flex items-center gap-3">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 p-1.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition text-xs font-semibold text-gray-700">
                            <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <span class="hidden md:inline-block max-w-[120px] truncate">{{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-gray-500 truncate">{{ Auth::user()->email }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

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
        </div>
    </header>
</div>