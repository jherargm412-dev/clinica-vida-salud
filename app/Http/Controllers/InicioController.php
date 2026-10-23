<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla de inicio después de iniciar sesión (UI_Dashboard). */
class InicioController extends Controller
{
    public function index(Request $request): View
    {
        return view('inicio', [
            'modulos' => $request->user()->modulosPermitidos(),
        ]);
    }
}
