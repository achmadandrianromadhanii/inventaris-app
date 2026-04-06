<?php

namespace App\Http\Controllers;

use App\Http\Requests\KembalikanSiswaRequest;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PeminjamanController extends Controller
{
    public function __construct(
        protected PeminjamanService $peminjamanService
    ) {}

    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'aktif');
        $tab = in_array($tab, ['aktif', 'selesai', 'semua'], true) ? $tab : 'aktif';

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'dari' => (string) $request->query('dari', ''),
            'sampai' => (string) $request->query('sampai', ''),
        ];

        $query = Peminjaman::query()
            ->select([
                'id',
                'kode_pinjam',
                'nama_peminjam',
                'kelas_id',
                'jurusan_id',
                'tanggal_pinjam',
                'status',
            ])
            ->with([
                'kelas:id,nama',
                'jurusan:id,nama',
            ])
            ->withCount('detailPeminjaman');

        if ($tab !== 'semua') {
            $query->where('status', $tab);
        }

        if ($filters['q'] !== '') {
            $query->where(function ($subQuery) use ($filters) {
                $subQuery
                    ->where('nama_peminjam', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('kode_pinjam', 'like', '%' . $filters['q'] . '%');
            });
        }

        if ($filters['dari'] !== '') {
            $query->whereDate('tanggal_pinjam', '>=', $filters['dari']);
        }

        if ($filters['sampai'] !== '') {
            $query->whereDate('tanggal_pinjam', '<=', $filters['sampai']);
        }

        $peminjaman = $query
            ->latest('tanggal_pinjam')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $countRow = Peminjaman::query()
            ->selectRaw("
                COUNT(*) as semua,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
            ")
            ->first();

        $counts = [
            'aktif' => (int) ($countRow->aktif ?? 0),
            'selesai' => (int) ($countRow->selesai ?? 0),
            'semua' => (int) ($countRow->semua ?? 0),
        ];

        return view('peminjaman.index', [
            'peminjaman' => $peminjaman,
            'tab' => $tab,
            'counts' => $counts,
            'filters' => $filters,
        ]);
    }

    public function show(Peminjaman $peminjaman): View
    {
        $peminjaman->loadMissing([
            'kelas:id,nama',
            'jurusan:id,nama',
            'pengguna:id,nama',
            'detailPeminjaman' => fn($query) => $query
                ->select([
                    'id',
                    'peminjaman_id',
                    'barang_id',
                    'unit_barang_id',
                    'jumlah',
                    'status_item',
                    'waktu_kembali',
                    'kondisi_kembali',
                    'catatan_kembali',
                ])
                ->orderBy('id'),
            'detailPeminjaman.barang:id,nama,tipe,kondisi_stok',
            'detailPeminjaman.unitBarang:id,nomor_unit,kondisi,status',
        ]);

        return view('peminjaman.show', [
            'peminjaman' => $peminjaman,
        ]);
    }

    public function kembalikan(KembalikanSiswaRequest $request, Peminjaman $peminjaman): RedirectResponse
    {
        $data = $request->validated();

        $detail = $peminjaman->detailPeminjaman()
            ->whereKey($data['detail_id'])
            ->first();

        if (! $detail) {
            throw ValidationException::withMessages([
                'detail_id' => 'Item tidak sesuai dengan data peminjaman.',
            ]);
        }

        if ($detail->status_item !== 'dipinjam') {
            throw ValidationException::withMessages([
                'detail_id' => 'Item ini sudah dikembalikan.',
            ]);
        }

        $this->peminjamanService->prosesPengembalianDetail(
            detail: $detail,
            kondisiKembali: (int) $data['kondisi_kembali'],
            catatanKembali: $data['catatan_kembali'] ?? null,
        );

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('sukses', 'Item peminjaman berhasil dikembalikan.');
    }
}
