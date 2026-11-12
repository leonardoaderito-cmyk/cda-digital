<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// Autor: Leonardo (leonardoaderito-cmyk) - contactos que aceitaram receber novidades da CDA
#[Fillable(['nome', 'email', 'activo', 'origem', 'ip'])]
class NewsletterSubscricao extends Model
{
    protected $table = 'newsletter_subscricoes';

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
