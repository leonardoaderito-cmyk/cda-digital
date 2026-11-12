@extends('layouts.admin')
@section('titulo', 'Mensagens')
@section('conteudo')
<h1 class="text-2xl font-semibold mb-4">Mensagens de contacto</h1>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula">De</div>
        <div class="tabela-celula" style="flex-grow:4">Mensagem</div>
        <div class="tabela-celula tabela-celula-estreita"></div>
    </div>
    @forelse ($mensagens as $m)
        <div class="tabela-linha {{ $m->lida ? '' : 'bg-yellow-50' }}">
            <div class="tabela-celula">{{ $m->nome }}<br><span class="text-xs text-zinc-500">{{ $m->email }}</span></div>
            <div class="tabela-celula" style="flex-grow:4"><strong>{{ $m->assunto }}</strong><br>{{ $m->mensagem }}</div>
            <div class="tabela-celula tabela-celula-estreita">
                @unless ($m->lida)
                    <form method="POST" action="{{ route('admin.mensagens.ler', $m) }}">@csrf<button class="text-blue-800 hover:underline">Marcar lida</button></form>
                @endunless
            </div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Sem mensagens.</div></div>
    @endforelse
</div>
@endsection
