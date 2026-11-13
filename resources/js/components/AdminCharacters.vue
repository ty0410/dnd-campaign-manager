<template>
    <div class="admin-section">
        <form class="admin-filters" role="search" @submit.prevent="list.load()">
            <div class="search-field admin-search">
                <AppIcon name="search" />
                <input v-model.trim="filters.search" type="search" placeholder="Buscar por personaje, raza o jugador..." aria-label="Buscar personajes">
            </div>
            <input v-model.trim="filters.campaign" type="search" placeholder="Campaña..." aria-label="Filtrar por campaña">
            <select v-model="filters.membership" aria-label="Pertenencia a campañas">
                <option value="">Con o sin campaña</option>
                <option value="with">Con campaña</option>
                <option value="without">Sin campaña</option>
            </select>
            <select v-model="filters.sort" aria-label="Ordenar">
                <option value="">Más recientes</option>
                <option value="name">Nombre</option>
                <option value="level">Nivel</option>
            </select>
        </form>

        <p class="admin-count" aria-live="polite">{{ list.loading.value ? 'Buscando...' : `${list.total.value} personaje${list.total.value === 1 ? '' : 's'}` }}</p>
        <p v-if="message" class="success-message" role="status">{{ message }}</p>
        <p v-if="list.error.value || actionError" class="error-message" role="alert">{{ list.error.value || actionError }}</p>

        <div v-if="list.rows.value.length" class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Personaje</th><th>Jugador</th><th>Campañas</th><th class="col-actions">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="character in list.rows.value" :key="character.id">
                        <td>
                            <div class="admin-cell-main">
                                <span class="character-avatar">
                                    <img v-if="character.image_url" :src="character.image_url" :alt="`Retrato de ${character.name}`" loading="lazy">
                                    <template v-else>{{ character.name?.slice(0, 1)?.toUpperCase() || '?' }}</template>
                                </span>
                                <span class="character-info"><strong>{{ character.name }}</strong><small>{{ character.race || 'Sin raza' }} · Nivel {{ character.level }}</small></span>
                            </div>
                        </td>
                        <td><div class="admin-cell-stack"><strong>{{ character.user?.name }}</strong><small>{{ character.user?.email }}</small></div></td>
                        <td>{{ character.campaigns?.length ? character.campaigns.map((campaign) => campaign.name).join(', ') : '—' }}</td>
                        <td class="col-actions">
                            <RouterLink class="icon-button" :to="`/characters/${character.id}`" :aria-label="`Ver la ficha de ${character.name}`" title="Ver ficha"><AppIcon name="external" /></RouterLink>
                            <button class="icon-button" type="button" :aria-label="`Editar ${character.name}`" title="Editar" @click="startEdit(character)"><AppIcon name="edit" /></button>
                            <button class="icon-button danger" type="button" :aria-label="`Eliminar ${character.name}`" title="Eliminar" :disabled="busyId === character.id" @click="remove(character)"><AppIcon name="x" /></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else-if="!list.loading.value" class="empty-hint">No hay personajes que coincidan con los filtros.</p>

        <AdminPager :page="list.page.value" :last-page="list.lastPage.value" @go="list.goTo" />

        <Transition name="modal">
            <div v-if="editing" class="modal-backdrop" @click.self="editing = null">
                <form class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="admin-char-title" @submit.prevent="save">
                    <button class="modal-close" type="button" aria-label="Cerrar" @click="editing = null"><AppIcon name="x" /></button>
                    <p class="panel-kicker">Editar personaje de {{ editing.user?.name }}</p>
                    <h2 id="admin-char-title">{{ editing.name }}</h2>
                    <label for="ac-name">Nombre</label><input id="ac-name" v-model.trim="form.name" required maxlength="255">
                    <label for="ac-description">Descripción</label><textarea id="ac-description" v-model="form.description" rows="3" maxlength="5000"></textarea>
                    <div class="field-row">
                        <div><label for="ac-hp">PG actuales</label><input id="ac-hp" v-model.number="form.current_hit_points" type="number" min="0" max="9999"></div>
                        <div><label for="ac-hpm">PG máximos</label><input id="ac-hpm" v-model.number="form.maximum_hit_points" type="number" min="0" max="9999"></div>
                        <div><label for="ac-hpt">PG temporales</label><input id="ac-hpt" v-model.number="form.temporary_hit_points" type="number" min="0" max="9999"></div>
                    </div>
                    <label for="ac-trait">Rasgo de personalidad</label><textarea id="ac-trait" v-model="form.personality_trait" rows="2" maxlength="5000"></textarea>
                    <label for="ac-ideal">Ideal</label><textarea id="ac-ideal" v-model="form.ideal" rows="2" maxlength="5000"></textarea>
                    <label for="ac-bond">Vínculo</label><textarea id="ac-bond" v-model="form.bond" rows="2" maxlength="5000"></textarea>
                    <label for="ac-flaw">Defecto</label><textarea id="ac-flaw" v-model="form.flaw" rows="2" maxlength="5000"></textarea>
                    <label for="ac-notes">Notas</label><textarea id="ac-notes" v-model="form.notes" rows="3" maxlength="10000"></textarea>
                    <p v-if="modalError" class="error-message" role="alert">{{ modalError }}</p>
                    <div class="modal-actions">
                        <button class="secondary-button" type="button" @click="editing = null">Cancelar</button>
                        <button class="primary-button" type="submit" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar cambios' }}</button>
                    </div>
                </form>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import { useAdminList } from '../services/adminList';
import AppIcon from './AppIcon.vue';
import AdminPager from './AdminPager.vue';

const filters = ref({ search: '', campaign: '', membership: '', sort: '' });
const list = useAdminList('/admin/characters', filters);

const editing = ref(null);
const form = ref({});
const saving = ref(false);
const modalError = ref('');
const message = ref('');
const actionError = ref('');
const busyId = ref(null);

const FIELDS = ['name', 'description', 'personality_trait', 'ideal', 'bond', 'flaw', 'notes', 'current_hit_points', 'maximum_hit_points', 'temporary_hit_points'];
const numberOrNull = (value) => (value === '' || value === undefined || Number.isNaN(value) ? null : value);

function startEdit(character) {
    editing.value = character;
    modalError.value = '';
    form.value = Object.fromEntries(FIELDS.map((key) => [key, character[key] ?? '']));
}

async function save() {
    saving.value = true; modalError.value = '';
    const payload = { ...form.value };
    for (const key of ['current_hit_points', 'maximum_hit_points', 'temporary_hit_points']) payload[key] = numberOrNull(payload[key]);
    try {
        const { data } = await api.patch(`/admin/characters/${editing.value.id}`, payload);
        message.value = data.message; editing.value = null;
        await list.load();
    } catch (err) {
        modalError.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo guardar.';
    } finally { saving.value = false; }
}

async function remove(character) {
    if (!window.confirm(`¿Eliminar definitivamente a «${character.name}» (de ${character.user?.name})? No se puede deshacer.`)) return;
    busyId.value = character.id; actionError.value = ''; message.value = '';
    try {
        const { data } = await api.delete(`/admin/characters/${character.id}`);
        message.value = data.message;
        await list.load();
    } catch (err) {
        actionError.value = err.response?.data?.message || 'No se pudo eliminar.';
    } finally { busyId.value = null; }
}

function onKeydown(event) { if (event.key === 'Escape') editing.value = null; }
onMounted(() => { window.addEventListener('keydown', onKeydown); list.load(); });
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
