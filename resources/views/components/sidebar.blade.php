@php $user = auth()->user(); $role = $user->role; @endphp

<aside id="sikma-sidebar"
    class="w-56 shrink-0 flex flex-col"
    style="background: linear-gradient(180deg, #0a2e5c 0%, #0d3a73 100%);">

    <div class="px-4 py-3 text-xs font-semibold uppercase tracking-widest text-sky/60 border-b border-white/10">
        Navigasi
    </div>

    <nav class="flex flex-col gap-0.5 p-2 flex-1">
        @php
            $active = 'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold bg-brand text-white shadow-sm';
            $idle   = 'flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-white/80 hover:bg-white/10 hover:text-white transition-colors';
        @endphp

        @if($role === 'pegawai')
            <a href="{{ route('pegawai.dashboard') }}"  class="{{ request()->routeIs('pegawai.dashboard')  ? $active : $idle }}">Dashboard</a>
            <a href="{{ route('pegawai.scan') }}"       class="{{ request()->routeIs('pegawai.scan')       ? $active : $idle }}">Scan QR</a>
            <a href="{{ route('pegawai.izin-dinas') }}" class="{{ request()->routeIs('pegawai.izin-dinas') ? $active : $idle }}">Izin Dinas</a>
            <a href="{{ route('pegawai.riwayat') }}"    class="{{ request()->routeIs('pegawai.riwayat')    ? $active : $idle }}">Riwayat Saya</a>
        @endif

        @if($role === 'atasan')
            <a href="{{ route('atasan.izin-dinas') }}" class="{{ request()->routeIs('atasan.izin-dinas') ? $active : $idle }}">Izin Dinas Bawahan</a>
        @endif

        @if($role === 'admin')
            <a href="{{ route('admin.dashboard') }}"  class="{{ request()->routeIs('admin.dashboard')  ? $active : $idle }}">Dashboard</a>
            <a href="{{ route('admin.pegawai') }}"    class="{{ request()->routeIs('admin.pegawai*')   ? $active : $idle }}">Pegawai</a>
            <a href="{{ route('admin.catatan') }}"    class="{{ request()->routeIs('admin.catatan*')   ? $active : $idle }}">Catatan</a>
            <a href="{{ route('admin.rekap') }}"      class="{{ request()->routeIs('admin.rekap')      ? $active : $idle }}">Rekap</a>
            <a href="{{ route('admin.pengaturan') }}" class="{{ request()->routeIs('admin.pengaturan') ? $active : $idle }}">Pengaturan</a>
        @endif

        @if($role === 'pimpinan')
            <a href="{{ route('pimpinan.rekap') }}" class="{{ request()->routeIs('pimpinan.rekap') ? $active : $idle }}">Rekap Unit</a>
        @endif
    </nav>
</aside>
