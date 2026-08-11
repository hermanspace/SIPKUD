<?php

namespace App\Livewire\Pinjaman;

use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\SektorUsaha;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Tambah Pinjaman'])]
class Create extends Component
{
    public ?int $anggota_id = null;

    public ?int $sektor_usaha_id = null;

    public string $tanggal_pinjaman = '';

    public string $jumlah_pinjaman = '';

    public string $jangka_waktu_bulan = '';

    public string $jasa_persen = '';

    public string $status_pinjaman = 'aktif';

    /** Untuk form "Tambah sektor usaha baru" */
    public bool $show_new_sektor = false;

    public string $new_sektor_nama = '';

    public function mount(): void
    {
        // Hanya Admin Desa yang bisa membuat pinjaman
        Gate::authorize('admin_desa');

        // Set default tanggal ke hari ini
        $this->tanggal_pinjaman = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'anggota_id' => ['required', 'exists:anggota,id'],
            'sektor_usaha_id' => ['nullable', 'exists:sektor_usaha,id'],
            'tanggal_pinjaman' => ['required', 'date'],
            'jumlah_pinjaman' => ['required', 'numeric', 'min:1'],
            'jangka_waktu_bulan' => ['required', 'integer', 'min:1'],
            'jasa_persen' => ['required', 'numeric', 'min:0', 'max:100'],
            'status_pinjaman' => ['required', 'in:aktif,lunas'],
        ], [
            'anggota_id.required' => 'Anggota wajib dipilih.',
            'anggota_id.exists' => 'Anggota yang dipilih tidak valid.',
            'tanggal_pinjaman.required' => 'Tanggal pinjaman wajib diisi.',
            'tanggal_pinjaman.date' => 'Tanggal pinjaman harus berupa tanggal yang valid.',
            'jumlah_pinjaman.required' => 'Jumlah pinjaman wajib diisi.',
            'jumlah_pinjaman.numeric' => 'Jumlah pinjaman harus berupa angka.',
            'jumlah_pinjaman.min' => 'Jumlah pinjaman harus lebih dari 0.',
            'jangka_waktu_bulan.required' => 'Jangka waktu wajib diisi.',
            'jangka_waktu_bulan.integer' => 'Jangka waktu harus berupa bilangan bulat.',
            'jangka_waktu_bulan.min' => 'Jangka waktu harus minimal 1 bulan.',
            'jasa_persen.required' => 'Jasa persen wajib diisi.',
            'jasa_persen.numeric' => 'Jasa persen harus berupa angka.',
            'jasa_persen.min' => 'Jasa persen tidak boleh negatif.',
            'jasa_persen.max' => 'Jasa persen tidak boleh lebih dari 100.',
            'status_pinjaman.required' => 'Status pinjaman wajib dipilih.',
            'status_pinjaman.in' => 'Status pinjaman tidak valid.',
        ]);

        // Pastikan hanya admin desa yang bisa membuat pinjaman
        Gate::authorize('admin_desa');

        $user = Auth::user();
        if (! $user->desa_id) {
            abort(403, 'Anda tidak memiliki izin untuk membuat pinjaman.');
        }

        // Validasi: Anggota harus berstatus aktif
        $anggota = Anggota::findOrFail($validated['anggota_id']);
        if ($anggota->status !== 'aktif') {
            $this->addError('anggota_id', 'Anggota yang dipilih tidak aktif.');

            return;
        }

        // Validasi: Satu anggota hanya boleh memiliki satu pinjaman aktif
        $pinjamanAktif = Pinjaman::where('anggota_id', $validated['anggota_id'])
            ->where('status_pinjaman', 'aktif')
            ->exists();

        if ($pinjamanAktif) {
            $this->addError('anggota_id', 'Anggota ini sudah memiliki pinjaman aktif.');

            return;
        }

        // Generate nomor pinjaman otomatis
        $validated['nomor_pinjaman'] = $this->generateNomorPinjaman($user->desa_id);
        $validated['desa_id'] = $user->desa_id;
        if (empty($validated['sektor_usaha_id'])) {
            unset($validated['sektor_usaha_id']);
        }
        $validated['jumlah_pinjaman'] = (float) $validated['jumlah_pinjaman'];
        $validated['jangka_waktu_bulan'] = (int) $validated['jangka_waktu_bulan'];
        $validated['jasa_persen'] = (float) $validated['jasa_persen'];
        $validated['tanggal_pinjaman'] = Carbon::parse($validated['tanggal_pinjaman']);

        Pinjaman::create($validated);

        $this->dispatch('success', message: 'Pinjaman berhasil ditambahkan.');
        $this->redirect(route('pinjaman.index'), navigate: true);
    }

    /**
     * Generate nomor pinjaman otomatis
     * Format: PINJ-YYYYMMDD-XXX (contoh: PINJ-20251218-001)
     */
    private function generateNomorPinjaman(int $desaId): string
    {
        $date = now()->format('Ymd');
        $prefix = "PINJ-{$date}-";

        // Cari nomor terakhir untuk tanggal hari ini
        $lastPinjaman = Pinjaman::where('desa_id', $desaId)
            ->where('nomor_pinjaman', 'like', $prefix.'%')
            ->orderBy('nomor_pinjaman', 'desc')
            ->first();

        if ($lastPinjaman) {
            // Extract nomor urut dari nomor terakhir
            $lastNumber = (int) substr($lastPinjaman->nomor_pinjaman, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Tambah sektor usaha baru dari form (untuk dropdown).
     */
    public function addSektorUsaha(): void
    {
        $this->validate([
            'new_sektor_nama' => ['required', 'string', 'max:100'],
        ], [
            'new_sektor_nama.required' => 'Nama sektor usaha wajib diisi.',
            'new_sektor_nama.max' => 'Nama sektor maksimal 100 karakter.',
        ]);

        $user = Auth::user();
        if (! $user || ! $user->desa_id) {
            return;
        }

        $sektor = SektorUsaha::firstOrCreate(
            [
                'desa_id' => $user->desa_id,
                'nama' => trim($this->new_sektor_nama),
            ],
            ['status' => 'aktif']
        );

        $this->sektor_usaha_id = $sektor->id;
        $this->new_sektor_nama = '';
        $this->show_new_sektor = false;
        $this->dispatch('success', message: 'Sektor usaha ditambahkan.');
    }

    public function render()
    {
        $user = Auth::user();

        // Get anggota aktif untuk dropdown
        $anggotaQuery = Anggota::aktif();
        if ($user && $user->desa_id) {
            $anggotaQuery->where('desa_id', $user->desa_id);
        }
        $anggota = $anggotaQuery->orderBy('nama')->get();

        $sektorUsaha = collect();
        if ($user && $user->desa_id) {
            $sektorUsaha = SektorUsaha::where('desa_id', $user->desa_id)->aktif()->orderBy('nama')->get();
        }

        return view('livewire.pinjaman.create', [
            'anggota' => $anggota,
            'sektorUsaha' => $sektorUsaha,
        ]);
    }
}
