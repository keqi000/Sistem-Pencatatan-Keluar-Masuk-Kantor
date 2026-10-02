@extends('layouts.app')

@section('title', 'Izin Dinas')

@push('styles')
<style>
    .form-input {
        width: 100%; padding: 9px 14px;
        border-radius: 10px; border: 1.5px solid #dbeeff;
        background: #f8fbff; color: #0a2e5c;
        font-size: 13px; outline: none;
        transition: all 0.15s; font-family: inherit;
        box-sizing: border-box;
    }
    .form-input:focus {
        border-color: #5cc2f2;
        box-shadow: 0 0 0 3px rgba(92,194,242,0.15);
        background: #ffffff;
    }
    .form-label {
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.08em; color: #0a2e5c;
    }
    .tab-btn { padding: 6px 18px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; transition: all 0.15s; }
    .tab-btn.active { background: linear-gradient(135deg,#0073e6,#0056b3); color: white; box-shadow: 0 2px 8px rgba(0,115,230,0.25); }
    .tab-btn.inactive { background: transparent; color: #64748b; }
    .tab-btn.inactive:hover { background: #f0f7ff; color: #0a2e5c; }
    tbody tr { transition: background 0.12s; }
    tbody tr:hover { background: #f8fbff; }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Form Pengajuan --}}
    <div style="background:#ffffff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f0f7ff;display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Ajukan Izin Dinas</p>
        </div>

        <form id="form-izin" style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" id="input-tanggal" required class="form-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="form-label">Tujuan</label>
                <input type="text" name="tujuan" placeholder="Nama tempat / instansi tujuan" required class="form-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="form-label">Perkiraan Jam Pergi</label>
                <input type="time" name="perkiraan_jam_pergi" required class="form-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="form-label">Perkiraan Jam Kembali</label>
                <input type="time" name="perkiraan_jam_kembali" required class="form-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;grid-column:1/-1;">
                <label class="form-label">Keperluan</label>
                <textarea name="keperluan" rows="3" placeholder="Jelaskan keperluan dinas..." required class="form-input" style="resize:none;"></textarea>
            </div>
            <div style="grid-column:1/-1;display:flex;align-items:center;gap:12px;">
                <button type="submit" style="padding:9px 22px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                    Ajukan Izin
                </button>
                <span id="form-msg" style="display:none;font-size:13px;"></span>
            </div>
        </form>
    </div>

    {{-- Daftar Izin --}}
    <div style="background:#ffffff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f0f7ff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Riwayat Pengajuan</p>
            <div style="display:flex;gap:3px;background:#f8fbff;border-radius:9px;border:1px solid #dbeeff;padding:3px;">
                @foreach(['semua' => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $val => $label)
                <button onclick="filterIzin('{{ $val }}')" data-tab="{{ $val }}"
                    class="tab-btn {{ $val === 'semua' ? 'active' : 'inactive' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Tanggal</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Tujuan</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Jam</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Status</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Catatan Atasan</th>
                    </tr>
                </thead>
                <tbody id="izin-tbody">
                    <tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat data...</td></tr>
                </tbody>
            </table>
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
let allIzin = [];
document.getElementById('input-tanggal').min = new Date().toISOString().split('T')[0];

async function loadIzin() {
    try {
        const res = await fetch('/api/izin-dinas', { headers });
        const { data } = await res.json();
        allIzin = data ?? [];
        renderIzin(allIzin);
    } catch (_) {
        document.getElementById('izin-tbody').innerHTML = `<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;">Gagal memuat data</td></tr>`;
    }
}

function renderIzin(data) {
    const tbody = document.getElementById('izin-tbody');
    const td = 'padding:11px 16px;font-size:13px;border-bottom:1px solid #f0f7ff;';
    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Belum ada pengajuan</td></tr>`;
        return;
    }
    const badge = {
        menunggu:  { bg:'#fff7ed', color:'#ea580c', border:'#fed7aa', label:'Menunggu' },
        disetujui: { bg:'#f0fdf4', color:'#16a34a', border:'#bbf7d0', label:'Disetujui' },
        ditolak:   { bg:'#fef2f2', color:'#dc2626', border:'#fecaca', label:'Ditolak' },
    };
    tbody.innerHTML = data.map(d => {
        const b = badge[d.status] ?? { bg:'#dbeeff', color:'#0073e6', border:'#bfdfff', label: d.status };
        return `<tr>
            <td style="${td}color:#0a2e5c;font-weight:600;">${d.tanggal}</td>
            <td style="${td}color:#0a2e5c;">${d.tujuan}</td>
            <td style="${td}color:#64748b;font-size:12px;">${d.perkiraan_jam_pergi} — ${d.perkiraan_jam_kembali}</td>
            <td style="${td}">
                <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${b.bg};color:${b.color};border:1px solid ${b.border};">
                    ${b.label}
                </span>
            </td>
            <td style="${td}color:#64748b;font-size:12px;">${d.catatan_atasan ?? '—'}</td>
        </tr>`;
    }).join('');
}

function filterIzin(status) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.className = 'tab-btn ' + (btn.dataset.tab === status ? 'active' : 'inactive');
    });
    renderIzin(status === 'semua' ? allIzin : allIzin.filter(d => d.status === status));
}

document.getElementById('form-izin').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(e.target));
    try {
        const res = await fetch('/api/izin-dinas', { method:'POST', headers, body: JSON.stringify(payload) });
        const json = await res.json();
        if (res.ok) {
            showToast('Izin berhasil diajukan!', 'success');
            e.target.reset();
            document.getElementById('input-tanggal').min = new Date().toISOString().split('T')[0];
            loadIzin();
        } else { showToast(json.message ?? 'Gagal mengajukan izin', 'danger'); }
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); }
});

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'success' ? 'linear-gradient(135deg,#16a34a,#15803d)' : 'linear-gradient(135deg,#dc2626,#b91c1c)';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

loadIzin();
setInterval(loadIzin, 10000);
</script>
@endpush
