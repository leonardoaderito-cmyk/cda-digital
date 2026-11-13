<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['titulo', 'slug', 'categoria', 'data', 'resumo', 'texto', 'imagem', 'actividade_id', 'publicada'])]
class Noticia extends Model
{
    protected function casts(): array
    {
        return ['data' => 'date', 'publicada' => 'boolean'];
    }

    /**
     * Texto da noticia em HTML seguro. As noticias antigas foram escritas em texto simples:
     * nesse caso cada bloco separado por uma linha em branco torna-se um paragrafo.
     */
    public static function paraHtml(?string $texto): string
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return '';
        }
        if ($texto !== strip_tags($texto)) {
            return Pagina::limparHtml($texto);
        }

        $paragrafos = preg_split('/\R\s*\R/', str_replace("\r\n", "\n", $texto));

        return collect($paragrafos)
            ->map(fn ($p) => trim($p))
            ->filter()
            ->map(fn ($p) => '<p>'.nl2br(e($p), false).'</p>')
            ->implode("\n");
    }

    /** Resumo em texto simples, para listas e carrossel. */
    public static function resumoDe(string $html, int $limite = 220): string
    {
        $html = preg_replace('#<(br|/p|/h[2-4]|/li|/blockquote|/td|/th|/figcaption)\b[^>]*>#i', '$0 ', $html);
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $texto)), $limite);
    }

    public function textoHtml(): string
    {
        return static::paraHtml($this->texto);
    }
}
