<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\MembroPublico;
use App\Models\Noticia;
use App\Models\User;
use App\Services\Assistente\Indice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Autor: Leonardo (leonardoaderito-cmyk) - Assistente CDA: motor proprio, pedidos frequentes, listas do Centro Documental e indice
class AssistenteTest extends TestCase
{
    use RefreshDatabase;

    private function circular(string $titulo, int $ano, string $status = 'vigente'): Documento
    {
        return Documento::create([
            'ficheiro' => 'teste-'.md5($titulo).'.pdf',
            'titulo' => $titulo,
            'tipo' => 'Circular',
            'categoria' => 'Circular',
            'entidade' => 'AT',
            'ano' => $ano,
            'status' => $status,
        ]);
    }

    public function test_encontra_documento_e_devolve_o_pdf(): void
    {
        $doc = $this->circular('Circular 35/AT/DGA/2020 — Cobrança de taxas de serviços aduaneiros', 2020);

        $resposta = $this->postJson('/assistente', ['pergunta' => 'Onde está a circular sobre cobrança de taxas?'])
            ->assertOk()
            ->assertJsonPath('modo', 'pesquisa');

        $this->assertStringContainsString('Cobrança de taxas', $resposta->json('resposta'));
        $pdfs = collect($resposta->json('fontes'))->pluck('pdf')->filter();
        $this->assertContains(route('documento.abrir', $doc), $pdfs->all());
        $this->assertDatabaseHas('assistente_perguntas', ['modo' => 'pesquisa']);
    }

    public function test_lista_documentos_por_tipo_e_ano_e_diz_quando_nao_existem(): void
    {
        $this->circular('Circular 11/AT/DGA/2023 — Levantamento de suspensão', 2023);
        $this->circular('Circular 12/AT/DGA/2023 — Exportação de madeira', 2023, 'revogado');
        $this->circular('Circular 01/AT/DGA/2021 — Prazos', 2021);

        $com = $this->postJson('/assistente', ['pergunta' => 'circulares de 2023'])->assertOk();
        $this->assertStringContainsString('Encontrei **2** documentos do tipo **Circular de 2023**', $com->json('resposta'));
        $fontes = collect($com->json('fontes'));
        $this->assertSame('lista', $fontes->first()['tipo']);
        $this->assertCount(2, $fontes->where('tipo', 'documento'));
        $this->assertTrue($fontes->firstWhere('titulo', 'Circular 12/AT/DGA/2023 — Exportação de madeira')['revogado']);

        $sem = $this->postJson('/assistente', ['pergunta' => 'circulares de 2024'])->assertOk();
        $this->assertStringContainsString('Não há documentos do tipo **Circular de 2024**', $sem->json('resposta'));
        $this->assertStringContainsString('2021, 2023', $sem->json('resposta'));

        // Seguimento: o ano da nova pergunta vence o da anterior
        $seguimento = $this->postJson('/assistente', [
            'pergunta' => 'e de 2021?',
            'historico' => [
                ['papel' => 'utilizador', 'texto' => 'circulares de 2023'],
                ['papel' => 'assistente', 'texto' => 'Encontrei 2 documentos.'],
            ],
        ])->assertOk();
        $this->assertStringContainsString('Circular de 2021', $seguimento->json('resposta'));
    }

    public function test_responde_com_a_frase_do_site_e_cita_a_fonte(): void
    {
        $noticia = Noticia::create([
            'titulo' => 'Seminário sobre valoração aduaneira',
            'slug' => 'seminario-valoracao',
            'data' => now(),
            'texto' => "A CDA realizou um seminário sobre valoração aduaneira em Nacala.\n\nO seminário teve oitenta participantes de todas as delegações.",
            'publicada' => true,
        ]);

        $resposta = $this->postJson('/assistente', ['pergunta' => 'Quantos participantes teve o seminário?'])
            ->assertOk()
            ->assertJsonPath('modo', 'resposta');

        $this->assertStringContainsString('O seminário teve oitenta participantes de todas as delegações. [1]', $resposta->json('resposta'));
        $this->assertSame(1, $resposta->json('fontes.0.n'));
        $this->assertSame(route('noticia', $noticia->slug), $resposta->json('fontes.0.url'));
    }

    public function test_corrige_erros_de_escrita(): void
    {
        $doc = $this->circular('Circular 02/2019 — Regime de trânsito aduaneiro', 2019);

        $resposta = $this->postJson('/assistente', ['pergunta' => 'trnsito'])->assertOk();

        $this->assertStringContainsString('Procurei por «transito»', $resposta->json('resposta'));
        $this->assertContains(route('documento.abrir', $doc), collect($resposta->json('fontes'))->pluck('pdf')->all());
    }

    public function test_cumprimentos_e_contactos_sem_pesquisa(): void
    {
        $this->postJson('/assistente', ['pergunta' => 'Olá, bom dia!'])
            ->assertOk()->assertJsonPath('modo', 'resposta')->assertJsonPath('fontes', [])
            ->assertJsonPath('resposta', fn ($r) => str_contains($r, 'Sou o Assistente CDA'));

        $this->postJson('/assistente', ['pergunta' => 'Obrigado'])
            ->assertOk()->assertJsonPath('resposta', fn ($r) => str_starts_with($r, 'De nada'));

        $this->postJson('/assistente', ['pergunta' => 'o que podes fazer?'])
            ->assertOk()->assertJsonPath('resposta', fn ($r) => str_contains($r, 'directório de membros'));

        $contactos = $this->postJson('/assistente', ['pergunta' => 'Qual é o telefone da CDA?'])->assertOk();
        $this->assertStringContainsString('+258 21 305 504', $contactos->json('resposta'));
        $this->assertStringNotContainsString('Morada', $contactos->json('resposta'));
        $this->assertSame(route('contactos'), $contactos->json('fontes.0.url'));

        $tudo = $this->postJson('/assistente', ['pergunta' => 'Contactos e horário'])->assertOk()->json('resposta');
        $this->assertStringContainsString('Morada', $tudo);
        $this->assertStringContainsString('8h às 16h', $tudo);
    }

    public function test_ultimas_noticias_e_directorio_de_membros(): void
    {
        Noticia::create(['titulo' => 'Notícia antiga', 'slug' => 'antiga', 'data' => now()->subYear(), 'texto' => 'Texto.', 'publicada' => true]);
        Noticia::create(['titulo' => 'Notícia de hoje', 'slug' => 'hoje', 'data' => now(), 'texto' => 'Texto.', 'publicada' => true]);
        Noticia::create(['titulo' => 'Rascunho', 'slug' => 'rascunho', 'data' => now(), 'texto' => 'Texto.', 'publicada' => false]);

        $noticias = $this->postJson('/assistente', ['pergunta' => 'últimas notícias'])->assertOk();
        $this->assertSame(['Notícia de hoje', 'Notícia antiga'], collect($noticias->json('fontes'))->pluck('titulo')->all());

        MembroPublico::create(['nome' => 'Ana Maria Cossa', 'n_carta' => '0457', 'empresa' => 'Cossa Despachos', 'situacao' => 'Activo', 'provincia' => 'Maputo', 'activo' => true]);
        MembroPublico::create(['nome' => 'Bento Mucavele', 'n_carta' => '0912', 'activo' => true]);

        $encontrado = $this->postJson('/assistente', ['pergunta' => 'A Ana Cossa está registada?'])->assertOk()->assertJsonPath('modo', 'resposta');
        $this->assertStringContainsString('**Ana Maria Cossa** — carteira n.º 0457', $encontrado->json('resposta'));
        $this->assertStringNotContainsString('Bento', $encontrado->json('resposta'));
        $this->assertSame(route('directorio', ['q' => 'ana cossa']), $encontrado->json('fontes.0.url'));

        $this->postJson('/assistente', ['pergunta' => 'O despachante Zacarias Inexistente está inscrito?'])
            ->assertOk()->assertJsonPath('modo', 'sem-resultados');

        // "Como ser despachante" nao e uma pesquisa no directorio
        $this->postJson('/assistente', ['pergunta' => 'Como ser despachante aduaneiro?'])
            ->assertOk()->assertJsonPath('resposta', fn ($r) => ! str_contains($r, 'directório'));
    }

    public function test_sem_resultados_sugere_contactos_e_valida_a_pergunta(): void
    {
        $this->postJson('/assistente', ['pergunta' => 'xyzzy plumbus'])
            ->assertOk()
            ->assertJsonPath('modo', 'sem-resultados')
            ->assertJsonPath('fontes', []);

        $this->postJson('/assistente', ['pergunta' => ''])->assertUnprocessable();
        $this->postJson('/assistente', ['pergunta' => str_repeat('a', 501)])->assertUnprocessable();
    }

    public function test_indice_acompanha_noticias_publicadas_e_apagadas(): void
    {
        $noticia = Noticia::create([
            'titulo' => 'Seminário sobre valoração aduaneira',
            'slug' => 'seminario-valoracao',
            'data' => now(),
            'texto' => 'A CDA realizou um seminário sobre valoração aduaneira em Nacala.',
            'publicada' => true,
        ]);
        $indice = app(Indice::class);
        $this->assertSame('noticia', $indice->procurar('valoração em Nacala')->first()['tipo']);

        $noticia->update(['publicada' => false]);
        $this->assertTrue($indice->procurar('valoração em Nacala')->where('tipo', 'noticia')->isEmpty());

        $noticia->update(['publicada' => true]);
        $noticia->delete();
        $this->assertSame(0, DB::table('assistente_trechos')->where('tipo', 'noticia')->count());
    }

    public function test_administracao_ve_o_estado_e_refaz_o_indice(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'super_admin']);
        $this->circular('Circular 03/2022 — Horários', 2022);
        $this->postJson('/assistente', ['pergunta' => 'pergunta sem resposta qwerty']);

        $this->actingAs($admin)->get('/admin/assistente')->assertOk()
            ->assertSee('Assistente CDA')->assertSee('sem serviços externos')->assertSee('pergunta sem resposta qwerty')->assertSee('Sem resposta');

        $this->actingAs($admin)->post('/admin/assistente/indexar')->assertRedirect()
            ->assertSessionHas('estado', fn ($m) => str_contains($m, '1 documentos'));

        // Membros sem permissao de conteudo nao entram
        $financeiro = User::factory()->create(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'financeiro']);
        $this->actingAs($financeiro)->get('/admin/assistente')->assertForbidden();
    }
}
