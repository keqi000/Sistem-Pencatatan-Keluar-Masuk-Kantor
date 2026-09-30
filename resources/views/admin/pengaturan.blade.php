@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="flex flex-col gap-6 max-w-3xl">

    {{-- Jam Kerja --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <h2 class="text-primary font-bold text-base mb-5">Jam Kerja & QR</h2>
        <form id="form-jamkerja" class="flex flex-col gap-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-primary">Jam Mulai</label>
                    <input type="time" name="jam_mulai" id="jam_mulai"
                        class="px-3 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-primary">Jam Selesai</label>
                    <input type="time" name="jam_selesai" id="jam_selesai"
                        class="px-3 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-primary">Istirahat Mulai</label>
                    <input type="time" name="istirahat_mulai" id="istirahat_mulai"
                        class="px-3 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-primary">Istirahat Selesai</label>
                    <input type="time" name="istirahat_selesai" id="istirahat_selesai"
                        class="px-3 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
            </div>

            <div class="flex flex-wrap gap-6 items-center">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="hitung_istirahat" id="hitung_istirahat"
                        class="w-4 h-4 rounded accent-brand">
                    <span class="text-sm text-text">Hitung jam istirahat dalam durasi</span>
                </label>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-semibold text-primary whitespace-nowrap">Interval QR</label>
                    <input type="number" name="interval_qr" id="interval_qr" min="10" max="300"
                        class="w-20 px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                    <span class="text-muted text-sm">detik</span>
                </div>
            </div>

            <div id="jamkerja-msg" class="hidden text-sm"></div>

            <div>
                <button type="submit"
                    class="px-6 py-2.5 bg-brand text-white text-sm font-semibold rounded-lg
                           hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

    {{-- Unit Kerja --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-primary font-bold text-base">Unit Kerja</h2>
            <button onclick="openModalUnit()"
                class="px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg
                       hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                + Tambah Unit
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-soft text-muted text-left">
                        <th class="pb-2 font-semibold">Nama Unit</th>
                        <th class="pb-2 font-semibold">Kode</th>
                        <th class="pb-2 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody id="unit-tbody" class="divide-y divide-soft">
                    <tr><td colspan="3" class="py-6 text-center text-muted">Memuat...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Modal Unit Kerja --}}
<div id="modal-unit" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(10,46,92,0.4)">
    <div class="bg-canvas rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <h3 id="modal-unit-title" class="text-primary font-bold text-base mb-5">Tambah Unit Kerja</h3>
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-primary">Nama Unit <span class="text-danger">*</span></label>
                <input type="text" id="unit-nama" placeholder="Nama unit kerja"
                    class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition placeholder:text-muted/50">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-primary">Kode Unit <span class="text-danger">*</span></label>
                <input type="text" id="unit-kode" placeholder="Contoh: TU, PD, dll"
                    class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition placeholder:text-muted/50">
            </div>
            <div id="unit-error" class="hidden text-danger text-sm"></div>
            <div class="flex gap-3">
                <button onclick="submitUnit()"
                    class="flex-1 py-2.5 rounded-lg bg-brand text-white text-sm font-semibold
                           hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                    Simpan
                </button>
                <button onclick="closeModalUnit()"
                    class="flex-1 py-2.5 rounded-lg border border-sky/40 text-muted text-sm hover:bg-soft transition cursor-pointer">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50
    px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium min-w-[240px] text-center">
</div>
@endsection

@push('scripts')
<script>
const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

let editUnitId = null;

// ── Jam Kerja ──────────────────────────────────────────
async function loadJamKerja() {
    try {
        const res = await fetch('/api/pengaturan/jam-kerja', { headers });
        const { data } = await res.json();
        if (!data) return;
        document.getElementById('jam_mulai').value        = data.jam_mulai ?? '';
        document.getElementById('jam_selesai').value      = data.jam_selesai ?? '';
        document.getElementById('istirahat_mulai').value  = data.istirahat_mulai ?? '';
        document.getElementById('istirahat_selesai').value= data.istirahat_selesai ?? '';
        document.getElementById('interval_qr').value      = data.interval_qr ?? 30;
        document.getElementById('hitung_istirahat').checked = !!data.hitung_istirahat;
    } catch (_) {}
}

document.getElementById('form-jamkerja').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msgEl = document.getElementById('jamkerja-msg');
    const payload = {
        jam_mulai:         document.getElementById('jam_mulai').value,
        jam_selesai:       document.getElementById('jam_selesai').value,
        istirahat_mulai:   document.getElementById('istirahat_mulai').value,
        istirahat_selesai: document.getElementById('istirahat_selesai').value,
        interval_qr:       parseInt(document.getElementById('interval_qr').value),
        hitung_istirahat:  document.getElementById('hitung_istirahat').checked,
    };
    try {
        const res = await fetch('/api/pengaturan/jam-kerja', {
            method: 'PUT', headers, body: JSON.stringify(payload),
        });
        const json = await res.json();
        msgEl.classList.remove('hidden', 'text-danger', 'text-success');
        if (res.ok) {
            msgEl.textContent = 'Pengaturan berhasil disimpan';
            msgEl.classList.add('text-success');
        } else {
            msgEl.textContent = json.message ?? 'Gagal menyimpan';
            msgEl.classList.add('text-danger');
        }
    } catch (_) {
        msgEl.textContent = 'Gagal terhubung ke server';
        msgEl.classList.remove('hidden');
        msgEl.classList.add('text-danger');
    }
});

// ── Unit Kerja ─────────────────────────────────────────
async function loadUnits() {
    try {
        const res = await fetch('/api/pengaturan/unit-kerja', { headers });
        const { data } = await res.json();
        const tbody = document.getElementById('unit-tbody');

        if (!data?.length) {
            tbody.innerHTML = `<tr><td colspan="3" class="py-6 text-center text-muted">Belum ada unit kerja</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(u => `
            <tr class="hover:bg-soft/50 transition">
                <td class="py-2.5 pr-4 text-text font-medium">${u.nama_unit}</td>
                <td class="py-2.5 pr-4 text-muted">${u.kode_unit}</td>
                <td class="py-2.5">
                    <div class="flex gap-3">
                        <button onclick="openModalUnit(${u.id}, '${u.nama_unit}', '${u.kode_unit}')"
                            class="text-xs text-brand hover:underline font-medium cursor-pointer">Edit</button>
                        <button onclick="hapusUnit(${u.id})"
                            class="text-xs text-danger hover:underline font-medium cursor-pointer">Hapus</button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (_) {}
}

function openModalUnit(id = null, nama = '', kode = '') {
    editUnitId = id;
    document.getElementById('modal-unit-title').textContent = id ? 'Edit Unit Kerja' : 'Tambah Unit Kerja';
    document.getElementById('unit-nama').value = nama;
    document.getElementById('unit-kode').value = kode;
    document.getElementById('unit-error').classList.add('hidden');
    document.getElementById('modal-unit').classList.remove('hidden');
}

function closeModalUnit() {
    document.getElementById('modal-unit').classList.add('hidden');
    editUnitId = null;
}

async function submitUnit() {
    const nama = document.getElementById('unit-nama').value.trim();
    const kode = document.getElementById('unit-kode').value.trim();
    const errEl = document.getElementById('unit-error');

    if (!nama || !kode) {
        errEl.textContent = 'Nama dan kode unit wajib diisi';
        errEl.classList.remove('hidden');
        return;
    }

    try {
        const url    = editUnitId ? `/api/pengaturan/unit-kerja/${editUnitId}` : '/api/pengaturan/unit-kerja';
        const method = editUnitId ? 'PUT' : 'POST';
        const res = await fetch(url, { method, headers, body: JSON.stringify({ nama_unit: nama, kode_unit: kode }) });
        const json = await res.json();

        if (res.ok) {
            closeModalUnit();
            showToast(editUnitId ? 'Unit berhasil diperbarui' : 'Unit berhasil ditambahkan', 'success');
            loadUnits();
        } else {
            errEl.textContent = json.message ?? 'Gagal menyimpan';
            errEl.classList.remove('hidden');
        }
    } catch (_) {
        errEl.textContent = 'Gagal terhubung ke server';
        errEl.classList.remove('hidden');
    }
}

async function hapusUnit(id) {
    if (!confirm('Hapus unit kerja ini?')) return;
    try {
        const res = await fetch(`/api/pengaturan/unit-kerja/${id}`, { method: 'DELETE', headers });
        const json = await res.json();
        if (res.ok) { showToast('Unit berhasil dihapus', 'success'); loadUnits(); }
        else showToast(json.message ?? 'Gagal menghapus', 'danger');
    } catch (_) { showToast('Gagal terhubung ke server', 'danger'); }
}

function showToast(msg, type) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.remove('hidden', 'bg-success', 'bg-danger');
    toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

loadJamKerja();
loadUnits();
</script>
@endpush
