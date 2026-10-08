<aside class="w-64 bg-slate-900 text-white flex flex-col border-r border-slate-800">
    <!-- Logo -->
    <div class="p-4 border-b border-slate-800 flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-linear-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-bold text-white shadow-md shadow-blue-500/20">
            A
        </div>
        <span class="text-xl font-extrabold tracking-tight bg-linear-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
            Admin Pro
        </span>
    </div>

    <!-- Search Bar -->
    <div class="p-4">
        <div class="relative">
            <input type="text" class="w-full bg-slate-800 text-white rounded-lg pl-10 pr-4 py-2 text-sm border border-slate-700/50 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-slate-400" placeholder="Search...">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Nav Links -->
    <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
        <!-- Dashboard -->
        <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg bg-blue-600 text-white shadow-lg shadow-blue-600/30 transition-all">
            <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <!-- Analytics Dropdown (Alpine.js) -->
        <div x-data="{ open: true }" class="space-y-1">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                <div class="flex items-center">
                    <svg class="h-5 w-5 mr-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Analytics
                </div>
                <svg class="h-4 w-4 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div x-show="open" x-collapse class="pl-11 space-y-1">
                <a href="#" class="block px-4 py-2 text-sm text-slate-400 rounded-md hover:text-white hover:bg-slate-800/60">Overview</a>
                <a href="#" class="block px-4 py-2 text-sm text-slate-400 rounded-md hover:text-white hover:bg-slate-800/60">Reports</a>
                <a href="#" class="block px-4 py-2 text-sm text-slate-400 rounded-md hover:text-white hover:bg-slate-800/60">Statistics</a>
            </div>
        </div>

        <!-- Team Dropdown -->
        <div x-data="{ open: false }" class="space-y-1">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                <div class="flex items-center">
                    <svg class="h-5 w-5 mr-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Team
                </div>
                <svg class="h-4 w-4 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div x-show="open" x-collapse class="pl-11 space-y-1" style="display: none;">
                <a href="#" class="block px-4 py-2 text-sm text-slate-400 rounded-md hover:text-white hover:bg-slate-800/60">Members</a>
                <a href="#" class="block px-4 py-2 text-sm text-slate-400 rounded-md hover:text-white hover:bg-slate-800/60">Calendar</a>
                <a href="#" class="block px-4 py-2 text-sm text-slate-400 rounded-md hover:text-white hover:bg-slate-800/60">Settings</a>
            </div>
        </div>

        <!-- Links -->
        <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
            <svg class="h-5 w-5 mr-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            Projects
        </a>
        <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
            <svg class="h-5 w-5 mr-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Calendar
        </a>
        <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
            <svg class="h-5 w-5 mr-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Documents
        </a>
    </nav>
</aside>