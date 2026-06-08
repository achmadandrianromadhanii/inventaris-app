<?php

namespace App\Http\Controllers;

use App\Helpers\KondisiHelper;
use App\Http\Requests\BarangMasukRequest;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\UnitBarang;
use App\Services\UnitBarangService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BarangController extends Controller
{
    public function __construct(
        protected UnitBarangService $unitBarangService
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'kategori_id' => $request->filled('kategori_id') ? (int) $request->input('kategori_id') : null,
            'tipe' => in_array($request->input('tipe'), ['aset', 'stok'], true) ? $request->input('tipe') : null,
            'status' => in_array($request->input('status'), ['tersedia', 'dipinjam', 'rusak', 'keluar'], true)
                ? $request->input('status')
                : null,
            'kondisi' => in_array($request->input('kondisi'), ['baik', 'lumayan', 'rusak', 'rusak_parah'], true)
                ? $request->input('kondisi')
                : null,
        ];

        $query = Barang::query()
            ->where('aktif', true)
            ->with([
                'kategori:id,nama',
                'merek:id,nama',
                'lokasi:id,nama',
            ])
            ->withCount([
                'unitBarang',
                'unitBarang as unit_tersedia_count' => fn (Builder $q) => $q->where('status', 'tersedia'),
                'unitBarang as unit_dipinjam_count' => fn (Builder $q) => $q->where('status', 'dipinjam'),
                'unitBarang as unit_rusak_count' => fn (Builder $q) => $q->where('status', 'rusak'),
                'unitBarang as unit_keluar_count' => fn (Builder $q) => $q->where('status', 'keluar'),
            ])
            ->withAvg('unitBarang as rata_kondisi_unit', 'kondisi')
            ->latest('id');

        if ($filters['q'] !== '') {
            $query->where(function (Builder $q) use ($filters) {
                // [OPTIMASI DB VERCEL]: Jika driver database adalah PostgreSQL, gunakan 'ilike' agar pencarian tidak sensitif terhadap huruf besar/kecil.
                // Jika di lokal MySQL, tetap gunakan 'like'. Ini membuat kode aman 100% di semua lingkungan.
                $likeOperator = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

                $q->where('nama', $likeOperator, '%'.$filters['q'].'%')
                    ->orWhereHas('kategori', fn (Builder $sub) => $sub->where('nama', $likeOperator, '%'.$filters['q'].'%'))
                    ->orWhereHas('merek', fn (Builder $sub) => $sub->where('nama', $likeOperator, '%'.$filters['q'].'%'))
                    ->orWhereHas('lokasi', fn (Builder $sub) => $sub->where('nama', $likeOperator, '%'.$filters['q'].'%'));
            });
        }

        if ($filters['kategori_id']) {
            $query->where('kategori_id', $filters['kategori_id']);
        }

        if ($filters['tipe']) {
            $query->where('tipe', $filters['tipe']);
        }

        if ($filters['status']) {
            $status = $filters['status'];

            $query->where(function (Builder $q) use ($status) {
                $q->where(function (Builder $aset) use ($status) {
                    $aset->where('tipe', 'aset')
                        ->whereHas('unitBarang', fn (Builder $sub) => $sub->where('status', $status));
                })->orWhere(function (Builder $stok) use ($status) {
                    $stok->where('tipe', 'stok');

                    match ($status) {
                        'tersedia' => $stok->where('qty_tersedia', '>', 0),
                        'dipinjam' => $stok->where('qty_dipinjam', '>', 0),
                        'rusak' => $stok->where('qty_rusak', '>', 0),
                        'keluar' => $stok->where(function (Builder $sub) {
                            $sub->where('qty_keluar', '>', 0)
                                ->orWhere('aktif', false);
                        }),
                    };
                });
            });
        }

        if ($filters['kondisi']) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where(function (Builder $aset) use ($filters) {
                    $aset->where('tipe', 'aset')
                        ->whereIn('id', function ($sub) use ($filters) {
                            $sub->select('barang_id')
                                ->from('unit_barang')
                                ->groupBy('barang_id');

                            match ($filters['kondisi']) {
                                'baik' => $sub->havingRaw('AVG(kondisi) >= 80'),
                                'lumayan' => $sub->havingRaw('AVG(kondisi) BETWEEN 60 AND 79'),
                                'rusak' => $sub->havingRaw('AVG(kondisi) BETWEEN 35 AND 59'),
                                'rusak_parah' => $sub->havingRaw('AVG(kondisi) <= 34'),
                            };
                        });
                })->orWhere(function (Builder $stok) use ($filters) {
                    $stok->where('tipe', 'stok');

                    match ($filters['kondisi']) {
                        'baik' => $stok->where('kondisi_stok', '>=', 80),
                        'lumayan' => $stok->whereBetween('kondisi_stok', [60, 79]),
                        'rusak' => $stok->whereBetween('kondisi_stok', [35, 59]),
                        'rusak_parah' => $stok->where('kondisi_stok', '<=', 34),
                    };
                });
            });
        }

        $barang = $query
            ->paginate(10)
            ->withQueryString();

        $kategori = Kategori::query()
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return view('barang.index', [
            'barang' => $barang,
            'kategori' => $kategori,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('barang.tambah', [
            'kategori' => \App\Models\Kategori::getCachedDropdown(),
            'merek' => \App\Models\Merek::getCachedDropdown(),
            'lokasi' => \App\Models\Lokasi::getCachedDropdown(),
        ]);
    }

    public function store(BarangMasukRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $barang = Barang::create([
                'nama' => $data['nama'],
                'kategori_id' => $data['kategori_id'],
                'merek_id' => $data['merek_id'] ?? null,
                'lokasi_id' => $data['lokasi_id'] ?? null,
                'tipe' => $data['tipe'],
                'spesifikasi' => $data['spesifikasi'] ?? null,
                'tahun_pengadaan' => $data['tahun_pengadaan'] ?? null,
                'qty_total' => $data['tipe'] === 'stok' ? (int) $data['qty_total'] : 0,
                'qty_tersedia' => $data['tipe'] === 'stok' ? (int) $data['qty_total'] : 0,
                'qty_dipinjam' => 0,
                'qty_rusak' => 0,
                'qty_keluar' => 0,
                'kondisi_stok' => $data['tipe'] === 'stok' ? (int) $data['kondisi_awal'] : 100,
                'aktif' => true,
                'catatan' => $data['catatan'] ?? null,
            ]);

            if ($barang->tipe === 'aset') {
                $this->unitBarangService->buatUnit(
                    barang: $barang,
                    jumlah: (int) $data['jumlah_unit'],
                    kondisiAwal: (int) $data['kondisi_awal'],
                    serials: $data['unit_serials'] ?? null,
                    kondisis: $data['unit_kondisis'] ?? null
                );
            }
        });

        return redirect()
            ->route('barang.index')
            ->with('sukses', 'Barang berhasil ditambahkan.');
    }

    public function show(Barang $barang): View
    {
        $relations = [
            'kategori:id,nama',
            'merek:id,nama',
            'lokasi:id,nama',
        ];

        if ($barang->tipe === 'aset') {
            $relations['unitBarang'] = fn ($q) => $q->orderBy('nomor_unit');
        }

        $barang->load($relations);

        $barang->loadCount([
            'unitBarang',
            'unitBarang as unit_tersedia_count' => fn (Builder $q) => $q->where('status', 'tersedia'),
            'unitBarang as unit_dipinjam_count' => fn (Builder $q) => $q->where('status', 'dipinjam'),
            'unitBarang as unit_rusak_count' => fn (Builder $q) => $q->where('status', 'rusak'),
            'unitBarang as unit_keluar_count' => fn (Builder $q) => $q->where('status', 'keluar'),
        ]);

        $riwayatTransaksi = $barang->transaksi()
            ->with(['pengguna:id,nama', 'unitBarang:id,nomor_unit'])
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->limit(10)
            ->get();

        $riwayatPeminjaman = $barang->detailPeminjaman()
            ->with([
                'peminjaman:id,nama_peminjam,tanggal_pinjam,status',
                'unitBarang:id,nomor_unit',
            ])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('barang.show', [
            'barang' => $barang,
            'riwayatTransaksi' => $riwayatTransaksi,
            'riwayatPeminjaman' => $riwayatPeminjaman,
        ]);
    }

    public function edit(Barang $barang): View
    {
        $barang->load([
            'kategori:id,nama',
            'merek:id,nama',
            'lokasi:id,nama',
        ]);

        return view('barang.edit', [
            'barang' => $barang,
            'kategori' => \App\Models\Kategori::getCachedDropdown(),
            'merek' => \App\Models\Merek::getCachedDropdown(),
            'lokasi' => \App\Models\Lokasi::getCachedDropdown(),
        ]);
    }

    public function update(BarangMasukRequest $request, Barang $barang): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($barang, $data) {
            $barangTerkini = Barang::query()
                ->lockForUpdate()
                ->findOrFail($barang->id);

            $payload = [
                'nama' => $data['nama'],
                'kategori_id' => $data['kategori_id'],
                'merek_id' => $data['merek_id'] ?? null,
                'lokasi_id' => $data['lokasi_id'] ?? null,
                'spesifikasi' => $data['spesifikasi'] ?? null,
                'tahun_pengadaan' => $data['tahun_pengadaan'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ];

            $barangTerkini->update($payload);
        });

        return redirect()
            ->route('barang.show', $barang)
            ->with('sukses', 'Barang berhasil diperbarui.');
    }

    public function destroy(Barang $barang): RedirectResponse
    {
        if ($barang->tipe === 'aset' && $barang->unitBarang()->where('status', 'dipinjam')->exists()) {
            return back()->with('galat', 'Barang tidak bisa dihapus karena masih ada unit yang sedang dipinjam.');
        }

        if ($barang->tipe === 'stok' && (int) $barang->qty_dipinjam > 0) {
            return back()->with('galat', 'Barang stok tidak bisa dihapus karena masih ada jumlah yang sedang dipinjam.');
        }

        if ($barang->transaksi()->exists() || $barang->detailPeminjaman()->exists()) {
            return back()->with('galat', 'Barang tidak bisa dihapus karena sudah memiliki riwayat transaksi atau peminjaman.');
        }

        DB::transaction(function () use ($barang) {
            if ($barang->tipe === 'aset') {
                $barang->unitBarang()->delete();
            }

            $barang->delete();
        });

        return redirect()
            ->route('barang.index')
            ->with('sukses', 'Barang berhasil dihapus.');
    }

    public function unitList(Barang $barang): View
    {
        abort_if($barang->tipe !== 'aset', 404);

        $barang->load(['kategori:id,nama']);

        $unit = $barang->unitBarang()
            ->orderBy('nomor_unit')
            ->paginate(12)
            ->withQueryString();

        return view('barang.unit', [
            'barang' => $barang,
            'unit' => $unit,
        ]);
    }

    /**
     * Update kondisi, status, serial number, dan catatan unit.
     * Mengembalikan JSON jika dipanggil via AJAX (simpan massal),
     * atau redirect biasa jika dipanggil via form submit standar.
     */
    public function updateUnit(Request $request, Barang $barang, UnitBarang $unit): RedirectResponse|JsonResponse
    {
        abort_if($barang->tipe !== 'aset', 404);
        abort_if($unit->barang_id !== $barang->id, 404);

        $validated = $request->validate([
            'serial_number' => ['nullable', 'string', 'max:100'],
            'kondisi' => ['required', 'integer', 'between:0,100'],
            'status' => ['required', 'in:tersedia,dipinjam,rusak,keluar'],
            'catatan' => ['nullable', 'string'],
        ], [
            'kondisi.required' => 'Kondisi unit wajib diisi.',
            'kondisi.between' => 'Kondisi unit harus antara 0 sampai 100.',
            'status.required' => 'Status unit wajib dipilih.',
            'status.in' => 'Status unit tidak valid.',
            'serial_number.max' => 'Serial number maksimal 100 karakter.',
        ]);

        $kondisi = (int) $validated['kondisi'];

        // Logika Status:
        // 1. Jika kondisi <= 34, paksa jadi rusak.
        // 2. Jika kondisi > 34 tapi status yang direquest adalah 'rusak' (artinya user merubah kondisi jadi bagus tapi lupa/terlewat merubah dropdown status),
        //    maka paksa statusnya kembali ke 'tersedia' karena barang bagus tidak mungkin statusnya masih rusak.
        // 3. Selain itu, gunakan status yang direquest.
        if ($kondisi <= 34) {
            $status = 'rusak';
        } elseif ($validated['status'] === 'rusak') {
            $status = 'tersedia';
        } else {
            $status = $validated['status'];
        }

        $unit->update([
            'serial_number' => isset($validated['serial_number']) ? trim((string) $validated['serial_number']) : null,
            'kondisi' => $kondisi,
            'status' => $status,
            'catatan' => isset($validated['catatan']) ? trim((string) $validated['catatan']) : null,
        ]);

        $pesan = $kondisi <= 34
            ? 'Unit berhasil diperbarui. Karena kondisi ≤34%, status otomatis menjadi rusak.'
            : 'Unit berhasil diperbarui.';

        // Jika request berasal dari AJAX (simpan massal), kembalikan JSON
        if ($request->ajax()) {
            return response()->json([
                'sukses' => true,
                'pesan' => $pesan,
                'unit_id' => $unit->id,
                'status' => $status,
                'kondisi' => $kondisi,
            ]);
        }

        // Jika request biasa (form submit), redirect seperti biasa
        return back()->with('sukses', $pesan);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = Barang::query()
            ->with([
                'kategori:id,nama',
                'merek:id,nama',
                'lokasi:id,nama',
            ])
            ->withCount([
                'unitBarang as unit_tersedia_count' => fn (Builder $sub) => $sub->where('status', 'tersedia'),
            ])
            ->withAvg('unitBarang as rata_kondisi_unit', 'kondisi')
            ->where('aktif', true)
            ->where(function (Builder $query) use ($q) {
                // [OPTIMASI DB VERCEL]: Sama seperti di index, deteksi otomatis driver DB agar Live Search bisa kebal huruf besar/kecil di Vercel.
                $likeOperator = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

                $query->where('nama', $likeOperator, '%'.$q.'%')
                    ->orWhereHas('kategori', fn (Builder $sub) => $sub->where('nama', $likeOperator, '%'.$q.'%'))
                    ->orWhereHas('merek', fn (Builder $sub) => $sub->where('nama', $likeOperator, '%'.$q.'%'));
            })
            ->orderBy('nama')
            ->limit(10)
            ->get()
            ->map(function (Barang $barang): array {
                $kondisi = $barang->tipe === 'aset'
                    ? (int) round((float) ($barang->rata_kondisi_unit ?? 0))
                    : (int) ($barang->kondisi_stok ?? 100);

                /** @var int|null $unitTersediaCount */
                $unitTersediaCount = $barang->getAttribute('unit_tersedia_count');

                return [
                    'id' => $barang->id,
                    'nama' => $barang->nama,
                    'tipe' => $barang->tipe,
                    'kategori' => $barang->kategori?->nama,
                    'merek' => $barang->label_merek,
                    'lokasi' => $barang->label_lokasi,
                    'kondisi' => $kondisi,
                    'label_kondisi' => KondisiHelper::label($kondisi),
                    'qty_tersedia' => $barang->tipe === 'stok' ? (int) $barang->qty_tersedia : null,
                    'unit_tersedia' => $barang->tipe === 'aset' ? (int) $unitTersediaCount : null,
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function unitTersedia(Barang $barang): JsonResponse
    {
        abort_if($barang->tipe !== 'aset', 404);

        $includeRusak = request()->boolean('include_rusak');

        $statusYangDiambil = $includeRusak
            ? ['tersedia', 'rusak']
            : ['tersedia'];

        /** @var \Illuminate\Database\Eloquent\Collection<int, UnitBarang> $units */
        $units = UnitBarang::query()
            ->where('barang_id', $barang->id)
            ->whereIn('status', $statusYangDiambil)
            ->orderByDesc('kondisi')
            ->orderBy('nomor_unit')
            ->get([
                'id',
                'barang_id',
                'nomor_unit',
                'serial_number',
                'kondisi',
                'status',
            ]);

        $unit = $units->map(fn (UnitBarang $item): array => [
            'id' => $item->id,
            'nomor_unit' => $item->nomor_unit,
            'serial_number' => $item->serial_number,
            'kondisi' => (int) $item->kondisi,
            'label_kondisi' => $item->label_kondisi,
            'status' => $item->status,
        ])
            ->values();

        return response()->json($unit);
    }
}
