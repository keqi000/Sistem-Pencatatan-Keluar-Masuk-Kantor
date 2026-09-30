@extends('layouts.app')

@section('title', 'Manajemen Catatan')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Filter --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-primary">Tanggal</label>
                <input type="date" id="filter-tanggal"
                    class="px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-primary">Pegawai</label>
                <input type="text" id="filter-pegawai" placeholder="Nama pegawai..."
                    class="px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm w-44
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition placeholder:text-muted/50">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-primary">Status</label>
                <select id="filter-status"
                    class="px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                    <option value="">Semua</option>
                    <option value="terbuka">Terbuka</option>
                    <option value="kembali">Kembali</option>
                    <option value="belum_kembali">Belum Kembali</option>
                </select>
            </div>
            <button onclick="loadCatatan(1)"
                class="px-5 py-2 bg-brand text-white text-sm font-semibold rounded-lg
                       hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                Cari
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-primary font-bold text-base">Daftar Catatan</h2>
            <span id="total-info" class="text-muted text-xs"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-soft text-muted text-left">
                        <th class="pb-2 font-semibold">Pegawai</th>
                        <th class="pb-2 font-semibold">Tanggal</th>
                        <th class="pb-2 font-semibold">Jam Keluar</th>
                        <th class="pb-2 font-semibold">Jam Kembali</th>
                        <th class="pb-2 font-semibold">Durasi</th>
                        <th class="pb-2 font-semibold">Status</th>
                        <th class="pb-2 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody id="catatan-tbody" class="divide-y divide-soft">
                    <tr><td colspan="7" class="py-6 text-center text-muted">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        <div id="pagination" class="hidden flex items-center justify-between mt-5 pt-4 border-t border-soft">
            <button id="btn-prev" onclick="changePage(-1)"
                class="px-4 py-1.5 text-sm rounded-lg border border-sky/40 text-muted hover:bg-soft transition cursor-pointer">
                ← Sebelumnya
            </button>
            <span id="page-info" class="text-muted text-xs"></span>
            <button id="btn-next" onclick="changePage(1)"
                class="px-4 py-1.5 text-sm rounded-lg border border-sky/40 text-muted hover:bg-soft transition cursor-pointer">
                Berikutnya →
            </button>
        </div>
    </div>

</div>

{{-- Modal Hapus --}}
<div id="modal-hapus" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(10,46,92,0.4)">
    <div class="bg-canvas rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="text-4xl mb-3">🗑️</div>
        <h3 class="text-primary font-bold text-base mb-1">Hapus Catatan?</h3>
        <p class="text-muted text-sm mb-6">Catatan ini akan dihapus permanen.</p>
        <div class="flex gap-3">
            <button onclick="confirmHapus()"
                class="flex-1 py-2.5 rounded-lg bg-danger text-white text-sm font-semibold hover:bg-red-700 transition cursor-pointer">
                Ya, Hapus
            </button>
            <button onclick="closeHapus()"
                class="flex-1 py-2.5 rounded-lg border border-sky/40 text-muted text-sm hover:bg-soft transition cursor-pointer">
                Batal
            </button>
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

let currentPage = 1;
let lastMeta = null;
let hapusId = null;

// Set default tanggal hari ini
document.getElementById('filter-tanggal').value = new Date().toISOString().split('T')[0];

async function loadCatatan(page = 1) {
    currentPage = page;
    const tanggal = document.getElementById('filter-tanggal').value;
    const pegawai = document.getElementById('filter-pegawai').value;
    const status  = document.getElementById('filter-status').value;

    const params = new URLSearchParams({ page, per_page: 20 });
    if (tanggal) params.append('tanggal', tanggal);
    if (pegawai) params.append('search', pegawai);
    if (status)  params.append('status', status);

    const tbody = document.getElementById('catatan-tbody');
    tbody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-muted">Memuat...</td></tr>`;

    try {
        const res = await fetch('/api/riwayat/semua?' + params, { headers });
        const json = await res.json();
        const data = json.data ?? [];
        lastMeta = json.meta ?? null;

        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-muted">Tidak ada data</td></tr>`;
            document.getElementById('pagination').classList.add('hidden');
            document.getElementById('total-info').textContent = '';
            return;
        }

        const statusClass = {
            terbuka:       'bg-orange-100 text-warning border-orange-200',
            kembali:       'bg-green-100 text-success border-green-200',
            belum_kembali: 'bg-red-100 text-danger border-red-200',
        };

        tbody.innerHTML = data.map(r => `
            <tr class="hover:bg-soft/50 transition">
                <td class="py-2.5 pr-4">
                    <p class="text-text font-medium">${r.pegawai?.nama_lengkap ?? '-'}</p>
                    <p class="text-muted text-xs">${r.pegawai?.unit_kerja?.nama_unit ?? ''}</p>
                </td>
                <td class="py-2.5 pr-4 text-text">${r.tanggal ?? '-'}</td>
                <td class="py-2.5 pr-4 text-text">${r.jam_keluar ?? '-'}</td>
                <td class="py-2.5 pr-4 text-text">${r.jam_kembali ?? '—'}</td>
                <td class="py-2.5 pr-4 text-muted">${r.durasi_menit ? r.durasi_menit + ' mnt' : '—'}</td>
                <td class="py-2.5 pr-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border
                        ${statusClass[r.status] ?? 'bg-soft text-brand border-sky/40'}">
                        ${r.status?.replace('_', ' ') ?? '-'}
                    </span>
                </td>
                <td class="py-2.5">
                    <div class="flex gap-2">
                        ${r.status === 'terbuka' ? `
                        <button onclick="tutupManual(${r.pasangan_id})"
                            class="text-xs text-success hover:underline font-medium cursor-pointer">
                            Tutup
                        </button>` : ''}
                        <a href="/admin/catatan/${r.id}/edit"
                            class="text-xs text-brand hover:underline font-medium">Edit</a>
                        <button onclick="openHapus(${r.id})"
                            class="text-xs text-danger hover:underline font-medium cursor-pointer">Hapus</button>
                    </div>
                </td>
            </tr>
        `).join('');

        if (lastMeta) {
            document.getElementById('total-info').textContent = `Total: ${lastMeta.total} data`;
            document.getElementById('page-info').textContent = `Halaman ${lastMeta.current_page} dari ${lastMeta.last_page}`;
            document.getElementById('btn-prev').disabled = lastMeta.current_page <= 1;
            document.getElementById('btn-next').disabled = lastMeta.current_page >= lastMeta.last_page;
            document.getElementById('pagination').classList.remove('hidden');
        }
    } catch (_) {
        tbody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-muted">Gagal memuat data</td></tr>`;
    }
}

async function tutupManual(pasanganId) {
    try {
        const res = await fetch('/api/pindaian/catat-masuk', {
            method: 'POST', headers,
            body: JSON.stringify({ pasangan_id: pasanganId, manual: true }),
        });
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

function openHapus(id) {
    hapusId = id;
    document.getElementById('modal-hapus').classList.remove('hidden');
}

function closeHapus() {
    document.getElementById('modal-hapus').classList.add('hidden');
    hapusId = null;
}

async function confirmHapus() {
    try {
        const res = await fetch(`/api/riwayat/${hapusId}`, { method: 'DELETE', headers });
        const json = await res.json();
        closeHapus();
        if (res.ok) { showToast('Catatan berhasil dihapus', 'success'); loadCatatan(currentPage); }
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

loadCatatan();
</script>
@endpush
