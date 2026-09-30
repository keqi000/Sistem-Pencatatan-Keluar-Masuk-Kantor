@props(['status'])

@php
    $map = [
        'di_kantor'     => ['label' => 'Di Kantor',      'class' => 'bg-green-100 text-success border-green-200'],
        'sedang_diluar' => ['label' => 'Sedang di Luar',  'class' => 'bg-orange-100 text-warning border-orange-200'],
        'belum_kembali' => ['label' => 'Belum Kembali',   'class' => 'bg-red-100 text-danger border-red-200'],
        'dinas'         => ['label' => 'Dinas',            'class' => 'bg-soft text-brand border-sky/40'],
        'menunggu'      => ['label' => 'Menunggu',         'class' => 'bg-orange-100 text-warning border-orange-200'],
        'disetujui'     => ['label' => 'Disetujui',        'class' => 'bg-green-100 text-success border-green-200'],
        'ditolak'       => ['label' => 'Ditolak',          'class' => 'bg-red-100 text-danger border-red-200'],
    ];
    $badge = $map[$status] ?? ['label' => $status, 'class' => 'bg-soft text-brand border-sky/40'];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badge['class'] }}">
    {{ $badge['label'] }}
</span>
