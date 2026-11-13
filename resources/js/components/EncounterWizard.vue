<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <section class="modal wizard-modal" role="dialog" aria-modal="true" aria-labelledby="wizard-title">
            <button class="modal-close" type="button" aria-label="Cerrar" @click="$emit('close')"><AppIcon name="x" /></button>
            <h2 id="wizard-title">{{ titles[step - 1] }}</h2>

            <ol class="wizard-steps" aria-hidden="true">
                <li v-for="(title, index) in titles" :key="title" :class="{ 'is-active': step === index + 1, 'is-done': step > index + 1 }"><span>{{ index + 1 }}</span>{{ title }}</li>
            </ol>

            <!-- Paso 1: datos -->
            <div v-if="step === 1" class="wizard-body wizard-body--form">
                <label for="wiz-name">Nombre del encuentro</label>
                <input id="wiz-name" v-model.trim="form.name" maxlength="255" required placeholder="Emboscada en el camino real">
                <label for="wiz-description">Descripción</label>
                <textarea id="wiz-description" v-model="form.description" rows="5" maxlength="3000" placeholder="Terreno, motivación de los enemigos, tesoro..."></textarea>
                <label for="wiz-status">Estado</label>
                <select id="wiz-status" v-model="form.status">
                    <option v-for="(label, value) in ENCOUNTER_STATUSES" :key="value" :value="value">{{ label }}</option>
                </select>
                <p class="modal-lead">Por ahora los encuentros son siempre de combate.</p>
            </div>

            <!-- Paso 2: combatientes -->
            <div v-else-if="step === 2" class="wizard-body wizard-roster">
                <div class="wizard-pick">
                    <div class="player-tabs" role="tablist" aria-label="Origen">
                        <button type="button" role="tab" class="chip-button" :class="{ 'is-active': source === 'monster' }" :aria-selected="source === 'monster'" @click="setSource('monster')">Monstruos</button>
                        <button type="button" role="tab" class="chip-button" :class="{ 'is-active': source === 'character' }" :aria-selected="source === 'character'" @click="source = 'character'">Personajes</button>
                    </div>

                    <template v-if="source === 'monster'">
                        <div class="search-field">
                            <AppIcon name="search" />
                            <input v-model.trim="search" type="search" placeholder="Buscar monstruo (p. ej. goblin, dragon)..." aria-label="Buscar monstruo">
                        </div>
                        <p v-if="loadingList" class="empty-hint">Cargando catálogo...</p>
                        <p v-else-if="listError" class="error-message" role="alert">{{ listError }}</p>
                        <ul class="srd-items wizard-matches">
                            <li v-for="monster in matches" :key="monster.index"><button type="button" class="srd-item" :class="{ 'is-selected': previewIndex === monster.index }" @click="preview(monster)">{{ monster.name }}</button></li>
                        </ul>
                        <p v-if="search.length < 2 && !loadingList" class="empty-hint">Escribe al menos dos letras para buscar.</p>
                        <p v-else-if="!matches.length && !loadingList" class="empty-hint">Sin resultados.</p>
                    </template>

                    <template v-else>
                        <p v-if="!campaignCharacters.length" class="empty-hint">La campaña todavía no tiene personajes.</p>
                        <ul v-else class="srd-items wizard-matches">
                            <li v-for="character in campaignCharacters" :key="character.id">
                                <button type="button" class="srd-item" :disabled="roster.some((entry) => entry.character_id === character.id)" @click="addCharacter(character)">
                                    {{ character.name }} <small>nivel {{ character.level }}</small>
                                </button>
                            </li>
                        </ul>
                    </template>
                </div>

                <!-- Estadísticas del monstruo elegido -->
                <div class="wizard-preview" aria-live="polite">
                    <p v-if="source !== 'monster'" class="empty-hint">Añade personajes de la campaña a la lista del encuentro.</p>
                    <p v-else-if="!previewIndex" class="empty-hint">Elige un monstruo para ver sus estadísticas, habilidades y acciones antes de añadirlo.</p>
                    <p v-else-if="loadingPreview" class="empty-hint">Cargando estadísticas...</p>
                    <template v-else-if="previewMonster">
                        <MonsterStatBlock :monster="previewMonster" />
                        <div class="wizard-add">
                            <label for="wiz-qty">Cantidad</label>
                            <input id="wiz-qty" v-model.number="quantity" type="number" min="1" max="10">
                            <button class="primary-button" type="button" @click="addMonster"><AppIcon name="plus" /> Añadir al encuentro</button>
                        </div>
                    </template>
                    <p v-if="previewError" class="error-message" role="alert">{{ previewError }}</p>
                </div>

                <aside class="wizard-list" aria-label="Combatientes del encuentro">
                    <h3>En el encuentro ({{ roster.length }})</h3>
                    <ul v-if="roster.length">
                        <li v-for="(entry, index) in roster" :key="index">
                            <span><strong>{{ entry.name }}</strong><small>{{ entry.is_player ? 'Jugador' : 'Monstruo' }} · PG {{ entry.hit_points ?? '—' }}</small></span>
                            <button type="button" class="icon-button danger" :aria-label="`Quitar ${entry.name}`" @click="roster.splice(index, 1)"><AppIcon name="x" /></button>
                        </li>
                    </ul>
                    <p v-else class="empty-hint">Todavía no hay combatientes. Puedes añadirlos ahora o más tarde.</p>
                </aside>
            </div>

            <!-- Paso 3: resumen -->
            <div v-else class="wizard-body">
                <dl class="sheet-facts">
                    <div><dt>Nombre</dt><dd>{{ form.name }}</dd></div>
                    <div><dt>Estado</dt><dd>{{ ENCOUNTER_STATUSES[form.status] }}</dd></div>
                    <div><dt>Combatientes</dt><dd>{{ roster.length }}</dd></div>
                </dl>
                <p v-if="form.description" class="sheet-text">{{ form.description }}</p>
                <ul v-if="roster.length" class="admin-detail-list">
                    <li v-for="(entry, index) in roster" :key="index">{{ entry.name }}<small> · {{ entry.is_player ? 'jugador' : 'monstruo' }}, PG {{ entry.hit_points ?? '—' }}</small></li>
                </ul>
                <p class="modal-lead">La iniciativa se tira después, desde la hoja del encuentro.</p>
            </div>

            <p v-if="error" class="error-message" role="alert">{{ error }}</p>

            <div class="modal-actions">
                <button v-if="step > 1" class="secondary-button" type="button" @click="step -= 1">Atrás</button>
                <button class="secondary-button" type="button" @click="$emit('close')">Cancelar</button>
                <button v-if="step < 3" class="primary-button" type="button" :disabled="step === 1 && !form.name" @click="step += 1">Siguiente</button>
                <button v-else class="primary-button" type="button" :disabled="saving" @click="create">{{ saving ? 'Creando...' : 'Crear encuentro' }}</button>
            </div>
        </section>
    </div>
</template>

<script>
// El catálogo de monstruos se pide una sola vez por sesión.
let monsterCatalog = null;
</script>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';
import MonsterStatBlock from './MonsterStatBlock.vue';
import { ENCOUNTER_STATUSES } from '../services/campaignItems';

const props = defineProps({
    campaignId: { type: [Number, String], required: true },
    campaignCharacters: { type: Array, default: () => [] },
});
const emit = defineEmits(['close', 'saved']);

const titles = ['Datos del encuentro', 'Combatientes', 'Resumen'];
const step = ref(1);
const form = ref({ name: '', description: '', status: 'planned' });
const roster = ref([]);
const source = ref('monster');
const error = ref('');
const saving = ref(false);

const monsters = ref([]);
const search = ref('');
const loadingList = ref(false);
const listError = ref('');

const previewIndex = ref('');
const previewMonster = ref(null);
const loadingPreview = ref(false);
const previewError = ref('');
const quantity = ref(1);

const normalize = (text) => String(text ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
const matches = computed(() => {
    const term = normalize(search.value);
    if (term.length < 2) return [];
    return monsters.value.filter((monster) => normalize(monster.name).includes(term)).slice(0, 40);
});

async function setSource(next) {
    source.value = next;
    if (next === 'monster') await loadMonsters();
}

async function loadMonsters() {
    if (monsterCatalog) { monsters.value = monsterCatalog; return; }
    loadingList.value = true; listError.value = '';
    try {
        const { data } = await api.get('/dnd/monsters');
        monsterCatalog = data.results || [];
        monsters.value = monsterCatalog;
    } catch {
        listError.value = 'No se pudo cargar el catálogo de monstruos.';
    } finally { loadingList.value = false; }
}

async function preview(monster) {
    previewIndex.value = monster.index; previewMonster.value = null; previewError.value = ''; loadingPreview.value = true; quantity.value = 1;
    try {
        const { data } = await api.get(`/dnd/monsters/${monster.index}`);
        previewMonster.value = data;
    } catch {
        previewError.value = 'No se pudieron cargar las estadísticas de ese monstruo.';
    } finally { loadingPreview.value = false; }
}

function addMonster() {
    const monster = previewMonster.value;
    const total = Math.min(10, Math.max(1, Number(quantity.value) || 1));
    for (let n = 1; n <= total; n += 1) {
        roster.value.push({
            name: total > 1 ? `${monster.name} ${n}` : monster.name,
            monster_index: previewIndex.value,
            character_id: null,
            hit_points: monster.hit_points ?? null,
            is_player: false,
        });
    }
}

function addCharacter(character) {
    roster.value.push({
        name: character.name,
        monster_index: null,
        character_id: character.id,
        hit_points: character.current_hit_points ?? character.maximum_hit_points ?? null,
        max_hit_points: character.maximum_hit_points ?? null,
        is_player: true,
    });
}

async function create() {
    saving.value = true; error.value = '';
    let encounterId = null;
    try {
        const { data } = await api.post(`/campaigns/${props.campaignId}/encounters`, { ...form.value, type: 'combat' });
        encounterId = data.encounter?.id ?? data.id;

        for (const entry of roster.value) {
            await api.post(`/encounters/${encounterId}/combatants`, {
                character_id: entry.character_id,
                monster_index: entry.monster_index,
                name: entry.name,
                initiative: null,
                hit_points: entry.hit_points,
                max_hit_points: entry.max_hit_points ?? entry.hit_points,
                is_player: entry.is_player,
                is_defeated: false,
            });
        }

        emit('saved');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo crear el encuentro.';
        // Si el encuentro se llegó a crear, se recarga para que aparezca.
        if (encounterId) emit('saved');
    } finally { saving.value = false; }
}

function onKeydown(event) { if (event.key === 'Escape') emit('close'); }
onMounted(() => { window.addEventListener('keydown', onKeydown); loadMonsters(); });
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
