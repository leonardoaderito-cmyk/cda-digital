@extends('layouts.app')
@section('titulo', 'Definir nova senha')
@section('conteudo')
{{-- Leonardo (leonardoaderito-cmyk): primeiro acesso com senha provisoria --}}
<div class="max-w-md mx-auto bg-white border border-zinc-200 p-6">
    <h1 class="text-xl font-semibold mb-2">Defina a sua senha</h1>
    <p class="text-sm text-zinc-600 mb-5">Esta conta entrou com uma senha provisória. Para continuar, crie uma senha pessoal com pelo menos 8 caracteres.</p>
    <form method="POST" action="{{ route('senha.trocar.guardar') }}" class="flex flex-col gap-4">
        @csrf
        @unless (filter_var($utilizador->email, FILTER_VALIDATE_EMAIL))
            <label class="flex flex-col gap-1 text-sm">O seu email <small class="text-zinc-500">(passa a ser o utilizador de entrada e serve para recuperar a senha)</small>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="border border-zinc-300 px-3 py-2">
            </label>
            @error('email')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        @endunless
        <label class="flex flex-col gap-1 text-sm">Nova senha
            <input type="password" name="password" required minlength="8" autocomplete="new-password" class="border border-zinc-300 px-3 py-2">
        </label>
        @error('password')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        <label class="flex flex-col gap-1 text-sm">Confirmar nova senha
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="border border-zinc-300 px-3 py-2">
        </label>
        <button class="bg-black text-white px-4 py-2 font-semibold">Guardar e continuar</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="text-sm text-zinc-600 underline">Sair</button></form>
</div>
@endsection
