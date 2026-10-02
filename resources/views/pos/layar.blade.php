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
            background-color: #f0f7ff;
        }

        .pos-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .pos-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            background: #ffffff;
            border-bottom: 1px solid rgba(92,194,242,0.25);
            box-shadow: 0 1px 8px rgba(10,46,92,0.06);
            flex-shrink: 0;
            position: relative;
            z-index: 10;
        }
        .pos-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, #0a2e5c, #0073e6, #5cc2f2);
        }

        /* Body — desktop: split kiri-kanan, mobile: stack atas-bawah */
        .pos-body {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .pos-qr-side {
            width: 320px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 20px 16px;
            border-right: 1px solid rgba(92,194,242,0.2);
            background: #f8fbff;
        }

        .qr-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(10,46,92,0.1), 0 0 0 1px rgba(92,194,242,0.2);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            width: 100%;
        }

        .qr-frame {
            background: #ffffff;
            border-radius: 14px;
            padding: 10px;
            box-shadow: 0 4px 16px rgba(10,46,92,0.08), 0 0 0 1px rgba(92,194,242,0.2);
            position: relative;
        }
        .qr-frame::before, .qr-frame::after {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            border-color: #0073e6; border-style: solid;
            z-index: 2;
        }
        .qr-frame::before { top: -1px; left: -1px; border-width: 3px 0 0 3px; border-radius: 3px 0 0 0; }
        .qr-frame::after  { bottom: -1px; right: -1px; border-width: 0 3px 3px 0; border-radius: 0 0 3px 0; }

        .countdown-ring { position: relative; width: 56px; height: 56px; }
        .countdown-ring svg { transform: rotate(-90deg); }
        .countdown-ring .num {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 800; color: #0a2e5c;
        }

        .pos-daftar-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 16px;
            overflow: hidden;
            min-width: 0;
        }

        .table-wrap {
            flex: 1;
            overflow-y: auto;
            border-radius: 14px;
            border: 1px solid rgba(92,194,242,0.2);
            background: #ffffff;
            box-shadow: 0 2px 12px rgba(10,46,92,0.05);
        }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            padding: 9px 12px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: #94a3b8; background: #f8fbff;
            border-bottom: 1px solid #dbeeff;
            text-align: left; position: sticky; top: 0; z-index: 1;
        }
        tbody tr { border-bottom: 1px solid #f0f7ff; transition: background 0.15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f8fbff; }
        tbody td { padding: 9px 12px; font-size: 12px; color: #0a2e5c; vertical-align: middle; }

        .highlight-new { animation: highlightRow 3s ease-out; }
        @keyframes highlightRow {
            0%   { background: rgba(92,194,242,0.15); }
            100% { background: transparent; }
        }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #dbeeff; border-radius: 99px; }

        /* Mobile */
        @media (max-width: 768px) {
            .pos-body {
                flex-direction: column;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .pos-qr-side {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid rgba(92,194,242,0.2);
                padding: 12px 16px;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 12px;
                flex-shrink: 0;
            }
            .qr-card {
                flex-direction: row;
                align-items: center;
                gap: 12px;
                padding: 12px 14px;
                width: 100%;
            }
            .qr-card > div:first-child { display: none; }
            .qr-frame { padding: 6px; flex-shrink: 0; }
            #qr-container { width: 110px !important; height: 110px !important; }
            .countdown-ring { width: 44px; height: 44px; }
            .countdown-ring svg { width: 44px; height: 44px; }
            .countdown-ring .num { font-size: 14px; }
            .pos-daftar-side {
                padding: 12px;
                overflow: visible;
                flex: none;
            }
            .table-wrap {
                flex: none;
                overflow-y: visible;
                max-height: none;
            }
            thead th { font-size: 9px; padding: 7px 8px; }
            tbody td { font-size: 11px; padding: 7px 8px; }
            .hide-mobile { display: none; }
            .pos-header-date { display: none; }
            .qr-info-row {
                flex-direction: column !important;
                gap: 6px !important;
                flex: 1;
                min-width: 0;
            }
            .qr-info-row > div[style*="width:1px"] { display: none; }
            .qr-info-row #clock { font-size: 14px !important; }
            .qr-info-row p[style*="Waktu"] { font-size: 8px !important; }
        }
    </style>
</head>
<body>

    <div class="pos-wrapper">

        {{-- Header --}}
        <div class="pos-header">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,115,230,0.25);">
                    <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo" style="width:20px;height:20px;object-fit:contain;">
                </div>
                <div>
                    <p style="font-size:12px;font-weight:700;color:#0a2e5c;">BPMP Provinsi Gorontalo</p>
                    <p style="font-size:10px;color:#5cc2f2;font-weight:500;">Pos Security</p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;">
                <div style="display:flex;align-items:center;gap:4px;padding:3px 8px;border-radius:99px;background:#f0fdf4;border:1px solid #bbf7d0;">
                    <span style="width:5px;height:5px;border-radius:50%;background:#16a34a;display:inline-block;"></span>
                    <span style="font-size:10px;font-weight:700;color:#16a34a;">AKTIF</span>
                </div>
                <span class="pos-header-date" style="font-size:11px;color:#94a3b8;" id="header-date"></span>
                <form method="POST" action="/logout" style="margin:0;">
                    @csrf
                    <button type="submit"
                        style="display:flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#94a3b8;cursor:pointer;background:none;border:1px solid transparent;padding:4px 8px;border-radius:7px;transition:all 0.15s;"
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

            {{-- QR Side --}}
            <div class="pos-qr-side">
                <div class="qr-card">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;background:linear-gradient(135deg,#16a34a,#15803d);color:white;box-shadow:0 2px 8px rgba(22,163,74,0.3);">
                            <span style="width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,0.8);display:inline-block;"></span>
                            QR Masuk
                        </span>
                        <p style="font-size:10px;color:#64748b;text-align:center;">Scan saat kembali ke kantor</p>
                    </div>

                    <div class="qr-frame">
                        <div id="qr-container" style="width:180px;height:180px;display:flex;align-items:center;justify-content:center;">
                            <p style="font-size:12px;color:#94a3b8;">Memuat QR...</p>
                        </div>
                    </div>

                    <div class="qr-info-row" style="display:flex;align-items:center;gap:16px;">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:3px;">
                            <div class="countdown-ring">
                                <svg width="56" height="56" viewBox="0 0 56 56">
                                    <circle cx="28" cy="28" r="23" fill="none" stroke="#dbeeff" stroke-width="4"/>
                                    <circle id="ring-progress" cx="28" cy="28" r="23" fill="none"
                                        stroke="url(#ringGrad)" stroke-width="4"
                                        stroke-linecap="round"
                                        stroke-dasharray="144.5"
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
                            <p style="font-size:9px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.07em;">detik</p>
                        </div>
                        <div style="width:1px;height:40px;background:#dbeeff;"></div>
                        <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                            <p style="font-size:9px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.07em;">Waktu</p>
                            <p id="clock" style="font-size:18px;font-weight:700;color:#0a2e5c;letter-spacing:0.04em;font-variant-numeric:tabular-nums;"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Daftar --}}
            <div class="pos-daftar-side">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-shrink:0;">
                    <div>
                        <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Pindaian Hari Ini</p>
                        <p style="font-size:10px;color:#64748b;margin-top:1px;">Diperbarui otomatis setiap 10 detik</p>
                    </div>
                    <span id="total-pindaian" style="font-size:11px;font-weight:600;color:#0073e6;padding:3px 10px;border-radius:99px;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);"></span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th class="hide-mobile">Unit</th>
                                <th>Keluar</th>
                                <th>Kembali</th>
                                <th class="hide-mobile">Keperluan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="pindaian-tbody">
                            <tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;">Memuat data...</td></tr>
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
    const circumference = 144.5;

    const isMobile = () => window.innerWidth <= 768;

    document.getElementById('header-date').textContent =
        new Date().toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' });

    function updateClock() {
        document.getElementById('clock').textContent =
            new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    function updateRing() {
        const offset = circumference * (1 - countdownVal / maxVal);
        document.getElementById('ring-progress').style.strokeDashoffset = offset;
        document.getElementById('countdown').textContent = countdownVal;
    }

    async function fetchQR() {
        try {
            const res = await fetch('/api/qr/current?jenis=masuk');
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json?.token) return;

            const size = isMobile() ? 120 : 180;
            const container = document.getElementById('qr-container');
            container.style.width = size + 'px';
            container.style.height = size + 'px';
            container.innerHTML = '<div id="qr-canvas"></div>';
            new QRCode(document.getElementById('qr-canvas'), {
                text: json.token,
                width: size, height: size,
                colorDark: '#0a2e5c', colorLight: '#ffffff',
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

    async function fetchPindaian() {
        try {
            const res = await fetch('/api/pindaian/pos-hari-ini');
            const json = await res.json();
            const list = json.data ?? [];

            document.getElementById('total-pindaian').textContent = `${list.length} catatan`;

            const isNew = list.length > lastPindaianCount;
            lastPindaianCount = list.length;

            const tbody = document.getElementById('pindaian-tbody');
            if (!list.length) {
                tbody.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Belum ada pindaian hari ini</td></tr>`;
                return;
            }

            const mobile = isMobile();
            tbody.innerHTML = list.map((p, i) => {
                const statusMap = {
                    terbuka:       { bg:'#fff7ed', color:'#ea580c', border:'#fed7aa', label:'Di Luar' },
                    kembali:       { bg:'#f0fdf4', color:'#16a34a', border:'#bbf7d0', label:'Kembali' },
                    belum_kembali: { bg:'#fef2f2', color:'#dc2626', border:'#fecaca', label:'Belum' },
                };
                const s = statusMap[p.status] ?? { bg:'#f8fbff', color:'#64748b', border:'#dbeeff', label: p.status };
                return `
                <tr class="${i === 0 && isNew ? 'highlight-new' : ''}">
                    <td style="font-weight:600;color:#0a2e5c;">${p.nama_lengkap ?? '-'}</td>
                    ${mobile ? '' : `<td style="color:#64748b;font-size:11px;">${p.nama_unit ?? '-'}</td>`}
                    <td style="color:#0073e6;font-weight:600;font-variant-numeric:tabular-nums;">${p.jam_keluar ?? '-'}</td>
                    <td style="color:#16a34a;font-weight:600;font-variant-numeric:tabular-nums;">${p.jam_kembali ?? '<span style="color:#94a3b8;">—</span>'}</td>
                    ${mobile ? '' : `<td style="color:#64748b;font-size:11px;text-transform:capitalize;">${p.keperluan_jenis?.replace('_',' ') ?? '-'}</td>`}
                    <td>
                        <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;background:${s.bg};color:${s.color};border:1px solid ${s.border};">
                            ${s.label}
                        </span>
                    </td>
                </tr>`;
            }).join('');
        } catch (_) {}
    }

    fetchQR();
    fetchPindaian();
    setInterval(fetchPindaian, 10000);

    history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, '', window.location.href);
        document.getElementById('logout-form').submit();
    });
    </script>
</body>
</html>
