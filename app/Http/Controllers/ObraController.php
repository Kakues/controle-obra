<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ObraController extends Controller
{
    public function index(): View
    {
        $obras = Obra::query()->orderByDesc('ativa')->orderBy('nome')->get();

        return view('obras.index', compact('obras'));
    }

    public function create(): View
    {
        return view('obras.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
            'ativa' => ['nullable', 'boolean'],
        ]);

        $dados['ativa'] = $request->boolean('ativa', true);

        Obra::create($dados);

        return redirect()->route('obras.index')->with('success', 'Obra cadastrada.');
    }

    public function edit(Obra $obra): View
    {
        return view('obras.edit', compact('obra'));
    }

    public function update(Request $request, Obra $obra): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
            'ativa' => ['nullable', 'boolean'],
        ]);

        $dados['ativa'] = $request->boolean('ativa');

        $obra->update($dados);

        return redirect()->route('obras.index')->with('success', 'Obra atualizada.');
    }

    public function destroy(Obra $obra): RedirectResponse
    {
        $obra->delete();

        return redirect()->route('obras.index')->with('success', 'Obra removida.');
    }
}
