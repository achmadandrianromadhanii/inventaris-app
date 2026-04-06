<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kategori = $this->route('kategori');

        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori', 'nama')->ignore($kategori?->id),
            ],
            'deskripsi' => [
                'nullable',
                'string',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nama = is_string($this->nama) ? trim($this->nama) : $this->nama;
        $deskripsi = is_string($this->deskripsi) ? trim($this->deskripsi) : $this->deskripsi;

        $this->merge([
            'nama' => $nama,
            'deskripsi' => $deskripsi === '' ? null : $deskripsi,
        ]);
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.string' => 'Nama kategori harus berupa teks.',
            'nama.max' => 'Nama kategori maksimal 100 karakter.',
            'nama.unique' => 'Nama kategori sudah digunakan.',
            'deskripsi.string' => 'Deskripsi kategori harus berupa teks.',
        ];
    }
}
