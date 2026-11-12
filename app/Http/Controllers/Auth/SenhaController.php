<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RegraPassword;

class SenhaController extends Controller
{
    public function pedir()
    {
        return view('auth.recuperar-senha');
    }

    public function enviar(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('estado', 'Se existir uma conta com esse email, receberá uma ligação para definir uma nova senha.');
    }

    public function editar(Request $request, string $token)
    {
        return view('auth.definir-senha', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function actualizar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', RegraPassword::min(8)],
        ]);

        $estado = Password::reset($dados, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'senha_definida_em' => now(),
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($user));
        });

        if ($estado !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'A ligação expirou ou não é válida. Solicite uma nova.',
            ]);
        }

        return redirect()->route('login')->with('estado', 'Senha definida. Já pode entrar com o seu número de membro.');
    }
}
