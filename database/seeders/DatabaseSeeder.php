<?php

namespace Database\Seeders;

use App\Services\Assistente\Indice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MembrosPublicosSeeder::class);
        $this->call(ConteudoSeeder::class);

        // Leonardo (leonardoaderito-cmyk): indice do Assistente CDA com o conteudo importado
        $indice = app(Indice::class);
        if ($indice->disponivel()) {
            $e = $indice->reconstruir();
            $this->command?->info("Assistente CDA: {$e['trechos']} trechos indexados ({$e['documentos_com_texto']} de {$e['documentos']} PDFs com texto).");
        }
    }
}
