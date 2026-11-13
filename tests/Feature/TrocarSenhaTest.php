<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Leonardo (leonardoaderito-cmyk): conta inicial admin/admin do alojamento partilhado
class TrocarSenhaTest extends TestCase
{
    use RefreshDatabase;

    public function test_conta_inicial_entra_e_tem_de_trocar_a_senha(): void
    {
        $this->artisan('cda:admin-inicial')->assertSuccessful();
        $this->artisan('cda:admin-inicial')->expectsOutputToContain('ja existe')->assertSuccessful();
        $conta = User::where('email', 'admin')->sole();
        $this->assertTrue($conta->is_admin);
        $this->assertTrue($conta->trocar_senha);

        $this->post('/entrar', ['identificador' => 'admin', 'password' => 'admin'])->assertRedirect(route('admin.painel'));
        $this->assertAuthenticatedAs($conta);

        // Enquanto nao trocar a senha, todo o portal leva a pagina da nova senha
        $this->get('/admin')->assertRedirect(route('senha.trocar'));
        $this->get('/membro')->assertRedirect(route('senha.trocar'));
        $this->get(route('senha.trocar'))->assertOk()->assertSee('O seu email');

        $this->post(route('senha.trocar.guardar'), ['email' => 'nao-e-email', 'password' => 'admin', 'password_confirmation' => 'admin'])
            ->assertSessionHasErrors(['email', 'password']);

        $this->post(route('senha.trocar.guardar'), [
            'email' => 'administracao@exemplo.co.mz', 'password' => 'Nova-senha-2026', 'password_confirmation' => 'Nova-senha-2026',
        ])->assertRedirect(route('admin.painel'));

        $conta->refresh();
        $this->assertFalse($conta->trocar_senha);
        $this->assertSame('administracao@exemplo.co.mz', $conta->email);
        $this->assertTrue(Hash::check('Nova-senha-2026', $conta->password));
        $this->get('/admin')->assertOk();
    }

    public function test_contas_normais_nao_sao_afectadas(): void
    {
        $membro = User::factory()->create(['is_admin' => false]);
        $membro->forceFill(['ativo' => true])->save();

        $this->actingAs($membro)->get('/membro')->assertOk();
        $this->actingAs($membro)->get(route('senha.trocar'))->assertRedirect(route('membro.area'));
    }
}
