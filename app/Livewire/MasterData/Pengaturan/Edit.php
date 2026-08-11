<?php

namespace App\Livewire\MasterData\Pengaturan;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app', ['title' => 'Pengaturan Sistem'])]
class Edit extends Component
{
    use WithFileUploads;

    public Pengaturan $pengaturan;

    public string $nama_instansi = '';

    public string $nama_daerah = '';

    public $logo_instansi = null;

    public $favicon = null;

    public ?string $alamat = null;

    public ?string $telepon = null;

    public ?string $warna_tema = null;

    public ?string $base_title = null;

    public ?float $persentase_shu = 20;

    public ?string $logo_instansi_path = null;

    public ?string $favicon_path = null;

    public function mount(): void
    {
        Gate::authorize('super_admin');

        $this->pengaturan = Pengaturan::getSettings();
        $this->nama_instansi = $this->pengaturan->nama_instansi;
        $this->nama_daerah = $this->pengaturan->nama_daerah;
        $this->alamat = $this->pengaturan->alamat;
        $this->telepon = $this->pengaturan->telepon;
        $this->warna_tema = $this->pengaturan->warna_tema;
        $this->base_title = $this->pengaturan->base_title;
        $this->persentase_shu = $this->pengaturan->persentase_shu ?? 20;
        $this->logo_instansi_path = $this->pengaturan->logo_instansi;
        $this->favicon_path = $this->pengaturan->favicon;
    }

    public function updatedLogoInstansi(): void
    {
        $this->validateOnly('logo_instansi', [
            'logo_instansi' => ['nullable', 'image', 'max:2048'], // 2MB in KB
        ], [
            'logo_instansi.image' => 'Logo harus berupa gambar (JPG, PNG, GIF, WEBP).',
            'logo_instansi.max' => 'Ukuran logo maksimal 2MB.',
        ]);
    }

    public function updatedFavicon(): void
    {
        $this->validateOnly('favicon', [
            'favicon' => ['nullable', 'image', 'max:512'], // 512KB in KB
        ], [
            'favicon.image' => 'Favicon harus berupa gambar (JPG, PNG, GIF, ICO).',
            'favicon.max' => 'Ukuran favicon maksimal 512KB.',
        ]);
    }

    public function update(): void
    {
        try {
            $validated = $this->validate([
                'nama_instansi' => ['required', 'string', 'max:255'],
                'nama_daerah' => ['required', 'string', 'max:255'],
                'logo_instansi' => ['nullable', 'image', 'max:2048'], // 2MB in KB
                'favicon' => ['nullable', 'image', 'max:512'], // 512KB in KB
                'alamat' => ['nullable', 'string'],
                'telepon' => ['nullable', 'string', 'max:50'],
                'warna_tema' => ['nullable', 'string', 'max:50'],
                'base_title' => ['nullable', 'string', 'max:255'],
                'persentase_shu' => ['required', 'numeric', 'min:0', 'max:100'],
            ], [
                'nama_instansi.required' => 'Nama instansi wajib diisi.',
                'nama_daerah.required' => 'Nama daerah wajib diisi.',
                'logo_instansi.image' => 'Logo harus berupa gambar (JPG, PNG, GIF, WEBP).',
                'logo_instansi.max' => 'Ukuran logo maksimal 2MB.',
                'favicon.image' => 'Favicon harus berupa gambar (JPG, PNG, GIF, ICO).',
                'favicon.max' => 'Ukuran favicon maksimal 512KB.',
                'persentase_shu.required' => 'Persentase SHU wajib diisi.',
                'persentase_shu.numeric' => 'Persentase SHU harus berupa angka.',
                'persentase_shu.min' => 'Persentase SHU minimal 0%.',
                'persentase_shu.max' => 'Persentase SHU maksimal 100%.',
            ]);

            // Handle logo upload
            if ($this->logo_instansi) {
                // Check if file is valid
                if (! $this->logo_instansi->isValid()) {
                    $this->dispatch('error', message: 'File logo tidak valid atau rusak.');

                    return;
                }

                // Delete old logo if exists
                if ($this->logo_instansi_path && Storage::disk('public')->exists($this->logo_instansi_path)) {
                    Storage::disk('public')->delete($this->logo_instansi_path);
                }

                // Ensure directory exists
                if (! Storage::disk('public')->exists('pengaturan')) {
                    Storage::disk('public')->makeDirectory('pengaturan');
                }

                $validated['logo_instansi'] = $this->logo_instansi->store('pengaturan', 'public');
                $this->logo_instansi_path = $validated['logo_instansi'];
            } else {
                unset($validated['logo_instansi']);
            }

            // Handle favicon upload
            if ($this->favicon) {
                // Check if file is valid
                if (! $this->favicon->isValid()) {
                    $this->dispatch('error', message: 'File favicon tidak valid atau rusak.');

                    return;
                }

                // Delete old favicon if exists
                if ($this->favicon_path && Storage::disk('public')->exists($this->favicon_path)) {
                    Storage::disk('public')->delete($this->favicon_path);
                }

                // Ensure directory exists
                if (! Storage::disk('public')->exists('pengaturan')) {
                    Storage::disk('public')->makeDirectory('pengaturan');
                }

                $validated['favicon'] = $this->favicon->store('pengaturan', 'public');
                $this->favicon_path = $validated['favicon'];
            } else {
                unset($validated['favicon']);
            }

            $this->pengaturan->update($validated);

            // Reset file inputs
            $this->logo_instansi = null;
            $this->favicon = null;

            $this->dispatch('success', message: 'Pengaturan sistem berhasil diperbarui.');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $errorMessage = 'Validasi gagal: ';
            foreach ($errors as $field => $messages) {
                $errorMessage .= implode(', ', $messages);
            }
            $this->dispatch('error', message: $errorMessage);
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function removeLogo(): void
    {
        if ($this->logo_instansi_path && Storage::disk('public')->exists($this->logo_instansi_path)) {
            Storage::disk('public')->delete($this->logo_instansi_path);
        }

        $this->pengaturan->update(['logo_instansi' => null]);
        $this->logo_instansi_path = null;

        $this->dispatch('success', message: 'Logo berhasil dihapus.');
    }

    public function removeFavicon(): void
    {
        if ($this->favicon_path && Storage::disk('public')->exists($this->favicon_path)) {
            Storage::disk('public')->delete($this->favicon_path);
        }

        $this->pengaturan->update(['favicon' => null]);
        $this->favicon_path = null;

        $this->dispatch('success', message: 'Favicon berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.master-data.pengaturan.edit');
    }
}
