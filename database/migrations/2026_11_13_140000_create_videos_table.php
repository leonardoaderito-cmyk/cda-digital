<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Leonardo (leonardoaderito-cmyk): videos dos eventos da CDA que rodam na pagina inicial
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('descricao', 400)->nullable();
            $table->string('tipo', 20)->default('ficheiro'); // ficheiro (carregado no portal) ou youtube
            $table->string('ficheiro')->nullable();
            $table->string('youtube_id', 20)->nullable();
            $table->string('capa')->nullable();
            $table->date('data')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Primeiro video: o video institucional do canal oficial da CDA no YouTube
        DB::table('videos')->insert([
            'titulo' => 'O Despachante Aduaneiro',
            'descricao' => 'Vídeo institucional da Câmara dos Despachantes Aduaneiros de Moçambique.',
            'tipo' => 'youtube',
            'youtube_id' => 'Nv8Wsc37jZw',
            'ordem' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
