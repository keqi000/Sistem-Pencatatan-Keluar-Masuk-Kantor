@extends('layouts.app')

@section('title', 'Dashboard Admin')

@push('styles')
<style>
    .stat-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(92,194,242,0.2);
        box-shadow: 0 2px 12px rgba(10,46,92,0.05);
        padding: 20px;
    }
    tbody tr { transition: background 0.12s; }
    tbody tr:hover { background: #f8fbff; }
    .dash-table th { padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left; }
    .dash-table td { padding:11px 16px;font-size:13px;border-bottom:1px solid #f0f7ff; }
    @media (max-width: 640px) {
        .stat-card { padding: 14px; }
        .stat-card p[id] { font-size: 22px !important; }
        .dash-table th { padding: 8px 10px; font-size: 9px; }
        .dash-table td { padding: 8px 10px; font-size: 11px; }
        .hide-mobile { display: none !important; }
    }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Stat Cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;">
        @foreach([
            ['id'=>'stat-diluar',  'label'=>'Sedang di Luar',      'icon'=>'M17 16l4-4m0 0l-4-4m4 4H7', 'bg'=>'#fff7ed', 'color'=>'#ea580c', 'border'=>'#fed7aa'],
            ['id'=>'stat-kembali', 'label'=>'Sudah Kembali',        'icon'=>'M7 8l-4 4m0 0l4 4m-4-4h18', 'bg'=>'#f0fdf4', 'color'=>'#16a34a', 'border'=>'#bbf7d0'],
            ['id'=>'stat-belum',   'label'=>'Belum Kembali',        'icon'=>'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'bg'=>'#fef2f2', 'color'=>'#dc2626', 'border'=>'#fecaca'],
            ['id'=>'stat-total',   'label'=>'Total Keluar Hari Ini','icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'bg'=>'#dbeeff', 'color'=>'#0073e6', 'border'=>'rgba(92,194,242,0.4)'],
        ] as $c)
        <div class="stat-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#94a3b8;">{{ $c['label'] }}</p>
                <div style="width:30px;height:30px;border-radius:8px;background:{{ $c['bg'] }};border:1px solid {{ $c['border'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="{{ $c['color'] }}" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $c['icon'] }}"/></svg>
                </div>
            </div>
            <p id="{{ $c['id'] }}" style="font-size:28px;font-weight:800;color:{{ $c['color'] }};">—</p>
        </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:1fr;gap:20px;">
        <div style="display:grid;grid-template-columns:1fr;gap:20px;" id="main-grid">

        {{-- Tabel Sedang di Luar --}}
        <div style="background:#ffffff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f0f7ff;display:flex;align-items:center;justify-content:space-between;">
                <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Sedang di Luar</p>
                <span id="last-refresh" style="font-size:11px;color:#94a3b8;"></span>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;" class="dash-table">
                    <thead>
                        <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                            <th>Nama</th>
                            <th class="hide-mobile">Unit</th>
                            <th>Jam Keluar</th>
                            <th class="hide-mobile">Keperluan</th>
                            <th>Durasi</th>
                        </tr>
                    </thead>
                    <tbody id="diluar-tbody">
                        <tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Chart --}}
        <div style="background:#ffffff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);padding:20px;">
            <p style="font-size:14px;font-weight:800;color:#0a2e5c;margin-bottom:16px;">Aktivitas per Jam</p>
            <canvas id="chart-aktivitas" height="200"></canvas>
        </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};
let chart = null;

async function loadStats() {
    try {
        const res = await fetch('/api/dashboard/stats', { headers });
        const json = await res.json();
        const s = json.stats ?? {};
        document.getElementById('stat-diluar').textContent  = s.sedang_diluar ?? 0;
        document.getElementById('stat-kembali').textContent = s.sudah_kembali ?? 0;
        document.getElementById('stat-belum').textContent   = s.belum_kembali ?? 0;
        document.getElementById('stat-total').textContent   = s.total_keluar  ?? 0;
    } catch (_) {}
}

async function loadDiluar() {
    try {
        const res = await fetch('/api/dashboard/sedang-diluar', { headers });
        const { data } = await res.json();
        const tbody = document.getElementById('diluar-tbody');
        const td = 'padding:11px 16px;font-size:13px;border-bottom:1px solid #f0f7ff;';

        if (!data?.length) {
            tbody.innerHTML = `<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada pegawai di luar saat ini</td></tr>`;
            return;
        }

        const isMobile = window.innerWidth <= 640;
        tbody.innerHTML = data.map(r => {
            const melebihi = r.is_overdue;
            const jamKeluar = r.jam_keluar ? new Date(r.jam_keluar).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}) : '-';
            return `<tr style="${melebihi ? 'background:#fef9f9;' : ''}">
                <td style="font-weight:600;color:#0a2e5c;">${r.nama_lengkap ?? '-'}</td>
                ${isMobile ? '' : `<td style="color:#64748b;font-size:12px;">${r.nama_unit ?? '-'}</td>`}
                <td style="color:#0073e6;font-weight:600;">${jamKeluar}</td>
                ${isMobile ? '' : `<td style="color:#64748b;text-transform:capitalize;">${r.keperluan_jenis?.replace('_',' ') ?? '-'}</td>`}
                <td>
                    <span style="font-size:12px;font-weight:600;color:${melebihi ? '#dc2626' : '#64748b'};">
                        ${melebihi ? '⚠ ' : ''}${r.durasi_format ?? '—'}
                    </span>
                </td>
            </tr>`;
        }).join('');

        document.getElementById('last-refresh').textContent =
            'Diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
    } catch (_) {}
}

async function loadChart() {
    try {
        const res = await fetch('/api/dashboard/chart', { headers });
        const json = await res.json();
        const data = json.chart ?? {};
        const ctx = document.getElementById('chart-aktivitas').getContext('2d');
        if (chart) chart.destroy();
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels ?? [],
                datasets: data.datasets ?? [
                    { label:'Keluar', data: [], backgroundColor:'rgba(0,115,230,0.75)', borderRadius:6 },
                    { label:'Masuk',  data: [], backgroundColor:'rgba(22,163,74,0.75)',  borderRadius:6 },
                ],
            },
            options: {
                responsive: true,
                plugins: { legend: { position:'bottom', labels:{ font:{ size:11 }, boxWidth:12 } } },
                scales: {
                    x: { grid:{ display:false }, ticks:{ font:{ size:10 } } },
                    y: { beginAtZero:true, ticks:{ stepSize:1, font:{ size:10 } }, grid:{ color:'rgba(219,238,255,0.6)' } },
                },
            },
        });
    } catch (_) {}
}

function loadAll() { loadStats(); loadDiluar(); loadChart(); }
loadAll();
setInterval(loadAll, 60000);
</script>
@endpush
