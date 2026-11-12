@extends('layouts.membro')
@section('titulo', 'Os meus dados')
@section('conteudo')
<div class="admin-cabecalho">
    <div><h1>Os meus dados</h1><p>Mantenha os seus contactos actualizados. É para o seu email que a CDA envia as ligações de acesso.</p></div>
</div>

<div class="admin-editor">
    <form method="POST" action="{{ route('membro.dados.actualizar') }}" class="admin-caixa">
        @csrf
        <h2>Contactos</h2>
        <div class="admin-form">
            <label class="largo">Email<input type="email" name="email" value="{{ old('email', $membro->email) }}" required maxlength="200" autocomplete="email"></label>
            <label>Telefone<input name="telefone" value="{{ old('telefone', $membro->telefone) }}" maxlength="40" autocomplete="tel"></label>
            <label class="largo">Entidade / empresa<input name="entidade" value="{{ old('entidade', $membro->entidade) }}" maxlength="160"></label>
            <label class="largo">Senha actual <small>(só é necessária se mudar o email)</small><input type="password" name="password_actual" autocomplete="current-password"></label>
            <div class="total"><button class="admin-botao">Guardar alterações</button></div>
        </div>
    </form>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>Registo na CDA</h2>
            <dl class="membro-ficha">
                <dt>Nome</dt><dd>{{ $membro->name }}</dd>
                <dt>Número de membro</dt><dd>{{ $membro->carteira ?: '—' }}</dd>
                @if ($registo)
                    <dt>Empresa</dt><dd>{{ $registo->empresa ?: '—' }}</dd>
                    <dt>Situação</dt><dd>{{ $registo->situacao ?: '—' }}</dd>
                    <dt>Província</dt><dd>{{ $registo->provincia ?: '—' }}</dd>
                    <dt>Delegação</dt><dd>{{ $registo->delegacao ?: '—' }}</dd>
                @endif
                <dt>Conta aprovada em</dt><dd>{{ $membro->aprovado_em?->format('d/m/Y') ?: '—' }}</dd>
            </dl>
            <p class="admin-nota">Estes dados são do registo oficial da CDA. Para os corrigir, faça um <a href="{{ route('membro.requerimentos') }}">requerimento de actualização de dados</a>.</p>
        </section>
    </aside>
</div>
@endsection
