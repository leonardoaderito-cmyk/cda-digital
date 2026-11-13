{{-- Capa de uma edicao: a imagem carregada ou, sem imagem, uma capa simples com o titulo --}}
@if ($revista->capa)
    <img src="{{ $revista->capa }}" alt="Capa: {{ $revista->nome() }}" loading="lazy">
@else
    <span class="revista-sem-capa"><small>Revista</small><strong>{{ $revista->titulo }}</strong>@if ($revista->edicao)<em>{{ $revista->edicao }}</em>@endif</span>
@endif
<span class="revista-abrir" aria-hidden="true"><x-icone nome="picture_as_pdf" /> Abrir PDF</span>
