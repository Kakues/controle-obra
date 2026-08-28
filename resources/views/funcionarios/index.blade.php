<x-app-layout>
    <x-slot name="header">
            <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pessoal</h2>
            @if (Auth::user()->isAdmin())
                <a href="{{ route('funcionarios.create') }}" class="inline-flex items-center px-3 py-2 bg-stone-800 text-white text-sm rounded-md">Novo</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif

            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('funcionarios.index', ['filtro' => 'ativos']) }}"
                   class="px-3 py-1.5 rounded-md border {{ $filtro === 'ativos' ? 'bg-stone-800 text-white border-stone-800' : 'bg-white text-gray-700 border-gray-200' }}">
                    Ativos
                </a>
                <a href="{{ route('funcionarios.index', ['filtro' => 'inativos']) }}"
                   class="px-3 py-1.5 rounded-md border {{ $filtro === 'inativos' ? 'bg-stone-800 text-white border-stone-800' : 'bg-white text-gray-700 border-gray-200' }}">
                    Removidos
                </a>
                <a href="{{ route('funcionarios.index', ['filtro' => 'todos']) }}"
                   class="px-3 py-1.5 rounded-md border {{ $filtro === 'todos' ? 'bg-stone-800 text-white border-stone-800' : 'bg-white text-gray-700 border-gray-200' }}">
                    Todos
                </a>
            </div>

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($funcionarios as $funcionario)
                    <a href="{{ route('funcionarios.show', $funcionario) }}" class="p-4 flex items-center justify-between gap-3 hover:bg-gray-50">
                        <div>
                            <div class="font-medium text-gray-900">{{ $funcionario->nome }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $funcionario->labelRegime() }}
                                @if ($funcionario->isDiaria())
                                    · Diária R$ {{ number_format($funcionario->diaria_atual, 2, ',', '.') }}
                                    @if ($funcionario->temLocomocao())
                                        · {{ $funcionario->labelLocomocao() }} R$ {{ number_format($funcionario->locomocao_valor, 2, ',', '.') }}/dia
                                    @endif
                                @else
                                    · Valor lançado por período
                                @endif
                                @if ($funcionario->telefone) · {{ $funcionario->telefone }} @endif
                            </div>
                            <div class="text-xs mt-1 {{ $funcionario->ativo ? 'text-green-700' : 'text-gray-400' }}">
                                {{ $funcionario->ativo ? 'Ativo na equipe' : 'Removido (histórico preservado)' }}
                            </div>
                        </div>
                        <span class="text-sm text-stone-600">Ver</span>
                    </a>
                @empty
                    <div class="p-6 text-gray-500">
                        @if ($filtro === 'inativos')
                            Nenhuma pessoa removida.
                        @else
                            Nenhum funcionário cadastrado.
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
