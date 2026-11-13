<template>
    <main class="admin-page">
        <header class="page-header">
            <h1>Administración</h1>
        </header>

        <div class="tab-bar" role="tablist" aria-label="Secciones de administración">
            <button v-for="entry in tabs" :key="entry.key" type="button" role="tab" class="tab" :class="{ 'is-active': tab === entry.key }" :aria-selected="tab === entry.key" @click="setTab(entry.key)">{{ entry.label }}</button>
        </div>

        <AdminUsers v-if="tab === 'users'" />
        <AdminCharacters v-else-if="tab === 'characters'" />
        <AdminCampaigns v-else-if="tab === 'campaigns'" />
        <AdminAdmins v-else />
    </main>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminUsers from '../components/AdminUsers.vue';
import AdminCharacters from '../components/AdminCharacters.vue';
import AdminCampaigns from '../components/AdminCampaigns.vue';
import AdminAdmins from '../components/AdminAdmins.vue';

const tabs = [
    { key: 'users', label: 'Usuarios' },
    { key: 'characters', label: 'Personajes' },
    { key: 'campaigns', label: 'Campañas' },
    { key: 'admins', label: 'Administradores' },
];

const route = useRoute();
const router = useRouter();

// La pestaña activa viaja en la URL (?tab=...) para poder enlazarla desde el dashboard.
const initial = tabs.some((entry) => entry.key === route.query.tab) ? route.query.tab : 'users';
const tab = ref(initial);

function setTab(key) {
    tab.value = key;
    router.replace({ query: { ...route.query, tab: key } });
}
</script>
