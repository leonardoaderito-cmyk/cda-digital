@extends('layouts.admin')
@section('titulo', 'Utilizadores e acessos')
@section('conteudo')
<div class="admin-cabecalho">
    <div><h1>Utilizadores e acessos</h1><p>Defina quem administra o portal, com que responsabilidades, e consulte o registo de actividade.</p></div>
</div>

<section class="admin-caixa">
    <h2>Níveis de acesso</h2>
    <dl class="admin-niveis">
        @foreach ([
            ['Administrador geral', 'Acesso total, incluindo utilizadores e níveis.'],
            ['Gestor de adesões', 'Aprova membros, contas e requerimentos.'],
            ['Gestor financeiro', 'Quotas, pagamentos e regularidade.'],
            ['Editor de conteúdos', 'Páginas, notícias, eventos e comunicação.'],
            ['Utilizador standard', 'Só consulta membros e situação financeira.'],
        ] as [$titulo, $texto])
            <div><dt>{{ $titulo }}</dt><dd>{{ $texto }}</dd></div>
        @endforeach
    </dl>
</section>

<details class="admin-caixa" @if ($errors->any()) open @endif>
    <summary>Adicionar utilizador administrativo <small>— envia por email a ligação para definir a senha</small></summary>
    <form method="POST" action="{{ route('admin.utilizadores.criar') }}" class="admin-form">@csrf
        <label>Nome<input name="name" value="{{ old('name') }}" required></label>
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>Nível<select name="nivel_acesso">@foreach ($niveis as $valor => $rotulo)<option value="{{ $valor }}" @selected(old('nivel_acesso') === $valor)>{{ $rotulo }}</option>@endforeach</select></label>
        <div><button class="admin-botao">Criar e enviar acesso</button></div>
    </form>
</details>

<h2 class="admin-seccao">Contas administrativas</h2>
<p>{{ $utilizadores->count() }} utilizadores com acesso à administração.</p>
<table class="admin-tabela">
    <thead><tr><th>Utilizador</th><th>Estado</th><th>Último acesso</th><th>Nível e estado</th></tr></thead>
    <tbody>
        @foreach ($utilizadores as $u)
            <tr class="{{ $u->ativo ? '' : 'inactiva' }}">
                <td>
                    <strong>{{ $u->name }}</strong>@if ($u->is(auth()->user())) <small style="display:inline">(a sua conta)</small>@endif
                    <small>{{ $u->email }}</small>
                    <div class="admin-accoes">
                        <form method="POST" action="{{ route('admin.utilizadores.credenciais', $u) }}">@csrf<button>{{ $u->credenciais_enviadas_em ? 'Reenviar acesso' : 'Enviar acesso' }}</button></form>
                    </div>
                </td>
                <td><span class="admin-estado {{ $u->ativo ? 'ligado' : 'desligado' }}">{{ $u->ativo ? 'Activo' : 'Inactivo' }}</span><small>{{ $u->nivelLabel() }}</small></td>
                <td>{{ $u->ultimo_login?->format('d/m/Y H:i') ?: 'Nunca entrou' }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.utilizadores.actualizar', $u) }}" class="admin-inline">@csrf
                        <select name="nivel_acesso" aria-label="Nível de {{ $u->name }}">@foreach ($niveis as $valor => $rotulo)<option value="{{ $valor }}" @selected($u->nivel_acesso === $valor)>{{ $rotulo }}</option>@endforeach</select>
                        <label><input type="checkbox" name="ativo" value="1" @checked($u->ativo)> Activo</label>
                        <button class="admin-botao admin-botao-s">Guardar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2 class="admin-seccao">Registo de actividade</h2>
<p>Alterações efectuadas na administração e entradas no portal, das mais recentes para as mais antigas.</p>
@if ($actividade->isEmpty())
    <div class="admin-vazio"><strong>Sem actividade registada</strong></div>
@else
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0">{{ $actividade->total() }} registos</span>
    <label class="admin-pesquisa">Procurar nesta página <input type="search" data-filtro="#tabela-logs" placeholder="Utilizador, acção, IP…"></label>
</div>
<table class="admin-tabela" id="tabela-logs">
    <thead><tr><th>Data</th><th>Utilizador</th><th>Acção</th><th>Alvo</th><th>IP</th></tr></thead>
    <tbody>
        @foreach ($actividade as $a)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($a->created_at)->format('d/m/Y H:i') }}</td>
                <td>{{ $a->user?->name ?: 'Sistema' }}</td>
                <td><code>{{ $a->accao }}</code></td>
                <td>{{ $a->detalhe ?: '—' }}</td>
                <td>{{ $a->ip ?: '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $actividade->links() }}</div>
@endif
@endsection
