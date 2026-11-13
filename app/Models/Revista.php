<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Leonardo (leonardoaderito-cmyk): edicao da revista; a capa abre o PDF guardado em acervo/revistas
#[Fillable(['titulo', 'edicao', 'data_publicacao', 'descricao', 'capa', 'ficheiro', 'activa'])]
class Revista extends Model
{
    public const PASTA = 'acervo/revistas';

    // PDF ate 80 MB (as revistas tem muitas fotografias)
    public const REGRAS_PDF = ['file', 'mimes:pdf', 'max:81920'];

    protected function casts(): array
    {
        return ['data_publicacao' => 'date', 'activa' => 'boolean'];
    }

    /** @param  Builder<Revista>  $query */
    public function scopePublicadas(Builder $query): void
    {
        $query->where('activa', true)->orderByDesc('data_publicacao')->orderByDesc('id');
    }

    public function nome(): string
    {
        return trim($this->titulo.($this->edicao ? ' — '.$this->edicao : ''));
    }

    public function caminho(): string
    {
        return base_path(self::PASTA.'/'.basename($this->ficheiro));
    }

    /** Guarda o PDF com um nome legivel e unico; devolve o nome do ficheiro. */
    public static function guardarPdf(UploadedFile $pdf, string $titulo): string
    {
        File::ensureDirectoryExists(base_path(self::PASTA));
        $nome = (Str::slug($titulo) ?: 'revista').'-'.Str::lower(Str::random(6)).'.pdf';
        $pdf->move(base_path(self::PASTA), $nome);

        return $nome;
    }

    public function apagarPdf(): void
    {
        if ($this->ficheiro && is_file($this->caminho())) {
            File::delete($this->caminho());
        }
    }
}
