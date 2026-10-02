@extends('layouts.app')

@section('title', 'Rekap Kehadiran')

@push('styles')
<style>
    .filter-input {
        padding: 8px 12px; border-radius: 10px;
        border: 1.5px solid #dbeeff; background: #f8fbff;
        color: #0a2e5c; font-size: 13px; outline: none;
        transition: all 0.15s; font-family: inherit;
        box-sizing: border-box;
    }
    .filter-input:focus { border-color: #5cc2f2; box-shadow: 0 0 0 3px rgba(92,194,242,0.15); background: #fff; }
    .tab-btn { padding: 7px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s; }
    .tab-btn.active { background: linear-gradient(135deg,#0073e6,#0056b3); color: white; box-shadow: 0 3px 10px rgba(0,115,230,0.25); }
    .tab-btn.inactive { background: transparent; color: #64748b; }
    .tab-btn.inactive:hover { background: #f0f7ff; color: #0a2e5c; }
    tbody tr { transition: background 0.12s; }
    tbody tr:hover { background: #f8fbff; }
    .rekap-table th { padding: 10px 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; text-align: left; white-space: nowrap; }
    .rekap-table td { padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f0f7ff; }
    @media (max-width: 640px) {
        .filter-row { flex-direction: column !important; align-items: stretch !important; }
        .filter-row > div, .filter-row > button { width: 100%; }
        .export-row { width: 100%; display: flex; gap: 8px; }
        .export-row button { flex: 1; justify-content: center; }
        .rekap-table th { padding: 7px 8px; font-size: 9px; }
        .rekap-table td { padding: 7px 8px; font-size: 11px; }
        .hide-mobile { display: none !important; }
    }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Filter Card --}}
    <div style="background:#ffffff;border-radius:16px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 2px 12px rgba(10,46,92,0.05);padding:18px 20px;">

        {{-- Tab --}}
        <div style="display:flex;gap:4px;background:#f8fbff;border-radius:10px;border:1px solid #dbeeff;padding:3px;width:fit-content;margin-bottom:16px;">
            <button class="tab-btn active" data-tab="harian" onclick="switchTab('harian')">Harian</button>
            <button class="tab-btn inactive" data-tab="bulanan" onclick="switchTab('bulanan')">Bulanan</button>
        </div>

        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;" class="filter-row">
            <div id="filter-harian" style="display:flex;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Tanggal</label>
                <input type="date" id="input-tanggal" class="filter-input">
            </div>
            <div id="filter-bulanan" style="display:none;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Bulan</label>
                <input type="month" id="input-bulan" class="filter-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Unit Kerja</label>
                <select id="filter-unit" class="filter-input">
                    <option value="">Semua Unit</option>
                </select>
            </div>
            <button onclick="loadRekap()" style="padding:8px 20px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);transition:all 0.15s;"
                onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                Tampilkan
            </button>
            <div style="display:flex;gap:8px;margin-left:auto;" class="export-row">
                <button onclick="exportDoc('pdf')" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;font-size:12px;font-weight:700;cursor:pointer;transition:all 0.15s;"
                    onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    PDF
                </button>
                <button onclick="exportDoc('excel')" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;font-size:12px;font-weight:700;cursor:pointer;transition:all 0.15s;"
                    onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Excel
                </button>
            </div>
        </div>
    </div>

    {{-- Tabel --}}
    <div style="background:#ffffff;border-radius:16px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 2px 12px rgba(10,46,92,0.05);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f0f7ff;">
            <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Data Rekap</p>
            <span id="rekap-info" style="font-size:12px;font-weight:600;color:#0073e6;padding:3px 12px;border-radius:99px;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);display:none;"></span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;" class="rekap-table">
                <thead id="rekap-thead">
                    <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                        <th>Nama Pegawai</th>
                        <th class="hide-mobile">Unit</th>
                        <th>Jam Keluar</th>
                        <th>Jam Kembali</th>
                        <th class="hide-mobile">Durasi</th>
                        <th class="hide-mobile">Keperluan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="rekap-tbody">
                    <tr><td colspan="7" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">Pilih tanggal/bulan lalu klik Tampilkan</td></tr>
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
let activeTab = 'harian';

document.getElementById('input-tanggal').value = new Date().toISOString().split('T')[0];
document.getElementById('input-bulan').value   = new Date().toISOString().slice(0, 7);

function exportDoc(tipe) {
    if (!window._exportQuery) {
        alert('Pilih tanggal/bulan dan klik Tampilkan terlebih dahulu sebelum mengunduh.');
        return;
    }
    window.open(tipe === 'pdf'
        ? `/api/rekap/export-pdf?${window._exportQuery}`
        : `/api/rekap/export-excel?${window._exportQuery}`, '_blank');
}

async function loadUnits() {
    try {
        const res = await fetch('/api/pengaturan/unit-kerja', { headers });
        const { data } = await res.json();
        const sel = document.getElementById('filter-unit');
        (data ?? []).forEach(u => sel.innerHTML += `<option value="${u.id}">${u.nama_unit}</option>`);
    } catch (_) {}
}

function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.className = 'tab-btn ' + (btn.dataset.tab === tab ? 'active' : 'inactive');
    });
    document.getElementById('filter-harian').style.display  = tab === 'harian'  ? 'flex' : 'none';
    document.getElementById('filter-bulanan').style.display = tab === 'bulanan' ? 'flex' : 'none';

    const isMobile = window.innerWidth <= 640;
    const thead = document.getElementById('rekap-thead');
    if (tab === 'bulanan') {
        thead.innerHTML = `<tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
            <th>Nama Pegawai</th>
            ${isMobile ? '' : '<th>Unit</th>'}
            <th>Hari Keluar</th>
            <th>Total Menit</th>
            <th>Belum Kembali</th>
        </tr>`;
    } else {
        thead.innerHTML = `<tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
            <th>Nama Pegawai</th>
            ${isMobile ? '' : '<th class="hide-mobile">Unit</th>'}
            <th>Jam Keluar</th>
            <th>Jam Kembali</th>
            ${isMobile ? '' : '<th class="hide-mobile">Durasi</th>'}
            ${isMobile ? '' : '<th class="hide-mobile">Keperluan</th>'}
            <th>Status</th>
        </tr>`;
    }
    document.getElementById('rekap-tbody').innerHTML =
        `<tr><td colspan="7" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">Pilih ${tab === 'harian' ? 'tanggal' : 'bulan'} lalu klik Tampilkan</td></tr>`;
}

async function loadRekap() {
    const tbody = document.getElementById('rekap-tbody');
    const unit  = document.getElementById('filter-unit').value;
    const isMobile = window.innerWidth <= 640;

    tbody.innerHTML = `<tr><td colspan="7" style="padding:48px;text-align:center;">
        <div style="width:28px;height:28px;border-radius:50%;border:3px solid #dbeeff;border-top-color:#0073e6;animation:spin 0.8s linear infinite;margin:0 auto 10px;"></div>
        <p style="font-size:13px;color:#94a3b8;">Memuat data...</p>
    </td></tr>`;

    const params = new URLSearchParams();
    if (unit) params.append('unit_id', unit);

    let url, exportQuery;
    if (activeTab === 'harian') {
        const tanggal = document.getElementById('input-tanggal').value;
        params.append('tanggal', tanggal);
        url = '/api/rekap/harian?' + params;
        exportQuery = `tipe=harian&tanggal=${tanggal}` + (unit ? `&unit_id=${unit}` : '');
    } else {
        const bulan = document.getElementById('input-bulan').value;
        params.append('bulan', bulan);
        url = '/api/rekap/bulanan?' + params;
        exportQuery = `tipe=bulanan&bulan=${bulan}` + (unit ? `&unit_id=${unit}` : '');
    }
    window._exportQuery = exportQuery;

    try {
        const res = await fetch(url, { headers });
        const { data } = await res.json();
        const list = data ?? [];

        const info = document.getElementById('rekap-info');
        info.textContent = `${list.length} data`;
        info.style.display = list.length ? 'inline' : 'none';

        if (!list.length) {
            tbody.innerHTML = `<tr><td colspan="7" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada data untuk periode ini</td></tr>`;
            return;
        }

        const statusBadge = {
            kembali:       { bg:'#f0fdf4', color:'#16a34a', border:'#bbf7d0', label:'Kembali' },
            terbuka:       { bg:'#fff7ed', color:'#ea580c', border:'#fed7aa', label:'Di Luar' },
            belum_kembali: { bg:'#fef2f2', color:'#dc2626', border:'#fecaca', label:'Belum Kembali' },
        };

        if (activeTab === 'harian') {
            tbody.innerHTML = list.map(r => {
                const s = statusBadge[r.status] ?? { bg:'#dbeeff', color:'#0073e6', border:'#bfdfff', label: r.status ?? '-' };
                return `<tr>
                    <td style="font-weight:600;color:#0a2e5c;">${r.nama_lengkap ?? '-'}</td>
                    ${isMobile ? '' : `<td style="color:#64748b;font-size:12px;">${r.nama_unit ?? '-'}</td>`}
                    <td style="color:#0073e6;font-weight:600;">${r.jam_keluar ?? '-'}</td>
                    <td style="color:#64748b;">${r.jam_kembali ?? '—'}</td>
                    ${isMobile ? '' : `<td style="color:#64748b;">${r.durasi_menit ? r.durasi_menit + ' mnt' : '—'}</td>`}
                    ${isMobile ? '' : `<td style="color:#64748b;text-transform:capitalize;">${r.keperluan_jenis?.replace('_',' ') ?? '-'}</td>`}
                    <td>
                        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${s.bg};color:${s.color};border:1px solid ${s.border};">
                            ${s.label}
                        </span>
                    </td>
                </tr>`;
            }).join('');
        } else {
            tbody.innerHTML = list.map(r => `<tr>
                <td style="font-weight:600;color:#0a2e5c;">${r.nama_lengkap ?? '-'}</td>
                ${isMobile ? '' : `<td style="color:#64748b;font-size:12px;">${r.nama_unit ?? '-'}</td>`}
                <td style="color:#64748b;">${r.jumlah_hari_keluar ?? 0} hari</td>
                <td style="color:#64748b;">${r.total_menit_keluar ?? 0} mnt</td>
                <td>
                    ${r.belum_kembali > 0
                        ? `<span style="display:inline-flex;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">${r.belum_kembali}x</span>`
                        : `<span style="color:#94a3b8;">—</span>`}
                </td>
            </tr>`).join('');
        }
    } catch (_) {
        tbody.innerHTML = `<tr><td colspan="7" style="padding:48px;text-align:center;color:#dc2626;font-size:13px;">Gagal memuat data</td></tr>`;
    }
}

loadUnits();
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
@endpush
