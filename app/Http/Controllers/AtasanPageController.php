<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AtasanPageController extends Controller
{
    public function izinDinas()
    {
        return view('atasan.izin-dinas');
    }
}
