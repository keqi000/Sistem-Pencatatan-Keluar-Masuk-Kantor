@php $user = auth()->user(); $role = $user->role; @endphp

<aside style="
    width: 220px;
    height: 100%;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-right: 1px solid #e8f0fe;
    overflow: hidden;
">
    {{-- Label navigasi --}}
    <div style="padding:14px 14px 6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;">
        Menu
    </div>

    {{-- Menu --}}
    <nav style="flex:1;padding:0 8px 8px;display:flex;flex-direction:column;gap:1px;overflow-y:auto;">
        @php
        $menuItems = [];
        if ($role === 'pegawai') {
            $menuItems = [
                ['route' => 'pegawai.dashboard', 'label' => 'Dashboard',    'match' => 'pegawai.dashboard',  'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>'],
                ['route' => 'pegawai.scan',       'label' => 'Scan QR',      'match' => 'pegawai.scan',        'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m0 14v1M4 12h1m14 0h1m-2.05-6.95l-.707.707M6.757 17.243l-.707.707m0-11.9l.707.707M17.243 17.243l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>'],
                ['route' => 'pegawai.izin-dinas', 'label' => 'Izin Dinas',   'match' => 'pegawai.izin-dinas',  'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'],
                ['route' => 'pegawai.riwayat',    'label' => 'Riwayat Saya', 'match' => 'pegawai.riwayat',     'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
            ];
        } elseif ($role === 'atasan') {
            $menuItems = [
                ['route' => 'atasan.izin-dinas', 'label' => 'Izin Dinas Bawahan', 'match' => 'atasan.izin-dinas', 'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
            ];
        } elseif ($role === 'admin') {
            $menuItems = [
                ['route' => 'admin.dashboard',  'label' => 'Dashboard',  'match' => 'admin.dashboard',  'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>'],
                ['route' => 'admin.catatan',    'label' => 'Catatan',    'match' => 'admin.catatan*',   'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'],
                ['route' => 'admin.rekap',      'label' => 'Rekap',      'match' => 'admin.rekap',      'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'],
                ['route' => 'admin.akun',       'label' => 'Akun',       'match' => 'admin.akun',       'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'],
                ['route' => 'admin.pengaturan', 'label' => 'Pengaturan', 'match' => 'admin.pengaturan', 'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'],
            ];
        } elseif ($role === 'pimpinan') {
            $menuItems = [
                ['route' => 'pimpinan.rekap', 'label' => 'Rekap Unit', 'match' => 'pimpinan.rekap', 'icon' => '<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'],
            ];
        }
        @endphp

        @foreach($menuItems as $item)
            @php $isActive = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               onclick="if(window.innerWidth<=768) closeSidebar()"
               style="display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:8px;font-size:13px;text-decoration:none;transition:all 0.15s;
                      {{ $isActive
                          ? 'background:#ffffff;color:#0073e6;font-weight:600;box-shadow:0 1px 6px rgba(0,115,230,0.12);border:1px solid rgba(0,115,230,0.18);'
                          : 'color:#475569;font-weight:500;border:1px solid transparent;' }}"
               @if(!$isActive)
                   onmouseover="this.style.background='rgba(255,255,255,0.7)';this.style.color='#0a2e5c';"
                   onmouseout="this.style.background='transparent';this.style.color='#475569';"
               @endif>
                <span style="flex-shrink:0;opacity:{{ $isActive ? '1' : '0.6' }};">{!! $item['icon'] !!}</span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- User info bawah --}}
    <div style="padding:10px 12px;border-top:1px solid #e8f0fe;display:flex;align-items:center;gap:8px;background:rgba(240,247,255,0.5);">
        <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,#0073e6,#5cc2f2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span style="color:white;font-size:11px;font-weight:700;">{{ strtoupper(substr($user->full_name, 0, 1)) }}</span>
        </div>
        <div style="min-width:0;flex:1;">
            <p style="font-size:11px;font-weight:700;color:#0a2e5c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0;">{{ $user->full_name }}</p>
            <p style="font-size:10px;color:#0073e6;font-weight:600;text-transform:capitalize;margin:0;">{{ $user->role }}</p>
        </div>
    </div>
</aside>
