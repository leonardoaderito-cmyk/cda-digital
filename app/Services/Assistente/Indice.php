<?php

namespace App\Services\Assistente;

use App\Models\Documento;
use App\Models\Evento;
use App\Models\Noticia;
use App\Models\Pagina;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Throwable;

// Leonardo (leonardoaderito-cmyk): indice de pesquisa do Assistente CDA sobre o conteudo publico do site
class Indice
{
    private const TABELA = 'assistente_trechos';

    private const TAMANHO_TRECHO = 900;

    private const SOBREPOSICAO = 150;

    /** Limite de texto lido por PDF (os anexos longos repetem-se e so encheriam o indice). */
    private const MAX_TEXTO_PDF = 120000;

    /** Palavras demasiado comuns para ajudar a pesquisa. */
    private const PALAVRAS_VAZIAS = [
        'a', 'ao', 'aos', 'as', 'com', 'como', 'da', 'das', 'de', 'do', 'dos', 'e', 'em', 'na', 'nas', 'no', 'nos',
        'o', 'os', 'ou', 'para', 'pela', 'pelas', 'pelo', 'pelos', 'por', 'que', 'qual', 'quais', 'quando', 'quem',
        'se', 'sem', 'sobre', 'um', 'uma', 'uns', 'umas', 'eu', 'me', 'meu', 'minha', 'voce', 'voces', 'ser', 'ter',
        'tem', 'ha', 'esta', 'este', 'isso', 'isto', 'ja', 'mais', 'muito', 'onde', 'pode', 'posso', 'preciso',
        'quero', 'gostaria', 'saber', 'favor', 'ola', 'bom', 'dia', 'boa', 'tarde', 'noite', 'obrigado', 'obrigada',
        'sao', 'foi', 'existe', 'existem', 'algum', 'alguma', 'cda', 'camara', 'site', 'portal', 'mostra', 'mostrar',
        'encontrar', 'procurar', 'procuro', 'diga', 'dizer', 'fale', 'falar', 'explica', 'explicar', 'ver', 'dar',
        'documento', 'documentos', 'pdf', 'ficheiro', 'informacao', 'informacoes', 'the', 'and',
    ];

    public function disponivel(): bool
    {
        try {
            return DB::connection()->getDriverName() === 'sqlite'
                && DB::selectOne('SELECT name FROM sqlite_master WHERE name = ?', [self::TABELA]) !== null;
        } catch (Throwable) {
            return false;
        }
    }

    /** Raiz aproximada de uma palavra ja normalizada (sem acentos, minusculas). */
    public static function raiz(string $palavra): string
    {
        return strlen($palavra) > 6 ? substr($palavra, 0, max(5, strlen($palavra) - 3)) : $palavra;
    }

    /**
     * Refaz o indice todo.
     *
     * @return array{trechos:int, documentos:int, documentos_com_texto:int, paginas:int, noticias:int, eventos:int}
     */
    public function reconstruir(): array
    {
        $estatisticas = ['trechos' => 0, 'documentos' => 0, 'documentos_com_texto' => 0, 'paginas' => 0, 'noticias' => 0, 'eventos' => 0];

        DB::transaction(function () use (&$estatisticas) {
            DB::table(self::TABELA)->delete();
            $estatisticas['trechos'] += $this->indexarFactos();

            foreach (Pagina::where('tipo', 'conteudo')->where('activa', true)->get() as $pagina) {
                $estatisticas['trechos'] += $this->indexarPagina($pagina);
                $estatisticas['paginas']++;
            }
            foreach (Noticia::where('publicada', true)->get() as $noticia) {
                $estatisticas['trechos'] += $this->indexarNoticia($noticia);
                $estatisticas['noticias']++;
            }
            foreach (Evento::where('activo', true)->get() as $evento) {
                $estatisticas['trechos'] += $this->indexarEvento($evento);
                $estatisticas['eventos']++;
            }
            foreach (Documento::all() as $documento) {
                $n = $this->indexarDocumento($documento);
                $estatisticas['trechos'] += $n;
                $estatisticas['documentos']++;
                // Mais de um trecho quer dizer que o PDF tinha texto alem da ficha
                $estatisticas['documentos_com_texto'] += $n > 1 ? 1 : 0;
            }
        });

        return $estatisticas;
    }

    /** Actualiza um conteudo depois de criado, editado ou apagado na administracao. */
    public function actualizar(object $modelo, bool $apagado = false): void
    {
        if (! $this->disponivel()) {
            return;
        }
        $tipo = $this->tipoDe($modelo);
        if (! $tipo) {
            return;
        }

        try {
            DB::transaction(function () use ($modelo, $tipo, $apagado) {
                $this->remover($tipo, $modelo->getKey());
                if ($apagado) {
                    return;
                }
                match ($tipo) {
                    'documento' => $this->indexarDocumento($modelo),
                    'pagina' => $modelo->tipo === 'conteudo' && $modelo->activa ? $this->indexarPagina($modelo) : 0,
                    'noticia' => $modelo->publicada ? $this->indexarNoticia($modelo) : 0,
                    'evento' => $modelo->activo ? $this->indexarEvento($modelo) : 0,
                };
            });
        } catch (Throwable $erro) {
            // O indice nunca pode impedir a gravacao de um conteudo
            Log::warning('Assistente: indice nao actualizado', ['tipo' => $tipo, 'id' => $modelo->getKey(), 'erro' => $erro->getMessage()]);
        }
    }

    /**
     * Trechos mais relevantes para a pergunta, no maximo dois por fonte.
     *
     * @return Collection<int, array{titulo:string, texto:string, tipo:string, origem_id:int, url:string, extra:array}>
     */
    public function procurar(string $pergunta, int $limite = 8): Collection
    {
        $termos = $this->termos($pergunta);
        if (! $termos || ! $this->disponivel()) {
            return collect();
        }

        // Primeiro os conteudos com todas as palavras; so depois os que tem alguma delas
        $todas = count($termos) > 1 ? $this->pesquisar(implode(' AND ', $termos), $limite * 5) : [];
        $linhas = count($todas) >= $limite
            ? $todas
            : array_merge($todas, $this->pesquisar(implode(' OR ', $termos), $limite * 5));

        // Os factos institucionais sao curtos e certos: os que batem com a pergunta vem a frente
        $factos = $this->pesquisar(implode(' OR ', $termos), 2, 'facto');
        $linhas = array_merge($factos, $linhas);

        $vistos = [];
        $porFonte = [];

        return collect($linhas)
            ->filter(function ($l) use (&$vistos) {
                // O mesmo trecho pode vir das duas pesquisas
                $chave = $l->tipo.':'.$l->origem_id.':'.md5($l->texto);

                return ! isset($vistos[$chave]) && ($vistos[$chave] = true);
            })
            ->filter(function ($l) use (&$porFonte) {
                $chave = $l->tipo.':'.$l->origem_id;
                $porFonte[$chave] = ($porFonte[$chave] ?? 0) + 1;

                return $porFonte[$chave] <= 2;
            })
            ->take($limite)
            ->map(fn ($l) => [
                'titulo' => $l->titulo,
                'texto' => $l->texto,
                'tipo' => $l->tipo,
                'origem_id' => (int) $l->origem_id,
                'url' => $l->url,
                'extra' => json_decode($l->extra ?: '[]', true) ?: [],
            ])
            ->values();
    }

    /** @return array{documentos:int, documentos_com_texto:int, trechos:int, por_tipo:array<string,int>} */
    public function estado(): array
    {
        if (! $this->disponivel()) {
            return ['documentos' => 0, 'documentos_com_texto' => 0, 'trechos' => 0, 'por_tipo' => []];
        }
        $porTipo = collect(DB::select('SELECT tipo, COUNT(DISTINCT origem_id) AS n FROM '.self::TABELA.' GROUP BY tipo'))
            ->pluck('n', 'tipo')->map(fn ($n) => (int) $n)->all();
        $comTexto = (int) DB::selectOne(
            'SELECT COUNT(*) AS n FROM (SELECT origem_id FROM '.self::TABELA." WHERE tipo = 'documento' GROUP BY origem_id HAVING COUNT(*) > 1)"
        )->n;

        return [
            'documentos' => $porTipo['documento'] ?? 0,
            'documentos_com_texto' => $comTexto,
            'trechos' => (int) DB::selectOne('SELECT COUNT(*) AS n FROM '.self::TABELA)->n,
            'por_tipo' => $porTipo,
        ];
    }

    /** bm25: o titulo pesa mais do que o texto (colunas na ordem da tabela). */
    private function pesquisar(string $expressao, int $limite, ?string $tipo = null): array
    {
        try {
            return DB::select(
                'SELECT titulo, texto, tipo, origem_id, url, extra, bm25('.self::TABELA.', 8.0, 1.0) AS pontos
                 FROM '.self::TABELA.' WHERE '.self::TABELA.' MATCH ?'.($tipo ? ' AND tipo = ?' : '').' ORDER BY pontos LIMIT ?',
                $tipo ? [$expressao, $tipo, $limite] : [$expressao, $limite],
            );
        } catch (Throwable $erro) {
            Log::warning('Assistente: pesquisa falhou', ['expressao' => $expressao, 'erro' => $erro->getMessage()]);

            return [];
        }
    }

    /**
     * Corrige palavras que nao existem no site pela mais parecida do vocabulario
     * ("circulr" -> "circular", "despachnte" -> "despachante"). Devolve null se nada mudou.
     */
    public function corrigir(string $pergunta): ?string
    {
        if (! $this->disponivel()) {
            return null;
        }
        $palavras = $this->palavras($pergunta);
        $mudou = false;

        foreach ($palavras as $i => $palavra) {
            if (strlen($palavra) < 4 || ctype_digit($palavra) || $this->existeNoVocabulario($palavra)) {
                continue;
            }
            $limite = strlen($palavra) >= 7 ? 2 : 1;
            $candidatos = DB::select(
                'SELECT term, doc FROM assistente_vocabulario WHERE term >= ? AND term < ? AND length(term) BETWEEN ? AND ?',
                [$palavra[0], chr(ord($palavra[0]) + 1), strlen($palavra) - $limite, strlen($palavra) + $limite],
            );
            $melhor = collect($candidatos)
                ->map(fn ($c) => ['termo' => $c->term, 'doc' => (int) $c->doc, 'distancia' => levenshtein($palavra, $c->term)])
                ->filter(fn ($c) => $c['distancia'] <= $limite)
                ->sortBy([['distancia', 'asc'], ['doc', 'desc']])
                ->first();
            if ($melhor) {
                $palavras[$i] = $melhor['termo'];
                $mudou = true;
            }
        }

        return $mudou ? implode(' ', $palavras) : null;
    }

    /**
     * Peso de cada palavra da pergunta (raiz => peso): as raras no site pesam mais do que as comuns.
     * Palavras que nao existem no site ficam de fora, porque nenhuma frase as pode ter.
     *
     * @return array<string, float>
     */
    public function pesos(array $palavras): array
    {
        $raizes = collect($palavras)->map(fn ($p) => ctype_digit($p) ? $p : self::raiz($p))->unique()->values();

        try {
            $total = max(1, (int) DB::selectOne('SELECT COUNT(*) AS n FROM '.self::TABELA)->n);
            $pesos = [];
            foreach ($raizes as $raiz) {
                $doc = (int) (DB::selectOne(
                    'SELECT SUM(doc) AS n FROM assistente_vocabulario WHERE term >= ? AND term < ?',
                    [$raiz, $raiz.'{'],
                )->n ?? 0);
                if ($doc > 0) {
                    $pesos[$raiz] = log(1 + $total / $doc);
                }
            }

            return $pesos;
        } catch (Throwable) {
            return $raizes->mapWithKeys(fn ($r) => [$r => 1.0])->all();
        }
    }

    private function existeNoVocabulario(string $palavra): bool
    {
        // Prefixo: "circulares" existe se houver "circular..." (a pesquisa tambem usa prefixos)
        $raiz = strlen($palavra) > 6 ? substr($palavra, 0, max(5, strlen($palavra) - 3)) : $palavra;

        return DB::selectOne('SELECT 1 AS ok FROM assistente_vocabulario WHERE term >= ? AND term < ? LIMIT 1', [$raiz, $raiz.'{']) !== null;
    }

    /** Expressao FTS5 (com OR) das palavras relevantes da pergunta. */
    public function consulta(string $pergunta): string
    {
        return implode(' OR ', $this->termos($pergunta));
    }

    /** Palavras relevantes da pergunta, sem acentos nem palavras vazias. */
    public function palavras(string $pergunta): array
    {
        preg_match_all('/[a-z0-9]+/', Str::lower(Str::ascii($pergunta)), $m);

        return collect($m[0])
            ->reject(fn ($p) => in_array($p, self::PALAVRAS_VAZIAS, true))
            ->filter(fn ($p) => strlen($p) >= 3 || ctype_digit($p))
            ->values()
            ->all();
    }

    /** Termos FTS5 da pergunta, com prefixo para apanhar plurais e derivadas. */
    private function termos(string $pergunta): array
    {
        return collect($this->palavras($pergunta))
            ->map(function ($p) {
                if (ctype_digit($p)) {
                    return '"'.$p.'"';
                }
                // Raiz aproximada: "circulares" e "circular" encontram-se pelo mesmo prefixo
                $raiz = strlen($p) > 6 ? substr($p, 0, max(5, strlen($p) - 3)) : $p;

                return '"'.$raiz.'"*';
            })
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function indexarFactos(): int
    {
        $ficheiro = resource_path('assistente/factos.txt');
        if (! is_file($ficheiro)) {
            return 0;
        }
        $linhas = collect(preg_split('/\R/', File::get($ficheiro)))
            ->map(fn ($l) => trim(ltrim(trim($l), '-')))
            ->filter(fn ($l) => $l !== '' && ! str_starts_with($l, 'FACTOS'));

        $n = 0;
        foreach ($linhas as $i => $linha) {
            $this->inserir('facto', $i + 1, 'Informação institucional da CDA', $linha, route('instituicao'), []);
            $n++;
        }

        return $n;
    }

    private function indexarPagina(Pagina $pagina): int
    {
        $texto = $this->textoDeHtml(($pagina->resumo ? $pagina->resumo."\n\n" : '').$pagina->conteudo);

        return $this->inserirTexto('pagina', $pagina->id, $pagina->titulo, $texto, $pagina->url(), []);
    }

    private function indexarNoticia(Noticia $noticia): int
    {
        $texto = $this->textoDeHtml(Noticia::paraHtml($noticia->texto));
        $cabeca = trim(($noticia->data ? 'Publicada em '.$noticia->data->format('d/m/Y').'. ' : '').($noticia->resumo ?? ''));

        return $this->inserirTexto('noticia', $noticia->id, $noticia->titulo, trim($cabeca."\n\n".$texto), route('noticia', $noticia->slug), [
            'data' => $noticia->data?->format('d/m/Y'),
            'categoria' => $noticia->categoria,
        ]);
    }

    private function indexarEvento(Evento $evento): int
    {
        $partes = [
            $evento->data_evento ? 'Data: '.$evento->data_evento->format('d/m/Y').'.' : '',
            $evento->local ? 'Local: '.$evento->local.'.' : '',
            (string) $evento->resumo,
        ];
        foreach ((array) $evento->blocos as $bloco) {
            $partes[] = $this->textoDeHtml(is_array($bloco) ? implode("\n", array_filter($bloco, 'is_string')) : (string) $bloco);
        }

        return $this->inserirTexto('evento', $evento->id, $evento->titulo, implode("\n", array_filter($partes)), route('evento', $evento->slug), [
            'data' => $evento->data_evento?->format('d/m/Y'),
        ]);
    }

    private function indexarDocumento(Documento $documento): int
    {
        $ficha = collect([
            $documento->titulo,
            $documento->tipo ? 'Tipo: '.$documento->tipo : null,
            $documento->categoria ? 'Categoria: '.$documento->categoria : null,
            $documento->entidade ? 'Entidade: '.$documento->entidade : null,
            $documento->ano ? 'Ano: '.$documento->ano : null,
            $documento->status ? 'Situação: '.$documento->status : null,
        ])->filter()->implode('. ').'.';

        $extra = array_filter([
            'pdf' => route('documento.abrir', $documento),
            'ano' => $documento->ano,
            'categoria' => $documento->categoria ?: $documento->tipo,
            'status' => $documento->status,
        ]);
        $url = route('documentos', ['q' => Str::limit($documento->titulo, 80, '')]);

        // A ficha entra sempre, mesmo quando o PDF e digitalizado e nao tem texto
        $this->inserir('documento', $documento->id, $documento->titulo, $ficha, $url, $extra);
        $texto = $this->textoDoPdf($documento->ficheiro);

        return 1 + ($texto !== '' ? $this->inserirTexto('documento', $documento->id, $documento->titulo, $texto, $url, $extra) : 0);
    }

    /** Texto do PDF: primeiro o texto manual/OCR, depois a cache, depois a extraccao. */
    public function textoDoPdf(?string $ficheiro): string
    {
        $nome = basename((string) $ficheiro);
        $pdf = base_path('acervo/'.$nome);
        if ($nome === '') {
            return '';
        }

        $manual = rtrim(config('cda.assistente.textos'), '/\\').DIRECTORY_SEPARATOR.$nome.'.txt';
        if (is_file($manual)) {
            return $this->limparTexto(File::get($manual));
        }
        if (! is_file($pdf)) {
            return '';
        }

        $cache = storage_path('app/assistente/texto/'.$nome.'-'.filemtime($pdf).'.txt');
        if (is_file($cache)) {
            return File::get($cache);
        }

        $texto = $this->limparTexto($this->extrairPdf($pdf));
        File::ensureDirectoryExists(dirname($cache));
        // Apaga versoes antigas do mesmo PDF e guarda a nova (vazia tambem, para nao repetir a tentativa)
        File::delete(File::glob(storage_path('app/assistente/texto/'.$nome.'-*.txt')));
        File::put($cache, $texto);

        return $texto;
    }

    /** Texto do PDF lido em PHP, sem programas externos. */
    private function extrairPdf(string $pdf): string
    {
        // PDFs muito grandes sao quase sempre digitalizados e so gastariam memoria
        if (filesize($pdf) > 25 * 1024 * 1024) {
            return '';
        }
        try {
            return (new Parser)->parseFile($pdf)->getText();
        } catch (Throwable) {
            return '';
        }
    }

    private function limparTexto(string $texto): string
    {
        $texto = str_replace("\f", "\n", $texto);
        $texto = preg_replace('/[^\P{C}\n]+/u', ' ', $texto) ?? '';
        $texto = preg_replace('/[ \t]+/u', ' ', $texto);
        $texto = trim(preg_replace('/\n\s*\n+/u', "\n\n", $texto));

        // Menos de 150 letras e quase sempre lixo de uma digitalizacao
        return preg_match_all('/\p{L}/u', $texto) < 150 ? '' : Str::limit($texto, self::MAX_TEXTO_PDF, '');
    }

    private function textoDeHtml(?string $html): string
    {
        $html = preg_replace('#<(br|/p|/h[1-6]|/li|/blockquote|/tr|/div|/figcaption)\b[^>]*>#i', "$0\n", (string) $html);
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/[ \t]+/u', ' ', $texto);

        return trim(preg_replace('/\n\s*\n+/u', "\n\n", $texto));
    }

    private function inserirTexto(string $tipo, int $id, string $titulo, string $texto, string $url, array $extra): int
    {
        $n = 0;
        foreach ($this->trechos($texto) as $trecho) {
            $this->inserir($tipo, $id, $titulo, $trecho, $url, $extra);
            $n++;
        }

        return $n;
    }

    /** Divide o texto em trechos sobrepostos, cortando de preferencia no fim de uma frase. */
    private function trechos(string $texto): array
    {
        $texto = trim(preg_replace('/\s+/u', ' ', $texto));
        if ($texto === '') {
            return [];
        }
        $trechos = [];
        $inicio = 0;
        $total = mb_strlen($texto);
        while ($inicio < $total) {
            $pedaco = mb_substr($texto, $inicio, self::TAMANHO_TRECHO);
            if ($inicio + self::TAMANHO_TRECHO < $total) {
                $fim = max(mb_strrpos($pedaco, '. ') ?: 0, mb_strrpos($pedaco, '; ') ?: 0);
                if ($fim > self::TAMANHO_TRECHO * 0.6) {
                    $pedaco = mb_substr($pedaco, 0, $fim + 1);
                }
            }
            $trechos[] = trim($pedaco);
            $avanco = mb_strlen($pedaco) - self::SOBREPOSICAO;
            $inicio += max($avanco, 200);
        }

        return $trechos;
    }

    private function inserir(string $tipo, int $id, string $titulo, string $texto, string $url, array $extra): void
    {
        DB::table(self::TABELA)->insert([
            'titulo' => $titulo,
            'texto' => $texto,
            'tipo' => $tipo,
            'origem_id' => $id,
            'url' => $url,
            'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function remover(string $tipo, int $id): void
    {
        DB::table(self::TABELA)->where('tipo', $tipo)->where('origem_id', $id)->delete();
    }

    private function tipoDe(object $modelo): ?string
    {
        return match (true) {
            $modelo instanceof Documento => 'documento',
            $modelo instanceof Pagina => 'pagina',
            $modelo instanceof Noticia => 'noticia',
            $modelo instanceof Evento => 'evento',
            default => null,
        };
    }
}
