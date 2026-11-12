@extends('layouts.admin')
@section('titulo', 'Páginas')
@section('conteudo')
<div class="admin-cabecalho">
    <h1>Páginas</h1>
    <a href="{{ route('admin.paginas.nova') }}" class="admin-botao">Adicionar nova</a>
</div>

<ul class="admin-filtros">
    <li><a href="{{ route('admin.paginas') }}" class="{{ $filtro ? '' : 'activo' }}">Todas <span>({{ $totais['todas'] }})</span></a></li>
    <li><a href="{{ route('admin.paginas', ['estado' => 'activas']) }}" class="{{ $filtro === 'activas' ? 'activo' : '' }}">Activas <span>({{ $totais['activas'] }})</span></a></li>
    <li><a href="{{ route('admin.paginas', ['estado' => 'inactivas']) }}" class="{{ $filtro === 'inactivas' ? 'activo' : '' }}">Desactivadas <span>({{ $totais['inactivas'] }})</span></a></li>
</ul>

<table class="admin-tabela">
    <thead>
        <tr><th>Título</th><th>Tipo</th><th>Endereço</th><th>Estado</th><th>Alterada</th></tr>
    </thead>
    <tbody>
        @forelse ($paginas as $p)
            <tr class="{{ $p->activa ? '' : 'inactiva' }}">
                <td>
                    <a href="{{ route('admin.paginas.editar', $p) }}" class="admin-titulo-linha">{{ $p->titulo }}</a>
                    <div class="admin-accoes">
                        <a href="{{ route('admin.paginas.editar', $p) }}">Editar</a>
                        @if ($p->activa)<a href="{{ $p->url() }}" target="_blank" rel="noopener">Ver</a>@endif
                        <form method="POST" action="{{ route('admin.paginas.alternar', $p) }}">@csrf<button>{{ $p->activa ? 'Desactivar' : 'Activar' }}</button></form>
                        @if (! $p->eSistema() && ! \Illuminate\Support\Facades\Route::has($p->slug))
                            <form method="POST" action="{{ route('admin.paginas.apagar', $p) }}" onsubmit="return confirm('Remover esta página definitivamente?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                        @endif
                    </div>
                </td>
                <td>{{ $p->eSistema() ? 'Secção do portal' : 'Página de texto' }}</td>
                <td><code>{{ parse_url($p->url(), PHP_URL_PATH) }}</code></td>
                <td><span class="admin-estado {{ $p->activa ? 'ligado' : 'desligado' }}">{{ $p->activa ? 'Activa' : 'Desactivada' }}</span></td>
                <td>{{ $p->updated_at?->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Não há páginas neste filtro.</td></tr>
        @endforelse
    </tbody>
</table>
<p class="admin-nota">Uma página desactivada deixa de abrir para os visitantes (responde "página não encontrada") e sai dos menus. As secções do portal só podem ser activadas ou desactivadas; o seu conteúdo é gerido nos respectivos menus.</p>
@endsection
