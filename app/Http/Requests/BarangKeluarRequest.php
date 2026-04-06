<?php

namespace App\Http\Requests;

use App\Models\Barang;
use App\Models\UnitBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BarangKeluarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'barang_id' => ['required', 'integer', 'exists:barang,id'],
            'alasan_keluar' => ['required', Rule::in(['pindah_lokasi', 'dibuang', 'hibah', 'lainnya'])],

            'unit_barang_ids' => ['nullable', 'array'],
            'unit_barang_ids.*' => ['integer', 'exists:unit_barang,id'],

            'jumlah' => ['nullable', 'integer', 'min:1'],

            'lokasi_tujuan_id' => ['nullable', 'integer', 'exists:lokasi,id'],
            'lokasi_tujuan_manual' => ['nullable', 'string', 'max:100'],

            'sumber_tujuan' => ['nullable', 'string', 'max:200'],
            'status_akhir' => ['nullable', Rule::in(['tersedia', 'rusak', 'keluar'])],

            'tanggal_transaksi' => ['required', 'date'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $lokasiTujuanId = $this->input('lokasi_tujuan_id');

        $unitBarangIds = collect($this->input('unit_barang_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'lokasi_tujuan_id' => ($lokasiTujuanId === 'manual' || $lokasiTujuanId === '') ? null : $lokasiTujuanId,
            'lokasi_tujuan_manual' => is_string($this->lokasi_tujuan_manual) ? trim($this->lokasi_tujuan_manual) : $this->lokasi_tujuan_manual,
            'sumber_tujuan' => is_string($this->sumber_tujuan) ? trim($this->sumber_tujuan) : $this->sumber_tujuan,
            'status_akhir' => is_string($this->status_akhir) ? trim($this->status_akhir) : $this->status_akhir,
            'catatan' => is_string($this->catatan) ? trim($this->catatan) : $this->catatan,
            'unit_barang_ids' => $unitBarangIds,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $barangId = $this->input('barang_id');

            if (!$barangId) {
                return;
            }

            $barang = Barang::query()->find($barangId);

            if (!$barang) {
                return;
            }

            if (!$barang->aktif) {
                $validator->errors()->add('barang_id', 'Barang tidak aktif dan tidak bisa diproses.');
                return;
            }

            $alasan = $this->input('alasan_keluar');

            if ($alasan === 'pindah_lokasi') {
                if (!$this->filled('lokasi_tujuan_id') && !$this->filled('lokasi_tujuan_manual')) {
                    $validator->errors()->add('lokasi_tujuan_id', 'Lokasi tujuan wajib diisi untuk pindah lokasi.');
                }
            }

            if ($alasan === 'hibah' && !$this->filled('sumber_tujuan')) {
                $validator->errors()->add('sumber_tujuan', 'Tujuan penerima hibah wajib diisi.');
            }

            if ($alasan === 'lainnya') {
                if (!$this->filled('status_akhir')) {
                    $validator->errors()->add('status_akhir', 'Status akhir wajib dipilih untuk alasan lainnya.');
                }

                if (!$this->filled('catatan')) {
                    $validator->errors()->add('catatan', 'Keterangan wajib diisi untuk alasan lainnya.');
                }
            }

            if ($barang->tipe === 'aset' && $alasan !== 'pindah_lokasi') {
                $unitIds = collect($this->input('unit_barang_ids', []))
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                if ($unitIds->isEmpty()) {
                    $validator->errors()->add('unit_barang_ids', 'Pilih minimal satu unit aset.');
                    return;
                }

                $unitValid = UnitBarang::query()
                    ->where('barang_id', $barang->id)
                    ->whereIn('id', $unitIds)
                    ->whereIn('status', ['tersedia', 'rusak'])
                    ->count();

                if ($unitValid !== $unitIds->count()) {
                    $validator->errors()->add('unit_barang_ids', 'Ada unit yang tidak valid atau tidak dapat diproses.');
                }
            }

            if ($barang->tipe === 'stok' && $alasan !== 'pindah_lokasi') {
                $jumlah = (int) $this->input('jumlah', 0);

                if ($jumlah < 1) {
                    $validator->errors()->add('jumlah', 'Jumlah barang wajib diisi untuk stok.');
                    return;
                }

                if ($jumlah > (int) $barang->qty_tersedia) {
                    $validator->errors()->add('jumlah', 'Jumlah melebihi stok tersedia.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'barang_id.required' => 'Barang wajib dipilih.',
            'barang_id.exists' => 'Barang tidak valid.',

            'alasan_keluar.required' => 'Alasan keluar wajib dipilih.',
            'alasan_keluar.in' => 'Alasan keluar tidak valid.',

            'unit_barang_ids.array' => 'Format unit tidak valid.',
            'unit_barang_ids.*.exists' => 'Salah satu unit tidak valid.',

            'jumlah.integer' => 'Jumlah harus berupa angka.',
            'jumlah.min' => 'Jumlah minimal 1.',

            'lokasi_tujuan_id.exists' => 'Lokasi tujuan tidak valid.',
            'lokasi_tujuan_manual.max' => 'Lokasi tujuan manual maksimal 100 karakter.',

            'sumber_tujuan.max' => 'Sumber/Tujuan maksimal 200 karakter.',

            'status_akhir.in' => 'Status akhir tidak valid.',

            'tanggal_transaksi.required' => 'Tanggal transaksi wajib diisi.',
            'tanggal_transaksi.date' => 'Tanggal transaksi tidak valid.',
        ];
    }
}
