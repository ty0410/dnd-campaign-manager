<template>
    <div class="app-shell">
        <header class="app-header">
            <RouterLink class="brand" to="/">Gestor de Tasha para todo</RouterLink>
            <nav v-if="isAuthenticated" class="main-nav" aria-label="Menú principal">
                <RouterLink to="/characters">Mis personajes</RouterLink>
                <RouterLink to="/campaigns">Mis campañas</RouterLink>
                <RouterLink to="/other-campaigns">Otras campañas</RouterLink>
                <RouterLink to="/api-2014">API 2014</RouterLink>
                <RouterLink to="/friends">Amigos</RouterLink>
                <RouterLink v-if="isAdmin" to="/admin" class="admin-link">Administración</RouterLink>
                <RouterLink v-if="currentUser" to="/profile" class="nav-profile" :aria-label="`Mi perfil (${currentUser.username || currentUser.name})`" title="Mi perfil">
                    <UserAvatar :user="currentUser" size="sm" />
                </RouterLink>
                <button type="button" class="secondary-button logout-button" @click="handleLogout">Cerrar sesión</button>
            </nav>
            <nav v-else class="main-nav" aria-label="Menú de acceso">
                <RouterLink to="/login">Iniciar sesión</RouterLink>
                <RouterLink to="/register">Registrarse</RouterLink>
            </nav>
        </header>
        <main class="page-content">
            <RouterView v-slot="{ Component, route: currentRoute }">
                <Transition name="page" mode="out-in">
                    <component :is="Component" :key="currentRoute.path" />
                </Transition>
            </RouterView>
        </main>
        <DiceRoller />
        <DiceTray v-if="isAuthenticated" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import DiceRoller from './components/DiceRoller.vue';
import DiceTray from './components/DiceTray.vue';
import UserAvatar from './components/UserAvatar.vue';
import { getToken, logout, getCurrentUser, removeToken, isAdminUser } from './services/auth';

const router = useRouter();
const isAuthenticated = ref(!!getToken());

const currentUser = ref(null);
const isAdmin = computed(() => isAuthenticated.value && isAdminUser(currentUser.value));

const updateAuthentication = async () => {
    isAuthenticated.value = !!getToken();

    if (!isAuthenticated.value) {
        currentUser.value = null;
        return;
    }

    try {
        currentUser.value = await getCurrentUser();
    } catch {
        currentUser.value = null;
    }
};

onMounted(async () => {
    window.addEventListener('auth-changed', updateAuthentication);
    if (isAuthenticated.value) {
        try {
            currentUser.value = await getCurrentUser();
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
