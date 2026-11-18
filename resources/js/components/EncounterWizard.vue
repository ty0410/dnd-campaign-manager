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
                <label for="wiz-description">Notas del encuentro</label>
                <textarea id="wiz-description" v-model="form.description" rows="6" maxlength="3000" placeholder="Terreno, motivación de los enemigos, tesoro..."></textarea>
            </div>

            <!-- Paso 2: monstruos con sus estadísticas -->
            <div v-else-if="step === 2" class="wizard-body wizard-roster">
                <div class="wizard-pick">
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
                </div>

                <div class="wizard-preview" aria-live="polite">
                    <p v-if="!previewIndex" class="empty-hint">Elige un monstruo para ver sus estadísticas, habilidades y acciones antes de añadirlo.</p>
                    <p v-else-if="loadingPreview" class="empty-hint">Cargando estadísticas...</p>
                    <template v-else-if="previewMonster">
                        <MonsterStatBlock :monster="previewMonster" />
                        <div class="wizard-add">
                            <label for="wiz-qty">Cantidad</label>
                            <input id="wiz-qty" v-model.number="quantity" type="number" min="1" max="20">
                            <button class="primary-button" type="button" @click="addMonster"><AppIcon name="plus" /> Añadir al encuentro</button>
                        </div>
                    </template>
                    <p v-if="previewError" class="error-message" role="alert">{{ previewError }}</p>
                </div>

                <aside class="wizard-list" aria-label="Monstruos del encuentro">
                    <h3>En el encuentro ({{ total }})</h3>
                    <ul v-if="rosterGroups.length">
                        <li v-for="group in rosterGroups" :key="group.key">
                            <span><strong>{{ group.name }}</strong><small>× {{ group.count }}</small></span>
                            <button type="button" class="icon-button danger" :aria-label="`Quitar ${group.name}`" @click="removeGroup(group)"><AppIcon name="x" /></button>
                        </li>
                    </ul>
                    <p v-else class="empty-hint">Aún no hay monstruos. Puedes añadirlos ahora o más tarde.</p>
                </aside>
            </div>

            <!-- Paso 3: resumen -->
            <div v-else class="wizard-body">
                <dl class="sheet-facts">
                    <div><dt>Nombre</dt><dd>{{ form.name }}</dd></div>
                    <div><dt>Monstruos</dt><dd>{{ total }}</dd></div>
                </dl>
                <p v-if="form.description" class="sheet-text">{{ form.description }}</p>
                <ul v-if="rosterGroups.length" class="admin-detail-list">
                    <li v-for="group in rosterGroups" :key="group.key">{{ group.name }}<small> × {{ group.count }}</small></li>
                </ul>
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

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';
import MonsterStatBlock from './MonsterStatBlock.vue';
import { fetchMonster, loadMonsterCatalog } from '../services/monsters';

const props = defineProps({
    campaignId: { type: [Number, String], required: true },
});
const emit = defineEmits(['close', 'saved']);

const titles = ['Datos', 'Monstruos', 'Resumen'];
const step = ref(1);
const form = ref({ name: '', description: '' });
const roster = ref([]);   // un registro por monstruo: { monster_index, name, hit_points }
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

const total = computed(() => roster.value.length);

const rosterGroups = computed(() => {
    const groups = new Map();
    for (const entry of roster.value) {
        if (!groups.has(entry.monster_index)) groups.set(entry.monster_index, { key: entry.monster_index, name: entry.base_name, count: 0 });
        groups.get(entry.monster_index).count += 1;
    }
    return [...groups.values()];
});

async function loadMonsters() {
    loadingList.value = true; listError.value = '';
    try { monsters.value = await loadMonsterCatalog(); }
    catch { listError.value = 'No se pudo cargar el catálogo de monstruos.'; }
    finally { loadingList.value = false; }
}

async function preview(monster) {
    previewIndex.value = monster.index; previewMonster.value = null; previewError.value = ''; loadingPreview.value = true; quantity.value = 1;
    try { previewMonster.value = await fetchMonster(monster.index); }
    catch { previewError.value = 'No se pudieron cargar las estadísticas de ese monstruo.'; }
    finally { loadingPreview.value = false; }
}

function addMonster() {
    const monster = previewMonster.value;
    const count = Math.min(20, Math.max(1, Number(quantity.value) || 1));
    const already = roster.value.filter((entry) => entry.monster_index === previewIndex.value).length;

    for (let n = 1; n <= count; n += 1) {
        const position = already + n;
        roster.value.push({
            monster_index: previewIndex.value,
            base_name: monster.name,
            // Numerados cuando hay más de uno del mismo tipo.
            name: already + count > 1 ? `${monster.name} ${position}` : monster.name,
            hit_points: monster.hit_points ?? null,
        });
    }
}

function removeGroup(group) {
    roster.value = roster.value.filter((entry) => entry.monster_index !== group.key);
}

async function create() {
    saving.value = true; error.value = '';
    let encounterId = null;
    try {
        const { data } = await api.post(`/campaigns/${props.campaignId}/encounters`, { ...form.value, type: 'combat', status: 'planned' });
        encounterId = data.encounter?.id ?? data.id;

        for (const entry of roster.value) {
            await api.post(`/encounters/${encounterId}/combatants`, {
                character_id: null, monster_index: entry.monster_index, name: entry.name, initiative: null,
                hit_points: entry.hit_points, max_hit_points: entry.hit_points, is_player: false, is_defeated: false,
            });
        }

        emit('saved');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo crear el encuentro.';
        if (encounterId) emit('saved');
    } finally { saving.value = false; }
}

function onKeydown(event) { if (event.key === 'Escape') emit('close'); }
onMounted(() => { window.addEventListener('keydown', onKeydown); loadMonsters(); });
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
