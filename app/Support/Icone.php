<?php

namespace App\Support;

// Autor: Leonardo (leonardoaderito-cmyk) - icones Material Symbols guardados em resources/icons
class Icone
{
    /** @var array<string, string> */
    private static array $caminhos = [];

    /** Desenho (atributo d do path) do icone; com $cheio usa a versao preenchida quando existe. */
    public static function caminho(string $nome, bool $cheio = false): string
    {
        $nome = preg_replace('/[^a-z0-9_]/', '', strtolower($nome));
        if ($cheio && is_file(resource_path("icons/{$nome}-fill.svg"))) {
            $nome .= '-fill';
        }

        return self::$caminhos[$nome] ??= self::ler($nome);
    }

    private static function ler(string $nome): string
    {
        $svg = @file_get_contents(resource_path("icons/{$nome}.svg"));

        return $svg && preg_match('#<path d="([^"]+)"#', $svg, $m) ? $m[1] : '';
    }
}
