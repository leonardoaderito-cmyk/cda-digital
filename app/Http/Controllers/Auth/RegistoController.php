<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MembroPublico;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegistoController extends Controller
{
    public function create()
    {
        return view('auth.registo');
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'carteira' => ['required', 'string', 'min:6', 'max:40'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'telefone' => ['nullable', 'string', 'max:40'],
            'entidade' => ['nullable', 'string', 'max:160'],
        ]);

        $carteira = preg_replace('/\D/', '', $dados['carteira']);
        $publico = MembroPublico::where('n_carta', $carteira)->first();

        if (! $publico) {
            return back()->withInput()->withErrors(['carteira' => 'Numero de carteira nao encontrado na lista publica da CDA.']);
        }
        if (! $publico->activo) {
            return back()->withInput()->withErrors(['carteira' => 'A carteira indicada nao esta activa.']);
        }
        if (Str::lower(Str::ascii($dados['name'])) !== Str::lower(Str::ascii($publico->nome))) {
            return back()->withInput()->withErrors(['name' => 'O nome nao corresponde ao titular da carteira.']);
        }
        if (User::where('carteira', $carteira)->exists()) {
            return back()->withInput()->withErrors(['carteira' => 'Ja existe uma conta associada a esta carteira.']);
        }

        User::create([
            'name' => $publico->nome,
            'email' => Str::lower($dados['email']),
            'password' => $dados['password'],
            'carteira' => $carteira,
            'telefone' => $dados['telefone'] ?? null,
            'entidade' => $dados['entidade'] ?? null,
            'senha_definida_em' => now(),
        ]);

        return redirect()->route('login')->with('estado', 'Conta criada. Aguarde a aprovacao do administrador da CDA.');
    }
}
