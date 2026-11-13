<?php

namespace Tests\Feature;

use App\Models\Imagem;
use App\Models\Parceiro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

// Leonardo (leonardoaderito-cmyk): parceiros geridos na administracao e mostrados no site
class ParceiroTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
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

    public function test_pagina_mostra_parceiros_iniciais_por_categoria(): void
    {
        // A migracao traz os parceiros da pagina antiga, com os logotipos existentes
        $this->assertTrue(Parceiro::where('sigla', 'AT')->where('logo', '/img/parceiros/at.png')->exists());

        $this->get('/parceiros')->assertOk()
            ->assertSeeInOrder(['Instituições e autoridades nacionais', 'Sector privado', 'Organizações internacionais'])
            ->assertSee('/img/parceiros/at.png', false)
            ->assertSee('parceiro-monograma', false) // parceiros sem logotipo mostram a sigla
            ->assertSee('https://www.at.gov.mz/', false);

        // Na pagina inicial so aparecem os que tem logotipo
        $this->get('/')->assertOk()->assertSee('/img/parceiros/wco.png', false)->assertDontSee('>IGEZE<', false);
    }

    public function test_admin_adiciona_ordena_e_esconde_parceiros(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/parceiros')->assertOk()->assertSee('Autoridade Tributária de Moçambique');
        $this->actingAs($admin)->get('/admin/parceiros/novo')->assertOk();

        $this->actingAs($admin)->post('/admin/parceiros', ['nome' => 'Porto de Maputo', 'categoria' => 'inexistente'])->assertSessionHasErrors('categoria');
        $this->actingAs($admin)->post('/admin/parceiros', ['nome' => 'Porto de Maputo', 'categoria' => 'nacional', 'url' => 'porto'])->assertSessionHasErrors('url');

        $this->actingAs($admin)->post('/admin/parceiros', [
            'nome' => 'Porto de Maputo',
            'sigla' => 'MPDC',
            'categoria' => 'sector_privado',
            'descricao' => 'Operador portuário.',
            'url' => 'https://www.portmaputo.com/',
            'imagem_ficheiro' => UploadedFile::fake()->image('porto.png', 400, 160),
            'activo' => '1',
        ])->assertRedirect();

        $porto = Parceiro::where('nome', 'Porto de Maputo')->sole();
        $this->assertStringStartsWith('/img/media/', $porto->logo);
        $this->get('/parceiros')->assertSee('Porto de Maputo')->assertSee($porto->logo, false);

        // Sobe uma posicao dentro da categoria (fica antes da CCM)
        $this->actingAs($admin)->post("/admin/parceiros/{$porto->id}/mover", ['sentido' => 'subir'])->assertRedirect();
        $ordem = Parceiro::where('categoria', 'sector_privado')->orderBy('ordem')->pluck('nome')->all();
        $this->assertSame(['Confederação das Associações Económicas', 'Porto de Maputo', 'Câmara de Comércio de Moçambique'], $ordem);

        $this->actingAs($admin)->post("/admin/parceiros/{$porto->id}/alternar")->assertRedirect();
        $this->get('/parceiros')->assertDontSee('Porto de Maputo');

        $this->actingAs($admin)->delete("/admin/parceiros/{$porto->id}")->assertRedirect('/admin/parceiros');
        $this->assertModelMissing($porto);

        $membro = User::factory()->create(['is_admin' => false]);
        $this->actingAs($membro)->get('/admin/parceiros')->assertForbidden();
    }
}
