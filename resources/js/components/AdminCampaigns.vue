<template>
    <div class="admin-section">
        <form class="admin-filters" role="search" @submit.prevent="list.load()">
            <div class="search-field admin-search">
                <AppIcon name="search" />
                <input v-model.trim="filters.search" type="search" placeholder="Buscar por campaña, descripción o Dungeon Master..." aria-label="Buscar campañas">
            </div>
            <select v-model="filters.sort" aria-label="Ordenar">
                <option value="">Más recientes</option>
                <option value="name">Nombre</option>
                <option value="characters">Más personajes</option>
            </select>
        </form>

        <p class="admin-count" aria-live="polite">{{ list.loading.value ? 'Buscando...' : `${list.total.value} campaña${list.total.value === 1 ? '' : 's'}` }}</p>
        <p v-if="message" class="success-message" role="status">{{ message }}</p>
        <p v-if="list.error.value || actionError" class="error-message" role="alert">{{ list.error.value || actionError }}</p>

        <div v-if="list.rows.value.length" class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Campaña</th><th>Dungeon Master</th><th>Personajes</th><th class="col-actions">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="campaign in list.rows.value" :key="campaign.id">
                        <td><div class="admin-cell-stack"><strong>{{ campaign.name }}</strong><small class="clamp-1">{{ campaign.description || 'Sin descripción' }}</small></div></td>
                        <td><div class="admin-cell-stack"><strong>{{ campaign.master?.name }}</strong><small>{{ campaign.master?.email }}</small></div></td>
                        <td>{{ campaign.characters_count }}</td>
                        <td class="col-actions">
                            <button class="icon-button" type="button" :aria-label="`Ver el contenido de ${campaign.name}`" title="Ver contenido" @click="openDetail(campaign)"><AppIcon name="search" /></button>
                            <button class="icon-button" type="button" :aria-label="`Editar ${campaign.name}`" title="Editar" @click="startEdit(campaign)"><AppIcon name="edit" /></button>
                            <button class="icon-button danger" type="button" :aria-label="`Eliminar ${campaign.name}`" title="Eliminar" :disabled="busyId === campaign.id" @click="remove(campaign)"><AppIcon name="x" /></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else-if="!list.loading.value" class="empty-hint">No hay campañas que coincidan con los filtros.</p>

        <AdminPager :page="list.page.value" :last-page="list.lastPage.value" @go="list.goTo" />

        <Transition name="modal">
            <div v-if="editing" class="modal-backdrop" @click.self="editing = null">
                <form class="modal" role="dialog" aria-modal="true" aria-labelledby="admin-camp-title" @submit.prevent="save">
                    <button class="modal-close" type="button" aria-label="Cerrar" @click="editing = null"><AppIcon name="x" /></button>
                    <p class="panel-kicker">Editar campaña de {{ editing.master?.name }}</p>
                    <h2 id="admin-camp-title">{{ editing.name }}</h2>
                    <label for="acp-name">Nombre</label><input id="acp-name" v-model.trim="form.name" required maxlength="255">
                    <label for="acp-description">Descripción</label><textarea id="acp-description" v-model="form.description" rows="5" maxlength="5000"></textarea>
                    <p v-if="modalError" class="error-message" role="alert">{{ modalError }}</p>
                    <div class="modal-actions">
                        <button class="secondary-button" type="button" @click="editing = null">Cancelar</button>
                        <button class="primary-button" type="submit" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar cambios' }}</button>
                    </div>
                </form>
            </div>
        </Transition>

        <Transition name="modal">
            <div v-if="detail" class="modal-backdrop" @click.self="detail = null">
                <section class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="admin-detail-title">
                    <button class="modal-close" type="button" aria-label="Cerrar" @click="detail = null"><AppIcon name="x" /></button>
                    <h2 id="admin-detail-title">{{ detail.name }}</h2>
                    <p class="muted">Dungeon Master: {{ detail.master?.name }} ({{ detail.master?.email }})</p>
                    <p v-if="detailLoading" class="empty-hint">Cargando...</p>
                    <template v-else>
                        <div class="admin-detail-grid">
                            <section v-for="group in detailGroups" :key="group.title">
                                <h3>{{ group.title }} <small>({{ group.items.length }})</small></h3>
                                <ul v-if="group.items.length" class="admin-detail-list"><li v-for="item in group.items" :key="item.key">{{ item.label }}<small v-if="item.extra"> · {{ item.extra }}</small></li></ul>
                                <p v-else class="sheet-empty">Vacío.</p>
                            </section>
                        </div>
                    </template>
                    <p v-if="modalError" class="error-message" role="alert">{{ modalError }}</p>
                </section>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import { useAdminList } from '../services/adminList';
import { formatDate } from '../services/campaignItems';
import AppIcon from './AppIcon.vue';
import AdminPager from './AdminPager.vue';

const filters = ref({ search: '', sort: '' });
const list = useAdminList('/admin/campaigns', filters);

const editing = ref(null);
const form = ref({});
const saving = ref(false);
const modalError = ref('');
const message = ref('');
const actionError = ref('');
const busyId = ref(null);

const detail = ref(null);
const detailLoading = ref(false);

function startEdit(campaign) {
    editing.value = campaign; modalError.value = '';
    form.value = { name: campaign.name, description: campaign.description ?? '' };
}

async function save() {
    saving.value = true; modalError.value = '';
    try {
        const { data } = await api.patch(`/admin/campaigns/${editing.value.id}`, form.value);
        message.value = data.message; editing.value = null;
        await list.load();
    } catch (err) {
        modalError.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo guardar.';
    } finally { saving.value = false; }
}

async function remove(campaign) {
    if (!window.confirm(`¿Eliminar definitivamente la campaña «${campaign.name}» (de ${campaign.master?.name}) con todo su contenido? No se puede deshacer.`)) return;
    busyId.value = campaign.id; actionError.value = ''; message.value = '';
    try {
        const { data } = await api.delete(`/admin/campaigns/${campaign.id}`);
        message.value = data.message;
        await list.load();
    } catch (err) {
        actionError.value = err.response?.data?.message || 'No se pudo eliminar.';
    } finally { busyId.value = null; }
}

async function openDetail(campaign) {
    detail.value = campaign; detailLoading.value = true; modalError.value = '';
    try {
        const { data } = await api.get(`/admin/campaigns/${campaign.id}`);
        detail.value = data;
    } catch (err) {
        modalError.value = err.response?.data?.message || 'No se pudo cargar el contenido.';
    } finally { detailLoading.value = false; }
}

const detailGroups = computed(() => {
    const d = detail.value || {};
    return [
        { title: 'Personajes', items: (d.characters || []).map((c) => ({ key: `c${c.id}`, label: c.name, extra: `${c.race || 'Sin raza'} · nivel ${c.level} · ${c.user?.name || ''}` })) },
        { title: 'Sesiones', items: (d.game_sessions || []).map((s) => ({ key: `s${s.id}`, label: s.title, extra: formatDate(s.session_date) })) },
        { title: 'Lugares', items: (d.locations || []).map((l) => ({ key: `l${l.id}`, label: l.name, extra: l.type })) },
        { title: 'PNJ', items: (d.npcs || []).map((n) => ({ key: `n${n.id}`, label: n.name, extra: n.role })) },
        { title: 'Encuentros', items: (d.encounters || []).map((e) => ({ key: `e${e.id}`, label: e.name })) },
    ];
});

function onKeydown(event) { if (event.key === 'Escape') { editing.value = null; detail.value = null; } }
onMounted(() => { window.addEventListener('keydown', onKeydown); list.load(); });
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
