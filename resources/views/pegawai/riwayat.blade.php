@extends('layouts.app')

@section('title', 'Riwayat Saya')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Filter --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-primary">Dari Tanggal</label>
                <input type="date" id="filter-dari"
                    class="px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-primary">Sampai Tanggal</label>
                <input type="date" id="filter-sampai"
                    class="px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-primary">Keperluan</label>
                <select id="filter-keperluan"
                    class="px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                    <option value="">Semua</option>
                    <option value="dinas">Dinas</option>
                    <option value="keperluan_lain">Keperluan Lain</option>
                </select>
            </div>
            <button onclick="loadRiwayat(1)"
                class="px-5 py-2 bg-brand text-white text-sm font-semibold rounded-lg
                       hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                Cari
            </button>
            <button onclick="resetFilter()"
                class="px-4 py-2 bg-soft text-muted text-sm rounded-lg hover:bg-sky/20 transition cursor-pointer">
                Reset
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-primary font-bold text-base">Riwayat Aktivitas</h2>
            <span id="total-info" class="text-muted text-xs"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-soft text-muted text-left">
                        <th class="pb-2 font-semibold">Tanggal</th>
                        <th class="pb-2 font-semibold">Jam Keluar</th>
                        <th class="pb-2 font-semibold">Jam Kembali</th>
                        <th class="pb-2 font-semibold">Durasi</th>
                        <th class="pb-2 font-semibold">Keperluan</th>
                        <th class="pb-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody id="riwayat-tbody" class="divide-y divide-soft">
                    <tr><td colspan="6" class="py-6 text-center text-muted">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="pagination" class="flex items-center justify-between mt-5 pt-4 border-t border-soft hidden">
            <button id="btn-prev" onclick="changePage(-1)"
                class="px-4 py-1.5 text-sm rounded-lg border border-sky/40 text-muted hover:bg-soft transition cursor-pointer disabled:opacity-40">
                ← Sebelumnya
            </button>
            <span id="page-info" class="text-muted text-xs"></span>
            <button id="btn-next" onclick="changePage(1)"
                class="px-4 py-1.5 text-sm rounded-lg border border-sky/40 text-muted hover:bg-soft transition cursor-pointer disabled:opacity-40">
                Berikutnya →
            </button>
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

let currentPage = 1;
let lastMeta = null;

async function loadRiwayat(page = 1) {
    currentPage = page;
    const dari     = document.getElementById('filter-dari').value;
    const sampai   = document.getElementById('filter-sampai').value;
    const keperluan = document.getElementById('filter-keperluan').value;

    const params = new URLSearchParams({ page, per_page: 15 });
    if (dari)      params.append('dari', dari);
    if (sampai)    params.append('sampai', sampai);
    if (keperluan) params.append('keperluan_jenis', keperluan);

    const tbody = document.getElementById('riwayat-tbody');
    tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-muted">Memuat...</td></tr>`;

    try {
        const res = await fetch('/api/riwayat?' + params, { headers });
        const json = await res.json();
        const data = json.data ?? [];
        lastMeta = json.meta ?? null;

        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-muted">Tidak ada data</td></tr>`;
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
                <td class="py-2.5 pr-4 text-text">${r.tanggal ?? '-'}</td>
                <td class="py-2.5 pr-4 text-text">${r.jam_keluar ?? '-'}</td>
                <td class="py-2.5 pr-4 text-text">${r.jam_kembali ?? '<span class="text-muted">—</span>'}</td>
                <td class="py-2.5 pr-4 text-muted">${r.durasi_menit ? r.durasi_menit + ' mnt' : '—'}</td>
                <td class="py-2.5 pr-4 text-text capitalize">${r.keperluan_jenis?.replace('_', ' ') ?? '-'}</td>
                <td class="py-2.5">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border
                        ${statusClass[r.status] ?? 'bg-soft text-brand border-sky/40'}">
                        ${r.status?.replace('_', ' ') ?? '-'}
                    </span>
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
        tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-muted">Gagal memuat data</td></tr>`;
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
