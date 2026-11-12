@extends('layouts.app')
@section('titulo', 'Entrar')
@section('conteudo')
<div class="max-w-md mx-auto bg-white rounded-lg border border-zinc-200 p-6">
    <h1 class="text-xl font-semibold mb-4">Entrar na área de membro</h1>
    <form method="POST" action="{{ url('/entrar') }}" class="flex flex-col gap-4">
        @csrf
        <label class="flex flex-col gap-1 text-sm">Email ou número de carteira
            <input name="identificador" value="{{ old('identificador') }}" required class="border border-zinc-300 rounded px-3 py-2">
        </label>
        <label class="flex flex-col gap-1 text-sm">Senha
            <input type="password" name="password" required class="border border-zinc-300 rounded px-3 py-2">
        </label>
        <button class="bg-blue-950 text-white rounded px-4 py-2">Entrar</button>
        <a href="{{ route('registo') }}" class="text-sm text-blue-800 hover:underline">Ainda não tem conta? Registar</a>
    </form>
</div>
@endsection
