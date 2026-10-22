<template>
    <div class="app-shell">
        <header class="app-header">
            <RouterLink class="brand" to="/">Gestor de Tasha para todo</RouterLink>
            <nav v-if="isAuthenticated" class="main-nav" aria-label="Menú principal">
                <RouterLink to="/characters">Mis personajes</RouterLink>
                <RouterLink to="/campaigns">Mis campañas</RouterLink>
                <RouterLink to="/other-campaigns">Otras campañas</RouterLink>
                <RouterLink to="/api-2014">API 2014</RouterLink>
                <button type="button" class="logout-button" @click="handleLogout">Cerrar sesión</button>
            </nav>
            <nav v-else class="main-nav" aria-label="Menú de acceso">
                <RouterLink to="/login">Iniciar sesión</RouterLink>
                <RouterLink to="/register">Registrarse</RouterLink>
            </nav>
        </header>
        <main class="page-content">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { getToken, logout, getCurrentUser, removeToken } from './services/auth';

const router = useRouter();
const isAuthenticated = ref(!!getToken());

const updateAuthentication = () => {
    isAuthenticated.value = !!getToken();
};

onMounted(async () => {
    window.addEventListener('auth-changed', updateAuthentication);
    if (isAuthenticated.value) {
        try {
            await getCurrentUser();
        } catch {
            removeToken();
            updateAuthentication();
            router.replace('/login');
        }
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('auth-changed', updateAuthentication);
});

const handleLogout = async () => {
    try {
        await logout();
    } finally {
        updateAuthentication();
        window.dispatchEvent(new Event('auth-changed'));
        router.push('/login');
    }
};
</script>

<style>
:root { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #242424; background: #f5f5f7; }
* { box-sizing: border-box; }
body { margin: 0; }
a { color: #5940a8; }
.app-shell { min-height: 100vh; }
.app-header { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap; padding: 1rem clamp(1rem, 4vw, 3rem); background: #211b32; color: white; }
.brand { color: white; text-decoration: none; font-weight: 750; font-size: 1.15rem; }
.main-nav { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.main-nav a { color: #eee9ff; text-decoration: none; }
.main-nav a.router-link-active { color: white; text-decoration: underline; text-underline-offset: .35rem; }
.logout-button { border: 1px solid #a89bc8; border-radius: .5rem; background: transparent; color: white; padding: .55rem .8rem; cursor: pointer; }
.page-content { width: min(1120px, calc(100% - 2rem)); margin: 2rem auto; }
.dashboard-grid, .catalog-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
.dashboard-card, .catalog-card { display: block; padding: 1.25rem; background: white; border: 1px solid #e5e1ee; border-radius: .9rem; color: inherit; text-decoration: none; box-shadow: 0 4px 16px #24163b0a; }
.dashboard-card:hover { border-color: #8d79c5; transform: translateY(-2px); }
.dashboard-card h3, .catalog-card h3 { margin: .5rem 0; }
.dashboard-card p, .catalog-card p { color: #666; line-height: 1.5; }
.card-icon { font-size: 1.8rem; }
.home-page { padding: 3rem 0; }
.home-actions { display: flex; gap: 1rem; }
.notice { margin: 1rem 0; padding: 1rem; border-left: 4px solid #8d79c5; background: #fff; line-height: 1.5; }
.catalog-status { font-size: .85rem; color: #6d5b99; }
@media (max-width: 700px) { .app-header { align-items: flex-start; flex-direction: column; } .main-nav { gap: .75rem; } }
</style>
