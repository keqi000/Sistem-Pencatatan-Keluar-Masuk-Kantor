@extends('layouts.app')
@section('title', 'Manajemen Akun & Pegawai')
@push('styles')
<style>
.filter-input,.form-input{padding:8px 12px;border-radius:10px;border:1.5px solid #dbeeff;background:#f8fbff;color:#0a2e5c;font-size:13px;outline:none;transition:all 0.15s;font-family:inherit;box-sizing:border-box;}
.filter-input:focus,.form-input:focus{border-color:#5cc2f2;box-shadow:0 0 0 3px rgba(92,194,242,0.15);background:#fff;}
.form-input{width:100%;}
.form-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;}
.tab-btn{padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all 0.15s;}
.tab-btn.active{background:linear-gradient(135deg,#0073e6,#0056b3);color:white;box-shadow:0 2px 8px rgba(0,115,230,0.25);}
.tab-btn.inactive{background:transparent;color:#64748b;}
.tab-btn.inactive:hover{background:#f0f7ff;color:#0a2e5c;}
tbody tr{transition:background 0.12s;}
tbody tr:hover{background:#f8fbff;}
.modal-overlay{display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:16px;background:rgba(10,46,92,0.3);backdrop-filter:blur(6px);}
.modal-box{background:#fff;border-radius:20px;box-shadow:0 32px 80px rgba(10,46,92,0.2);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;padding:24px;border:1px solid #dbeeff;animation:modalIn 0.2s ease;}
@keyframes modalIn{from{opacity:0;transform:scale(0.95) translateY(8px)}to{opacity:1;transform:scale(1) translateY(0)}}
.section-divider{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;padding:8px 0 4px;border-bottom:1px solid #f0f7ff;margin-bottom:8px;}
.foto-preview{width:64px;height:64px;border-radius:12px;object-fit:cover;border:2px solid #dbeeff;}
.foto-placeholder{width:64px;height:64px;border-radius:12px;background:linear-gradient(135deg,#0073e6,#5cc2f2);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:white;flex-shrink:0;}
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- Toolbar --}}
    <div style="background:#fff;border-radius:16px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 2px 12px rgba(10,46,92,0.05);padding:14px 18px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <div style="display:flex;gap:3px;background:#f8fbff;border-radius:10px;border:1px solid #dbeeff;padding:3px;">
            @foreach(['semua'=>'Semua','pegawai'=>'Pegawai','atasan'=>'Atasan','pimpinan'=>'Pimpinan','admin'=>'Admin','lobby'=>'Lobby','pos'=>'Pos'] as $val=>$label)
            <button class="tab-btn {{ $val==='semua'?'active':'inactive' }}" data-tab="{{ $val }}" onclick="filterRole('{{ $val }}')">{{ $label }}</button>
            @endforeach
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="filter-search" placeholder="Cari nama / username / NIP..." oninput="loadUsers()" class="filter-input" style="width:220px;" autocomplete="off">
            <button onclick="openModal()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tambah
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <div style="background:#fff;border-radius:20px;border:1px solid rgba(92,194,242,0.2);box-shadow:0 4px 20px rgba(10,46,92,0.06);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f0f7ff;display:flex;align-items:center;justify-content:space-between;">
            <p style="font-size:14px;font-weight:800;color:#0a2e5c;">Daftar Akun & Pegawai</p>
            <span id="total-info" style="font-size:12px;font-weight:600;color:#0073e6;padding:3px 12px;border-radius:99px;background:#dbeeff;border:1px solid rgba(92,194,242,0.3);display:none;"></span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fbff;border-bottom:1px solid #dbeeff;">
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Pegawai / Akun</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">NIP & Jabatan</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Unit Kerja</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Role</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Status</th>
                        <th style="padding:10px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:left;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="user-tbody">
                    <tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah/Edit --}}
<div id="modal-akun" class="modal-overlay">
    <div class="modal-box">
        <input type="text" style="display:none;" autocomplete="username">
        <input type="password" style="display:none;" autocomplete="new-password">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#0a2e5c,#0073e6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h3 id="modal-title" style="font-size:15px;font-weight:800;color:#0a2e5c;"></h3>
        </div>

        {{-- Section: Info Akun --}}
        <div class="section-divider">Info Akun Login</div>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="form-label">Nama Lengkap <span style="color:#dc2626;">*</span></label>
                <input type="text" id="akun-nama" placeholder="Nama lengkap" class="form-input" autocomplete="off">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Username <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="akun-username" placeholder="Username login" class="form-input" autocomplete="off">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label" id="akun-password-label">Password <span style="color:#dc2626;">*</span></label>
                    <input type="password" id="akun-password" placeholder="Min. 6 karakter" class="form-input" autocomplete="new-password">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Role <span style="color:#dc2626;">*</span></label>
                    <select id="akun-role" class="form-input" onchange="onRoleChange()">
                        <option value="">Pilih role</option>
                        <option value="pegawai">Pegawai</option>
                        <option value="atasan">Atasan</option>
                        <option value="pimpinan">Pimpinan</option>
                        <option value="admin">Admin</option>
                        <option value="lobby">Lobby</option>
                        <option value="pos">Pos</option>
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Status Akun</label>
                    <select id="akun-status" class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Section: Data Pegawai (kondisional) --}}
        <div id="pegawai-section" style="display:none;">
            <div class="section-divider">Data Kepegawaian</div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">

                {{-- Foto --}}
                <div style="display:flex;align-items:center;gap:14px;">
                    <div id="foto-preview-wrap">
                        <div class="foto-placeholder" id="foto-placeholder">?</div>
                        <img id="foto-preview" class="foto-preview" style="display:none;" src="" alt="foto">
                    </div>
                    <div style="flex:1;">
                        <label class="form-label" style="display:block;margin-bottom:5px;">Foto Pegawai</label>
                        <input type="file" id="akun-foto" accept="image/*" class="form-input" style="padding:5px;" onchange="previewFoto(this)">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label class="form-label">NIP <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="akun-nip" placeholder="NIP pegawai" class="form-input" autocomplete="off">
                    </div>
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label class="form-label">Nomor HP</label>
                        <input type="text" id="akun-hp" placeholder="08xx..." class="form-input" autocomplete="off">
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Jabatan <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="akun-jabatan" placeholder="Jabatan / posisi" class="form-input" autocomplete="off">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label class="form-label">Unit Kerja <span style="color:#dc2626;">*</span></label>
                        <select id="akun-unit" class="form-input">
                            <option value="">Pilih unit...</option>
                        </select>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label class="form-label">Atasan Langsung</label>
                        <select id="akun-atasan" class="form-input">
                            <option value="">Tidak ada</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="form-label">Status Kepegawaian</label>
                    <select id="akun-status-pegawai" class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="akun-error" style="display:none;font-size:12px;color:#dc2626;padding:8px 12px;background:#fef2f2;border-radius:8px;border:1px solid #fecaca;margin-bottom:12px;"></div>

        <div style="display:flex;gap:10px;">
            <button onclick="submitAkun()" style="flex:1;padding:10px;border-radius:10px;background:linear-gradient(135deg,#0073e6,#0056b3);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(0,115,230,0.25);">Simpan</button>
            <button onclick="closeModal()" style="flex:1;padding:10px;border-radius:10px;background:#f8fbff;border:1.5px solid #dbeeff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;">Batal</button>
        </div>
    </div>
</div>

{{-- Modal Reset Password --}}
<div id="modal-reset" class="modal-overlay">
    <div class="modal-box" style="max-width:360px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:34px;height:34px;border-radius:9px;background:#fff7ed;border:1px solid #fed7aa;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#ea580c" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h3 style="font-size:15px;font-weight:800;color:#0a2e5c;">Reset Password</h3>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:18px;">
            <label class="form-label">Password Baru <span style="color:#dc2626;">*</span></label>
            <input type="password" id="reset-password" placeholder="Min. 6 karakter" class="form-input">
            <div id="reset-error" style="display:none;font-size:12px;color:#dc2626;margin-top:4px;"></div>
        </div>
        <div style="display:flex;gap:10px;">
            <button onclick="submitReset()" style="flex:1;padding:10px;border-radius:10px;background:linear-gradient(135deg,#ea580c,#c2410c);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;">Reset</button>
            <button onclick="closeReset()" style="flex:1;padding:10px;border-radius:10px;background:#f8fbff;border:1.5px solid #dbeeff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;">Batal</button>
        </div>
    </div>
</div>

{{-- Modal Hapus --}}
<div id="modal-hapus" class="modal-overlay">
    <div class="modal-box" style="max-width:360px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:34px;height:34px;border-radius:9px;background:#fef2f2;border:1px solid #fecaca;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 style="font-size:15px;font-weight:800;color:#0a2e5c;">Hapus Akun</h3>
        </div>
        <p id="hapus-info" style="font-size:13px;color:#64748b;margin-bottom:18px;line-height:1.5;"></p>
        <div style="display:flex;gap:10px;">
            <button onclick="confirmHapus()" style="flex:1;padding:10px;border-radius:10px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;">Hapus</button>
            <button onclick="closeHapus()" style="flex:1;padding:10px;border-radius:10px;background:#f8fbff;border:1.5px solid #dbeeff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;">Batal</button>
        </div>
    </div>
</div>

<div id="toast" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:60;padding:12px 24px;border-radius:12px;color:white;font-size:13px;font-weight:600;min-width:220px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,0.15);"></div>
@endsection

@push('scripts')
<script>
const H = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};
const HJ = { ...H, 'Content-Type': 'application/json' };

let activeRole = 'semua', editId = null, editPegawaiId = null, resetId = null, hapusId = null;
let allUsers = [], unitList = [], atasanList = [];

const roleBadge = {
    pegawai:  { bg:'#dbeeff',  color:'#0073e6', border:'rgba(92,194,242,0.4)', label:'Pegawai' },
    atasan:   { bg:'#fff7ed',  color:'#ea580c', border:'#fed7aa',              label:'Atasan' },
    pimpinan: { bg:'#fdf4ff',  color:'#9333ea', border:'#e9d5ff',              label:'Pimpinan' },
    admin:    { bg:'#fef2f2',  color:'#dc2626', border:'#fecaca',              label:'Admin' },
    lobby:    { bg:'#f0fdf4',  color:'#16a34a', border:'#bbf7d0',              label:'Lobby' },
    pos:      { bg:'#f0f7ff',  color:'#0a2e5c', border:'#bfdfff',              label:'Pos' },
};

async function loadDropdowns() {
    try {
        const [ru, rp] = await Promise.all([
            fetch('/api/pengaturan/unit-kerja', { headers: H }),
            fetch('/api/pegawai?all=1', { headers: H }),
        ]);
        const ju = await ru.json(); unitList = ju.data ?? [];
        const jp = await rp.json(); atasanList = jp.data ?? [];

        const selUnit = document.getElementById('akun-unit');
        selUnit.innerHTML = '<option value="">Pilih unit...</option>';
        unitList.forEach(u => selUnit.innerHTML += `<option value="${u.id}">${u.nama_unit}</option>`);

        const selAtasan = document.getElementById('akun-atasan');
        selAtasan.innerHTML = '<option value="">Tidak ada</option>';
        atasanList.forEach(p => selAtasan.innerHTML += `<option value="${p.id}">${p.nama_lengkap}</option>`);
    } catch (_) {}
}

async function loadUsers() {
    const search = document.getElementById('filter-search').value;
    const params = new URLSearchParams();
    if (activeRole !== 'semua') params.append('role', activeRole);
    if (search) params.append('search', search);

    const tbody = document.getElementById('user-tbody');
    const td = 'padding:11px 16px;font-size:13px;border-bottom:1px solid #f0f7ff;vertical-align:middle;';
    tbody.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Memuat...</td></tr>`;

    try {
        const res = await fetch('/api/users?' + params, { headers: H });
        const json = await res.json();
        const data = json.data ?? [];
        allUsers = data;

        const ti = document.getElementById('total-info');
        ti.textContent = `${data.length} akun`;
        ti.style.display = data.length ? 'inline' : 'none';

        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Tidak ada akun</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(u => {
            const b = roleBadge[u.role] ?? { bg:'#f8fbff', color:'#64748b', border:'#dbeeff', label: u.role };
            const p = u.pegawai;
            const inisial = (p?.nama_lengkap ?? u.full_name).charAt(0).toUpperCase();
            const fotoHtml = p?.foto
                ? `<img src="/${p.foto}" style="width:34px;height:34px;border-radius:8px;object-fit:cover;border:1.5px solid #dbeeff;">`
                : `<div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#0073e6,#5cc2f2);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:white;flex-shrink:0;">${inisial}</div>`;

            const nipJabatan = p
                ? `<p style="font-size:12px;color:#0a2e5c;font-weight:600;">${p.nip}</p><p style="font-size:11px;color:#94a3b8;">${p.jabatan}</p>`
                : `<span style="color:#cbd5e1;font-size:12px;">—</span>`;

            const unit = p?.unit_kerja?.nama_unit
                ? `<span style="font-size:12px;color:#0a2e5c;">${p.unit_kerja.nama_unit}</span>`
                : `<span style="color:#cbd5e1;font-size:12px;">—</span>`;

            return `<tr>
                <td style="${td}">
                    <div style="display:flex;align-items:center;gap:10px;">
                        ${fotoHtml}
                        <div>
                            <p style="font-weight:700;color:#0a2e5c;font-size:13px;">${u.full_name}</p>
                            <p style="font-size:11px;color:#94a3b8;">@${u.username}</p>
                        </div>
                    </div>
                </td>
                <td style="${td}">${nipJabatan}</td>
                <td style="${td}">${unit}</td>
                <td style="${td}"><span style="display:inline-flex;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${b.bg};color:${b.color};border:1px solid ${b.border};">${b.label}</span></td>
                <td style="${td}"><span style="display:inline-flex;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${u.status==='aktif'?'#f0fdf4':'#fef2f2'};color:${u.status==='aktif'?'#16a34a':'#dc2626'};border:1px solid ${u.status==='aktif'?'#bbf7d0':'#fecaca'};">${u.status==='aktif'?'Aktif':'Tidak Aktif'}</span></td>
                <td style="${td}">
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button onclick="openModal(${u.id})" title="Edit" style="width:28px;height:28px;border-radius:7px;background:#dbeeff;border:1px solid rgba(92,194,242,0.4);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.background='#bfdfff'" onmouseout="this.style.background='#dbeeff'"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#0073e6" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                        <button onclick="openReset(${u.id})" title="Reset Password" style="width:28px;height:28px;border-radius:7px;background:#fff7ed;border:1px solid #fed7aa;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.background='#ffedd5'" onmouseout="this.style.background='#fff7ed'"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#ea580c" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></button>
                        <button onclick="openHapus(${u.id},'${u.full_name.replace(/'/g,"\\'")}',${u.status==='aktif'})" title="Hapus" style="width:28px;height:28px;border-radius:7px;background:#fef2f2;border:1px solid #fecaca;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'"><svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    } catch (_) {
        tbody.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:#dc2626;font-size:13px;">Gagal memuat data</td></tr>`;
    }
}

function filterRole(role) {
    activeRole = role;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.className = 'tab-btn ' + (btn.dataset.tab === role ? 'active' : 'inactive');
    });
    loadUsers();
}

function onRoleChange() {
    const role = document.getElementById('akun-role').value;
    const needPegawai = ['pegawai','atasan','pimpinan'].includes(role);
    document.getElementById('pegawai-section').style.display = needPegawai ? 'block' : 'none';
}

function previewFoto(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('foto-preview').src = e.target.result;
        document.getElementById('foto-preview').style.display = 'block';
        document.getElementById('foto-placeholder').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function openModal(id = null) {
    editId = id;
    editPegawaiId = null;
    document.getElementById('modal-title').textContent = id ? 'Edit Akun & Data Pegawai' : 'Tambah Akun Baru';
    ['akun-nama','akun-username','akun-password','akun-nip','akun-hp','akun-jabatan'].forEach(i => document.getElementById(i).value = '');
    document.getElementById('akun-role').value = '';
    document.getElementById('akun-status').value = 'aktif';
    document.getElementById('akun-status-pegawai').value = 'aktif';
    document.getElementById('akun-unit').value = '';
    document.getElementById('akun-atasan').value = '';
    document.getElementById('akun-foto').value = '';
    document.getElementById('foto-preview').style.display = 'none';
    document.getElementById('foto-placeholder').style.display = 'flex';
    document.getElementById('foto-placeholder').textContent = '?';
    document.getElementById('pegawai-section').style.display = 'none';
    document.getElementById('akun-error').style.display = 'none';

    const pwLabel = document.getElementById('akun-password-label');
    if (id) {
        pwLabel.innerHTML = 'Password <span style="color:#94a3b8;font-weight:400;text-transform:none;letter-spacing:0;">(kosongkan jika tidak diubah)</span>';
        document.getElementById('akun-password').placeholder = 'Kosongkan jika tidak diubah';
        const u = allUsers.find(x => x.id === id);
        if (u) {
            document.getElementById('akun-nama').value = u.full_name;
            document.getElementById('akun-username').value = u.username;
            document.getElementById('akun-role').value = u.role;
            document.getElementById('akun-status').value = u.status;
            onRoleChange();
            if (u.pegawai) {
                const p = u.pegawai;
                editPegawaiId = p.id;
                document.getElementById('akun-nip').value = p.nip ?? '';
                document.getElementById('akun-hp').value = p.nomor_hp ?? '';
                document.getElementById('akun-jabatan').value = p.jabatan ?? '';
                document.getElementById('akun-unit').value = p.unit_kerja_id ?? '';
                document.getElementById('akun-atasan').value = p.atasan_id ?? '';
                document.getElementById('akun-status-pegawai').value = p.status ?? 'aktif';
                document.getElementById('foto-placeholder').textContent = p.nama_lengkap.charAt(0).toUpperCase();
                if (p.foto) {
                    document.getElementById('foto-preview').src = '/' + p.foto;
                    document.getElementById('foto-preview').style.display = 'block';
                    document.getElementById('foto-placeholder').style.display = 'none';
                }
            }
        }
    } else {
        pwLabel.innerHTML = 'Password <span style="color:#dc2626;">*</span>';
        document.getElementById('akun-password').placeholder = 'Min. 6 karakter';
    }

    document.getElementById('modal-akun').style.display = 'flex';
}

function closeModal() { document.getElementById('modal-akun').style.display = 'none'; editId = null; editPegawaiId = null; }

async function submitAkun() {
    const nama     = document.getElementById('akun-nama').value.trim();
    const username = document.getElementById('akun-username').value.trim();
    const password = document.getElementById('akun-password').value;
    const role     = document.getElementById('akun-role').value;
    const status   = document.getElementById('akun-status').value;
    const errEl    = document.getElementById('akun-error');
    errEl.style.display = 'none';

    if (!nama || !username || !role) { errEl.textContent = 'Nama, username, dan role wajib diisi'; errEl.style.display = 'block'; return; }
    if (!editId && !password) { errEl.textContent = 'Password wajib diisi untuk akun baru'; errEl.style.display = 'block'; return; }

    const needPegawai = ['pegawai','atasan','pimpinan'].includes(role);
    const nip     = document.getElementById('akun-nip').value.trim();
    const jabatan = document.getElementById('akun-jabatan').value.trim();
    const unitId  = document.getElementById('akun-unit').value;

    if (needPegawai && (!nip || !jabatan || !unitId)) {
        errEl.textContent = 'NIP, jabatan, dan unit kerja wajib diisi untuk role ini'; errEl.style.display = 'block'; return;
    }

    try {
        // 1. Simpan/update data pegawai jika perlu
        let pegawaiId = editPegawaiId;
        if (needPegawai) {
            const fotoInput = document.getElementById('akun-foto');
            const formData = new FormData();
            formData.append('nip', nip);
            formData.append('nama_lengkap', nama);
            formData.append('jabatan', jabatan);
            formData.append('unit_kerja_id', unitId);
            formData.append('nomor_hp', document.getElementById('akun-hp').value.trim());
            formData.append('atasan_id', document.getElementById('akun-atasan').value);
            formData.append('status', document.getElementById('akun-status-pegawai').value);
            if (fotoInput.files[0]) formData.append('foto', fotoInput.files[0]);

            let pRes;
            if (editPegawaiId) {
                formData.append('_method', 'POST');
                pRes = await fetch(`/api/pegawai/${editPegawaiId}`, { method: 'POST', headers: H, body: formData });
            } else {
                pRes = await fetch('/api/pegawai', { method: 'POST', headers: H, body: formData });
            }
            const pJson = await pRes.json();
            if (!pRes.ok) {
                errEl.textContent = pJson.message ?? Object.values(pJson.errors ?? {}).flat().join(', ') ?? 'Gagal simpan data pegawai';
                errEl.style.display = 'block'; return;
            }
            pegawaiId = pJson.data?.id ?? pegawaiId;
        }

        // 2. Simpan/update akun user
        const body = { full_name: nama, username, role, status };
        if (password) body.password = password;
        if (pegawaiId) body.pegawai_id = pegawaiId;

        const url = editId ? `/api/users/${editId}` : '/api/users';
        const method = editId ? 'PUT' : 'POST';
        const res = await fetch(url, { method, headers: HJ, body: JSON.stringify(body) });
        const json = await res.json();

        if (res.ok) {
            closeModal();
            showToast(editId ? 'Data berhasil diperbarui' : 'Akun berhasil dibuat', 'success');
            loadUsers();
        } else {
            errEl.textContent = json.message ?? Object.values(json.errors ?? {}).flat().join(', ') ?? 'Gagal menyimpan';
            errEl.style.display = 'block';
        }
    } catch (_) { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'block'; }
}

function openReset(id) {
    resetId = id;
    document.getElementById('reset-password').value = '';
    document.getElementById('reset-error').style.display = 'none';
    document.getElementById('modal-reset').style.display = 'flex';
}
function closeReset() { document.getElementById('modal-reset').style.display = 'none'; resetId = null; }

async function submitReset() {
    const pw = document.getElementById('reset-password').value;
    const errEl = document.getElementById('reset-error');
    if (!pw || pw.length < 6) { errEl.textContent = 'Password minimal 6 karakter'; errEl.style.display = 'block'; return; }
    try {
        const res = await fetch('/api/users/reset-password', { method:'POST', headers: HJ, body: JSON.stringify({ id: resetId, new_password: pw }) });
        const json = await res.json();
        if (res.ok) { closeReset(); showToast('Password berhasil direset', 'success'); }
        else { errEl.textContent = json.message ?? 'Gagal reset password'; errEl.style.display = 'block'; }
    } catch (_) { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'block'; }
}

function openHapus(id, nama, isAktif) {
    hapusId = id;
    document.getElementById('hapus-info').textContent = isAktif
        ? `Akun "${nama}" masih aktif. Akun akan dinonaktifkan (tidak dihapus permanen).`
        : `Yakin hapus akun "${nama}"? Jika tidak ada riwayat pindaian, data akan dihapus permanen.`;
    document.getElementById('modal-hapus').style.display = 'flex';
}
function closeHapus() { document.getElementById('modal-hapus').style.display = 'none'; hapusId = null; }

async function confirmHapus() {
    try {
        const res = await fetch(`/api/users/${hapusId}`, { method:'DELETE', headers: HJ });
        const json = await res.json();
        if (res.ok) { closeHapus(); showToast(json.message ?? 'Akun berhasil dihapus', 'success'); loadUsers(); }
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

loadDropdowns();
loadUsers();
</script>
@endpush
