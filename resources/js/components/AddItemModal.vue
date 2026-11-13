<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <section class="modal modal-wide add-item-modal" role="dialog" aria-modal="true" aria-labelledby="add-item-title">
            <button class="modal-close" type="button" aria-label="Cerrar" @click="$emit('close')"><AppIcon name="x" /></button>
            <h2 id="add-item-title">Añadir objeto</h2>

            <div class="player-tabs" role="tablist" aria-label="Origen del objeto">
                <button type="button" role="tab" class="chip-button" :class="{ 'is-active': mode === 'catalog' }" :aria-selected="mode === 'catalog'" @click="mode = 'catalog'">Catálogo de la API</button>
                <button type="button" role="tab" class="chip-button" :class="{ 'is-active': mode === 'custom' }" :aria-selected="mode === 'custom'" @click="mode = 'custom'">Objeto personalizado</button>
            </div>

            <!-- Catálogo -->
            <div v-if="mode === 'catalog'" class="catalog-picker">
                <div class="catalog-list">
                    <div class="search-field">
                        <AppIcon name="search" />
                        <input v-model.trim="search" type="search" placeholder="Buscar (p. ej. longsword, rope)..." aria-label="Buscar objeto">
                    </div>
                    <p v-if="loadingList" class="empty-hint">Cargando catálogo...</p>
                    <p v-else-if="listError" class="error-message" role="alert">{{ listError }}</p>
                    <ul class="srd-items">
                        <li v-for="entry in matches" :key="entry.index"><button type="button" class="srd-item" :class="{ 'is-selected': picked?.index === entry.index }" @click="pick(entry)">{{ entry.name }}</button></li>
                    </ul>
                    <p v-if="search.length >= 2 && !matches.length && !loadingList" class="empty-hint">Sin resultados.</p>
                    <p v-else-if="search.length < 2 && !loadingList" class="empty-hint">Escribe al menos dos letras para buscar.</p>
                </div>

                <div class="catalog-detail" aria-live="polite">
                    <p v-if="!picked" class="empty-hint">Elige un objeto para ver sus detalles.</p>
                    <p v-else-if="loadingDetail" class="empty-hint">Cargando...</p>
                    <template v-else-if="detail">
                        <h3>{{ detail.name }}</h3>
                        <dl v-if="facts.length" class="sheet-facts">
                            <div v-for="fact in facts" :key="fact.label"><dt>{{ fact.label }}</dt><dd>{{ fact.value }}</dd></div>
                        </dl>
                        <p v-for="(line, index) in description" :key="index" class="sheet-text">{{ line }}</p>
                    </template>
                </div>
            </div>

            <!-- Personalizado -->
            <div v-else class="custom-item">
                <label for="item-custom-name">Nombre</label>
                <input id="item-custom-name" v-model.trim="custom.name" maxlength="255" placeholder="Poción extraña">
                <label for="item-custom-kind">Tipo</label>
                <select id="item-custom-kind" v-model="custom.kind">
                    <option value="item">Objeto</option>
                    <option value="light">Armadura ligera</option>
                    <option value="medium">Armadura media</option>
                    <option value="heavy">Armadura pesada</option>
                    <option value="shield">Escudo</option>
                </select>
                <template v-if="custom.kind !== 'item'">
                    <label for="item-custom-ac">CA base</label>
                    <input id="item-custom-ac" v-model.number="custom.armor_base" type="number" min="0" max="30" :placeholder="custom.kind === 'shield' ? '2' : '11'">
                </template>
                <label v-else class="check-row"><input v-model="custom.stackable" type="checkbox"> Se posee en cantidad (flechas, pociones...)</label>
                <label for="item-custom-weight">Peso unitario (kg, opcional)</label>
                <input id="item-custom-weight" v-model.number="custom.weight" type="number" min="0" step="0.1">
                <label for="item-custom-notes">Notas (opcional)</label>
                <textarea id="item-custom-notes" v-model="custom.notes" rows="3" maxlength="1000"></textarea>
            </div>

            <template v-if="showQuantity">
                <label for="item-quantity">Cantidad</label>
                <input id="item-quantity" v-model.number="quantity" class="quantity-input" type="number" min="1" max="999">
            </template>

            <p v-if="error" class="error-message" role="alert">{{ error }}</p>
            <div class="modal-actions">
                <button class="secondary-button" type="button" @click="$emit('close')">Cancelar</button>
                <button class="primary-button" type="button" :disabled="saving || !canSave" @click="save">{{ saving ? 'Añadiendo...' : 'Añadir al inventario' }}</button>
            </div>
        </section>
    </div>
</template>

<script>
// El catálogo se pide una sola vez por sesión.
let equipmentCatalog = null;
</script>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';

const props = defineProps({ characterId: { type: [Number, String], required: true } });
const emit = defineEmits(['close', 'saved']);

const mode = ref('catalog');
const search = ref('');
const items = ref([]);
const loadingList = ref(false);
const listError = ref('');
const picked = ref(null);
const detail = ref(null);
const loadingDetail = ref(false);
const quantity = ref(1);
const custom = ref({ name: '', kind: 'item', armor_base: '', stackable: false, weight: '', notes: '' });
const saving = ref(false);
const error = ref('');

const normalize = (text) => String(text ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
const matches = computed(() => {
    const term = normalize(search.value);
    if (term.length < 2) return [];
    return items.value.filter((entry) => normalize(entry.name).includes(term)).slice(0, 40);
});

const STACKABLE = /(arrow|bolt|bullet|dart|needle|potion|vial|flask|ration|torch|oil|candle|chalk|ink|parchment|paper|piton|caltrops|antitoxin|holy water|acid|alchemist)/i;

// La cantidad solo se pide para objetos que suelen poseerse en número.
const showQuantity = computed(() => (mode.value === 'custom'
    ? custom.value.kind === 'item' && custom.value.stackable
    : !!detail.value && (detail.value.equipment_category?.index === 'ammunition' || STACKABLE.test(detail.value.name || ''))));

const canSave = computed(() => (mode.value === 'catalog' ? !!detail.value : !!custom.value.name));

async function loadCatalog() {
    if (equipmentCatalog) { items.value = equipmentCatalog; return; }
    loadingList.value = true;
    try {
        const { data } = await api.get('/dnd/equipment');
        equipmentCatalog = Array.isArray(data) ? data : (data.results || []);
        items.value = equipmentCatalog;
    } catch {
        listError.value = 'No se pudo cargar el catálogo. Puedes añadir un objeto personalizado.';
    } finally { loadingList.value = false; }
}

async function pick(entry) {
    picked.value = entry; detail.value = null; error.value = ''; loadingDetail.value = true;
    try {
        const { data } = await api.get(`/dnd/equipment/${entry.index}`);
        detail.value = data;
    } catch {
        error.value = 'No se pudo cargar ese objeto.';
    } finally { loadingDetail.value = false; }
}

// Presentación de los datos del objeto
const facts = computed(() => {
    const d = detail.value || {};
    const list = [];
    const add = (label, value) => { if (value !== undefined && value !== null && value !== '') list.push({ label, value }); };

    add('Categoría', d.equipment_category?.name);
    add('Tipo de arma', d.weapon_category);
    add('Tipo de armadura', d.armor_category);
    add('Tipo', d.gear_category?.name || d.tool_category || d.vehicle_category);
    add('Coste', d.cost ? `${d.cost.quantity} ${d.cost.unit}` : null);
    add('Peso', d.weight !== undefined ? `${Math.round(d.weight * 0.4536 * 100) / 100} kg` : null);
    add('Daño', d.damage ? `${d.damage.damage_dice} ${d.damage.damage_type?.name || ''}`.trim() : null);
    add('Alcance', d.range ? `${d.range.normal}${d.range.long ? `/${d.range.long}` : ''} pies` : null);
    add('Clase de armadura', d.armor_class ? `${d.armor_class.base}${d.armor_class.dex_bonus ? ' + Des' : ''}` : null);
    add('Fuerza mínima', d.str_minimum || null);
    add('Sigilo', d.stealth_disadvantage ? 'Desventaja' : null);
    add('Propiedades', (d.properties || []).map((entry) => entry.name).join(', '));
    return list;
});

const description = computed(() => (Array.isArray(detail.value?.desc) ? detail.value.desc : []));

async function save() {
    saving.value = true; error.value = '';
    const payload = mode.value === 'catalog'
        ? { equipment_index: picked.value.index, quantity: showQuantity.value ? (quantity.value || 1) : 1 }
        : {
            item_name: custom.value.name,
            custom_kind: custom.value.kind,
            armor_base: custom.value.kind === 'item' ? null : (custom.value.armor_base === '' ? null : custom.value.armor_base),
            stackable: custom.value.kind === 'item' && custom.value.stackable,
            weight: custom.value.weight === '' ? null : custom.value.weight,
            notes: custom.value.notes || null,
            quantity: showQuantity.value ? (quantity.value || 1) : 1,
        };

    try {
        await api.post(`/characters/${props.characterId}/inventory`, payload);
        emit('saved');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo añadir el objeto.';
    } finally { saving.value = false; }
}

function onKeydown(event) { if (event.key === 'Escape') emit('close'); }
onMounted(() => { window.addEventListener('keydown', onKeydown); loadCatalog(); });
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
