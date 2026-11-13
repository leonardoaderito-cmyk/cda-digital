<?php

namespace App\Services\Assistente;

use App\Models\Documento;
use App\Models\Evento;
use App\Models\MembroPublico;
use App\Models\Noticia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * Leonardo (leonardoaderito-cmyk): Assistente CDA - motor proprio do portal, sem servicos externos.
 *
 * Responde so com o que esta publicado no site: reconhece pedidos frequentes (contactos, noticias,
 * eventos, directorio de membros, listas do Centro Documental), escolhe as frases mais relevantes
 * dos conteudos encontrados e indica as fontes e os PDFs.
 */
class Assistente
{
    private const TIPOS = [
        'documento' => 'Documento',
        'pagina' => 'Página',
        'noticia' => 'Notícia',
        'evento' => 'Evento',
        'facto' => 'Informação institucional',
        'lista' => 'Centro Documental',
        'membro' => 'Directório de membros',
    ];

    /** Fontes mostradas por resposta. */
    private const MAX_FONTES = 6;

    /** Palavras de conversa que nao sao assunto ("ola", "obrigado", "ate logo"...). */
    private const CONVERSA = [
        'ola', 'oi', 'bom', 'boa', 'dia', 'tarde', 'noite', 'obrigado', 'obrigada', 'obrigadao', 'muito', 'ok', 'okay',
        'certo', 'tchau', 'adeus', 'ate', 'logo', 'breve', 'sim', 'nao', 'hello', 'hi', 'bem', 'tudo', 'esta', 'estas',
        'como', 'vai', 'vais', 'e', 'a', 'o', 'de', 'agradeco', 'perfeito', 'optimo', 'otimo', 'excelente', 'pronto',
    ];

    /** Inicio das palavras que pedem contactos da CDA. */
    private const CONTACTOS = [
        'contact', 'telefon', 'telemovel', 'ligar', 'numero', 'email', 'mail', 'correio', 'electronic', 'eletronic',
        'morada', 'endereco', 'localiza', 'fica', 'sede', 'escritorio', 'horario', 'abre', 'abert', 'fecha',
        'funciona', 'atendimento', 'delegac', 'nampula', 'beira', 'maputo', 'zona', 'norte', 'centro', 'sul', 'geral',
    ];

    public function __construct(private Indice $indice) {}

    /**
     * @param  array<int, array{papel:string, texto:string}>  $historico  mensagens anteriores
     * @return array{resposta:string, fontes:array, modo:string}
     */
    public function responder(string $pergunta, array $historico = []): array
    {
        $inicio = microtime(true);
        $pergunta = trim(preg_replace('/\s+/u', ' ', $pergunta));
        $historico = $this->limparHistorico($historico);

        $resultado = $this->conversa($pergunta)
            ?? $this->membros($pergunta)
            ?? $this->contactos($pergunta)
            ?? $this->noticias($pergunta)
            ?? $this->eventos($pergunta)
            ?? $this->pesquisa($pergunta, $historico);

        $this->registar($pergunta, $resultado, $inicio);

        return $resultado;
    }

    // ------------------------------------------------------------------ pesquisa no indice

    private function pesquisa(string $pergunta, array $historico): array
    {
        // Pergunta de seguimento ("e de 2024?", "e sobre o estatuto?") pesquisa tambem com a anterior
        $anterior = collect($historico)->where('papel', 'utilizador')->last()['texto'] ?? '';
        $pesquisa = $anterior && $this->eSeguimento($pergunta) ? $anterior.' '.$pergunta : $pergunta;
        $limite = max(3, (int) config('cda.assistente.trechos'));

        $lista = $this->listaDocumentos($pesquisa, $pergunta);
        $trechos = $lista ? collect() : $this->indice->procurar($pesquisa, $limite);

        // Erros de escrita: tenta de novo com as palavras do site mais parecidas
        $correccao = null;
        if (! $lista && ($corrigida = $this->indice->corrigir($pesquisa))) {
            $listaCorrigida = $this->listaDocumentos($corrigida, $corrigida);
            if ($listaCorrigida) {
                [$lista, $correccao] = [$listaCorrigida, $corrigida];
            } elseif ($trechos->isEmpty()) {
                $trechos = $this->indice->procurar($corrigida, $limite);
                $correccao = $trechos->isNotEmpty() ? $corrigida : null;
            }
        }
        $nota = $correccao ? 'Procurei por «'.$correccao.'».'."\n" : '';

        if ($lista) {
            // Pedido de uma categoria ("circulares de 2023"): a lista do Centro Documental e a resposta
            $fontes = collect([$lista['trecho']])->concat($lista['documentos'])->values()
                ->map(fn ($t, $i) => $this->fonte($t, $i + 1))->take(9)->all();

            return ['resposta' => $nota.$lista['trecho']['extra']['resumo'], 'fontes' => $fontes, 'modo' => 'resposta'];
        }

        if ($trechos->isEmpty()) {
            return ['resposta' => $this->semResultados(), 'fontes' => [], 'modo' => 'sem-resultados'];
        }

        $extracto = $this->extracto($pesquisa, $trechos);
        if ($extracto) {
            return ['resposta' => $nota.$extracto['resposta'], 'fontes' => $extracto['fontes'], 'modo' => 'resposta'];
        }

        $local = $this->local($trechos);

        return ['resposta' => $nota.$local['resposta'], 'fontes' => $local['fontes'], 'modo' => 'pesquisa'];
    }

    private function eSeguimento(string $pergunta): bool
    {
        $texto = Str::lower(Str::ascii($pergunta));
        $palavras = $this->indice->palavras($pergunta);

        return (bool) preg_match('/^(e|mas|entao|tambem|e quanto|e sobre)\b/', $texto)
            || ($palavras !== [] && collect($palavras)->every(fn ($p) => ctype_digit($p)));
    }

    /**
     * Resposta com as frases dos conteudos que mais batem com a pergunta (1 a 3, de fontes diferentes).
     *
     * @return array{resposta:string, fontes:array}|null
     */
    private function extracto(string $pergunta, Collection $trechos): ?array
    {
        $pesos = $this->indice->pesos($this->indice->palavras($pergunta));
        $total = array_sum($pesos);
        if ($total <= 0 || ! collect(array_keys($pesos))->contains(fn ($r) => ! ctype_digit((string) $r))) {
            return null;
        }
        // Palavras exactas da pergunta (mesmo as curtas, "ser", "como"): desempatam frases com o mesmo assunto
        preg_match_all('/[a-z0-9]{2,}/', Str::lower(Str::ascii($pergunta)), $m);
        $exactas = array_unique($m[0]);

        // Os factos institucionais sao poucos e certos: entram todos como candidatos, alem dos trechos encontrados
        $factos = collect($this->factos(true))->map(fn ($linha, $i) => [
            'titulo' => 'Informação institucional da CDA',
            'texto' => $linha,
            'tipo' => 'facto',
            'origem_id' => $i + 1,
            'url' => route('instituicao'),
            'extra' => [],
        ]);
        $candidatos = $trechos->concat($factos->reject(fn ($f) => $trechos->contains('texto', $f['texto'])))->values();

        $candidatas = [];
        foreach ($candidatos as $posicao => $t) {
            if ($this->eFicha($t)) {
                continue;
            }
            foreach ($this->frases($t) as $frase) {
                $palavras = $this->palavrasDe($frase);
                $peso = $this->cobertura($palavras, $pesos);
                if ($peso === null || $peso / $total < 0.5) {
                    continue;
                }
                $candidatas[] = [
                    'frase' => $frase,
                    'trecho' => $t,
                    'pontos' => $peso * ($t['tipo'] === 'facto' ? 1.25 : 1.0)
                        + count(array_intersect($exactas, $palavras)) * 0.15
                        + max(0, count($trechos) - $posicao) * 0.01,
                ];
            }
        }
        if (! $candidatas) {
            return null;
        }

        usort($candidatas, fn ($a, $b) => $b['pontos'] <=> $a['pontos']);
        $melhor = $candidatas[0]['pontos'];
        $escolhidas = [];
        $usadas = [];
        foreach ($candidatas as $c) {
            $chave = $this->chave($c['trecho']);
            // Uma frase por fonte; dos factos institucionais (todos uma so fonte) podem vir duas
            if (($usadas[$chave] ?? 0) >= ($chave === 'facto' ? 2 : 1) || $c['pontos'] < $melhor * 0.6) {
                continue;
            }
            $usadas[$chave] = ($usadas[$chave] ?? 0) + 1;
            $escolhidas[] = $c;
            if (count($escolhidas) === 3) {
                break;
            }
        }

        $ordem = [];
        $linhas = [];
        foreach ($escolhidas as $c) {
            $linhas[] = $c['frase'].' ['.$this->numerar($ordem, $c['trecho']).']';
        }

        // Documentos encontrados, ainda nao citados, cujo titulo e sobre o mesmo assunto: indicados com o PDF
        $documentos = $trechos
            ->filter(fn ($t) => $t['tipo'] === 'documento' && ! isset($usadas[$this->chave($t)]))
            ->filter(fn ($t) => ($this->cobertura($this->palavrasDe($t['titulo']), $pesos) ?? 0) / $total >= 0.5)
            ->unique(fn ($t) => $this->chave($t))->take(3);
        if ($documentos->isNotEmpty()) {
            $linhas[] = 'Documentos relacionados:';
            foreach ($documentos as $d) {
                $linhas[] = '- **'.$d['titulo'].'**'.$this->detalheDocumento($d).' ['.$this->numerar($ordem, $d).']';
            }
        }

        return ['resposta' => implode("\n", $linhas), 'fontes' => $this->fontesNumeradas($ordem)];
    }

    private function palavrasDe(string $texto): array
    {
        preg_match_all('/[a-z0-9]+/', Str::lower(Str::ascii($texto)), $m);

        return array_values(array_unique($m[0]));
    }

    /**
     * Soma dos pesos das raizes da pergunta presentes nas palavras.
     * Null se so batem numeros (ex.: o ano) ou, quando a pergunta tem varios assuntos, se so bate um deles.
     */
    private function cobertura(array $palavras, array $pesos): ?float
    {
        $peso = 0.0;
        $assuntos = 0;
        $totalAssuntos = 0;
        foreach ($pesos as $raiz => $p) {
            $raiz = (string) $raiz;
            $numero = ctype_digit($raiz);
            $totalAssuntos += $numero ? 0 : 1;
            $tem = $numero
                ? in_array($raiz, $palavras, true)
                : collect($palavras)->contains(fn ($w) => str_starts_with($w, $raiz));
            if ($tem) {
                $peso += $p;
                $assuntos += $numero ? 0 : 1;
            }
        }

        return $assuntos >= min(2, $totalAssuntos) && $assuntos > 0 ? $peso : null;
    }

    /** Frases de um trecho; os factos institucionais ficam inteiros. Titulos e perguntas nao contam. */
    private function frases(array $trecho): array
    {
        $texto = trim(preg_replace('/[ \t]+/u', ' ', $trecho['texto']));
        if ($trecho['tipo'] === 'facto') {
            return [$this->fecharFrase($texto)];
        }

        return collect(preg_split('/(?<=[.!?;:])\s+(?=[\p{Lu}0-9«"(])|\R+/u', $texto))
            ->map(fn ($f) => trim($f, " \t-•"))
            ->filter(fn ($f) => mb_strlen($f) >= 40 && mb_strlen($f) <= 420)
            ->reject(fn ($f) => str_ends_with($f, '?') || mb_strtoupper($f) === $f || str_word_count(Str::ascii($f)) < 7)
            ->map(fn ($f) => $this->fecharFrase($f))
            ->values()
            ->all();
    }

    private function fecharFrase(string $frase): string
    {
        $frase = rtrim($frase, ' ;:,');

        return preg_match('/[.!?]$/u', $frase) ? $frase : $frase.'.';
    }

    /** Sem frase que responda: lista dos conteudos encontrados, numerados como os cartoes. */
    private function local(Collection $trechos): array
    {
        $ordem = [];
        $linhas = ['Encontrei estes conteúdos do site relacionados com a sua pergunta:'];

        foreach ($trechos->unique(fn ($t) => $this->chave($t))->take(5) as $t) {
            $n = ' ['.$this->numerar($ordem, $t).']';
            $resumo = Str::limit(trim(preg_replace('/\s+/u', ' ', $t['texto'])), 220);
            $linhas[] = match (true) {
                $t['tipo'] === 'facto' => '- '.$resumo.$n,
                // Ficha de um PDF sem texto: basta o titulo e os dados do documento
                $this->eFicha($t) => '- **'.$t['titulo'].'**'.$this->detalheDocumento($t).' — PDF disponível abaixo.'.$n,
                default => '- **'.$t['titulo'].'**: '.$resumo.$n,
            };
        }
        $linhas[] = 'Abra as fontes abaixo para ver o conteúdo completo.';

        return ['resposta' => implode("\n", $linhas), 'fontes' => $this->fontesNumeradas($ordem)];
    }

    private function semResultados(): string
    {
        return 'Não encontrei essa informação no site da CDA. Experimente outras palavras (por exemplo o tipo de documento, '
            .'o assunto ou o ano), consulte o Centro Documental ou contacte a CDA: geral@cda-mz.org · +258 21 305 504.';
    }

    // ------------------------------------------------------------------ pedidos frequentes

    /** Cumprimentos, agradecimentos, despedidas e "o que podes fazer?". */
    private function conversa(string $pergunta): ?array
    {
        $texto = Str::lower(Str::ascii($pergunta));
        $ajuda = preg_match('/\b(o )?que (e que )?(podes|pode|sabes|sabe|consegues|consegue) fazer|como (me )?(podes|pode) ajudar|\bquem (es|e) (tu|voce)\b|^(ajuda|menu|help)\W*$/', $texto);

        preg_match_all('/[a-z0-9]+/', $texto, $m);
        $resto = array_diff($m[0], self::CONVERSA);
        if (! $ajuda && ($m[0] === [] || $resto !== [])) {
            return null;
        }

        $resposta = match (true) {
            (bool) $ajuda => "Sou o assistente do portal da CDA e respondo com o que está publicado no site. Posso ajudar a:\n"
                ."- encontrar documentos e abrir os PDFs (ex.: «circulares de 2023», «estatuto da CDA»);\n"
                ."- responder sobre a CDA e a profissão de despachante aduaneiro;\n"
                ."- mostrar as últimas notícias e os próximos eventos;\n"
                ."- indicar contactos, morada e horário;\n"
                .'- procurar um despachante registado no directório de membros.',
            (bool) preg_match('/obrigad|agradec/', $texto) => 'De nada! Se precisar de mais alguma informação do site da CDA, é só perguntar.',
            (bool) preg_match('/tchau|adeus|ate (logo|breve)/', $texto) => 'Até breve! Estou aqui sempre que precisar.',
            default => 'Olá! Sou o Assistente CDA. Posso encontrar documentos e PDFs, notícias, eventos, contactos e '
                .'informação sobre a CDA e a profissão de despachante aduaneiro. O que procura?',
        };

        return ['resposta' => $resposta, 'fontes' => [], 'modo' => 'resposta'];
    }

    /** Morada, telefones, e-mail, horario e delegacoes, a partir dos factos institucionais. */
    private function contactos(string $pergunta): ?array
    {
        $palavras = collect($this->indice->palavras($pergunta));
        $eContacto = fn ($p) => collect(self::CONTACTOS)->contains(fn ($c) => str_starts_with($p, $c));
        if ($palavras->isEmpty() || ! $palavras->contains($eContacto) || $palavras->reject($eContacto)->isNotEmpty()) {
            return null;
        }

        $factos = $this->factos();
        $pede = fn (string $padrao) => $palavras->contains(fn ($p) => preg_match('/^('.$padrao.')/', $p));
        $geral = ['morada', 'telefones', 'horario'];
        $chaves = array_keys(array_filter([
            'morada' => $pede('morada|endereco|localiza|fica|sede|escritorio'),
            'telefones' => $pede('telefon|telemovel|ligar|numero|email|mail|correio|electronic|eletronic'),
            'horario' => $pede('horario|abre|abert|fecha|funciona|atendimento'),
            'delegacoes' => $pede('delegac|nampula|beira|zona|norte|centro|sul'),
        ]));
        // "Contactos" (ou nada de concreto) da a morada, os telefones e o horario
        if (! $chaves || $pede('contact')) {
            $chaves = array_values(array_unique(array_merge($geral, $chaves)));
        }

        $linhas = collect($chaves)->map(fn ($c) => $factos[$c] ?? null)->filter()->map(fn ($l) => '- '.$l)->all();
        if (! $linhas) {
            return null;
        }
        $fonte = $this->fonte([
            'titulo' => 'Contactos da CDA',
            'tipo' => 'pagina',
            'origem_id' => 0,
            'url' => route('contactos'),
            'extra' => [],
        ], 1);
        $fonte['chave'] = 'contactos';

        return [
            'resposta' => "Contactos da CDA [1]:\n".implode("\n", $linhas)."\nTambém pode escrever à CDA pelo formulário da página de contactos.",
            'fontes' => [$fonte],
            'modo' => 'resposta',
        ];
    }

    /**
     * Factos institucionais (resources/assistente/factos.txt): todas as linhas, ou so as de contacto
     * por assunto (morada, telefones, horario, delegacoes).
     */
    private function factos(bool $todos = false): array
    {
        $ficheiro = resource_path('assistente/factos.txt');
        if (! is_file($ficheiro)) {
            return [];
        }
        $linhas = collect(preg_split('/\R/', File::get($ficheiro)))
            ->map(fn ($l) => trim(ltrim(trim($l), '-')))
            ->filter(fn ($l) => $l !== '' && ! str_starts_with($l, 'FACTOS'))
            ->values();
        if ($todos) {
            return $linhas->all();
        }

        return $linhas
            ->mapWithKeys(function ($l) {
                $inicio = Str::lower(Str::ascii(Str::before($l, ':')));

                return in_array($inicio, ['morada', 'telefones', 'horario', 'delegacoes'], true) ? [$inicio => $l] : [];
            })
            ->all();
    }

    /** "Ultimas noticias": as 5 mais recentes publicadas. */
    private function noticias(string $pergunta): ?array
    {
        if (! $this->pedidoSimples($pergunta, ['noticia', 'novidade'], ['ultim', 'recent', 'nov', 'actual', 'atual', 'flash', 'hoje', 'semana', 'publicad', 'lista'])) {
            return null;
        }
        $noticias = Noticia::where('publicada', true)->orderByDesc('data')->orderByDesc('id')->limit(5)->get();
        if ($noticias->isEmpty()) {
            return ['resposta' => 'Ainda não há notícias publicadas no site.', 'fontes' => [], 'modo' => 'sem-resultados'];
        }

        $ordem = [];
        $linhas = ['Estas são as notícias mais recentes do site:'];
        foreach ($noticias as $n) {
            $trecho = [
                'titulo' => $n->titulo,
                'texto' => '',
                'tipo' => 'noticia',
                'origem_id' => $n->id,
                'url' => route('noticia', $n->slug),
                'extra' => array_filter(['data' => $n->data?->format('d/m/Y'), 'categoria' => $n->categoria]),
            ];
            $linhas[] = '- **'.$n->titulo.'**'.($n->data ? ' ('.$n->data->format('d/m/Y').')' : '').' ['.$this->numerar($ordem, $trecho).']';
        }

        return ['resposta' => implode("\n", $linhas), 'fontes' => $this->fontesNumeradas($ordem), 'modo' => 'resposta'];
    }

    /** "Proximos eventos": os marcados a partir de hoje, ou os ultimos realizados. */
    private function eventos(string $pergunta): ?array
    {
        if (! $this->pedidoSimples($pergunta, ['evento', 'actividade', 'atividade', 'agenda'], ['proxim', 'futur', 'ultim', 'recent', 'agend', 'marcad', 'nov', 'actual', 'atual', 'lista', 'realizad'])) {
            return null;
        }
        $proximos = Evento::where('activo', true)->whereDate('data_evento', '>=', today())->orderBy('data_evento')->limit(5)->get();
        $eventos = $proximos->isNotEmpty() ? $proximos
            : Evento::where('activo', true)->orderByDesc('data_evento')->limit(5)->get();
        if ($eventos->isEmpty()) {
            return ['resposta' => 'Não há eventos publicados no site.', 'fontes' => [], 'modo' => 'sem-resultados'];
        }

        $ordem = [];
        $linhas = [$proximos->isNotEmpty() ? 'Próximos eventos da CDA:' : 'Não há eventos marcados de momento. Estes foram os mais recentes:'];
        foreach ($eventos as $e) {
            $trecho = [
                'titulo' => $e->titulo,
                'texto' => '',
                'tipo' => 'evento',
                'origem_id' => $e->id,
                'url' => route('evento', $e->slug),
                'extra' => array_filter(['data' => $e->data_evento?->format('d/m/Y')]),
            ];
            $detalhe = collect([$e->data_evento?->format('d/m/Y'), $e->local])->filter()->implode(', ');
            $linhas[] = '- **'.$e->titulo.'**'.($detalhe ? ' ('.$detalhe.')' : '').' ['.$this->numerar($ordem, $trecho).']';
        }

        return ['resposta' => implode("\n", $linhas), 'fontes' => $this->fontesNumeradas($ordem), 'modo' => 'resposta'];
    }

    /** A pergunta tem uma das palavras principais e, alem delas, so palavras sem assunto. */
    private function pedidoSimples(string $pergunta, array $principais, array $acessorias): bool
    {
        $palavras = collect($this->indice->palavras($pergunta))->reject(fn ($p) => ctype_digit($p));
        $comeca = fn (array $lista) => fn ($p) => collect($lista)->contains(fn ($c) => str_starts_with($p, $c));

        return $palavras->contains($comeca($principais))
            && $palavras->reject($comeca($principais))->reject($comeca($acessorias))->isEmpty();
    }

    /** "O despachante X esta registado?": procura no directorio publico de membros. */
    private function membros(string $pergunta): ?array
    {
        $texto = Str::lower(Str::ascii($pergunta));
        if (! preg_match('/\b(registad|inscrit|dire(c)?torio|carta\b)|(procur|encontr|verific)\w* (o |a |um |uma )?(despachante|membro)/', $texto)) {
            return null;
        }

        $ignorar = '/^(despachant|aduaneir|registad|inscrit|directori|diretori|membr|carta|numero|procur|encontr|verific|lista|camara|esta|estao|consta|quero)/';
        $termos = collect($this->indice->palavras($pergunta))->reject(fn ($p) => preg_match($ignorar, $p))->values();
        $directorio = route('directorio', array_filter(['q' => $termos->implode(' ') ?: null]));
        $cartao = function (int $n) use ($termos, $directorio) {
            $fonte = $this->fonte([
                'titulo' => 'Directório de membros'.($termos->isNotEmpty() ? ' — «'.$termos->implode(' ').'»' : ''),
                'tipo' => 'membro',
                'origem_id' => 0,
                'url' => $directorio,
                'extra' => [],
            ], $n);
            $fonte['chave'] = 'directorio';

            return $fonte;
        };

        if ($termos->isEmpty()) {
            return [
                'resposta' => 'No **Directório de membros** pode procurar um despachante pelo nome, pela empresa ou pelo número da carteira profissional [1].',
                'fontes' => [$cartao(1)],
                'modo' => 'resposta',
            ];
        }

        $membros = MembroPublico::query()
            ->where(function ($q) use ($termos) {
                foreach ($termos as $t) {
                    $q->where(fn ($w) => $w->where('nome', 'like', '%'.$t.'%')->orWhere('empresa', 'like', '%'.$t.'%')->orWhere('n_carta', 'like', '%'.$t.'%'));
                }
            })
            ->orderByDesc('activo')->orderBy('nome')->limit(6)->get();

        if ($membros->isEmpty()) {
            return [
                'resposta' => 'Não encontrei nenhum despachante com «'.$termos->implode(' ').'» no directório público de membros. '
                    .'Confirme o nome ou procure no directório [1]. Em caso de dúvida, contacte a CDA: geral@cda-mz.org.',
                'fontes' => [$cartao(1)],
                'modo' => 'sem-resultados',
            ];
        }

        $linhas = [($membros->count() === 1 ? 'Encontrei este despachante' : 'Encontrei estes despachantes').' no directório de membros [1]:'];
        foreach ($membros->take(5) as $m) {
            $dados = collect([
                $m->n_carta ? 'carteira n.º '.$m->n_carta : null,
                $m->empresa,
                $m->provincia ?: $m->delegacao,
                $m->situacao ?: ($m->activo ? 'activo' : 'inactivo'),
            ])->filter()->implode(' · ');
            $linhas[] = '- **'.$m->nome.'**'.($dados ? ' — '.$dados : '');
        }
        if ($membros->count() > 5) {
            $linhas[] = 'Há mais resultados no directório.';
        }

        return ['resposta' => implode("\n", $linhas), 'fontes' => [$cartao(1)], 'modo' => 'resposta'];
    }

    // ------------------------------------------------------------------ Centro Documental

    /**
     * Lista de documentos quando a pergunta nomeia uma categoria do Centro Documental (e talvez um ano).
     *
     * @return array{trecho:array, documentos:Collection}|null
     */
    private function listaDocumentos(string $pergunta, string $actual = ''): ?array
    {
        $texto = Str::lower(Str::ascii($pergunta));
        preg_match_all('/[a-z]+/', $texto, $palavras);

        // Raiz de cada palavra: "circular" -> "circul", "ordem" -> "orde", "boletim" -> "boleti"
        $raiz = fn (string $p) => strlen($p) >= 7 ? substr($p, 0, 6) : substr($p, 0, max(3, strlen($p) - 1));

        $categoria = Documento::query()->whereNotNull('tipo')->distinct()->pluck('tipo')
            ->reject(fn ($c) => Str::lower(Str::ascii($c)) === 'outro')
            ->first(function ($c) use ($palavras, $raiz) {
                $primeira = $raiz(Str::before(Str::lower(Str::ascii($c)), ' '));

                return collect($palavras[0])->contains(fn ($p) => str_starts_with($p, $primeira));
            });
        if (! $categoria) {
            return null;
        }

        // So e um pedido de lista quando nao ha um assunto concreto alem do tipo e do ano
        // ("circulares de 2023" sim; "circular sobre cobrança de taxas" vai para a pesquisa normal)
        $raizesTipo = collect(preg_split('/\s+/', Str::lower(Str::ascii($categoria))))->filter(fn ($p) => strlen($p) >= 3)->map($raiz);
        $assunto = collect($this->indice->palavras($pergunta))
            ->reject(fn ($p) => ctype_digit($p) || $raizesTipo->contains(fn ($r) => str_starts_with($p, $r)))
            ->reject(fn ($p) => in_array($p, ['ultimo', 'ultima', 'ultimos', 'ultimas', 'recente', 'recentes', 'novo', 'novos', 'nova', 'novas', 'lista', 'todos', 'todas', 'ano'], true));
        if ($assunto->isNotEmpty()) {
            return null;
        }

        // O ano da pergunta actual vence o da anterior ("e de 2023?")
        $padraoAno = '/\b(19[89]\d|20\d{2})\b/';
        $ano = preg_match($padraoAno, $actual, $m) || preg_match($padraoAno, $texto, $m) ? (int) $m[1] : null;
        $documentos = Documento::where('tipo', $categoria)
            ->when($ano, fn ($q) => $q->where('ano', $ano))
            ->orderByDesc('ano')->orderByDesc('id')
            ->limit(8)->get();
        $total = Documento::where('tipo', $categoria)->when($ano, fn ($q) => $q->where('ano', $ano))->count();
        $anos = Documento::where('tipo', $categoria)->whereNotNull('ano')->distinct()->orderBy('ano')->pluck('ano');
        $descricao = $categoria.($ano ? ' de '.$ano : '');

        $texto = $total
            ? "Centro Documental: existem {$total} documento(s) da categoria {$descricao}".($total > 8 ? ' (mostram-se os 8 mais recentes)' : '').': '
                .$documentos->map(fn ($d) => $d->titulo.($d->ano ? ' ('.$d->ano.')' : '').($d->status === 'revogado' ? ' [revogado]' : ''))->implode('; ').'.'
            : "Centro Documental: não existe nenhum documento da categoria {$descricao}."
                .($anos->isNotEmpty() ? ' Anos com documentos desta categoria: '.$anos->implode(', ').'.' : '');

        return [
            'trecho' => [
                'titulo' => 'Centro Documental — '.$descricao,
                'texto' => $texto,
                'tipo' => 'lista',
                'origem_id' => 0,
                'url' => route('documentos', array_filter(['tipo' => $categoria, 'ano' => $ano])),
                'extra' => [
                    'resumo' => $total
                        ? "Encontrei **{$total}** ".($total === 1 ? 'documento' : 'documentos')." do tipo **{$descricao}**"
                            .($total > 8 ? '; seguem os 8 mais recentes.' : '.').' Abra o PDF que pretende ou veja a lista completa no Centro Documental.'
                        : "Não há documentos do tipo **{$descricao}** no Centro Documental."
                            .($anos->isNotEmpty() ? ' Existem deste tipo nos anos: '.$anos->implode(', ').'.' : ''),
                ],
            ],
            'documentos' => $documentos->map(fn (Documento $d) => [
                'titulo' => $d->titulo,
                'texto' => $d->titulo.'.',
                'tipo' => 'documento',
                'origem_id' => $d->id,
                'url' => route('documentos', ['q' => Str::limit($d->titulo, 80, '')]),
                'extra' => array_filter([
                    'pdf' => route('documento.abrir', $d),
                    'ano' => $d->ano,
                    'categoria' => $d->tipo ?: $d->categoria,
                    'status' => $d->status,
                ]),
            ]),
        ];
    }

    // ------------------------------------------------------------------ fontes

    /** Ficha de um documento (titulo, tipo, ano...) e nao texto do PDF. */
    private function eFicha(array $trecho): bool
    {
        return $trecho['tipo'] === 'documento' && str_starts_with($trecho['texto'], $trecho['titulo']);
    }

    private function detalheDocumento(array $trecho): string
    {
        $d = collect([$trecho['extra']['categoria'] ?? null, $trecho['extra']['ano'] ?? null])->filter()->implode(', ');

        return $d ? ' ('.$d.')' : '';
    }

    /** Os factos institucionais contam como uma so fonte. */
    private function chave(array $trecho): string
    {
        return $trecho['tipo'] === 'facto' ? 'facto' : $trecho['tipo'].':'.$trecho['origem_id'];
    }

    /** Numero da fonte de um trecho; a primeira citada e a [1], e assim por diante. */
    private function numerar(array &$ordem, array $trecho): int
    {
        $chave = $this->chave($trecho);
        if (! isset($ordem[$chave])) {
            $ordem[$chave] = $trecho;
        }

        return array_search($chave, array_keys($ordem), true) + 1;
    }

    private function fontesNumeradas(array $ordem): array
    {
        return collect(array_values($ordem))->take(self::MAX_FONTES)->map(fn ($t, $i) => $this->fonte($t, $i + 1))->all();
    }

    private function fonte(array $t, int $n): array
    {
        return [
            'n' => $n,
            'chave' => $this->chave($t),
            'titulo' => $t['tipo'] === 'facto' ? 'Informação institucional da CDA' : $t['titulo'],
            'tipo' => $t['tipo'],
            'rotulo' => self::TIPOS[$t['tipo']] ?? 'Conteúdo',
            'url' => $t['url'],
            'pdf' => $t['extra']['pdf'] ?? null,
            'detalhe' => collect([$t['extra']['data'] ?? null, $t['extra']['ano'] ?? null, $t['extra']['categoria'] ?? null])
                ->filter()->unique()->implode(' · '),
            'revogado' => ($t['extra']['status'] ?? '') === 'revogado',
        ];
    }

    // ------------------------------------------------------------------ apoio

    private function limparHistorico(array $historico): array
    {
        return collect($historico)
            ->filter(fn ($m) => is_array($m) && in_array($m['papel'] ?? null, ['utilizador', 'assistente'], true) && filled($m['texto'] ?? null))
            ->map(fn ($m) => ['papel' => $m['papel'], 'texto' => Str::limit(trim((string) $m['texto']), 1200)])
            ->take(-6)
            ->values()
            ->all();
    }

    private function registar(string $pergunta, array $resultado, float $inicio): void
    {
        try {
            DB::table('assistente_perguntas')->insert([
                'pergunta' => Str::limit($pergunta, 495),
                'modo' => $resultado['modo'],
                'fontes' => count($resultado['fontes']),
                'duracao_ms' => (int) ((microtime(true) - $inicio) * 1000),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // O registo e so estatistico
        }
    }
}
