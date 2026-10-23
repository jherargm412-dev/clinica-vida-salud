<?php

namespace App\Modulos\usuarios_acceso\controller\iniciar_sesion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla de inicio después de iniciar sesión (UI_Dashboard). */
class InicioController extends Controller
{
    public function index(Request $request): View
    {
        return view('compartido.inicio', [
            'modulos' => $request->user()->modulosPermitidos(),
        ]);
    }
}
