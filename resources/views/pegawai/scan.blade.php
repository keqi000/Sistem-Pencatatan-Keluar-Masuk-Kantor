@extends('layouts.app')

@section('title', 'Scan QR')

@section('content')
<div class="flex flex-col items-center gap-6 max-w-md mx-auto">

    {{-- Viewfinder --}}
    <div class="w-full bg-canvas rounded-2xl shadow-sm border border-soft overflow-hidden">
        <div class="bg-primary px-5 py-3">
            <h2 class="text-white font-semibold text-sm">Kamera Scan QR</h2>
            <p class="text-sky/70 text-xs mt-0.5">Arahkan kamera ke QR Code di layar lobby / pos</p>
        </div>
        <div class="relative bg-gray-900 aspect-square">
            <video id="qr-video" class="w-full h-full object-cover" playsinline></video>
            {{-- Overlay sudut --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-48 h-48 relative">
                    <span class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-brand rounded-tl-lg"></span>
                    <span class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-brand rounded-tr-lg"></span>
                    <span class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-brand rounded-bl-lg"></span>
                    <span class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-brand rounded-br-lg"></span>
                </div>
            </div>
        </div>
        <div class="px-5 py-3 flex items-center justify-between">
            <span id="scan-status-text" class="text-muted text-xs">Menunggu kamera...</span>
            <button id="btn-start-camera"
                class="text-xs px-3 py-1.5 bg-brand text-white rounded-lg hover:bg-blue-700 transition cursor-pointer">
                Aktifkan Kamera
            </button>
        </div>
    </div>

    {{-- Form Keperluan (muncul setelah scan QR Keluar) --}}
    <div id="keperluan-form" class="hidden w-full bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <h3 class="text-primary font-bold text-base mb-1">QR Keluar Terdeteksi</h3>
        <p class="text-muted text-sm mb-5">Pilih keperluan keluar Anda:</p>

        <div class="flex flex-col gap-3">
            <button onclick="submitKeluar('dinas')"
                class="w-full py-3 rounded-xl border-2 border-brand bg-soft text-brand font-semibold text-sm
                       hover:bg-brand hover:text-white transition cursor-pointer">
                📋 Dinas / Tugas Luar
            </button>
            <button onclick="submitKeluar('keperluan_lain')"
                class="w-full py-3 rounded-xl border-2 border-sky/40 bg-canvas text-primary font-semibold text-sm
                       hover:bg-soft transition cursor-pointer">
                🚶 Keperluan Lain
            </button>
        </div>

        <button onclick="resetScan()"
            class="mt-4 w-full py-2 text-muted text-xs hover:text-danger transition cursor-pointer">
            Batal
        </button>
    </div>

    {{-- Konfirmasi Masuk (muncul setelah scan QR Masuk) --}}
    <div id="masuk-form" class="hidden w-full bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <h3 class="text-primary font-bold text-base mb-1">QR Masuk Terdeteksi</h3>
        <p class="text-muted text-sm mb-5">Konfirmasi bahwa Anda sudah kembali ke kantor.</p>

        <button onclick="submitMasuk()"
            class="w-full py-3 rounded-xl bg-success text-white font-semibold text-sm
                   hover:bg-green-700 transition shadow shadow-green-200 cursor-pointer">
            ✅ Konfirmasi Kembali
        </button>

        <button onclick="resetScan()"
            class="mt-3 w-full py-2 text-muted text-xs hover:text-danger transition cursor-pointer">
            Batal
        </button>
    </div>

    {{-- Toast --}}
    <div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50
        px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium min-w-[240px] text-center transition-all">
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
let videoStream = null;
let scanInterval = null;
let scannedToken = null;
let scannedJenis = null;
let scanning = true;

const video = document.getElementById('qr-video');
const statusText = document.getElementById('scan-status-text');
const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

document.getElementById('btn-start-camera').addEventListener('click', startCamera);

async function startCamera() {
    try {
        videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        video.srcObject = videoStream;
        await video.play();
        statusText.textContent = 'Kamera aktif — arahkan ke QR Code';
        document.getElementById('btn-start-camera').classList.add('hidden');
        startScan();
    } catch (e) {
        statusText.textContent = 'Gagal akses kamera. Izinkan akses kamera di browser.';
    }
}

function startScan() {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    scanInterval = setInterval(() => {
        if (!scanning || video.readyState !== video.HAVE_ENOUGH_DATA) return;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code?.data) {
            scanning = false;
            statusText.textContent = 'QR terdeteksi, memvalidasi...';
            validateQR(code.data);
        }
    }, 300);
}

async function validateQR(token) {
    try {
        const res = await fetch('/api/qr/validate', {
            method: 'POST',
            headers,
            body: JSON.stringify({ token }),
        });
        const json = await res.json();

        if (!res.ok) {
            showToast(json.message ?? 'QR tidak valid atau sudah kadaluarsa', 'danger');
            setTimeout(resetScan, 2500);
            return;
        }

        scannedToken = token;
        scannedJenis = json.data?.jenis;

        if (scannedJenis === 'keluar') {
            document.getElementById('keperluan-form').classList.remove('hidden');
            statusText.textContent = 'Pilih keperluan keluar';
        } else {
            document.getElementById('masuk-form').classList.remove('hidden');
            statusText.textContent = 'Konfirmasi kembali ke kantor';
        }
    } catch (e) {
        showToast('Gagal terhubung ke server', 'danger');
        setTimeout(resetScan, 2500);
    }
}

async function submitKeluar(keperluan) {
    try {
        const res = await fetch('/api/pindaian/catat-keluar', {
            method: 'POST',
            headers,
            body: JSON.stringify({ token: scannedToken, keperluan_jenis: keperluan }),
        });
        const json = await res.json();
        if (res.ok) {
            showToast('Berhasil dicatat keluar!', 'success');
            setTimeout(() => window.location.href = '/pegawai/dashboard', 1800);
        } else {
            showToast(json.message ?? 'Gagal mencatat keluar', 'danger');
            setTimeout(resetScan, 2500);
        }
    } catch (e) {
        showToast('Gagal terhubung ke server', 'danger');
        setTimeout(resetScan, 2500);
    }
}

async function submitMasuk() {
    try {
        const res = await fetch('/api/pindaian/catat-masuk', {
            method: 'POST',
            headers,
            body: JSON.stringify({ token: scannedToken }),
        });
        const json = await res.json();
        if (res.ok) {
            showToast('Berhasil dicatat kembali!', 'success');
            setTimeout(() => window.location.href = '/pegawai/dashboard', 1800);
        } else {
            showToast(json.message ?? 'Gagal mencatat masuk', 'danger');
            setTimeout(resetScan, 2500);
        }
    } catch (e) {
        showToast('Gagal terhubung ke server', 'danger');
        setTimeout(resetScan, 2500);
    }
}

function resetScan() {
    scannedToken = null;
    scannedJenis = null;
    scanning = true;
    document.getElementById('keperluan-form').classList.add('hidden');
    document.getElementById('masuk-form').classList.add('hidden');
    statusText.textContent = 'Kamera aktif — arahkan ke QR Code';
}

function showToast(msg, type) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = toast.className.replace(/bg-\S+/, '');
    toast.classList.remove('hidden');
    toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}
</script>
@endpush
