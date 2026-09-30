@extends('layouts.app')

@section('title', 'Edit Catatan')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">

        <div class="flex items-center gap-3 mb-6">
            <a href="/admin/catatan" class="text-muted hover:text-brand transition text-sm">← Kembali</a>
            <span class="text-muted">/</span>
            <h2 class="text-primary font-bold text-base">Edit Catatan</h2>
        </div>

        {{-- Info Pegawai --}}
        <div id="info-pegawai" class="bg-soft rounded-xl p-4 mb-5 text-sm text-muted">
            Memuat data...
        </div>

        <form id="form-catatan" class="flex flex-col gap-4">

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-primary">Jam Keluar</label>
                    <input type="datetime-local" name="jam_keluar" id="input-jam-keluar"
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-primary">Jam Kembali</label>
                    <input type="datetime-local" name="jam_kembali" id="input-jam-kembali"
                        class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-primary">Keperluan</label>
                <select name="keperluan_jenis" id="sel-keperluan"
                    class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                    <option value="">— Tidak diubah —</option>
                    <option value="dinas">Dinas</option>
                    <option value="keperluan_lain">Keperluan Lain</option>
                </select>
            </div>

            <div id="form-error" class="hidden px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-danger text-sm"></div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-brand text-white text-sm font-semibold rounded-lg
                           hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                    Simpan Perubahan
                </button>
                <a href="/admin/catatan"
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
const catatanId = @json($id);
const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

async function loadData() {
    try {
        const res = await fetch(`/api/riwayat/all?per_page=1&pasangan_id=${catatanId}`, { headers });
        const json = await res.json();
        const r = (json.data ?? [])[0];
        if (!r) {
            document.getElementById('info-pegawai').textContent = 'Data tidak ditemukan';
            return;
        }

        document.getElementById('info-pegawai').innerHTML = `
            <span style="font-weight:700;color:#0a2e5c;">${r.nama_lengkap ?? '-'}</span>
            <span style="margin:0 6px;color:#cbd5e1;">·</span>${r.nama_unit ?? ''}
        `;

        if (r.jam_keluar)  document.getElementById('input-jam-keluar').value  = r.jam_keluar.replace(' ', 'T').substring(0, 16);
        if (r.jam_kembali) document.getElementById('input-jam-kembali').value = r.jam_kembali.replace(' ', 'T').substring(0, 16);
        if (r.keperluan_jenis) document.getElementById('sel-keperluan').value = r.keperluan_jenis;
    } catch (_) {
        document.getElementById('info-pegawai').textContent = 'Gagal memuat data';
    }
}

document.getElementById('form-catatan').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errEl = document.getElementById('form-error');
    errEl.classList.add('hidden');

    const payload = {};
    const jamKeluar  = document.getElementById('input-jam-keluar').value;
    const jamKembali = document.getElementById('input-jam-kembali').value;
    const keperluan  = document.getElementById('sel-keperluan').value;

    if (jamKeluar)  payload.jam_keluar  = jamKeluar.replace('T', ' ');
    if (jamKembali) payload.jam_kembali = jamKembali.replace('T', ' ');
    if (keperluan)  payload.keperluan_jenis = keperluan;

    try {
        const res = await fetch(`/api/riwayat/${catatanId}`, {
            method: 'PUT', headers, body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (res.ok) {
            window.location.href = '/admin/catatan';
        } else {
            errEl.textContent = json.message ?? 'Gagal menyimpan perubahan';
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
