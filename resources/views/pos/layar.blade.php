@extends('layouts.fullscreen')

@section('title', 'Layar Pos Security — SIKMA')

@section('content')
<div class="min-h-screen flex flex-col lg:flex-row"
     style="background: linear-gradient(160deg, #0a2e5c 0%, #0d3a73 100%);">

    {{-- KIRI — QR Masuk --}}
    <div class="flex flex-col items-center justify-center gap-6 p-8 lg:w-5/12 border-b lg:border-b-0 lg:border-r border-white/10">

        <div class="flex items-center gap-3">
            <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo" class="h-10 w-10 object-contain">
            <div>
                <p class="text-white font-bold text-sm">BPMP Provinsi Gorontalo</p>
                <p class="text-sky/60 text-xs">Pos Security</p>
            </div>
        </div>

        <span class="inline-block px-5 py-1.5 rounded-full bg-success text-white font-bold text-xs tracking-widest uppercase shadow">
            QR Masuk
        </span>

        <div class="bg-white rounded-3xl p-5 shadow-2xl shadow-black/40">
            <div id="qr-container" class="w-52 h-52 flex items-center justify-center">
                <div class="text-muted text-sm">Memuat QR...</div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-white/40 text-xs uppercase tracking-widest mb-1">Berganti dalam</p>
            <p id="countdown" class="text-white text-4xl font-bold tabular-nums">--</p>
            <p class="text-white/30 text-xs mt-0.5">detik</p>
        </div>

        <div id="clock" class="text-white/30 text-xl font-light tabular-nums"></div>
    </div>

    {{-- KANAN — Daftar Pindaian --}}
    <div class="flex flex-col flex-1 p-6 overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-white font-bold text-base">Pindaian Hari Ini</h2>
            <span id="total-pindaian" class="text-white/40 text-xs"></span>
        </div>

        <div class="flex-1 overflow-y-auto rounded-xl" style="max-height: calc(100vh - 120px);">
            <table class="w-full text-sm">
                <thead class="sticky top-0" style="background:#0d3a73;">
                    <tr class="text-white/50 text-left text-xs uppercase tracking-wide">
                        <th class="pb-2 pt-1 pr-3 font-semibold">Nama</th>
                        <th class="pb-2 pt-1 pr-3 font-semibold">Unit</th>
                        <th class="pb-2 pt-1 pr-3 font-semibold">Jam</th>
                        <th class="pb-2 pt-1 pr-3 font-semibold">Jenis</th>
                        <th class="pb-2 pt-1 font-semibold">Keperluan</th>
                    </tr>
                </thead>
                <tbody id="pindaian-tbody" class="divide-y divide-white/5">
                    <tr><td colspan="5" class="py-6 text-center text-white/30">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

let countdownVal = 30;
let countdownTimer = null;
let lastPindaianCount = 0;

// Jam
function updateClock() {
    document.getElementById('clock').textContent =
        new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

// QR
async function fetchQR() {
    try {
        const res = await fetch('/api/qr/current?jenis=masuk', { headers });
        const { data } = await res.json();
        if (!data?.token) return;

        const container = document.getElementById('qr-container');
        container.innerHTML = '<canvas id="qr-canvas"></canvas>';
        await QRCode.toCanvas(document.getElementById('qr-canvas'), data.token, {
            width: 208, margin: 0,
            color: { dark: '#0a2e5c', light: '#ffffff' },
        });

        const expiredAt = new Date(data.expired_at);
        countdownVal = Math.max(0, Math.round((expiredAt - new Date()) / 1000));
        startCountdown();
    } catch (_) {}
}

function startCountdown() {
    clearInterval(countdownTimer);
    countdownTimer = setInterval(() => {
        countdownVal--;
        document.getElementById('countdown').textContent = countdownVal;
        if (countdownVal <= 0) { clearInterval(countdownTimer); fetchQR(); }
    }, 1000);
}

// Pindaian
async function fetchPindaian() {
    try {
        const res = await fetch('/api/pindaian/hari-ini-pos', { headers });
        const { data } = await res.json();
        const list = data ?? [];

        document.getElementById('total-pindaian').textContent = `${list.length} pindaian`;

        const isNew = list.length > lastPindaianCount;
        lastPindaianCount = list.length;

        const tbody = document.getElementById('pindaian-tbody');
        if (!list.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-white/30">Belum ada pindaian</td></tr>`;
            return;
        }

        tbody.innerHTML = list.map((p, i) => `
            <tr class="transition ${i === 0 && isNew ? 'highlight-new' : ''}">
                <td class="py-2.5 pr-3 text-white font-medium text-sm">${p.pegawai?.nama_lengkap ?? '-'}</td>
                <td class="py-2.5 pr-3 text-white/50 text-xs">${p.pegawai?.unit_kerja?.nama_unit ?? '-'}</td>
                <td class="py-2.5 pr-3 text-white/70 text-xs tabular-nums">${p.jam ?? '-'}</td>
                <td class="py-2.5 pr-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                        ${p.jenis === 'keluar' ? 'bg-accent/20 text-accent' : 'bg-success/20 text-green-300'}">
                        ${p.jenis === 'keluar' ? 'Keluar' : 'Masuk'}
                    </span>
                </td>
                <td class="py-2.5 text-white/50 text-xs capitalize">${p.keperluan_jenis?.replace('_', ' ') ?? '-'}</td>
            </tr>
        `).join('');
    } catch (_) {}
}

// Highlight animasi baris baru
const style = document.createElement('style');
style.textContent = `
    .highlight-new { animation: highlightRow 3s ease-out; }
    @keyframes highlightRow {
        0%   { background: rgba(92,194,242,0.25); }
        100% { background: transparent; }
    }
`;
document.head.appendChild(style);

fetchQR();
fetchPindaian();
setInterval(fetchPindaian, 10000);
</script>
@endpush
