<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Autor: Leonardo (leonardoaderito-cmyk) - talao de pagamento enviado pelo membro e instrucoes de pagamento
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotas', function (Blueprint $table) {
            $table->string('talao')->nullable();
            // em_validacao | rejeitado | validado
            $table->string('talao_estado', 20)->nullable()->index();
            $table->string('talao_referencia', 120)->nullable();
            $table->date('talao_data')->nullable();
            $table->string('talao_nota', 300)->nullable();
            $table->timestamp('talao_enviado_em')->nullable();
        });

        if (! DB::table('paginas')->where('slug', 'pagamento-de-quotas')->exists()) {
            DB::table('paginas')->insert([
                'slug' => 'pagamento-de-quotas',
                'titulo' => 'Pagamento de quotas',
                'resumo' => 'Como pagar a quota anual da CDA.',
                'conteudo' => '<p>A CDA ainda não publicou aqui os dados para pagamento. Contacte a secretaria para obter as contas bancárias e as referências a utilizar.</p><p>Depois de pagar, envie o talão na Área do Membro, em <strong>Quotas e pagamentos</strong>, para a secretaria validar.</p>',
                'tipo' => 'conteudo',
                'activa' => true,
                'no_menu' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('quotas', function (Blueprint $table) {
            $table->dropColumn(['talao', 'talao_estado', 'talao_referencia', 'talao_data', 'talao_nota', 'talao_enviado_em']);
        });
        DB::table('paginas')->where('slug', 'pagamento-de-quotas')->delete();
    }
};
