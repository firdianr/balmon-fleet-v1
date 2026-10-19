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
            'vehicle_type'         => ['required', 'in:operational,monitoring'],
            'vehicle_id'           => ['required_if:vehicle_type,monitoring', 'nullable', 'exists:vehicles,id'],
            'vehicle_requirements' => ['nullable', 'string'],
            'start_date'           => ['required', 'date', 'after_or_equal:today'],
            'end_date'             => ['required', 'date', 'after_or_equal:start_date'],
            'destination'          => ['required', 'string', 'max:255'],
            'purpose'              => ['required', 'string'],
            'participants'         => ['nullable', 'array'],
            'participants.*'       => ['nullable', 'exists:users,id'],
            'letter_file'          => ['required_without:letter_file_temp', 'nullable', 'file', 'mimes:pdf', 'max:5120'],
            // 'letter_file_temp'     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'letter_number.required'        => 'Nomor Surat Tugas wajib diisi.',
            'vehicle_type.required'         => 'Jenis kendaraan dinas wajib dipilih.',
            'vehicle_type.in'               => 'Jenis kendaraan dinas tidak valid.',
            'vehicle_id.required_if'        => 'Untuk armada monitoring, Anda wajib memilih unit kendaraan.',
            'vehicle_id.exists'             => 'Kendaraan yang dipilih tidak valid.',
            
            'start_date.required'           => 'Tanggal mulai perjalanan dinas wajib diisi.',
            'start_date.date'               => 'Format tanggal mulai tidak valid.',
            'start_date.after_or_equal'     => 'Tanggal mulai tidak boleh lewat dari waktu sekarang.',
            
            'end_date.required'             => 'Tanggal selesai perjalanan dinas wajib diisi.',
            'end_date.date'                 => 'Format tanggal selesai tidak valid.',
            'end_date.after_or_equal'       => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
            
            'destination.required'          => 'Tujuan perjalanan dinas wajib diisi.',
            'purpose.required'              => 'Maksud / keperluan perjalanan dinas wajib diisi.',
            
            // Pesan Kustom Lengkap File Surat Tugas
            'letter_file.required_without'  => 'Berkas Surat Tugas (PDF) wajib diunggah.',
            'letter_file.file'              => 'Berkas Surat Tugas harus berupa dokumen file.',
            'letter_file.mimes'             => 'File Surat Tugas harus berupa file berformat PDF.',
            'letter_file.max'               => 'Ukuran file Surat Tugas tidak boleh lebih dari 5MB.',
        ];
    }
}
