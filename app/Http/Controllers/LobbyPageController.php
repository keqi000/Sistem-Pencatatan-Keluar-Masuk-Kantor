<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LobbyPageController extends Controller
{
    public function layar()
    {
        return view('lobby.layar');
    }
}
