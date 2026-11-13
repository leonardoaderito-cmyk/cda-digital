<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// Leonardo (leonardoaderito-cmyk): anuncio com imagem, ligacao e periodo de exibicao
#[Fillable(['titulo', 'anunciante', 'imagem', 'url', 'inicio', 'fim', 'ordem', 'activo'])]
class Publicidade extends Model
{
    protected $table = 'publicidades';

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'ordem' => 'integer', 'inicio' => 'date', 'fim' => 'date'];
    }

    /**
     * Anuncios activos dentro do periodo (sem datas = sempre).
     *
     * @param  Builder<Publicidade>  $query
     */
    public function scopeVisiveis(Builder $query): void
    {
        $query->where('activo', true)
            ->where(fn ($q) => $q->whereNull('inicio')->orWhereDate('inicio', '<=', today()))
            ->where(fn ($q) => $q->whereNull('fim')->orWhereDate('fim', '>=', today()))
            ->orderBy('ordem')
            ->orderBy('id');
    }

    /** Estado para a lista da administracao. */
    public function estado(): string
    {
        return match (true) {
            ! $this->activo => 'Escondido',
            $this->inicio?->isFuture() => 'Agendado',
            $this->fim !== null && $this->fim->lt(today()) => 'Terminado',
            default => 'No site',
        };
    }
}
