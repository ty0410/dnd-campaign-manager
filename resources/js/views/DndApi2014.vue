<template>
    <main class="srd-page">
        <header class="page-header">
            <h1>API 2014</h1>
        </header>

        <div class="tab-bar" role="tablist" aria-label="Categorías">
            <button v-for="entry in categories" :key="entry.key" type="button" role="tab" class="tab" :class="{ 'is-active': category.key === entry.key }" :aria-selected="category.key === entry.key" @click="selectCategory(entry)">{{ entry.label }}</button>
        </div>

        <div class="srd-layout">
            <section class="srd-list" :aria-label="`Listado de ${category.label}`">
                <div class="search-field">
                    <AppIcon name="search" />
                    <input v-model.trim="search" type="search" :placeholder="`Buscar en ${category.label.toLowerCase()}...`" aria-label="Buscar">
                </div>
                <p class="list-count">{{ loadingList ? 'Cargando...' : `${filtered.length} de ${items.length}` }}</p>
                <p v-if="listError" class="error-message" role="alert">{{ listError }}</p>
                <ul class="srd-items">
                    <li v-for="item in visible" :key="item.index">
                        <button type="button" class="srd-item" :class="{ 'is-selected': selected?.index === item.index }" @click="openItem(item)">{{ item.name }}</button>
                    </li>
                </ul>
                <button v-if="filtered.length > limit" class="secondary-button" type="button" @click="limit += 60">Mostrar más ({{ filtered.length - limit }})</button>
            </section>

            <article class="srd-detail" aria-live="polite">
                <p v-if="!selected && !loadingDetail" class="empty-hint">Elige un elemento del listado para leer su ficha.</p>
                <p v-if="loadingDetail" class="empty-hint">Cargando...</p>
                <p v-if="detailError" class="error-message" role="alert">{{ detailError }}</p>
                <template v-if="detail && !loadingDetail">
                    <h2>{{ detail.name || selected?.name }}</h2>
                    <dl v-if="facts.length" class="sheet-facts">
                        <div v-for="fact in facts" :key="fact.label"><dt>{{ fact.label }}</dt><dd>{{ fact.value }}</dd></div>
                    </dl>
                    <section v-for="block in blocks" :key="block.title" class="sheet-block">
                        <h3>{{ block.title }}</h3>
                        <p v-for="(line, index) in block.lines" :key="index" class="sheet-text">{{ line }}</p>
                    </section>
                </template>
            </article>
        </div>

        <RouterLink class="back-link" to="/dashboard"><AppIcon name="back" /> Volver al dashboard</RouterLink>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';

const categories = [
    { key: 'races', label: 'Razas', path: '/dnd/races' },
    { key: 'subraces', label: 'Subrazas', path: '/dnd/subraces' },
    { key: 'classes', label: 'Clases', path: '/dnd/classes' },
    { key: 'backgrounds', label: 'Trasfondos', path: '/dnd/backgrounds' },
    { key: 'spells', label: 'Conjuros', path: '/dnd/spells' },
    { key: 'monsters', label: 'Monstruos', path: '/dnd/monsters' },
    { key: 'equipment', label: 'Equipo', path: '/dnd/equipment' },
];

const category = ref(categories[0]);
const items = ref([]);
const search = ref('');
const limit = ref(60);
const loadingList = ref(false);
const listError = ref('');

const selected = ref(null);
const detail = ref(null);
const loadingDetail = ref(false);
const detailError = ref('');

// Los listados se piden una vez por categoría.
const cache = {};

const normalize = (text) => String(text ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
const filtered = computed(() => {
    const term = normalize(search.value);
    return term ? items.value.filter((item) => normalize(item.name).includes(term)) : items.value;
});
const visible = computed(() => filtered.value.slice(0, limit.value));

async function selectCategory(entry) {
    category.value = entry;
    search.value = ''; limit.value = 60;
    selected.value = null; detail.value = null; detailError.value = '';
    await loadList();
}

async function loadList() {
    listError.value = '';
    if (cache[category.value.key]) { items.value = cache[category.value.key]; return; }

    loadingList.value = true; items.value = [];
    try {
        const { data } = await api.get(category.value.path);
        const list = Array.isArray(data) ? data : (data.results || []);
        cache[category.value.key] = list;
        items.value = list;
    } catch {
        listError.value = 'No se pudo cargar el listado. Inténtalo de nuevo en unos segundos.';
    } finally { loadingList.value = false; }
}

async function openItem(item) {
    selected.value = item; detail.value = null; detailError.value = ''; loadingDetail.value = true;
    try {
        const { data } = await api.get(`${category.value.path}/${item.index}`);
        detail.value = data;
    } catch {
        detailError.value = 'No se pudo cargar este elemento.';
    } finally { loadingDetail.value = false; }
}

// ---- Presentación genérica del JSON de la API ------------------------------
const LABELS = {
    hit_die: 'Dado de golpe', size: 'Tamaño', speed: 'Velocidad', alignment: 'Alineamiento', type: 'Tipo',
    level: 'Nivel', school: 'Escuela', range: 'Alcance', duration: 'Duración', casting_time: 'Tiempo de lanzamiento',
    components: 'Componentes', ritual: 'Ritual', concentration: 'Concentración', armor_class: 'Clase de armadura',
    hit_points: 'Puntos de golpe', challenge_rating: 'Desafío', cost: 'Coste', weight: 'Peso',
    equipment_category: 'Categoría', gear_category: 'Tipo de equipo', weapon_category: 'Categoría de arma',
    weapon_range: 'Alcance del arma', category_range: 'Alcance', ability_score_bonuses: 'Bonos de característica',
    language_desc: 'Idiomas', age: 'Edad', size_description: 'Tamaño', speed_walk: 'Velocidad',
};

const label = (key) => LABELS[key] || key.replaceAll('_', ' ').replace(/^./, (c) => c.toUpperCase());

const nameOf = (value) => (value && typeof value === 'object' ? (value.name ?? value.desc ?? '') : value);

function scalar(value) {
    if (value === null || value === undefined || value === '') return '';
    if (typeof value === 'boolean') return value ? 'Sí' : 'No';
    if (Array.isArray(value)) {
        const parts = value.map((entry) => (typeof entry === 'object' ? nameOf(entry) : entry)).filter((entry) => entry !== '' && entry !== undefined);
        return parts.length && parts.every((part) => typeof part !== 'object') ? parts.join(', ') : '';
    }
    if (typeof value === 'object') {
        if (value.name) return value.name;
        if ('quantity' in value && 'unit' in value) return `${value.quantity} ${value.unit}`;
        return '';
    }
    return String(value);
}

const SKIP = new Set(['index', 'name', 'url', 'updated_at', 'desc', 'higher_level', 'special_abilities', 'actions', 'legendary_actions', 'reactions', 'proficiencies', 'starting_equipment', 'class_levels', 'spellcasting', 'multi_classing', 'subclasses', 'starting_proficiencies', 'starting_proficiency_options', 'traits', 'language_options', 'ability_bonus_options', 'feature', 'feature_name']);

const facts = computed(() => {
    const data = detail.value || {};
    return Object.entries(data)
        .filter(([key]) => !SKIP.has(key))
        .map(([key, value]) => ({ label: label(key), value: scalar(value) }))
        .filter((fact) => fact.value !== '' && String(fact.value).length <= 140);
});

const toLines = (value) => {
    if (Array.isArray(value)) return value.flatMap((entry) => (typeof entry === 'string' ? [entry] : entry?.desc ? [`${entry.name ? `${entry.name}. ` : ''}${Array.isArray(entry.desc) ? entry.desc.join(' ') : entry.desc}`] : entry?.name ? [entry.name] : []));
    return typeof value === 'string' ? [value] : [];
};

const blocks = computed(() => {
    const data = detail.value || {};
    const list = [];
    const add = (title, value) => { const lines = toLines(value); if (lines.length) list.push({ title, lines }); };

    add('Descripción', data.desc);
    add('A niveles superiores', data.higher_level);
    add('Rasgos', data.traits);
    add('Capacidades especiales', data.special_abilities);
    add('Acciones', data.actions);
    add('Acciones legendarias', data.legendary_actions);
    add('Reacciones', data.reactions);
    add('Competencias', (data.proficiencies || []).map((entry) => entry.name || entry.proficiency?.name).filter(Boolean));
    add('Equipo inicial', (data.starting_equipment || []).map((entry) => `${entry.quantity ?? 1}× ${entry.equipment?.name || ''}`));
    add('Subclases', (data.subclasses || []).map((entry) => entry.name));
    add('Rasgo de trasfondo', data.feature ? [{ name: data.feature.name, desc: data.feature.desc }] : []);
    add('Idiomas', data.language_desc);
    add('Edad', data.age);
    add('Tamaño', data.size_description);
    add('Alineamiento', data.alignment && typeof data.alignment === 'string' && data.alignment.length > 140 ? data.alignment : '');

    return list;
});

onMounted(loadList);
</script>
