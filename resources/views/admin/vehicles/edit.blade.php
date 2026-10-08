<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Data Armada Mobil') }}
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

                <form action="{{ route('admin.vehicles.update', $vehicle) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Plat Nomor</label>
                        <input type="text" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Merk</label>
                            <input type="text" name="brand" value="{{ old('brand', $vehicle->brand) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model</label>
                            <input type="text" name="model" value="{{ old('model', $vehicle->model) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kapasitas</label>
                            <input type="number" name="capacity" value="{{ old('capacity', $vehicle->capacity) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bahan Bakar</label>
                            <input type="text" name="fuel_type" value="{{ old('fuel_type', $vehicle->fuel_type) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status Ketersediaan</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="available" {{ $vehicle->status == 'available' ? 'selected' : '' }}>Available (Tersedia)</option>
                            <option value="borrowed" {{ $vehicle->status == 'borrowed' ? 'selected' : '' }}>Borrowed (Sedang Dipinjam)</option>
                            <option value="maintenance" {{ $vehicle->status == 'maintenance' ? 'selected' : '' }}>Maintenance (Servis)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Foto Kendaraan (Biarkan kosong jika tidak diubah)
                        </label>

                        <!-- 1. Area Preview Foto (Menampilkan Foto Lama / Preview Baru) -->
                        <!-- Menggunakan class 'hidden' jika $vehicle->photo kosong -->
                        <div id="previewContainer" class="relative inline-block mt-2 {{ empty($vehicle->photo) ? 'hidden' : '' }}">
                            <img id="photoPreview" 
                                src="{{ !empty($vehicle->photo) ? asset('storage/' . $vehicle->photo) : '' }}" 
                                alt="Foto Kendaraan" 
                                class="h-40 w-auto object-cover rounded-lg border border-gray-200 shadow-sm">
                            
                            <!-- Tombol Hapus Foto -->
                            <button type="button" 
                                    id="btnHapusFoto"
                                    title="Hapus Foto"
                                    class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full p-1 shadow-lg hover:bg-red-700 focus:outline-none transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <!-- 2. Input File untuk Unggah/Ganti Foto -->
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

                        <!-- 3. Hidden Input untuk Tanda Hapus ke Controller / Backend -->
                        <input type="hidden" name="action_hapus_foto" id="actionHapusFoto" value="0">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan Unit</label>
                        <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $vehicle->notes) }}</textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-4">
                        <a href="{{ route('admin.vehicles.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Perbarui Mobil</button>
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
        const actionHapusFoto = document.getElementById('actionHapusFoto');

        // 1. Saat memilih file baru
        photoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                    // Batal tandai hapus karena user mengunggah foto baru
                    actionHapusFoto.value = "0";
                }
                
                reader.readAsDataURL(file);
            }
        });

        // 2. Saat tombol hapus diklik
        btnHapusFoto.addEventListener('click', function() {
            // Sembunyikan container preview
            previewContainer.classList.add('hidden');
            photoPreview.src = '';
            
            // Kosongkan input file jika user sempat memilih file baru
            photoInput.value = '';
            
            // Set nilai ke '1' untuk memberi tahu backend agar menghapus foto lama di database/storage
            actionHapusFoto.value = "1";
        });
    </script>
</x-app-layout>