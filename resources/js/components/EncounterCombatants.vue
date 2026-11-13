<template>
    <div class="combat-manager">
        <ul v-if="sorted.length" class="combatant-list">
            <li v-for="combatant in sorted" :key="combatant.id" class="combatant" :class="{ 'is-defeated': combatant.is_defeated }">
                <div class="combatant-head">
                    <strong>{{ combatant.name }}</strong>
                    <span class="tag">{{ kindLabel(combatant) }}</span>
                    <button class="icon-button dice-button" type="button" :aria-label="`Tirar iniciativa de ${combatant.name}`" title="Tirar iniciativa (d20 + Destreza)" @click="rollFor(combatant)">d20</button>
                    <button class="icon-button danger" type="button" :aria-label="`Quitar a ${combatant.name} del encuentro`" title="Quitar del encuentro" :disabled="busyId === combatant.id" @click="remove(combatant)"><AppIcon name="x" /></button>
                </div>
                <div class="combatant-stats">
                    <label>Iniciativa
                        <input type="number" min="0" max="999" :value="combatant.initiative ?? ''" @change="update(combatant, { initiative: toNumber($event) })">
                    </label>
                    <label>PG
                        <span class="hp-pair">
                            <input type="number" min="0" :value="combatant.hit_points ?? ''" aria-label="Puntos de golpe actuales" @change="changeHp(combatant, $event)">
                            <span aria-hidden="true">/</span>
                            <input type="number" min="0" :value="combatant.max_hit_points ?? ''" aria-label="Puntos de golpe máximos" @change="update(combatant, { max_hit_points: toNumber($event) })">
                        </span>
                    </label>
                    <label class="check-row">
                        <input type="checkbox" :checked="combatant.is_defeated" @change="update(combatant, { is_defeated: $event.target.checked })"> Derrotado
                    </label>
                </div>
            </li>
        </ul>
        <p v-else class="sheet-empty">Aún no hay combatientes.</p>

        <p v-if="error" class="error-message" role="alert">{{ error }}</p>

        <form class="combatant-form" @submit.prevent="add">
            <h4>Añadir combatiente</h4>

            <div class="player-tabs" role="tablist" aria-label="Origen del combatiente">
                <button v-for="option in modes" :key="option.key" type="button" role="tab" class="chip-button" :class="{ 'is-active': mode === option.key }" :aria-selected="mode === option.key" @click="setMode(option.key)">{{ option.label }}</button>
            </div>

            <!-- Personaje de la campaña -->
            <template v-if="mode === 'character'">
                <select v-model="form.characterId" aria-label="Personaje" @change="pickCharacter">
                    <option value="" disabled>Elige un personaje de la campaña</option>
                    <option v-for="character in freeCharacters" :key="character.id" :value="character.id">{{ character.name }} (nivel {{ character.level }})</option>
                </select>
                <p v-if="!freeCharacters.length" class="sheet-empty">Todos los personajes de la campaña ya están en el encuentro.</p>
            </template>

            <!-- Monstruo del catálogo -->
            <template v-else-if="mode === 'monster'">
                <div class="search-field">
                    <AppIcon name="search" />
                    <input v-model.trim="monsterSearch" type="search" placeholder="Buscar monstruo (p. ej. goblin)" aria-label="Buscar monstruo">
                </div>
                <p v-if="monstersLoading" class="sheet-empty">Cargando catálogo de monstruos...</p>
                <p v-else-if="monstersError" class="error-message" role="alert">{{ monstersError }}</p>
                <ul v-else-if="monsterMatches.length && !form.monsterIndex" class="monster-matches">
                    <li v-for="monster in monsterMatches" :key="monster.index"><button type="button" class="available-character" @click="pickMonster(monster)">{{ monster.name }}<AppIcon name="plus" /></button></li>
                </ul>
                <p v-if="form.monsterIndex" class="viewing-label">Elegido: <strong>{{ form.name }}</strong> <button type="button" class="chip-button" @click="clearMonster">Cambiar</button></p>
            </template>

            <!-- Criatura personalizada -->
            <template v-else>
                <input v-model.trim="form.name" maxlength="255" placeholder="Nombre (p. ej. Bandido)" aria-label="Nombre">
                <label class="check-row"><input v-model="form.isPlayer" type="checkbox"> Es un jugador</label>
            </template>

            <div class="combatant-numbers">
                <label v-if="mode !== 'character'">Cantidad<input v-model.number="form.quantity" type="number" min="1" max="10"></label>
                <label>PG<input v-model.number="form.hp" type="number" min="0" placeholder="—"></label>
                <label>Iniciativa
                    <span class="hp-pair">
                        <input v-model.number="form.initiative" type="number" min="0" max="999" placeholder="—">
                        <button type="button" class="secondary-button" title="Tirar 1d20 con el modificador de Destreza (se muestra el resultado)" @click="rollInitiative">d20</button>
                    </span>
                </label>
            </div>

            <button class="primary-button" type="submit" :disabled="adding || !canAdd"><AppIcon name="plus" /> {{ adding ? 'Añadiendo...' : 'Añadir al encuentro' }}</button>
        </form>
    </div>
</template>

<script>
// Catálogo de monstruos compartido entre encuentros: se pide una sola vez.
let monsterCatalog = null;
</script>

<script setup>
import { computed, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';
import { rollD20 } from '../services/dice';

const props = defineProps({
    encounter: { type: Object, required: true },
    campaignCharacters: { type: Array, default: () => [] },
});
const emit = defineEmits(['changed']);

const modes = [
    { key: 'character', label: 'Personaje' },
    { key: 'monster', label: 'Monstruo' },
    { key: 'custom', label: 'Personalizado' },
];

const mode = ref('character');
const error = ref('');
const adding = ref(false);
const busyId = ref(null);

const blankForm = () => ({ characterId: '', monsterIndex: '', name: '', isPlayer: false, quantity: 1, hp: '', initiative: '', dex: 10 });
const form = ref(blankForm());

const monsters = ref([]);
const monsterSearch = ref('');
const monstersLoading = ref(false);
const monstersError = ref('');

// Orden de turno: mayor iniciativa primero, sin iniciativa al final.
const sorted = computed(() => [...(props.encounter.combatants || [])].sort((a, b) => {
    const left = a.initiative ?? -1;
    const right = b.initiative ?? -1;
    return right - left || a.id - b.id;
}));

const freeCharacters = computed(() => {
    const used = new Set((props.encounter.combatants || []).map((combatant) => combatant.character_id).filter(Boolean));
    return props.campaignCharacters.filter((character) => !used.has(character.id));
});

const monsterMatches = computed(() => {
    const term = monsterSearch.value.toLowerCase();
    if (term.length < 2) return [];
    return monsters.value.filter((monster) => monster.name.toLowerCase().includes(term)).slice(0, 8);
});

const canAdd = computed(() => {
    if (mode.value === 'character') return !!form.value.characterId;
    if (mode.value === 'monster') return !!form.value.monsterIndex;
    return !!form.value.name;
});

const kindLabel = (combatant) => (combatant.is_player ? 'Jugador' : combatant.monster_index ? 'Monstruo' : 'Criatura');
const toNumber = (event) => (event.target.value === '' ? null : Number(event.target.value));
const modifier = (score) => Math.floor((Number(score || 10) - 10) / 2);

function setMode(next) {
    mode.value = next; error.value = ''; form.value = blankForm(); monsterSearch.value = '';
    if (next === 'monster') loadMonsters();
}

async function loadMonsters() {
    if (monsterCatalog) { monsters.value = monsterCatalog; return; }
    monstersLoading.value = true; monstersError.value = '';
    try {
        const { data } = await api.get('/dnd/monsters');
        monsterCatalog = data.results || [];
        monsters.value = monsterCatalog;
    } catch {
        monstersError.value = 'No se pudo cargar el catálogo de monstruos. Puedes usar «Personalizado».';
    } finally { monstersLoading.value = false; }
}

function pickCharacter() {
    const character = props.campaignCharacters.find((entry) => entry.id === form.value.characterId);
    if (!character) return;
    form.value.name = character.name;
    form.value.hp = character.current_hit_points ?? character.maximum_hit_points ?? '';
    form.value.dex = Number(character.dexterity || 10) + Number(character.dexterity_bonus || 0);
}

async function pickMonster(monster) {
    error.value = '';
    try {
        const { data } = await api.get(`/dnd/monsters/${monster.index}`);
        form.value.monsterIndex = monster.index;
        form.value.name = data.name || monster.name;
        form.value.hp = data.hit_points ?? '';
        form.value.dex = data.dexterity ?? 10;
    } catch {
        error.value = 'No se pudo cargar ese monstruo.';
    }
}

function clearMonster() { form.value.monsterIndex = ''; form.value.name = ''; form.value.hp = ''; monsterSearch.value = ''; }

function rollInitiative() {
    rollD20({
        label: `Iniciativa — ${form.value.name || 'combatiente'}`,
        modifier: modifier(form.value.dex),
        onResult: (result) => { form.value.initiative = Math.max(0, result.total); },
    });
}

// Iniciativa de un combatiente ya añadido: d20 + modificador de Destreza.
async function rollFor(combatant) {
    let dexterity = 10;

    if (combatant.character_id) {
        const character = props.campaignCharacters.find((entry) => entry.id === combatant.character_id);
        if (character) dexterity = Number(character.dexterity || 10) + Number(character.dexterity_bonus || 0);
    } else if (combatant.monster_index) {
        try {
            const { data } = await api.get(`/dnd/monsters/${combatant.monster_index}`);
            dexterity = data.dexterity ?? 10;
        } catch { /* sin modificador si no se puede consultar */ }
    }

    rollD20({
        label: `Iniciativa — ${combatant.name}`,
        modifier: modifier(dexterity),
        onResult: (result) => update(combatant, { initiative: Math.max(0, result.total) }),
    });
}

const optionalNumber = (value) => (value === '' || value === null || Number.isNaN(value) ? null : value);

async function add() {
    adding.value = true; error.value = '';
    const quantity = mode.value === 'character' ? 1 : Math.min(10, Math.max(1, Number(form.value.quantity) || 1));
    const hp = optionalNumber(form.value.hp);

    try {
        for (let n = 1; n <= quantity; n += 1) {
            await api.post(`/encounters/${props.encounter.id}/combatants`, {
                character_id: mode.value === 'character' ? form.value.characterId : null,
                monster_index: mode.value === 'monster' ? form.value.monsterIndex : null,
                name: quantity > 1 ? `${form.value.name} ${n}` : form.value.name,
                initiative: optionalNumber(form.value.initiative),
                hit_points: hp,
                max_hit_points: hp,
                is_player: mode.value === 'character' ? true : form.value.isPlayer,
                is_defeated: false,
            });
        }
        form.value = blankForm(); monsterSearch.value = '';
        emit('changed');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo añadir el combatiente.';
        // Si ya se crearon algunos, se refleja igualmente.
        emit('changed');
    } finally { adding.value = false; }
}

async function update(combatant, changes) {
    error.value = '';
    try {
        await api.put(`/combatants/${combatant.id}`, {
            character_id: combatant.character_id,
            monster_index: combatant.monster_index,
            name: combatant.name,
            initiative: combatant.initiative,
            hit_points: combatant.hit_points,
            max_hit_points: combatant.max_hit_points,
            is_player: !!combatant.is_player,
            is_defeated: !!combatant.is_defeated,
            ...changes,
        });
        emit('changed');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo guardar el cambio.';
        emit('changed');
    }
}

// Al llegar a 0 PG el combatiente se marca como derrotado (se puede desmarcar).
function changeHp(combatant, event) {
    const hitPoints = toNumber(event);
    update(combatant, { hit_points: hitPoints, ...(hitPoints === 0 ? { is_defeated: true } : {}) });
}

async function remove(combatant) {
    if (!window.confirm(`¿Quitar a «${combatant.name}» del encuentro?`)) return;
    busyId.value = combatant.id; error.value = '';
    try {
        await api.delete(`/combatants/${combatant.id}`);
        emit('changed');
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo quitar el combatiente.';
    } finally { busyId.value = null; }
}
</script>
