<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lançamentos</h2>
            <a href="{{ route('lancamentos.create') }}" class="inline-flex items-center px-3 py-2 bg-stone-800 text-white text-sm rounded-md">Novo</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}</div>
            @endif

            <form method="GET" class="bg-white shadow-sm rounded-lg p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div>
                    <x-input-label for="tipo" value="Tipo" />
                    <select id="tipo" name="tipo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Lancamento::TIPOS as $valor => $label)
                            <option value="{{ $valor }}" @selected($tipo === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="funcionario_id" value="Funcionário" />
                    <select id="funcionario_id" name="funcionario_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        @foreach ($funcionarios as $funcionario)
                            <option value="{{ $funcionario->id }}" @selected($funcionarioId == $funcionario->id)>{{ $funcionario->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Filtrar</x-primary-button>
            </form>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('lancamentos.create', ['tipo' => 'adiantamento']) }}" class="text-sm px-3 py-2 rounded-md bg-amber-50 text-amber-900 border border-amber-200">+ Adiantamento</a>
                <a href="{{ route('lancamentos.create', ['tipo' => 'desconto']) }}" class="text-sm px-3 py-2 rounded-md bg-red-50 text-red-900 border border-red-200">+ Desconto</a>
                <a href="{{ route('lancamentos.create', ['tipo' => 'bonus']) }}" class="text-sm px-3 py-2 rounded-md bg-emerald-50 text-emerald-900 border border-emerald-200">+ Bônus</a>
            </div>

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($lancamentos as $lancamento)
                    @php
                        $bloqueado = $lancamento->periodo && in_array($lancamento->periodo->status, ['fechado', 'pago'], true);
                    @endphp
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ $lancamento->funcionario?->nome }}
                                · {{ \App\Models\Lancamento::TIPOS[$lancamento->tipo] ?? $lancamento->tipo }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $lancamento->data->format('d/m/Y') }}
                                · R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                                @if ($lancamento->descricao) · {{ $lancamento->descricao }} @endif
                            </div>
                            @if ($lancamento->periodo)
                                <div class="text-xs text-gray-400 mt-1">
                                    Período: {{ $lancamento->periodo->nome ?: ($lancamento->periodo->data_inicio->format('d/m') . '–' . $lancamento->periodo->data_fim->format('d/m')) }}
                                    ({{ \App\Models\PeriodoPagamento::STATUS[$lancamento->periodo->status] ?? $lancamento->periodo->status }})
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-3 text-sm">
                            @unless ($bloqueado)
                                <a href="{{ route('lancamentos.edit', $lancamento) }}" class="text-stone-700 underline">Editar</a>
                                <form method="POST" action="{{ route('lancamentos.destroy', $lancamento) }}" onsubmit="return confirm('Remover este lançamento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 underline">Remover</button>
                                </form>
                            @else
                                <span class="text-gray-400">Bloqueado</span>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-gray-500">Nenhum lançamento ainda.</div>
                @endforelse
            </div>

            <div>{{ $lancamentos->links() }}</div>
        </div>
    </div>
</x-app-layout>
