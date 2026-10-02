<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap SIKMA - {{ $namaInstansi }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; margin: 30px; color: #000; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
        .kop h3 { margin: 0; font-size: 13pt; text-transform: uppercase; font-weight: normal; }
        .kop h2 { margin: 4px 0; font-size: 15pt; text-transform: uppercase; font-weight: bold; }
        .kop p { margin: 0; font-size: 10pt; font-style: italic; }
        .judul { text-align: center; margin-bottom: 20px; }
        .judul h4 { margin: 0; text-transform: uppercase; text-decoration: underline; font-size: 13pt; }
        .judul p { margin: 4px 0; font-size: 11pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer-sign { margin-top: 40px; float: right; width: 250px; text-align: center; }
        .footer-sign .space { height: 70px; }
        @media print {
            .no-print { display: none; }
            body { margin: 15mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #1A5276; color: #fff; border: none; cursor: pointer; border-radius: 4px;">🖨️ Cetak / Simpan PDF</button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #555; color: #fff; border: none; cursor: pointer; border-radius: 4px;">✕ Tutup</button>
    </div>

    <div class="kop">
        <h3>{{ $kementerian }}</h3>
        <h2>{{ $namaInstansi }}</h2>
        <p>{{ $alamatInstansi }}</p>
    </div>

    <div class="judul">
        <h4>{{ $tipe === 'bulanan' ? 'Laporan Rekapitulasi Bulanan Keluar–Masuk Kantor' : 'Laporan Rekapitulasi Harian Keluar–Masuk Kantor' }}</h4>
        <p>Periode: <strong>{{ $tipe === 'bulanan' ? $bulan : date('d F Y', strtotime($tanggal)) }}</strong></p>
    </div>

    @if ($tipe === 'bulanan')
        @php
            $pegawais = \App\Models\Pegawai::with(['unitKerja', 'pasanganKeluarMasuk' => function($q) use ($bulan) {
                $q->whereYear('jam_keluar', substr($bulan, 0, 4))
                  ->whereMonth('jam_keluar', substr($bulan, 5, 2))
                  ->with('pindaianKeluar');
            }])->where('status', 'aktif')
            ->when($unitKerjaId ?? 0, fn($q, $id) => $q->where('unit_kerja_id', $id))
            ->get();
        @endphp
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Pegawai / NIP</th>
                    <th>Unit Kerja</th>
                    <th width="10%">Hari Keluar</th>
                    <th width="10%">Total Keluar</th>
                    <th width="10%">Dinas</th>
                    <th width="10%">Tanpa Izin</th>
                    <th width="12%">Belum Kembali</th>
                    <th width="15%">Total Durasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pegawais as $idx => $r)
                    @php
                        $sesi = $r->pasanganKeluarMasuk;
                        $hari = $sesi->pluck('jam_keluar')->map(fn($d) => $d->toDateString())->unique()->count();
                        $dinas = $sesi->filter(fn($s) => $s->pindaianKeluar?->keperluan_jenis === 'dinas')->count();
                        $tanpaIzin = $sesi->filter(fn($s) => $s->pindaianKeluar?->keperluan_jenis === 'keperluan_lain')->count();
                        $belum = $sesi->where('status', 'belum_kembali')->count();
                        $totalMenit = (int)$sesi->sum('durasi_menit');
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $r->nama_lengkap }}</strong><br><small>{{ $r->nip }}</small></td>
                        <td>{{ $r->unitKerja?->nama_unit ?? '-' }}</td>
                        <td class="text-center">{{ $hari }}</td>
                        <td class="text-center">{{ $sesi->count() }}</td>
                        <td class="text-center">{{ $dinas }}</td>
                        <td class="text-center">{{ $tanpaIzin }}</td>
                        <td class="text-center">{{ $belum }}</td>
                        <td class="text-right">{{ $totalMenit }} menit</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center">Tidak ada data aktivitas pada bulan ini</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        @php
            $records = \App\Models\PasanganKeluarMasuk::with(['pegawai.unitKerja', 'pindaianKeluar.izinDinas'])
                ->whereDate('jam_keluar', $tanggal)
                ->when($unitKerjaId ?? 0, fn($q, $id) => $q->whereHas('pegawai', fn($pq) => $pq->where('unit_kerja_id', $id)))
                ->orderBy('jam_keluar', 'asc')
                ->get();
        @endphp
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Pegawai / NIP</th>
                    <th>Unit Kerja</th>
                    <th>Jam Keluar</th>
                    <th>Jam Kembali</th>
                    <th>Durasi</th>
                    <th>Keperluan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $idx => $r)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $r->pegawai?->nama_lengkap }}</strong><br><small>{{ $r->pegawai?->nip }}</small></td>
                        <td>{{ $r->pegawai?->unitKerja?->nama_unit ?? '-' }}</td>
                        <td class="text-center">{{ $r->jam_keluar?->format('H:i') }}</td>
                        <td class="text-center">{{ $r->jam_kembali ? $r->jam_kembali->format('H:i') : '-' }}</td>
                        <td class="text-center">{{ $r->durasi_menit ? $r->durasi_menit . ' mnt' : '-' }}</td>
                        <td>{{ $r->pindaianKeluar?->keperluan_jenis === 'dinas' ? 'Dinas: ' . ($r->pindaianKeluar?->izinDinas?->tujuan ?? '') : 'Keperluan Lain' }}</td>
                        <td class="text-center">{{ ucfirst(str_replace('_', ' ', $r->status)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">Tidak ada catatan keluar pada tanggal ini</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer-sign">
        @php
            $pimpinanUser = \App\Models\User::where('role', 'pimpinan')
                ->where('status', 'aktif')
                ->with('pegawai')
                ->first();
        @endphp
        <p>Gorontalo, {{ date('d F Y') }}<br>Mengetahui,<br>Pimpinan</p>
        <div class="space"></div>
        <p>
            <strong>{{ $pimpinanUser?->full_name ?? 'Pimpinan' }}</strong>
            @if($pimpinanUser?->pegawai?->nip)
                <br>NIP. {{ $pimpinanUser->pegawai->nip }}
            @endif
        </p>
    </div>
</body>
</html>
