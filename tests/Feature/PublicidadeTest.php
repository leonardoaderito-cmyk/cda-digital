<?php

namespace Tests\Feature;

use App\Models\Imagem;
use App\Models\Pagina;
use App\Models\Publicidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Leonardo (leonardoaderito-cmyk): publicidade da pagina inicial e paginas institucionais redesenhadas
class PublicidadeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'super_admin'])->save();

        return $admin;
    }

    public function test_sem_anuncios_mostra_o_convite_para_anunciar(): void
    {
        $this->get('/')->assertOk()->assertSee('Anuncie no portal da CDA');
    }

    public function test_so_mostra_anuncios_activos_e_dentro_do_periodo(): void
    {
        Publicidade::create(['titulo' => 'Anúncio em curso', 'imagem' => '/img/cda-logo.jpg', 'url' => 'https://example.com', 'activo' => true]);
        Publicidade::create(['titulo' => 'Anúncio escondido', 'imagem' => '/img/cda-logo.jpg', 'activo' => false]);
        Publicidade::create(['titulo' => 'Anúncio futuro', 'imagem' => '/img/cda-logo.jpg', 'inicio' => today()->addWeek(), 'activo' => true]);
        Publicidade::create(['titulo' => 'Anúncio terminado', 'imagem' => '/img/cda-logo.jpg', 'fim' => today()->subDay(), 'activo' => true]);

        $this->get('/')->assertOk()
            ->assertSee('Anúncio em curso')
            ->assertSee('rel="noopener sponsored"', false)
            ->assertDontSee('Anúncio escondido')
            ->assertDontSee('Anúncio futuro')
            ->assertDontSee('Anúncio terminado')
            ->assertDontSee('Anuncie no portal da CDA');
    }

    public function test_admin_gere_anuncios(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/publicidade')->assertOk()->assertSee('Ainda não há anúncios');
        $this->actingAs($admin)->get('/admin/publicidade/novo')->assertOk();

        // Sem imagem nao ha anuncio
        $this->actingAs($admin)->post('/admin/publicidade', ['titulo' => 'Sem imagem', 'activo' => '1'])->assertSessionHasErrors('imagem_ficheiro');
        $this->actingAs($admin)->post('/admin/publicidade', ['titulo' => 'Datas trocadas', 'inicio' => '2026-12-10', 'fim' => '2026-12-01'])->assertSessionHasErrors('fim');

        $imagem = Imagem::create(['ficheiro' => 'anuncio.jpg', 'nome' => 'anuncio.jpg', 'alt' => 'Anúncio']);
        $this->actingAs($admin)->post('/admin/publicidade', [
            'titulo' => 'Formação aduaneira', 'anunciante' => 'Escola X', 'url' => 'https://example.com', 'imagem_biblioteca' => $imagem->id, 'activo' => '1',
        ])->assertRedirect();
        $anuncio = Publicidade::where('titulo', 'Formação aduaneira')->sole();
        $this->assertSame($imagem->url(), $anuncio->imagem);
        $this->get('/')->assertSee('Formação aduaneira');

        $this->actingAs($admin)->post("/admin/publicidade/{$anuncio->id}/alternar")->assertRedirect();
        $this->get('/')->assertDontSee('Formação aduaneira');

        $this->actingAs($admin)->delete("/admin/publicidade/{$anuncio->id}")->assertRedirect('/admin/publicidade');
        $this->assertDatabaseMissing('publicidades', ['id' => $anuncio->id]);

        $membro = User::factory()->create(['is_admin' => false]);
        $this->actingAs($membro)->get('/admin/publicidade')->assertForbidden();
    }

    public function test_paginas_institucionais_tem_desenho_proprio(): void
    {
        Pagina::where('slug', 'instituicao')->update(['conteudo' => file_get_contents(database_path('data/paginas/instituicao.html'))]);

        $this->get('/instituicao')->assertOk()
            ->assertSee('cabecalho-instituicao', false)
            ->assertSee('pagina-instituicao', false)
            ->assertSee('O percurso da CDA')
            ->assertSee('qs-cronologia', false);

        $this->get('/orgaos')->assertOk()->assertSee('pagina-orgaos', false);
    }
}
