<template>
    <section class="dashboard-page">
        <!-- Administradores: prioridad a la gestión del contenido de la página -->
        <template v-if="isAdmin">
            <header class="page-header">
                <h1>Gestión de la página</h1>
                <p>Hola, {{ user.name }}. Aquí tienes el estado de la comunidad y accesos directos para gestionar su contenido.</p>
            </header>

            <p v-if="statsError" class="error-message" role="alert">{{ statsError }}</p>

            <div class="admin-stats stagger" aria-label="Resumen">
                <RouterLink v-for="stat in statTiles" :key="stat.label" class="admin-stat" :to="stat.to">
                    <strong>{{ stats ? stat.value : '—' }}</strong>
                    <span>{{ stat.label }}</span>
                </RouterLink>
            </div>

            <h2 class="dashboard-heading">Gestionar contenido</h2>
            <div class="dashboard-grid dashboard-grid--priority stagger">
                <RouterLink v-for="card in adminCards" :key="card.title" class="dashboard-card" :to="card.to">
                    <span class="card-icon">{{ card.kicker }}</span>
                    <h3>{{ card.title }}</h3>
                    <p>{{ card.text }}</p>
                </RouterLink>
            </div>

            <div class="admin-recent">
                <section class="panel">
                    <header class="panel-heading"><div><h2>Últimos personajes</h2></div></header>
                    <ul v-if="stats?.recent_characters?.length" class="admin-detail-list recent-list">
                        <li v-for="character in stats.recent_characters" :key="character.id"><strong>{{ character.name }}</strong><small> · nivel {{ character.level }} · @{{ character.user?.username || character.user?.name }}</small></li>
                    </ul>
                    <p v-else class="empty-hint">Sin actividad todavía.</p>
                </section>
                <section class="panel">
                    <header class="panel-heading"><div><h2>Últimas campañas</h2></div></header>
                    <ul v-if="stats?.recent_campaigns?.length" class="admin-detail-list recent-list">
                        <li v-for="campaign in stats.recent_campaigns" :key="campaign.id"><strong>{{ campaign.name }}</strong><small> · @{{ campaign.master?.username || campaign.master?.name }}</small></li>
                    </ul>
                    <p v-else class="empty-hint">Sin actividad todavía.</p>
                </section>
            </div>

            <h2 class="dashboard-heading">Tu cuenta</h2>
        </template>

        <header v-else class="page-header">
            <h1>Dashboard</h1>
            <p v-if="user">¡Bienvenido/a, {{ user.name }}! ¿Qué quieres hacer hoy?</p>
            <p v-else>Elige una sección para continuar con tu aventura.</p>
        </header>

        <div class="dashboard-grid stagger" :class="{ 'dashboard-grid--compact': isAdmin }">
            <RouterLink class="dashboard-card" to="/characters">
                <span class="card-icon">PERSONAJES</span>
                <h3>Mis personajes</h3>
                <p>Consulta, edita y gestiona tus personajes.</p>
            </RouterLink>
            <RouterLink class="dashboard-card" to="/campaigns">
                <span class="card-icon">CAMPAÑAS</span>
                <h3>Mis campañas</h3>
                <p>Administra las campañas en las que eres Dungeon Master.</p>
            </RouterLink>
            <RouterLink class="dashboard-card" to="/other-campaigns">
                <span class="card-icon">PARTIDAS</span>
                <h3>Otras campañas</h3>
                <p>Campañas donde juegas y unión con código.</p>
            </RouterLink>
            <RouterLink class="dashboard-card" to="/friends">
                <span class="card-icon">COMUNIDAD</span>
                <h3>Amigos</h3>
                <p>Busca jugadores y gestiona tus solicitudes.</p>
            </RouterLink>
            <RouterLink class="dashboard-card" to="/api-2014">
                <span class="card-icon">REGLAS</span>
                <h3>API 2014</h3>
                <p>Consulta el catálogo de D&amp;D 5.ª edición.</p>
            </RouterLink>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import { getCurrentUser, isAdminUser } from '../services/auth';

const user = ref(null);
const stats = ref(null);
const statsError = ref('');

const isAdmin = computed(() => isAdminUser(user.value));

const statTiles = computed(() => [
    { label: 'Usuarios activos', value: stats.value?.users_active, to: { path: '/admin', query: { tab: 'users' } } },
    { label: 'Usuarios de baja', value: stats.value?.users_deactivated, to: { path: '/admin', query: { tab: 'users' } } },
    { label: 'Personajes', value: stats.value?.characters, to: { path: '/admin', query: { tab: 'characters' } } },
    { label: 'Campañas', value: stats.value?.campaigns, to: { path: '/admin', query: { tab: 'campaigns' } } },
    { label: 'Administradores', value: stats.value?.admins, to: { path: '/admin', query: { tab: 'admins' } } },
]);

const adminCards = [
    { kicker: 'USUARIOS', title: 'Usuarios', text: 'Busca cuentas, dalas de baja o reactívalas.', to: { path: '/admin', query: { tab: 'users' } } },
    { kicker: 'PERSONAJES', title: 'Personajes', text: 'Busca, filtra, modifica y elimina personajes.', to: { path: '/admin', query: { tab: 'characters' } } },
    { kicker: 'CAMPAÑAS', title: 'Campañas', text: 'Revisa su contenido, edítalas o elimínalas.', to: { path: '/admin', query: { tab: 'campaigns' } } },
    { kicker: 'ACCESO', title: 'Administradores', text: 'Crea nuevas cuentas de administrador.', to: { path: '/admin', query: { tab: 'admins' } } },
];

onMounted(async () => {
    try {
        user.value = await getCurrentUser();
    } catch {
        return; // El interceptor global y el guard de rutas gestionarán sesiones caducadas.
    }

    if (isAdmin.value) {
        try {
            const { data } = await api.get('/admin/stats');
            stats.value = data;
        } catch (err) {
            statsError.value = err.response?.data?.message || 'No se pudo cargar el resumen.';
        }
    }
});
</script>
