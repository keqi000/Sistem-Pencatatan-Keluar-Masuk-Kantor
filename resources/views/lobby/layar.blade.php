@extends('layouts.fullscreen')

@section('title', 'Layar Lobby — SIKMA')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center gap-8 p-8"
     style="background: linear-gradient(160deg, #0a2e5c 0%, #0d3a73 60%, #0073e6 100%);">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <img src="{{ asset('img/logo-bpmp.png') }}" alt="Logo" class="h-12 w-12 object-contain drop-shadow">
        <div class="text-center">
            <p class="text-white font-bold text-lg tracking-wide">BPMP Provinsi Gorontalo</p>
            <p class="text-sky/70 text-sm">Sistem Informasi Keluar Masuk</p>
        </div>
    </div>

    {{-- Label --}}
    <div class="text-center">
        <span class="inline-block px-6 py-2 rounded-full bg-accent text-white font-bold text-sm tracking-widest uppercase shadow-lg">
            QR Keluar
        </span>
        <p class="text-white/60 text-sm mt-2">Scan QR ini sebelum meninggalkan kantor</p>
    </div>

    {{-- QR Container --}}
    <div class="bg-white rounded-3xl p-6 shadow-2xl shadow-black/40">
        <div id="qr-container" class="w-64 h-64 flex items-center justify-center">
            <div class="text-muted text-sm">Memuat QR...</div>
        </div>
    </div>

    {{-- Countdown --}}
    <div class="text-center">
        <p class="text-white/50 text-xs uppercase tracking-widest mb-1">Berganti dalam</p>
        <p id="countdown" class="text-white text-5xl font-bold tabular-nums">--</p>
        <p class="text-white/30 text-xs mt-1">detik</p>
    </div>

    {{-- Jam --}}
    <div id="clock" class="text-white/40 text-2xl font-light tabular-nums tracking-widest"></div>

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

// Jam real-time
function updateClock() {
    document.getElementById('clock').textContent =
        new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

async function fetchQR() {
    try {
        const res = await fetch('/api/qr/current?jenis=keluar', { headers });
        const { data } = await res.json();

        if (!data?.token) return;

        const container = document.getElementById('qr-container');
        container.innerHTML = '<canvas id="qr-canvas"></canvas>';

        await QRCode.toCanvas(document.getElementById('qr-canvas'), data.token, {
            width: 256,
            margin: 0,
            color: { dark: '#0a2e5c', light: '#ffffff' },
        });

        // Hitung sisa waktu dari expired_at
        const expiredAt = new Date(data.expired_at);
        const now = new Date();
        countdownVal = Math.max(0, Math.round((expiredAt - now) / 1000));
        startCountdown();
    } catch (_) {}
}

function startCountdown() {
    clearInterval(countdownTimer);
    countdownTimer = setInterval(() => {
        countdownVal--;
        document.getElementById('countdown').textContent = countdownVal;
        if (countdownVal <= 0) {
            clearInterval(countdownTimer);
            fetchQR();
        }
    }, 1000);
}

fetchQR();
</script>
@endpush
