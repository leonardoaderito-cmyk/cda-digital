@extends('layouts.admin')
@section('titulo', 'Carrossel')
@section('conteudo')
@php $activos = $slides->where('activo', true)->count(); @endphp
<div class="admin-cabecalho">
    <div>
        <h1>Carrossel da página inicial</h1>
        <p>
            @if ($activos)
                O carrossel mostra os {{ $activos }} slides activos, pela ordem desta lista.
            @else
                Não há slides activos, por isso o carrossel mostra automaticamente as 4 notícias mais recentes.
            @endif
        </p>
    </div>
    <div class="admin-cabecalho-accoes"><a href="{{ route('admin.slides.novo') }}" class="admin-botao"><x-icone nome="add" /> Novo slide</a></div>
</div>

<table class="admin-tabela">
    <thead>
        <tr><th>Ordem</th><th class="admin-col-img"></th><th>Slide</th><th>Botão</th><th>Estado</th></tr>
    </thead>
    <tbody>
        @forelse ($slides as $s)
            <tr class="{{ $s->activo ? '' : 'inactiva' }}">
                <td class="admin-ordem">
                    <form method="POST" action="{{ route('admin.slides.mover', $s) }}">@csrf<input type="hidden" name="sentido" value="subir"><button @disabled($loop->first) aria-label="Subir «{{ $s->titulo }}»">&uarr;</button></form>
                    <form method="POST" action="{{ route('admin.slides.mover', $s) }}">@csrf<input type="hidden" name="sentido" value="descer"><button @disabled($loop->last) aria-label="Descer «{{ $s->titulo }}»">&darr;</button></form>
                </td>
                <td class="admin-col-img">@if ($s->imagem)<img src="{{ $s->imagem }}" alt="">@endif</td>
                <td>
                    <a href="{{ route('admin.slides.editar', $s) }}" class="admin-titulo-linha">{{ $s->titulo }}</a>
                    @if ($s->etiqueta)<small> &middot; {{ $s->etiqueta }}</small>@endif
                    <div class="admin-accoes">
                        <a href="{{ route('admin.slides.editar', $s) }}">Editar</a>
                        <form method="POST" action="{{ route('admin.slides.alternar', $s) }}">@csrf<button>{{ $s->activo ? 'Desactivar' : 'Activar' }}</button></form>
                        <form method="POST" action="{{ route('admin.slides.apagar', $s) }}" onsubmit="return confirm('Remover este slide definitivamente?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                    </div>
                </td>
                <td>@if ($s->botao_url){{ $s->botao_texto ?: 'Saber mais' }}<br><code>{{ \Illuminate\Support\Str::limit($s->botao_url, 40) }}</code>@else — @endif</td>
                <td><span class="admin-estado {{ $s->activo ? 'ligado' : 'desligado' }}">{{ $s->activo ? 'Activo' : 'Desactivado' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5">Ainda não criou nenhum slide.</td></tr>
        @endforelse
    </tbody>
</table>

@if ($noticias->isNotEmpty())
    <form method="POST" action="{{ route('admin.slides.da-noticia') }}" class="admin-caixa admin-carregar" style="margin-top:1.25rem">
        @csrf
        <label for="noticia"><strong>Criar slide a partir de uma notícia</strong><small>Copia o título, o resumo, a imagem e a ligação. Pode ajustar tudo a seguir.</small></label>
        <select id="noticia" name="noticia" class="admin-campo" style="flex:1 1 16rem;width:auto" required>
            @foreach ($noticias as $n)
                <option value="{{ $n->id }}">{{ $n->data?->format('d/m/Y') }} — {{ \Illuminate\Support\Str::limit($n->titulo, 70) }}</option>
            @endforeach
        </select>
        <button class="admin-botao admin-botao-secundario">Criar slide</button>
    </form>
@endif
@endsection
