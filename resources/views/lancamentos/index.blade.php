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

            <form method="GET"
                  class="bg-white shadow-sm rounded-lg p-4 space-y-4"
                  x-data
                  @change="$el.submit()">
                <input type="hidden" name="filtro" value="1">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="periodo_pagamento_id" value="Período" />
                        <select id="periodo_pagamento_id" name="periodo_pagamento_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="" @selected(! $periodoId)>Todos os períodos</option>
                            @foreach ($periodos as $periodo)
                                <option value="{{ $periodo->id }}" @selected($periodoId == $periodo->id)>
                                    {{ $periodo->label() }}
                                    · {{ $periodo->badgeStatus() }}
                                </option>
                            @endforeach
                        </select>
                        @if ($periodoFiltro)
                            <p class="text-xs text-gray-500 mt-1">
                                Mostrando lançamentos do período
                                <a href="{{ route('periodos.show', $periodoFiltro) }}" class="underline text-stone-700">{{ $periodoFiltro->badgeStatus() }}</a>.
                            </p>
                        @elseif (! $filtroExplicito)
                            <p class="text-xs text-gray-500 mt-1">Padrão: período aberto mais recente.</p>
                        @endif
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
                </div>

                <div>
                    <x-input-label value="Tipos de lançamento" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (\App\Models\Lancamento::TIPOS as $valor => $label)
                            @php
                                $estilo = match ($valor) {
                                    'adiantamento' => 'bg-amber-50 text-amber-900 border-amber-200',
                                    'desconto' => 'bg-red-50 text-red-900 border-red-200',
                                    'bonus' => 'bg-emerald-50 text-emerald-900 border-emerald-200',
                                    'empreita' => 'bg-sky-50 text-sky-900 border-sky-200',
                                    default => 'bg-gray-50 text-gray-900 border-gray-200',
                                };
                                $marcado = in_array($valor, $tiposSelecionados, true);
                            @endphp
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-md border text-sm cursor-pointer
                                {{ $marcado ? $estilo : 'bg-white text-gray-600 border-gray-200' }}">
                                <input type="checkbox" name="tipos[]" value="{{ $valor }}" @checked($marcado) class="rounded border-gray-300">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Os filtros são aplicados automaticamente ao alterar.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('lancamentos.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700">
                        Limpar filtros
                    </a>
                </div>
            </form>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('lancamentos.create', ['tipo' => 'adiantamento', 'periodo_pagamento_id' => $periodoId]) }}" class="text-sm px-3 py-2 rounded-md bg-amber-50 text-amber-900 border border-amber-200">+ Adiantamento</a>
                <a href="{{ route('lancamentos.create', ['tipo' => 'desconto', 'periodo_pagamento_id' => $periodoId]) }}" class="text-sm px-3 py-2 rounded-md bg-red-50 text-red-900 border border-red-200">+ Desconto</a>
                <a href="{{ route('lancamentos.create', ['tipo' => 'bonus', 'periodo_pagamento_id' => $periodoId]) }}" class="text-sm px-3 py-2 rounded-md bg-emerald-50 text-emerald-900 border border-emerald-200">+ Bônus</a>
                <a href="{{ route('lancamentos.create', ['tipo' => 'empreita', 'periodo_pagamento_id' => $periodoId]) }}" class="text-sm px-3 py-2 rounded-md bg-sky-50 text-sky-900 border border-sky-200">+ Valor empreita</a>
            </div>

            @if ($periodoFiltro)
                <div class="bg-stone-50 border border-stone-200 rounded-lg px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div class="font-medium text-stone-900">{{ $periodoFiltro->label() }}</div>
                        <div class="text-sm text-stone-600">{{ $periodoFiltro->badgeStatus() }}</div>
                    </div>
                    <a href="{{ route('periodos.show', $periodoFiltro) }}" class="text-sm text-stone-800 underline whitespace-nowrap">
                        Abrir período
                    </a>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($lancamentos as $lancamento)
                    @php
                        $bloqueado = $lancamento->periodo && in_array($lancamento->periodo->status, ['fechado', 'pago'], true);
                        $tipoCores = match ($lancamento->tipo) {
                            'adiantamento' => 'bg-amber-100 text-amber-900',
                            'desconto' => 'bg-red-100 text-red-900',
                            'bonus' => 'bg-emerald-100 text-emerald-900',
                            'empreita' => 'bg-sky-100 text-sky-900',
                            default => 'bg-gray-100 text-gray-800',
                        };
                        $statusCores = match ($lancamento->periodo?->status) {
                            'aberto' => 'bg-blue-100 text-blue-900',
                            'fechado' => 'bg-amber-100 text-amber-900',
                            'pago' => 'bg-emerald-100 text-emerald-900',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-gray-900">{{ $lancamento->funcionario?->nome }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $tipoCores }}">
                                    {{ \App\Models\Lancamento::TIPOS[$lancamento->tipo] ?? $lancamento->tipo }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ $lancamento->data->format('d/m/Y') }}
                                · <span class="font-medium text-gray-900">R$ {{ number_format($lancamento->valor, 2, ',', '.') }}</span>
                                @if ($lancamento->descricao)
                                    · {{ $lancamento->descricao }}
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                @if ($lancamento->periodo)
                                    <a href="{{ route('periodos.show', $lancamento->periodo) }}"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-stone-100 text-stone-800 hover:bg-stone-200">
                                        {{ $lancamento->periodo->label() }}
                                    </a>
                                    <span class="px-2 py-0.5 rounded-full {{ $statusCores }}">
                                        {{ $lancamento->periodo->badgeStatus() }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-md bg-gray-100 text-gray-600">Sem período vinculado</span>
                                @endif
                            </div>
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
                                <span class="text-gray-400">Bloqueado (período {{ strtolower($lancamento->periodo->badgeStatus()) }})</span>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-gray-500">
                        @if (empty($tiposSelecionados))
                            Marque pelo menos um tipo de lançamento para listar.
                        @else
                            Nenhum lançamento encontrado com estes filtros.
                        @endif
                    </div>
                @endforelse
            </div>

            <div>{{ $lancamentos->links() }}</div>
        </div>
    </div>
</x-app-layout>
