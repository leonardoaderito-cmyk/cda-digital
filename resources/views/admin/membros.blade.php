@extends('layouts.admin')
@section('titulo', 'Membros')
@section('conteudo')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div><h1 class="text-2xl font-semibold">Contas de membros</h1><p class="text-sm text-zinc-600 mt-1">Crie acessos, envie as primeiras credenciais e acompanhe as entradas.</p></div>
    <span class="bg-zinc-100 rounded-full px-3 py-1 text-sm">{{ $membros->count() }} contas</span>
</div>

@if(auth()->user()->pode('membros.gerir'))
<section class="bg-white border border-zinc-200 rounded-lg p-5 mb-8">
    <h2 class="text-lg font-semibold mb-1">Criar acesso de membro</h2>
    <p class="text-sm text-zinc-600 mb-4">O membro receberá no email uma ligação válida por 72 horas para definir a sua própria senha.</p>
    <form method="POST" action="{{ route('admin.membros.criar') }}" class="flex flex-wrap gap-3 items-end">
        @csrf
        <label class="flex flex-col gap-1 text-sm flex-[2_1_18rem]">Membro da lista CDA
            <select name="membro_publico_id" required class="border border-zinc-300 rounded px-3 py-2">
                <option value="">Seleccionar membro sem conta</option>
                @foreach ($semConta as $registo)<option value="{{ $registo->id }}" @selected(old('membro_publico_id') == $registo->id)>{{ $registo->nome }} — {{ $registo->n_carta }}</option>@endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1 text-sm flex-1 min-w-52">Email registado
            <input type="email" name="email" value="{{ old('email') }}" required class="border border-zinc-300 rounded px-3 py-2">
        </label>
        <label class="flex flex-col gap-1 text-sm flex-1 min-w-40">Telefone
            <input name="telefone" value="{{ old('telefone') }}" class="border border-zinc-300 rounded px-3 py-2">
        </label>
        <button class="bg-blue-950 text-white rounded px-4 py-2">Criar e enviar acesso</button>
    </form>
</section>
@endif

<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula" style="flex-grow:2">Membro</div><div class="tabela-celula">Contacto</div><div class="tabela-celula">Acesso</div><div class="tabela-celula">Situação financeira</div><div class="tabela-celula" style="flex-grow:2">Acções</div>
    </div>
    @forelse ($membros as $m)
        <div class="tabela-linha">
            <div class="tabela-celula" style="flex-grow:2"><strong>{{ $m->name }}</strong><small class="block text-zinc-500">N.º {{ $m->carteira ?: '—' }} · {{ $m->entidade ?: 'Sem entidade' }}</small></div>
            <div class="tabela-celula">{{ $m->email }}<small class="block text-zinc-500">{{ $m->telefone ?: 'Sem telefone' }}</small></div>
            <div class="tabela-celula"><span class="{{ $m->ativo ? 'text-emerald-700' : 'text-amber-700' }} font-medium">{{ $m->ativo ? 'Activa' : 'Pendente' }}</span><small class="block text-zinc-500">{{ $m->ultimo_login ? 'Última entrada '.$m->ultimo_login->format('d/m/Y H:i') : 'Nunca entrou' }}</small><small class="block text-zinc-500">{{ $m->senha_definida_em ? 'Senha definida' : ($m->credenciais_enviadas_em ? 'Convite enviado' : 'Sem convite') }}</small></div>
            <div class="tabela-celula"><span class="{{ $m->estaRegular() ? 'text-emerald-700' : 'text-amber-700' }} font-medium">{{ $m->estaRegular() ? 'Regularizada' : 'Não regularizada' }}</span></div>
            <div class="tabela-celula" style="flex-grow:2"><div class="flex flex-wrap gap-3 items-center">
                @if(auth()->user()->pode('membros.gerir'))
                <form method="POST" action="{{ route($m->ativo ? 'admin.membros.desactivar' : 'admin.membros.aprovar', $m) }}">@csrf<button class="text-blue-800 hover:underline">{{ $m->ativo ? 'Desactivar' : 'Aprovar' }}</button></form>
                <form method="POST" action="{{ route('admin.membros.credenciais', $m) }}">@csrf<button class="text-blue-800 hover:underline">{{ $m->credenciais_enviadas_em ? 'Reenviar credenciais' : 'Enviar credenciais' }}</button></form>
                @else<span class="text-zinc-500">Consulta</span>@endif
            </div></div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Ainda não há contas registadas.</div></div>
    @endforelse
</div>
@endsection
