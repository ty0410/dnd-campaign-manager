// Requisitos de característica para ser multiclase (manual del jugador).
// «all»: se exigen todas; «any»: basta con una (el guerrero).
const ABILITY_LABELS = {
    strength: 'Fuerza', dexterity: 'Destreza', constitution: 'Constitución',
    intelligence: 'Inteligencia', wisdom: 'Sabiduría', charisma: 'Carisma',
};

export const CLASS_LABELS = {
    barbarian: 'Bárbaro', bard: 'Bardo', cleric: 'Clérigo', druid: 'Druida', fighter: 'Guerrero',
    monk: 'Monje', paladin: 'Paladín', ranger: 'Explorador', rogue: 'Pícaro',
    sorcerer: 'Hechicero', warlock: 'Brujo', wizard: 'Mago',
};

const REQUIREMENTS = {
    barbarian: { all: [['strength', 13]] },
    bard: { all: [['charisma', 13]] },
    warlock: { all: [['charisma', 13]] },
    cleric: { all: [['wisdom', 13]] },
    druid: { all: [['wisdom', 13]] },
    ranger: { all: [['dexterity', 13], ['wisdom', 13]] },
    fighter: { any: [['strength', 13], ['dexterity', 13]] },
    sorcerer: { all: [['charisma', 13]] },
    wizard: { all: [['intelligence', 13]] },
    monk: { all: [['dexterity', 13], ['wisdom', 13]] },
    paladin: { all: [['strength', 13], ['charisma', 13]] },
    rogue: { all: [['dexterity', 13]] },
};

/**
 * Avisos de multiclase: con más de una clase hay que cumplir los requisitos de
 * todas. Solo informa; nunca impide crear ni subir de nivel.
 * scoreOf(clave) devuelve la puntuación efectiva de una característica.
 */
export function multiclassWarnings(classIndexes, scoreOf) {
    const unique = [...new Set(classIndexes.filter(Boolean))];
    if (unique.length < 2) return [];

    const warnings = [];

    for (const index of unique) {
        const rule = REQUIREMENTS[index];
        if (!rule) continue;

        const className = CLASS_LABELS[index] || index;

        if (rule.any) {
            if (!rule.any.some(([ability, minimum]) => Number(scoreOf(ability)) >= minimum)) {
                warnings.push(`${className}: ${rule.any.map(([ability, minimum]) => `${ABILITY_LABELS[ability]} ${minimum}`).join(' o ')}`);
            }
            continue;
        }

        const missing = rule.all.filter(([ability, minimum]) => !(Number(scoreOf(ability)) >= minimum));
        if (missing.length) {
            warnings.push(`${className}: ${missing.map(([ability, minimum]) => `${ABILITY_LABELS[ability]} ${minimum}`).join(' y ')}`);
        }
    }

    return warnings;
}
