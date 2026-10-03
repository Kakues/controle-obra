@props(['papel' => null])

@if ($papel === 'lider')
    <span class="inline-flex items-center text-[10px] uppercase tracking-wide font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-200">
        Líder
    </span>
@elseif ($papel === 'ajudante')
    <span class="inline-flex items-center text-[10px] uppercase tracking-wide font-semibold px-2 py-0.5 rounded-full bg-sky-100 text-sky-900 border border-sky-200">
        Ajudante
    </span>
@endif
