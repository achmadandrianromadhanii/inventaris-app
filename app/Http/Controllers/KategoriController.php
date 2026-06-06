<?php

namespace App\Http\Controllers;

use App\Http\Requests\KategoriRequest;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        $kategori = Kategori::query()
            ->withCount('barang')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('kategori.index', [
            'kategori' => $kategori,
        ]);
    }

    public function store(KategoriRequest $request): RedirectResponse
    {
        Kategori::create([
            'nama' => $request->validated('nama'),
            'deskripsi' => $request->validated('deskripsi'),
        ]);

        \Illuminate\Support\Facades\Cache::forget('kategori_dropdown');

        return redirect()
            ->route('kategori.index')
            ->with('sukses', 'Kategori berhasil ditambahkan.');
    }

    public function update(KategoriRequest $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update([
            'nama' => $request->validated('nama'),
            'deskripsi' => $request->validated('deskripsi'),
        ]);

        \Illuminate\Support\Facades\Cache::forget('kategori_dropdown');

        return redirect()
            ->route('kategori.index')
            ->with('sukses', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->barang()->exists()) {
            return redirect()
                ->route('kategori.index')
                ->with('galat', 'Kategori tidak bisa dihapus karena masih memiliki data barang.');
        }

        $kategori->delete();

        \Illuminate\Support\Facades\Cache::forget('kategori_dropdown');

        return redirect()
            ->route('kategori.index')
            ->with('sukses', 'Kategori berhasil dihapus.');
    }
}
