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

                <div class="space-y-6">
                    @if ($agrupado['diretos']->isNotEmpty())
                        <section class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-800">Pessoal direto</h3>
                                <span class="text-xs text-gray-500">{{ $agrupado['diretos']->count() }} pessoa(s)</span>
                            </div>

                            <div class="bg-white shadow-sm rounded-lg divide-y">
                                @foreach ($agrupado['diretos'] as $funcionario)
                                    @include('presencas._marcacao-linha', [
                                        'funcionario' => $funcionario,
                                        'indentado' => false,
                                        'papel' => null,
                                    ])
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($agrupado['equipes']->isNotEmpty())
                        <section class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-800">Equipes</h3>
                                <span class="text-xs text-gray-500">{{ $agrupado['equipes']->count() }} equipe(s)</span>
                            </div>

                            <div class="space-y-4">
                                @foreach ($agrupado['equipes'] as $bloco)
                                    <div class="rounded-xl border-2 border-stone-300 overflow-hidden shadow-sm bg-white">
                                        <x-equipe-cabecalho
                                            :lider="$bloco['lider']"
                                            :membros-count="$bloco['membros']->count()"
                                        />

                                        <div class="divide-y">
                                            @if ($bloco['lider']->isDiaria())
                                                @include('presencas._marcacao-linha', [
                                                    'funcionario' => $bloco['lider'],
                                                    'indentado' => false,
                                                    'papel' => 'lider',
                                                ])
                                            @endif

                                            @foreach ($bloco['membros'] as $funcionario)
                                                @include('presencas._marcacao-linha', [
                                                    'funcionario' => $funcionario,
                                                    'indentado' => true,
                                                    'papel' => 'ajudante',
                                                ])
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($agrupado['diretos']->isEmpty() && $agrupado['equipes']->isEmpty())
                        <div class="bg-white shadow-sm rounded-lg p-6 text-gray-500">
                            Nenhuma pessoa em diária ativa. Empreiteiros não aparecem aqui.
                        </div>
                    @endif
                </div>

                @if (! $periodoFechado && $funcionarios->isNotEmpty())
                    <x-primary-button class="w-full justify-center sm:w-auto">Salvar presenças</x-primary-button>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
