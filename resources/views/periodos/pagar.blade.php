<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar pagamentos</h2>
            <p class="text-sm text-gray-500">
                {{ $periodo->nome ?: 'Período' }}
                · {{ $periodo->data_inicio->format('d/m/Y') }} a {{ $periodo->data_fim->format('d/m/Y') }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('error'))
                <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('periodos.pagar', $periodo) }}" class="space-y-4">
                @csrf

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <x-input-label for="data_pagamento" value="Data do pagamento" />
                    <x-text-input id="data_pagamento" name="data_pagamento" type="date" class="block mt-1 w-full sm:w-60" :value="old('data_pagamento', now()->toDateString())" required />
                    <x-input-error :messages="$errors->get('data_pagamento')" class="mt-2" />
                </div>

                <div class="bg-white shadow-sm rounded-lg divide-y">
                    @foreach ($resumo as $item)
                        @php $funcionarioId = $item['funcionario']->id; @endphp
                        <div class="p-4 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $item['funcionario']->nome }}</div>
                                    <div class="text-sm text-gray-500">{{ $item['dias'] }} dia(s)</div>
                                </div>
                                <div class="text-lg font-semibold">
                                    R$ {{ number_format($item['a_pagar'], 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500">Forma de pagamento</label>
                                    <select name="pagamentos[{{ $funcionarioId }}][forma]"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                                            required>
                                        @foreach (\App\Models\PagamentoFuncionario::FORMAS as $valor => $label)
                                            <option value="{{ $valor }}" @selected(old("pagamentos.$funcionarioId.forma", 'pix') === $valor)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Observação (opcional)</label>
                                    <input type="text"
                                           name="pagamentos[{{ $funcionarioId }}][observacao]"
                                           value="{{ old("pagamentos.$funcionarioId.observacao") }}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                                           placeholder="Ex: pago no canteiro">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-primary-button>Confirmar pagamentos</x-primary-button>
                    <a href="{{ route('periodos.show', $periodo) }}" class="text-sm text-gray-600 underline self-center">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
