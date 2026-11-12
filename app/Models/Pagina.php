<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Autor: Leonardo (leonardoaderito-cmyk) - paginas geridas na administracao
#[Fillable(['slug', 'titulo', 'resumo', 'conteudo', 'tipo', 'activa', 'no_menu'])]
class Pagina extends Model
{
    /** Etiquetas HTML aceites no conteudo das paginas. */
    private const ETIQUETAS = '<h2><h3><h4><p><br><hr><strong><b><em><i><u><s><ul><ol><li><a><img><figure><figcaption><blockquote><table><thead><tbody><tr><th><td><div><section><article><span>';

    /** Estado das paginas, lido uma vez por pedido. */
    private static ?array $estados = null;

    protected function casts(): array
    {
        return ['activa' => 'boolean', 'no_menu' => 'boolean'];
    }

    /** Uma pagina sem registo conta como activa, para o portal nunca ficar sem seccoes. */
    public static function estaActiva(string $slug): bool
    {
        if (static::$estados === null) {
            try {
                static::$estados = Schema::hasTable('paginas') ? static::pluck('activa', 'slug')->all() : [];
            } catch (\Throwable) {
                static::$estados = [];
            }
        }

        return (bool) (static::$estados[$slug] ?? true);
    }

    /** Diz se a pagina para onde um endereco do portal aponta esta activa (para esconder ligacoes dos menus). */
    public static function urlActiva(string $url): bool
    {
        $segmento = explode('/', trim((string) parse_url($url, PHP_URL_PATH), '/'))[0] ?? '';

        return static::estaActiva($segmento === 'membros' ? 'directorio' : $segmento);
    }

    public static function esquecerEstados(): void
    {
        static::$estados = null;
    }

    public function eSistema(): bool
    {
        return $this->tipo === 'sistema';
    }

    /** Endereco publico: rota propria quando existe, senao /pagina/{slug}. */
    public function url(): string
    {
        return Route::has($this->slug) ? route($this->slug) : route('pagina', $this->slug);
    }

    /** Remove scripts, atributos de eventos e enderecos perigosos do HTML escrito no editor. */
    public static function limparHtml(?string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b.*?</\1>#is', '', (string) $html);
        $html = strip_tags($html, self::ETIQUETAS);
        $html = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#\s+style\s*=\s*("[^"]*"|\'[^\']*\')#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'>\s]*#i', '$1=$2#', $html);

        return trim($html);
    }
}
