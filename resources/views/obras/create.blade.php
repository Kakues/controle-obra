<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nova obra</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('obras.store') }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="nome" value="Nome" />
                    <x-text-input id="nome" name="nome" class="block mt-1 w-full" :value="old('nome')" required />
                    <x-input-error :messages="$errors->get('nome')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="endereco" value="Endereço" />
                    <x-text-input id="endereco" name="endereco" class="block mt-1 w-full" :value="old('endereco')" />
                </div>
                <div>
                    <x-input-label for="observacoes" value="Observações" />
                    <textarea id="observacoes" name="observacoes" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('observacoes') }}</textarea>
                </div>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="ativa" value="1" checked class="rounded border-gray-300">
                    <span class="text-sm text-gray-700">Obra ativa</span>
                </label>
                <div class="flex gap-3">
                    <x-primary-button>Salvar</x-primary-button>
                    <a href="{{ route('obras.index') }}" class="text-sm text-gray-600 underline self-center">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
