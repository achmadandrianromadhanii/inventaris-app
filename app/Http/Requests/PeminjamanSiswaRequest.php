<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeminjamanSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $decodedItems = [];

        if (is_string($this->items_json) && trim($this->items_json) !== '') {
            $parsed = json_decode($this->items_json, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                $decodedItems = collect($parsed)
                    ->filter(fn ($item) => is_array($item))
                    ->map(function (array $item) {
                        return [
                            'barang_id' => isset($item['barang_id']) ? (int) $item['barang_id'] : null,
                            'jumlah' => isset($item['jumlah']) ? (int) $item['jumlah'] : null,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        $this->merge([
            'nama_peminjam' => is_string($this->nama_peminjam) ? trim($this->nama_peminjam) : $this->nama_peminjam,
            'no_hp' => is_string($this->no_hp) ? trim($this->no_hp) : $this->no_hp,
            'mata_pelajaran' => is_string($this->mata_pelajaran) ? trim($this->mata_pelajaran) : $this->mata_pelajaran,
            'catatan' => is_string($this->catatan) ? trim($this->catatan) : $this->catatan,
            'items_json' => is_string($this->items_json) ? trim($this->items_json) : $this->items_json,
            'items' => $decodedItems,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_peminjam' => ['required', 'string', 'max:150'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'jurusan_id' => ['required', 'integer', 'exists:jurusan,id'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'mata_pelajaran' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string'],

            'items_json' => ['required', 'string', 'json'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'integer', 'exists:barang,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_peminjam.required' => 'Nama peminjam wajib diisi.',
            'nama_peminjam.max' => 'Nama peminjam maksimal 150 karakter.',

            'kelas_id.required' => 'Kelas wajib dipilih.',
            'kelas_id.exists' => 'Kelas tidak valid.',

            'jurusan_id.required' => 'Jurusan wajib dipilih.',
            'jurusan_id.exists' => 'Jurusan tidak valid.',

            'no_hp.max' => 'Nomor HP maksimal 20 karakter.',
            'mata_pelajaran.max' => 'Mata pelajaran maksimal 100 karakter.',

            'items_json.required' => 'Daftar barang wajib diisi.',
            'items_json.json' => 'Format daftar barang tidak valid.',

            'items.required' => 'Tambahkan minimal satu barang ke daftar.',
            'items.array' => 'Format daftar barang tidak valid.',
            'items.min' => 'Tambahkan minimal satu barang ke daftar.',

            'items.*.barang_id.required' => 'Barang pada daftar tidak valid.',
            'items.*.barang_id.exists' => 'Barang pada daftar tidak ditemukan.',

            'items.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'items.*.jumlah.integer' => 'Jumlah barang harus berupa angka.',
            'items.*.jumlah.min' => 'Jumlah barang minimal 1.',
        ];
    }
}
