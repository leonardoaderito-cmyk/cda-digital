@extends('layouts.admin')
@section('titulo', 'Mensagens')
@section('conteudo')
@php $porLer = $mensagens->where('lida', false)->count(); @endphp
<div class="admin-cabecalho">
    <div><h1>Mensagens de contacto</h1><p>Mensagens enviadas pelo formulário de Contactos do portal.</p></div>
</div>

<div class="admin-stats">
    <div class="admin-stat {{ $porLer ? 'destaque' : '' }}"><span>Por ler</span><strong>{{ $porLer }}</strong></div>
    <div class="admin-stat"><span>Total</span><strong>{{ $mensagens->count() }}</strong></div>
</div>

@if ($mensagens->isEmpty())
    <div class="admin-vazio"><strong>Sem mensagens</strong>As mensagens enviadas pelos visitantes aparecem aqui.</div>
@else
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0" data-filtro-conta>{{ $mensagens->count() }} mensagens</span>
    <label class="admin-pesquisa">Procurar <input type="search" data-filtro="#tabela-mensagens" placeholder="Nome, email, assunto…"></label>
</div>
<table class="admin-tabela" id="tabela-mensagens">
    <thead><tr><th>De</th><th>Mensagem</th><th>Recebida</th><th>Estado</th></tr></thead>
    <tbody>
        @foreach ($mensagens as $m)
            <tr class="{{ $m->lida ? '' : 'destaque' }}">
                <td><strong>{{ $m->nome }}</strong><small><a href="mailto:{{ $m->email }}" class="admin-ligacao" style="font-weight:400">{{ $m->email }}</a></small></td>
                <td><strong>{{ $m->assunto ?: 'Sem assunto' }}</strong><div class="admin-mensagem" style="font-weight:400">{{ $m->mensagem }}</div></td>
                <td style="font-weight:400;white-space:nowrap">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                <td>
                    @if ($m->lida)
                        <span class="admin-estado neutro">Lida</span>
                    @else
                        <span class="admin-estado alerta">Por ler</span>
                        <form method="POST" action="{{ route('admin.mensagens.ler', $m) }}" style="margin-top:.4rem">@csrf<button class="admin-ligacao">Marcar como lida</button></form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
