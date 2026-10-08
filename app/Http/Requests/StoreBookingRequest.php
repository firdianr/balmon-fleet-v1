<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'letter_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bookings', 'letter_number')->where(function ($query) {
                    return $query->whereNotIn('status', ['canceled', 'rejected']);
                }),
            ],
            'vehicle_id'    => ['required', 'exists:vehicles,id'],
            'start_date'    => ['required', 'date', 'after_or_equal:now'],
            'end_date'      => ['required', 'date', 'after:start_date'],
            'destination'   => ['required', 'string', 'max:255'],
            'purpose'       => ['required', 'string'],
            'letter_file'   => ['required', 'file', 'mimes:pdf', 'max:5120'], // Max 5MB PDF
            'participants'  => ['nullable', 'array'],
            'participants.*'=> ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'letter_file.required' => 'File Surat Tugas wajib diunggah.',
            'letter_file.mimes'    => 'File Surat Tugas harus berupa file PDF.',
            'letter_file.max'      => 'File Surat Tugas tidak boleh lebih dari 5MB.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required'   => 'Tanggal selesai wajib diisi.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh lewat dari waktu sekarang.',
            'end_date.after'            => 'Tanggal selesai harus lebih lambat dari tanggal mulai.',
            'vehicle_id.exists'         => 'Kendaraan yang dipilih tidak valid.',
            'letter_number.unique' => 'Nomor Surat Tugas ini sudah pernah digunakan pada permohonan lain. Silakan gunakan nomor surat yang baru.',
            'participants.*.required' => 'Nama peserta wajib diisi.',
        ];
    }
}
