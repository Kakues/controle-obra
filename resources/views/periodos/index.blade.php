<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Períodos de pagamento</h2>
            @if (Auth::user()->isAdmin())
                <a href="{{ route('periodos.create') }}" class="inline-flex items-center px-3 py-2 bg-stone-800 text-white text-sm rounded-md">Novo período</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($periodos as $periodo)
                    <a href="{{ route('periodos.show', $periodo) }}" class="p-4 flex items-center justify-between gap-3 hover:bg-gray-50 block">
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ $periodo->nome ?: 'Período' }}
                                · {{ $periodo->data_inicio->format('d/m/Y') }} a {{ $periodo->data_fim->format('d/m/Y') }}
                            </div>
                            <div class="text-sm text-gray-500">{{ \App\Models\PeriodoPagamento::STATUS[$periodo->status] ?? $periodo->status }}</div>
                        </div>
                        <span class="text-sm text-stone-600">Abrir</span>
                    </a>
                @empty
                    <div class="p-6 text-gray-500">Nenhum período criado.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
