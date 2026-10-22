<template>
    <main class="home-page">
        <h1>Gestor de Tasha para todo</h1>
        <p>Tu espacio para crear personajes y gestionar campañas de Dungeons & Dragons 5.ª edición.</p>
        <div class="home-actions">
            <RouterLink v-if="!authenticated" to="/login">Iniciar sesión</RouterLink>
            <RouterLink v-if="!authenticated" to="/register">Crear cuenta</RouterLink>
            <RouterLink v-else to="/dashboard">Ir al dashboard</RouterLink>
        </div>
    </main>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { getToken } from '../services/auth';

const authenticated = computed(() => !!getToken());
</script>
