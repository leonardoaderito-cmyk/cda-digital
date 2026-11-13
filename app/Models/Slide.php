<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// Autor: Leonardo (leonardoaderito-cmyk) - slide do carrossel da pagina inicial
#[Fillable(['titulo', 'etiqueta', 'texto', 'imagem', 'botao_texto', 'botao_url', 'ordem', 'activo'])]
class Slide extends Model
{
    protected function casts(): array
    {
        return ['activo' => 'boolean', 'ordem' => 'integer'];
    }
}
