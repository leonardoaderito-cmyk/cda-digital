<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use App\Models\Publicidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// Leonardo (leonardoaderito-cmyk): gestao dos anuncios da pagina inicial (imagem, ligacao, periodo e ordem)
class PublicidadeController extends Controller
{
    public function index()
    {
        return view('admin.publicidades', ['publicidades' => Publicidade::orderBy('ordem')->orderBy('id')->get()]);
    }

    public function create()
    {
        return view('admin.publicidade_form', ['publicidade' => new Publicidade(['activo' => true]), 'imagens' => Imagem::latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $publicidade = Publicidade::create($this->validar($request) + ['ordem' => (int) Publicidade::max('ordem') + 1]);

        return redirect()->route('admin.publicidades.editar', $publicidade)->with('estado', 'Anúncio adicionado.');
    }

    public function edit(Publicidade $publicidade)
    {
        return view('admin.publicidade_form', ['publicidade' => $publicidade, 'imagens' => Imagem::latest()->get()]);
    }

    public function update(Request $request, Publicidade $publicidade): RedirectResponse
    {
        $publicidade->update($this->validar($request, $publicidade));

        return back()->with('estado', 'Anúncio actualizado.');
    }

    public function alternar(Publicidade $publicidade): RedirectResponse
    {
        $publicidade->update(['activo' => ! $publicidade->activo]);

        return back()->with('estado', $publicidade->activo ? 'Anúncio visível no site.' : 'Anúncio escondido do site.');
    }

    /** Troca o anuncio de posicao com o vizinho. */
    public function mover(Request $request, Publicidade $publicidade): RedirectResponse
    {
        $ids = Publicidade::orderBy('ordem')->orderBy('id')->pluck('id')->all();
        $posicao = array_search($publicidade->id, $ids, true);
        $destino = $posicao + ($request->input('sentido') === 'subir' ? -1 : 1);

        if (isset($ids[$destino])) {
            [$ids[$posicao], $ids[$destino]] = [$ids[$destino], $ids[$posicao]];
            foreach ($ids as $i => $id) {
                Publicidade::whereKey($id)->update(['ordem' => $i + 1]);
            }
        }

        return back();
    }

    public function destroy(Publicidade $publicidade): RedirectResponse
    {
        $publicidade->delete();

        return redirect()->route('admin.publicidades')->with('estado', 'Anúncio removido.');
    }

    private function validar(Request $request, ?Publicidade $publicidade = null): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:250'],
            'anunciante' => ['nullable', 'string', 'max:250'],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'inicio' => ['nullable', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
            'imagem_ficheiro' => ['nullable', ...Imagem::REGRAS],
            'imagem_biblioteca' => ['nullable', 'integer', 'exists:imagens,id'],
        ], [
            'url.url' => 'A ligação tem de ser um endereço completo, por exemplo https://www.empresa.co.mz/.',
            'fim.after_or_equal' => 'A data de fim não pode ser anterior à data de início.',
        ]);

        $imagem = $publicidade?->imagem;
        if ($request->hasFile('imagem_ficheiro')) {
            $imagem = Imagem::guardar($request->file('imagem_ficheiro'), 'Publicidade: '.$dados['titulo'])->url();
        } elseif (! empty($dados['imagem_biblioteca'])) {
            $imagem = Imagem::findOrFail($dados['imagem_biblioteca'])->url();
        }

        // O anuncio e uma imagem: sem ela nao ha nada para mostrar
        if (! $imagem) {
            throw ValidationException::withMessages(['imagem_ficheiro' => 'Escolha a imagem do anúncio.']);
        }

        return [
            'titulo' => $dados['titulo'],
            'anunciante' => $dados['anunciante'] ?? null,
            'url' => $dados['url'] ?? null,
            'inicio' => $dados['inicio'] ?? null,
            'fim' => $dados['fim'] ?? null,
            'imagem' => $imagem,
            'activo' => $request->boolean('activo'),
        ];
    }
}
