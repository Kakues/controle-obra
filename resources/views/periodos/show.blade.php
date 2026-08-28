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

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($resumo as $item)
                    @php
                        $pagamento = $pagamentos->get($item['funcionario']?->id);
                    @endphp
                    <div class="p-4" x-data="{ aberto: false }">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <div class="font-medium text-gray-900">{{ $item['funcionario']?->nome }}</div>
                                <div class="text-sm text-gray-500">
                                    @if ($item['dias'] > 0)
                                        {{ $item['dias'] }} dia(s)
                                        · Diárias R$ {{ number_format($item['total_diarias'], 2, ',', '.') }}
                                    @endif
                                    @if ($item['empreitas'] > 0)
                                        @if ($item['dias'] > 0) · @endif
                                        Empreita R$ {{ number_format($item['empreitas'], 2, ',', '.') }}
                                    @endif
                                    @if ($item['total_locomocao'] > 0) · Locomoção R$ {{ number_format($item['total_locomocao'], 2, ',', '.') }} @endif
                                    @if ($item['bonus'] > 0) · Bônus R$ {{ number_format($item['bonus'], 2, ',', '.') }} @endif
                                    @if ($item['adiantamentos'] > 0) · Adiant. R$ {{ number_format($item['adiantamentos'], 2, ',', '.') }} @endif
                                    @if ($item['descontos'] > 0) · Desc. R$ {{ number_format($item['descontos'], 2, ',', '.') }} @endif
                                </div>
                                @if ($pagamento)
                                    <div class="text-sm text-emerald-700 mt-1">
                                        Pago em {{ $pagamento->labelForma() }}
                                        @if ($pagamento->data_pagamento)
                                            · {{ $pagamento->data_pagamento->format('d/m/Y') }}
                                        @endif
                                        @if ($pagamento->observacao)
                                            · {{ $pagamento->observacao }}
                                        @endif
                                    </div>
                                @endif
                                @if ($item['presencas']->isNotEmpty())
                                    <button type="button" @click="aberto = !aberto" class="text-xs text-stone-600 underline mt-1">
                                        <span x-text="aberto ? 'Ocultar dias' : 'Ver dias marcados'"></span>
                                    </button>
                                @endif
                            </div>
                            <div class="text-lg font-semibold text-gray-900">
                                R$ {{ number_format($item['a_pagar'], 2, ',', '.') }}
                            </div>
                        </div>
                        <div x-show="aberto" x-cloak class="mt-3 text-sm text-gray-600 space-y-2 border-t border-gray-100 pt-3">
                            @foreach ($item['presencas'] as $presenca)
                                @php
                                    $lancamentosDoDia = $item['lancamentos']
                                        ->filter(fn ($lancamento) => $lancamento->data->isSameDay($presenca->data));
                                    $descontosDoDia = (float) $lancamentosDoDia->where('tipo', 'desconto')->sum('valor');
                                    $adiantamentosDoDia = (float) $lancamentosDoDia->where('tipo', 'adiantamento')->sum('valor');
                                    $bonusDoDia = (float) $lancamentosDoDia->where('tipo', 'bonus')->sum('valor');
                                    $totalDia = round(
                                        (float) $presenca->valor_aplicado
                                        + (float) $presenca->valor_locomocao
                                        + $bonusDoDia
                                        - $descontosDoDia
                                        - $adiantamentosDoDia,
                                        2
                                    );
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex justify-between gap-3">
                                        <span>
                                            {{ $presenca->data->format('d/m/Y') }}
                                            · {{ $presenca->obra?->nome }}
                                            · {{ \App\Models\Presenca::TIPOS[$presenca->tipo] ?? $presenca->tipo }}
                                        </span>
                                        <span class="font-medium whitespace-nowrap">
                                            R$ {{ number_format($totalDia, 2, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap justify-between gap-x-3 gap-y-1 text-xs pl-3 border-l-2 border-gray-100">
                                        <span class="text-gray-500">
                                            Diária R$ {{ number_format($presenca->valor_aplicado, 2, ',', '.') }}
                                            @if ($presenca->valor_locomocao > 0)
                                                · Locomoção R$ {{ number_format($presenca->valor_locomocao, 2, ',', '.') }}
                                            @endif
                                        </span>
                                    </div>
                                    @foreach ($lancamentosDoDia as $lancamento)
                                        <div class="flex justify-between gap-3 text-xs pl-3 border-l-2 {{ in_array($lancamento->tipo, ['desconto', 'adiantamento'], true) ? 'border-red-200 text-red-700' : 'border-emerald-200 text-emerald-700' }}">
                                            <span>
                                                {{ \App\Models\Lancamento::TIPOS[$lancamento->tipo] ?? $lancamento->tipo }}
                                                @if ($lancamento->descricao)
                                                    · {{ $lancamento->descricao }}
                                                @endif
                                            </span>
                                            <span class="whitespace-nowrap">
                                                {{ in_array($lancamento->tipo, ['desconto', 'adiantamento'], true) ? '−' : '+' }}
                                                R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-gray-500">Nenhuma presença ou lançamento neste período.</div>
                @endforelse
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
