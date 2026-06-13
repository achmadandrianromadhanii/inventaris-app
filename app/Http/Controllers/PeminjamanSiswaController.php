<?php

namespace App\Http\Controllers;

use App\Helpers\KondisiHelper;
use App\Http\Requests\KembalikanSiswaRequest;
use App\Http\Requests\PeminjamanSiswaRequest;
use App\Models\Barang;
use App\Models\DetailPeminjaman;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeminjamanSiswaController extends Controller
{
    public function __construct(
        protected PeminjamanService $peminjamanService
    ) {}

    public function index(): View
    {
        $peminjamanAktif = Peminjaman::query()
            ->select([
                'id',
                'nama_peminjam',
                'kelas_id',
                'jurusan_id',
                'tanggal_pinjam',
                'status',
            ])
            ->with([
                'kelas:id,nama',
                'jurusan:id,nama',
                'detailPeminjaman' => fn ($query) => $query
                    ->select([
                        'id',
                        'peminjaman_id',
                        'barang_id',
                        'unit_barang_id',
                        'jumlah',
                        'status_item',
                        'kondisi_awal',
                        'kondisi_kembali',
                        'waktu_kembali',
                        'catatan_kembali',
                    ])
                    ->orderBy('id'),
                'detailPeminjaman.barang:id,nama,tipe',
                'detailPeminjaman.unitBarang:id,nomor_unit,kondisi',
            ])
            ->where('status', 'aktif')
            ->orderByDesc('id')
            ->get();

        $laptopTersedia = Barang::query()
            ->select(['id', 'nama', 'tipe', 'kategori_id', 'merek_id'])
            ->with(['merek:id,nama'])
            ->withCount([
                'unitBarang as unit_tersedia_count' => fn (Builder $sub) => $sub->where('status', 'tersedia'),
            ])
            ->withMax([
                'unitBarang as kondisi_terbaik' => fn (Builder $sub) => $sub->where('status', 'tersedia'),
            ], 'kondisi')
            ->where('aktif', true)
            ->where('tipe', 'aset')
            // [UPDATE] Filter ketat HANYA dari kategori 'Laptop', tidak mengambil kategori lain.
            // Jika suatu barang adalah laptop tapi kategorinya bukan 'Laptop', maka tidak akan muncul.
            ->whereHas('kategori', fn ($q) => $q->where('nama', 'Laptop'))
            ->whereHas('unitBarang', fn ($q) => $q->where('status', 'tersedia'))
            ->orderBy('nama')
            ->get()
            ->map(function (Barang $barang): array {
                /** @var int|null $kondisiTerbaik */
                $kondisiTerbaik = $barang->getAttribute('kondisi_terbaik');
                /** @var int|null $unitTersediaCount */
                $unitTersediaCount = $barang->getAttribute('unit_tersedia_count');
                /** @var string $namaMerek */
                $namaMerek = $barang->merek ? $barang->merek->nama : 'Lainnya';

                return [
                    'id' => $barang->id,
                    'nama' => $barang->nama,
                    'tipe' => $barang->tipe,
                    'merek' => $namaMerek,
                    'kondisi' => (int) ($kondisiTerbaik ?? 100),
                    'label_kondisi' => KondisiHelper::label((int) ($kondisiTerbaik ?? 100)),
                    'unit_tersedia' => (int) $unitTersediaCount,
                    'qty_tersedia' => null,
                ];
            })
            ->values()
            ->all();

        return view('siswa.pinjam', [
            'kelas' => Kelas::query()
                ->select(['id', 'nama'])
                ->orderBy('id')
                ->get(),

            'jurusan' => Jurusan::query()
                ->select(['id', 'nama'])
                ->orderBy('nama')
                ->get(),

            'peminjamanAktif' => $peminjamanAktif,
            'laptopTersedia' => $laptopTersedia,
        ]);
    }

    public function cariBarang(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = Barang::query()
            ->select([
                'id',
                'nama',
                'tipe',
                'kategori_id',
                'merek_id',
                'qty_tersedia',
                'kondisi_stok',
                'aktif',
            ])
            ->with([
                'kategori:id,nama',
                'merek:id,nama',
            ])
            ->withCount([
                'unitBarang as unit_tersedia_count' => fn (Builder $sub) => $sub->where('status', 'tersedia'),
            ])
            ->withMax([
                'unitBarang as kondisi_terbaik' => fn (Builder $sub) => $sub->where('status', 'tersedia'),
            ], 'kondisi')
            ->where('aktif', true)
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $aset) {
                        $aset->where('tipe', 'aset')
                            ->whereHas('unitBarang', fn (Builder $sub) => $sub->where('status', 'tersedia'));
                    })
                    ->orWhere(function (Builder $stok) {
                        $stok->where('tipe', 'stok')
                            ->where('qty_tersedia', '>', 0);
                    });
            })
            ->where(function (Builder $query) use ($q) {
                // [OPTIMASI DB VERCEL]: Deteksi otomatis driver DB agar pencarian
                // kebal huruf besar/kecil di PostgreSQL (Vercel/Neon).
                $likeOperator = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

                $query
                    ->where('nama', $likeOperator, '%'.$q.'%')
                    ->orWhereHas('kategori', fn (Builder $sub) => $sub->where('nama', $likeOperator, '%'.$q.'%'))
                    ->orWhereHas('merek', fn (Builder $sub) => $sub->where('nama', $likeOperator, '%'.$q.'%'));
            })
            ->orderBy('nama')
            ->limit(10)
            ->get()
            ->map(function (Barang $barang): array {
                /** @var int|null $kondisiTerbaik */
                $kondisiTerbaik = $barang->getAttribute('kondisi_terbaik');
                /** @var int|null $unitTersediaCount */
                $unitTersediaCount = $barang->getAttribute('unit_tersedia_count');
                /** @var string|null $namaKategori */
                $namaKategori = $barang->kategori ? $barang->kategori->nama : null;

                $kondisi = $barang->tipe === 'aset'
                    ? (int) ($kondisiTerbaik ?? 100)
                    : (int) ($barang->kondisi_stok ?? 100);

                return [
                    'id' => $barang->id,
                    'nama' => $barang->nama,
                    'tipe' => $barang->tipe,
                    'kategori' => $namaKategori,
                    'merek' => $barang->label_merek,
                    'kondisi' => $kondisi,
                    'label_kondisi' => KondisiHelper::label($kondisi),
                    'unit_tersedia' => $barang->tipe === 'aset'
                        ? (int) $unitTersediaCount
                        : null,
                    'qty_tersedia' => $barang->tipe === 'stok'
                        ? (int) $barang->qty_tersedia
                        : null,
                ];
            })
            ->values()
            ->all();

        return response()->json($items);
    }

    public function ajukan(PeminjamanSiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $hasil = $this->peminjamanService->buatPeminjamanPublik(
            data: $data,
            items: $data['items'] ?? [],
        );

        return redirect()
            ->route('siswa.pinjam')
            ->with('tab_aktif', 'peminjaman')
            ->with('sukses', 'Peminjaman berhasil diajukan.')
            ->with('peminjaman_sukses', [
                'peminjaman_id' => $hasil['peminjaman_id'],
                'items' => $hasil['items'],
            ]);
    }

    public function ajukanApi(PeminjamanSiswaRequest $request): JsonResponse
    {
        $data = $request->validated();

        $hasil = $this->peminjamanService->buatPeminjamanPublik(
            data: $data,
            items: $data['items'] ?? [],
        );

        $peminjamanBaru = Peminjaman::query()
            ->with($this->hasilKodeRelations())
            ->find($hasil['peminjaman_id']);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Pengajuan peminjaman anda telah masuk',
            'data' => $this->formatHasilKode($peminjamanBaru),
        ]);
    }

    public function kembalikan(KembalikanSiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $peminjaman = Peminjaman::query()
            ->with($this->hasilKodeRelations())
            ->where('id', $data['peminjaman_id'] ?? null)
            ->first();

        if (! $peminjaman || $peminjaman->status === 'selesai') {
            return redirect()
                ->route('siswa.pinjam')
                ->with('tab_aktif', 'pengembalian')
                ->with('galat', 'Peminjaman tidak valid atau sudah selesai.');
        }

        /** @var \App\Models\DetailPeminjaman|null $detail */
        $detail = $peminjaman->detailPeminjaman()
            ->whereKey($data['detail_id'])
            ->first();

        if (! $detail || $detail->status_item !== 'dipinjam') {
            return redirect()
                ->route('siswa.pinjam')
                ->withInput()
                ->with('tab_aktif', 'pengembalian')
                ->with('hasil_kode', $this->formatHasilKode($peminjaman))
                ->with('galat', 'Item tidak valid atau sudah dikembalikan.');
        }

        $this->peminjamanService->prosesPengembalianDetail(
            detail: $detail,
            kondisiKembali: (int) $data['kondisi_kembali'],
            catatanKembali: $data['catatan_kembali'] ?? null,
        );

        $peminjaman->refresh();

        return redirect()
            ->route('siswa.pinjam')
            ->with('tab_aktif', 'pengembalian')
            ->with('sukses', 'Item berhasil dikembalikan.')
            ->with('hasil_kode', $this->formatHasilKode($peminjaman));
    }

    protected function hasilKodeRelations(): array
    {
        return [
            'kelas:id,nama',
            'jurusan:id,nama',
            'detailPeminjaman' => fn ($query) => $query
                ->select([
                    'id',
                    'peminjaman_id',
                    'barang_id',
                    'unit_barang_id',
                    'jumlah',
                    'status_item',
                    'kondisi_kembali',
                    'waktu_kembali',
                    'catatan_kembali',
                ])
                ->orderBy('id'),
            'detailPeminjaman.barang:id,nama,tipe',
            'detailPeminjaman.unitBarang:id,nomor_unit,kondisi',
        ];
    }

    protected function formatHasilKode(Peminjaman $peminjaman): array
    {
        /** @var string|null $namaKelas */
        $namaKelas = $peminjaman->kelas ? $peminjaman->kelas->nama : null;
        /** @var string|null $namaJurusan */
        $namaJurusan = $peminjaman->jurusan ? $peminjaman->jurusan->nama : null;

        return [
            'id' => $peminjaman->id,
            'nama_peminjam' => $peminjaman->nama_peminjam,
            'kelas' => $namaKelas,
            'jurusan' => $namaJurusan,
            'tanggal_pinjam' => Carbon::parse($peminjaman->tanggal_pinjam)->format('d M Y'),
            'status' => $peminjaman->status,
            'items' => $peminjaman->detailPeminjaman
                ->map(function (DetailPeminjaman $detail): array {
                    /** @var string $namaBarang */
                    $namaBarang = $detail->barang ? $detail->barang->nama : '-';
                    /** @var string $tipeBarang */
                    $tipeBarang = $detail->barang ? $detail->barang->tipe : 'aset';
                    /** @var string $nomorUnit */
                    $nomorUnit = $detail->unitBarang ? $detail->unitBarang->nomor_unit : ('Qty '.$detail->jumlah);
                    /** @var int|null $kondisiAwal */
                    $kondisiAwal = $detail->kondisi_awal ?? ($detail->unitBarang ? $detail->unitBarang->kondisi : null);

                    return [
                        'detail_id' => $detail->id,
                        'barang' => $namaBarang,
                        'tipe' => $tipeBarang,
                        'unit_qty' => $nomorUnit,
                        'status_item' => $detail->status_item,
                        'kondisi_awal' => $kondisiAwal,
                        'kondisi_kembali' => $detail->kondisi_kembali,
                        'waktu_kembali' => $detail->waktu_kembali
                            ? Carbon::parse($detail->waktu_kembali)->format('d M Y H:i')
                            : null,
                        'catatan_kembali' => $detail->catatan_kembali,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function kembalikanApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'peminjaman_id' => ['required', 'integer'],
            'detail_id' => ['required', 'integer', 'exists:detail_peminjaman,id'],
            'kondisi_kembali' => ['required', 'integer', 'between:0,100'],
            'catatan_kembali' => ['nullable', 'string', 'max:1000'],
        ], [
            'peminjaman_id.required' => 'Peminjaman ID wajib diisi.',
            'detail_id.required' => 'Item pengembalian wajib dipilih.',
            'detail_id.exists' => 'Item pengembalian tidak valid.',
            'kondisi_kembali.required' => 'Kondisi saat kembali wajib diisi.',
            'kondisi_kembali.between' => 'Kondisi saat kembali harus antara 0 sampai 100.',
        ]);

        $peminjaman = Peminjaman::query()
            ->with($this->hasilKodeRelations())
            ->where('id', $validated['peminjaman_id'])
            ->first();

        if (! $peminjaman || $peminjaman->status === 'selesai') {
            return response()->json([
                'sukses' => false,
                'pesan' => 'Peminjaman tidak ditemukan atau sudah selesai.',
            ], 422);
        }

        /** @var \App\Models\DetailPeminjaman|null $detail */
        $detail = $peminjaman->detailPeminjaman()
            ->whereKey($validated['detail_id'])
            ->first();

        if (! $detail || $detail->status_item !== 'dipinjam') {
            return response()->json([
                'sukses' => false,
                'pesan' => 'Item tidak valid atau sudah dikembalikan.',
            ], 422);
        }

        $this->peminjamanService->prosesPengembalianDetail(
            detail: $detail,
            kondisiKembali: (int) $validated['kondisi_kembali'],
            catatanKembali: $validated['catatan_kembali'] ?? null,
        );

        $peminjaman->refresh();

        return response()->json([
            'sukses' => true,
            'pesan' => 'Item berhasil dikembalikan.',
            'data' => $this->formatHasilKode($peminjaman),
        ]);
    }
}
