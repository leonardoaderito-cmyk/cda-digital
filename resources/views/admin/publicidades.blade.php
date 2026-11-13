@extends('layouts.admin')
@section('titulo', 'Publicidade')
@section('conteudo')
{{-- Leonardo (leonardoaderito-cmyk): anuncios que rodam na pagina inicial, ao lado dos videos --}}
<div class="admin-cabecalho">
    <div>
        <h1>Publicidade</h1>
        <p>Os anúncios activos rodam na página inicial, ao lado dos vídeos, pela ordem desta lista. Sem anúncios, o espaço convida empresas a anunciar.</p>
    </div>
    <div class="admin-cabecalho-accoes">
        <a href="{{ route('inicio') }}#videos" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver no site</a>
        <a href="{{ route('admin.publicidades.novo') }}" class="admin-botao"><x-icone nome="add" /> Novo anúncio</a>
    </div>
</div>

@if ($publicidades->isEmpty())
    <p>Ainda não há anúncios.</p>
@else
    <table class="admin-tabela">
        <thead>
            <tr><th>Ordem</th><th class="admin-col-img">Imagem</th><th>Anúncio</th><th>Período</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @foreach ($publicidades as $p)
                @php $estado = $p->estado(); @endphp
                <tr class="{{ $estado === 'No site' ? '' : 'inactiva' }}">
                    <td class="admin-ordem">
                        <form method="POST" action="{{ route('admin.publicidades.mover', $p) }}">@csrf<input type="hidden" name="sentido" value="subir"><button @disabled($loop->first) aria-label="Subir «{{ $p->titulo }}»">&uarr;</button></form>
                        <form method="POST" action="{{ route('admin.publicidades.mover', $p) }}">@csrf<input type="hidden" name="sentido" value="descer"><button @disabled($loop->last) aria-label="Descer «{{ $p->titulo }}»">&darr;</button></form>
                    </td>
                    <td class="admin-col-img"><img src="{{ $p->imagem }}" alt="" class="admin-capa"></td>
                    <td>
                        <a href="{{ route('admin.publicidades.editar', $p) }}" class="admin-titulo-linha">{{ $p->titulo }}</a>
                        @if ($p->anunciante)<small> &middot; {{ $p->anunciante }}</small>@endif
                        <div class="admin-accoes">
                            <a href="{{ route('admin.publicidades.editar', $p) }}">Editar</a>
                            <form method="POST" action="{{ route('admin.publicidades.alternar', $p) }}">@csrf<button>{{ $p->activo ? 'Esconder' : 'Mostrar' }}</button></form>
                            <form method="POST" action="{{ route('admin.publicidades.apagar', $p) }}" onsubmit="return confirm('Remover este anúncio?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                        </div>
                    </td>
                    <td>
                        @if ($p->inicio || $p->fim)
                            {{ $p->inicio?->format('d/m/Y') ?? 'Já' }} &ndash; {{ $p->fim?->format('d/m/Y') ?? 'sem fim' }}
                        @else
                            Sempre
                        @endif
                    </td>
                    <td><span class="admin-estado {{ $estado === 'No site' ? 'ligado' : 'desligado' }}">{{ $estado }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection
