@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .status-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(92,194,242,0.2);
        box-shadow: 0 4px 20px rgba(10,46,92,0.06);
        padding: 24px;
    }
    .action-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px; border-radius: 12px;
        font-size: 13px; font-weight: 700;
        border: none; cursor: pointer; text-decoration: none;
        transition: all 0.15s;
    }
    .action-btn:hover { transform: translateY(-1px); }
    .action-btn-keluar {
        background: linear-gradient(135deg, #0073e6, #0056b3);
        color: white; box-shadow: 0 4px 14px rgba(0,115,230,0.3);
    }
    .action-btn-masuk {
        background: linear-gradient(135deg, #16a34a, #15803d);
        color: white; box-shadow: 0 4px 14px rgba(22,163,74,0.3);
    }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Status Card --}}
    <div class="status-card" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div id="status-dot" style="width:48px;height:48px;border-radius:14px;background:#f0f7ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="width:14px;height:14px;border-radius:50%;background:#cbd5e1;display:inline-block;"></span>
            </div>
            <div>
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:3px;">Status Anda Saat Ini</p>
                <div id="status-badge" style="font-size:20px;font-weight:800;color:#0a2e5c;">Memuat...</div>
                <p id="status-durasi" style="font-size:12px;color:#64748b;margin-top:2px;display:none;"></p>
            </div>
        </div>
        <div id="status-action"></div>
    </div>

    {{-- Izin Dinas Aktif --}}
    <div id="izin-aktif-section" style="display:none;align-items:flex-start;gap:12px;padding:16px 20px;background:#ffffff;border-radius:16px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 2px 12px rgba(10,46,92,0.05);">
        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px rgba(0,115,230,0.2);">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
            <p style="font-size:13px;font-weight:700;color:#0a2e5c;">Izin Dinas Aktif Hari Ini</p>
            <p id="izin-aktif-info" style="font-size:12px;color:#64748b;margin-top:2px;"></p>
        </div>
    </div>

    {{-- Aktivitas Hari Ini --}}
    <div style="background:#ffffff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f0f7ff;">
            <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Aktivitas Hari Ini</p>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Jenis</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Jam</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Keperluan</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Durasi</th>
                    </tr>
                </thead>
                <tbody id="riwayat-hari-ini">
                    <tr><td colspan="4" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

async function loadStatus() {
    try {
        const res = await fetch('/api/pindaian/status-saya', { headers });
        const json = await res.json();
        const data = json.data ?? json;

        const badge  = document.getElementById('status-badge');
        const dot    = document.getElementById('status-dot');
        const durasi = document.getElementById('status-durasi');
        const action = document.getElementById('status-action');

        const map = {
            di_kantor:     { label:'Di Kantor',      color:'#16a34a', bg:'#f0fdf4', dotColor:'#16a34a' },
            sedang_diluar: { label:'Sedang di Luar',  color:'#ea580c', bg:'#fff7ed', dotColor:'#ea580c' },
            belum_kembali: { label:'Belum Kembali',   color:'#dc2626', bg:'#fef2f2', dotColor:'#dc2626' },
        };
        const s = map[data.status] ?? { label: data.status ?? 'Tidak Diketahui', color:'#64748b', bg:'#f8fbff', dotColor:'#94a3b8' };

        badge.textContent = s.label;
        badge.style.color = s.color;
        dot.style.background = s.bg;
        dot.innerHTML = `<span style="width:14px;height:14px;border-radius:50%;background:${s.dotColor};display:inline-block;box-shadow:0 0 0 3px ${s.dotColor}33;"></span>`;

        if (data.active_session?.durasi_berjalan_format) {
            durasi.textContent = `Sudah di luar selama ${data.active_session.durasi_berjalan_format}`;
            durasi.style.display = 'block';
        }

        if (data.status === 'di_kantor') {
            action.innerHTML = `<a href="/pegawai/scan" class="action-btn action-btn-keluar">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                Scan QR Keluar
            </a>`;
        } else {
            action.innerHTML = `<a href="/pegawai/scan" class="action-btn action-btn-masuk">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8l-4 4m0 0l4 4m-4-4h18"/></svg>
                Scan QR Masuk
            </a>`;
        }
    } catch (_) {
        document.getElementById('status-badge').textContent = 'Gagal memuat status';
    }
}

async function loadIzinAktif() {
    try {
        const res = await fetch('/api/izin-dinas/aktif-hari-ini', { headers });
        const json = await res.json();
        const data = json.data?.[0] ?? null;
        if (data) {
            const sec = document.getElementById('izin-aktif-section');
            sec.style.display = 'flex';
            document.getElementById('izin-aktif-info').textContent =
                `${data.tujuan} — ${data.keperluan} (${data.perkiraan_jam_pergi} s/d ${data.perkiraan_jam_kembali})`;
        }
    } catch (_) {}
}

async function loadRiwayat() {
    try {
        const res = await fetch('/api/riwayat/saya?per_page=5', { headers });
        const { data } = await res.json();
        const tbody = document.getElementById('riwayat-hari-ini');
        const td = 'padding:11px 16px;font-size:13px;border-bottom:1px solid #f0f7ff;';

        if (!data?.length) {
            tbody.innerHTML = `<tr><td colspan="4" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Belum ada aktivitas hari ini</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(r => {
            const isKeluar = r.jenis === 'keluar';
            const jam = r.jam_keluar ? r.jam_keluar.substring(11, 19) : '-';
            return `<tr>
                <td style="${td}">
                    <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;
                        background:${isKeluar ? '#fff7ed' : '#f0fdf4'};
                        color:${isKeluar ? '#ea580c' : '#16a34a'};
                        border:1px solid ${isKeluar ? '#fed7aa' : '#bbf7d0'};">
                        ${isKeluar ? 'Keluar' : 'Masuk'}
                    </span>
                </td>
                <td style="${td}color:#0073e6;font-weight:600;">${jam}</td>
                <td style="${td}color:#64748b;text-transform:capitalize;">${r.keperluan_jenis?.replace('_',' ') ?? '-'}</td>
                <td style="${td}color:#64748b;">${r.durasi_menit ? r.durasi_menit + ' mnt' : '—'}</td>
            </tr>`;
        }).join('');
    } catch (_) {}
}

loadStatus();
loadIzinAktif();
loadRiwayat();
setInterval(loadStatus, 30000);
</script>
@endpush
