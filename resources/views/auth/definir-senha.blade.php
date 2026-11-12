@extends('layouts.app')
@section('titulo', 'Definir senha')
@section('conteudo')
<div class="max-w-md mx-auto bg-white rounded-lg border border-zinc-200 p-6">
    <h1 class="text-xl font-semibold mb-2">Definir a sua senha</h1>
    <p class="text-sm text-zinc-600 mb-5">Crie uma senha pessoal com pelo menos 8 caracteres.</p>
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="flex flex-col gap-1 text-sm">Email
            <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="border border-zinc-300 rounded px-3 py-2">
        </label>
        <label class="flex flex-col gap-1 text-sm">Nova senha
            <input type="password" name="password" required autocomplete="new-password" class="border border-zinc-300 rounded px-3 py-2">
        </label>
        <label class="flex flex-col gap-1 text-sm">Confirmar nova senha
            <input type="password" name="password_confirmation" required autocomplete="new-password" class="border border-zinc-300 rounded px-3 py-2">
        </label>
        <button class="bg-blue-950 text-white rounded px-4 py-2">Guardar senha</button>
    </form>
</div>
@endsection
