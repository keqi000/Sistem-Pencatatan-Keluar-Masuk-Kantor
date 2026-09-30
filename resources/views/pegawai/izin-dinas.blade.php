@extends('layouts.app')

@section('title', 'Izin Dinas')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Form Pengajuan --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <h2 class="text-primary font-bold text-base mb-5">Ajukan Izin Dinas</h2>

        <form id="form-izin" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-primary">Tanggal</label>
                <input type="date" name="tanggal" id="input-tanggal" required
                    class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-primary">Tujuan</label>
                <input type="text" name="tujuan" placeholder="Nama tempat / instansi tujuan" required
                    class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition
                           placeholder:text-muted/50">
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-primary">Perkiraan Jam Pergi</label>
                <input type="time" name="perkiraan_jam_pergi" required
                    class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-primary">Perkiraan Jam Kembali</label>
                <input type="time" name="perkiraan_jam_kembali" required
                    class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
            </div>

            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label class="text-sm font-semibold text-primary">Keperluan</label>
                <textarea name="keperluan" rows="3" placeholder="Jelaskan keperluan dinas..." required
                    class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition
                           placeholder:text-muted/50 resize-none"></textarea>
            </div>

            <div class="sm:col-span-2 flex items-center gap-3">
                <button type="submit"
                    class="px-6 py-2.5 bg-brand text-white text-sm font-semibold rounded-lg
                           hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                    Ajukan Izin
                </button>
                <span id="form-msg" class="text-sm hidden"></span>
            </div>
        </form>
    </div>

    {{-- Daftar Izin --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <h2 class="text-primary font-bold text-base mb-4">Riwayat Pengajuan</h2>

        {{-- Tab --}}
        <div class="flex gap-1 mb-5 bg-soft rounded-lg p-1 w-fit">
            @foreach(['semua' => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $val => $label)
            <button onclick="filterIzin('{{ $val }}')" data-tab="{{ $val }}"
                class="tab-btn px-4 py-1.5 rounded-md text-sm font-medium transition cursor-pointer
                       {{ $val === 'semua' ? 'bg-canvas text-primary shadow-sm' : 'text-muted hover:text-primary' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-soft text-muted text-left">
                        <th class="pb-2 font-semibold">Tanggal</th>
                        <th class="pb-2 font-semibold">Tujuan</th>
                        <th class="pb-2 font-semibold">Jam</th>
                        <th class="pb-2 font-semibold">Status</th>
                        <th class="pb-2 font-semibold">Catatan Atasan</th>
                    </tr>
                </thead>
                <tbody id="izin-tbody" class="divide-y divide-soft">
                    <tr><td colspan="5" class="py-6 text-center text-muted">Memuat data...</td></tr>
                </tbody>
            </table>
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

let allIzin = [];

// Set min date = hari ini
document.getElementById('input-tanggal').min = new Date().toISOString().split('T')[0];

async function loadIzin() {
    try {
        const res = await fetch('/api/izin-dinas', { headers });
        const { data } = await res.json();
        allIzin = data ?? [];
        renderIzin(allIzin);
    } catch (_) {
        document.getElementById('izin-tbody').innerHTML =
            `<tr><td colspan="5" class="py-6 text-center text-muted">Gagal memuat data</td></tr>`;
    }
}

function renderIzin(data) {
    const tbody = document.getElementById('izin-tbody');
    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-muted">Belum ada pengajuan</td></tr>`;
        return;
    }

    const badgeClass = {
        menunggu:  'bg-orange-100 text-warning border-orange-200',
        disetujui: 'bg-green-100 text-success border-green-200',
        ditolak:   'bg-red-100 text-danger border-red-200',
    };

    tbody.innerHTML = data.map(d => `
        <tr class="hover:bg-soft/50 transition">
            <td class="py-2.5 pr-4 text-text">${d.tanggal}</td>
            <td class="py-2.5 pr-4 text-text">${d.tujuan}</td>
            <td class="py-2.5 pr-4 text-muted text-xs">${d.perkiraan_jam_pergi} — ${d.perkiraan_jam_kembali}</td>
            <td class="py-2.5 pr-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ${badgeClass[d.status] ?? 'bg-soft text-brand border-sky/40'}">
                    ${d.status.charAt(0).toUpperCase() + d.status.slice(1)}
                </span>
            </td>
            <td class="py-2.5 text-muted text-xs">${d.catatan_atasan ?? '-'}</td>
        </tr>
    `).join('');
}

function filterIzin(status) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.dataset.tab === status;
        btn.className = btn.className.replace(
            isActive ? 'text-muted hover:text-primary' : 'bg-canvas text-primary shadow-sm',
            isActive ? 'bg-canvas text-primary shadow-sm' : 'text-muted hover:text-primary'
        );
    });
    renderIzin(status === 'semua' ? allIzin : allIzin.filter(d => d.status === status));
}

document.getElementById('form-izin').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('form-msg');
    const form = e.target;
    const payload = Object.fromEntries(new FormData(form));

    try {
        const res = await fetch('/api/izin-dinas', {
            method: 'POST', headers, body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (res.ok) {
            showToast('Izin berhasil diajukan!', 'success');
            form.reset();
            document.getElementById('input-tanggal').min = new Date().toISOString().split('T')[0];
            loadIzin();
        } else {
            showToast(json.message ?? 'Gagal mengajukan izin', 'danger');
        }
    } catch (_) {
        showToast('Gagal terhubung ke server', 'danger');
    }
});

function showToast(msg, type) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.remove('hidden', 'bg-success', 'bg-danger');
    toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

loadIzin();
</script>
@endpush
