<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

// Leonardo (leonardoaderito-cmyk): troca obrigatoria da senha provisoria no primeiro acesso
class TrocarSenhaController extends Controller
{
    public function editar(Request $request)
    {
        if (! $request->user()->trocar_senha) {
            return redirect()->route($request->user()->is_admin ? 'admin.painel' : 'membro.area');
        }

        return view('auth.trocar-senha', ['utilizador' => $request->user()]);
    }

    public function actualizar(Request $request): RedirectResponse
    {
        $utilizador = $request->user();
        $regras = [
            'password' => ['required', 'confirmed', Password::min(8), 'not_in:admin,password,12345678'],
        ];
        // A conta "admin" do pacote nao tem email: aproveita-se para o indicar
        if (! filter_var($utilizador->email, FILTER_VALIDATE_EMAIL)) {
            $regras['email'] = ['required', 'email', 'max:200', 'unique:users,email,'.$utilizador->id];
        }
        $dados = $request->validate($regras, [
            'password.not_in' => 'Escolha uma senha diferente da provisória.',
            'email.unique' => 'Já existe uma conta com este email.',
        ]);

        if (Hash::check($dados['password'], $utilizador->password)) {
            return back()->withErrors(['password' => 'A nova senha tem de ser diferente da actual.']);
        }

        $utilizador->forceFill(array_filter([
            'email' => isset($dados['email']) ? strtolower($dados['email']) : null,
            'password' => Hash::make($dados['password']),
            'senha_definida_em' => now(),
        ]) + ['trocar_senha' => false])->save();
        $request->session()->regenerate();
        Auditoria::registar('senha.provisoria.trocada', null, $utilizador->id);

        return redirect()->route($utilizador->is_admin ? 'admin.painel' : 'membro.area')->with('estado', 'Senha definida. Já pode usar o portal.');
    }
}
