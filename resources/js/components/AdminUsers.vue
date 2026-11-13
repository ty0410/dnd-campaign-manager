<template>
    <div class="admin-section">
        <form class="admin-filters" role="search" @submit.prevent="list.load()">
            <div class="search-field admin-search">
                <AppIcon name="search" />
                <input v-model.trim="filters.search" type="search" placeholder="Buscar por nombre, usuario o correo..." aria-label="Buscar usuarios">
            </div>
            <select v-model="filters.status" aria-label="Estado de la cuenta">
                <option value="">Activos y de baja</option>
                <option value="active">Solo activos</option>
                <option value="deactivated">Solo de baja</option>
            </select>
            <select v-model="filters.sort" aria-label="Ordenar">
                <option value="">Más recientes</option>
                <option value="name">Nombre</option>
                <option value="characters">Más personajes</option>
            </select>
        </form>

        <p class="admin-count" aria-live="polite">{{ list.loading.value ? 'Buscando...' : `${list.total.value} usuario${list.total.value === 1 ? '' : 's'}` }}</p>
        <p v-if="message" class="success-message" role="status">{{ message }}</p>
        <p v-if="list.error.value || actionError" class="error-message" role="alert">{{ list.error.value || actionError }}</p>

        <div v-if="list.rows.value.length" class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Usuario</th><th>Correo</th><th>Contenido</th><th>Estado</th><th class="col-actions">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="user in list.rows.value" :key="user.id" :class="{ 'is-deactivated': user.deactivated_at }">
                        <td><div class="admin-cell-main"><UserAvatar :user="user" /><span class="admin-cell-stack"><strong>@{{ user.username }}</strong><small>{{ user.name }}</small></span></div></td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.characters_count }} personaje{{ user.characters_count === 1 ? '' : 's' }} · {{ user.mastered_campaigns_count }} campaña{{ user.mastered_campaigns_count === 1 ? '' : 's' }}</td>
                        <td><span class="tag" :class="user.deactivated_at ? 'tag--off' : 'tag--ok'">{{ user.deactivated_at ? 'De baja' : 'Activo' }}</span></td>
                        <td class="col-actions">
                            <button v-if="!user.deactivated_at" class="danger-button" type="button" :disabled="busyId === user.id" @click="deactivate(user)">Dar de baja</button>
                            <button v-else class="secondary-button" type="button" :disabled="busyId === user.id" @click="reactivate(user)">Reactivar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else-if="!list.loading.value" class="empty-hint">No hay usuarios que coincidan con los filtros.</p>

        <AdminPager :page="list.page.value" :last-page="list.lastPage.value" @go="list.goTo" />
        <p class="muted admin-note">Dar de baja cierra sus sesiones y le impide iniciar sesión, pero conserva sus personajes y campañas. Se puede reactivar en cualquier momento.</p>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../services/api';
import { useAdminList } from '../services/adminList';
import AppIcon from './AppIcon.vue';
import AdminPager from './AdminPager.vue';
import UserAvatar from './UserAvatar.vue';

const filters = ref({ search: '', status: '', sort: '' });
const list = useAdminList('/admin/users', filters);

const message = ref('');
const actionError = ref('');
const busyId = ref(null);

async function change(user, action, question) {
    if (question && !window.confirm(question)) return;
    busyId.value = user.id; message.value = ''; actionError.value = '';
    try {
        const { data } = await api.post(`/admin/users/${user.id}/${action}`);
        message.value = data.message;
        await list.load();
    } catch (err) {
        actionError.value = err.response?.data?.message || 'No se pudo completar la acción.';
    } finally { busyId.value = null; }
}

const deactivate = (user) => change(user, 'deactivate', `¿Dar de baja a @${user.username}? No podrá iniciar sesión hasta que lo reactives.`);
const reactivate = (user) => change(user, 'reactivate');

onMounted(() => list.load());
</script>
