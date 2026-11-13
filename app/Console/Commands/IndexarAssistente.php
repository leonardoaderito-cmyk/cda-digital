<?php

namespace App\Console\Commands;

use App\Services\Assistente\Indice;
use Illuminate\Console\Command;

// Leonardo (leonardoaderito-cmyk): refaz o indice de pesquisa do Assistente CDA
class IndexarAssistente extends Command
{
    protected $signature = 'cda:assistente-indexar';

    protected $description = 'Refaz o índice do Assistente CDA (documentos e texto dos PDFs, páginas, notícias e eventos)';

    public function handle(Indice $indice): int
    {
        if (! $indice->disponivel()) {
            $this->error('O índice do assistente não existe. Corra primeiro: php artisan migrate');

            return self::FAILURE;
        }

        $this->line('A ler os conteúdos e o texto dos PDFs (a primeira vez pode demorar alguns minutos)...');
        $e = $indice->reconstruir();
        $this->info("Índice pronto: {$e['trechos']} trechos.");
        $this->table(['Conteúdo', 'Quantidade'], [
            ['Documentos', $e['documentos']],
            ['  com texto pesquisável', $e['documentos_com_texto']],
            ['  só pela ficha (PDF digitalizado)', $e['documentos'] - $e['documentos_com_texto']],
            ['Páginas', $e['paginas']],
            ['Notícias', $e['noticias']],
            ['Eventos', $e['eventos']],
        ]);

        return self::SUCCESS;
    }
}
