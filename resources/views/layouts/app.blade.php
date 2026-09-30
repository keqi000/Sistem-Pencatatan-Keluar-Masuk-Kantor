<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <title>@yield('title', 'SIKMA') — BPMP Gorontalo</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-bpmp.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        *, *::before, *::after { font-family: 'Inter', sans-serif; box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            background-color: #f0f7ff;
            background-image:
                radial-gradient(ellipse 70% 50% at 0% 0%, rgba(92,194,242,0.13) 0%, transparent 55%),
                radial-gradient(ellipse 50% 60% at 100% 100%, rgba(0,115,230,0.08) 0%, transparent 55%);
            display: flex;
            flex-direction: column;
            min-height: 100svh;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #dbeeff; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #5cc2f2; }
    </style>
    @stack('styles')
</head>
<body>

    {{-- ══ HEADER ══════════════════════════════════════════════════════════ --}}
    <header style="
        background: #ffffff;
        border-bottom: 1px solid #e8f0fe;
        padding: 0 20px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        box-shadow: 0 1px 8px rgba(10,46,92,0.06);
        position: relative;
        z-index: 20;
    ">
        {{-- Accent line atas --}}
        <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#0a2e5c,#0073e6,#5cc2f2);"></div>

        {{-- Logo + nama instansi --}}
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#0a2e5c 0%,#0073e6 100%);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 10px rgba(0,115,230,0.25);">
                <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo BPMP" style="width:26px;height:26px;object-fit:contain;border-radius:8px;">
            </div>
            <div style="display:flex;flex-direction:column;line-height:1.25;">
                <span style="font-size:13px;font-weight:800;color:#0a2e5c;letter-spacing:-0.01em;">BPMP Provinsi Gorontalo</span>
                <span style="font-size:10px;font-weight:600;color:#0073e6;letter-spacing:0.04em;">Sistem Informasi Keluar Masuk</span>
            </div>
        </div>

        {{-- Kanan: tanggal + status sistem + logout --}}
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:11px;color:#94a3b8;font-weight:500;" id="header-date"></span>
            <div style="display:flex;align-items:center;gap:5px;padding:4px 10px;border-radius:99px;background:#f0fdf4;border:1px solid #bbf7d0;">
                <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;box-shadow:0 0 0 2px rgba(22,163,74,0.2);"></span>
                <span style="font-size:10px;font-weight:700;color:#16a34a;letter-spacing:0.04em;">SISTEM AKTIF</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#94a3b8;cursor:pointer;background:none;border:1px solid transparent;padding:4px 10px;border-radius:7px;transition:all 0.15s;"
                    onmouseover="this.style.background='#fef2f2';this.style.color='#dc2626';this.style.borderColor='#fecaca';"
                    onmouseout="this.style.background='transparent';this.style.color='#94a3b8';this.style.borderColor='transparent';">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </form>
        </div>
    </header>

    {{-- ══ BODY ═════════════════════════════════════════════════════════════ --}}
    <div style="display:flex;flex:1;overflow:hidden;">

        {{-- SIDEBAR --}}
        @include('components.sidebar')

        <div style="display:flex;flex-direction:column;flex:1;overflow:hidden;">

            {{-- MAIN CONTENT --}}
            <main style="flex:1;overflow-y:auto;padding:22px 24px;background:#f0f7ff;">
                @yield('content')
            </main>

        </div>
    </div>

    {{-- ══ FOOTER ═══════════════════════════════════════════════════════════ --}}
    <footer style="
        background: #ffffff;
        border-top: 1px solid #e8f0fe;
        padding: 7px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    ">
        <span style="font-size:11px;color:#94a3b8;">
            &copy; {{ date('Y') }} <span style="color:#0073e6;font-weight:600;">BPMP Provinsi Gorontalo</span> — Kementerian Pendidikan Dasar dan Menengah
        </span>
        <span style="font-size:11px;color:#cbd5e1;font-weight:500;">SIKMA v2.0.0</span>
    </footer>

    <script>
        // Tanggal di header
        const d = new Date();
        const opts = { weekday:'long', day:'numeric', month:'long', year:'numeric' };
        document.getElementById('header-date').textContent = d.toLocaleDateString('id-ID', opts);

        // Cegah BFCache
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) window.location.reload();
        });

        // Cegah tombol back browser — arahkan ke logout
        history.pushState(null, '', window.location.href);
        window.addEventListener('popstate', function () {
            history.pushState(null, '', window.location.href);
            document.getElementById('logout-form').submit();
        });
    </script>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>

    @stack('scripts')
</body>
</html>
