@props([
    'lider',
    'membrosCount' => 0,
    'total' => null,
])

<div {{ $attributes->merge(['class' => 'px-4 py-3 bg-stone-800 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2']) }}>
    <div>
        <div class="text-[11px] uppercase tracking-wider text-stone-300 font-medium">Equipe de</div>
        <div class="font-semibold text-lg leading-tight">{{ $lider->nome }}</div>
        @if ($membrosCount > 0)
            <div class="text-xs text-stone-300 mt-0.5">{{ $membrosCount }} ajudante{{ $membrosCount > 1 ? 's' : '' }}</div>
        @endif
    </div>
    @if ($total !== null)
        <div class="text-left sm:text-right">
            <div class="text-[11px] uppercase tracking-wider text-stone-300">Total da equipe</div>
            <div class="font-semibold text-lg">R$ {{ number_format($total, 2, ',', '.') }}</div>
        </div>
    @endif
</div>
