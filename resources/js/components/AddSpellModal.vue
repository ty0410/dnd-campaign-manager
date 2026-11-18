<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <section class="modal modal-wide add-spell-modal" role="dialog" aria-modal="true" aria-labelledby="add-spell-title">
            <button class="modal-close" type="button" aria-label="Cerrar" @click="$emit('close')"><AppIcon name="x" /></button>
            <h2 id="add-spell-title">Añadir conjuro</h2>

            <div class="spell-sources">
                <div v-if="mine.length" class="spell-source-group">
                    <span class="spell-source-label">Tus clases</span>
                    <div class="player-tabs" role="group" aria-label="Tus clases lanzadoras">
                        <button v-for="entry in mine" :key="entry.key" type="button" class="chip-button" :class="{ 'is-active': source === entry.key }" :aria-pressed="source === entry.key" @click="setSource(entry.key)">{{ entry.label }}</button>
                    </div>
                </div>
                <div class="spell-source-group">
                    <span class="spell-source-label">{{ mine.length ? 'Otras listas' : 'Listas' }}</span>
                    <div class="player-tabs" role="group" aria-label="Otras listas de conjuros">
                        <button v-for="entry in LISTS" :key="entry.key" type="button" class="chip-button" :class="{ 'is-active': source === entry.key }" :aria-pressed="source === entry.key" @click="setSource(entry.key)">{{ entry.label }}</button>
                    </div>
                </div>
            </div>

            <p v-if="info" class="spell-info">{{ info }}</p>

            <div class="catalog-picker spell-picker">
                <div class="catalog-list">
                    <div class="search-field">
                        <AppIcon name="search" />
                        <input v-model.trim="search" type="search" :placeholder="source === 'all' ? 'Buscar conjuro (mínimo dos letras)...' : 'Filtrar por nombre...'" aria-label="Buscar conjuro">
                    </div>
                    <p v-if="loadingList" class="empty-hint">Cargando conjuros...</p>
                    <p v-else-if="listError" class="error-message" role="alert">{{ listError }}</p>

                    <div v-else class="spell-browser">
                        <section v-for="group in groups" :key="group.level" class="spell-browser-group">
                            <h3>{{ group.label }}</h3>
                            <ul class="srd-items">
                                <li v-for="entry in group.spells" :key="entry.index">
                                    <label class="spell-option" :class="{ 'is-selected': selected.has(entry.index), 'is-owned': isOwned(entry) }">
                                        <input type="checkbox" :checked="selected.has(entry.index) || isOwned(entry)" :disabled="isOwned(entry)" @change="toggle(entry, $event.target.checked)">
                                        <span class="spell-option-name" @click.prevent="preview(entry)">{{ entry.name }}</span>
                                        <small v-if="isOwned(entry)">Ya lo tienes</small>
                                        <small v-else-if="entry.school">{{ entry.school }}</small>
                                    </label>
                                </li>
                            </ul>
                        </section>
                        <p v-if="!groups.length" class="empty-hint">{{ source === 'all' && search.length < 2 ? 'Escribe al menos dos letras.' : 'Sin resultados.' }}</p>
                    </div>
                </div>

                <div class="catalog-detail" aria-live="polite">
                    <p v-if="!picked" class="empty-hint">Marca los conjuros que quieras y pulsa Añadir. Pulsa un nombre para ver sus detalles.</p>
                    <p v-else-if="loadingDetail" class="empty-hint">Cargando...</p>
                    <template v-else-if="detail">
                        <h3>{{ detail.name }}</h3>
                        <dl class="sheet-facts">
                            <div><dt>Nivel</dt><dd>{{ detail.level === 0 ? 'Truco' : detail.level }}</dd></div>
                            <div v-if="detail.school?.name"><dt>Escuela</dt><dd>{{ detail.school.name }}</dd></div>
                            <div v-if="detail.casting_time"><dt>Lanzamiento</dt><dd>{{ detail.casting_time }}</dd></div>
                            <div v-if="detail.range"><dt>Alcance</dt><dd>{{ detail.range }}</dd></div>
                            <div v-if="detail.duration"><dt>Duración</dt><dd>{{ detail.duration }}</dd></div>
                        </dl>
                        <p v-for="(line, index) in description" :key="index" class="sheet-text">{{ line }}</p>
                    </template>
                </div>
            </div>

            <p v-if="error" class="error-message" role="alert">{{ error }}</p>
            <div class="modal-actions">
                <button class="secondary-button" type="button" @click="$emit('close')">Cancelar</button>
                <button class="primary-button" type="button" :disabled="saving || !selected.size" @click="save">{{ saving ? 'Añadiendo...' : (selected.size ? `Añadir ${selected.size} ${selected.size === 1 ? 'conjuro' : 'conjuros'}` : 'Añadir conjuros') }}</button>
            </div>
        </section>
    </div>
</template>

<script>
// Las listas completas por clase se piden una vez por sesión.
const classLists = {};
let allSpells = null;
</script>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    characterId: { type: [Number, String], required: true },
    classes: { type: Array, default: () => [] },
    owned: { type: Array, default: () => [] },
});
const emit = defineEmits(['close', 'saved']);

// Cualquier lista de lanzador, aunque el personaje no tenga esa clase.
const LISTS = [
    { key: 'all', label: 'Todas' },
    { key: 'bard', label: 'Bardo' },
    { key: 'cleric', label: 'Clérigo' },
    { key: 'druid', label: 'Druida' },
    { key: 'paladin', label: 'Paladín' },
    { key: 'ranger', label: 'Explorador' },
    { key: 'sorcerer', label: 'Hechicero' },
    { key: 'warlock', label: 'Brujo' },
    { key: 'wizard', label: 'Mago' },
];

const mine = ref([]);          // clases del personaje que lanzan conjuros, con sus conjuros accesibles
const source = ref('all');
const items = ref([]);
const search = ref('');
const loadingList = ref(true);
const listError = ref('');
const picked = ref(null);
const selected = ref(new Set());
const detail = ref(null);
const loadingDetail = ref(false);
const saving = ref(false);
const error = ref('');

const normalize = (text) => String(text ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
const ownedSet = computed(() => new Set(props.owned));
const isOwned = (entry) => ownedSet.value.has(entry.index);

const currentMine = computed(() => mine.value.find((entry) => entry.key === source.value) || null);

const info = computed(() => {
    const data = currentMine.value?.options;
    if (!data) return '';
    const parts = [data.max_spell_level ? `Hasta nivel ${data.max_spell_level}` : 'Solo trucos'];
    if (data.cantrips_known != null) parts.push(`Trucos conocidos: ${data.cantrips_known}`);
    if (data.spells_known != null) parts.push(`Conocidos: ${data.spells_known}`);
    return parts.join(' · ');
});

// Listado agrupado por nivel de conjuro (los de «Todas» solo se muestran al buscar).
const groups = computed(() => {
    const term = normalize(search.value);
    if (source.value === 'all' && term.length < 2) return [];

    const filtered = items.value.filter((entry) => !term || normalize(entry.name).includes(term));
    const byLevel = new Map();

    for (const entry of filtered) {
        const level = entry.level ?? null;
        if (!byLevel.has(level)) byLevel.set(level, []);
        byLevel.get(level).push(entry);
    }

    return [...byLevel.entries()]
        .sort((a, b) => (a[0] ?? 99) - (b[0] ?? 99))
        .map(([level, spells]) => ({
            level,
            label: level === null ? 'Conjuros' : level === 0 ? 'Trucos' : `Nivel ${level}`,
            spells: spells.sort((a, b) => a.name.localeCompare(b.name, 'es')),
        }));
});

const description = computed(() => (Array.isArray(detail.value?.desc) ? detail.value.desc.slice(0, 3) : []));

async function loadMine() {
    const results = await Promise.all(props.classes.map(async (characterClass) => {
        try {
            const { data } = await api.get(`/characters/${props.characterId}/classes/${characterClass.id}/spell-options`);
            if (!data.spellcasting) return null;
            return {
                key: `class:${characterClass.id}`,
                label: `${characterClass.class} ${characterClass.level}`,
                options: data,
            };
        } catch {
            return null;
        }
    }));

    mine.value = results.filter(Boolean);
}

async function loadSource() {
    listError.value = '';
    loadingList.value = true;
    items.value = [];

    try {
        const own = currentMine.value;

        if (own) {
            // Conjuros que le corresponden por clase y nivel.
            items.value = own.options.spells;
        } else if (source.value === 'all') {
            if (!allSpells) {
                const { data } = await api.get('/dnd/spells');
                allSpells = data.results || [];
            }
            items.value = allSpells;
        } else {
            if (!classLists[source.value]) {
                const { data } = await api.get(`/dnd/classes/${source.value}/spell-list`);
                classLists[source.value] = data;
            }
            items.value = classLists[source.value];
        }
    } catch {
        listError.value = 'No se pudo cargar la lista de conjuros.';
    } finally {
        loadingList.value = false;
    }
}

function setSource(key) {
    source.value = key;
    picked.value = null;
    detail.value = null;
    loadSource();
}

function toggle(entry, checked) {
    const next = new Set(selected.value);
    if (checked) next.add(entry.index); else next.delete(entry.index);
    selected.value = next;
    if (checked) preview(entry);
}

async function preview(entry) {
    picked.value = entry; detail.value = null; error.value = ''; loadingDetail.value = true;
    try {
        const { data } = await api.get(`/dnd/spells/${entry.index}`);
        detail.value = data;
    } catch {
        error.value = 'No se pudo cargar ese conjuro.';
    } finally { loadingDetail.value = false; }
}

async function save() {
    saving.value = true; error.value = '';

    // Lista de la que procede: la clase del personaje o la lista elegida.
    const listClass = currentMine.value ? currentMine.value.options.class.index : (source.value === 'all' ? null : source.value);

    try {
        await api.post(`/characters/${props.characterId}/spells`, { spell_indexes: [...selected.value], list_class: listClass });
        emit('saved');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo añadir el conjuro.';
    } finally { saving.value = false; }
}

function onKeydown(event) { if (event.key === 'Escape') emit('close'); }

onMounted(async () => {
    window.addEventListener('keydown', onKeydown);
    await loadMine();
    // Primero se muestran los conjuros de las clases del personaje.
    source.value = mine.value[0]?.key || 'all';
    await loadSource();
});

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
