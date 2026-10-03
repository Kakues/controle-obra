<div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="font-medium text-gray-900">{{ $funcionario->nome }}</span>
        <x-papel-equipe :papel="$papel" />
        @if ($funcionario->temEquipe())
            <span class="text-[10px] uppercase tracking-wide font-semibold px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 border border-stone-200">
                {{ $funcionario->equipe->count() }} na equipe
            </span>
        @endif
    </div>
    <div class="text-sm text-gray-500 mt-1">
        {{ $funcionario->labelRegime() }}
        @if ($funcionario->isDiaria())
            · Diária R$ {{ number_format($funcionario->diaria_atual, 2, ',', '.') }}
            @if ($funcionario->temLocomocao())
                · {{ $funcionario->labelLocomocao() }} R$ {{ number_format($funcionario->locomocao_valor, 2, ',', '.') }}/dia
            @endif
        @else
            · Valor lançado por período
        @endif
        @if ($funcionario->telefone) · {{ $funcionario->telefone }} @endif
    </div>
    <div class="text-xs mt-1 {{ $funcionario->ativo ? 'text-green-700' : 'text-gray-400' }}">
        {{ $funcionario->ativo ? 'Ativo na equipe' : 'Removido (histórico preservado)' }}
    </div>
</div>
<span class="text-sm text-stone-600 shrink-0">Ver</span>
