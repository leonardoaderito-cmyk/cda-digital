@extends('layouts.admin')
@section('titulo', 'Revista')
@section('conteudo')
{{-- Leonardo (leonardoaderito-cmyk): edicoes da revista; no site a capa abre o PDF --}}
<div class="admin-cabecalho">
    <div>
        <h1>Revista</h1>
        <p>Cada edição tem uma capa e um PDF. No site, ao clicar na capa abre-se a revista.</p>
    </div>
    <div class="admin-cabecalho-accoes">
        <a href="{{ route('revista') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver no site</a>
        <a href="{{ route('admin.revistas.nova') }}" class="admin-botao"><x-icone nome="add" /> Nova edição</a>
    </div>
</div>

<table class="admin-tabela" id="tabela-revistas">
    <thead>
        <tr><th class="admin-col-img"></th><th>Edição</th><th>Publicação</th><th>PDF</th><th>Estado</th></tr>
    </thead>
    <tbody>
        @forelse ($revistas as $r)
            <tr class="{{ $r->activa ? '' : 'inactiva' }}">
                <td class="admin-col-img">@if ($r->capa)<img src="{{ $r->capa }}" alt="">@endif</td>
                <td>
                    <a href="{{ route('admin.revistas.editar', $r) }}" class="admin-titulo-linha">{{ $r->titulo }}</a>
                    @if ($r->edicao)<small> &middot; {{ $r->edicao }}</small>@endif
                    <div class="admin-accoes">
                        <a href="{{ route('admin.revistas.editar', $r) }}">Editar</a>
                        <a href="{{ route('revista.abrir', $r) }}" target="_blank" rel="noopener">Abrir PDF</a>
                        <form method="POST" action="{{ route('admin.revistas.alternar', $r) }}">@csrf<button>{{ $r->activa ? 'Esconder' : 'Mostrar' }}</button></form>
                        <form method="POST" action="{{ route('admin.revistas.apagar', $r) }}" onsubmit="return confirm('Remover esta edição e o seu PDF definitivamente?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                    </div>
                </td>
                <td>{{ $r->data_publicacao?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ is_file($r->caminho()) ? \Illuminate\Support\Number::fileSize(filesize($r->caminho()), 1) : 'Em falta' }}</td>
                <td><span class="admin-estado {{ $r->activa ? 'ligado' : 'desligado' }}">{{ $r->activa ? 'No site' : 'Escondida' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5">Ainda não há edições da revista.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
