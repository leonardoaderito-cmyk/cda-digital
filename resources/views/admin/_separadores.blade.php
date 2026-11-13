{{-- Autor: Leonardo (leonardoaderito-cmyk) - separadores que filtram uma tabela pelo data-grupo das linhas.
     Espera $tabela (seletor, ex.: #tabela-quotas) e $separadores: [valor => [rotulo, total]]; o valor '' mostra tudo.
     Os separadores sem linhas ficam escondidos. --}}
<div class="admin-separadores" data-separadores="{{ $tabela }}" role="group" aria-label="{{ $rotulo ?? 'Filtrar a lista' }}">
    @foreach ($separadores as $valor => [$nome, $total])
        @continue($valor !== '' && ! $total)
        <button type="button" data-valor="{{ $valor }}" aria-pressed="{{ $valor === '' ? 'true' : 'false' }}">{{ $nome }} <span>{{ $total }}</span></button>
    @endforeach
</div>
