<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Leonardo (leonardoaderito-cmyk): o ficheiro do logotipo da CTA vindo do site antigo e um recorte que corta o desenho.
// Ate ser carregado o logotipo completo (Administracao > Parceiros), a CTA aparece com a placa da sigla.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('parceiros')->where('logo', '/img/parceiros/cta.png')->update(['logo' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nada a repor: o logotipo cortado nao deve voltar
    }
};
