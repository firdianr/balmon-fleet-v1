<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'end_km'          => ['required', 'integer'],
            'end_fuel_level'  => ['required', 'string'],
            'end_photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'condition_notes' => ['nullable', 'string'],
            'condition_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
