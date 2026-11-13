<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Leonardo (leonardoaderito-cmyk): indice de pesquisa do Assistente CDA (SQLite FTS5) e registo das perguntas
return new class extends Migration
{
    public function up(): void
    {
        // Um trecho por linha; titulo e texto sao pesquisaveis, o resto so acompanha o resultado
        DB::statement(<<<'SQL'
            CREATE VIRTUAL TABLE assistente_trechos USING fts5(
                titulo, texto,
                tipo UNINDEXED, origem_id UNINDEXED, url UNINDEXED, extra UNINDEXED,
                tokenize = "unicode61 remove_diacritics 2"
            )
        SQL);

        Schema::create('assistente_perguntas', function (Blueprint $table) {
            $table->id();
            $table->string('pergunta', 500);
            $table->string('modo', 20);
            $table->unsignedSmallInteger('fontes')->default(0);
            $table->unsignedInteger('duracao_ms')->default(0);
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistente_perguntas');
        DB::statement('DROP TABLE IF EXISTS assistente_trechos');
    }
};
