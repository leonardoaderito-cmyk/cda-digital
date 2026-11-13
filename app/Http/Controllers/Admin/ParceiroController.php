<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use App\Models\Parceiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Leonardo (leonardoaderito-cmyk): gestao dos parceiros (logotipo, categoria, ligacao e ordem)
class ParceiroController extends Controller
{
    public function index()
    {
        return view('admin.parceiros', [
            'parceiros' => Parceiro::orderBy('ordem')->orderBy('id')->get()->groupBy('categoria'),
        ]);
    }

    public function create()
    {
        return view('admin.parceiro_form', ['parceiro' => new Parceiro(['activo' => true, 'categoria' => 'nacional']), 'imagens' => Imagem::latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $parceiro = Parceiro::create($this->validar($request) + ['ordem' => (int) Parceiro::max('ordem') + 1]);

        return redirect()->route('admin.parceiros.editar', $parceiro)->with('estado', 'Parceiro adicionado.');
    }

    public function edit(Parceiro $parceiro)
    {
        return view('admin.parceiro_form', ['parceiro' => $parceiro, 'imagens' => Imagem::latest()->get()]);
    }

    public function update(Request $request, Parceiro $parceiro): RedirectResponse
    {
        $parceiro->update($this->validar($request, $parceiro));

        return back()->with('estado', 'Parceiro actualizado.');
    }

    public function alternar(Parceiro $parceiro): RedirectResponse
    {
        $parceiro->update(['activo' => ! $parceiro->activo]);

        return back()->with('estado', $parceiro->activo ? 'Parceiro visível no site.' : 'Parceiro escondido do site.');
    }

    /** Troca o parceiro de posicao com o vizinho da mesma categoria. */
    public function mover(Request $request, Parceiro $parceiro): RedirectResponse
    {
        $ids = Parceiro::where('categoria', $parceiro->categoria)->orderBy('ordem')->orderBy('id')->pluck('id')->all();
        $posicao = array_search($parceiro->id, $ids, true);
        $destino = $posicao + ($request->input('sentido') === 'subir' ? -1 : 1);

        if (isset($ids[$destino])) {
            // Renumera so esta categoria, a partir da sua primeira posicao
            $base = (int) Parceiro::whereIn('id', $ids)->min('ordem');
            [$ids[$posicao], $ids[$destino]] = [$ids[$destino], $ids[$posicao]];
            foreach ($ids as $i => $id) {
                Parceiro::whereKey($id)->update(['ordem' => $base + $i]);
            }
        }

        return back();
    }

    public function destroy(Parceiro $parceiro): RedirectResponse
    {
        $parceiro->delete();

        return redirect()->route('admin.parceiros')->with('estado', 'Parceiro removido.');
    }

    private function validar(Request $request, ?Parceiro $parceiro = null): array
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:250'],
            'sigla' => ['nullable', 'string', 'max:30'],
            'categoria' => ['required', Rule::in(array_keys(Parceiro::CATEGORIAS))],
            'descricao' => ['nullable', 'string', 'max:400'],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'imagem_ficheiro' => ['nullable', ...Imagem::REGRAS],
            'imagem_biblioteca' => ['nullable', 'integer', 'exists:imagens,id'],
        ], ['url.url' => 'A ligação tem de ser um endereço completo, por exemplo https://www.at.gov.mz/.']);

        $logo = $parceiro?->logo;
        if ($request->hasFile('imagem_ficheiro')) {
            $logo = Imagem::guardar($request->file('imagem_ficheiro'), 'Logotipo: '.$dados['nome'])->url();
        } elseif (! empty($dados['imagem_biblioteca'])) {
            $logo = Imagem::findOrFail($dados['imagem_biblioteca'])->url();
        } elseif ($request->boolean('remover_imagem')) {
            $logo = null;
        }

        return [
            'nome' => $dados['nome'],
            'sigla' => $dados['sigla'] ?? null,
            'categoria' => $dados['categoria'],
            'descricao' => $dados['descricao'] ?? null,
            'url' => $dados['url'] ?? null,
            'logo' => $logo,
            'activo' => $request->boolean('activo'),
        ];
    }
}
