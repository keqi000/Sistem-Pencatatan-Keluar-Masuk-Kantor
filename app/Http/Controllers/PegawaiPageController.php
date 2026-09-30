<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PegawaiPageController extends Controller
{
    public function dashboard()
    {
        return view('pegawai.dashboard');
    }

    public function scan()
    {
        return view('pegawai.scan');
    }

    public function izinDinas()
    {
        return view('pegawai.izin-dinas');
    }

    public function riwayat()
    {
        return view('pegawai.riwayat');
    }
}
