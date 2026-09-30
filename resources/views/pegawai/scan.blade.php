@extends('layouts.app')

@section('title', 'Scan QR')

@push('styles')
<style>
    .scan-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(92,194,242,0.2);
        box-shadow: 0 4px 20px rgba(10,46,92,0.06);
        overflow: hidden;
    }
    .choice-btn {
        width: 100%; padding: 14px;
        border-radius: 14px; border: 1.5px solid #dbeeff;
        background: #f8fbff; color: #0a2e5c;
        font-size: 13px; font-weight: 700;
        cursor: pointer; transition: all 0.15s;
        display: flex; align-items: center; gap: 10px;
    }
    .choice-btn:hover {
        border-color: #0073e6;
        background: #dbeeff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,115,230,0.12);
    }
    .choice-btn.dinas { border-color: rgba(0,115,230,0.3); }
    .confirm-btn {
        width: 100%; padding: 13px;
        border-radius: 14px; border: none;
        background: linear-gradient(135deg, #16a34a, #15803d);
        color: white; font-size: 13px; font-weight: 700;
        cursor: pointer; transition: all 0.15s;
        box-shadow: 0 4px 14px rgba(22,163,74,0.3);
    }
    .confirm-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(22,163,74,0.4); }
    .cancel-btn {
        width: 100%; padding: 9px;
        border-radius: 10px; border: 1.5px solid #dbeeff;
        background: transparent; color: #94a3b8;
        font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all 0.15s;
    }
    .cancel-btn:hover { border-color: #fecaca; color: #dc2626; background: #fef2f2; }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;align-items:center;gap:20px;max-width:420px;margin:0 auto;">

    {{-- Viewfinder --}}
    <div class="scan-card" style="width:100%;">
        <div style="padding:14px 18px;border-bottom:1px solid #f0f7ff;display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m0 14v1M4 12h1m14 0h1m-2.05-6.95l-.707.707M6.757 17.243l-.707.707m0-11.9l.707.707M17.243 17.243l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
            </div>
            <div>
                <p style="font-size:13px;font-weight:700;color:#0a2e5c;">Kamera Scan QR</p>
                <p style="font-size:11px;color:#64748b;">Arahkan ke QR Code di layar lobby / pos</p>
            </div>
        </div>

        <div style="position:relative;background:#0a1628;aspect-ratio:1;">
            <video id="qr-video" style="width:100%;height:100%;object-fit:cover;" playsinline></video>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
                <div style="width:180px;height:180px;position:relative;">
                    <span style="position:absolute;top:0;left:0;width:28px;height:28px;border-top:3px solid #0073e6;border-left:3px solid #0073e6;border-radius:4px 0 0 0;"></span>
                    <span style="position:absolute;top:0;right:0;width:28px;height:28px;border-top:3px solid #0073e6;border-right:3px solid #0073e6;border-radius:0 4px 0 0;"></span>
                    <span style="position:absolute;bottom:0;left:0;width:28px;height:28px;border-bottom:3px solid #0073e6;border-left:3px solid #0073e6;border-radius:0 0 0 4px;"></span>
                    <span style="position:absolute;bottom:0;right:0;width:28px;height:28px;border-bottom:3px solid #0073e6;border-right:3px solid #0073e6;border-radius:0 0 4px 0;"></span>
                </div>
            </div>
        </div>

        <div style="padding:12px 18px;display:flex;align-items:center;justify-content:space-between;background:#f8fbff;border-top:1px solid #dbeeff;">
            <span id="scan-status-text" style="font-size:12px;color:#64748b;">Menunggu kamera...</span>
            <button id="btn-start-camera" style="font-size:12px;padding:6px 14px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;box-shadow:0 2px 8px rgba(0,115,230,0.25);">
                Aktifkan Kamera
            </button>
        </div>
    </div>

    {{-- Form Keperluan --}}
    <div id="keperluan-form" style="display:none;width:100%;">
        <div class="scan-card" style="padding:22px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <div style="width:36px;height:36px;border-radius:10px;background:#dbeeff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#0073e6" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:800;color:#0a2e5c;">QR Keluar Terdeteksi</p>
                    <p style="font-size:12px;color:#64748b;">Pilih keperluan keluar Anda</p>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
                <button class="choice-btn dinas" onclick="submitKeluar('dinas')">
                    <span style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#dbeeff,#bfdfff);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">📋</span>
                    <div style="text-align:left;">
                        <p style="font-size:13px;font-weight:700;color:#0a2e5c;">Dinas / Tugas Luar</p>
                        <p style="font-size:11px;color:#64748b;font-weight:400;">Perjalanan dinas resmi dengan izin</p>
                    </div>
                </button>
                <button class="choice-btn" onclick="submitKeluar('keperluan_lain')">
                    <span style="width:32px;height:32px;border-radius:8px;background:#f0f7ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">🚶</span>
                    <div style="text-align:left;">
                        <p style="font-size:13px;font-weight:700;color:#0a2e5c;">Keperluan Lain</p>
                        <p style="font-size:11px;color:#64748b;font-weight:400;">Keperluan pribadi di luar kantor</p>
                    </div>
                </button>
            </div>
            <button class="cancel-btn" onclick="resetScan()">Batal</button>
        </div>
    </div>

    {{-- Konfirmasi Masuk --}}
    <div id="masuk-form" style="display:none;width:100%;">
        <div class="scan-card" style="padding:22px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <div style="width:36px;height:36px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:800;color:#0a2e5c;">QR Masuk Terdeteksi</p>
                    <p style="font-size:12px;color:#64748b;">Konfirmasi bahwa Anda sudah kembali</p>
                </div>
            </div>
            <button class="confirm-btn" onclick="submitMasuk()" style="margin-bottom:10px;">✓ Konfirmasi Kembali ke Kantor</button>
            <button class="cancel-btn" onclick="resetScan()">Batal</button>
        </div>
    </div>

</div>

<div id="toast" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:60;padding:12px 24px;border-radius:12px;color:white;font-size:13px;font-weight:600;min-width:220px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,0.15);"></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
let videoStream = null, scanInterval = null, scannedToken = null, scannedJenis = null, scanning = true;
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
        document.getElementById('btn-start-camera').style.display = 'none';
        startScan();
    } catch (_) {
        statusText.textContent = 'Gagal akses kamera. Izinkan akses kamera di browser.';
    }
}

function startScan() {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    scanInterval = setInterval(() => {
        if (!scanning || video.readyState !== video.HAVE_ENOUGH_DATA) return;
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);
        const code = jsQR(ctx.getImageData(0, 0, canvas.width, canvas.height).data, canvas.width, canvas.height);
        if (code?.data) { scanning = false; statusText.textContent = 'QR terdeteksi, memvalidasi...'; validateQR(code.data); }
    }, 300);
}

async function validateQR(token) {
    try {
        const res = await fetch('/api/qr/validate', { method:'POST', headers, body: JSON.stringify({ token }) });
        const json = await res.json();
        if (!res.ok) { showToast(json.message ?? 'QR tidak valid', 'danger'); setTimeout(resetScan, 2500); return; }
        scannedToken = token; scannedJenis = json.data?.jenis;
        if (scannedJenis === 'keluar') {
            document.getElementById('keperluan-form').style.display = 'block';
            statusText.textContent = 'Pilih keperluan keluar';
        } else {
            document.getElementById('masuk-form').style.display = 'block';
            statusText.textContent = 'Konfirmasi kembali ke kantor';
        }
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); setTimeout(resetScan, 2500); }
}

async function submitKeluar(keperluan) {
    try {
        const res = await fetch('/api/pindaian/catat-keluar', { method:'POST', headers, body: JSON.stringify({ token: scannedToken, keperluan_jenis: keperluan }) });
        const json = await res.json();
        if (res.ok) { showToast('Berhasil dicatat keluar!', 'success'); setTimeout(() => window.location.href = '/pegawai/dashboard', 1800); }
        else { showToast(json.message ?? 'Gagal mencatat keluar', 'danger'); setTimeout(resetScan, 2500); }
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); setTimeout(resetScan, 2500); }
}

async function submitMasuk() {
    try {
        const res = await fetch('/api/pindaian/catat-masuk', { method:'POST', headers, body: JSON.stringify({ token: scannedToken }) });
        const json = await res.json();
        if (res.ok) { showToast('Berhasil dicatat kembali!', 'success'); setTimeout(() => window.location.href = '/pegawai/dashboard', 1800); }
        else { showToast(json.message ?? 'Gagal mencatat masuk', 'danger'); setTimeout(resetScan, 2500); }
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); setTimeout(resetScan, 2500); }
}

function resetScan() {
    scannedToken = null; scannedJenis = null; scanning = true;
    document.getElementById('keperluan-form').style.display = 'none';
    document.getElementById('masuk-form').style.display = 'none';
    statusText.textContent = 'Kamera aktif — arahkan ke QR Code';
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'success' ? 'linear-gradient(135deg,#16a34a,#15803d)' : 'linear-gradient(135deg,#dc2626,#b91c1c)';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}
</script>
@endpush
