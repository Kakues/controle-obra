<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Obras</h2>
            <a href="{{ route('obras.create') }}" class="inline-flex items-center px-3 py-2 bg-stone-800 text-white text-sm rounded-md">Nova obra</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($obras as $obra)
                    <div class="p-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium text-gray-900">{{ $obra->nome }}</div>
                            @if ($obra->endereco)
                                <div class="text-sm text-gray-500">{{ $obra->endereco }}</div>
                            @endif
                            <div class="text-xs mt-1 {{ $obra->ativa ? 'text-green-700' : 'text-gray-400' }}">
                                {{ $obra->ativa ? 'Ativa' : 'Inativa' }}
                            </div>
                        </div>
                        <a href="{{ route('obras.edit', $obra) }}" class="text-sm text-stone-700 underline">Editar</a>
                    </div>
                @empty
                    <div class="p-6 text-gray-500">Nenhuma obra cadastrada.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
