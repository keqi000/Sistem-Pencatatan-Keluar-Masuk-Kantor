@extends('layouts.app')

@section('title', 'Pengaturan')

@push('styles')
<style>
    .form-input {
        width: 100%; padding: 9px 12px;
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
    .section-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(92,194,242,0.2);
        box-shadow: 0 4px 20px rgba(10,46,92,0.06);
        overflow: hidden;
    }
    .section-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f7ff;
        display: flex; align-items: center; justify-content: space-between;
    }
    tbody tr { transition: background 0.12s; }
    tbody tr:hover { background: #f8fbff; }
    .modal-overlay {
        display: none; position: fixed; inset: 0; z-index: 50;
        align-items: center; justify-content: center; padding: 16px;
        background: rgba(10,46,92,0.3); backdrop-filter: blur(6px);
    }
    .modal-box {
        background: #ffffff; border-radius: 20px;
        box-shadow: 0 32px 80px rgba(10,46,92,0.2);
        width: 100%; max-width: 380px; padding: 24px;
        border: 1px solid #dbeeff;
        animation: modalIn 0.2s ease;
    }
    @keyframes modalIn {
        from { opacity:0; transform:scale(0.95) translateY(8px); }
        to   { opacity:1; transform:scale(1) translateY(0); }
    }
    @media (max-width: 640px) {
        .grid-jamkerja { grid-template-columns: 1fr !important; }
        .inline-settings { flex-direction: column !important; align-items: flex-start !important; gap: 12px !important; }
    }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Jam Kerja & QR --}}
    <div class="section-card">
        <div class="section-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Jam Kerja & QR</p>
            </div>
        </div>
        <form id="form-jamkerja" style="padding:20px;display:flex;flex-direction:column;gap:16px;">
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;" class="grid-jamkerja">
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="jam_masuk" id="jam_masuk" class="form-input">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="jam_pulang" id="jam_pulang" class="form-input">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Istirahat Mulai</label>
                    <input type="time" name="jam_istirahat_mulai" id="jam_istirahat_mulai" class="form-input">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Istirahat Selesai</label>
                    <input type="time" name="jam_istirahat_selesai" id="jam_istirahat_selesai" class="form-input">
                </div>
            </div>

            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:20px;padding:14px 16px;background:#f8fbff;border-radius:12px;border:1px solid #dbeeff;" class="inline-settings">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none;">
                    <input type="checkbox" name="hitung_jam_istirahat" id="hitung_jam_istirahat" style="width:15px;height:15px;accent-color:#0073e6;border-radius:4px;">
                    <span style="font-size:13px;color:#0a2e5c;font-weight:500;">Hitung jam istirahat dalam durasi</span>
                </label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <label class="form-label" style="white-space:nowrap;">Interval QR</label>
                    <input type="number" name="qr_interval" id="qr_interval" min="5" max="300"
                        class="form-input" style="width:80px;">
                    <span style="font-size:13px;color:#64748b;">detik</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <label class="form-label" style="white-space:nowrap;">Ambang Terlambat</label>
                    <input type="number" name="ambang_terlambat_menit" id="ambang_terlambat_menit" min="10" max="480"
                        class="form-input" style="width:80px;">
                    <span style="font-size:13px;color:#64748b;">menit</span>
                </div>
            </div>

            <div id="jamkerja-msg" style="display:none;font-size:13px;padding:10px 14px;border-radius:10px;"></div>

            <div>
                <button type="submit" style="padding:9px 22px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);transition:all 0.15s;"
                    onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

    {{-- Unit Kerja --}}
    <div class="section-card">
        <div class="section-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Unit Kerja</p>
            </div>
            <button onclick="openModalUnit()" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:12px;font-weight:700;border:none;cursor:pointer;box-shadow:0 2px 8px rgba(0,115,230,0.25);transition:all 0.15s;"
                onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tambah Unit
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Nama Unit</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Kode</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Pimpinan</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="unit-tbody">
                    <tr><td colspan="3" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Modal Unit Kerja --}}
<div id="modal-unit" class="modal-overlay">
    <div class="modal-box">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h3 id="modal-unit-title" style="font-size:15px;font-weight:800;color:#0a2e5c;"></h3>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px;">
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="form-label">Nama Unit <span style="color:#dc2626;">*</span></label>
                <input type="text" id="unit-nama" placeholder="Nama unit kerja" class="form-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="form-label">Kode Unit <span style="color:#dc2626;">*</span></label>
                <input type="text" id="unit-kode" placeholder="Contoh: TU, PD, dll" class="form-input">
            </div>
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="form-label">Pimpinan Unit</label>
                <select id="unit-pimpinan" class="form-input">
                    <option value="">Tidak ada / pilih nanti</option>
                </select>
            </div>
            <div id="unit-error" style="display:none;font-size:12px;color:#dc2626;padding:8px 12px;background:#fef2f2;border-radius:8px;border:1px solid #fecaca;"></div>
        </div>
        <div style="display:flex;gap:10px;">
            <button onclick="submitUnit()" style="flex:1;padding:10px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);transition:all 0.15s;">
                Simpan
            </button>
            <button onclick="closeModalUnit()" style="flex:1;padding:10px;border-radius:10px;background:#f8fbff;border:1.5px solid #dbeeff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.15s;"
                onmouseover="this.style.background='#dbeeff'" onmouseout="this.style.background='#f8fbff'">
                Batal
            </button>
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
let editUnitId = null;

async function loadJamKerja() {
    try {
        const res = await fetch('/api/pengaturan/jam-kerja', { headers });
        const json = await res.json();
        const s = json.settings ?? {};
        document.getElementById('jam_masuk').value              = s.jam_masuk ?? '';
        document.getElementById('jam_pulang').value             = s.jam_pulang ?? '';
        document.getElementById('jam_istirahat_mulai').value    = s.jam_istirahat_mulai ?? '';
        document.getElementById('jam_istirahat_selesai').value  = s.jam_istirahat_selesai ?? '';
        document.getElementById('qr_interval').value            = s.qr_interval ?? 10;
        document.getElementById('ambang_terlambat_menit').value = s.ambang_terlambat_menit ?? 120;
        document.getElementById('hitung_jam_istirahat').checked = s.hitung_jam_istirahat == 1;
    } catch (_) {}
}

document.getElementById('form-jamkerja').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msgEl = document.getElementById('jamkerja-msg');
    const payload = {
        jam_masuk:               document.getElementById('jam_masuk').value,
        jam_pulang:              document.getElementById('jam_pulang').value,
        jam_istirahat_mulai:     document.getElementById('jam_istirahat_mulai').value,
        jam_istirahat_selesai:   document.getElementById('jam_istirahat_selesai').value,
        qr_interval:             parseInt(document.getElementById('qr_interval').value),
        ambang_terlambat_menit:  parseInt(document.getElementById('ambang_terlambat_menit').value),
        hitung_jam_istirahat:    document.getElementById('hitung_jam_istirahat').checked ? 1 : 0,
    };
    try {
        const res = await fetch('/api/pengaturan/jam-kerja', { method:'POST', headers, body: JSON.stringify(payload) });
        const json = await res.json();
        msgEl.style.display = 'block';
        if (res.ok) {
            msgEl.textContent = '✓ Pengaturan berhasil disimpan';
            msgEl.style.background = '#f0fdf4'; msgEl.style.color = '#16a34a'; msgEl.style.border = '1px solid #bbf7d0';
        } else {
            msgEl.textContent = json.message ?? 'Gagal menyimpan';
            msgEl.style.background = '#fef2f2'; msgEl.style.color = '#dc2626'; msgEl.style.border = '1px solid #fecaca';
        }
        setTimeout(() => msgEl.style.display = 'none', 3000);
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); }
});

async function loadUnits() {
    try {
        const [unitRes, userRes] = await Promise.all([
            fetch('/api/pengaturan/unit-kerja', { headers }),
            fetch('/api/users?role=pimpinan', { headers }),
        ]);
        const { data } = await unitRes.json();
        const { data: pimpinanList } = await userRes.json();

        // Isi select pimpinan di modal
        const sel = document.getElementById('unit-pimpinan');
        sel.innerHTML = '<option value="">Tidak ada / pilih nanti</option>';
        (pimpinanList ?? []).forEach(u => sel.innerHTML += `<option value="${u.id}">${u.full_name}</option>`);

        const tbody = document.getElementById('unit-tbody');
        const td = 'padding:11px 16px;font-size:13px;border-bottom:1px solid #f0f7ff;';

        if (!data?.length) {
            tbody.innerHTML = `<tr><td colspan="4" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Belum ada unit kerja</td></tr>`;
            return;
        }
        tbody.innerHTML = data.map(u => `<tr>
            <td style="${td}font-weight:600;color:#0a2e5c;">${u.nama_unit}</td>
            <td style="${td}"><span style="display:inline-flex;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#dbeeff;color:#0073e6;border:1px solid rgba(92,194,242,0.4);">${u.kode_unit}</span></td>
            <td style="${td}color:#64748b;font-size:12px;">${u.pimpinan?.full_name ?? '<span style="color:#cbd5e1;">—</span>'}</td>
            <td style="${td}">
                <div style="display:flex;gap:12px;">
                    <button onclick="openModalUnit(${u.id},'${u.nama_unit}','${u.kode_unit}',${u.pimpinan_id ?? 'null'})" style="font-size:12px;font-weight:600;color:#0073e6;background:none;border:none;cursor:pointer;padding:0;">Edit</button>
                    <button onclick="hapusUnit(${u.id})" style="font-size:12px;font-weight:600;color:#dc2626;background:none;border:none;cursor:pointer;padding:0;">Hapus</button>
                </div>
            </td>
        </tr>`).join('');
    } catch (_) {}
}

function openModalUnit(id = null, nama = '', kode = '', pimpinanId = null) {
    editUnitId = id;
    document.getElementById('modal-unit-title').textContent = id ? 'Edit Unit Kerja' : 'Tambah Unit Kerja';
    document.getElementById('unit-nama').value = nama;
    document.getElementById('unit-kode').value = kode;
    document.getElementById('unit-pimpinan').value = pimpinanId ?? '';
    document.getElementById('unit-error').style.display = 'none';
    document.getElementById('modal-unit').style.display = 'flex';
}

function closeModalUnit() {
    document.getElementById('modal-unit').style.display = 'none';
    editUnitId = null;
}

async function submitUnit() {
    const nama = document.getElementById('unit-nama').value.trim();
    const kode = document.getElementById('unit-kode').value.trim();
    const pimpinanId = document.getElementById('unit-pimpinan').value;
    const errEl = document.getElementById('unit-error');
    if (!nama || !kode) { errEl.textContent = 'Nama dan kode unit wajib diisi'; errEl.style.display = 'block'; return; }
    try {
        const body = {
            subaction: editUnitId ? 'update' : 'create',
            nama_unit: nama,
            kode_unit: kode,
        };
        if (editUnitId) body.id = editUnitId;
        if (pimpinanId) body.pimpinan_id = parseInt(pimpinanId);
        const res = await fetch('/api/pengaturan/unit-kerja', { method:'POST', headers, body: JSON.stringify(body) });
        const json = await res.json();
        if (res.ok) { closeModalUnit(); showToast(editUnitId ? 'Unit berhasil diperbarui' : 'Unit berhasil ditambahkan', 'success'); loadUnits(); }
        else { errEl.textContent = json.message ?? 'Gagal menyimpan'; errEl.style.display = 'block'; }
    } catch (_) { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'block'; }
}

async function hapusUnit(id) {
    if (!confirm('Hapus unit kerja ini?')) return;
    try {
        const res = await fetch('/api/pengaturan/unit-kerja', {
            method: 'POST', headers,
            body: JSON.stringify({ subaction: 'delete', id }),
        });
        const json = await res.json();
        if (res.ok) { showToast('Unit berhasil dihapus', 'success'); loadUnits(); }
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

loadJamKerja();
loadUnits();
</script>
@endpush
