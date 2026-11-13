<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'identificador' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $id = trim($dados['identificador']);
        $utilizador = preg_match('/^[\d\s.\-\/]+$/', $id)
            ? User::where('carteira', preg_replace('/\D/', '', $id))->first()
            : User::where('email', Str::lower($id))->first();

        if (! $utilizador || ! Hash::check($dados['password'], $utilizador->password)) {
            Auditoria::registar('login.falhou', Str::limit($id, 60));

            return back()->withInput($request->only('identificador'))
                ->withErrors(['identificador' => 'Email/carteira ou senha incorrectos.']);
        }
        if (! $utilizador->ativo) {
            return back()->withInput($request->only('identificador'))
                ->withErrors(['identificador' => 'Conta pendente de aprovacao. Contacte a CDA.']);
        }

        Auth::login($utilizador);
        $request->session()->regenerate();
        $utilizador->forceFill(['ultimo_login' => now()])->save();
        Auditoria::registar('login.ok', null, $utilizador->id);

        return redirect()->intended($utilizador->is_admin ? route('admin.painel') : route('membro.area'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio');
    }
}
