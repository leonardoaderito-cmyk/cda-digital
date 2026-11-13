<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Autor: Leonardo (leonardoaderito-cmyk) - biblioteca de imagens da administracao
#[Fillable(['ficheiro', 'nome', 'alt', 'tamanho', 'largura', 'altura'])]
class Imagem extends Model
{
    protected $table = 'imagens';

    public const PASTA = 'img/media';

    public const REGRAS = ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'];

    /** Guarda o ficheiro em public/img/media e regista-o na biblioteca. */
    public static function guardar(UploadedFile $ficheiro, ?string $alt = null): self
    {
        File::ensureDirectoryExists(public_path(self::PASTA));
        [$largura, $altura] = @getimagesize($ficheiro->getPathname()) ?: [null, null];
        $tamanho = $ficheiro->getSize();
        $original = pathinfo($ficheiro->getClientOriginalName(), PATHINFO_FILENAME);
        $nome = (Str::slug($original) ?: 'imagem').'-'.Str::lower(Str::random(8)).'.'.$ficheiro->extension();
        $ficheiro->move(public_path(self::PASTA), $nome);

        return static::create([
            'ficheiro' => self::PASTA.'/'.$nome,
            'nome' => Str::limit($original ?: 'Imagem', 180, ''),
            'alt' => $alt,
            'tamanho' => $tamanho,
            'largura' => $largura,
            'altura' => $altura,
        ]);
    }

    /** Endereco relativo a raiz do site, para funcionar em qualquer dominio. */
    public function url(): string
    {
        return '/'.$this->ficheiro;
    }

    public function apagarFicheiro(): void
    {
        $caminho = public_path(self::PASTA.'/'.basename($this->ficheiro));
        if (is_file($caminho)) {
            unlink($caminho);
        }
    }
}
