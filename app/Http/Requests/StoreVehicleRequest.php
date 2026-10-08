<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return Auth::check() && $user && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'brand'        => ['required', 'string', 'max:100'],
            'model'        => ['required', 'string', 'max:100'],
            'capacity'     => ['required', 'integer', 'min:1'],
            'fuel_type'    => ['required', 'string', 'max:50'],
            'status'       => ['required', 'in:available,borrowed,maintenance'],
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'notes'        => ['nullable', 'string'],
        ];
    }

    public function messages(): array 
    {
        return [
            'plate_number.unique' => 'Nomor plat kendaraan ini sudah terdaftar. Silakan gunakan nomor plat yang berbeda.',
            'capacity.min' => 'Kapasitas kendaraan harus minimal 1.',
            'status.in' => 'Status kendaraan tidak valid. Pilih salah satu dari: available, borrowed, maintenance.',
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'File foto harus berformat JPG, JPEG, atau PNG.',
            'photo.max' => 'Ukuran file foto tidak boleh lebih dari 2MB.',
        ];
    }
}
