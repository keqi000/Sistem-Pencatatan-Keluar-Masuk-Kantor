<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Layar Lobby — SIKMA</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-bpmp.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    @vite(['resources/css/app.css'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

        html, body {
            width: 100%; height: 100%;
            background-color: #f0f7ff;
            background-image:
                radial-gradient(ellipse 70% 60% at 0% 0%, rgba(92,194,242,0.2) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 100% 100%, rgba(0,115,230,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 50% 50%, rgba(219,238,255,0.5) 0%, transparent 70%);
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }

        .lobby-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            padding: 16px;
            gap: 0;
        }

        .card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(10,46,92,0.12), 0 0 0 1px rgba(92,194,242,0.2);
            padding: clamp(16px, 3vh, 32px) clamp(20px, 4vw, 40px);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(12px, 2vh, 20px);
            width: 100%;
            max-width: 480px;
        }

        .qr-frame {
            background: #ffffff;
            border-radius: 20px;
            padding: 12px;
            box-shadow: 0 4px 24px rgba(10,46,92,0.1), 0 0 0 1px rgba(92,194,242,0.2);
            position: relative;
        }

        /* Sudut QR frame */
        .qr-frame::before, .qr-frame::after {
            content: '';
            position: absolute;
            width: 20px; height: 20px;
            border-color: #0073e6;
            border-style: solid;
            z-index: 2;
        }
        .qr-frame::before { top: -1px; left: -1px; border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
        .qr-frame::after  { bottom: -1px; right: -1px; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }

        #qr-container {
            width: clamp(160px, 22vh, 220px);
            height: clamp(160px, 22vh, 220px);
        }

        .countdown-ring {
            position: relative;
            width: clamp(52px, 7vh, 72px);
            height: clamp(52px, 7vh, 72px);
        }
        .countdown-ring svg {
            transform: rotate(-90deg);
            width: 100%; height: 100%;
        }
        .countdown-ring .num {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(16px, 2.5vh, 22px);
            font-weight: 800;
            color: #0a2e5c;
        }

        /* Mobile */
        @media (max-width: 480px) {
            .lobby-wrapper { padding: 12px; }
            .card { border-radius: 18px; }
            #qr-container { width: 180px !important; height: 180px !important; }
            .countdown-ring { width: 56px !important; height: 56px !important; }
            .countdown-ring .num { font-size: 18px !important; }
        }
    </style>
</head>
<body>

    {{-- Orbs --}}
    <div class="orb" style="width:500px;height:500px;top:-150px;left:-150px;background:rgba(92,194,242,0.15);"></div>
    <div class="orb" style="width:400px;height:400px;bottom:-100px;right:-100px;background:rgba(0,115,230,0.08);"></div>

    {{-- Dot grid --}}
    <div style="position:fixed;inset:0;background-image:radial-gradient(circle,rgba(92,194,242,0.15) 1px,transparent 1px);background-size:32px 32px;opacity:0.4;pointer-events:none;z-index:0;"></div>

    <div class="lobby-wrapper">

        {{-- Header instansi --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,115,230,0.3);">
                <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo" style="width:28px;height:28px;object-fit:contain;">
            </div>
            <div>
                <p style="font-size:14px;font-weight:700;color:#0a2e5c;">BPMP Provinsi Gorontalo</p>
                <p style="font-size:11px;color:#5cc2f2;font-weight:500;">Sistem Informasi Keluar Masuk</p>
            </div>
        </div>

        {{-- Card utama --}}
        <div class="card">

            {{-- Badge QR Keluar --}}
            <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:99px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;box-shadow:0 3px 10px rgba(0,115,230,0.3);">
                    <span style="width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,0.8);display:inline-block;"></span>
                    QR Keluar
                </span>
                <p style="font-size:12px;color:#64748b;">Scan QR ini sebelum meninggalkan kantor</p>
            </div>

            {{-- QR Code --}}
            <div class="qr-frame">
                <div id="qr-container" style="width:220px;height:220px;display:flex;align-items:center;justify-content:center;">
                    <p style="font-size:12px;color:#94a3b8;">Memuat QR...</p>
                </div>
            </div>

            {{-- Countdown ring + jam --}}
            <div style="display:flex;align-items:center;gap:24px;">
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                    <div class="countdown-ring">
                        <svg width="72" height="72" viewBox="0 0 72 72">
                            <circle cx="36" cy="36" r="30" fill="none" stroke="#dbeeff" stroke-width="5"/>
                            <circle id="ring-progress" cx="36" cy="36" r="30" fill="none"
                                stroke="url(#ringGrad)" stroke-width="5"
                                stroke-linecap="round"
                                stroke-dasharray="188.5"
                                stroke-dashoffset="0"/>
                            <defs>
                                <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#0073e6"/>
                                    <stop offset="100%" stop-color="#5cc2f2"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="num" id="countdown">--</div>
                    </div>
                    <p style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">detik</p>
                </div>

                <div style="width:1px;height:48px;background:#dbeeff;"></div>

                <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                    <p style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Waktu</p>
                    <p id="clock" style="font-size:22px;font-weight:700;color:#0a2e5c;letter-spacing:0.04em;font-variant-numeric:tabular-nums;"></p>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div style="margin-top:16px;display:flex;align-items:center;gap:16px;">
            <form method="POST" action="/logout" style="margin:0;">
                @csrf
                <button type="submit"
                    style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:99px;font-size:12px;font-weight:600;color:#0073e6;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);cursor:pointer;transition:all 0.15s;"
                    onmouseover="this.style.background='#c7e2ff'" onmouseout="this.style.background='#dbeeff'">
                    ← Kembali ke Login
                </button>
            </form>
            <p style="font-size:11px;color:#94a3b8;">SIKMA v2.0.0 &copy; {{ date('Y') }}</p>
        </div>

    </div>

    <script>
    const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    };

    let countdownVal = 10;
    let maxVal = 10;
    let countdownTimer = null;
    const circumference = 188.5;

    function updateClock() {
        document.getElementById('clock').textContent =
            new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    function updateRing() {
        const progress = countdownVal / maxVal;
        const offset = circumference * (1 - progress);
        document.getElementById('ring-progress').style.strokeDashoffset = offset;
        document.getElementById('countdown').textContent = countdownVal;
    }

    async function fetchQR() {
        try {
            const res = await fetch('/api/qr/current?jenis=keluar');
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json?.token) throw new Error('token tidak ada di response');

            const container = document.getElementById('qr-container');
            const size = container.offsetWidth || 200;
            container.innerHTML = '<div id="qr-canvas"></div>';

            new QRCode(document.getElementById('qr-canvas'), {
                text: json.token,
                width: size,
                height: size,
                colorDark: '#0a2e5c',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });

            maxVal = json.interval ?? 10;
            countdownVal = json.remaining_seconds ?? maxVal;
            updateRing();
            startCountdown();
        } catch (e) {
            document.getElementById('qr-container').innerHTML =
                '<p style="font-size:12px;color:#dc2626;">Gagal memuat QR: ' + e.message + '</p>';
            setTimeout(fetchQR, 3000);
        }
    }

    function startCountdown() {
        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            countdownVal--;
            updateRing();
            if (countdownVal <= 0) {
                clearInterval(countdownTimer);
                fetchQR();
            }
        }, 1000);
    }

    fetchQR();

    // Cegah tombol back browser — arahkan ke logout
    history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, '', window.location.href);
        document.getElementById('logout-form').submit();
    });
    </script>

    <form id="logout-form" method="POST" action="/logout" style="display:none;">
        @csrf
    </form>
</body>
</html>
