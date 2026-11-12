<nav class="flex flex-wrap gap-2 mb-6 text-sm">
    @foreach ([['admin.painel','Painel'],['admin.membros','Membros'],['admin.quotas','Quotas'],['admin.requerimentos','Requerimentos'],['admin.documentos','Documentos'],['admin.noticias','Notícias'],['admin.eventos','Eventos'],['admin.newsletter','Newsletter'],['admin.mensagens','Mensagens']] as [$rota, $rotulo])
        <a href="{{ route($rota) }}" class="rounded px-3 py-1 border {{ request()->routeIs($rota, $rota.'.*') ? 'bg-cda-preto text-white border-cda-preto' : 'bg-white border-zinc-300' }}">{{ $rotulo }}</a>
    @endforeach
</nav>
