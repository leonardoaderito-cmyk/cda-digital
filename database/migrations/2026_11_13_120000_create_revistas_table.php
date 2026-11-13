<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Leonardo (leonardoaderito-cmyk): edicoes da revista O Despachante (capa que abre o PDF)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revistas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('edicao', 120)->nullable();
            $table->date('data_publicacao')->nullable();
            $table->text('descricao')->nullable();
            $table->string('capa')->nullable();
            $table->string('ficheiro');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revistas');
    }
};
