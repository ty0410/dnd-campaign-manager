import { reactive } from 'vue';

// Estado compartido del diálogo de tiradas (lo muestra DiceRoller.vue).
export const diceState = reactive({ open: false, result: null, request: null });

const roll = (sides) => Math.floor(Math.random() * sides) + 1;

export const signed = (value) => (Number(value) >= 0 ? `+${Number(value)}` : `${Number(value)}`);

/**
 * Tirada de d20 con modificador. mode: 'normal' | 'advantage' | 'disadvantage'.
 * Abre el diálogo y devuelve el resultado.
 */
export function rollD20({ label = 'Tirada', modifier = 0, mode = 'normal', onResult = null } = {}) {
    const modifierValue = Number(modifier) || 0;
    const dice = mode === 'normal' ? [roll(20)] : [roll(20), roll(20)];
    const natural = mode === 'advantage' ? Math.max(...dice) : mode === 'disadvantage' ? Math.min(...dice) : dice[0];

    const result = {
        kind: 'd20',
        label,
        mode,
        dice,
        natural,
        modifier: modifierValue,
        total: natural + modifierValue,
        critical: natural === 20 ? 'success' : natural === 1 ? 'failure' : null,
    };

    present(result, { kind: 'd20', label, modifier: modifierValue, onResult }, onResult);
    return result;
}

/** Tirada de varios dados iguales (por ejemplo 2d6+3). */
export function rollDice({ label = 'Tirada', count = 1, sides = 6, modifier = 0, onResult = null } = {}) {
    const modifierValue = Number(modifier) || 0;
    const dice = Array.from({ length: count }, () => roll(sides));
    const sum = dice.reduce((acc, value) => acc + value, 0);

    const result = { kind: 'dice', label, count, sides, dice, sum, modifier: modifierValue, total: sum + modifierValue };

    present(result, { kind: 'dice', label, count, sides, modifier: modifierValue, onResult }, onResult);
    return result;
}

function present(result, request, onResult) {
    diceState.result = result;
    diceState.request = request;
    diceState.open = true;
    if (onResult) onResult(result);
}

/** Repite la última tirada (con o sin ventaja). */
export function reroll(mode) {
    const request = diceState.request;
    if (!request) return;

    if (request.kind === 'd20') rollD20({ ...request, mode: mode ?? 'normal' });
    else rollDice(request);
}

export function closeDice() {
    diceState.open = false;
}
