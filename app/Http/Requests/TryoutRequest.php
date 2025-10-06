<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TryoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Add proper authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'package_id' => 'required|exists:packages,id',
            'type_tryout' => 'required|in:TIU,TWK,TKP,SKD_FULL',
            'is_certification' => 'boolean',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tryout harus diisi',
            'name.max' => 'Nama tryout maksimal 255 karakter',
            'description.required' => 'Deskripsi tryout harus diisi',
            'package_id.required' => 'Paket harus dipilih',
            'package_id.exists' => 'Paket yang dipilih tidak valid',
            'type_tryout.required' => 'Tipe tryout harus dipilih',
            'type_tryout.in' => 'Tipe tryout tidak valid',
            'start_date.required' => 'Tanggal mulai harus diisi',
            'start_date.after' => 'Tanggal mulai harus setelah hari ini',
            'end_date.required' => 'Tanggal selesai harus diisi',
            'end_date.after' => 'Tanggal selesai harus setelah tanggal mulai'
        ];
    }
}
