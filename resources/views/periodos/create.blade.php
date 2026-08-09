<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo período</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('periodos.store') }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="nome" value="Nome (opcional)" />
                    <x-text-input id="nome" name="nome" class="block mt-1 w-full" :value="old('nome')" placeholder="Ex: Quinzena 1 - Agosto" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="data_inicio" value="Início" />
                        <x-text-input id="data_inicio" name="data_inicio" type="date" class="block mt-1 w-full" :value="old('data_inicio')" required />
                    </div>
                    <div>
                        <x-input-label for="data_fim" value="Fim" />
                        <x-text-input id="data_fim" name="data_fim" type="date" class="block mt-1 w-full" :value="old('data_fim')" required />
                    </div>
                </div>
                <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                    Presenças e lançamentos já feitos entre essas datas entram automaticamente no período.
                    Não precisa marcar de novo.
                </p>
                <div>
                    <x-input-label for="observacoes" value="Observações" />
                    <textarea id="observacoes" name="observacoes" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('observacoes') }}</textarea>
                </div>
                <div class="flex gap-3">
                    <x-primary-button>Criar</x-primary-button>
                    <a href="{{ route('periodos.index') }}" class="text-sm text-gray-600 underline self-center">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
