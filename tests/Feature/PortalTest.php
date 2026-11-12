<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\NewsletterSubscricao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Autor: Leonardo (leonardoaderito-cmyk)
class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginas_publicas_respondem(): void
    {
        foreach (['/', '/noticias', '/eventos', '/actividades', '/documentos', '/membros', '/contactos', '/entrar', '/registar', '/instituicao', '/historia', '/orgaos', '/estatutos', '/parceiros'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_area_de_administracao_bloqueia_nao_admin(): void
    {
        $this->get('/admin')->assertRedirect();

        $membro = User::factory()->create(['is_admin' => false]);
        $this->actingAs($membro)->get('/admin')->assertForbidden();
    }

    public function test_cabecalhos_e_auditoria(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'DENY');

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'ativo' => true])->save();
        $this->actingAs($admin)->post('/admin/noticias', ['titulo' => 'T', 'texto' => 'X', 'data' => '2026-01-01']);
        $this->assertDatabaseHas('auditoria', ['accao' => 'admin.noticias.guardar', 'user_id' => $admin->id]);
    }

    public function test_conta_inactiva_nao_entra(): void
    {
        $user = User::factory()->create(['email' => 'a@b.mz', 'password' => 'segredo123']);
        $user->forceFill(['ativo' => false])->save();

        $this->post('/entrar', ['identificador' => 'a@b.mz', 'password' => 'segredo123'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_visitante_pode_subscrever_newsletter_sem_duplicar_email(): void
    {
        $this->post('/newsletter', ['nome' => 'Ana', 'email' => 'ANA@example.com'])
            ->assertSessionHas('estado');
        $this->post('/newsletter', ['nome' => 'Ana Maria', 'email' => 'ana@example.com'])
            ->assertSessionHas('estado');

        $this->assertDatabaseCount('newsletter_subscricoes', 1);
        $this->assertDatabaseHas('newsletter_subscricoes', [
            'nome' => 'Ana Maria',
            'email' => 'ana@example.com',
            'activo' => true,
        ]);
    }

    public function test_admin_cria_evento_e_visitante_ve_pagina_publicada(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'ativo' => true])->save();

        $this->actingAs($admin)->post('/admin/eventos', [
            'titulo' => 'Conferência Aduaneira',
            'data_evento' => '2026-12-10',
            'local' => 'Maputo',
            'resumo' => 'Encontro anual do sector.',
            'activo' => '1',
            'blocos' => [
                ['tipo' => 'texto', 'conteudo' => 'Programa completo do encontro.'],
            ],
        ])->assertRedirect();

        $evento = Evento::firstOrFail();
        $this->get('/eventos/'.$evento->slug)
            ->assertOk()
            ->assertSee('Conferência Aduaneira')
            ->assertSee('Programa completo do encontro.');
    }

    public function test_admin_pode_gerir_newsletter(): void
    {
        NewsletterSubscricao::create(['email' => 'membro@cda.mz']);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'ativo' => true])->save();

        $this->actingAs($admin)->get('/admin/newsletter')->assertOk()->assertSee('membro@cda.mz');
        $this->actingAs($admin)->get('/admin/newsletter/exportar')->assertOk();
    }
}
