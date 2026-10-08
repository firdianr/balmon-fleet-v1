<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return Auth::check() && $user && $user->isAdmin();
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number,' . $vehicle->id],
            'brand'        => ['required', 'string', 'max:100'],
            'model'        => ['required', 'string', 'max:100'],
            'capacity'     => ['required', 'integer', 'min:1'],
            'fuel_type'    => ['required', 'string', 'max:50'],
            'status'       => ['required', 'in:available,borrowed,maintenance'],
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'notes'        => ['nullable', 'string'],
        ];
    }
}
