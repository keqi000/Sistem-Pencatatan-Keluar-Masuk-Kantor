@extends('layouts.app')

@section('title', 'Rekap Unit Kerja')

@push('styles')
<style>
    .filter-input {
        padding: 8px 12px;
        border-radius: 10px;
        border: 1.5px solid #dbeeff;
        background: #f8fbff;
        color: #0a2e5c;
        font-size: 13px;
        outline: none;
        transition: all 0.15s;
        font-family: inherit;
        width: 100%;
    }
    .filter-input:focus {
        border-color: #5cc2f2;
        box-shadow: 0 0 0 3px rgba(92,194,242,0.15);
        background: #ffffff;
    }
    .tab-btn {
        padding: 7px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
    }
    .tab-btn.active {
        background: linear-gradient(135deg, #0073e6, #0056b3);
        color: white;
        box-shadow: 0 3px 10px rgba(0,115,230,0.25);
    }
    .tab-btn.inactive {
        background: transparent;
        color: #64748b;
    }
    .tab-btn.inactive:hover { background: #f0f7ff; color: #0a2e5c; }

    tbody tr { transition: background 0.12s; }
    tbody tr:hover { background: #f8fbff; }

    .rekap-table th {
        padding: 10px 14px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
        text-align: left;
        white-space: nowrap;
    }
    .rekap-table td {
        padding: 10px 14px;
        font-size: 13px;
        border-bottom: 1px solid #f0f7ff;
    }

    .export-btns { display: flex; gap: 8px; }

    @media (max-width: 640px) {
        .filter-row { flex-direction: column !important; align-items: stretch !important; }
        .filter-row > * { width: 100%; }
        .export-btns { width: 100%; }
        .export-btns button { flex: 1; justify-content: center; }
        .rekap-table th { padding: 8px 10px; font-size: 9px; }
        .rekap-table td { padding: 8px 10px; font-size: 11px; }
        .tab-btn { padding: 6px 14px; font-size: 12px; }
    }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Info Unit --}}
    <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:#ffffff;border-radius:16px;border:1px solid rgba(92,194,242,0.25);box-shadow:0 2px 12px rgba(10,46,92,0.05);">
        <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px rgba(0,115,230,0.2);">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div>
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Unit Kerja Anda</p>
            <p id="nama-unit" style="font-size:14px;font-weight:700;color:#0a2e5c;margin-top:1px;">Memuat...</p>
        </div>
    </div>

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

            <button onclick="loadRekap()" style="padding:8px 20px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);transition:all 0.15s;"
                onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                Tampilkan
            </button>

            <div style="display:flex;gap:8px;margin-left:auto;" class="export-btns">
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
            <span id="rekap-info" style="font-size:12px;font-weight:600;color:#0073e6;padding:3px 12px;border-radius:99px;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);"></span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;" class="rekap-table">
                <thead id="rekap-thead">
                    <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                        <th>Nama Pegawai</th>
                        <th>Jam Keluar</th>
                        <th>Jam Kembali</th>
                        <th>Durasi</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="rekap-tbody">
                    <tr><td colspan="6" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">Pilih tanggal lalu klik Tampilkan</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const unitKerjaId = @json($unitKerjaId);
const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

let activeTab = 'harian';

document.getElementById('input-tanggal').value = new Date().toISOString().split('T')[0];
document.getElementById('input-bulan').value   = new Date().toISOString().slice(0, 7);

async function loadNamaUnit() {
    if (!unitKerjaId) { document.getElementById('nama-unit').textContent = 'Unit kerja tidak ditemukan'; return; }
    try {
        const res = await fetch('/api/pengaturan/unit-kerja', { headers });
        const { data } = await res.json();
        const unit = (data ?? []).find(u => u.id == unitKerjaId);
        document.getElementById('nama-unit').textContent = unit?.nama_unit ?? 'Unit kerja tidak ditemukan';
    } catch (_) {}
}

function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.dataset.tab === tab;
        btn.className = 'tab-btn ' + (isActive ? 'active' : 'inactive');
    });
    document.getElementById('filter-harian').style.display  = tab === 'harian'  ? 'flex' : 'none';
    document.getElementById('filter-bulanan').style.display = tab === 'bulanan' ? 'flex' : 'none';

    const thStyle = 'text-align:left;';
    const trStyle = 'background:#f8fbff;border-bottom:1px solid #dbeeff;';
    const thead = document.getElementById('rekap-thead');

    if (tab === 'bulanan') {
        thead.innerHTML = `<tr style="${trStyle}">
            <th style="${thStyle}">Nama Pegawai</th>
            <th style="${thStyle}">Hari Keluar</th>
            <th style="${thStyle}">Total Menit</th>
            <th style="${thStyle}">Belum Kembali</th>
        </tr>`;
    } else {
        thead.innerHTML = `<tr style="${trStyle}">
            <th style="${thStyle}">Nama Pegawai</th>
            <th style="${thStyle}">Jam Keluar</th>
            <th style="${thStyle}">Jam Kembali</th>
            <th style="${thStyle}">Durasi</th>
            <th style="${thStyle}">Keperluan</th>
            <th style="${thStyle}">Status</th>
        </tr>`;
    }
    document.getElementById('rekap-tbody').innerHTML =
        `<tr><td colspan="6" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">Pilih ${tab === 'harian' ? 'tanggal' : 'bulan'} lalu klik Tampilkan</td></tr>`;
}

async function loadRekap() {
    const tbody = document.getElementById('rekap-tbody');
    tbody.innerHTML = `<tr><td colspan="6" style="padding:48px;text-align:center;">
        <div style="width:32px;height:32px;border-radius:50%;border:3px solid #dbeeff;border-top-color:#0073e6;animation:spin 0.8s linear infinite;margin:0 auto 10px;"></div>
        <p style="font-size:13px;color:#94a3b8;">Memuat data...</p>
    </td></tr>`;

    const params = new URLSearchParams();
    if (unitKerjaId) params.append('unit_id', unitKerjaId);

    let url;
    if (activeTab === 'harian') {
        const tgl = document.getElementById('input-tanggal').value;
        params.append('tanggal', tgl);
        url = '/api/rekap/harian?' + params;
        window._exportQuery = `tipe=harian&tanggal=${tgl}` + (unitKerjaId ? `&unit_id=${unitKerjaId}` : '');
    } else {
        const bln = document.getElementById('input-bulan').value;
        params.append('bulan', bln);
        url = '/api/rekap/bulanan?' + params;
        window._exportQuery = `tipe=bulanan&bulan=${bln}` + (unitKerjaId ? `&unit_id=${unitKerjaId}` : '');
    }

    try {
        const res  = await fetch(url, { headers });
        const { data } = await res.json();
        const list = data ?? [];

        document.getElementById('rekap-info').textContent = `${list.length} data`;
        document.getElementById('rekap-info').style.display = list.length ? 'inline' : 'none';

        if (!list.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="padding:48px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada data untuk periode ini</td></tr>`;
            return;
        }

        const statusBadge = {
            kembali:       { bg:'#f0fdf4', color:'#16a34a', border:'#bbf7d0', label:'Kembali' },
            terbuka:       { bg:'#fff7ed', color:'#ea580c', border:'#fed7aa', label:'Di Luar' },
            belum_kembali: { bg:'#fef2f2', color:'#dc2626', border:'#fecaca', label:'Belum Kembali' },
        };

        const tdBase = 'color:#0a2e5c;';

        if (activeTab === 'harian') {
            tbody.innerHTML = list.map(r => {
                const s = statusBadge[r.status] ?? { bg:'#dbeeff', color:'#0073e6', border:'#bfdfff', label: r.status ?? '-' };
                return `<tr>
                    <td style="font-weight:600;${tdBase}">${r.nama_lengkap ?? '-'}</td>
                    <td style="color:#0073e6;font-weight:600;">${r.jam_keluar ?? '-'}</td>
                    <td style="color:#64748b;">${r.jam_kembali ?? '—'}</td>
                    <td style="color:#64748b;">${r.durasi_menit ? r.durasi_menit + ' mnt' : '—'}</td>
                    <td style="color:#64748b;text-transform:capitalize;">${r.keperluan_jenis?.replace('_',' ') ?? '-'}</td>
                    <td>
                        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${s.bg};color:${s.color};border:1px solid ${s.border};">
                            ${s.label}
                        </span>
                    </td>
                </tr>`;
            }).join('');
        } else {
            tbody.innerHTML = list.map(r => `<tr>
                <td style="font-weight:600;${tdBase}">${r.nama_lengkap ?? '-'}</td>
                <td style="color:#64748b;">${r.jumlah_hari_keluar ?? 0} hari</td>
                <td style="color:#64748b;">${r.total_menit_keluar ?? 0} mnt</td>
                <td>
                    ${r.belum_kembali > 0
                        ? `<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">${r.belum_kembali}x</span>`
                        : `<span style="color:#94a3b8;">—</span>`}
                </td>
            </tr>`).join('');
        }
    } catch (_) {
        tbody.innerHTML = `<tr><td colspan="6" style="padding:48px;text-align:center;color:#dc2626;font-size:13px;">Gagal memuat data</td></tr>`;
    }
}

function exportDoc(tipe) {
    if (!window._exportQuery) {
        alert('Pilih tanggal/bulan dan klik Tampilkan terlebih dahulu sebelum mengunduh.');
        return;
    }
    window.open(tipe === 'pdf'
        ? `/api/rekap/export-pdf?${window._exportQuery}`
        : `/api/rekap/export-excel?${window._exportQuery}`, '_blank');
}

loadNamaUnit();
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
@endpush
