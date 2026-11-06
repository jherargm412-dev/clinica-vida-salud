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
        // Mismos grupos que el menú lateral (config/modulos.php), solo con lo que el rol puede ver
        return view('compartido.inicio', [
            'grupos' => $request->user()->gruposMenu(),
        ]);
    }
}
