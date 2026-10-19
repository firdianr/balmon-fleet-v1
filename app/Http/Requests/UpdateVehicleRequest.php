<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return Auth::check() && $user && $user->isAdminApp();
    }

    public function rules(): array
    {
        // Ambil objek vehicle dari route parameter (slug/id)
        $vehicle = $this->route('vehicle');

        return [
            'plate_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate_number')->ignore($vehicle->id),
            ],
            'brand'     => 'required|string|max:100',
            'model'     => 'required|string|max:100',
            'code'      => 'required|string|max:100',
            'type'      => 'required|in:operational,monitoring',
            'capacity'  => 'required|integer|min:1',
            'fuel_type' => 'required|string|max:50',
            'status'    => 'required|in:available,borrowed,maintenance',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'notes'     => 'nullable|string',
        ];
    }

    /**
     * Pesan kesalahan kustom.
     */
    public function messages(): array
    {
        return [
            'plate_number.required' => 'Nomor Plat Kendaraan wajib diisi.',
            'plate_number.unique'   => 'Nomor Plat Kendaraan sudah digunakan armada lain.',
            'brand.required'        => 'Merk kendaraan wajib diisi.',
            'model.required'        => 'Tipe/Model kendaraan wajib diisi.',
            'code.required'         => 'Kode kendaraan wajib diisi.',
            'capacity.required'     => 'Kapasitas penumpang wajib diisi.',
            'fuel_type.required'    => 'Jenis bahan bakar wajib diisi.',
            'photo.image'           => 'File foto harus berupa gambar.',
            'photo.max'             => 'Ukuran foto kendaraan maksimal 2MB.',
        ];
    }
}
