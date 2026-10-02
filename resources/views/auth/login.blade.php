<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIKMA BPMP Gorontalo</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-bpmp.png') }}">
    @vite(['resources/css/app.css'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

        html {
            height: 100%;
            overflow: hidden;
        }

        body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #f0f7ff;
            background-image:
                radial-gradient(ellipse 70% 50% at 10% 20%, rgba(92,194,242,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 90% 80%, rgba(0,115,230,0.1) 0%, transparent 60%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Wrapper utama ── */
        .login-wrapper {
            width: 100%;
            display: flex;
        }

        /* Semua ukuran: selalu card */
        body { padding: 20px; }

        .login-wrapper {
            max-width: 480px;
            min-height: auto;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 16px 48px rgba(10,46,92,0.15), 0 0 0 1px rgba(92,194,242,0.2);
        }

        @media (max-width: 400px) {
            body { padding: 12px; }
        }

        /* Desktop: lebih lebar dengan panel kiri */
        @media (min-width: 1024px) {
            body { padding: 48px; }
            .login-wrapper {
                max-width: 780px;
                border-radius: 24px;
            }
        }

        /* ── Panel Kiri ── */
        .panel-left {
            display: none;
            width: 40%;
            flex-direction: column;
            justify-content: space-between;
            padding: 28px 32px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(145deg, #0a2e5c 0%, #0d3a73 55%, #0073e6 100%);
        }
        @media (min-width: 1024px) {
            .panel-left { display: flex; }
        }

        /* ── Panel Kanan ── */
        .panel-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
            padding: 28px 24px;
        }
        @media (min-width: 1024px) {
            .panel-right { padding: 28px 36px; }
        }

        .dot-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(92,194,242,0.25) 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.1;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            pointer-events: none;
        }

        .input-field {
            width: 100%;
            background: #f8fbff;
            border: 1.5px solid #dbeeff;
            color: #0a2e5c;
            border-radius: 12px;
            padding: 10px 16px 10px 40px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .input-field::placeholder { color: #94a3b8; }
        .input-field:focus {
            outline: none;
            background: #ffffff;
            border-color: #5cc2f2;
            box-shadow: 0 0 0 3px rgba(92,194,242,0.15);
        }

        .btn-login {
            width: 100%;
            padding: 11px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0073e6 0%, #0056b3 100%);
            color: white;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.03em;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(0,115,230,0.3);
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(0,115,230,0.4);
        }
        .btn-login:active { transform: translateY(0); }

        .line-deco {
            height: 2px;
            width: 40px;
            border-radius: 99px;
            background: linear-gradient(90deg, rgba(92,194,242,0.8), rgba(0,115,230,0.4), transparent);
            margin-bottom: 12px;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">

        {{-- ═══ PANEL KIRI ═══ --}}
        <div class="panel-left">
            <div class="dot-grid"></div>

            {{-- Dekorasi --}}
            <div class="orb" style="width:280px;height:280px;bottom:-80px;right:-80px;background:radial-gradient(circle,rgba(92,194,242,0.15),transparent 70%);"></div>
            <div class="orb" style="width:160px;height:160px;top:30%;left:-50px;border:1px solid rgba(92,194,242,0.08);filter:none;background:transparent;"></div>

            {{-- Logo --}}
            <div style="position:relative;display:flex;align-items:center;gap:12px;">
                <div style="width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);flex-shrink:0;">
                    <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo BPMP" style="width:32px;height:32px;object-fit:contain;border-radius:10px;">
                </div>
                <div>
                    <p style="color:white;font-weight:700;font-size:13px;">BPMP Provinsi</p>
                    <p style="color:#5cc2f2;font-weight:600;font-size:13px;">Gorontalo</p>
                </div>
            </div>

            {{-- Konten --}}
            <div style="position:relative;display:flex;flex-direction:column;gap:20px;">
                <div>
                    <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:99px;font-size:11px;font-weight:600;background:rgba(92,194,242,0.15);border:1px solid rgba(92,194,242,0.3);color:#5cc2f2;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#5cc2f2;display:inline-block;"></span>
                        Sistem Aktif
                    </span>
                </div>
                <div>
                    <h1 style="color:white;font-weight:800;font-size:1.6rem;line-height:1.2;letter-spacing:-0.02em;margin-bottom:10px;">
                        Sistem<br>Informasi<br>
                        <span style="background:linear-gradient(90deg,#5cc2f2,#7dd3fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                            Keluar Masuk
                        </span>
                    </h1>
                    <div class="line-deco"></div>
                    <p style="font-size:13px;line-height:1.6;color:rgba(255,255,255,0.5);">
                        Pencatatan aktivitas pegawai berbasis
                        <span style="color:#5cc2f2;">QR Code dinamis</span>
                        yang berganti otomatis setiap 30 detik.
                    </p>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    @foreach(['QR Code Sesaat (Dynamic)', 'Persetujuan Izin Dinas', 'Dashboard Real-time'] as $f)
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:rgba(92,194,242,0.2);border:1px solid rgba(92,194,242,0.35);">
                            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="#5cc2f2" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span style="font-size:12px;color:rgba(255,255,255,0.55);">{{ $f }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <p style="position:relative;font-size:11px;color:rgba(255,255,255,0.2);">SIKMA v2.0.0 &copy; {{ date('Y') }}</p>
        </div>

        {{-- ═══ PANEL KANAN ═══ --}}
        <div class="panel-right">

            {{-- Mobile header --}}
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:32px;" class="lg:hidden">
                <div style="width:40px;height:40px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);flex-shrink:0;">
                    <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo" style="width:24px;height:24px;object-fit:contain;border-radius:8px;">
                </div>
                <div>
                    <p style="color:#0a2e5c;font-weight:700;font-size:13px;">BPMP Provinsi Gorontalo</p>
                    <p style="color:#64748b;font-size:11px;">SIKMA v2.0.0</p>
                </div>
            </div>

            {{-- Heading --}}
            <div style="margin-bottom:20px;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#5cc2f2;margin-bottom:6px;">
                    Selamat Datang
                </p>
                <h2 style="font-size:1.4rem;font-weight:800;color:#0a2e5c;letter-spacing:-0.02em;margin-bottom:4px;">
                    Masuk ke SIKMA
                </h2>
                <p style="font-size:13px;color:#64748b;">
                    Gunakan akun kepegawaian yang diberikan admin
                </p>
            </div>

            {{-- Error --}}
            @if($errors->any())
            <div style="margin-bottom:20px;padding:12px 16px;border-radius:12px;display:flex;align-items:flex-start;gap:10px;font-size:13px;background:#fef2f2;border:1.5px solid #fecaca;color:#dc2626;">
                <span>⚠</span>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <form method="POST" action="/login" style="display:flex;flex-direction:column;gap:12px;">
                @csrf

                {{-- Username --}}
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <label for="username" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">
                        Username
                    </label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#5cc2f2;pointer-events:none;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input id="username" type="text" name="username"
                               value="{{ old('username') }}"
                               required autofocus autocomplete="username"
                               placeholder="Masukkan username"
                               class="input-field">
                    </div>
                </div>

                {{-- Password --}}
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <label for="password" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">
                        Kata Sandi
                    </label>
                    <div style="position:relative;">
                        <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#5cc2f2;pointer-events:none;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="password" type="password" name="password"
                               required autocomplete="current-password"
                               placeholder="Masukkan kata sandi"
                               class="input-field" style="padding-right:40px;">
                        <button type="button" onclick="togglePassword()"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;display:flex;align-items:center;transition:color 0.15s;"
                            onmouseover="this.style.color='#0073e6'" onmouseout="this.style.color='#94a3b8'">
                            {{-- Icon mata tertutup (default: password tersembunyi) --}}
                            <svg id="icon-eye-off" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                            {{-- Icon mata terbuka (saat password terlihat) --}}
                            <svg id="icon-eye" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember --}}
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none;">
                    <input type="checkbox" name="remember" style="width:15px;height:15px;accent-color:#0073e6;border-radius:4px;">
                    <span style="font-size:13px;color:#64748b;">Ingat saya</span>
                </label>

                <button type="submit" class="btn-login">Masuk ke Sistem</button>
            </form>

            {{-- Divider --}}
            <div style="display:flex;align-items:center;gap:12px;margin:14px 0;">
                <div style="flex:1;height:1px;background:#dbeeff;"></div>
                <span style="font-size:11px;color:#94a3b8;">BPMP Gorontalo</span>
                <div style="flex:1;height:1px;background:#dbeeff;"></div>
            </div>

            <p style="font-size:12px;text-align:center;color:#94a3b8;">
                Lupa akun? Hubungi
                <span style="font-weight:600;color:#0073e6;">Admin Kepegawaian</span>
            </p>
        </div>

    </div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const eyeOff = document.getElementById('icon-eye-off');
    const eye    = document.getElementById('icon-eye');
    const show   = input.type === 'password';
    input.type       = show ? 'text' : 'password';
    eyeOff.style.display = show ? 'none'  : 'block';
    eye.style.display    = show ? 'block' : 'none';
}
</script>
</body>
</html>
