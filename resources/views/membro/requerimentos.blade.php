@extends('layouts.membro')
@section('titulo', 'Requerimentos')
@section('conteudo')
@php
    $estados = ['em_analise' => 'Em análise', 'aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado'];
    $cores = ['em_analise' => 'aviso', 'aprovado' => 'ligado', 'rejeitado' => 'alerta'];
@endphp
<div class="admin-cabecalho">
    <div><h1>Requerimentos</h1><p>Peça declarações, segundas vias e outros serviços à CDA e acompanhe a resposta.</p></div>
</div>

<details class="admin-caixa" @if ($requerimentos->isEmpty() || $errors->any()) open @endif>
    <summary>Novo requerimento</summary>
    <form method="POST" action="{{ route('membro.requerimento') }}" class="admin-form">
        @csrf
        <label>Tipo de pedido
            <select name="tipo" required>
                <option value="">Seleccionar</option>
                @foreach ($tipos as $tipo)<option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ $tipo }}</option>@endforeach
            </select>
        </label>
        <label class="total">Descrição <small>(explique o que precisa; até 2000 caracteres)</small><textarea name="descricao" rows="4" maxlength="2000">{{ old('descricao') }}</textarea></label>
        <div><button class="admin-botao">Submeter requerimento</button></div>
    </form>
</details>

@if ($requerimentos->isEmpty())
    <div class="admin-vazio"><strong>Ainda não fez nenhum requerimento</strong>As respostas da CDA aparecem nesta página.</div>
@else
<table class="admin-tabela">
    <thead><tr><th>Pedido</th><th>Submetido</th><th>Estado</th><th>Resposta da CDA</th></tr></thead>
    <tbody>
        @foreach ($requerimentos as $r)
            <tr>
                <td><strong>{{ $r->tipo }}</strong><div class="admin-mensagem">{{ $r->descricao }}</div></td>
                <td style="white-space:nowrap">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                <td><span class="admin-estado {{ $cores[$r->estado] ?? 'neutro' }}">{{ $estados[$r->estado] ?? $r->estado }}</span></td>
                <td>@if ($r->resposta)<div class="admin-mensagem">{{ $r->resposta }}</div><small>{{ $r->updated_at->format('d/m/Y') }}</small>@else<small>Ainda sem resposta</small>@endif</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
