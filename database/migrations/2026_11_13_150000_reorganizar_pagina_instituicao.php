<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Leonardo (leonardoaderito-cmyk): pagina "A Instituicao" reorganizada (apresentacao, numeros, cronologia, orgaos e delegacoes)
return new class extends Migration
{
    public function up(): void
    {
        $pagina = DB::table('paginas')->where('slug', 'instituicao')->first();
        $novo = database_path('data/paginas/instituicao.html');
        if (! $pagina || ! is_file($novo)) {
            return;
        }

        // So substitui o texto de origem; um texto ja reescrito na administracao fica como esta
        $actual = (string) $pagina->conteudo;
        $deOrigem = trim($actual) === '' || str_contains($actual, '<h2>Quem somos</h2>') || str_contains($actual, '<span>Quem somos</span></h3>');
        if (! $deOrigem || str_contains($actual, 'qs-apresentacao')) {
            return;
        }

        DB::table('paginas')->where('id', $pagina->id)->update([
            'resumo' => 'Quem somos, missão, valores, cronologia, órgãos sociais e delegações da Câmara dos Despachantes Aduaneiros de Moçambique.',
            'conteudo' => trim(file_get_contents($novo)),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // O texto anterior nao e reposto: a pagina continua editavel na administracao
    }
};
