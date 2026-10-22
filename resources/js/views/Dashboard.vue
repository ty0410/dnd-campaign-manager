<template>
    <section class="dashboard-page">
        <h2>Dashboard</h2>
        <p v-if="user">¡Bienvenido/a, {{ user.name }}! ¿Qué quieres hacer hoy?</p>
        <p v-else>Elige una sección para continuar con tu aventura.</p>

        <div class="dashboard-grid">
            <RouterLink class="dashboard-card" to="/characters">
                <span class="card-icon">🧙</span>
                <h3>Mis personajes</h3>
                <p>Consulta tus personajes y accede a su gestión.</p>
            </RouterLink>
            <RouterLink class="dashboard-card" to="/campaigns">
                <span class="card-icon">🐉</span>
                <h3>Mis campañas</h3>
                <p>Administra las campañas en las que eres Dungeon Master.</p>
            </RouterLink>
            <RouterLink class="dashboard-card" to="/other-campaigns">
                <span class="card-icon">🛡️</span>
                <h3>Otras campañas</h3>
                <p>Consulta las campañas donde participas como jugador.</p>
            </RouterLink>
            <RouterLink class="dashboard-card" to="/api-2014">
                <span class="card-icon">📚</span>
                <h3>API 2014</h3>
                <p>Explora el catálogo de D&D 5.ª edición de 2014.</p>
            </RouterLink>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { getCurrentUser } from '../services/auth';

const user = ref(null);

onMounted(async () => {
    try {
        user.value = await getCurrentUser();
    } catch {
        // El interceptor global y el guard de rutas gestionarán sesiones caducadas.
    }
});
</script>
