<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Leonardo (leonardoaderito-cmyk): parceiros geridos na administracao (logotipo, categoria e ligacao)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parceiros', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('sigla', 30)->nullable();
            $table->string('categoria', 30)->default('nacional');
            $table->string('descricao', 400)->nullable();
            $table->string('logo')->nullable();
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Parceiros que ja estavam na pagina antiga, com os logotipos que existem em public/img/parceiros
        $agora = now();
        $ordem = 0;
        foreach ([
            ['Autoridade Tributária de Moçambique', 'AT', 'nacional', 'Entidade que tutela as alfândegas e o licenciamento da actividade de despacho aduaneiro.', '/img/parceiros/at.png', 'https://www.at.gov.mz/'],
            ['Alfândegas de Moçambique', 'DGA', 'nacional', 'Entidade aduaneira com quem a CDA coordena a actividade operacional.', null, null],
            ['Ministério da Economia e Finanças', 'MEF', 'nacional', 'Tutela da fiscalidade, do orçamento e da economia do Estado.', '/img/parceiros/mef.png', null],
            ['Ministério da Indústria e Comércio', 'MIC', 'nacional', 'Supervisão das políticas de comércio externo e interno.', '/img/parceiros/mic.png', 'https://www.mic.gov.mz/'],
            ['Banco de Moçambique', 'BM', 'nacional', 'Regulação e estabilidade macroeconómica e financeira.', '/img/parceiros/bancomoc.png', null],
            ['Agência para a Promoção de Investimentos e Exportações', 'APIEX', 'nacional', 'Promoção do investimento e das exportações de Moçambique.', '/img/parceiros/apiex.png', null],
            ['Instituto de Gestão de Zonas Económicas Especiais', 'IGEZE', 'nacional', 'Gestão das zonas económicas especiais e zonas francas.', null, null],
            ['Janela Única Electrónica', 'JUE', 'nacional', 'Plataforma única para o desembaraço electrónico de mercadorias.', null, 'https://jue.gov.mz/'],
            ['Confederação das Associações Económicas', 'CTA', 'sector_privado', 'Confederação do sector privado, de que a CDA é membro activo.', '/img/parceiros/cta.png', null],
            ['Câmara de Comércio de Moçambique', 'CCM', 'sector_privado', 'Representação do sector empresarial moçambicano.', null, null],
            ['ASAPRA', 'ASAPRA', 'internacional', 'Associação internacional de agentes profissionais de alfândega; a CDA é membro activo.', null, 'https://www.asapra.org/'],
            ['FIATA', 'FIATA', 'internacional', 'Federação Internacional de Associações de Transitários e Similares.', '/img/parceiros/fiata.svg', 'https://fiata.org/'],
            ['Organização Mundial das Alfândegas', 'OMA', 'internacional', 'Referência mundial em inovação e facilitação do comércio aduaneiro.', '/img/parceiros/wco.png', 'https://www.wcoomd.org/'],
            ['Legislação Aduaneira', null, 'ligacao', 'Legislação aduaneira publicada pela Autoridade Tributária.', null, 'https://www.at.gov.mz/legislacao'],
        ] as [$nome, $sigla, $categoria, $descricao, $logo, $url]) {
            DB::table('parceiros')->insert([
                'nome' => $nome, 'sigla' => $sigla, 'categoria' => $categoria, 'descricao' => $descricao,
                'logo' => $logo, 'url' => $url, 'ordem' => ++$ordem, 'activo' => true,
                'created_at' => $agora, 'updated_at' => $agora,
            ]);
        }

        // A pagina /parceiros passa a ser gerada a partir desta tabela: fica so a opcao de activar ou desactivar
        DB::table('paginas')->where('slug', 'parceiros')->update(['tipo' => 'sistema', 'titulo' => 'Parceiros', 'conteudo' => null, 'updated_at' => $agora]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parceiros');
        DB::table('paginas')->where('slug', 'parceiros')->update(['tipo' => 'conteudo']);
    }
};
