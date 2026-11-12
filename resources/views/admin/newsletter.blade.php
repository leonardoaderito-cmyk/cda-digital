@extends('layouts.app')
@section('titulo', 'Newsletter')
@section('conteudo')
@include('admin._menu')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-2xl font-semibold">Newsletter</h1>
        <p class="text-zinc-600">{{ $subscricoes->total() }} contactos registados.</p>
    </div>
    <a href="{{ route('admin.newsletter.exportar') }}" class="bg-blue-950 text-white rounded px-4 py-2">Exportar CSV</a>
</div>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula">Nome</div>
        <div class="tabela-celula" style="flex-grow:2">Email</div>
        <div class="tabela-celula tabela-celula-estreita">Estado</div>
        <div class="tabela-celula tabela-celula-estreita">Acção</div>
    </div>
    @forelse ($subscricoes as $subscricao)
        <div class="tabela-linha">
            <div class="tabela-celula">{{ $subscricao->nome ?: '—' }}</div>
            <div class="tabela-celula" style="flex-grow:2">{{ $subscricao->email }}</div>
            <div class="tabela-celula tabela-celula-estreita">{{ $subscricao->activo ? 'Activo' : 'Inactivo' }}</div>
            <div class="tabela-celula tabela-celula-estreita">
                <form method="POST" action="{{ route('admin.newsletter.alternar', $subscricao) }}">@csrf<button class="text-blue-800 hover:underline">{{ $subscricao->activo ? 'Desactivar' : 'Reactivar' }}</button></form>
            </div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Ainda não há subscrições.</div></div>
    @endforelse
</div>
<div class="mt-6">{{ $subscricoes->links() }}</div>
@endsection
