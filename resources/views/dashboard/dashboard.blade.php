<x-layout>
    <x-slot:title>{{ $title ?? 'Dashboard Admin' }}</x-slot:title>

    <div class="flex h-screen bg-slate-100 transition-colors">
        <!-- 1. Component Sidebar -->
        <x-side-bar />

        <!-- Area Konten Kanan -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- 2. Component Navbar -->
            <x-nav-bar />

            <!-- 3. Main Content Area -->
            <main class="flex-1 p-6 overflow-y-auto">
                <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
                
                <div class="mt-4 p-6 bg-white rounded-2xl shadow-sm border border-slate-200/60">
                    <p class="text-slate-600">
                        Ini adalah area konten utama ber-tema biru yang sudah terintegrasi penuh dengan dark mode & dropdown Alpine.js.
                    </p>
                </div>
            </main>
        </div>
    </div>
</x-layout>