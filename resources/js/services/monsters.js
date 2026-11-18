import api from './api';

// Catálogo de monstruos compartido: el listado y cada ficha se piden una sola vez por sesión.
let catalog = null;
const details = new Map();

export async function loadMonsterCatalog() {
    if (!catalog) {
        const { data } = await api.get('/dnd/monsters');
        catalog = data.results || [];
    }

    return catalog;
}

export async function fetchMonster(index) {
    if (!details.has(index)) {
        const { data } = await api.get(`/dnd/monsters/${index}`);
        details.set(index, data);
    }

    return details.get(index);
}

// Agrupa las filas de un encuentro por monstruo: «3 × Goblin».
export function groupMonsters(combatants = []) {
    const groups = new Map();

    for (const combatant of combatants) {
        const key = combatant.monster_index || `name:${combatant.name}`;
        const base = String(combatant.name || '').replace(/\s+\d+$/, '');

        if (!groups.has(key)) {
            groups.set(key, { key, monster_index: combatant.monster_index || null, name: base || combatant.name, ids: [] });
        }

        groups.get(key).ids.push(combatant.id);
    }

    return [...groups.values()]
        .map((group) => ({ ...group, count: group.ids.length }))
        .sort((a, b) => a.name.localeCompare(b.name, 'es'));
}
