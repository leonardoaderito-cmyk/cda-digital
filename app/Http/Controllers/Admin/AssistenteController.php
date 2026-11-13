<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Services\Assistente\Indice;
use Illuminate\Support\Facades\DB;

// Leonardo (leonardoaderito-cmyk): estado do Assistente CDA, reindexacao e perguntas recentes
class AssistenteController extends Controller
{
    public function index(Indice $indice)
    {
        $perguntas = DB::table('assistente_perguntas')->latest('created_at')->limit(60)->get();
        $semana = DB::table('assistente_perguntas')->where('created_at', '>=', now()->subDays(7));

        return view('admin.assistente', [
            'estado' => $indice->estado(),
            'perguntas' => $perguntas,
            'totalSemana' => (clone $semana)->count(),
            'semResposta' => (clone $semana)->where('modo', 'sem-resultados')->count(),
        ]);
    }

    public function indexar(Indice $indice)
    {
        @set_time_limit(600);
        $e = $indice->reconstruir();
        Auditoria::registar('assistente.indexado', $e['trechos'].' trechos');

        return back()->with('estado', "Índice refeito: {$e['documentos']} documentos ({$e['documentos_com_texto']} com texto), {$e['paginas']} páginas, {$e['noticias']} notícias e {$e['eventos']} eventos.");
    }
}
