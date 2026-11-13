<template>
    <section class="content-manager">
        <header class="content-manager-heading">
            <div>
                <p class="panel-kicker">Contenido</p>
                <h2>Todo lo que hay en tu campaña</h2>
                <p class="muted">Edita o elimina cualquier elemento. Los cambios se guardan al momento.</p>
            </div>
        </header>

        <div class="tab-bar" role="tablist" aria-label="Tipo de contenido">
            <button v-for="tab in tabs" :key="tab.key" type="button" role="tab" class="tab" :class="{ 'is-active': activeTab === tab.key }" :aria-selected="activeTab === tab.key" @click="activeTab = tab.key">{{ tab.label }}</button>
        </div>

        <p v-if="error" class="error-message" role="alert">{{ error }}</p>
        <p v-if="loading" class="state-message">Cargando contenido...</p>

        <template v-else>
            <div class="content-toolbar">
                <p class="muted">{{ currentTab.hint }}</p>
                <button v-if="currentTab.type" class="primary-button" type="button" @click="modal = { type: currentTab.type, item: null }"><AppIcon name="plus" /> Añadir</button>
            </div>

            <ul v-if="rows.length" class="content-list">
                <li v-for="row in rows" :key="row.id" class="content-row">
                    <div class="content-row-copy">
                        <strong>{{ row.title }}</strong>
                        <small v-if="row.subtitle">{{ row.subtitle }}</small>
                    </div>
                    <div class="content-row-actions">
                        <button v-if="currentTab.type" class="icon-button" type="button" :aria-label="`Editar ${row.title}`" title="Editar" @click="modal = { type: currentTab.type, item: row.item }"><AppIcon name="edit" /></button>
                        <button class="icon-button danger" type="button" :aria-label="`${currentTab.type ? 'Eliminar' : 'Retirar'} ${row.title}`" :title="currentTab.type ? 'Eliminar' : 'Retirar de la campaña'" :disabled="busyId === row.id" @click="remove(row)"><AppIcon name="x" /></button>
                    </div>
                </li>
            </ul>
            <p v-else class="empty-hint">{{ currentTab.empty }}</p>
        </template>

        <Transition name="modal">
            <CampaignItemModal v-if="modal" :key="`${modal.type}-${modal.item?.id || 'new'}`" :type="modal.type" :item="modal.item" :campaign-id="campaignId" @close="modal = null" @saved="onSaved" />
        </Transition>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';
import CampaignItemModal from './CampaignItemModal.vue';
import { ITEM_TYPES, itemSubtitle } from '../services/campaignItems';

const props = defineProps({
    campaignId: { type: [Number, String], required: true },
});

const tabs = [
    { key: 'sessions', type: 'session', label: 'Sesiones', hint: 'Cronología de la campaña.', empty: 'No hay sesiones.' },
    { key: 'locations', type: 'location', label: 'Lugares', hint: 'Regiones, ciudades y mazmorras.', empty: 'No hay lugares.' },
    { key: 'npcs', type: 'npc', label: 'PNJ', hint: 'Personajes no jugadores.', empty: 'No hay PNJ.' },
    { key: 'encounters', type: 'encounter', label: 'Encuentros', hint: 'Encuentros de combate.', empty: 'No hay encuentros.' },
    { key: 'characters', type: null, label: 'Personajes', hint: 'Para añadir personajes usa el botón + del panel de la campaña.', empty: 'No hay personajes asignados.' },
];

const activeTab = ref('sessions');
const data = ref({ sessions: [], locations: [], npcs: [], encounters: [], characters: [] });
const loading = ref(true);
const error = ref('');
const modal = ref(null);
const busyId = ref(null);

const currentTab = computed(() => tabs.find((tab) => tab.key === activeTab.value));

const rows = computed(() => {
    const tab = currentTab.value;
    return data.value[tab.key].map((item) => {
        if (!tab.type) {
            return { id: item.id, item, title: item.name, subtitle: `${item.race || 'Sin raza'} · Nivel ${item.level}${item.user?.name ? ` · de ${item.user.name}` : ''}` };
        }
        return { id: item.id, item, title: item[ITEM_TYPES[tab.type].nameKey], subtitle: itemSubtitle(tab.type, item) };
    });
});

async function load() {
    loading.value = true; error.value = '';
    const id = props.campaignId;
    try {
        const [sessions, locations, npcs, encounters, characters] = await Promise.all([
            api.get(`/campaigns/${id}/sessions`), api.get(`/campaigns/${id}/locations`), api.get(`/campaigns/${id}/npcs`),
            api.get(`/campaigns/${id}/encounters`), api.get(`/campaigns/${id}/characters`),
        ]);
        data.value = { sessions: sessions.data, locations: locations.data, npcs: npcs.data, encounters: encounters.data, characters: characters.data };
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar el contenido.';
    } finally { loading.value = false; }
}

async function onSaved() {
    modal.value = null;
    await load();
}

async function remove(row) {
    const tab = currentTab.value;
    const question = tab.type
        ? `¿Eliminar «${row.title}»? Esta acción no se puede deshacer.`
        : `¿Retirar a «${row.title}» de la campaña? La ficha se conserva.`;
    if (!window.confirm(question)) return;

    busyId.value = row.id; error.value = '';
    try {
        if (tab.type) await api.delete(`/${ITEM_TYPES[tab.type].endpoint}/${row.id}`);
        else await api.delete(`/campaigns/${props.campaignId}/characters/${row.id}`);
        await load();
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo completar la acción.';
    } finally { busyId.value = null; }
}

onMounted(load);
</script>
