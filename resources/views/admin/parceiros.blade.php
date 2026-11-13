@extends('layouts.admin')
@section('titulo', 'Parceiros')
@section('conteudo')
{{-- Leonardo (leonardoaderito-cmyk): parceiros agrupados por categoria, pela ordem em que aparecem no site --}}
<div class="admin-cabecalho">
    <div>
        <h1>Parceiros</h1>
        <p>Aparecem na página de parceiros, por categoria, e os que têm logotipo aparecem também na página inicial.</p>
    </div>
    <div class="admin-cabecalho-accoes">
        <a href="{{ route('parceiros') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver no site</a>
        <a href="{{ route('admin.parceiros.novo') }}" class="admin-botao"><x-icone nome="add" /> Novo parceiro</a>
    </div>
</div>

@forelse (\App\Models\Parceiro::CATEGORIAS as $chave => $categoria)
    @continue(! isset($parceiros[$chave]))
    <h2 class="admin-subtitulo">{{ $categoria }}</h2>
    <table class="admin-tabela" style="margin-bottom:1.5rem">
        <thead>
            <tr><th>Ordem</th><th class="admin-col-img">Logotipo</th><th>Parceiro</th><th>Ligação</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @foreach ($parceiros[$chave] as $p)
                <tr class="{{ $p->activo ? '' : 'inactiva' }}">
                    <td class="admin-ordem">
                        <form method="POST" action="{{ route('admin.parceiros.mover', $p) }}">@csrf<input type="hidden" name="sentido" value="subir"><button @disabled($loop->first) aria-label="Subir «{{ $p->nome }}»">&uarr;</button></form>
                        <form method="POST" action="{{ route('admin.parceiros.mover', $p) }}">@csrf<input type="hidden" name="sentido" value="descer"><button @disabled($loop->last) aria-label="Descer «{{ $p->nome }}»">&darr;</button></form>
                    </td>
                    <td class="admin-col-img admin-col-logo">
                        @if ($p->logo)<img src="{{ $p->logo }}" alt="">@else<span class="admin-sem-logo">{{ $p->monograma() }}</span>@endif
                    </td>
                    <td>
                        <a href="{{ route('admin.parceiros.editar', $p) }}" class="admin-titulo-linha">{{ $p->nome }}</a>
                        @if ($p->sigla)<small> &middot; {{ $p->sigla }}</small>@endif
                        <div class="admin-accoes">
                            <a href="{{ route('admin.parceiros.editar', $p) }}">Editar</a>
                            <form method="POST" action="{{ route('admin.parceiros.alternar', $p) }}">@csrf<button>{{ $p->activo ? 'Esconder' : 'Mostrar' }}</button></form>
                            <form method="POST" action="{{ route('admin.parceiros.apagar', $p) }}" onsubmit="return confirm('Remover este parceiro?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                        </div>
                    </td>
                    <td>@if ($p->url)<code>{{ \Illuminate\Support\Str::limit(preg_replace('#^https?://(www\.)?#', '', $p->url), 32) }}</code>@else — @endif</td>
                    <td><span class="admin-estado {{ $p->activo ? 'ligado' : 'desligado' }}">{{ $p->activo ? 'No site' : 'Escondido' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@empty
@endforelse

@if ($parceiros->isEmpty())
    <p>Ainda não há parceiros.</p>
@endif
@endsection
