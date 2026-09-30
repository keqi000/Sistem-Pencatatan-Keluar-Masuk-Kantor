@extends('layouts.app')

@section('title', 'Rekap Unit Kerja')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Info Unit --}}
    <div class="bg-soft border border-sky/40 rounded-2xl px-5 py-4 flex items-center gap-3">
        <span class="text-brand text-xl">🏢</span>
        <div>
            <p class="text-primary font-semibold text-sm">Unit Kerja Anda</p>
            <p id="nama-unit" class="text-muted text-sm">Memuat...</p>
        </div>
    </div>

    {{-- Tab Harian / Bulanan --}}
    <div class="flex gap-1 bg-canvas rounded-xl border border-soft p-1 w-fit shadow-sm">
        <button onclick="switchTab('harian')" data-tab="harian"
            class="tab-btn px-5 py-2 rounded-lg text-sm font-medium transition cursor-pointer bg-brand text-white shadow">
            Harian
        </button>
        <button onclick="switchTab('bulanan')" data-tab="bulanan"
            class="tab-btn px-5 py-2 rounded-lg text-sm font-medium transition cursor-pointer text-muted hover:text-primary">
            Bulanan
        </button>
    </div>

    {{-- Filter --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div id="filter-harian" class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-primary">Tanggal</label>
                <input type="date" id="input-tanggal"
                    class="px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
            </div>
            <div id="filter-bulanan" class="hidden flex-col gap-1.5">
                <label class="text-xs font-semibold text-primary">Bulan</label>
                <input type="month" id="input-bulan"
                    class="px-3 py-2 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                           focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
            </div>
            <button onclick="loadRekap()"
                class="px-5 py-2 bg-brand text-white text-sm font-semibold rounded-lg
                       hover:bg-blue-700 transition shadow shadow-brand/30 cursor-pointer">
                Tampilkan
            </button>
            <div class="flex gap-2 ml-auto">
                <a id="btn-pdf" href="#"
                    class="px-4 py-2 bg-danger text-white text-sm font-semibold rounded-lg
                           hover:bg-red-700 transition shadow shadow-red-200">
                    ↓ PDF
                </a>
                <a id="btn-excel" href="#"
                    class="px-4 py-2 bg-success text-white text-sm font-semibold rounded-lg
                           hover:bg-green-700 transition shadow shadow-green-200">
                    ↓ Excel
                </a>
            </div>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-primary font-bold text-base">Data Rekap</h2>
            <span id="rekap-info" class="text-muted text-xs"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead id="rekap-thead">
                    <tr class="border-b border-soft text-muted text-left">
                        <th class="pb-2 font-semibold">Nama Pegawai</th>
                        <th class="pb-2 font-semibold">Jam Keluar</th>
                        <th class="pb-2 font-semibold">Jam Kembali</th>
                        <th class="pb-2 font-semibold">Durasi</th>
                        <th class="pb-2 font-semibold">Keperluan</th>
                        <th class="pb-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody id="rekap-tbody" class="divide-y divide-soft">
                    <tr><td colspan="6" class="py-6 text-center text-muted">Pilih tanggal/bulan lalu klik Tampilkan</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const unitKerjaId = @json($unitKerjaId);
const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

let activeTab = 'harian';

// Set default tanggal & bulan
document.getElementById('input-tanggal').value = new Date().toISOString().split('T')[0];
document.getElementById('input-bulan').value = new Date().toISOString().slice(0, 7);

// Load nama unit
async function loadNamaUnit() {
    if (!unitKerjaId) {
        document.getElementById('nama-unit').textContent = 'Unit kerja tidak ditemukan';
        return;
    }
    try {
        const res = await fetch('/api/pengaturan/unit-kerja', { headers });
        const { data } = await res.json();
        const unit = (data ?? []).find(u => u.id == unitKerjaId);
        document.getElementById('nama-unit').textContent = unit?.nama_unit ?? 'Unit kerja tidak ditemukan';
    } catch (_) {}
}

function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.dataset.tab === tab;
        btn.className = btn.className
            .replace(isActive ? 'text-muted hover:text-primary' : 'bg-brand text-white shadow',
                     isActive ? 'bg-brand text-white shadow'     : 'text-muted hover:text-primary');
    });
    document.getElementById('filter-harian').classList.toggle('hidden', tab !== 'harian');
    document.getElementById('filter-harian').classList.toggle('flex', tab === 'harian');
    document.getElementById('filter-bulanan').classList.toggle('hidden', tab !== 'bulanan');
    document.getElementById('filter-bulanan').classList.toggle('flex', tab === 'bulanan');

    // Update thead
    const thead = document.getElementById('rekap-thead');
    if (tab === 'bulanan') {
        thead.innerHTML = `<tr class="border-b border-soft text-muted text-left">
            <th class="pb-2 font-semibold">Nama Pegawai</th>
            <th class="pb-2 font-semibold">Hari Keluar</th>
            <th class="pb-2 font-semibold">Total Menit</th>
            <th class="pb-2 font-semibold">Belum Kembali</th>
        </tr>`;
    } else {
        thead.innerHTML = `<tr class="border-b border-soft text-muted text-left">
            <th class="pb-2 font-semibold">Nama Pegawai</th>
            <th class="pb-2 font-semibold">Jam Keluar</th>
            <th class="pb-2 font-semibold">Jam Kembali</th>
            <th class="pb-2 font-semibold">Durasi</th>
            <th class="pb-2 font-semibold">Keperluan</th>
            <th class="pb-2 font-semibold">Status</th>
        </tr>`;
    }
    document.getElementById('rekap-tbody').innerHTML =
        `<tr><td colspan="6" class="py-6 text-center text-muted">Pilih ${tab === 'harian' ? 'tanggal' : 'bulan'} lalu klik Tampilkan</td></tr>`;
}

async function loadRekap() {
    const tbody = document.getElementById('rekap-tbody');
    tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-muted">Memuat...</td></tr>`;

    const params = new URLSearchParams();
    if (unitKerjaId) params.append('unit_id', unitKerjaId);

    let url;
    if (activeTab === 'harian') {
        params.append('tanggal', document.getElementById('input-tanggal').value);
        url = '/api/rekap/harian?' + params;
        updateExportLinks('tanggal=' + document.getElementById('input-tanggal').value);
    } else {
        params.append('bulan', document.getElementById('input-bulan').value);
        url = '/api/rekap/bulanan?' + params;
        updateExportLinks('bulan=' + document.getElementById('input-bulan').value);
    }

    try {
        const res = await fetch(url, { headers });
        const { data, meta } = await res.json();
        const list = data ?? [];

        document.getElementById('rekap-info').textContent = `${list.length} pegawai`;

        if (!list.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-muted">Tidak ada data</td></tr>`;
            return;
        }

        const statusClass = {
            kembali:       'bg-green-100 text-success border-green-200',
            terbuka:       'bg-orange-100 text-warning border-orange-200',
            belum_kembali: 'bg-red-100 text-danger border-red-200',
        };

        if (activeTab === 'harian') {
            tbody.innerHTML = list.map(r => `
                <tr class="hover:bg-soft/50 transition">
                    <td class="py-2.5 pr-4 text-text font-medium">${r.nama_lengkap ?? '-'}</td>
                    <td class="py-2.5 pr-4 text-text">${r.jam_keluar ?? '-'}</td>
                    <td class="py-2.5 pr-4 text-text">${r.jam_kembali ?? '—'}</td>
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
        } else {
            tbody.innerHTML = list.map(r => `
                <tr class="hover:bg-soft/50 transition">
                    <td class="py-2.5 pr-4 text-text font-medium">${r.nama_lengkap ?? '-'}</td>
                    <td class="py-2.5 pr-4 text-text">${r.hari_keluar ?? 0} hari</td>
                    <td class="py-2.5 pr-4 text-text">${r.total_menit ?? 0} mnt</td>
                    <td class="py-2.5">
                        ${r.belum_kembali > 0
                            ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-red-100 text-danger border-red-200">${r.belum_kembali}x</span>`
                            : `<span class="text-muted">—</span>`}
                    </td>
                </tr>
            `).join('');
        }
    } catch (_) {
        tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-muted">Gagal memuat data</td></tr>`;
    }
}

function updateExportLinks(query) {
    const unitParam = unitKerjaId ? `&unit_id=${unitKerjaId}` : '';
    document.getElementById('btn-pdf').href   = `/api/rekap/export-pdf?${query}${unitParam}`;
    document.getElementById('btn-excel').href = `/api/rekap/export-excel?${query}${unitParam}`;
}

loadNamaUnit();
</script>
@endpush
