<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Layar Pos Security — SIKMA</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-bpmp.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    @vite(['resources/css/app.css'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

        html, body {
            width: 100%; height: 100%;
            overflow: hidden;
            background-color: #f0f7ff;
            background-image:
                radial-gradient(ellipse 70% 60% at 0% 0%, rgba(92,194,242,0.2) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 100% 100%, rgba(0,115,230,0.12) 0%, transparent 60%);
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }

        .pos-wrapper {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
        }

        /* ── Header bar ── */
        .pos-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 24px;
            background: #ffffff;
            border-bottom: 1px solid rgba(92,194,242,0.25);
            box-shadow: 0 1px 8px rgba(10,46,92,0.06);
            flex-shrink: 0;
            position: relative;
        }
        .pos-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, #0a2e5c, #0073e6, #5cc2f2);
        }

        /* ── Body split ── */
        .pos-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            gap: 0;
        }

        /* ── Kiri: QR ── */
        .pos-qr-side {
            width: 340px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 24px;
            border-right: 1px solid rgba(92,194,242,0.2);
        }

        .qr-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(10,46,92,0.1), 0 0 0 1px rgba(92,194,242,0.2);
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            width: 100%;
        }

        .qr-frame {
            background: #ffffff;
            border-radius: 16px;
            padding: 12px;
            box-shadow: 0 4px 20px rgba(10,46,92,0.08), 0 0 0 1px rgba(92,194,242,0.2);
            position: relative;
        }
        .qr-frame::before, .qr-frame::after {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            border-color: #0073e6;
            border-style: solid;
        }
        .qr-frame::before { top: 5px; left: 5px; border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
        .qr-frame::after  { bottom: 5px; right: 5px; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }

        .countdown-ring { position: relative; width: 64px; height: 64px; }
        .countdown-ring svg { transform: rotate(-90deg); }
        .countdown-ring .num {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 800; color: #0a2e5c;
        }

        /* ── Kanan: Tabel ── */
        .pos-daftar-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px 24px;
            overflow: hidden;
        }

        .daftar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-shrink: 0;
        }

        .table-wrap {
            flex: 1;
            overflow-y: auto;
            border-radius: 16px;
            border: 1px solid rgba(92,194,242,0.2);
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(10,46,92,0.05);
        }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            padding: 10px 14px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            background: #f8fbff;
            border-bottom: 1px solid #dbeeff;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        tbody tr {
            border-bottom: 1px solid #f0f7ff;
            transition: background 0.15s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f8fbff; }

        tbody td {
            padding: 10px 14px;
            font-size: 12px;
            color: #0a2e5c;
            vertical-align: middle;
        }

        .highlight-new { animation: highlightRow 3s ease-out; }
        @keyframes highlightRow {
            0%   { background: rgba(92,194,242,0.15); }
            100% { background: transparent; }
        }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #dbeeff; border-radius: 99px; }
    </style>
</head>
<body>

    <div class="orb" style="width:500px;height:500px;top:-150px;left:-150px;background:rgba(92,194,242,0.12);"></div>
    <div class="orb" style="width:400px;height:400px;bottom:-100px;right:-100px;background:rgba(0,115,230,0.07);"></div>
    <div style="position:fixed;inset:0;background-image:radial-gradient(circle,rgba(92,194,242,0.12) 1px,transparent 1px);background-size:32px 32px;opacity:0.4;pointer-events:none;z-index:0;"></div>

    <div class="pos-wrapper">

        {{-- Header --}}
        <div class="pos-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(0,115,230,0.25);">
                    <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo" style="width:22px;height:22px;object-fit:contain;">
                </div>
                <div>
                    <p style="font-size:13px;font-weight:700;color:#0a2e5c;">BPMP Provinsi Gorontalo</p>
                    <p style="font-size:10px;color:#5cc2f2;font-weight:500;">Pos Security — Layar Monitor</p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:12px;">
                <div style="display:flex;align-items:center;gap:5px;padding:4px 10px;border-radius:99px;background:#f0fdf4;border:1px solid #bbf7d0;">
                    <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;box-shadow:0 0 0 2px rgba(22,163,74,0.2);"></span>
                    <span style="font-size:10px;font-weight:700;color:#16a34a;letter-spacing:0.04em;">SISTEM AKTIF</span>
                </div>
                <span style="font-size:11px;color:#94a3b8;font-weight:500;" id="header-date"></span>
                <form method="POST" action="/logout" style="margin:0;">
                    @csrf
                    <button type="submit"
                        style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#94a3b8;cursor:pointer;background:none;border:1px solid transparent;padding:4px 10px;border-radius:7px;transition:all 0.15s;"
                        onmouseover="this.style.background='#fef2f2';this.style.color='#dc2626';this.style.borderColor='#fecaca';"
                        onmouseout="this.style.background='transparent';this.style.color='#94a3b8';this.style.borderColor='transparent';">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        {{-- Body --}}
        <div class="pos-body">

            {{-- Kiri: QR Masuk --}}
            <div class="pos-qr-side">
                <div class="qr-card">

                    <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:linear-gradient(135deg,#16a34a,#15803d);color:white;box-shadow:0 3px 10px rgba(22,163,74,0.3);">
                            <span style="width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,0.8);display:inline-block;"></span>
                            QR Masuk
                        </span>
                        <p style="font-size:11px;color:#64748b;">Scan QR ini saat kembali ke kantor</p>
                    </div>

                    <div class="qr-frame">
                        <div id="qr-container" style="width:200px;height:200px;display:flex;align-items:center;justify-content:center;">
                            <p style="font-size:12px;color:#94a3b8;">Memuat QR...</p>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:20px;">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                            <div class="countdown-ring">
                                <svg width="64" height="64" viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="27" fill="none" stroke="#dbeeff" stroke-width="5"/>
                                    <circle id="ring-progress" cx="32" cy="32" r="27" fill="none"
                                        stroke="url(#ringGrad)" stroke-width="5"
                                        stroke-linecap="round"
                                        stroke-dasharray="169.6"
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

                        <div style="width:1px;height:44px;background:#dbeeff;"></div>

                        <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                            <p style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Waktu</p>
                            <p id="clock" style="font-size:20px;font-weight:700;color:#0a2e5c;letter-spacing:0.04em;font-variant-numeric:tabular-nums;"></p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Kanan: Daftar Pindaian --}}
            <div class="pos-daftar-side">
                <div class="daftar-header">
                    <div>
                        <p style="font-size:15px;font-weight:800;color:#0a2e5c;">Pindaian Hari Ini</p>
                        <p style="font-size:11px;color:#64748b;margin-top:1px;">Diperbarui otomatis setiap 10 detik</p>
                    </div>
                    <span id="total-pindaian" style="font-size:12px;font-weight:600;color:#0073e6;padding:4px 12px;border-radius:99px;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);"></span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Unit</th>
                                <th>Jam</th>
                                <th>Jenis</th>
                                <th>Keperluan</th>
                            </tr>
                        </thead>
                        <tbody id="pindaian-tbody">
                            <tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <form id="logout-form" method="POST" action="/logout" style="display:none;">
        @csrf
    </form>

    <script>
    let countdownVal = 10, maxVal = 10, countdownTimer = null;
    let lastPindaianCount = 0;
    const circumference = 169.6;

    // Tanggal header
    document.getElementById('header-date').textContent =
        new Date().toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

    // Jam
    function updateClock() {
        document.getElementById('clock').textContent =
            new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Ring countdown
    function updateRing() {
        const offset = circumference * (1 - countdownVal / maxVal);
        document.getElementById('ring-progress').style.strokeDashoffset = offset;
        document.getElementById('countdown').textContent = countdownVal;
    }

    // QR
    async function fetchQR() {
        try {
            const res = await fetch('/api/qr/current?jenis=masuk');
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json?.token) return;

            const container = document.getElementById('qr-container');
            container.innerHTML = '<div id="qr-canvas"></div>';
            new QRCode(document.getElementById('qr-canvas'), {
                text: json.token,
                width: 200,
                height: 200,
                colorDark: '#0a2e5c',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });

            maxVal = json.interval ?? 10;
            countdownVal = json.remaining_seconds ?? maxVal;
            updateRing();
            startCountdown();
        } catch (_) {}
    }

    function startCountdown() {
        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            countdownVal--;
            updateRing();
            if (countdownVal <= 0) { clearInterval(countdownTimer); fetchQR(); }
        }, 1000);
    }

    // Pindaian
    async function fetchPindaian() {
        try {
            const res = await fetch('/api/pindaian/pos-hari-ini');
            const json = await res.json();
            const list = json.data ?? [];

            document.getElementById('total-pindaian').textContent = `${list.length} pindaian`;

            const isNew = list.length > lastPindaianCount;
            lastPindaianCount = list.length;

            const tbody = document.getElementById('pindaian-tbody');
            if (!list.length) {
                tbody.innerHTML = `<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Belum ada pindaian hari ini</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map((p, i) => {
                const isKeluar = p.jenis === 'keluar';
                const badgeBg    = isKeluar ? '#fff7ed' : '#f0fdf4';
                const badgeColor = isKeluar ? '#ea580c' : '#16a34a';
                const badgeBorder= isKeluar ? '#fed7aa' : '#bbf7d0';
                return `
                <tr class="${i === 0 && isNew ? 'highlight-new' : ''}">
                    <td style="font-weight:600;color:#0a2e5c;">${p.nama_lengkap ?? '-'}</td>
                    <td style="color:#64748b;font-size:11px;">${p.nama_unit ?? '-'}</td>
                    <td style="color:#0073e6;font-weight:600;font-variant-numeric:tabular-nums;">${p.jam ? p.jam.substring(11,19) : '-'}</td>
                    <td>
                        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${badgeBg};color:${badgeColor};border:1px solid ${badgeBorder};">
                            ${isKeluar ? 'Keluar' : 'Masuk'}
                        </span>
                    </td>
                    <td style="color:#64748b;font-size:11px;text-transform:capitalize;">${p.keperluan_jenis?.replace('_',' ') ?? '-'}</td>
                </tr>`;
            }).join('');
        } catch (_) {}
    }

    fetchQR();
    fetchPindaian();
    setInterval(fetchPindaian, 10000);

    // Cegah tombol back browser → logout
    history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, '', window.location.href);
        document.getElementById('logout-form').submit();
    });
    </script>
</body>
</html>
