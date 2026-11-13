<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Leonardo (leonardoaderito-cmyk): gestao dos videos que rodam na pagina inicial
class VideoController extends Controller
{
    public function index()
    {
        return view('admin.videos', ['videos' => Video::orderBy('ordem')->orderByDesc('data')->orderByDesc('id')->get()]);
    }

    public function create()
    {
        return view('admin.video_form', ['video' => new Video(['activo' => true, 'tipo' => 'ficheiro', 'data' => today()]), 'imagens' => Imagem::latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $video = Video::create($this->validar($request) + ['ordem' => (int) Video::max('ordem') + 1]);

        return redirect()->route('admin.videos.editar', $video)->with('estado', 'Vídeo adicionado.');
    }

    public function edit(Video $video)
    {
        return view('admin.video_form', ['video' => $video, 'imagens' => Imagem::latest()->get()]);
    }

    public function update(Request $request, Video $video): RedirectResponse
    {
        $video->update($this->validar($request, $video));

        return back()->with('estado', 'Vídeo actualizado.');
    }

    public function alternar(Video $video): RedirectResponse
    {
        $video->update(['activo' => ! $video->activo]);

        return back()->with('estado', $video->activo ? 'Vídeo visível no site.' : 'Vídeo escondido do site.');
    }

    public function mover(Request $request, Video $video): RedirectResponse
    {
        $ids = Video::orderBy('ordem')->orderByDesc('data')->orderByDesc('id')->pluck('id')->all();
        $posicao = array_search($video->id, $ids, true);
        $destino = $posicao + ($request->input('sentido') === 'subir' ? -1 : 1);

        if (isset($ids[$destino])) {
            [$ids[$posicao], $ids[$destino]] = [$ids[$destino], $ids[$posicao]];
            foreach ($ids as $i => $id) {
                Video::whereKey($id)->update(['ordem' => $i + 1]);
            }
        }

        return back();
    }

    public function destroy(Video $video): RedirectResponse
    {
        $video->apagarFicheiro();
        $video->delete();

        return redirect()->route('admin.videos')->with('estado', 'Vídeo removido.');
    }

    private function validar(Request $request, ?Video $video = null): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:250'],
            'descricao' => ['nullable', 'string', 'max:400'],
            'data' => ['nullable', 'date'],
            'tipo' => ['required', Rule::in(['ficheiro', 'youtube'])],
            'ficheiro' => ['nullable', ...Video::REGRAS_FICHEIRO],
            'youtube' => ['nullable', 'string', 'max:300'],
            'imagem_ficheiro' => ['nullable', ...Imagem::REGRAS],
            'imagem_biblioteca' => ['nullable', 'integer', 'exists:imagens,id'],
        ], [
            'ficheiro.mimetypes' => 'O vídeo tem de ser MP4 ou WebM.',
            'ficheiro.max' => 'O vídeo não pode ter mais de 80 MB. Para vídeos maiores use uma ligação do YouTube.',
        ]);

        $youtube = null;
        $ficheiro = $video?->ficheiro;
        if ($dados['tipo'] === 'youtube') {
            $youtube = Video::codigoYoutube($dados['youtube'] ?? null);
            if (! $youtube) {
                throw ValidationException::withMessages(['youtube' => 'Cole a ligação completa do vídeo no YouTube (por exemplo https://youtu.be/...).']);
            }
        } elseif ($request->hasFile('ficheiro')) {
            $video?->apagarFicheiro();
            $ficheiro = Video::guardarFicheiro($request->file('ficheiro'), $dados['titulo']);
        } elseif (! $ficheiro) {
            throw ValidationException::withMessages(['ficheiro' => 'Escolha o ficheiro do vídeo (MP4 ou WebM).']);
        }

        $capa = $video?->capa;
        if ($request->hasFile('imagem_ficheiro')) {
            $capa = Imagem::guardar($request->file('imagem_ficheiro'), 'Capa do vídeo: '.$dados['titulo'])->url();
        } elseif (! empty($dados['imagem_biblioteca'])) {
            $capa = Imagem::findOrFail($dados['imagem_biblioteca'])->url();
        } elseif ($request->boolean('remover_imagem')) {
            $capa = null;
        }

        // Passar de ficheiro para YouTube apaga o ficheiro antigo
        if ($dados['tipo'] === 'youtube' && $video?->ficheiro) {
            $video->apagarFicheiro();
            $ficheiro = null;
        }

        return [
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'] ?? null,
            'data' => $dados['data'] ?? null,
            'tipo' => $dados['tipo'],
            'ficheiro' => $dados['tipo'] === 'ficheiro' ? $ficheiro : null,
            'youtube_id' => $youtube,
            'capa' => $capa,
            'activo' => $request->boolean('activo'),
        ];
    }
}
