<template>
    <div class="encounter-monsters">
        <p v-if="error" class="error-message" role="alert">{{ error }}</p>

        <ul v-if="groups.length" class="monster-groups">
            <li v-for="group in groups" :key="group.key" class="monster-group">
                <div class="monster-group-head">
                    <button type="button" class="monster-toggle" :aria-expanded="open.has(group.key)" @click="toggle(group)">
                        <AppIcon name="chevron" class="monster-chevron" :class="{ 'is-open': open.has(group.key) }" />
                        <strong>{{ group.name }}</strong>
                        <small v-if="stats[group.key]">CA {{ armor(stats[group.key]) }} · PG {{ stats[group.key].hit_points }} · CR {{ stats[group.key].challenge_rating }}</small>
                    </button>

                    <div class="monster-count" role="group" :aria-label="`Cantidad de ${group.name}`">
                        <button type="button" class="icon-button" :disabled="busy" :aria-label="`Quitar un ${group.name}`" @click="changeCount(group, -1)">−</button>
                        <output>{{ group.count }}</output>
                        <button type="button" class="icon-button" :disabled="busy" :aria-label="`Añadir un ${group.name}`" @click="changeCount(group, 1)">+</button>
                        <button type="button" class="icon-button danger" :disabled="busy" :aria-label="`Quitar ${group.name} del encuentro`" @click="removeGroup(group)"><AppIcon name="trash" /></button>
                    </div>
                </div>

                <div v-if="open.has(group.key)" class="monster-group-body">
                    <p v-if="loading.has(group.key)" class="empty-hint">Cargando ficha...</p>
                    <p v-else-if="!group.monster_index" class="sheet-empty">Criatura sin ficha del catálogo.</p>
                    <MonsterStatBlock v-else-if="stats[group.key]" :monster="stats[group.key]" />
                </div>
            </li>
        </ul>
        <p v-else class="sheet-empty">Sin monstruos todavía.</p>

        <div class="monster-add">
            <div class="search-field">
                <AppIcon name="search" />
                <input v-model.trim="search" type="search" placeholder="Añadir monstruo (p. ej. goblin)..." aria-label="Buscar monstruo para añadir" @focus="ensureCatalog">
            </div>
            <p v-if="catalogError" class="error-message" role="alert">{{ catalogError }}</p>
            <ul v-if="matches.length" class="srd-items monster-add-results">
                <li v-for="monster in matches" :key="monster.index"><button type="button" class="srd-item" :disabled="busy" @click="addMonster(monster)">{{ monster.name }}</button></li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';
import MonsterStatBlock from './MonsterStatBlock.vue';
import { fetchMonster, groupMonsters, loadMonsterCatalog } from '../services/monsters';

const props = defineProps({ encounter: { type: Object, required: true } });
const emit = defineEmits(['changed']);

const open = ref(new Set());
const loading = ref(new Set());
const stats = ref({});
const error = ref('');
const busy = ref(false);

const catalog = ref([]);
const catalogError = ref('');
const search = ref('');

const groups = computed(() => groupMonsters(props.encounter.combatants));

const normalize = (text) => String(text ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
const matches = computed(() => {
    const term = normalize(search.value);
    if (term.length < 2) return [];
    return catalog.value.filter((monster) => normalize(monster.name).includes(term)).slice(0, 8);
});

const armor = (monster) => (Array.isArray(monster.armor_class) ? monster.armor_class.map((entry) => entry.value).join('/') : monster.armor_class);

async function ensureCatalog() {
    if (catalog.value.length) return;
    try { catalog.value = await loadMonsterCatalog(); }
    catch { catalogError.value = 'No se pudo cargar el catálogo de monstruos.'; }
}

async function load(group) {
    if (!group.monster_index || stats.value[group.key]) return;
    loading.value = new Set(loading.value).add(group.key);
    try {
        stats.value = { ...stats.value, [group.key]: await fetchMonster(group.monster_index) };
    } catch {
        error.value = `No se pudo cargar la ficha de ${group.name}.`;
    } finally {
        const next = new Set(loading.value);
        next.delete(group.key);
        loading.value = next;
    }
}

async function toggle(group) {
    const next = new Set(open.value);
    if (next.has(group.key)) next.delete(group.key); else next.add(group.key);
    open.value = next;
    if (next.has(group.key)) await load(group);
}

async function run(task, fallback) {
    busy.value = true; error.value = '';
    try { await task(); emit('changed'); }
    catch (err) { error.value = err.response?.data?.message || fallback; }
    finally { busy.value = false; }
}

async function createRow(monsterIndex, name) {
    let hitPoints = null;
    try { hitPoints = (await fetchMonster(monsterIndex)).hit_points ?? null; } catch { /* sin PG */ }

    await api.post(`/encounters/${props.encounter.id}/combatants`, {
        monster_index: monsterIndex, character_id: null, name, initiative: null,
        hit_points: hitPoints, max_hit_points: hitPoints, is_player: false, is_defeated: false,
    });
}

const addMonster = (monster) => run(async () => { await createRow(monster.index, monster.name); search.value = ''; }, 'No se pudo añadir el monstruo.');

function changeCount(group, delta) {
    if (delta > 0) return run(() => createRow(group.monster_index, group.name), 'No se pudo añadir.');
    if (group.count <= 1) return removeGroup(group);
    return run(() => api.delete(`/combatants/${group.ids[group.ids.length - 1]}`), 'No se pudo quitar.');
}

function removeGroup(group) {
    if (!window.confirm(`¿Quitar ${group.name} del encuentro?`)) return Promise.resolve();
    return run(async () => { for (const id of group.ids) await api.delete(`/combatants/${id}`); }, 'No se pudo quitar el monstruo.');
}
</script>
