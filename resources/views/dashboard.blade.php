<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Início</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <div class="text-sm text-gray-500">Funcionários ativos</div>
                    <div class="text-3xl font-semibold mt-1">{{ $totalFuncionarios }}</div>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <div class="text-sm text-gray-500">Obras ativas</div>
                    <div class="text-3xl font-semibold mt-1">{{ $totalObras }}</div>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <div class="text-sm text-gray-500">Períodos abertos</div>
                    <div class="text-3xl font-semibold mt-1">{{ $periodosAbertos }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('presencas.marcacao') }}" class="block bg-stone-800 text-white rounded-lg p-5 hover:bg-stone-700 transition">
                    <div class="text-lg font-semibold">Marcar presenças de hoje</div>
                    <div class="text-sm text-stone-300 mt-1">Lista rápida para marcar quem trabalhou</div>
                </a>

                @if (Auth::user()->isAdmin())
                    <a href="{{ route('lancamentos.create') }}" class="block bg-white border border-gray-200 rounded-lg p-5 hover:border-stone-400 transition">
                        <div class="text-lg font-semibold text-gray-800">Adiantamento ou desconto</div>
                        <div class="text-sm text-gray-500 mt-1">Vale, ferramenta, bônus e afins</div>
                    </a>
                    <a href="{{ route('periodos.create') }}" class="block bg-white border border-gray-200 rounded-lg p-5 hover:border-stone-400 transition">
                        <div class="text-lg font-semibold text-gray-800">Novo período de pagamento</div>
                        <div class="text-sm text-gray-500 mt-1">Semanal, quinzenal ou mensal</div>
                    </a>
                    <a href="{{ route('relatorios.obras') }}" class="block bg-white border border-gray-200 rounded-lg p-5 hover:border-stone-400 transition">
                        <div class="text-lg font-semibold text-gray-800">Custo por obra</div>
                        <div class="text-sm text-gray-500 mt-1">Quanto cada obra gastou de mão de obra</div>
                    </a>
                @else
                    <a href="{{ route('periodos.index') }}" class="block bg-white border border-gray-200 rounded-lg p-5 hover:border-stone-400 transition">
                        <div class="text-lg font-semibold text-gray-800">Ver pagamentos</div>
                        <div class="text-sm text-gray-500 mt-1">Consulta dos períodos (somente leitura)</div>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
