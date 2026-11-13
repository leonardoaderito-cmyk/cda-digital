<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Leonardo (leonardoaderito-cmyk): video de um evento da CDA (ficheiro no portal ou ligacao do YouTube)
#[Fillable(['titulo', 'descricao', 'tipo', 'ficheiro', 'youtube_id', 'capa', 'data', 'ordem', 'activo'])]
class Video extends Model
{
    public const PASTA = 'video/eventos';

    // MP4 ou WebM ate 80 MB (o mesmo limite do servidor para as revistas)
    public const REGRAS_FICHEIRO = ['file', 'mimetypes:video/mp4,video/webm', 'max:81920'];

    protected function casts(): array
    {
        return ['data' => 'date', 'activo' => 'boolean', 'ordem' => 'integer'];
    }

    /** @param  Builder<Video>  $query */
    public function scopeVisiveis(Builder $query): void
    {
        $query->where('activo', true)->orderBy('ordem')->orderByDesc('data')->orderByDesc('id');
    }

    /** Extrai o codigo de 11 caracteres de qualquer ligacao do YouTube (watch, youtu.be, shorts, embed). */
    public static function codigoYoutube(?string $ligacao): ?string
    {
        if (! $ligacao) {
            return null;
        }
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $ligacao)) {
            return $ligacao;
        }
        $padrao = '#(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#i';

        return preg_match($padrao, $ligacao, $m) ? $m[1] : null;
    }

    public function eYoutube(): bool
    {
        return $this->tipo === 'youtube' && $this->youtube_id;
    }

    public function urlFicheiro(): ?string
    {
        return $this->ficheiro ? asset(self::PASTA.'/'.basename($this->ficheiro)) : null;
    }

    /** Endereco de incorporacao sem cookies de publicidade (youtube-nocookie). */
    public function urlYoutube(): ?string
    {
        return $this->eYoutube() ? 'https://www.youtube-nocookie.com/embed/'.$this->youtube_id : null;
    }

    public function imagemCapa(): ?string
    {
        return $this->capa ?: ($this->eYoutube() ? 'https://i.ytimg.com/vi/'.$this->youtube_id.'/hqdefault.jpg' : null);
    }

    public static function guardarFicheiro(UploadedFile $ficheiro, string $titulo): string
    {
        File::ensureDirectoryExists(public_path(self::PASTA));
        $nome = (Str::slug($titulo) ?: 'video').'-'.Str::lower(Str::random(6)).'.'.($ficheiro->extension() === 'webm' ? 'webm' : 'mp4');
        $ficheiro->move(public_path(self::PASTA), $nome);

        return $nome;
    }

    public function apagarFicheiro(): void
    {
        if ($this->ficheiro && is_file($caminho = public_path(self::PASTA.'/'.basename($this->ficheiro)))) {
            File::delete($caminho);
        }
    }
}
