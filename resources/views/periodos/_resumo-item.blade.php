@php
    $pagamento = $pagamentos->get($item['funcionario']?->id);
@endphp
<div class="p-4 {{ $indentado ? 'bg-sky-50/40 border-l-4 border-sky-300' : '' }}" x-data="{ aberto: false }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 {{ $indentado ? 'pl-1' : '' }}">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-medium text-gray-900">{{ $item['funcionario']?->nome }}</span>
                <x-papel-equipe :papel="$papel ?? null" />
            </div>
            <div class="text-sm text-gray-500">
                @if ($item['dias'] > 0)
                    {{ $item['dias'] }} dia(s)
                    · Diárias R$ {{ number_format($item['total_diarias'], 2, ',', '.') }}
                @endif
                @if ($item['empreitas'] > 0)
                    @if ($item['dias'] > 0)
                        ·
                    @endif
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
    <div x-show="aberto" x-cloak class="mt-3 text-sm text-gray-600 space-y-2 border-t border-gray-100 pt-3 {{ $indentado ? 'pl-2' : '' }}">
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
