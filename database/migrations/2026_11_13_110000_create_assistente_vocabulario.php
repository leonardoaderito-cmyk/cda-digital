<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Leonardo (leonardoaderito-cmyk): vocabulario do indice do Assistente CDA, para corrigir erros de escrita nas perguntas
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE VIRTUAL TABLE assistente_vocabulario USING fts5vocab(assistente_trechos, 'row')");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS assistente_vocabulario');
    }
};
