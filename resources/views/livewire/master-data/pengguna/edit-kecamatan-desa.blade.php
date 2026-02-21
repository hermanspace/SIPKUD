<?php
$showKecamatanDesa = $role !== 'super_admin';
$showDesaSelect = $kecamatan_id && $role !== 'admin_kecamatan';
?>
<?php if ($showKecamatanDesa): ?>
<flux:select wire:model.live="kecamatan_id" label="Kecamatan" required {{ $isAdminKecamatan ? 'disabled' : '' }}>
    <option value="">Pilih Kecamatan</option>
    @foreach($kecamatan as $kec)
        <option value="{{ $kec->id }}">{{ $kec->nama_kecamatan }}</option>
    @endforeach
</flux:select>
<flux:error name="kecamatan_id" />
<?php if ($isAdminKecamatan): ?>
<flux:text class="text-xs text-zinc-500">
    Kecamatan sudah ditetapkan berdasarkan akun Anda
</flux:text>
<?php endif; ?>
<?php if ($showDesaSelect): ?>
<flux:select wire:model="desa_id" label="Desa" required>
    <option value="">Pilih Desa</option>
    @foreach($desa as $d)
        <option value="{{ $d->id }}">{{ $d->nama_desa }}</option>
    @endforeach
</flux:select>
<flux:error name="desa_id" />
<?php endif; ?>
<?php endif; ?>
