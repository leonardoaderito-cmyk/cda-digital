<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use App\Models\Revista;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// Leonardo (leonardoaderito-cmyk): gestao das edicoes da revista (capa + PDF)
class RevistaController extends Controller
{
    public function index()
    {
        return view('admin.revistas', [
            'revistas' => Revista::orderByDesc('data_publicacao')->orderByDesc('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.revista_form', [
            'revista' => new Revista(['titulo' => 'O Despachante', 'activa' => true, 'data_publicacao' => today()]),
            'imagens' => Imagem::latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $revista = Revista::create($this->validar($request));

        return redirect()->route('admin.revistas.editar', $revista)->with('estado', 'Edição da revista publicada.');
    }

    public function edit(Revista $revista)
    {
        return view('admin.revista_form', ['revista' => $revista, 'imagens' => Imagem::latest()->get()]);
    }

    public function update(Request $request, Revista $revista): RedirectResponse
    {
        $revista->update($this->validar($request, $revista));

        return back()->with('estado', 'Edição actualizada.');
    }

    public function alternar(Revista $revista): RedirectResponse
    {
        $revista->update(['activa' => ! $revista->activa]);

        return back()->with('estado', $revista->activa ? 'Edição visível no site.' : 'Edição escondida do site.');
    }

    public function destroy(Revista $revista): RedirectResponse
    {
        $revista->apagarPdf();
        $revista->delete();

        return redirect()->route('admin.revistas')->with('estado', 'Edição removida.');
    }

    private function validar(Request $request, ?Revista $revista = null): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'edicao' => ['nullable', 'string', 'max:120'],
            'data_publicacao' => ['nullable', 'date'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'pdf' => [$revista ? 'nullable' : 'required', ...Revista::REGRAS_PDF],
            'imagem_ficheiro' => ['nullable', ...Imagem::REGRAS],
            'imagem_biblioteca' => ['nullable', 'integer', 'exists:imagens,id'],
        ], [
            'pdf.required' => 'Escolha o ficheiro PDF da revista.',
            'pdf.mimes' => 'A revista tem de ser um ficheiro PDF.',
            'pdf.max' => 'O PDF não pode ter mais de 80 MB.',
        ]);

        $capa = $revista?->capa;
        if ($request->hasFile('imagem_ficheiro')) {
            $capa = Imagem::guardar($request->file('imagem_ficheiro'), 'Capa: '.$dados['titulo'])->url();
        } elseif (! empty($dados['imagem_biblioteca'])) {
            $capa = Imagem::findOrFail($dados['imagem_biblioteca'])->url();
        } elseif ($request->boolean('remover_imagem')) {
            $capa = null;
        }

        $ficheiro = $revista?->ficheiro;
        if ($request->hasFile('pdf')) {
            $revista?->apagarPdf();
            $ficheiro = Revista::guardarPdf($request->file('pdf'), trim($dados['titulo'].' '.($dados['edicao'] ?? '')));
        }

        return [
            'titulo' => $dados['titulo'],
            'edicao' => $dados['edicao'] ?? null,
            'data_publicacao' => $dados['data_publicacao'] ?? null,
            'descricao' => $dados['descricao'] ?? null,
            'capa' => $capa,
            'ficheiro' => $ficheiro,
            'activa' => $request->boolean('activa'),
        ];
    }
}
