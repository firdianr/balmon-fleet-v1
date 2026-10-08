<header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between transition-colors">
    <!-- Judul / Breadcrumb -->
    <div class="flex items-center gap-2">
        <h2 class="text-lg font-bold text-slate-800">Dashboard Overview</h2>
    </div>

    <!-- Actions Kanan -->
    <div class="flex items-center gap-4">
        <!-- Toggle Dark Mode Static/Dynamic -->
        <x-theme-toggle class="static! top-0! right-0!" />

        <!-- User Profile Dropdown -->
        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
            <button @click="open = !open" type="button" class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150" alt="Avatar" class="w-9 h-9 rounded-full object-cover border border-blue-500/30">
                <div class="hidden sm:block text-left leading-tight">
                    <p class="text-sm font-semibold text-slate-800">Sarah Johnson</p>
                    <p class="text-xs text-slate-500">Administrator</p>
                </div>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div 
                x-show="open" 
                x-cloak 
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50"
            >
                <div class="px-4 py-2 border-b border-slate-100 sm:hidden">
                    <p class="text-sm font-semibold text-slate-800">Sarah Johnson</p>
                    <p class="text-xs text-slate-500">Administrator</p>
                </div>
                
                <a href="#" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600">
                    My Profile
                </a>
                <a href="#" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-600">
                    Account Settings
                </a>
                
                <div class="border-t border-slate-100 my-1"></div>

                <!-- Form Logout -->
                <form method="POST" action="#">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 cursor-pointer">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>