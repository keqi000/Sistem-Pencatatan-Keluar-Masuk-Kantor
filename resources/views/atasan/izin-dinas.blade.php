@extends('layouts.app')

@section('title', 'Izin Dinas Bawahan')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Tab --}}
    <div class="flex gap-1 bg-canvas rounded-xl border border-soft p-1 w-fit shadow-sm">
        @foreach(['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $val => $label)
        <button onclick="filterTab('{{ $val }}')" data-tab="{{ $val }}"
            class="tab-btn relative px-5 py-2 rounded-lg text-sm font-medium transition cursor-pointer
                   {{ $val === 'menunggu' ? 'bg-brand text-white shadow' : 'text-muted hover:text-primary' }}">
            {{ $label }}
            <span id="badge-{{ $val }}"
                class="hidden absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-accent text-white text-xs font-bold flex items-center justify-center">
            </span>
        </button>
        @endforeach
    </div>

    {{-- Daftar Kartu --}}
    <div id="izin-list" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="col-span-full py-10 text-center text-muted">Memuat data...</div>
    </div>

</div>

{{-- Modal Putuskan --}}
<div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(10,46,92,0.4)">
    <div class="bg-canvas rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 id="modal-title" class="text-primary font-bold text-base mb-1"></h3>
        <p id="modal-sub" class="text-muted text-sm mb-5"></p>

        <div class="flex flex-col gap-1.5 mb-5">
            <label id="modal-label" class="text-sm font-semibold text-primary"></label>
            <textarea id="modal-catatan" rows="3"
                class="px-4 py-2.5 rounded-lg border border-sky/40 bg-soft/40 text-text text-sm
                       focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition
                       placeholder:text-muted/50 resize-none"></textarea>
        </div>

        <div class="flex gap-3">
            <button id="modal-confirm" onclick="submitPutuskan()"
                class="flex-1 py-2.5 rounded-lg text-white text-sm font-semibold transition cursor-pointer shadow">
                Konfirmasi
            </button>
            <button onclick="closeModal()"
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

let allData = [];
let activeTab = 'menunggu';
let modalIzinId = null;
let modalAksi = null;

async function loadIzin() {
    try {
        const res = await fetch('/api/izin-dinas/bawahan', { headers });
        const { data } = await res.json();
        allData = data ?? [];

        // Update badge counter
        ['menunggu', 'disetujui', 'ditolak'].forEach(s => {
            const count = allData.filter(d => d.status === s).length;
            const badge = document.getElementById('badge-' + s);
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });

        renderKartu(allData.filter(d => d.status === activeTab));
    } catch (_) {
        document.getElementById('izin-list').innerHTML =
            `<div class="col-span-full py-10 text-center text-muted">Gagal memuat data</div>`;
    }
}

function renderKartu(data) {
    const list = document.getElementById('izin-list');

    if (!data.length) {
        list.innerHTML = `<div class="col-span-full py-10 text-center text-muted">Tidak ada pengajuan</div>`;
        return;
    }

    const badgeClass = {
        menunggu:  'bg-orange-100 text-warning border-orange-200',
        disetujui: 'bg-green-100 text-success border-green-200',
        ditolak:   'bg-red-100 text-danger border-red-200',
    };

    list.innerHTML = data.map(d => `
        <div class="bg-canvas rounded-2xl border border-soft shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-primary font-bold text-sm">${d.pegawai?.nama_lengkap ?? '-'}</p>
                    <p class="text-muted text-xs">${d.pegawai?.jabatan ?? ''} — ${d.pegawai?.unit_kerja?.nama_unit ?? ''}</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border shrink-0
                    ${badgeClass[d.status] ?? 'bg-soft text-brand border-sky/40'}">
                    ${d.status.charAt(0).toUpperCase() + d.status.slice(1)}
                </span>
            </div>

            <div class="bg-soft rounded-xl p-3 flex flex-col gap-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted">Tanggal</span>
                    <span class="text-text font-medium">${d.tanggal}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">Tujuan</span>
                    <span class="text-text font-medium">${d.tujuan}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">Jam</span>
                    <span class="text-text">${d.perkiraan_jam_pergi} — ${d.perkiraan_jam_kembali}</span>
                </div>
                <div class="pt-1 border-t border-sky/20">
                    <span class="text-muted text-xs">${d.keperluan}</span>
                </div>
            </div>

            ${d.catatan_atasan ? `
            <div class="text-xs text-muted bg-soft/60 rounded-lg px-3 py-2">
                <span class="font-semibold text-primary">Catatan:</span> ${d.catatan_atasan}
            </div>` : ''}

            ${d.status === 'menunggu' ? `
            <div class="flex gap-2 pt-1">
                <button onclick="openModal(${d.id}, 'setujui')"
                    class="flex-1 py-2 rounded-lg bg-success text-white text-xs font-semibold
                           hover:bg-green-700 transition shadow shadow-green-200 cursor-pointer">
                    ✓ Setujui
                </button>
                <button onclick="openModal(${d.id}, 'tolak')"
                    class="flex-1 py-2 rounded-lg bg-danger text-white text-xs font-semibold
                           hover:bg-red-700 transition shadow shadow-red-200 cursor-pointer">
                    ✗ Tolak
                </button>
            </div>` : ''}
        </div>
    `).join('');
}

function filterTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.dataset.tab === tab;
        btn.className = btn.className
            .replace(isActive ? 'text-muted hover:text-primary' : 'bg-brand text-white shadow',
                     isActive ? 'bg-brand text-white shadow'     : 'text-muted hover:text-primary');
    });
    renderKartu(allData.filter(d => d.status === tab));
}

function openModal(id, aksi) {
    modalIzinId = id;
    modalAksi = aksi;
    const isSetujui = aksi === 'setujui';
    document.getElementById('modal-title').textContent = isSetujui ? 'Setujui Izin Dinas' : 'Tolak Izin Dinas';
    document.getElementById('modal-sub').textContent = isSetujui
        ? 'Tambahkan catatan opsional untuk pegawai.'
        : 'Berikan alasan penolakan (wajib diisi).';
    document.getElementById('modal-label').textContent = isSetujui ? 'Catatan (opsional)' : 'Alasan Penolakan *';
    document.getElementById('modal-catatan').placeholder = isSetujui ? 'Catatan untuk pegawai...' : 'Alasan penolakan...';
    document.getElementById('modal-catatan').value = '';
    const btn = document.getElementById('modal-confirm');
    btn.className = btn.className.replace(/bg-\S+/, isSetujui ? 'bg-success' : 'bg-danger');
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    modalIzinId = null;
    modalAksi = null;
}

async function submitPutuskan() {
    const catatan = document.getElementById('modal-catatan').value.trim();
    if (modalAksi === 'tolak' && !catatan) {
        showToast('Alasan penolakan wajib diisi', 'danger');
        return;
    }

    try {
        const res = await fetch(`/api/izin-dinas/${modalIzinId}/putuskan`, {
            method: 'POST',
            headers,
            body: JSON.stringify({ aksi: modalAksi, catatan_atasan: catatan }),
        });
        const json = await res.json();
        if (res.ok) {
            showToast(modalAksi === 'setujui' ? 'Izin disetujui' : 'Izin ditolak',
                      modalAksi === 'setujui' ? 'success' : 'danger');
            closeModal();
            loadIzin();
        } else {
            showToast(json.message ?? 'Gagal memproses', 'danger');
        }
    } catch (_) {
        showToast('Gagal terhubung ke server', 'danger');
    }
}

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
