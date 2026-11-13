<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Leonardo (leonardoaderito-cmyk): contas com senha provisoria (p. ex. admin/admin do pacote partilhado) mudam-na ao entrar
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('trocar_senha')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('trocar_senha');
        });
    }
};
