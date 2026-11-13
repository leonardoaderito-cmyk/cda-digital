@extends('layouts.app')
@section('titulo', 'Recuperar senha')
@section('conteudo')
<div class="max-w-md mx-auto bg-white rounded-lg border border-zinc-200 p-6">
    <h1 class="text-xl font-semibold mb-2">Recuperar senha</h1>
    <p class="text-sm text-zinc-600 mb-5">Indique o email registado na CDA. Enviaremos uma ligação para definir uma nova senha.</p>
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf
        <label class="flex flex-col gap-1 text-sm">Email
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="border border-zinc-300 rounded px-3 py-2">
        </label>
        <button class="bg-blue-950 text-white rounded px-4 py-2">Enviar ligação</button>
        <a href="{{ route('login') }}" class="text-sm text-blue-800 hover:underline">Voltar ao início de sessão</a>
    </form>
</div>
@endsection
