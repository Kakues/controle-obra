<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar {{ $funcionario->nome }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @unless ($funcionario->ativo)
                <div class="bg-amber-50 text-amber-900 border border-amber-200 rounded-lg px-4 py-3 text-sm">
                    Esta pessoa está removida da equipe. O histórico continua disponível.
                </div>
            @endunless

            <form method="POST" action="{{ route('funcionarios.update', $funcionario) }}"
                  class="bg-white shadow-sm rounded-lg p-6 space-y-4"
                  x-data="{ regime: '{{ old('regime', $funcionario->regime ?? 'diaria') }}' }">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label for="nome" value="Nome" />
                    <x-text-input id="nome" name="nome" class="block mt-1 w-full" :value="old('nome', $funcionario->nome)" required />
                </div>
                <div>
                    <x-input-label for="regime" value="Regime de pagamento" />
                    <select id="regime" name="regime" x-model="regime" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                        @foreach (\App\Models\Funcionario::REGIMES as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('regime', $funcionario->regime ?? 'diaria') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="superior_id" value="Líder da equipe" />
                    <select id="superior_id" name="superior_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Trabalha direto comigo</option>
                        @foreach ($lideres as $lider)
                            <option value="{{ $lider->id }}" @selected(old('superior_id', $funcionario->superior_id) == $lider->id)>{{ $lider->nome }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Quem já lidera equipe não pode ter outro líder.
                    </p>
                </div>
                <div>
                    <x-input-label for="telefone" value="Telefone" />
                    <x-text-input id="telefone" name="telefone" class="block mt-1 w-full" :value="old('telefone', $funcionario->telefone)" />
                </div>
                <div x-show="regime === 'diaria'" x-cloak>
                    <x-input-label for="diaria_atual" value="Valor da diária (R$)" />
                    <x-text-input id="diaria_atual" name="diaria_atual" type="number" step="0.01" min="0" class="block mt-1 w-full" :value="old('diaria_atual', $funcionario->diaria_atual)" />
                    <p class="text-xs text-gray-500 mt-1">Se mudar a diária, o histórico guarda a data de vigência abaixo.</p>
                </div>
                <div x-show="regime === 'diaria'" x-cloak>
                    <x-input-label for="vigente_desde" value="Diária vigente desde" />
                    <x-text-input id="vigente_desde" name="vigente_desde" type="date" class="block mt-1 w-full" :value="old('vigente_desde', now()->toDateString())" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="regime === 'diaria'" x-cloak>
                    <div>
                        <x-input-label for="locomocao_tipo" value="Auxílio de locomoção" />
                        <select id="locomocao_tipo" name="locomocao_tipo" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (\App\Models\Funcionario::LOCOMOCAO_TIPOS as $valor => $label)
                                <option value="{{ $valor }}" @selected(old('locomocao_tipo', $funcionario->locomocao_tipo) === $valor)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="locomocao_valor" value="Valor por dia (R$)" />
                        <x-text-input id="locomocao_valor" name="locomocao_valor" type="number" step="0.01" min="0" class="block mt-1 w-full" :value="old('locomocao_valor', $funcionario->locomocao_valor)" />
                        <p class="text-xs text-gray-500 mt-1">Somado em cada dia trabalhado.</p>
                    </div>
                </div>
                <div>
                    <x-input-label for="observacoes" value="Observações" />
                    <textarea id="observacoes" name="observacoes" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('observacoes', $funcionario->observacoes) }}</textarea>
                </div>
                <div class="flex gap-3">
                    <x-primary-button>Salvar</x-primary-button>
                    <a href="{{ route('funcionarios.show', $funcionario) }}" class="text-sm text-gray-600 underline self-center">Voltar</a>
                </div>
            </form>

            <div class="bg-white shadow-sm rounded-lg p-6 space-y-3">
                <h3 class="font-medium text-gray-900">Equipe</h3>
                @if ($funcionario->ativo)
                    <p class="text-sm text-gray-600">
                        Remover tira a pessoa da marcação de presença e da equipe ativa.
                        Calendário, pagamentos e lançamentos antigos continuam salvos.
                    </p>
                    <form method="POST" action="{{ route('funcionarios.destroy', $funcionario) }}"
                          onsubmit="return confirm('Remover {{ $funcionario->nome }} da equipe? O histórico será mantido.')">
                        @csrf
                        @method('DELETE')
                        <button class="text-sm text-red-600 underline">Remover da equipe</button>
                    </form>
                @else
                    <p class="text-sm text-gray-600">
                        Esta pessoa está fora da equipe ativa. Você pode reativar quando quiser.
                    </p>
                    <form method="POST" action="{{ route('funcionarios.reativar', $funcionario) }}">
                        @csrf
                        <x-primary-button>Reativar na equipe</x-primary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
