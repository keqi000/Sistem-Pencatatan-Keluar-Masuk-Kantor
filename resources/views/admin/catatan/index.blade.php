@extends('layouts.app')

@section('title', 'Manajemen Catatan')

@push('styles')
<style>
    .filter-input {
        padding: 8px 12px; border-radius: 10px;
        border: 1.5px solid #dbeeff; background: #f8fbff;
        color: #0a2e5c; font-size: 13px; outline: none;
        transition: all 0.15s; font-family: inherit;
    }
    .filter-input:focus {
        border-color: #5cc2f2;
        box-shadow: 0 0 0 3px rgba(92,194,242,0.15);
        background: #ffffff;
    }
    tbody tr { transition: background 0.12s; }
    tbody tr:hover { background: #f8fbff; }
    .modal-overlay {
        display: none; position: fixed; inset: 0; z-index: 50;
        align-items: center; justify-content: center; padding: 16px;
        background: rgba(10,46,92,0.3); backdrop-filter: blur(6px);
    }
    @keyframes modalIn { from{opacity:0;transform:scale(0.95) translateY(8px)} to{opacity:1;transform:scale(1) translateY(0)} }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Filter --}}
    <div style="background:#ffffff;border-radius:16px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 2px 12px rgba(10,46,92,0.05);padding:16px 18px;">
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;">
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Tanggal</label>
                <input type="date" id="filter-tanggal" class="filter-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Pegawai</label>
                <input type="text" id="filter-pegawai" placeholder="Nama pegawai..." class="filter-input" style="width:180px;">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Status</label>
                <select id="filter-status" class="filter-input">
                    <option value="">Semua</option>
                    <option value="terbuka">Terbuka</option>
                    <option value="kembali">Kembali</option>
                    <option value="belum_kembali">Belum Kembali</option>
                </select>
            </div>
            <button onclick="loadCatatan(1)" style="padding:8px 20px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);transition:all 0.15s;"
                onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                Cari
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <div style="background:#ffffff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f0f7ff;display:flex;align-items:center;justify-content:space-between;">
            <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Daftar Catatan</p>
            <span id="total-info" style="font-size:12px;font-weight:600;color:#0073e6;padding:3px 12px;border-radius:99px;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);display:none;"></span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Pegawai</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Tanggal</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Jam Keluar</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Jam Kembali</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Durasi</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Status</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="catatan-tbody">
                    <tr><td colspan="7" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="pagination" style="display:none;padding:14px 20px;border-top:1px solid #f0f7ff;align-items:center;justify-content:space-between;">
            <button id="btn-prev" onclick="changePage(-1)" style="padding:6px 16px;border-radius:9px;border:1.5px solid #dbeeff;background:#f8fbff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;"
                onmouseover="this.style.background='#dbeeff'" onmouseout="this.style.background='#f8fbff'">← Sebelumnya</button>
            <span id="page-info" style="font-size:12px;color:#64748b;"></span>
            <button id="btn-next" onclick="changePage(1)" style="padding:6px 16px;border-radius:9px;border:1.5px solid #dbeeff;background:#f8fbff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;"
                onmouseover="this.style.background='#dbeeff'" onmouseout="this.style.background='#f8fbff'">Berikutnya →</button>
        </div>
    </div>

</div>

{{-- Modal Hapus --}}
<div id="modal-hapus" class="modal-overlay">
    <div style="background:#ffffff;border-radius:20px;box-shadow:0 32px 80px rgba(10,46,92,0.2);width:100%;max-width:360px;padding:28px;border:1px solid #dbeeff;text-align:center;animation:modalIn 0.2s ease;">
        <div style="width:52px;height:52px;border-radius:14px;background:#fef2f2;border:1px solid #fecaca;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 style="font-size:15px;font-weight:800;color:#0a2e5c;margin-bottom:6px;">Hapus Catatan?</h3>
        <p style="font-size:13px;color:#64748b;margin-bottom:20px;">Catatan ini akan dihapus permanen.</p>
        <div style="display:flex;gap:10px;">
            <button onclick="confirmHapus()" style="flex:1;padding:10px;border-radius:10px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(220,38,38,0.25);">Ya, Hapus</button>
            <button onclick="closeHapus()" style="flex:1;padding:10px;border-radius:10px;background:#f8fbff;border:1.5px solid #dbeeff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;"
                onmouseover="this.style.background='#dbeeff'" onmouseout="this.style.background='#f8fbff'">Batal</button>
        </div>
    </div>
</div>

<div id="toast" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:60;padding:12px 24px;border-radius:12px;color:white;font-size:13px;font-weight:600;min-width:220px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,0.15);"></div>
@endsection

@push('scripts')
<script>
const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};
let currentPage = 1, lastMeta = null, hapusId = null;

document.getElementById('filter-tanggal').value = new Date().toISOString().split('T')[0];

async function loadCatatan(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({ page, per_page: 20 });
    const tanggal = document.getElementById('filter-tanggal').value;
    const pegawai = document.getElementById('filter-pegawai').value;
    const status  = document.getElementById('filter-status').value;
    if (tanggal) params.append('tanggal', tanggal);
    if (pegawai) params.append('search', pegawai);
    if (status)  params.append('status', status);

    const tbody = document.getElementById('catatan-tbody');
    tbody.innerHTML = `<tr><td colspan="7" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat...</td></tr>`;

    try {
        const res = await fetch('/api/riwayat/all?' + params, { headers });
        const json = await res.json();
        const data = json.data ?? [];
        lastMeta = json.meta ?? null;
        const total = json.total ?? data.length;
        const totalPage = json.total_page ?? lastMeta?.last_page ?? 1;
        const currentPageRes = json.page ?? lastMeta?.current_page ?? 1;

        const totalInfo = document.getElementById('total-info');
        const pagination = document.getElementById('pagination');

        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="7" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada data</td></tr>`;
            totalInfo.style.display = 'none'; pagination.style.display = 'none'; return;
        }

        const td = 'padding:11px 16px;font-size:13px;border-bottom:1px solid #f0f7ff;';
        const statusBadge = {
            terbuka:       { bg:'#fff7ed', color:'#ea580c', border:'#fed7aa', label:'Di Luar' },
            kembali:       { bg:'#f0fdf4', color:'#16a34a', border:'#bbf7d0', label:'Kembali' },
            belum_kembali: { bg:'#fef2f2', color:'#dc2626', border:'#fecaca', label:'Belum Kembali' },
        };

        tbody.innerHTML = data.map(r => {
            const s = statusBadge[r.status] ?? { bg:'#dbeeff', color:'#0073e6', border:'#bfdfff', label: r.status ?? '-' };
            const jamKeluar  = r.jam_keluar  ? r.jam_keluar.substring(11,19)  : '-';
            const jamKembali = r.jam_kembali ? r.jam_kembali.substring(11,19) : '—';
            const tanggal    = r.jam_keluar  ? r.jam_keluar.substring(0,10)   : '-';
            return `<tr>
                <td style="${td}">
                    <p style="font-weight:600;color:#0a2e5c;">${r.nama_lengkap ?? '-'}</p>
                    <p style="font-size:11px;color:#94a3b8;">${r.nama_unit ?? ''}</p>
                </td>
                <td style="${td}color:#0a2e5c;">${tanggal}</td>
                <td style="${td}color:#0073e6;font-weight:600;">${jamKeluar}</td>
                <td style="${td}color:#64748b;">${jamKembali}</td>
                <td style="${td}color:#64748b;">${r.durasi_menit ? r.durasi_menit + ' mnt' : '—'}</td>
                <td style="${td}">
                    <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${s.bg};color:${s.color};border:1px solid ${s.border};">
                        ${s.label}
                    </span>
                </td>
                <td style="${td}">
                    <div style="display:flex;gap:6px;align-items:center;">
                        ${r.status === 'terbuka' ? `<button onclick="tutupManual(${r.pasangan_id})" title="Tutup Manual" style="width:28px;height:28px;border-radius:7px;background:#f0fdf4;border:1px solid #bbf7d0;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>` : ''}
                        <a href="/admin/catatan/${r.pasangan_id}/edit" title="Edit" style="width:28px;height:28px;border-radius:7px;background:#dbeeff;border:1px solid rgba(92,194,242,0.4);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.background='#bfdfff'" onmouseout="this.style.background='#dbeeff'"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#0073e6" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                        <button onclick="openHapus(${r.pasangan_id})" title="Hapus" style="width:28px;height:28px;border-radius:7px;background:#fef2f2;border:1px solid #fecaca;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        totalInfo.textContent = `${total} data`;
        totalInfo.style.display = 'inline';
        document.getElementById('page-info').textContent = `Halaman ${currentPageRes} dari ${totalPage}`;
        document.getElementById('btn-prev').disabled = currentPageRes <= 1;
        document.getElementById('btn-next').disabled = currentPageRes >= totalPage;
        pagination.style.display = 'flex';
        lastMeta = { current_page: currentPageRes, last_page: totalPage };
    } catch (_) {
        document.getElementById('catatan-tbody').innerHTML = `<tr><td colspan="7" style="padding:40px;text-align:center;color:#dc2626;font-size:13px;">Gagal memuat data</td></tr>`;
    }
}

async function tutupManual(pasanganId) {
    try {
        const res = await fetch('/api/pindaian/tutup-manual', { method:'POST', headers, body: JSON.stringify({ pasangan_id: pasanganId }) });
        const json = await res.json();
        if (res.ok) { showToast('Catatan berhasil ditutup', 'success'); loadCatatan(currentPage); }
        else showToast(json.message ?? 'Gagal menutup catatan', 'danger');
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); }
}

function changePage(dir) {
    if (!lastMeta) return;
    const next = currentPage + dir;
    if (next < 1 || next > lastMeta.last_page) return;
    loadCatatan(next);
}

function openHapus(id) { hapusId = id; document.getElementById('modal-hapus').style.display = 'flex'; }
function closeHapus() { document.getElementById('modal-hapus').style.display = 'none'; hapusId = null; }

async function confirmHapus() {
    try {
        const res = await fetch(`/api/pindaian/${hapusId}`, { method:'DELETE', headers });
        const json = await res.json();
        closeHapus();
        if (res.ok) { showToast('Catatan berhasil dihapus', 'success'); loadCatatan(currentPage); }
        else showToast(json.message ?? 'Gagal menghapus', 'danger');
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); }
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'success' ? 'linear-gradient(135deg,#16a34a,#15803d)' : 'linear-gradient(135deg,#dc2626,#b91c1c)';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

loadCatatan();
</script>
@endpush
