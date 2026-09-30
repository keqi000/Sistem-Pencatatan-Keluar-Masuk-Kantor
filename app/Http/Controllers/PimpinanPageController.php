<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PimpinanPageController extends Controller
{
    public function rekap()
    {
        $user = auth()->user();
        $unitKerjaId = $user->pegawai?->unit_kerja_id;

        return view('pimpinan.rekap', compact('unitKerjaId'));
    }
}
