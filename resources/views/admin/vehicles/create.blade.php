<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Armada Kendaraan Baru') }}
        </h2>
    </x-slot>

    <div class="pt-2 pb-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <ul class="list-disc pl-5 text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.vehicles.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Plat Nomor (Nomor Polisi)</label>
                        <input type="text" name="plate_number" value="{{ old('plate_number') }}" placeholder="Contoh: H 1234 AB" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Merk / Pabrikan</label>
                            <input type="text" name="brand" value="{{ old('brand') }}" placeholder="Contoh: Toyota" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model / Tipe</label>
                            <input type="text" name="model" value="{{ old('model') }}" placeholder="Contoh: Innova Zenix" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kapasitas (Penumpang)</label>
                            <input type="number" name="capacity" value="{{ old('capacity', 7) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bahan Bakar</label>
                            <select name="fuel_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="Bensin">Bensin</option>
                                <option value="Bensin/Hybrid">Bensin / Hybrid</option>
                                <option value="Diesel">Diesel</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status Awal Ketersediaan</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="available">Available (Tersedia)</option>
                            <option value="maintenance">Maintenance (Servis/Bengkel)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Foto Kendaraan (Opsional)
                        </label>

                        <!-- 1. Area Preview Foto (Default: Sembunyi / 'hidden') -->
                        <div id="previewContainer" class="relative mt-2 hidden">
                            <img id="photoPreview" 
                                src="" 
                                alt="Preview Foto Kendaraan" 
                                class="h-40 w-auto object-cover rounded-lg border border-gray-200 shadow-sm">
                            
                            <!-- Tombol Batal / Hapus File Terpilih -->
                            <button type="button" 
                                    id="btnHapusFoto"
                                    title="Batal Pilih Foto"
                                    class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full p-1 shadow-lg hover:bg-red-700 focus:outline-none transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <!-- 2. Input File untuk Upload Foto -->
                        <input type="file" 
                            name="photo" 
                            id="photoInput" 
                            accept="image/*" 
                            class="mt-2 block w-full text-xs text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-xs file:font-semibold
                                    file:bg-violet-50 file:text-violet-700
                                    hover:file:bg-violet-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan / Keterangan Unit (Opsional)</label>
                        <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-4">
                        <a href="{{ route('admin.vehicles.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Simpan Mobil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const photoInput = document.getElementById('photoInput');
        const photoPreview = document.getElementById('photoPreview');
        const previewContainer = document.getElementById('previewContainer');
        const btnHapusFoto = document.getElementById('btnHapusFoto');

        // 1. Tampilkan preview saat user memilih foto
        photoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                }
                
                reader.readAsDataURL(file);
            } else {
                // Jika user membatalkan dialog pemilihan file
                resetPreview();
            }
        });

        // 2. Kosongkan pilihan & sembunyikan preview saat tombol hapus diklik
        btnHapusFoto.addEventListener('click', function() {
            resetPreview();
        });

        // Helper function untuk mereset input & preview
        function resetPreview() {
            photoInput.value = '';
            photoPreview.src = '';
            previewContainer.classList.add('hidden');
        }
    </script>
</x-app-layout>