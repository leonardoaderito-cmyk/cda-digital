{{-- Autor: Leonardo (leonardoaderito-cmyk) - aviso de cookies; o JS mostra-o enquanto nao houver escolha guardada --}}
<div class="cookies" data-cookies hidden role="region" aria-label="Aviso de cookies">
    <div class="cookies-conteudo">
        <div class="cookies-texto">
            <strong>Este portal utiliza cookies</strong>
            <p>Usamos cookies essenciais para manter a sessão iniciada e proteger os formulários. Não usamos cookies de publicidade.
                @if (\App\Models\Pagina::estaActiva('cookies'))<a href="{{ route('cookies') }}">Política de cookies</a>@endif
            </p>
        </div>
        <div class="cookies-botoes">
            <button type="button" data-cookies-escolha="aceite" class="cookies-aceitar">Aceitar</button>
            <button type="button" data-cookies-escolha="essenciais" class="cookies-recusar">Só essenciais</button>
        </div>
    </div>
</div>
