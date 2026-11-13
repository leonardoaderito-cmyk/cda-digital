<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Imagem;
use App\Models\Revista;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

// Leonardo (leonardoaderito-cmyk): revista (capa que abre o PDF) e seccoes novas da pagina inicial
class RevistaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Revista::all() as $revista) {
            $revista->apagarPdf();
        }
        foreach (Imagem::all() as $imagem) {
            $imagem->apagarFicheiro();
        }
        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'super_admin'])->save();

        return $admin;
    }

    public function test_admin_publica_revista_e_a_capa_abre_o_pdf(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/revista')->assertOk()->assertSee('Ainda não há edições');
        $this->actingAs($admin)->get('/admin/revista/nova')->assertOk();

        // Sem PDF nao se publica
        $this->actingAs($admin)->post('/admin/revista', ['titulo' => 'O Despachante'])->assertSessionHasErrors('pdf');

        $this->actingAs($admin)->post('/admin/revista', [
            'titulo' => 'O Despachante',
            'edicao' => 'Edição Julho 2026',
            'data_publicacao' => '2026-07-15',
            'descricao' => 'Entrevistas e cooperação institucional.',
            'pdf' => UploadedFile::fake()->create('revista.pdf', 300, 'application/pdf'),
            'imagem_ficheiro' => UploadedFile::fake()->image('capa.jpg', 600, 800),
            'activa' => '1',
        ])->assertRedirect();

        $revista = Revista::sole();
        $this->assertFileExists($revista->caminho());
        $this->assertStringStartsWith('/img/media/', $revista->capa);

        // A pagina da revista e a pagina inicial mostram a capa com ligacao para o PDF
        $abrir = route('revista.abrir', $revista);
        $this->get('/revista')->assertOk()->assertSee('Edição Julho 2026')->assertSee($abrir, false)->assertSee($revista->capa, false);
        $this->get('/')->assertOk()->assertSee('O Despachante')->assertSee($abrir, false);

        $resposta = $this->get($abrir)->assertOk();
        $this->assertSame('application/pdf', $resposta->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline', $resposta->headers->get('Content-Disposition'));

        // Escondida: some do site e o PDF deixa de abrir para visitantes (o administrador ainda o ve)
        $this->actingAs($admin)->post("/admin/revista/{$revista->id}/alternar")->assertRedirect();
        $this->assertFalse($revista->fresh()->activa);
        auth()->logout();
        $this->get('/revista')->assertOk()->assertDontSee('Edição Julho 2026');
        $this->get($abrir)->assertNotFound();
        $this->actingAs($admin)->get($abrir)->assertOk();

        // Remover apaga tambem o PDF
        $caminho = $revista->caminho();
        $this->actingAs($admin)->delete("/admin/revista/{$revista->id}")->assertRedirect('/admin/revista');
        $this->assertDatabaseCount('revistas', 0);
        $this->assertFileDoesNotExist($caminho);
    }

    public function test_revista_so_aceita_pdf_e_membros_nao_gerem(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/revista', [
            'titulo' => 'O Despachante',
            'pdf' => UploadedFile::fake()->create('revista.docx', 100),
        ])->assertSessionHasErrors('pdf');

        $membro = User::factory()->create(['is_admin' => false]);
        $this->actingAs($membro)->get('/admin/revista')->assertForbidden();
    }

    public function test_pagina_inicial_mostra_acervo_por_grupos_e_filtra_documentos(): void
    {
        foreach ([['Lei n.º 1', 'Lei'], ['Decreto 2', 'Decreto'], ['Circular 3', 'Circular'], ['Ordem 4', 'Ordem de Serviço']] as [$titulo, $tipo]) {
            Documento::create(['ficheiro' => Str::slug($titulo).'.pdf', 'titulo' => $titulo, 'tipo' => $tipo, 'categoria' => $tipo, 'ano' => 2025]);
        }

        $this->get('/')->assertOk()
            ->assertSee('O acervo da CDA')
            ->assertSee('data-contador="2"', false) // Legislacao: Lei + Decreto
            ->assertSee(route('documentos', ['grupo' => 'legislacao']), false)
            ->assertSee('Publicações recentes')
            ->assertSee('O que precisa, num clique')
            ->assertDontSee('Receba as novidades da CDA');

        $this->get('/documentos?grupo=legislacao')->assertOk()
            ->assertSee('Lei n.º 1')->assertSee('Decreto 2')
            ->assertDontSee('Circular 3')->assertDontSee('Ordem 4');

        // Um grupo desconhecido nao filtra nada
        $this->get('/documentos?grupo=nada')->assertOk()->assertSee('Circular 3');
    }
}
