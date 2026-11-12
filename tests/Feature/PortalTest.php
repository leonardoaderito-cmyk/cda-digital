<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Imagem;
use App\Models\MembroPublico;
use App\Models\NewsletterSubscricao;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Models\Quota;
use App\Models\Slide;
use App\Models\User;
use App\Notifications\CredenciaisIniciais;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
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
        $admin->forceFill(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'super_admin'])->save();
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
        $admin->forceFill(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'super_admin'])->save();

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

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'super_admin'])->save();

        return $admin;
    }

    public function test_ecras_da_administracao_respondem(): void
    {
        $admin = $this->admin();
        foreach (['/admin', '/admin/utilizadores', '/admin/paginas', '/admin/paginas/nova', '/admin/noticias', '/admin/noticias/nova', '/admin/eventos', '/admin/imagens', '/admin/documentos', '/admin/membros', '/admin/quotas', '/admin/requerimentos', '/admin/mensagens', '/admin/newsletter'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        foreach (Pagina::all() as $pagina) {
            $this->actingAs($admin)->get('/admin/paginas/'.$pagina->id.'/editar')->assertOk();
        }
    }

    public function test_admin_desactiva_seccao_e_pagina(): void
    {
        $admin = $this->admin();
        $this->get('/')->assertSee('href="'.route('eventos').'"', false);

        $this->actingAs($admin)->post('/admin/paginas/'.Pagina::where('slug', 'eventos')->value('id').'/alternar')->assertRedirect();
        $this->actingAs($admin)->post('/admin/paginas/'.Pagina::where('slug', 'historia')->value('id').'/alternar')->assertRedirect();

        $this->get('/eventos')->assertNotFound();
        $this->get('/historia')->assertNotFound();
        $this->get('/')->assertOk()
            ->assertDontSee('href="'.route('eventos').'"', false)
            ->assertDontSee('href="'.route('historia').'"', false);

        $this->actingAs($admin)->post('/admin/paginas/'.Pagina::where('slug', 'eventos')->value('id').'/alternar');
        $this->get('/eventos')->assertOk();
    }

    public function test_admin_edita_e_cria_paginas_com_html_limpo(): void
    {
        $admin = $this->admin();
        $instituicao = Pagina::where('slug', 'instituicao')->firstOrFail();

        $this->actingAs($admin)->post('/admin/paginas/'.$instituicao->id, [
            'titulo' => 'A Câmara',
            'resumo' => 'Resumo novo.',
            'conteudo' => '<h2>Texto editado</h2><p onclick="alert(1)">Olá</p><script>alert(2)</script><a href="javascript:alert(3)">x</a>',
            'activa' => '1',
        ])->assertRedirect();

        $this->get('/instituicao')->assertOk()
            ->assertSee('A Câmara')->assertSee('Texto editado')
            ->assertDontSee('alert(', false)->assertDontSee('onclick', false);

        $this->actingAs($admin)->post('/admin/paginas', ['titulo' => 'Código de Ética', 'conteudo' => '<p>Deveres do despachante.</p>', 'activa' => '1', 'no_menu' => '1'])->assertRedirect();
        $this->get('/pagina/codigo-de-etica')->assertOk()->assertSee('Deveres do despachante.');
        $this->get('/')->assertSee('/pagina/codigo-de-etica', false);

        // Uma seccao do portal nao pode ser removida, so desactivada
        $this->actingAs($admin)->delete('/admin/paginas/'.Pagina::where('slug', 'noticias')->value('id'))->assertForbidden();
    }

    public function test_admin_carrega_imagem_e_usa_a_numa_noticia(): void
    {
        $admin = $this->admin();
        $pasta = public_path(Imagem::PASTA);

        $this->actingAs($admin)->post('/admin/imagens', ['ficheiros' => [UploadedFile::fake()->image('Sede da CDA.png', 640, 480)]])->assertRedirect();
        $imagem = Imagem::firstOrFail();
        $this->assertFileExists(public_path($imagem->ficheiro));
        $this->assertSame(640, $imagem->largura);

        $this->actingAs($admin)->post('/admin/noticias', ['titulo' => 'Nova sede', 'texto' => 'Texto.', 'data' => '2026-02-01', 'imagem_biblioteca' => $imagem->id])->assertRedirect();
        $this->get('/')->assertSee($imagem->url(), false);

        $this->actingAs($admin)->post('/admin/imagens', ['ficheiros' => [UploadedFile::fake()->create('virus.php', 10)]])->assertSessionHasErrors();

        $this->actingAs($admin)->delete('/admin/imagens/'.$imagem->id)->assertRedirect();
        $this->assertFileDoesNotExist($pasta.'/'.basename($imagem->ficheiro));
    }

    public function test_admin_configura_o_carrossel(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/carrossel')->assertOk();
        $this->actingAs($admin)->get('/admin/carrossel/novo')->assertOk();

        $this->actingAs($admin)->post('/admin/carrossel', ['titulo' => 'Bem-vindo à CDA', 'texto' => 'Portal oficial.', 'botao_texto' => 'Conhecer', 'botao_url' => '/instituicao', 'activo' => '1'])->assertRedirect();
        $this->actingAs($admin)->post('/admin/carrossel', ['titulo' => 'Quotas 2026', 'activo' => '1'])->assertRedirect();
        $this->actingAs($admin)->post('/admin/carrossel', ['titulo' => 'Perigoso', 'botao_url' => 'javascript:alert(1)'])->assertSessionHasErrors('botao_url');

        [$primeiro, $segundo] = Slide::orderBy('ordem')->get()->all();
        $this->actingAs($admin)->get('/admin/carrossel/'.$primeiro->id.'/editar')->assertOk();
        $this->get('/')->assertSeeInOrder(['Bem-vindo à CDA', 'Quotas 2026'])->assertSee('href="/instituicao"', false);

        $this->actingAs($admin)->post('/admin/carrossel/'.$segundo->id.'/mover', ['sentido' => 'subir'])->assertRedirect();
        $this->get('/')->assertSeeInOrder(['Quotas 2026', 'Bem-vindo à CDA']);

        $this->actingAs($admin)->post('/admin/carrossel/'.$primeiro->id.'/alternar');
        $this->get('/')->assertDontSee('Bem-vindo à CDA')->assertSee('Quotas 2026');
    }

    public function test_carrossel_sem_slides_mostra_noticias_e_slide_pode_vir_de_noticia(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/noticias', ['titulo' => 'Assembleia Geral marcada', 'texto' => 'Convocatória publicada.', 'data' => '2026-03-01']);
        $this->get('/')->assertSee('carrossel', false)->assertSee('Ler mais');

        $this->actingAs($admin)->post('/admin/carrossel/da-noticia', ['noticia' => Noticia::firstOrFail()->id])->assertRedirect();
        $this->assertDatabaseHas('slides', ['titulo' => 'Assembleia Geral marcada', 'botao_url' => '/noticias/assembleia-geral-marcada']);
    }

    public function test_aviso_e_politica_de_cookies(): void
    {
        $this->get('/')->assertSee('data-cookies', false)->assertSee('Só essenciais');
        $this->get('/cookies')->assertOk()->assertSee('Política de cookies');
    }

    public function test_admin_pode_gerir_newsletter(): void
    {
        NewsletterSubscricao::create(['email' => 'membro@cda.mz']);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'super_admin'])->save();

        $this->actingAs($admin)->get('/admin/newsletter')->assertOk()->assertSee('membro@cda.mz');
        $this->actingAs($admin)->get('/admin/newsletter/exportar')->assertOk();
    }

    public function test_admin_cria_conta_e_envia_primeiras_credenciais(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $registo = MembroPublico::create([
            'nome' => 'Amélia Matola',
            'n_carta' => '123456',
            'empresa' => 'Matola Despachos',
            'activo' => true,
        ]);

        $this->actingAs($admin)->post('/admin/membros', [
            'membro_publico_id' => $registo->id,
            'email' => 'amelia@example.com',
            'telefone' => '841234567',
        ])->assertRedirect()->assertSessionHas('estado');

        $membro = User::where('carteira', '123456')->firstOrFail();
        $this->assertTrue($membro->ativo);
        $this->assertNotNull($membro->credenciais_enviadas_em);
        $this->assertDatabaseHas('quotas', ['user_id' => $membro->id, 'ano' => now()->year]);
        Notification::assertSentTo($membro, CredenciaisIniciais::class);
    }

    public function test_membro_define_senha_e_entra_com_numero(): void
    {
        $membro = User::factory()->create([
            'email' => 'membro@example.com',
            'carteira' => '987654',
            'ativo' => true,
        ]);
        $token = Password::broker()->createToken($membro);

        $this->post('/definir-senha', [
            'token' => $token,
            'email' => $membro->email,
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ])->assertRedirect(route('login'));

        $membro->refresh();
        $this->assertTrue(Hash::check('NovaSenha123', $membro->password));
        $this->assertNotNull($membro->senha_definida_em);
        $this->post('/entrar', ['identificador' => '987654', 'password' => 'NovaSenha123'])
            ->assertRedirect(route('membro.area'));
    }

    public function test_admin_regista_pagamento_e_membro_emite_comprovativo(): void
    {
        $admin = $this->admin();
        $membro = User::factory()->create(['carteira' => '456789', 'ativo' => true]);

        $this->actingAs($admin)->post('/admin/quotas', [
            'user_id' => $membro->id,
            'ano' => 2026,
            'valor' => 7500,
            'estado' => 'paga',
            'referencia' => 'REC-2026-001',
            'data_pagamento' => '2026-11-12',
        ])->assertRedirect();

        $quota = Quota::where('user_id', $membro->id)->firstOrFail();
        $pendente = Quota::create(['user_id' => $membro->id, 'ano' => 2025, 'valor' => 5000, 'estado' => 'pendente']);
        $this->actingAs($admin)->post('/admin/quotas/'.$pendente->id, [
            'estado' => 'paga',
            'valor' => 5000,
            'referencia' => 'REC-2025-001',
            'data_pagamento' => '',
        ])->assertRedirect();
        $this->assertNotNull($pendente->refresh()->data_pagamento);

        $this->actingAs($membro)->get('/membro')->assertOk()->assertSee('Regularizada')->assertSee('7.500 MT');
        $this->actingAs($membro)->get('/membro/quotas/'.$quota->id.'/comprovativo')
            ->assertOk()->assertSee('REC-2026-001')->assertSee('CDA-Q');
    }

    public function test_niveis_de_acesso_separam_adesoes_financas_e_conteudos(): void
    {
        $editor = User::factory()->create(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'editor']);
        $adesoes = User::factory()->create(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'adesoes']);
        $financeiro = User::factory()->create(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'financeiro']);
        $standard = User::factory()->create(['is_admin' => true, 'ativo' => true, 'nivel_acesso' => 'standard']);

        $this->actingAs($editor)->get('/admin/paginas')->assertOk();
        $this->actingAs($editor)->get('/admin/membros')->assertForbidden();
        $this->actingAs($adesoes)->get('/admin/membros')->assertOk();
        $this->actingAs($adesoes)->get('/admin/quotas')->assertForbidden();
        $this->actingAs($financeiro)->get('/admin/quotas')->assertOk();
        $this->actingAs($financeiro)->get('/admin/paginas')->assertForbidden();
        $this->actingAs($standard)->get('/admin/membros')->assertOk();
        $this->actingAs($standard)->get('/admin/quotas')->assertOk();
        $this->actingAs($standard)->post('/admin/quotas', [])->assertForbidden();
        $this->actingAs($standard)->get('/admin/utilizadores')->assertForbidden();
    }

    public function test_declaracao_pdf_e_qr_reflectem_regularidade_actual(): void
    {
        $membro = User::factory()->create(['carteira' => '777888', 'ativo' => true]);
        $quota = Quota::create([
            'user_id' => $membro->id,
            'ano' => now()->year,
            'valor' => 7500,
            'estado' => 'paga',
            'referencia' => 'Q-REGULAR',
            'data_pagamento' => now(),
        ]);

        $this->actingAs($membro)->get('/membro/declaracao-quitacao')
            ->assertOk()->assertSee('Declaração de quitação')->assertSee('data:image/png;base64,', false);
        $pdf = $this->actingAs($membro)->get('/membro/declaracao-quitacao/descarregar');
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->actingAs($membro)->get('/membro/quotas/'.$quota->id.'/comprovativo/descarregar')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $url = URL::signedRoute('regularidade.verificar', ['user' => $membro->id]);
        $this->get($url)->assertOk()->assertSee('Situação regularizada');

        $quota->update(['estado' => 'em_atraso', 'data_pagamento' => null]);
        $this->get($url)->assertOk()->assertSee('Situação não regularizada');
        $this->actingAs($membro)->get('/membro/declaracao-quitacao')->assertForbidden();
    }
}
