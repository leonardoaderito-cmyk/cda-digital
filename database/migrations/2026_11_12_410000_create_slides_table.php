<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Autor: Leonardo (leonardoaderito-cmyk) - slides do carrossel da pagina inicial
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('etiqueta', 60)->nullable();
            $table->string('texto', 400)->nullable();
            $table->string('imagem')->nullable();
            $table->string('botao_texto', 60)->nullable();
            $table->string('botao_url', 500)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
