@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['id' => 'stat-diluar',   'label' => 'Sedang di Luar',     'color' => 'text-warning', 'bg' => 'bg-orange-50 border-orange-200'],
            ['id' => 'stat-kembali',  'label' => 'Sudah Kembali',       'color' => 'text-success', 'bg' => 'bg-green-50 border-green-200'],
            ['id' => 'stat-belum',    'label' => 'Belum Kembali',       'color' => 'text-danger',  'bg' => 'bg-red-50 border-red-200'],
            ['id' => 'stat-total',    'label' => 'Total Keluar Hari Ini','color' => 'text-brand',   'bg' => 'bg-soft border-sky/30'],
        ] as $card)
        <div class="bg-canvas rounded-2xl border {{ $card['bg'] }} p-5 shadow-sm">
            <p class="text-muted text-xs font-medium mb-2">{{ $card['label'] }}</p>
            <p id="{{ $card['id'] }}" class="text-3xl font-bold {{ $card['color'] }}">—</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Tabel Sedang di Luar --}}
        <div class="xl:col-span-2 bg-canvas rounded-2xl shadow-sm border border-soft p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-primary font-bold text-base">Sedang di Luar</h2>
                <span id="last-refresh" class="text-muted text-xs"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-soft text-muted text-left">
                            <th class="pb-2 font-semibold">Nama</th>
                            <th class="pb-2 font-semibold">Unit</th>
                            <th class="pb-2 font-semibold">Jam Keluar</th>
                            <th class="pb-2 font-semibold">Keperluan</th>
                            <th class="pb-2 font-semibold">Durasi</th>
                        </tr>
                    </thead>
                    <tbody id="diluar-tbody" class="divide-y divide-soft">
                        <tr><td colspan="5" class="py-6 text-center text-muted">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Chart --}}
        <div class="bg-canvas rounded-2xl shadow-sm border border-soft p-6">
            <h2 class="text-primary font-bold text-base mb-4">Aktivitas per Jam</h2>
            <canvas id="chart-aktivitas" height="260"></canvas>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

let chart = null;

async function loadStats() {
    try {
        const res = await fetch('/api/dashboard/stats', { headers });
        const { data } = await res.json();
        document.getElementById('stat-diluar').textContent  = data.sedang_diluar  ?? 0;
        document.getElementById('stat-kembali').textContent = data.sudah_kembali  ?? 0;
        document.getElementById('stat-belum').textContent   = data.belum_kembali  ?? 0;
        document.getElementById('stat-total').textContent   = data.total_keluar   ?? 0;
    } catch (_) {}
}

async function loadDiluar() {
    try {
        const res = await fetch('/api/dashboard/sedang-diluar', { headers });
        const { data } = await res.json();
        const tbody = document.getElementById('diluar-tbody');

        if (!data?.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-muted">Tidak ada pegawai di luar</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(r => {
            const melebihi = r.melebihi_estimasi;
            return `
            <tr class="hover:bg-soft/50 transition ${melebihi ? 'bg-red-50/50' : ''}">
                <td class="py-2.5 pr-4 text-text font-medium">${r.nama_lengkap ?? '-'}</td>
                <td class="py-2.5 pr-4 text-muted text-xs">${r.unit_kerja ?? '-'}</td>
                <td class="py-2.5 pr-4 text-text">${r.jam_keluar ?? '-'}</td>
                <td class="py-2.5 pr-4 text-text capitalize">${r.keperluan_jenis?.replace('_', ' ') ?? '-'}</td>
                <td class="py-2.5">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold
                        ${melebihi ? 'text-danger' : 'text-muted'}">
                        ${melebihi ? '⚠' : ''} ${r.durasi_berjalan ?? '—'}
                    </span>
                </td>
            </tr>`;
        }).join('');

        document.getElementById('last-refresh').textContent =
            'Diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    } catch (_) {}
}

async function loadChart() {
    try {
        const res = await fetch('/api/dashboard/chart', { headers });
        const { data } = await res.json();

        const ctx = document.getElementById('chart-aktivitas').getContext('2d');
        if (chart) chart.destroy();

        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels ?? [],
                datasets: [{
                    label: 'Keluar',
                    data: data.keluar ?? [],
                    backgroundColor: 'rgba(0,115,230,0.7)',
                    borderRadius: 6,
                }, {
                    label: 'Masuk',
                    data: data.masuk ?? [],
                    backgroundColor: 'rgba(22,163,74,0.7)',
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } },
                },
            },
        });
    } catch (_) {}
}

function loadAll() {
    loadStats();
    loadDiluar();
    loadChart();
}

loadAll();
setInterval(loadAll, 60000);
</script>
@endpush
