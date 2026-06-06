<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KembalikanSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'peminjaman_id' => $this->filled('peminjaman_id')
                ? (int) $this->input('peminjaman_id')
                : $this->input('peminjaman_id'),

            'detail_id' => $this->filled('detail_id')
                ? (int) $this->input('detail_id')
                : $this->input('detail_id'),

            'kondisi_kembali' => $this->filled('kondisi_kembali')
                ? (int) $this->input('kondisi_kembali')
                : $this->input('kondisi_kembali'),

            'catatan_kembali' => is_string($this->input('catatan_kembali'))
                ? trim($this->input('catatan_kembali'))
                : $this->input('catatan_kembali'),
        ]);
    }

    public function rules(): array
    {
        return [
            'peminjaman_id' => ['required', 'integer'],
            'detail_id' => ['required', 'integer', 'exists:detail_peminjaman,id'],
            'kondisi_kembali' => ['required', 'integer', 'between:0,100'],
            'catatan_kembali' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'peminjaman_id.required' => 'Peminjaman ID wajib diisi.',
            'detail_id.required' => 'Item pengembalian wajib dipilih.',
            'detail_id.integer' => 'Item pengembalian tidak valid.',
            'detail_id.exists' => 'Item pengembalian tidak valid.',

            'kondisi_kembali.required' => 'Kondisi saat kembali wajib diisi.',
            'kondisi_kembali.integer' => 'Kondisi saat kembali harus berupa angka.',
            'kondisi_kembali.between' => 'Kondisi saat kembali harus antara 0 sampai 100.',

            'catatan_kembali.max' => 'Catatan pengembalian maksimal 1000 karakter.',
        ];
    }
}
