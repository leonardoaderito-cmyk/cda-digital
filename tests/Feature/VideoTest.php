<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

// Leonardo (leonardoaderito-cmyk): videos dos eventos na pagina inicial e na administracao
class VideoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Video::all() as $video) {
            $video->apagarFicheiro();
        }
        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'super_admin'])->save();

        return $admin;
    }

    public function test_reconhece_ligacoes_do_youtube(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=Nv8Wsc37jZw',
            'https://youtu.be/Nv8Wsc37jZw?si=abc',
            'https://www.youtube.com/shorts/Nv8Wsc37jZw',
            'https://www.youtube.com/watch?feature=share&v=Nv8Wsc37jZw',
            'Nv8Wsc37jZw',
        ] as $ligacao) {
            $this->assertSame('Nv8Wsc37jZw', Video::codigoYoutube($ligacao), $ligacao);
        }
        $this->assertNull(Video::codigoYoutube('https://vimeo.com/123'));
    }

    public function test_pagina_inicial_mostra_o_video_institucional(): void
    {
        // A migracao traz o video institucional da CDA
        $this->get('/')->assertOk()
            ->assertSee('Vídeos dos eventos')
            ->assertSee('O Despachante Aduaneiro')
            ->assertSee('https://www.youtube.com/watch?v=Nv8Wsc37jZw', false);
    }

    public function test_admin_gere_videos_do_youtube_e_ficheiros(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/videos')->assertOk()->assertSee('O Despachante Aduaneiro');
        $this->actingAs($admin)->get('/admin/videos/novo')->assertOk();

        $this->actingAs($admin)->post('/admin/videos', ['titulo' => 'Sem origem', 'tipo' => 'youtube', 'youtube' => 'https://vimeo.com/1'])->assertSessionHasErrors('youtube');
        $this->actingAs($admin)->post('/admin/videos', ['titulo' => 'Sem ficheiro', 'tipo' => 'ficheiro'])->assertSessionHasErrors('ficheiro');

        $this->actingAs($admin)->post('/admin/videos', [
            'titulo' => 'Dia do Despachante 2026', 'tipo' => 'youtube', 'youtube' => 'https://youtu.be/AbCdEfGhIjK', 'data' => '2026-09-14', 'activo' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('videos', ['titulo' => 'Dia do Despachante 2026', 'youtube_id' => 'AbCdEfGhIjK']);

        $this->actingAs($admin)->post('/admin/videos', [
            'titulo' => 'Assembleia Geral', 'tipo' => 'ficheiro', 'activo' => '1',
            'ficheiro' => UploadedFile::fake()->create('assembleia.mp4', 500, 'video/mp4'),
        ])->assertRedirect();
        $ficheiro = Video::where('titulo', 'Assembleia Geral')->sole();
        $this->assertFileExists(public_path(Video::PASTA.'/'.$ficheiro->ficheiro));

        // Os dois aparecem na lista da pagina inicial, com o leitor certo para cada um
        $this->get('/')->assertSee('data-src="AbCdEfGhIjK"', false)->assertSee($ficheiro->urlFicheiro(), false);

        $this->actingAs($admin)->post("/admin/videos/{$ficheiro->id}/alternar")->assertRedirect();
        $this->get('/')->assertDontSee('Assembleia Geral');

        $caminho = public_path(Video::PASTA.'/'.$ficheiro->ficheiro);
        $this->actingAs($admin)->delete("/admin/videos/{$ficheiro->id}")->assertRedirect('/admin/videos');
        $this->assertFileDoesNotExist($caminho);

        $membro = User::factory()->create(['is_admin' => false]);
        $this->actingAs($membro)->get('/admin/videos')->assertForbidden();
    }
}
