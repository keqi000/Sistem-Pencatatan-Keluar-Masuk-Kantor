@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Status Badge Besar --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <p class="text-muted text-sm mb-1">Status Anda Saat Ini</p>
            <div id="status-badge" class="text-2xl font-bold text-primary flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded-full bg-gray-300"></span>
                <span>Memuat...</span>
            </div>
            <p id="status-durasi" class="text-muted text-sm mt-1 hidden"></p>
        </div>
        <div id="status-action" class="shrink-0"></div>
    </div>

    {{-- Info Izin Dinas Aktif --}}
    <div id="izin-aktif-section" class="hidden bg-soft border border-sky/40 rounded-2xl p-5 flex items-start gap-3">
        <span class="text-brand text-xl mt-0.5">📋</span>
        <div>
            <p class="text-primary font-semibold text-sm">Izin Dinas Aktif Hari Ini</p>
            <p id="izin-aktif-info" class="text-muted text-sm mt-0.5"></p>
        </div>
    </div>

    {{-- Riwayat Hari Ini --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <h2 class="text-primary font-bold text-base mb-4">Aktivitas Hari Ini</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-soft text-muted text-left">
                        <th class="pb-2 font-semibold">Jenis</th>
                        <th class="pb-2 font-semibold">Jam</th>
                        <th class="pb-2 font-semibold">Keperluan</th>
                        <th class="pb-2 font-semibold">Durasi</th>
                    </tr>
                </thead>
                <tbody id="riwayat-hari-ini" class="divide-y divide-soft">
                    <tr>
                        <td colspan="4" class="py-6 text-center text-muted">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const API = {
    status: '/api/pindaian/status-saya',
    izin:   '/api/izin-dinas/aktif-hari-ini',
    riwayat:'/api/riwayat',
};

const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

async function loadStatus() {
    try {
        const res = await fetch(API.status, { headers });
        const { data } = await res.json();

        const badge = document.getElementById('status-badge');
        const durasi = document.getElementById('status-durasi');
        const action = document.getElementById('status-action');

        const statusMap = {
            di_kantor:     { label: 'Di Kantor',      color: 'text-success', dot: 'bg-success' },
            sedang_diluar: { label: 'Sedang di Luar',  color: 'text-warning', dot: 'bg-warning' },
            belum_kembali: { label: 'Belum Kembali',   color: 'text-danger',  dot: 'bg-danger'  },
        };

        const s = statusMap[data.status] ?? { label: data.status, color: 'text-muted', dot: 'bg-gray-300' };
        badge.innerHTML = `<span class="inline-block w-3 h-3 rounded-full ${s.dot}"></span><span class="${s.color}">${s.label}</span>`;

        if (data.durasi_berjalan) {
            durasi.textContent = `Sudah di luar selama ${data.durasi_berjalan}`;
            durasi.classList.remove('hidden');
        }

        if (data.status === 'di_kantor') {
            action.innerHTML = `<a href="/pegawai/scan"
                class="inline-block px-5 py-2.5 bg-brand text-white text-sm font-semibold rounded-lg shadow shadow-brand/30 hover:bg-blue-700 transition">
                Scan QR Keluar
            </a>`;
        } else if (data.status === 'sedang_diluar' || data.status === 'belum_kembali') {
            action.innerHTML = `<a href="/pegawai/scan"
                class="inline-block px-5 py-2.5 bg-success text-white text-sm font-semibold rounded-lg shadow shadow-green-200 hover:bg-green-700 transition">
                Scan QR Masuk
            </a>`;
        }
    } catch (e) {
        document.getElementById('status-badge').innerHTML =
            `<span class="text-muted text-base font-normal">Gagal memuat status</span>`;
    }
}

async function loadIzinAktif() {
    try {
        const res = await fetch(API.izin, { headers });
        const { data } = await res.json();
        if (data) {
            document.getElementById('izin-aktif-section').classList.remove('hidden');
            document.getElementById('izin-aktif-info').textContent =
                `${data.tujuan} — ${data.keperluan} (${data.perkiraan_jam_pergi} s/d ${data.perkiraan_jam_kembali})`;
        }
    } catch (_) {}
}

async function loadRiwayat() {
    try {
        const res = await fetch(API.riwayat + '?per_page=5', { headers });
        const { data } = await res.json();
        const tbody = document.getElementById('riwayat-hari-ini');

        if (!data?.length) {
            tbody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-muted">Belum ada aktivitas hari ini</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(r => `
            <tr class="hover:bg-soft/50 transition">
                <td class="py-2.5 pr-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border
                        ${r.jenis === 'keluar' ? 'bg-orange-100 text-warning border-orange-200' : 'bg-green-100 text-success border-green-200'}">
                        ${r.jenis === 'keluar' ? 'Keluar' : 'Masuk'}
                    </span>
                </td>
                <td class="py-2.5 pr-4 text-text">${r.jam ?? '-'}</td>
                <td class="py-2.5 pr-4 text-text capitalize">${r.keperluan_jenis?.replace('_', ' ') ?? '-'}</td>
                <td class="py-2.5 text-muted">${r.durasi_menit ? r.durasi_menit + ' menit' : '-'}</td>
            </tr>
        `).join('');
    } catch (_) {}
}

loadStatus();
loadIzinAktif();
loadRiwayat();

// Auto-refresh status setiap 30 detik
setInterval(loadStatus, 30000);
</script>
@endpush
