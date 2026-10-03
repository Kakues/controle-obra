<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $periodo->nome ?: 'Período' }}
            </h2>
            <p class="text-sm text-gray-500">
                {{ $periodo->data_inicio->format('d/m/Y') }} a {{ $periodo->data_fim->format('d/m/Y') }}
                · {{ \App\Models\PeriodoPagamento::STATUS[$periodo->status] ?? $periodo->status }}
            </p>
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

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('periodos.comprovante', $periodo) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700"
                   target="_blank">
                    Comprovante
                </a>

                @if (Auth::user()->isAdmin())
                    @if ($periodo->status === 'aberto')
                        <a href="{{ route('lancamentos.create', ['periodo_pagamento_id' => $periodo->id]) }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700">
                            + Lançamento
                        </a>
                        <form method="POST" action="{{ route('periodos.fechar', $periodo) }}">
                            @csrf
                            <x-primary-button>Fechar período</x-primary-button>
                        </form>
                    @endif
                    @if ($periodo->status === 'fechado')
                        <a href="{{ route('periodos.pagar.form', $periodo) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Registrar pagamentos
                        </a>
                    @endif
                    @if ($periodo->status !== 'aberto')
                        <form method="POST" action="{{ route('periodos.reabrir', $periodo) }}">
                            @csrf
                            <button class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700">Reabrir</button>
                        </form>
                    @endif
                @endif
            </div>

            <div class="space-y-4">
                @foreach ($agrupado['blocos'] as $bloco)
                    <div class="rounded-xl border-2 border-stone-300 overflow-hidden shadow-sm bg-white">
                        <x-equipe-cabecalho
                            :lider="$bloco['lider']"
                            :membros-count="$bloco['membros']->count()"
                            :total="$bloco['total_equipe']"
                        />

                        <div class="divide-y bg-white">
                            @if ($bloco['lider_item'])
                                @include('periodos._resumo-item', [
                                    'item' => $bloco['lider_item'],
                                    'indentado' => false,
                                    'papel' => 'lider',
                                    'pagamentos' => $pagamentos,
                                ])
                            @endif

                            @foreach ($bloco['membros'] as $item)
                                @include('periodos._resumo-item', [
                                    'item' => $item,
                                    'indentado' => true,
                                    'papel' => 'ajudante',
                                    'pagamentos' => $pagamentos,
                                ])
                            @endforeach
                        </div>

                        <div class="px-4 py-2 bg-stone-50 border-t border-stone-200 text-xs text-stone-600">
                            Você paga cada pessoa separadamente. O total da equipe é só referência.
                        </div>
                    </div>
                @endforeach

                @if ($agrupado['solos']->isNotEmpty())
                    <div class="bg-white shadow-sm rounded-lg divide-y border border-gray-200">
                        @if ($agrupado['blocos']->isNotEmpty())
                            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-700">
                                Sem equipe vinculada
                            </div>
                        @endif

                        @foreach ($agrupado['solos'] as $item)
                            @include('periodos._resumo-item', [
                                'item' => $item,
                                'indentado' => false,
                                'papel' => null,
                                'pagamentos' => $pagamentos,
                            ])
                        @endforeach
                    </div>
                @endif

                @if ($agrupado['blocos']->isEmpty() && $agrupado['solos']->isEmpty())
                    <div class="bg-white shadow-sm rounded-lg p-6 text-gray-500">Nenhuma presença ou lançamento neste período.</div>
                @endif
            </div>

            <div class="text-right text-lg font-semibold">
                Total geral:
                R$ {{ number_format(collect($resumo)->sum('a_pagar'), 2, ',', '.') }}
            </div>

            @if ($pagamentos->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-4 space-y-3">
                    <h3 class="font-medium text-gray-900">Resumo por forma de pagamento</h3>
                    <div class="space-y-2 text-sm">
                        @foreach ($pagamentos->groupBy('forma') as $forma => $grupo)
                            <div class="flex justify-between gap-3 border-b border-gray-100 pb-2">
                                <span>{{ \App\Models\PagamentoFuncionario::FORMAS[$forma] ?? $forma }} ({{ $grupo->count() }})</span>
                                <span class="font-medium">R$ {{ number_format($grupo->sum('valor'), 2, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-medium text-gray-900">Lançamentos do período</h3>
                    <a href="{{ route('lancamentos.index') }}" class="text-sm text-stone-700 underline">Ver todos</a>
                </div>
                <div class="divide-y">
                    @forelse ($lancamentos as $lancamento)
                        <div class="py-3 flex justify-between gap-3 text-sm">
                            <div>
                                <div class="font-medium text-gray-800">
                                    {{ $lancamento->funcionario?->nome }}
                                    · {{ \App\Models\Lancamento::TIPOS[$lancamento->tipo] ?? $lancamento->tipo }}
                                </div>
                                <div class="text-gray-500">
                                    {{ $lancamento->data->format('d/m/Y') }}
                                    @if ($lancamento->descricao) · {{ $lancamento->descricao }} @endif
                                </div>
                            </div>
                            <div class="font-medium whitespace-nowrap">
                                R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                            </div>
                        </div>
                    @empty
                        <div class="py-2 text-sm text-gray-500">Nenhum lançamento neste período.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
