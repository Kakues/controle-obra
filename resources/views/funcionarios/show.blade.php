@php
    $diasNoMes = $inicio->daysInMonth;
    $primeiroDiaSemana = (int) $inicio->dayOfWeek; // 0=domingo
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $funcionario->nome }}</h2>
            @if (Auth::user()->isAdmin())
                <a href="{{ route('funcionarios.edit', $funcionario) }}" class="text-sm text-stone-700 underline">Editar</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-5">
                <div class="text-sm text-gray-500">Regime</div>
                <div class="text-2xl font-semibold">{{ $funcionario->labelRegime() }}</div>
                @if ($funcionario->isDiaria())
                    <div class="text-sm text-gray-500 mt-3">Diária atual</div>
                    <div class="text-xl font-semibold">R$ {{ number_format($funcionario->diaria_atual, 2, ',', '.') }}</div>
                    @if ($funcionario->temLocomocao())
                        <div class="text-sm text-gray-700 mt-2">
                            Locomoção: {{ $funcionario->labelLocomocao() }}
                            · R$ {{ number_format($funcionario->locomocao_valor, 2, ',', '.') }} por dia
                        </div>
                    @else
                        <div class="text-sm text-gray-500 mt-2">Sem auxílio de locomoção</div>
                    @endif
                @else
                    <div class="text-sm text-gray-600 mt-2">
                        Não entra na marcação de presença. Lance o valor da semana em Lançamentos.
                    </div>
                    @if (Auth::user()->isAdmin())
                        <a href="{{ route('lancamentos.create', ['tipo' => 'empreita', 'funcionario_id' => $funcionario->id]) }}"
                           class="inline-flex mt-3 text-sm text-stone-800 underline">+ Valor empreita</a>
                    @endif
                @endif
                @if ($funcionario->telefone)
                    <div class="text-sm text-gray-500 mt-2">{{ $funcionario->telefone }}</div>
                @endif
                @if ($funcionario->temSuperior())
                    <div class="text-sm text-stone-700 mt-2">
                        Líder: <a href="{{ route('funcionarios.show', $funcionario->superior) }}" class="underline">{{ $funcionario->superior->nome }}</a>
                    </div>
                @endif
                @if ($funcionario->equipe->isNotEmpty())
                    <div class="text-sm text-stone-700 mt-3">
                        <div class="font-medium">Equipe ({{ $funcionario->equipe->count() }})</div>
                        <ul class="mt-1 space-y-1">
                            @foreach ($funcionario->equipe as $membro)
                                <li>
                                    <a href="{{ route('funcionarios.show', $membro) }}" class="underline">{{ $membro->nome }}</a>
                                    @unless ($membro->ativo)
                                        <span class="text-gray-400">· removido</span>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="text-sm mt-3 {{ $funcionario->ativo ? 'text-green-700' : 'text-amber-700' }}">
                    {{ $funcionario->ativo ? 'Ativo na equipe' : 'Removido da equipe (histórico preservado)' }}
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    @if (Auth::user()->isAdmin())
                        @if ($funcionario->ativo)
                            <form method="POST" action="{{ route('funcionarios.destroy', $funcionario) }}"
                                  onsubmit="return confirm('Remover {{ $funcionario->nome }} da equipe? O histórico será mantido.')">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm text-red-600 underline">Remover da equipe</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('funcionarios.reativar', $funcionario) }}">
                                @csrf
                                <button class="text-sm text-stone-800 underline">Reativar na equipe</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            @if ($funcionario->isEmpreita())
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-medium text-gray-900 mb-3">Lançamentos recentes</h3>
                    <div class="space-y-2">
                        @forelse ($lancamentosRecentes as $lancamento)
                            <div class="flex justify-between text-sm border-b border-gray-100 pb-2 gap-3">
                                <span>
                                    {{ $lancamento->data->format('d/m/Y') }}
                                    · {{ \App\Models\Lancamento::TIPOS[$lancamento->tipo] ?? $lancamento->tipo }}
                                    @if ($lancamento->descricao) · {{ $lancamento->descricao }} @endif
                                </span>
                                <span class="font-medium whitespace-nowrap">R$ {{ number_format($lancamento->valor, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">Nenhum lançamento ainda.</div>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg p-5 space-y-4">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <x-input-label for="mes" value="Mês do calendário" />
                            <x-text-input id="mes" name="mes" type="month" class="block mt-1" :value="$mes" />
                        </div>
                        <x-primary-button>Ver</x-primary-button>
                    </form>

                    <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-500">
                        <div>D</div><div>S</div><div>T</div><div>Q</div><div>Q</div><div>S</div><div>S</div>
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        @for ($i = 0; $i < $primeiroDiaSemana; $i++)
                            <div class="aspect-square"></div>
                        @endfor

                        @for ($dia = 1; $dia <= $diasNoMes; $dia++)
                            @php
                                $data = $inicio->copy()->day($dia)->format('Y-m-d');
                                $presenca = $presencas->get($data);
                            @endphp
                            <div class="aspect-square rounded-md border text-xs flex flex-col items-center justify-center
                                {{ $presenca ? 'bg-stone-800 text-white border-stone-800' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                <span class="font-medium">{{ $dia }}</span>
                                @if ($presenca)
                                    <span class="opacity-80">R$ {{ number_format($presenca->valor_aplicado, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        @endfor
                    </div>

                    <div class="text-sm text-gray-600">
                        Dias marcados neste mês:
                        <strong>{{ $presencas->count() }}</strong>
                        · Diárias:
                        <strong>R$ {{ number_format($presencas->sum('valor_aplicado'), 2, ',', '.') }}</strong>
                        @if ($presencas->sum('valor_locomocao') > 0)
                            · Locomoção:
                            <strong>R$ {{ number_format($presencas->sum('valor_locomocao'), 2, ',', '.') }}</strong>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-medium text-gray-900 mb-3">Histórico de diárias</h3>
                    <div class="space-y-2">
                        @forelse ($historico as $item)
                            <div class="flex justify-between text-sm border-b border-gray-100 pb-2">
                                <span>Desde {{ $item->vigente_desde->format('d/m/Y') }}</span>
                                <span>R$ {{ number_format($item->valor, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">Sem histórico.</div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
