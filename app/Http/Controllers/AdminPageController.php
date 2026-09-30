<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminPageController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function pegawai()
    {
        return view('admin.pegawai.index');
    }

    public function pegawaiCreate()
    {
        return view('admin.pegawai.create');
    }

    public function pegawaiEdit($id)
    {
        return view('admin.pegawai.edit', compact('id'));
    }

    public function catatan()
    {
        return view('admin.catatan.index');
    }

    public function catatanEdit($id)
    {
        return view('admin.catatan.edit', compact('id'));
    }

    public function rekap()
    {
        return view('admin.rekap');
    }

    public function pengaturan()
    {
        return view('admin.pengaturan');
    }
}
