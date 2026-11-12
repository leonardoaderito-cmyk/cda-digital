@extends('layouts.app')
@section('titulo', 'Membros')
@section('conteudo')
@include('admin._menu')
<h1 class="text-2xl font-semibold mb-4">Contas de membros</h1>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula" style="flex-grow:2">Nome</div>
        <div class="tabela-celula">Carteira</div>
        <div class="tabela-celula" style="flex-grow:2">Email</div>
        <div class="tabela-celula tabela-celula-estreita">Estado</div>
        <div class="tabela-celula tabela-celula-estreita"></div>
    </div>
    @forelse ($membros as $m)
        <div class="tabela-linha">
            <div class="tabela-celula" style="flex-grow:2">{{ $m->name }}</div>
            <div class="tabela-celula">{{ $m->carteira }}</div>
            <div class="tabela-celula" style="flex-grow:2">{{ $m->email }}</div>
            <div class="tabela-celula tabela-celula-estreita">{{ $m->ativo ? 'Activa' : 'Pendente' }}</div>
            <div class="tabela-celula tabela-celula-estreita">
                <form method="POST" action="{{ route($m->ativo ? 'admin.membros.desactivar' : 'admin.membros.aprovar', $m) }}">@csrf
                    <button class="text-blue-800 hover:underline">{{ $m->ativo ? 'Desactivar' : 'Aprovar' }}</button>
                </form>
            </div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Ainda não há contas registadas.</div></div>
    @endforelse
</div>
@endsection
