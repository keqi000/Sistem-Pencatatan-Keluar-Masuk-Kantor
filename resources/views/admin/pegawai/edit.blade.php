@extends('layouts.app')

@section('title', 'Edit Pegawai')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">

        <div class="flex items-center gap-3 mb-6">
            <a href="/admin/pegawai" class="text-muted hover:text-brand transition text-sm">← Kembali</a>
            <span class="text-muted">/</span>
            <h2 class="text-primary font-bold text-base">Edit Pegawai</h2>
        </div>

        <form id="form-pegawai" class="flex flex-col gap-5">

            {{-- Foto Preview --}}
            <div class="flex flex-col items-center gap-3">
                <div id="foto-preview"
                    class="w-24 h-24 rounded-full bg-soft border-2 border-sky/30 flex items-center justify-center overflow-hidden">
                    <span class="text-muted text-3xl">👤</span>
                </div>
                <label class="cursor-pointer text-xs text-brand hover:underline font-medium">
                    Ganti Foto
                    <input type="file" name="foto" id="input-foto" accept="image/*" class="hidden" onchange="previewFoto(this)">
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-primary">NIP <span class="text-danger">*</span></label>
                    <input type="text" name="nip" id="input-nip" required
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-primary">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" id="input-nama" required
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-primary">Jabatan <span class="text-danger">*</span></label>
                    <input type="text" name="jabatan" id="input-jabatan" required
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-primary">Unit Kerja <span class="text-danger">*</span></label>
                    <select name="unit_kerja_id" id="sel-unit" required
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                        <option value="">Pilih unit kerja</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-primary">Atasan Langsung</label>
                    <select name="atasan_id" id="sel-atasan"
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                        <option value="">Tidak ada</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-primary">Nomor HP</label>
                    <input type="text" name="nomor_hp" id="input-hp"
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label class="text-sm font-semibold text-primary">Status</label>
                    <select name="status" id="sel-status"
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>
            </div>

            <div id="form-error" class="hidden px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-danger text-sm"></div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-brand text-white text-sm font-semibold rounded-lg
                           hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                    Simpan Perubahan
                </button>
                <a href="/admin/pegawai"
                    class="px-5 py-2.5 border border-sky/40 text-muted text-sm rounded-lg hover:bg-soft transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const pegawaiId = @json($id);
const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

function previewFoto(input) {
    if (!input.files?.[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('foto-preview').innerHTML =
            `<img src="${e.target.result}" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(input.files[0]);
}

async function loadData() {
    try {
        const [unitRes, pegawaiListRes, pegawaiRes] = await Promise.all([
            fetch('/api/pengaturan/unit-kerja', { headers }),
            fetch('/api/pegawai?per_page=999', { headers }),
            fetch(`/api/pegawai/${pegawaiId}`, { headers }),
        ]);
        const { data: units }   = await unitRes.json();
        const { data: list }    = await pegawaiListRes.json();
        const { data: p }       = await pegawaiRes.json();

        const selUnit = document.getElementById('sel-unit');
        (units ?? []).forEach(u => {
            selUnit.innerHTML += `<option value="${u.id}" ${u.id == p.unit_kerja_id ? 'selected' : ''}>${u.nama_unit}</option>`;
        });

        const selAtasan = document.getElementById('sel-atasan');
        (list ?? []).filter(x => x.id != pegawaiId).forEach(x => {
            selAtasan.innerHTML += `<option value="${x.id}" ${x.id == p.atasan_id ? 'selected' : ''}>${x.nama_lengkap}</option>`;
        });

        document.getElementById('input-nip').value    = p.nip ?? '';
        document.getElementById('input-nama').value   = p.nama_lengkap ?? '';
        document.getElementById('input-jabatan').value = p.jabatan ?? '';
        document.getElementById('input-hp').value     = p.nomor_hp ?? '';
        document.getElementById('sel-status').value   = p.status ?? 'aktif';

        if (p.foto) {
            document.getElementById('foto-preview').innerHTML =
                `<img src="/storage/${p.foto}" class="w-full h-full object-cover">`;
        }
    } catch (_) {}
}

document.getElementById('form-pegawai').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errEl = document.getElementById('form-error');
    errEl.classList.add('hidden');

    const formData = new FormData(e.target);
    formData.append('_method', 'PUT');
    const fotoFile = document.getElementById('input-foto').files[0];

    const payload = fotoFile ? formData : JSON.stringify(Object.fromEntries(formData));
    const fetchHeaders = fotoFile
        ? { 'Accept': 'application/json', 'X-CSRF-TOKEN': headers['X-CSRF-TOKEN'] }
        : { ...headers, 'Content-Type': 'application/json' };

    try {
        const res = await fetch(`/api/pegawai/${pegawaiId}`, {
            method: fotoFile ? 'POST' : 'PUT',
            headers: fetchHeaders,
            body: payload,
        });
        const json = await res.json();
        if (res.ok) {
            window.location.href = '/admin/pegawai';
        } else {
            const msg = json.message ?? Object.values(json.errors ?? {}).flat().join(', ');
            errEl.textContent = msg;
            errEl.classList.remove('hidden');
        }
    } catch (_) {
        errEl.textContent = 'Gagal terhubung ke server';
        errEl.classList.remove('hidden');
    }
});

loadData();
</script>
@endpush
