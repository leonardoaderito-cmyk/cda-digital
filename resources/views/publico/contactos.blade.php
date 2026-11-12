@extends('layouts.app')
@section('titulo', 'Contactos')
@section('conteudo')
<h1>Contactos</h1>
<div class="flex flex-wrap gap-8">
    <div class="flex-[1_1_16rem]">
        <div class="titulo-seccao"><h2>Sede</h2></div>
        <address class="not-italic">
            Edifício Central<br>
            Rua João Carlos Raposo Beirão, n.º 508, R/C<br>
            Maputo — Moçambique
        </address>
        <p class="mt-3"><strong>Telefones:</strong><br><a href="tel:+25821305504" class="hover:underline">+258 21 305 504</a><br><a href="tel:+25821305506" class="hover:underline">+258 21 305 506</a></p>
        <p class="mt-3"><strong>Delegações:</strong><br>Nampula (Norte), Beira (Centro) e Maputo (Sul).</p>
    </div>
    <form method="POST" action="{{ route('contactos.enviar') }}" class="flex-[2_1_24rem] bg-white border border-zinc-300 p-5 flex flex-col gap-4">
        @csrf
        <div class="titulo-seccao"><h2>Enviar mensagem</h2></div>
        @foreach ([['nome','Nome'],['email','Email'],['assunto','Assunto']] as [$c, $r])
            <label class="flex flex-col gap-1 text-sm">{{ $r }}
                <input name="{{ $c }}" value="{{ old($c) }}" class="border border-zinc-300 px-3 py-2">
            </label>
        @endforeach
        <label class="flex flex-col gap-1 text-sm">Mensagem
            <textarea name="mensagem" rows="5" class="border border-zinc-300 px-3 py-2">{{ old('mensagem') }}</textarea>
        </label>
        <button class="bg-cda-vermelho hover:bg-cda-preto text-white font-semibold px-5 py-2 self-start">Enviar</button>
    </form>
</div>
@endsection
