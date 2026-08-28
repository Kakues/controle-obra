<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo funcionário</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('funcionarios.store') }}"
                  class="bg-white shadow-sm rounded-lg p-6 space-y-4"
                  x-data="{ regime: '{{ old('regime', 'diaria') }}' }">
                @csrf
                <div>
                    <x-input-label for="nome" value="Nome" />
                    <x-text-input id="nome" name="nome" class="block mt-1 w-full" :value="old('nome')" required />
                    <x-input-error :messages="$errors->get('nome')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="regime" value="Regime de pagamento" />
                    <select id="regime" name="regime" x-model="regime" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach (\App\Models\Funcionario::REGIMES as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('regime', 'diaria') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Diária: marca presença no calendário. Empreita: lança o valor da semana (sem marcação diária).
                    </p>
                    <x-input-error :messages="$errors->get('regime')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="telefone" value="Telefone" />
                    <x-text-input id="telefone" name="telefone" class="block mt-1 w-full" :value="old('telefone')" />
                </div>
                <div x-show="regime === 'diaria'" x-cloak>
                    <x-input-label for="diaria_atual" value="Valor da diária (R$)" />
                    <x-text-input id="diaria_atual" name="diaria_atual" type="number" step="0.01" min="0" class="block mt-1 w-full" :value="old('diaria_atual')" />
                    <x-input-error :messages="$errors->get('diaria_atual')" class="mt-2" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="regime === 'diaria'" x-cloak>
                    <div>
                        <x-input-label for="locomocao_tipo" value="Auxílio de locomoção" />
                        <select id="locomocao_tipo" name="locomocao_tipo" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (\App\Models\Funcionario::LOCOMOCAO_TIPOS as $valor => $label)
                                <option value="{{ $valor }}" @selected(old('locomocao_tipo', 'nenhuma') === $valor)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="locomocao_valor" value="Valor por dia (R$)" />
                        <x-text-input id="locomocao_valor" name="locomocao_valor" type="number" step="0.01" min="0" class="block mt-1 w-full" :value="old('locomocao_valor', '0')" />
                        <p class="text-xs text-gray-500 mt-1">Somado em cada dia trabalhado.</p>
                    </div>
                </div>
                <div x-show="regime === 'empreita'" x-cloak class="text-sm text-gray-600 bg-stone-50 border border-stone-200 rounded-md px-3 py-2">
                    Cadastre o valor da semana em <strong>Lançamentos → Valor empreita</strong>.
                </div>
                <div>
                    <x-input-label for="observacoes" value="Observações" />
                    <textarea id="observacoes" name="observacoes" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('observacoes') }}</textarea>
                </div>
                <p class="text-xs text-gray-500">A pessoa entra ativa na equipe. Se sair depois, use “Remover da equipe” — o histórico fica guardado.</p>
                <div class="flex gap-3">
                    <x-primary-button>Salvar</x-primary-button>
                    <a href="{{ route('funcionarios.index') }}" class="text-sm text-gray-600 underline self-center">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
