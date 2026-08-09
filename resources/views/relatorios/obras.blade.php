<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Custo por obra</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="bg-white shadow-sm rounded-lg p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div>
                    <x-input-label for="inicio" value="Início" />
                    <x-text-input id="inicio" name="inicio" type="date" class="block mt-1 w-full" :value="$inicio" />
                </div>
                <div>
                    <x-input-label for="fim" value="Fim" />
                    <x-text-input id="fim" name="fim" type="date" class="block mt-1 w-full" :value="$fim" />
                </div>
                <x-primary-button>Filtrar</x-primary-button>
            </form>

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($resumo as $item)
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <div class="font-medium text-gray-900">{{ $item['obra_nome'] }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $item['dias'] }} dia(s) de trabalho
                                · Diárias R$ {{ number_format($item['total_diarias'], 2, ',', '.') }}
                                @if ($item['total_locomocao'] > 0)
                                    · Locomoção R$ {{ number_format($item['total_locomocao'], 2, ',', '.') }}
                                @endif
                            </div>
                        </div>
                        <div class="text-lg font-semibold">
                            R$ {{ number_format($item['total'], 2, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-gray-500">Nenhuma presença neste intervalo.</div>
                @endforelse
            </div>

            @if ($resumo->isNotEmpty())
                <div class="text-right text-lg font-semibold">
                    Total:
                    R$ {{ number_format($resumo->sum('total'), 2, ',', '.') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
