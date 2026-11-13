@extends('layouts.app')
@section('titulo', 'Registar')
@section('conteudo')
<div class="max-w-lg mx-auto bg-white rounded-lg border border-zinc-200 p-6">
    <h1 class="text-xl font-semibold mb-1">Criar conta de membro</h1>
    <p class="text-sm text-zinc-600 mb-4">O nome e o número de carteira são validados contra a lista pública da CDA. A conta fica pendente até ser aprovada.</p>
    <form method="POST" action="{{ url('/registar') }}" class="flex flex-col gap-4">
        @csrf
        @foreach ([['name','Nome completo','text'],['carteira','Número de carteira','text'],['email','Email','email'],['telefone','Telefone (opcional)','text'],['entidade','Entidade / empresa (opcional)','text'],['password','Senha (mínimo 8 caracteres)','password'],['password_confirmation','Confirmar senha','password']] as [$campo, $rotulo, $tipo])
            <label class="flex flex-col gap-1 text-sm">{{ $rotulo }}
                <input type="{{ $tipo }}" name="{{ $campo }}" @if ($tipo !== 'password') value="{{ old($campo) }}" @endif class="border border-zinc-300 rounded px-3 py-2">
            </label>
        @endforeach
        <button class="bg-blue-950 text-white rounded px-4 py-2">Registar</button>
    </form>
</div>
@endsection
