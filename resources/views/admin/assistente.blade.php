@extends('layouts.admin')
@section('titulo', 'Assistente CDA')
@section('conteudo')
{{-- Autor: Leonardo (leonardoaderito-cmyk) - estado do Assistente CDA, reindexacao e perguntas dos visitantes --}}
@php
    $modos = [
        'resposta' => ['Respondida', 'ligado'],
        'pesquisa' => ['Lista de resultados', 'aviso'],
        'sem-resultados' => ['Sem resposta', 'alerta'],
        'limite' => ['Limite de perguntas', 'neutro'],
    ];
    $semTexto = $estado['documentos'] - $estado['documentos_com_texto'];
@endphp
<div class="admin-cabecalho">
    <div>
        <h1>Assistente CDA</h1>
        <p>Janela de conversa do site. Responde só com o que está publicado: documentos, páginas, notícias e eventos.</p>
    </div>
    <div class="admin-cabecalho-accoes">
        <form method="POST" action="{{ route('admin.assistente.indexar') }}">@csrf
            <button class="admin-botao admin-botao-secundario"><x-icone nome="refresh" /> Refazer o índice</button>
        </form>
    </div>
</div>

<div class="admin-stats">
    <div class="admin-stat ok"><span>Motor</span><strong>Próprio</strong></div>
    <div class="admin-stat"><span>Documentos pesquisáveis</span><strong>{{ $estado['documentos'] }}</strong></div>
    <div class="admin-stat"><span>Com texto completo</span><strong>{{ $estado['documentos_com_texto'] }}</strong></div>
    <div class="admin-stat"><span>Perguntas nos últimos 7 dias</span><strong>{{ $totalSemana }}</strong></div>
    <div class="admin-stat {{ $semResposta ? 'destaque' : '' }}"><span>Sem resposta (7 dias)</span><strong>{{ $semResposta }}</strong></div>
</div>

<section class="admin-caixa">
    <h2>Como está configurado</h2>
    <ul class="admin-ligacoes admin-ligacoes-numeros">
        <li><span>Motor de respostas<small>Próprio do portal, sem serviços externos: escolhe as frases dos conteúdos publicados que respondem à pergunta, corrige erros de escrita e indica as fontes e os PDFs</small></span><strong>Activo</strong></li>
        <li><span>Pedidos reconhecidos<small>Contactos e horário, últimas notícias, próximos eventos, directório de membros e listas do Centro Documental por tipo e ano</small></span><strong>5</strong></li>
        <li><span>Páginas e notícias no índice<small>Actualizadas sozinhas ao publicar, editar ou apagar</small></span><strong>{{ ($estado['por_tipo']['pagina'] ?? 0) + ($estado['por_tipo']['noticia'] ?? 0) + ($estado['por_tipo']['evento'] ?? 0) }}</strong></li>
        <li><span>PDFs encontrados só pela ficha<small>Documentos digitalizados (imagem): pesquisa pelo título, tipo, entidade e ano. O texto pode ser acrescentado em database/data/textos.</small></span><strong>{{ $semTexto }}</strong></li>
    </ul>
</section>

<h2 class="admin-seccao">Perguntas recentes</h2>
@if ($perguntas->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há perguntas</strong>O assistente aparece no canto inferior direito de todas as páginas do site.</div>
@else
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0">As perguntas sem resposta mostram o que falta publicar no site.</span>
    <label class="admin-pesquisa">Procurar <input type="search" data-filtro="#tabela-assistente" placeholder="Palavras da pergunta…"></label>
</div>
<div class="admin-tabela-caixa">
<table class="admin-tabela" id="tabela-assistente">
    <thead><tr><th>Pergunta</th><th>Resultado</th><th class="num">Fontes</th><th>Data</th></tr></thead>
    <tbody>
        @foreach ($perguntas as $p)
            @php [$rotulo, $classe] = $modos[$p->modo] ?? [ucfirst($p->modo), 'neutro']; @endphp
            <tr>
                <td data-rotulo="Pergunta">{{ $p->pergunta }}</td>
                <td data-rotulo="Resultado"><span class="admin-estado {{ $classe }}">{{ $rotulo }}</span></td>
                <td data-rotulo="Fontes" class="num">{{ $p->fontes }}</td>
                <td data-rotulo="Data" class="admin-col-data">{{ \Illuminate\Support\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif
@endsection
