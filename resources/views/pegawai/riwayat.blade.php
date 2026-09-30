@extends('layouts.app')

@section('title', 'Riwayat Saya')

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
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Filter --}}
    <div style="background:#ffffff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);padding:18px 20px;">
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px;">
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Dari Tanggal</label>
                <input type="date" id="filter-dari" class="filter-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Sampai Tanggal</label>
                <input type="date" id="filter-sampai" class="filter-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;">Keperluan</label>
                <select id="filter-keperluan" class="filter-input">
                    <option value="">Semua</option>
                    <option value="dinas">Dinas</option>
                    <option value="keperluan_lain">Keperluan Lain</option>
                </select>
            </div>
            <button onclick="loadRiwayat(1)" style="padding:8px 20px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);transition:all 0.15s;"
                onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                Cari
            </button>
            <button onclick="resetFilter()" style="padding:8px 16px;border-radius:10px;background:#f8fbff;border:1.5px solid #dbeeff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.15s;"
                onmouseover="this.style.background='#dbeeff'" onmouseout="this.style.background='#f8fbff'">
                Reset
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <div style="background:#ffffff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f0f7ff;display:flex;align-items:center;justify-content:space-between;">
            <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Riwayat Aktivitas</p>
            <span id="total-info" style="font-size:12px;font-weight:600;color:#0073e6;padding:3px 12px;border-radius:99px;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);display:none;"></span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Tanggal</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Jam Keluar</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Jam Kembali</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Durasi</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Keperluan</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Status</th>
                    </tr>
                </thead>
                <tbody id="riwayat-tbody">
                    <tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="pagination" style="display:none;padding:14px 20px;border-top:1px solid #f0f7ff;display:flex;align-items:center;justify-content:space-between;">
            <button id="btn-prev" onclick="changePage(-1)" style="padding:6px 16px;border-radius:9px;border:1.5px solid #dbeeff;background:#f8fbff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;"
                onmouseover="this.style.background='#dbeeff'" onmouseout="this.style.background='#f8fbff'">← Sebelumnya</button>
            <span id="page-info" style="font-size:12px;color:#64748b;"></span>
            <button id="btn-next" onclick="changePage(1)" style="padding:6px 16px;border-radius:9px;border:1.5px solid #dbeeff;background:#f8fbff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;"
                onmouseover="this.style.background='#dbeeff'" onmouseout="this.style.background='#f8fbff'">Berikutnya →</button>
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
let currentPage = 1, lastMeta = null;

async function loadRiwayat(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({ page, per_page: 15 });
    const dari = document.getElementById('filter-dari').value;
    const sampai = document.getElementById('filter-sampai').value;
    const keperluan = document.getElementById('filter-keperluan').value;
    if (dari) params.append('start_date', dari);
    if (sampai) params.append('end_date', sampai);
    if (keperluan) params.append('keperluan_jenis', keperluan);

    const tbody = document.getElementById('riwayat-tbody');
    tbody.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat...</td></tr>`;

    try {
        const res = await fetch('/api/riwayat/saya?' + params, { headers });
        const json = await res.json();
        const data = json.data ?? [];
        const totalPage = json.total_page ?? 1;
        const currentPageRes = json.page ?? 1;
        const total = json.total ?? 0;

        const totalInfo = document.getElementById('total-info');
        const pagination = document.getElementById('pagination');

        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada data</td></tr>`;
            totalInfo.style.display = 'none';
            pagination.style.display = 'none';
            return;
        }

        const td = 'padding:11px 16px;font-size:13px;border-bottom:1px solid #f0f7ff;';
        const statusBadge = {
            terbuka:       { bg:'#fff7ed', color:'#ea580c', border:'#fed7aa', label:'Di Luar' },
            kembali:       { bg:'#f0fdf4', color:'#16a34a', border:'#bbf7d0', label:'Kembali' },
            belum_kembali: { bg:'#fef2f2', color:'#dc2626', border:'#fecaca', label:'Belum Kembali' },
        };

        tbody.innerHTML = data.map(r => {
            const s = statusBadge[r.status] ?? { bg:'#dbeeff', color:'#0073e6', border:'#bfdfff', label: r.status ?? '-' };
            const jamKeluar = r.jam_keluar ? r.jam_keluar.substring(11,19) : '-';
            const jamKembali = r.jam_kembali ? r.jam_kembali.substring(11,19) : '—';
            const tanggal = r.jam_keluar ? r.jam_keluar.substring(0,10) : '-';
            return `<tr>
                <td style="${td}color:#0a2e5c;font-weight:600;">${tanggal}</td>
                <td style="${td}color:#0073e6;font-weight:600;">${jamKeluar}</td>
                <td style="${td}color:#64748b;">${jamKembali}</td>
                <td style="${td}color:#64748b;">${r.durasi_menit ? r.durasi_menit + ' mnt' : '—'}</td>
                <td style="${td}color:#64748b;text-transform:capitalize;">${r.keperluan_jenis?.replace('_',' ') ?? '-'}</td>
                <td style="${td}">
                    <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${s.bg};color:${s.color};border:1px solid ${s.border};">
                        ${s.label}
                    </span>
                </td>
            </tr>`;
        }).join('');

        if (total > 0) {
            totalInfo.textContent = `${total} data`;
            totalInfo.style.display = 'inline';
            document.getElementById('page-info').textContent = `Halaman ${currentPageRes} dari ${totalPage}`;
            document.getElementById('btn-prev').disabled = currentPageRes <= 1;
            document.getElementById('btn-next').disabled = currentPageRes >= totalPage;
            pagination.style.display = 'flex';
            lastMeta = { current_page: currentPageRes, last_page: totalPage };
        }
    } catch (_) {
        document.getElementById('riwayat-tbody').innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:#dc2626;font-size:13px;">Gagal memuat data</td></tr>`;
    }
}

function changePage(dir) {
    if (!lastMeta) return;
    const next = currentPage + dir;
    if (next < 1 || next > lastMeta.last_page) return;
    loadRiwayat(next);
}

function resetFilter() {
    document.getElementById('filter-dari').value = '';
    document.getElementById('filter-sampai').value = '';
    document.getElementById('filter-keperluan').value = '';
    loadRiwayat(1);
}

loadRiwayat();
</script>
@endpush
