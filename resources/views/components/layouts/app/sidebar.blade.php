<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    <flux:navlist.item icon="book-open" :href="route('user-manual.index')" :current="request()->routeIs('user-manual.*')" wire:navigate>{{ __('User Manual') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            @if(auth()->user()->isSuperAdmin())
                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Master Data')" class="grid">
                        <flux:navlist.item icon="map" :href="route('kecamatan.index')" :current="request()->routeIs('kecamatan.*')" wire:navigate>{{ __('Kecamatan') }}</flux:navlist.item>
                        <flux:navlist.item icon="building-office" :href="route('desa.index')" :current="request()->routeIs('desa.*')" wire:navigate>{{ __('Desa') }}</flux:navlist.item>
                        <flux:navlist.item icon="user-group" :href="route('kelompok.index')" :current="request()->routeIs('kelompok.*')" wire:navigate>{{ __('Kelompok') }}</flux:navlist.item>
                        <flux:navlist.item icon="user" :href="route('anggota.index')" :current="request()->routeIs('anggota.*')" wire:navigate>{{ __('Anggota') }}</flux:navlist.item>
                        <flux:navlist.item icon="chart-pie" :href="route('akun.index')" :current="request()->routeIs('akun.*')" wire:navigate>{{ __('Akun (COA)') }}</flux:navlist.item>
                        <flux:navlist.item icon="briefcase" :href="route('unit-usaha.index')" :current="request()->routeIs('unit-usaha.*')" wire:navigate>{{ __('Unit Usaha') }}</flux:navlist.item>
                        <flux:navlist.item icon="computer-desktop" :href="route('aset-tetap.index')" :current="request()->routeIs('aset-tetap.*')" wire:navigate>{{ __('Aset Tetap') }}</flux:navlist.item>
                        <flux:navlist.item icon="building-storefront" :href="route('sektor-usaha.index')" :current="request()->routeIs('sektor-usaha.*')" wire:navigate>{{ __('Sektor Usaha') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Transaksi')" class="grid">
                        <flux:navlist.item icon="currency-dollar" :href="route('pinjaman.index')" :current="request()->routeIs('pinjaman.*')" wire:navigate>{{ __('Pinjaman') }}</flux:navlist.item>
                        <flux:navlist.item icon="banknotes" :href="route('angsuran.index')" :current="request()->routeIs('angsuran.*')" wire:navigate>{{ __('Angsuran') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Akuntansi')" class="grid">
                        <flux:navlist.item icon="banknotes" :href="route('kas.index')" :current="request()->routeIs('kas.index') || request()->routeIs('kas.create') || request()->routeIs('kas.edit')" wire:navigate>{{ __('Kas Harian') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('memorial.index')" :current="request()->routeIs('memorial.*')" wire:navigate>{{ __('Buku Memorial') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Laporan')" class="grid">
                        <flux:navlist.item icon="document-chart-bar" :href="route('laporan.lpp-ued')" :current="request()->routeIs('laporan.lpp-ued')" wire:navigate>{{ __('LPP UED') }}</flux:navlist.item>
                        <flux:navlist.item icon="wallet" :href="route('laporan.buku-kas')" :current="request()->routeIs('laporan.buku-kas')" wire:navigate>{{ __('Buku Kas') }}</flux:navlist.item>
                        <flux:navlist.item icon="chart-bar" :href="route('laporan.akhir-usp')" :current="request()->routeIs('laporan.akhir-usp')" wire:navigate>{{ __('Laporan Akhir USP') }}</flux:navlist.item>
                        <flux:navlist.item icon="scale" :href="route('laporan.neraca-saldo')" :current="request()->routeIs('laporan.neraca-saldo')" wire:navigate>{{ __('Neraca Saldo') }}</flux:navlist.item>
                        <flux:navlist.item icon="presentation-chart-line" :href="route('laporan.laba-rugi')" :current="request()->routeIs('laporan.laba-rugi')" wire:navigate>{{ __('Laba Rugi') }}</flux:navlist.item>
                        <flux:navlist.item icon="clipboard-document-list" :href="route('laporan.neraca')" :current="request()->routeIs('laporan.neraca')" wire:navigate>{{ __('Neraca') }}</flux:navlist.item>
                        <flux:navlist.item icon="arrows-right-left" :href="route('laporan.arus-kas')" :current="request()->routeIs('laporan.arus-kas')" wire:navigate>{{ __('Arus Kas') }}</flux:navlist.item>
                        <flux:navlist.item icon="scale" :href="route('laporan.perubahan-ekuitas')" :current="request()->routeIs('laporan.perubahan-ekuitas')" wire:navigate>{{ __('Perubahan Ekuitas') }}</flux:navlist.item>
                        <flux:navlist.item icon="exclamation-triangle" :href="route('laporan.kolektibilitas')" :current="request()->routeIs('laporan.kolektibilitas')" wire:navigate>{{ __('Kolektibilitas') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('laporan.calk')" :current="request()->routeIs('laporan.calk')" wire:navigate>{{ __('CALK') }}</flux:navlist.item>
                        <flux:navlist.item icon="archive-box" :href="route('laporan.tahunan')" :current="request()->routeIs('laporan.tahunan')" wire:navigate>{{ __('Laporan Tahunan') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Periode Akuntansi')" class="grid">
                        <flux:navlist.item icon="calendar" :href="route('periode.index')" :current="request()->routeIs('periode.*')" wire:navigate>{{ __('Manajemen Periode') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Pengaturan')" class="grid">
                        <flux:navlist.item icon="users" :href="route('pengguna.index')" :current="request()->routeIs('pengguna.*')" wire:navigate>{{ __('Pengguna') }}</flux:navlist.item>
                        <flux:navlist.item icon="megaphone" :href="route('pengumuman.index')" :current="request()->routeIs('pengumuman.*')" wire:navigate>{{ __('Pengumuman') }}</flux:navlist.item>
                        <flux:navlist.item icon="cog-6-tooth" :href="route('pengaturan.index')" :current="request()->routeIs('pengaturan.*')" wire:navigate>{{ __('Pengaturan Sistem') }}</flux:navlist.item>
                        <flux:navlist.item icon="circle-stack" :href="route('backup.index')" :current="request()->routeIs('backup.*')" wire:navigate>{{ __('Backup Database') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>
            @elseif(auth()->user()->isAdminKecamatan())
                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Master Data')" class="grid">
                        <flux:navlist.item icon="user-group" :href="route('kelompok.index')" :current="request()->routeIs('kelompok.*')" wire:navigate>{{ __('Kelompok') }}</flux:navlist.item>
                        <flux:navlist.item icon="user" :href="route('anggota.index')" :current="request()->routeIs('anggota.*')" wire:navigate>{{ __('Anggota') }}</flux:navlist.item>
                        <flux:navlist.item icon="chart-pie" :href="route('akun.index')" :current="request()->routeIs('akun.*')" wire:navigate>{{ __('Akun (COA)') }}</flux:navlist.item>
                        <flux:navlist.item icon="briefcase" :href="route('unit-usaha.index')" :current="request()->routeIs('unit-usaha.*')" wire:navigate>{{ __('Unit Usaha') }}</flux:navlist.item>
                        <flux:navlist.item icon="computer-desktop" :href="route('aset-tetap.index')" :current="request()->routeIs('aset-tetap.*')" wire:navigate>{{ __('Aset Tetap') }}</flux:navlist.item>
                        <flux:navlist.item icon="building-storefront" :href="route('sektor-usaha.index')" :current="request()->routeIs('sektor-usaha.*')" wire:navigate>{{ __('Sektor Usaha') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Transaksi')" class="grid">
                        <flux:navlist.item icon="currency-dollar" :href="route('pinjaman.index')" :current="request()->routeIs('pinjaman.*')" wire:navigate>{{ __('Pinjaman') }}</flux:navlist.item>
                        <flux:navlist.item icon="banknotes" :href="route('angsuran.index')" :current="request()->routeIs('angsuran.*')" wire:navigate>{{ __('Angsuran') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Akuntansi')" class="grid">
                        <flux:navlist.item icon="banknotes" :href="route('kas.index')" :current="request()->routeIs('kas.index') || request()->routeIs('kas.create') || request()->routeIs('kas.edit')" wire:navigate>{{ __('Kas Harian') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('memorial.index')" :current="request()->routeIs('memorial.*')" wire:navigate>{{ __('Buku Memorial') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Laporan')" class="grid">
                        <flux:navlist.item icon="document-chart-bar" :href="route('laporan.lpp-ued')" :current="request()->routeIs('laporan.lpp-ued')" wire:navigate>{{ __('LPP UED') }}</flux:navlist.item>
                        <flux:navlist.item icon="wallet" :href="route('laporan.buku-kas')" :current="request()->routeIs('laporan.buku-kas')" wire:navigate>{{ __('Buku Kas') }}</flux:navlist.item>
                        <flux:navlist.item icon="chart-bar" :href="route('laporan.akhir-usp')" :current="request()->routeIs('laporan.akhir-usp')" wire:navigate>{{ __('Laporan Akhir USP') }}</flux:navlist.item>
                        <flux:navlist.item icon="scale" :href="route('laporan.neraca-saldo')" :current="request()->routeIs('laporan.neraca-saldo')" wire:navigate>{{ __('Neraca Saldo') }}</flux:navlist.item>
                        <flux:navlist.item icon="presentation-chart-line" :href="route('laporan.laba-rugi')" :current="request()->routeIs('laporan.laba-rugi')" wire:navigate>{{ __('Laba Rugi') }}</flux:navlist.item>
                        <flux:navlist.item icon="clipboard-document-list" :href="route('laporan.neraca')" :current="request()->routeIs('laporan.neraca')" wire:navigate>{{ __('Neraca') }}</flux:navlist.item>
                        <flux:navlist.item icon="arrows-right-left" :href="route('laporan.arus-kas')" :current="request()->routeIs('laporan.arus-kas')" wire:navigate>{{ __('Arus Kas') }}</flux:navlist.item>
                        <flux:navlist.item icon="scale" :href="route('laporan.perubahan-ekuitas')" :current="request()->routeIs('laporan.perubahan-ekuitas')" wire:navigate>{{ __('Perubahan Ekuitas') }}</flux:navlist.item>
                        <flux:navlist.item icon="exclamation-triangle" :href="route('laporan.kolektibilitas')" :current="request()->routeIs('laporan.kolektibilitas')" wire:navigate>{{ __('Kolektibilitas') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('laporan.calk')" :current="request()->routeIs('laporan.calk')" wire:navigate>{{ __('CALK') }}</flux:navlist.item>
                        <flux:navlist.item icon="archive-box" :href="route('laporan.tahunan')" :current="request()->routeIs('laporan.tahunan')" wire:navigate>{{ __('Laporan Tahunan') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Periode Akuntansi')" class="grid">
                        <flux:navlist.item icon="calendar" :href="route('periode.index')" :current="request()->routeIs('periode.*')" wire:navigate>{{ __('Manajemen Periode') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Pengaturan')" class="grid">
                        <flux:navlist.item icon="users" :href="route('pengguna.index')" :current="request()->routeIs('pengguna.*')" wire:navigate>{{ __('Pengguna') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                
            @else
                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Master Data')" class="grid">
                        <flux:navlist.item icon="user-group" :href="route('kelompok.index')" :current="request()->routeIs('kelompok.*')" wire:navigate>{{ __('Kelompok') }}</flux:navlist.item>
                        <flux:navlist.item icon="user" :href="route('anggota.index')" :current="request()->routeIs('anggota.*')" wire:navigate>{{ __('Anggota') }}</flux:navlist.item>
                        <flux:navlist.item icon="chart-pie" :href="route('akun.index')" :current="request()->routeIs('akun.*')" wire:navigate>{{ __('Akun (COA)') }}</flux:navlist.item>
                        <flux:navlist.item icon="briefcase" :href="route('unit-usaha.index')" :current="request()->routeIs('unit-usaha.*')" wire:navigate>{{ __('Unit Usaha') }}</flux:navlist.item>
                        <flux:navlist.item icon="computer-desktop" :href="route('aset-tetap.index')" :current="request()->routeIs('aset-tetap.*')" wire:navigate>{{ __('Aset Tetap') }}</flux:navlist.item>
                        <flux:navlist.item icon="building-storefront" :href="route('sektor-usaha.index')" :current="request()->routeIs('sektor-usaha.*')" wire:navigate>{{ __('Sektor Usaha') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Transaksi')" class="grid">
                        <flux:navlist.item icon="currency-dollar" :href="route('pinjaman.index')" :current="request()->routeIs('pinjaman.*')" wire:navigate>{{ __('Pinjaman') }}</flux:navlist.item>
                        <flux:navlist.item icon="banknotes" :href="route('angsuran.index')" :current="request()->routeIs('angsuran.*')" wire:navigate>{{ __('Angsuran') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Akuntansi')" class="grid">
                        <flux:navlist.item icon="banknotes" :href="route('kas.index')" :current="request()->routeIs('kas.index') || request()->routeIs('kas.create') || request()->routeIs('kas.edit')" wire:navigate>{{ __('Kas Harian') }}</flux:navlist.item>
                        <flux:navlist.item icon="calculator" :href="route('kas.saldo-awal')" :current="request()->routeIs('kas.saldo-awal')" wire:navigate>{{ __('Saldo Awal') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('memorial.index')" :current="request()->routeIs('memorial.*')" wire:navigate>{{ __('Buku Memorial') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Laporan')" class="grid">
                        <flux:navlist.item icon="document-chart-bar" :href="route('laporan.lpp-ued')" :current="request()->routeIs('laporan.lpp-ued')" wire:navigate>{{ __('LPP UED') }}</flux:navlist.item>
                        <flux:navlist.item icon="wallet" :href="route('laporan.buku-kas')" :current="request()->routeIs('laporan.buku-kas')" wire:navigate>{{ __('Buku Kas') }}</flux:navlist.item>
                        <flux:navlist.item icon="chart-bar" :href="route('laporan.akhir-usp')" :current="request()->routeIs('laporan.akhir-usp')" wire:navigate>{{ __('Laporan Akhir USP') }}</flux:navlist.item>
                        <flux:navlist.item icon="scale" :href="route('laporan.neraca-saldo')" :current="request()->routeIs('laporan.neraca-saldo')" wire:navigate>{{ __('Neraca Saldo') }}</flux:navlist.item>
                        <flux:navlist.item icon="presentation-chart-line" :href="route('laporan.laba-rugi')" :current="request()->routeIs('laporan.laba-rugi')" wire:navigate>{{ __('Laba Rugi') }}</flux:navlist.item>
                        <flux:navlist.item icon="clipboard-document-list" :href="route('laporan.neraca')" :current="request()->routeIs('laporan.neraca')" wire:navigate>{{ __('Neraca') }}</flux:navlist.item>
                        <flux:navlist.item icon="arrows-right-left" :href="route('laporan.arus-kas')" :current="request()->routeIs('laporan.arus-kas')" wire:navigate>{{ __('Arus Kas') }}</flux:navlist.item>
                        <flux:navlist.item icon="scale" :href="route('laporan.perubahan-ekuitas')" :current="request()->routeIs('laporan.perubahan-ekuitas')" wire:navigate>{{ __('Perubahan Ekuitas') }}</flux:navlist.item>
                        <flux:navlist.item icon="exclamation-triangle" :href="route('laporan.kolektibilitas')" :current="request()->routeIs('laporan.kolektibilitas')" wire:navigate>{{ __('Kolektibilitas') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('laporan.calk')" :current="request()->routeIs('laporan.calk')" wire:navigate>{{ __('CALK') }}</flux:navlist.item>
                        <flux:navlist.item icon="archive-box" :href="route('laporan.tahunan')" :current="request()->routeIs('laporan.tahunan')" wire:navigate>{{ __('Laporan Tahunan') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Periode Akuntansi')" class="grid">
                        <flux:navlist.item icon="calendar" :href="route('periode.index')" :current="request()->routeIs('periode.*')" wire:navigate>{{ __('Manajemen Periode') }}</flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                
            @endif

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->nama"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->nama }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->nama }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
