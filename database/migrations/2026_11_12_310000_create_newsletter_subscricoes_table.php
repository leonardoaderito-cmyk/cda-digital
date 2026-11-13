<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Autor: Leonardo (leonardoaderito-cmyk) - lista de distribuição da newsletter da CDA
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscricoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 160)->nullable();
            $table->string('email', 200)->unique();
            $table->boolean('activo')->default(true);
            $table->string('origem', 40)->default('portal');
            $table->string('ip', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscricoes');
    }
};
