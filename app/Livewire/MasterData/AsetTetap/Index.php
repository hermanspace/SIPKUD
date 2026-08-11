<?php

namespace App\Livewire\MasterData\AsetTetap;

use App\Models\Akun;
use App\Models\AsetTetap;
use App\Services\AsetTetapService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Aset Tetap'])]
class Index extends Component
{
    public bool $showForm = false;

    public string $nama_aset = '';

    public string $tanggal_perolehan = '';

    public $harga_perolehan = null;

    public $nilai_residu = 0;

    public $umur_bulan = 48;

    public $akun_aset_id = null;

    public $akun_akumulasi_id = null;

    public $akun_beban_id = null;

    public function mount(): void
    {
        Gate::authorize('view_desa_data');
        $this->tanggal_perolehan = now()->toDateString();
    }

    public function simpan(): void
    {
        Gate::authorize('admin_desa');

        $this->validate([
            'nama_aset' => 'required|string|max:255',
            'tanggal_perolehan' => 'required|date',
            'harga_perolehan' => 'required|numeric|min:1',
            'nilai_residu' => 'required|numeric|min:0',
            'umur_bulan' => 'required|integer|min:1|max:600',
            'akun_aset_id' => 'required|exists:akun,id',
            'akun_akumulasi_id' => 'required|exists:akun,id',
            'akun_beban_id' => 'required|exists:akun,id',
        ]);

        AsetTetap::create([
            'desa_id' => Auth::user()->desa_id,
            'nama_aset' => $this->nama_aset,
            'tanggal_perolehan' => $this->tanggal_perolehan,
            'harga_perolehan' => $this->harga_perolehan,
            'nilai_residu' => $this->nilai_residu,
            'umur_bulan' => $this->umur_bulan,
            'akun_aset_id' => $this->akun_aset_id,
            'akun_akumulasi_id' => $this->akun_akumulasi_id,
            'akun_beban_id' => $this->akun_beban_id,
            'status' => 'aktif',
            'created_by' => Auth::id(),
        ]);

        $this->reset('showForm', 'nama_aset', 'harga_perolehan');
        session()->flash('message', 'Aset tetap berhasil ditambahkan. Penyusutan akan dijurnal otomatis tiap awal bulan.');
    }

    public function prosesPenyusutan(AsetTetapService $service): void
    {
        Gate::authorize('admin_desa');

        $hasil = $service->prosesPenyusutan(Auth::user()->desa_id);

        session()->flash('message', $hasil['diproses'] > 0
            ? sprintf('%d aset disusutkan, total Rp %s.', $hasil['diproses'], number_format($hasil['total'], 2, ',', '.'))
            : 'Tidak ada aset yang perlu disusutkan periode ini.');
    }

    public function render()
    {
        return view('livewire.master-data.aset-tetap.index', [
            'asets' => AsetTetap::with(['akunAset'])->orderBy('tanggal_perolehan', 'desc')->get(),
            'akunAset' => Akun::aktif()->byTipe('aset')->orderBy('kode_akun')->get(),
            'akunBeban' => Akun::aktif()->byTipe('beban')->orderBy('kode_akun')->get(),
        ]);
    }
}
