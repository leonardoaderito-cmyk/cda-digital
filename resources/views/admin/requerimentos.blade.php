@extends('layouts.admin')
@section('titulo', 'Requerimentos')
@section('conteudo')
@php
    $gere = auth()->user()->pode('requerimentos.gerir');
    $estados = ['em_analise' => 'Em análise', 'aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado'];
    $cores = ['em_analise' => 'aviso', 'aprovado' => 'ligado', 'rejeitado' => 'alerta'];
@endphp
<div class="admin-cabecalho">
    <div><h1>Requerimentos</h1><p>Pedidos submetidos pelos membros na Área do Membro.</p></div>
</div>

<div class="admin-stats">
    <div class="admin-stat {{ $requerimentos->where('estado', 'em_analise')->count() ? 'destaque' : '' }}"><span>Em análise</span><strong>{{ $requerimentos->where('estado', 'em_analise')->count() }}</strong></div>
    <div class="admin-stat ok"><span>Aprovados</span><strong>{{ $requerimentos->where('estado', 'aprovado')->count() }}</strong></div>
    <div class="admin-stat"><span>Rejeitados</span><strong>{{ $requerimentos->where('estado', 'rejeitado')->count() }}</strong></div>
</div>

@if ($requerimentos->isEmpty())
    <div class="admin-vazio"><strong>Sem requerimentos</strong>Quando um membro submeter um pedido, aparece aqui.</div>
@else
@include('admin._separadores', ['tabela' => '#tabela-requerimentos', 'separadores' => ['' => ['Todos', $requerimentos->count()]] + collect($estados)->map(fn ($rotulo, $valor) => [$rotulo, $requerimentos->where('estado', $valor)->count()])->all()])
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0" data-filtro-conta>{{ $requerimentos->count() }} requerimentos</span>
    <label class="admin-pesquisa">Procurar <input type="search" data-filtro="#tabela-requerimentos" placeholder="Membro, número, tipo de pedido…"></label>
</div>
<table class="admin-tabela" id="tabela-requerimentos">
    <thead><tr><th>Membro</th><th>Pedido</th><th>Estado</th><th>Resposta</th></tr></thead>
    <tbody>
        @foreach ($requerimentos as $r)
            <tr class="{{ $r->estado === 'em_analise' ? 'destaque' : '' }}" data-grupo="{{ $r->estado }}">
                <td><strong>{{ $r->user->name }}</strong><small>N.º {{ $r->user->carteira ?: '—' }}</small><small>{{ $r->created_at?->format('d/m/Y H:i') }}</small></td>
                <td><strong>{{ $r->tipo }}</strong><div class="admin-mensagem" style="font-weight:400">{{ $r->descricao }}</div></td>
                <td><span class="admin-estado {{ $cores[$r->estado] ?? 'neutro' }}">{{ $estados[$r->estado] ?? $r->estado }}</span></td>
                <td>
                    @if ($gere)
                        <form method="POST" action="{{ route('admin.requerimentos.responder', $r) }}" class="admin-inline" style="font-weight:400">@csrf
                            <select name="estado" aria-label="Estado">@foreach ($estados as $e => $rotulo)<option value="{{ $e }}" @selected($r->estado === $e)>{{ $rotulo }}</option>@endforeach</select>
                            <input name="resposta" value="{{ $r->resposta }}" placeholder="Resposta ao membro" aria-label="Resposta">
                            <button class="admin-botao admin-botao-s">Guardar</button>
                        </form>
                    @else
                        <span style="font-weight:400">{{ $r->resposta ?: '—' }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
