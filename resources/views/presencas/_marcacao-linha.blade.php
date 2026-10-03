@php $presenca = $presencas->get($funcionario->id); @endphp
<div class="p-4 space-y-3 {{ $indentado ? 'bg-sky-50/40 border-l-4 border-sky-300' : '' }}" x-data="{
    presente: {{ $presenca ? 'true' : 'false' }},
    pagarLocomocao: {{ ($presenca ? (float) $presenca->valor_locomocao > 0 : $funcionario->temLocomocao()) ? 'true' : 'false' }}
}">
    <label class="flex items-center justify-between gap-3 {{ $indentado ? 'pl-2' : '' }}">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-medium text-gray-900">{{ $funcionario->nome }}</span>
                <x-papel-equipe :papel="$papel ?? null" />
            </div>
            <div class="text-xs text-gray-500">
                Diária R$ {{ number_format($funcionario->diaria_atual, 2, ',', '.') }}
                @if ($funcionario->temLocomocao())
                    · {{ $funcionario->labelLocomocao() }} R$ {{ number_format($funcionario->locomocao_valor, 2, ',', '.') }}
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Foi</span>
            <input type="checkbox"
                   name="marcacoes[{{ $funcionario->id }}][presente]"
                   value="1"
                   x-model="presente"
                   class="rounded border-gray-300"
                   @disabled($periodoFechado)>
        </div>
    </label>

    <div x-show="presente" class="grid grid-cols-1 sm:grid-cols-3 gap-3 {{ $indentado ? 'pl-2' : '' }}">
        <div>
            <label class="text-xs text-gray-500">Obra</label>
            <select name="marcacoes[{{ $funcionario->id }}][obra_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" @disabled($periodoFechado)>
                <option value="">Usar padrão</option>
                @foreach ($obras as $obra)
                    <option value="{{ $obra->id }}" @selected(optional($presenca)->obra_id == $obra->id)>{{ $obra->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-gray-500">Tipo</label>
            <select name="marcacoes[{{ $funcionario->id }}][tipo]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" @disabled($periodoFechado)>
                @foreach (\App\Models\Presenca::TIPOS as $valor => $label)
                    <option value="{{ $valor }}" @selected(optional($presenca)->tipo == $valor)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-gray-500">Valor especial (opcional)</label>
            <input type="number" step="0.01" min="0" inputmode="decimal"
                   name="marcacoes[{{ $funcionario->id }}][valor_especial]"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                   value="{{ optional($presenca)->tipo === 'especial' ? $presenca->valor_aplicado : '' }}"
                   @disabled($periodoFechado)>
        </div>
    </div>

    @if ($funcionario->temLocomocao())
        <label x-show="presente" class="flex items-center gap-2 text-sm text-gray-700 {{ $indentado ? 'pl-2' : '' }}">
            <input type="hidden" name="marcacoes[{{ $funcionario->id }}][pagar_locomocao]" value="0">
            <input type="checkbox"
                   name="marcacoes[{{ $funcionario->id }}][pagar_locomocao]"
                   value="1"
                   x-model="pagarLocomocao"
                   class="rounded border-gray-300"
                   @disabled($periodoFechado)>
            Pagar locomoção neste dia
            ({{ $funcionario->labelLocomocao() }} · R$ {{ number_format($funcionario->locomocao_valor, 2, ',', '.') }})
        </label>
    @endif
</div>
