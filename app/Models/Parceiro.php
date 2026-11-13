<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Leonardo (leonardoaderito-cmyk): instituicao parceira mostrada na pagina de parceiros e na pagina inicial
#[Fillable(['nome', 'sigla', 'categoria', 'descricao', 'logo', 'url', 'ordem', 'activo'])]
class Parceiro extends Model
{
    protected $table = 'parceiros';

    public const CATEGORIAS = [
        'nacional' => 'Instituições e autoridades nacionais',
        'sector_privado' => 'Sector privado',
        'internacional' => 'Organizações internacionais',
        'ligacao' => 'Ligações úteis',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'ordem' => 'integer'];
    }

    /** @param  Builder<Parceiro>  $query */
    public function scopeVisiveis(Builder $query): void
    {
        $query->where('activo', true)->orderBy('ordem')->orderBy('id');
    }

    public function categoriaNome(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? 'Outros';
    }

    /** Sigla para a placa sem logotipo: a indicada ou as iniciais do nome. */
    public function monograma(): string
    {
        if ($this->sigla) {
            return Str::upper($this->sigla);
        }

        return collect(preg_split('/\s+/', Str::ascii($this->nome)))
            ->filter(fn ($p) => strlen($p) > 2)
            ->map(fn ($p) => Str::upper($p[0]))
            ->take(3)
            ->implode('') ?: 'CDA';
    }
}
