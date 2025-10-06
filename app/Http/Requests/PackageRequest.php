<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageRequest extends FormRequest
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
            'class_id' => 'required|exists:classes,id'
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
            'name.required' => 'Nama paket harus diisi',
            'name.max' => 'Nama paket maksimal 255 karakter',
            'description.required' => 'Deskripsi paket harus diisi',
            'class_id.required' => 'Kelas harus dipilih',
            'class_id.exists' => 'Kelas yang dipilih tidak valid'
        ];
    }
}
