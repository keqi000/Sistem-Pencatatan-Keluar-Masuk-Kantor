@extends('layouts.app')

@section('title', 'Manajemen Pegawai')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <input type="text" id="filter-nama" placeholder="Cari nama / NIP..."
                oninput="loadPegawai()"
                class="px-3 py-2 rounded-lg border border-sky/40 bg-canvas text-text text-sm w-52
                       focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition placeholder:text-muted/50">
            <select id="filter-unit" onchange="loadPegawai()"
                class="px-3 py-2 rounded-lg border border-sky/40 bg-canvas text-text text-sm
                       focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                <option value="">Semua Unit</option>
            </select>
            <select id="filter-status" onchange="loadPegawai()"
                class="px-3 py-2 rounded-lg border border-sky/40 bg-canvas text-text text-sm
                       focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand transition">
                <option value="">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="tidak_aktif">Tidak Aktif</option>
            </select>
        </div>
        <a href="/admin/pegawai/create"
            class="px-5 py-2 bg-brand text-white text-sm font-semibold rounded-lg
                   hover:bg-blue-700 transition shadow shadow-brand/30">
            + Tambah Pegawai
        </a>
    </div>

    {{-- Grid --}}
    <div id="pegawai-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div class="col-span-full py-10 text-center text-muted">Memuat data...</div>
    </div>

    {{-- Pagination --}}
    <div id="pagination" class="hidden flex items-center justify-between pt-2">
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

{{-- Modal Hapus --}}
<div id="modal-hapus" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(10,46,92,0.4)">
    <div class="bg-canvas rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="text-4xl mb-3">🗑️</div>
        <h3 class="text-primary font-bold text-base mb-1">Hapus Pegawai?</h3>
        <p id="hapus-nama" class="text-muted text-sm mb-6"></p>
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
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};

let currentPage = 1;
let lastMeta = null;
let hapusId = null;

async function loadUnits() {
    try {
        const res = await fetch('/api/pengaturan/unit-kerja', { headers });
        const { data } = await res.json();
        const sel = document.getElementById('filter-unit');
        (data ?? []).forEach(u => {
            sel.innerHTML += `<option value="${u.id}">${u.nama_unit}</option>`;
        });
    } catch (_) {}
}

async function loadPegawai(page = 1) {
    currentPage = page;
    const nama   = document.getElementById('filter-nama').value;
    const unit   = document.getElementById('filter-unit').value;
    const status = document.getElementById('filter-status').value;

    const params = new URLSearchParams({ page, per_page: 12 });
    if (nama)   params.append('search', nama);
    if (unit)   params.append('unit_kerja_id', unit);
    if (status) params.append('status', status);

    const grid = document.getElementById('pegawai-grid');
    grid.innerHTML = `<div class="col-span-full py-10 text-center text-muted">Memuat...</div>`;

    try {
        const res = await fetch('/api/pegawai?' + params, { headers });
        const json = await res.json();
        const data = json.data ?? [];
        lastMeta = json.meta ?? null;

        if (!data.length) {
            grid.innerHTML = `<div class="col-span-full py-10 text-center text-muted">Tidak ada data pegawai</div>`;
            document.getElementById('pagination').classList.add('hidden');
            return;
        }

        grid.innerHTML = data.map(p => `
            <div class="bg-canvas rounded-2xl border border-soft shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-soft border border-sky/30 overflow-hidden shrink-0 flex items-center justify-center">
                        ${p.foto
                            ? `<img src="/storage/${p.foto}" class="w-full h-full object-cover">`
                            : `<span class="text-brand font-bold text-lg">${p.nama_lengkap?.charAt(0) ?? '?'}</span>`}
                    </div>
                    <div class="min-w-0">
                        <p class="text-primary font-bold text-sm truncate">${p.nama_lengkap}</p>
                        <p class="text-muted text-xs">${p.nip}</p>
                    </div>
                </div>
                <div class="text-xs text-muted flex flex-col gap-1">
                    <span>📌 ${p.jabatan ?? '-'}</span>
                    <span>🏢 ${p.unit_kerja?.nama_unit ?? '-'}</span>
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-soft">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border
                        ${p.status === 'aktif' ? 'bg-green-100 text-success border-green-200' : 'bg-red-100 text-danger border-red-200'}">
                        ${p.status === 'aktif' ? 'Aktif' : 'Tidak Aktif'}
                    </span>
                    <div class="flex gap-2">
                        <a href="/admin/pegawai/${p.id}/edit"
                            class="text-xs text-brand hover:underline font-medium">Edit</a>
                        <button onclick="openHapus(${p.id}, '${p.nama_lengkap}')"
                            class="text-xs text-danger hover:underline font-medium cursor-pointer">Hapus</button>
                    </div>
                </div>
            </div>
        `).join('');

        if (lastMeta) {
            document.getElementById('page-info').textContent = `Halaman ${lastMeta.current_page} dari ${lastMeta.last_page}`;
            document.getElementById('btn-prev').disabled = lastMeta.current_page <= 1;
            document.getElementById('btn-next').disabled = lastMeta.current_page >= lastMeta.last_page;
            document.getElementById('pagination').classList.remove('hidden');
        }
    } catch (_) {
        grid.innerHTML = `<div class="col-span-full py-10 text-center text-muted">Gagal memuat data</div>`;
    }
}

function changePage(dir) {
    if (!lastMeta) return;
    const next = currentPage + dir;
    if (next < 1 || next > lastMeta.last_page) return;
    loadPegawai(next);
}

function openHapus(id, nama) {
    hapusId = id;
    document.getElementById('hapus-nama').textContent = `Pegawai "${nama}" akan dihapus permanen.`;
    document.getElementById('modal-hapus').classList.remove('hidden');
}

function closeHapus() {
    document.getElementById('modal-hapus').classList.add('hidden');
    hapusId = null;
}

async function confirmHapus() {
    try {
        const res = await fetch(`/api/pegawai/${hapusId}`, {
            method: 'DELETE',
            headers: { ...headers, 'Content-Type': 'application/json' },
        });
        const json = await res.json();
        closeHapus();
        if (res.ok) {
            showToast('Pegawai berhasil dihapus', 'success');
            loadPegawai(currentPage);
        } else {
            showToast(json.message ?? 'Gagal menghapus', 'danger');
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

loadUnits();
loadPegawai();
</script>
@endpush
