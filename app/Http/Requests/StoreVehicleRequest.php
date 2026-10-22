<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-vehicles');
    }

    public function rules(): array
    {
        return [
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'brand'        => 'required|string|max:100',
            'model'        => 'required|string|max:100',
            'code'         => 'required|string|max:100',
            'type'         => 'required|in:operational,monitoring',
            'capacity'     => 'required|integer|min:1',
            'fuel_type'    => 'required|string|max:50',
            'status'       => 'required|in:available,borrowed,maintenance',
            'photo'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'notes'        => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.required' => 'Nomor Plat Kendaraan wajib diisi.',
            'plate_number.unique'   => 'Nomor Plat Kendaraan sudah terdaftar pada armada lain.',
            'brand.required'        => 'Merk kendaraan wajib diisi.',
            'model.required'        => 'Tipe/Model kendaraan wajib diisi.',
            'code.required'         => 'Kode kendaraan wajib diisi.',
            'code.unique'           => 'Kode kendaraan sudah digunakan armada lain.',
            'type.required'         => 'Tipe kendaraan wajib diisi.',
            'type.in'               => 'Tipe kendaraan harus berupa "operational" atau "monitoring".',
            'capacity.required'     => 'Kapasitas penumpang wajib diisi.',
            'fuel_type.required'    => 'Jenis bahan bakar wajib diisi.',
            'photo.image'           => 'File foto harus berupa gambar.',
            'photo.max'             => 'Ukuran foto kendaraan maksimal 8MB.',
        ];
    }
}
