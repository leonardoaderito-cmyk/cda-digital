@extends('layouts.admin')
@section('titulo', 'Utilizadores e acessos')
@section('conteudo')
<div class="admin-cabecalho"><div><h1>Utilizadores e acessos</h1><p>Defina responsabilidades e consulte os logs da administração.</p></div></div>

<div class="grid md:grid-cols-2 xl:grid-cols-5 gap-3 mb-7">
    @foreach([
        ['Administrador geral','Acesso total, utilizadores e níveis.'],
        ['Gestor de adesões','Aprova membros, contas e requerimentos.'],
        ['Gestor financeiro','Quotas, pagamentos e regularidade.'],
        ['Editor de conteúdos','Páginas, notícias, eventos e comunicação.'],
        ['Utilizador standard','Consulta membros e situação financeira.'],
    ] as [$titulo,$texto])
    <div class="bg-white border border-zinc-200 rounded p-4"><strong class="block text-sm">{{ $titulo }}</strong><span class="text-xs text-zinc-500">{{ $texto }}</span></div>
    @endforeach
</div>

<section class="bg-white border border-zinc-200 rounded-lg p-5 mb-8">
    <h2 class="text-lg font-semibold mb-1">Adicionar utilizador administrativo</h2>
    <p class="text-sm text-zinc-600 mb-4">Será enviada uma ligação para o utilizador definir a própria senha.</p>
    <form method="POST" action="{{ route('admin.utilizadores.criar') }}" class="flex flex-wrap gap-3 items-end">@csrf
        <label class="flex flex-col gap-1 text-sm flex-1 min-w-52">Nome<input name="name" value="{{ old('name') }}" required class="border border-zinc-300 rounded px-3 py-2"></label>
        <label class="flex flex-col gap-1 text-sm flex-1 min-w-52">Email<input type="email" name="email" value="{{ old('email') }}" required class="border border-zinc-300 rounded px-3 py-2"></label>
        <label class="flex flex-col gap-1 text-sm min-w-52">Nível<select name="nivel_acesso" class="border border-zinc-300 rounded px-3 py-2">@foreach($niveis as $valor=>$rotulo)<option value="{{ $valor }}">{{ $rotulo }}</option>@endforeach</select></label>
        <button class="bg-blue-950 text-white rounded px-4 py-2">Criar e enviar acesso</button>
    </form>
</section>

<h2 class="text-lg font-semibold mb-3">Contas administrativas</h2>
<div class="tabela mb-9">
    <div class="tabela-linha tabela-cabecalho"><div class="tabela-celula" style="flex-grow:2">Utilizador</div><div class="tabela-celula">Último acesso</div><div class="tabela-celula" style="flex-grow:2">Nível e estado</div><div class="tabela-celula">Credenciais</div></div>
    @foreach($utilizadores as $u)
    <div class="tabela-linha">
        <div class="tabela-celula" style="flex-grow:2"><strong>{{ $u->name }}</strong><small class="block text-zinc-500">{{ $u->email }}</small></div>
        <div class="tabela-celula">{{ $u->ultimo_login?->format('d/m/Y H:i') ?: 'Nunca entrou' }}</div>
        <div class="tabela-celula" style="flex-grow:2">
            <form method="POST" action="{{ route('admin.utilizadores.actualizar', $u) }}" class="flex flex-wrap gap-2 items-center">@csrf
                <select name="nivel_acesso" class="border border-zinc-300 rounded px-2 py-1">@foreach($niveis as $valor=>$rotulo)<option value="{{ $valor }}" @selected($u->nivel_acesso===$valor)>{{ $rotulo }}</option>@endforeach</select>
                <label class="inline-flex items-center gap-1 text-sm"><input type="checkbox" name="ativo" value="1" @checked($u->ativo)> Activo</label>
                <button class="text-blue-800 hover:underline">Guardar</button>
            </form>
        </div>
        <div class="tabela-celula"><form method="POST" action="{{ route('admin.utilizadores.credenciais', $u) }}">@csrf<button class="text-blue-800 hover:underline">{{ $u->credenciais_enviadas_em ? 'Reenviar acesso' : 'Enviar acesso' }}</button></form></div>
    </div>
    @endforeach
</div>

<h2 class="text-lg font-semibold mb-1">Logs de actividade</h2>
<p class="text-sm text-zinc-600 mb-3">Registo das alterações efectuadas pelos utilizadores administrativos.</p>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho"><div class="tabela-celula">Data</div><div class="tabela-celula" style="flex-grow:2">Utilizador</div><div class="tabela-celula" style="flex-grow:2">Acção</div><div class="tabela-celula">Alvo</div><div class="tabela-celula">IP</div></div>
    @forelse($actividade as $a)
    <div class="tabela-linha"><div class="tabela-celula">{{ IlluminateSupportCarbon::parse($a->created_at)->format('d/m/Y H:i') }}</div><div class="tabela-celula" style="flex-grow:2">{{ $a->user?->name ?: 'Sistema' }}</div><div class="tabela-celula" style="flex-grow:2"><code>{{ $a->accao }}</code></div><div class="tabela-celula">{{ $a->detalhe ?: '—' }}</div><div class="tabela-celula">{{ $a->ip ?: '—' }}</div></div>
    @empty<div class="tabela-linha"><div class="tabela-celula">Sem actividade registada.</div></div>@endforelse
</div>
<div class="mt-4">{{ $actividade->links() }}</div>
@endsection
