{{-- Autor: Leonardo (leonardoaderito-cmyk) - mega rodape --}}
<footer class="mega-rodape">
    <div class="rodape-topo">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-3">
            <strong class="text-lg">Portal oficial da Câmara dos Despachantes Aduaneiros</strong>
            <a href="#topo" class="rodape-subir">Voltar ao topo &uarr;</a>
        </div>
    </div>
    <div class="max-w-6xl mx-auto px-4 py-10 flex flex-wrap gap-10">
        <div class="rodape-col rodape-marca">
            <div class="flex items-center gap-3 mb-3">
                <img src="{{ asset('img/cda-logo.jpg') }}" alt="CDA" class="h-14 w-14 rounded-full bg-white object-cover">
                <strong class="text-lg leading-tight">Câmara dos Despachantes Aduaneiros de Moçambique</strong>
            </div>
            <p>Instituição de direito público, criada pela Lei n.º 4/2011, de 11 de Janeiro, que representa os despachantes aduaneiros.</p>
        </div>

        <div class="rodape-col">
            <h3>A CDA</h3>
            <ul>
                @if (\App\Models\Pagina::estaActiva('instituicao'))<li><a href="{{ route('instituicao') }}">Quem somos</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('historia'))<li><a href="{{ route('historia') }}">História</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('orgaos'))<li><a href="{{ route('orgaos') }}">Órgãos sociais</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('estatutos'))<li><a href="{{ route('estatutos') }}">Estatutos</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('parceiros'))<li><a href="{{ route('parceiros') }}">Parceiros</a></li>@endif
            </ul>
        </div>

        <div class="rodape-col">
            <h3>Documentação</h3>
            <ul>
                @if (\App\Models\Pagina::estaActiva('documentos'))<li><a href="{{ route('documentos', ['tipo' => 'Lei']) }}">Legislação</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('documentos'))<li><a href="{{ route('documentos', ['tipo' => 'Circular']) }}">Circulares</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('documentos'))<li><a href="{{ route('documentos', ['tipo' => 'Comunicado']) }}">Comunicados</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('documentos'))<li><a href="{{ route('documentos', ['tipo' => 'Relatório']) }}">Relatórios</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('documentos'))<li><a href="{{ route('documentos') }}">Todos os documentos</a></li>@endif
            </ul>
        </div>

        <div class="rodape-col">
            <h3>Membros</h3>
            <ul>
                @if (\App\Models\Pagina::estaActiva('directorio'))<li><a href="{{ route('directorio') }}">Directório</a></li>@endif
                <li><a href="{{ route('login') }}">Entrar</a></li>
                <li><a href="{{ route('registo') }}">Criar conta</a></li>
                @if (\App\Models\Pagina::estaActiva('noticias'))<li><a href="{{ route('noticias') }}">Notícias</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('eventos'))<li><a href="{{ route('eventos') }}">Eventos</a></li>@endif
                @if (\App\Models\Pagina::estaActiva('actividades'))<li><a href="{{ route('actividades') }}">Actividades</a></li>@endif
            </ul>
        </div>

        <div class="rodape-col rodape-contacto">
            <h3>Contactos</h3>
            <address class="not-italic">
                Edifício Central<br>
                Rua João Carlos Raposo Beirão, n.º 508, R/C<br>
                Maputo — Moçambique
            </address>
            <p class="mt-3"><a href="tel:+25821305504">+258 21 305 504</a><br><a href="tel:+25821305506">+258 21 305 506</a></p>
            <p class="mt-3 text-zinc-300">Delegações: Nampula (Norte), Beira (Centro) e Maputo (Sul).</p>
            @if (\App\Models\Pagina::estaActiva('contactos'))<a href="{{ route('contactos') }}" class="rodape-botao">Enviar mensagem</a>@endif
        </div>
    </div>
    <div class="rodape-newsletter">
        <div class="max-w-6xl mx-auto px-4 py-5 flex flex-wrap items-center gap-4">
            <div class="flex-[1_1_18rem]">
                <strong class="text-white text-lg">Newsletter CDA</strong>
                <p class="text-zinc-300 text-sm">Receba notícias, eventos, circulares e oportunidades relevantes para o sector aduaneiro.</p>
            </div>
            <form method="POST" action="{{ route('newsletter.subscrever') }}" class="flex flex-wrap gap-2 flex-[2_1_28rem]">
                @csrf
                <label class="sr-only" for="newsletter-nome-rodape">Nome</label>
                <input id="newsletter-nome-rodape" name="nome" maxlength="160" placeholder="Nome (opcional)" class="flex-1 min-w-40 rounded px-3 py-2 text-zinc-900 bg-white">
                <label class="sr-only" for="newsletter-email-rodape">Email</label>
                <input id="newsletter-email-rodape" type="email" name="email" required maxlength="200" placeholder="O seu email" class="flex-[2_1_14rem] rounded px-3 py-2 text-zinc-900 bg-white">
                <button class="bg-cda-vermelho text-white font-semibold rounded px-5 py-2 hover:bg-cda-vermelho-escuro">Subscrever</button>
            </form>
        </div>
    </div>
    <div class="rodape-base">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap justify-between gap-2">
            <span>&copy; {{ date('Y') }} Câmara dos Despachantes Aduaneiros de Moçambique. Todos os direitos reservados.</span>
            <span>@if (\App\Models\Pagina::estaActiva('cookies'))<a href="{{ route('cookies') }}" class="hover:underline">Política de cookies</a> &middot; @endif<button type="button" data-cookies-abrir class="hover:underline">Definições de cookies</button></span>
        </div>
    </div>
</footer>
