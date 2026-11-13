<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// Autor: Leonardo (leonardoaderito-cmyk)
#[Fillable(['titulo', 'slug', 'data_evento', 'local', 'resumo', 'imagem', 'blocos', 'activo'])]
class Evento extends Model
{
    public const TIPOS_BLOCO = [
        'titulo' => 'Título',
        'texto' => 'Texto',
        'imagem' => 'Imagem',
        'destaque' => 'Destaque',
        'lista' => 'Lista',
        'botao' => 'Botão',
    ];

    protected function casts(): array
    {
        return ['data_evento' => 'date', 'activo' => 'boolean', 'blocos' => 'array'];
    }
}
