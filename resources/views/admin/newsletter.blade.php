@extends('layouts.admin')
@section('titulo', 'Newsletter')
@section('conteudo')
<div class="admin-cabecalho">
    <div><h1>Newsletter</h1><p>{{ $subscricoes->total() }} contactos registados pelo formulário do portal.</p></div>
    <div class="admin-cabecalho-accoes"><a href="{{ route('admin.newsletter.exportar') }}" class="admin-botao">Exportar CSV</a></div>
</div>

@if ($subscricoes->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há subscrições</strong>Os visitantes subscrevem na página inicial e no rodapé do portal.</div>
@else
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0">Página {{ $subscricoes->currentPage() }} de {{ $subscricoes->lastPage() }}</span>
    <label class="admin-pesquisa">Procurar nesta página <input type="search" data-filtro="#tabela-newsletter" placeholder="Nome ou email…"></label>
</div>
<table class="admin-tabela" id="tabela-newsletter">
    <thead><tr><th>Email</th><th>Nome</th><th>Subscrito em</th><th>Estado</th></tr></thead>
    <tbody>
        @foreach ($subscricoes as $subscricao)
            <tr class="{{ $subscricao->activo ? '' : 'inactiva' }}">
                <td>
                    <strong>{{ $subscricao->email }}</strong>
                    <div class="admin-accoes">
                        <form method="POST" action="{{ route('admin.newsletter.alternar', $subscricao) }}">@csrf<button class="{{ $subscricao->activo ? 'perigo' : '' }}">{{ $subscricao->activo ? 'Desactivar' : 'Reactivar' }}</button></form>
                    </div>
                </td>
                <td>{{ $subscricao->nome ?: '—' }}</td>
                <td>{{ $subscricao->created_at?->format('d/m/Y H:i') }}</td>
                <td><span class="admin-estado {{ $subscricao->activo ? 'ligado' : 'desligado' }}">{{ $subscricao->activo ? 'Activo' : 'Inactivo' }}</span></td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $subscricoes->links() }}</div>
@endif
@endsection
