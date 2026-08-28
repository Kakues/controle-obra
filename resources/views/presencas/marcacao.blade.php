<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Marcar presenças</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('presencas.marcacao') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <x-input-label for="data" value="Data" />
                    <x-text-input id="data" name="data" type="date" class="block mt-1" :value="$data" />
                </div>
                <x-primary-button>Trocar data</x-primary-button>
            </form>

            @if ($periodoFechado)
                <div class="bg-amber-50 text-amber-900 border border-amber-200 rounded-lg px-4 py-3">
                    Esta data está em um período fechado/pago. Não é possível alterar.
                </div>
            @elseif ($periodoAberto)
                <div class="bg-sky-50 text-sky-900 border border-sky-200 rounded-lg px-4 py-3 text-sm">
                    Este dia entra no período
                    <a href="{{ route('periodos.show', $periodoAberto) }}" class="underline font-medium">
                        {{ $periodoAberto->nome ?: ($periodoAberto->data_inicio->format('d/m') . '–' . $periodoAberto->data_fim->format('d/m')) }}
                    </a>.
                </div>
            @else
                <div class="bg-gray-50 text-gray-700 border border-gray-200 rounded-lg px-4 py-3 text-sm">
                    Ainda não há período de pagamento aberto para este dia.
                    Pode marcar normalmente — quando criar o período cobrindo esta data, as presenças entram sozinhas.
                    <a href="{{ route('periodos.create') }}" class="underline font-medium">Criar período</a>
                </div>
            @endif

            <form method="POST" action="{{ route('presencas.marcacao.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="data" value="{{ $data }}">

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <x-input-label for="obra_padrao_id" value="Obra padrão do dia" />
                    <select id="obra_padrao_id" name="obra_padrao_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" @disabled($periodoFechado)>
                        <option value="">Selecione...</option>
                        @foreach ($obras as $obra)
                            <option value="{{ $obra->id }}">{{ $obra->nome }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Usada quando a pessoa não tiver obra específica.</p>
                </div>

                <p class="text-xs text-gray-500">Só entram pessoas em regime de diária. Empreiteiros são pagos por lançamento.</p>

                <div class="bg-white shadow-sm rounded-lg divide-y">
                    @forelse ($funcionarios as $funcionario)
                        @php $presenca = $presencas->get($funcionario->id); @endphp
                        <div class="p-4 space-y-3" x-data="{
                            presente: {{ $presenca ? 'true' : 'false' }},
                            pagarLocomocao: {{ ($presenca ? (float) $presenca->valor_locomocao > 0 : $funcionario->temLocomocao()) ? 'true' : 'false' }}
                        }">
                            <label class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $funcionario->nome }}</div>
                                    <div class="text-xs text-gray-500">
                                        Diária R$ {{ number_format($funcionario->diaria_atual, 2, ',', '.') }}
                                        @if ($funcionario->temLocomocao())
                                            · {{ $funcionario->labelLocomocao() }} R$ {{ number_format($funcionario->locomocao_valor, 2, ',', '.') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600">Foi</span>
                                    <input type="checkbox"
                                           name="marcacoes[{{ $funcionario->id }}][presente]"
                                           value="1"
                                           x-model="presente"
                                           class="rounded border-gray-300"
                                           @disabled($periodoFechado)>
                                </div>
                            </label>

                            <div x-show="presente" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500">Obra</label>
                                    <select name="marcacoes[{{ $funcionario->id }}][obra_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" @disabled($periodoFechado)>
                                        <option value="">Usar padrão</option>
                                        @foreach ($obras as $obra)
                                            <option value="{{ $obra->id }}" @selected(optional($presenca)->obra_id == $obra->id)>{{ $obra->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Tipo</label>
                                    <select name="marcacoes[{{ $funcionario->id }}][tipo]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" @disabled($periodoFechado)>
                                        @foreach (\App\Models\Presenca::TIPOS as $valor => $label)
                                            <option value="{{ $valor }}" @selected(optional($presenca)->tipo == $valor)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Valor especial (opcional)</label>
                                    <input type="number" step="0.01" min="0" inputmode="decimal"
                                           name="marcacoes[{{ $funcionario->id }}][valor_especial]"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                                           value="{{ optional($presenca)->tipo === 'especial' ? $presenca->valor_aplicado : '' }}"
                                           @disabled($periodoFechado)>
                                </div>
                            </div>

                            @if ($funcionario->temLocomocao())
                                <label x-show="presente" class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="hidden" name="marcacoes[{{ $funcionario->id }}][pagar_locomocao]" value="0">
                                    <input type="checkbox"
                                           name="marcacoes[{{ $funcionario->id }}][pagar_locomocao]"
                                           value="1"
                                           x-model="pagarLocomocao"
                                           class="rounded border-gray-300"
                                           @disabled($periodoFechado)>
                                    Pagar locomoção neste dia
                                    ({{ $funcionario->labelLocomocao() }} · R$ {{ number_format($funcionario->locomocao_valor, 2, ',', '.') }})
                                </label>
                            @endif
                        </div>
                    @empty
                        <div class="p-6 text-gray-500">Nenhuma pessoa em diária ativa. Empreiteiros não aparecem aqui.</div>
                    @endforelse
                </div>

                @if (! $periodoFechado && $funcionarios->isNotEmpty())
                    <x-primary-button class="w-full justify-center sm:w-auto">Salvar presenças</x-primary-button>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
