@extends('layouts.admin')
@section('titulo', 'Documentos')
@section('conteudo')
<div class="admin-cabecalho">
    <div><h1>Documentos</h1><p>{{ $documentos->total() }} documentos no centro documental do portal.</p></div>
</div>

<details class="admin-caixa" @if ($errors->any() || $documentos->total() === 0) open @endif>
    <summary>Publicar documento <small>— ficheiro PDF até 30 MB</small></summary>
    <form method="POST" action="{{ route('admin.documentos.guardar') }}" enctype="multipart/form-data" class="admin-form">
        @csrf
        <label class="largo">Título<input name="titulo" value="{{ old('titulo') }}" required></label>
        <label>Tipo<input name="tipo" value="{{ old('tipo') }}" list="tipos-documento" placeholder="Ex.: Circular"></label>
        <label>Ano<input name="ano" type="number" min="1990" max="2100" value="{{ old('ano', now()->year) }}"></label>
        <label class="largo">Ficheiro PDF<input type="file" name="ficheiro" accept="application/pdf" required style="margin-top:.35rem"></label>
        <div><button class="admin-botao">Publicar</button></div>
    </form>
    <datalist id="tipos-documento">
        @foreach (['Lei', 'Decreto', 'Regulamento', 'Estatuto', 'Circular', 'Comunicado', 'Convocatória', 'Ordem de Serviço', 'Relatório', 'Boletim'] as $tipo)<option value="{{ $tipo }}">@endforeach
    </datalist>
</details>

@if ($documentos->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há documentos</strong>Publique o primeiro documento em PDF.</div>
@else
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0">Página {{ $documentos->currentPage() }} de {{ $documentos->lastPage() }}</span>
    <label class="admin-pesquisa">Procurar nesta página <input type="search" data-filtro="#tabela-documentos" placeholder="Título, tipo, ano…"></label>
</div>
<table class="admin-tabela" id="tabela-documentos">
    <thead><tr><th>Título</th><th>Tipo</th><th>Ano</th><th>Alterado</th></tr></thead>
    <tbody>
        @foreach ($documentos as $d)
            <tr>
                <td>
                    <strong>{{ $d->titulo }}</strong>
                    <div class="admin-accoes">
                        <a href="{{ route('documento.abrir', $d) }}" target="_blank" rel="noopener">Abrir PDF</a>
                        <form method="POST" action="{{ route('admin.documentos.apagar', $d) }}" onsubmit="return confirm('Remover este documento definitivamente?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                    </div>
                </td>
                <td>{{ $d->tipo ?: '—' }}</td>
                <td>{{ $d->ano ?: '—' }}</td>
                <td>{{ $d->updated_at?->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $documentos->links() }}</div>
@endif
@endsection
