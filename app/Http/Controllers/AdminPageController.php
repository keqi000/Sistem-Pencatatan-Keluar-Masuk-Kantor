<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminPageController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
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

    public function akun()
    {
        return view('admin.akun');
    }
}
