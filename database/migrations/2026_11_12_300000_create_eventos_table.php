<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Autor: Leonardo (leonardoaderito-cmyk) - eventos da CDA com páginas desenhadas por blocos
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->date('data_evento')->nullable();
            $table->string('local')->nullable();
            $table->text('resumo')->nullable();
            $table->string('imagem')->nullable();
            $table->json('blocos')->nullable();
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
