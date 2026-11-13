<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Leonardo (leonardoaderito-cmyk): anuncios que rodam na pagina inicial, ao lado dos videos
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicidades', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('anunciante')->nullable();
            $table->string('imagem');
            $table->string('url', 500)->nullable();
            $table->date('inicio')->nullable();
            $table->date('fim')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicidades');
    }
};
