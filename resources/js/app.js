import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/**
 * Ao focar campos numéricos, seleciona o valor inteiro
 * para digitar por cima sem precisar apagar.
 */
document.addEventListener('focusin', (event) => {
    const el = event.target;

    if (!(el instanceof HTMLInputElement) || el.disabled || el.readOnly) {
        return;
    }

    const deveSelecionar =
        el.type === 'number' ||
        el.inputMode === 'decimal' ||
        el.inputMode === 'numeric' ||
        el.dataset.selectOnFocus !== undefined;

    if (! deveSelecionar) {
        return;
    }

    requestAnimationFrame(() => {
        el.select();
    });
});
