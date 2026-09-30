@extends('layouts.app')

@section('title', 'Izin Dinas Bawahan')

@push('styles')
<style>
    .izin-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e8f4fd;
        box-shadow: 0 2px 8px rgba(10,46,92,0.05);
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        transition: all 0.2s;
    }
    .izin-card:hover {
        box-shadow: 0 8px 28px rgba(10,46,92,0.12);
        transform: translateY(-2px);
        border-color: #bfdfff;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }
    .tab-item {
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
        position: relative;
    }
    .tab-item.active {
        background: linear-gradient(135deg, #0073e6, #0056b3);
        color: white;
        box-shadow: 0 3px 10px rgba(0,115,230,0.3);
    }
    .tab-item.inactive {
        background: transparent;
        color: #64748b;
    }
    .tab-item.inactive:hover { background: #f0f7ff; color: #0a2e5c; }

    .btn-setujui {
        flex: 1; padding: 9px; border-radius: 9px;
        background: linear-gradient(135deg, #16a34a, #15803d);
        color: white; font-size: 12px; font-weight: 700;
        border: none; cursor: pointer;
        box-shadow: 0 3px 10px rgba(22,163,74,0.25);
        transition: all 0.15s;
    }
    .btn-setujui:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(22,163,74,0.35); }

    .btn-tolak {
        flex: 1; padding: 9px; border-radius: 9px;
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white; font-size: 12px; font-weight: 700;
        border: none; cursor: pointer;
        box-shadow: 0 3px 10px rgba(220,38,38,0.25);
        transition: all 0.15s;
    }
    .btn-tolak:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(220,38,38,0.35); }

    .modal-overlay {
        display: none; position: fixed; inset: 0; z-index: 50;
        align-items: center; justify-content: center; padding: 16px;
        background: rgba(10,46,92,0.3);
        backdrop-filter: blur(6px);
    }
    .modal-box {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 32px 80px rgba(10,46,92,0.2);
        width: 100%; max-width: 400px;
        padding: 28px;
        border: 1px solid #dbeeff;
        animation: modalIn 0.2s ease;
    }
    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(8px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .modal-input {
        width: 100%; padding: 10px 14px;
        border-radius: 10px; border: 1.5px solid #dbeeff;
        background: #f8fbff; color: #0a2e5c;
        font-size: 13px; resize: none; outline: none;
        transition: all 0.15s; font-family: inherit;
    }
    .modal-input:focus {
        border-color: #5cc2f2;
        box-shadow: 0 0 0 3px rgba(92,194,242,0.15);
        background: #ffffff;
    }
    .toast {
        display: none; position: fixed; bottom: 24px;
        left: 50%; transform: translateX(-50%); z-index: 60;
        padding: 12px 24px; border-radius: 12px; color: white;
        font-size: 13px; font-weight: 600; min-width: 220px;
        text-align: center; box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        animation: toastIn 0.2s ease;
    }
    @keyframes toastIn {
        from { opacity: 0; transform: translateX(-50%) translateY(8px); }
        to   { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
</style>
@endpush

@section('content')
<div style="display:flex;flex-direction:column;gap:24px;">

    {{-- Tab --}}
    <div style="display:flex;gap:4px;background:#f8fbff;border-radius:12px;border:1px solid #dbeeff;padding:4px;width:fit-content;box-shadow:0 1px 4px rgba(10,46,92,0.05);">
        @foreach(['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $val => $label)
        <button class="tab-item {{ $val === 'menunggu' ? 'active' : 'inactive' }}"
                data-tab="{{ $val }}" onclick="filterTab('{{ $val }}')">
            {{ $label }}
            <span id="badge-{{ $val }}"
                style="display:none;position:absolute;top:-5px;right:-5px;width:17px;height:17px;border-radius:50%;background:#ff9f1c;color:white;font-size:9px;font-weight:800;align-items:center;justify-content:center;border:2px solid white;">
            </span>
        </button>
        @endforeach
    </div>

    {{-- Grid Kartu --}}
    <div id="izin-list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
        <div style="grid-column:1/-1;padding:60px;text-align:center;">
            <div style="width:40px;height:40px;border-radius:50%;border:3px solid #dbeeff;border-top-color:#0073e6;animation:spin 0.8s linear infinite;margin:0 auto 12px;"></div>
            <p style="font-size:13px;color:#94a3b8;">Memuat data...</p>
        </div>
    </div>

</div>

{{-- Modal --}}
<div id="modal" class="modal-overlay">
    <div class="modal-box">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <div id="modal-icon" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;"></div>
            <div>
                <h3 id="modal-title" style="font-size:15px;font-weight:800;color:#0a2e5c;"></h3>
                <p id="modal-sub" style="font-size:12px;color:#64748b;margin-top:1px;"></p>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:20px;">
            <label id="modal-label" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#0a2e5c;"></label>
            <textarea id="modal-catatan" rows="3" class="modal-input"></textarea>
        </div>

        <div style="display:flex;gap:10px;">
            <button id="modal-confirm" onclick="submitPutuskan()"
                style="flex:1;padding:10px;border-radius:10px;color:white;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:all 0.15s;">
                Konfirmasi
            </button>
            <button onclick="closeModal()"
                style="flex:1;padding:10px;border-radius:10px;background:#f8fbff;border:1.5px solid #dbeeff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;"
                onmouseover="this.style.background='#dbeeff';" onmouseout="this.style.background='#f8fbff';">
                Batal
            </button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>
@endsection

@push('scripts')
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
<script>
const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

let allData = [], activeTab = 'menunggu', modalIzinId = null, modalAksi = null;

async function loadIzin() {
    try {
        const res = await fetch('/api/izin-dinas/bawahan');
        const json = await res.json();
        console.log('Response status:', res.status);
        console.log('Response body:', json);
        if (!res.ok) {
            console.error('401 detail:', json);
            document.getElementById('izin-list').innerHTML =
                `<div style="grid-column:1/-1;padding:40px;text-align:center;color:#dc2626;font-size:13px;">Error ${res.status}: ${json.message ?? 'Unauthorized'}</div>`;
            return;
        }
        allData = json.data ?? [];

        ['menunggu','disetujui','ditolak'].forEach(s => {
            const c = allData.filter(d => d.status === s).length;
            const b = document.getElementById('badge-' + s);
            b.textContent = c;
            b.style.display = c > 0 ? 'flex' : 'none';
        });

        renderKartu(allData.filter(d => d.status === activeTab));
    } catch (_) {
        document.getElementById('izin-list').innerHTML =
            `<div style="grid-column:1/-1;padding:40px;text-align:center;color:#94a3b8;font-size:13px;">Gagal memuat data</div>`;
    }
}

function renderKartu(data) {
    const list = document.getElementById('izin-list');
    if (!data.length) {
        list.innerHTML = `<div style="grid-column:1/-1;padding:60px;text-align:center;">
            <p style="font-size:32px;margin-bottom:8px;">📋</p>
            <p style="font-size:13px;color:#94a3b8;">Tidak ada pengajuan ${activeTab}</p>
        </div>`;
        return;
    }

    const badge = {
        menunggu:  { bg:'#fff7ed', color:'#ea580c', border:'#fed7aa', label:'Menunggu' },
        disetujui: { bg:'#f0fdf4', color:'#16a34a', border:'#bbf7d0', label:'Disetujui' },
        ditolak:   { bg:'#fef2f2', color:'#dc2626', border:'#fecaca', label:'Ditolak' },
    };

    list.innerHTML = data.map(d => {
        const b = badge[d.status] ?? { bg:'#dbeeff', color:'#0073e6', border:'#bfdfff', label: d.status };
        return `
        <div class="izin-card">
            {{-- Header kartu --}}
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#dbeeff,#bfdfff);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;font-weight:800;color:#0073e6;">
                        ${(d.pegawai?.nama_lengkap ?? '?').charAt(0)}
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:700;color:#0a2e5c;">${d.pegawai?.nama_lengkap ?? '-'}</p>
                        <p style="font-size:11px;color:#64748b;margin-top:1px;">${d.pegawai?.jabatan ?? ''} · ${d.pegawai?.unit_kerja?.nama_unit ?? ''}</p>
                    </div>
                </div>
                <span style="flex-shrink:0;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;background:${b.bg};color:${b.color};border:1px solid ${b.border};">
                    ${b.label}
                </span>
            </div>

            {{-- Info detail --}}
            <div style="background:#f8fbff;border-radius:10px;padding:12px;border:1px solid #e8f4fd;display:flex;flex-direction:column;gap:7px;">
                <div class="info-row">
                    <span style="color:#64748b;">Tanggal</span>
                    <span style="font-weight:600;color:#0a2e5c;">${d.tanggal}</span>
                </div>
                <div class="info-row">
                    <span style="color:#64748b;">Tujuan</span>
                    <span style="font-weight:600;color:#0a2e5c;max-width:60%;text-align:right;">${d.tujuan}</span>
                </div>
                <div class="info-row">
                    <span style="color:#64748b;">Jam</span>
                    <span style="font-weight:600;color:#0073e6;">${d.perkiraan_jam_pergi} — ${d.perkiraan_jam_kembali}</span>
                </div>
                <div style="padding-top:7px;border-top:1px solid #dbeeff;">
                    <p style="font-size:11px;color:#64748b;line-height:1.5;">${d.keperluan}</p>
                </div>
            </div>

            ${d.catatan_atasan ? `
            <div style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border-radius:8px;background:#f0f7ff;border-left:3px solid #5cc2f2;">
                <span style="font-size:14px;">💬</span>
                <p style="font-size:11px;color:#0a2e5c;line-height:1.5;"><span style="font-weight:700;">Catatan:</span> ${d.catatan_atasan}</p>
            </div>` : ''}

            ${d.status === 'menunggu' ? `
            <div style="display:flex;gap:8px;">
                <button class="btn-setujui" onclick="openModal(${d.id}, 'setujui')">✓ Setujui</button>
                <button class="btn-tolak"   onclick="openModal(${d.id}, 'tolak')">✗ Tolak</button>
            </div>` : ''}
        </div>`;
    }).join('');
}

function filterTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-item').forEach(btn => {
        const isActive = btn.dataset.tab === tab;
        btn.className = 'tab-item ' + (isActive ? 'active' : 'inactive');
    });
    renderKartu(allData.filter(d => d.status === tab));
}

function openModal(id, aksi) {
    modalIzinId = id; modalAksi = aksi;
    const isSetujui = aksi === 'setujui';
    document.getElementById('modal-icon').textContent = isSetujui ? '✓' : '✗';
    document.getElementById('modal-icon').style.background = isSetujui ? '#f0fdf4' : '#fef2f2';
    document.getElementById('modal-icon').style.color = isSetujui ? '#16a34a' : '#dc2626';
    document.getElementById('modal-title').textContent = isSetujui ? 'Setujui Izin Dinas' : 'Tolak Izin Dinas';
    document.getElementById('modal-sub').textContent = isSetujui ? 'Tambahkan catatan opsional.' : 'Alasan penolakan wajib diisi.';
    document.getElementById('modal-label').textContent = isSetujui ? 'Catatan (opsional)' : 'Alasan Penolakan *';
    document.getElementById('modal-catatan').placeholder = isSetujui ? 'Catatan untuk pegawai...' : 'Alasan penolakan...';
    document.getElementById('modal-catatan').value = '';
    const btn = document.getElementById('modal-confirm');
    btn.style.background = isSetujui ? 'linear-gradient(135deg,#16a34a,#15803d)' : 'linear-gradient(135deg,#dc2626,#b91c1c)';
    btn.style.boxShadow = isSetujui ? '0 3px 10px rgba(22,163,74,0.3)' : '0 3px 10px rgba(220,38,38,0.3)';
    document.getElementById('modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

async function submitPutuskan() {
    const catatan = document.getElementById('modal-catatan').value.trim();
    if (modalAksi === 'tolak' && !catatan) { showToast('Alasan penolakan wajib diisi', 'danger'); return; }
    try {
        const res = await fetch(`/api/izin-dinas/${modalIzinId}/putuskan`, {
            method: 'POST', headers,
            body: JSON.stringify({ aksi: modalAksi, catatan_atasan: catatan }),
        });
        const json = await res.json();
        if (res.ok) {
            showToast(modalAksi === 'setujui' ? '✓ Izin berhasil disetujui' : '✗ Izin ditolak',
                      modalAksi === 'setujui' ? 'success' : 'danger');
            closeModal(); loadIzin();
        } else {
            showToast(json.message ?? 'Gagal memproses', 'danger');
        }
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); }
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'success'
        ? 'linear-gradient(135deg,#16a34a,#15803d)'
        : 'linear-gradient(135deg,#dc2626,#b91c1c)';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

loadIzin();
</script>
@endpush
