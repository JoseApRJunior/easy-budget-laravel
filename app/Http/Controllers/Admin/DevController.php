<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Laravel\Pennant\Feature;

class DevController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Alterna entre o modo de simulação de produção e modo admin normal.
     */
    public function toggleSimulationMode(Request $request)
    {
        $current = session('simulation_mode', 'development');
        $new = ($current === 'development') ? 'production' : 'development';
        
        session(['simulation_mode' => $new]);

        // Limpa o cache do Pennant para o usuário atual para forçar a revalidação das flags
        Feature::purge();
        Artisan::call('cache:clear');

        $message = $new === 'production' 
            ? 'Modo de Simulação de Produção ATIVADO (Você verá o sistema como um usuário comum).' 
            : 'Modo de Desenvolvimento ATIVADO (Acesso total restaurado).';

        return back()->with('success', $message);
    }
}
