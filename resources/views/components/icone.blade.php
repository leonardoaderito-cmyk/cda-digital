{{-- Autor: Leonardo (leonardoaderito-cmyk) - icone Material Symbols (resources/icons). Uso: <x-icone nome="newspaper" /> ou <x-icone nome="newspaper" cheio />. --}}
@props(['nome', 'cheio' => false])
<svg {{ $attributes->merge(['class' => 'icone']) }} viewBox="0 -960 960 960" fill="currentColor" aria-hidden="true" focusable="false"><path d="{{ \App\Support\Icone::caminho($nome, (bool) $cheio) }}"/></svg>
