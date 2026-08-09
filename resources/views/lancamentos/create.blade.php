<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo lançamento</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('error'))
                <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('lancamentos.store') }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="tipo" value="Tipo" />
                    <select id="tipo" name="tipo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach (\App\Models\Lancamento::TIPOS as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('tipo', $tipoPadrao) === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="funcionario_id" value="Funcionário" />
                    <select id="funcionario_id" name="funcionario_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach ($funcionarios as $funcionario)
                            <option value="{{ $funcionario->id }}" @selected(old('funcionario_id', $funcionarioId) == $funcionario->id)>
                                {{ $funcionario->nome }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('funcionario_id')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="valor" value="Valor (R$)" />
                        <x-text-input id="valor" name="valor" type="number" step="0.01" min="0.01" class="block mt-1 w-full" :value="old('valor')" required />
                        <x-input-error :messages="$errors->get('valor')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="data" value="Data" />
                        <x-text-input id="data" name="data" type="date" class="block mt-1 w-full" :value="old('data', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('data')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="periodo_pagamento_id" value="Período (opcional)" />
                    <select id="periodo_pagamento_id" name="periodo_pagamento_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Detectar automaticamente pela data</option>
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected(old('periodo_pagamento_id', $periodoId) == $periodo->id)>
                                {{ $periodo->nome ?: 'Período' }}
                                · {{ $periodo->data_inicio->format('d/m/Y') }} a {{ $periodo->data_fim->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Se houver um período aberto cobrindo a data, ele será vinculado sozinho.</p>
                </div>

                <div>
                    <x-input-label for="descricao" value="Descrição" />
                    <x-text-input id="descricao" name="descricao" class="block mt-1 w-full" :value="old('descricao')" placeholder="Ex: Vale, ferramenta, gasolina extra..." />
                </div>

                <div class="flex gap-3">
                    <x-primary-button>Salvar</x-primary-button>
                    <a href="{{ route('lancamentos.index') }}" class="text-sm text-gray-600 underline self-center">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
