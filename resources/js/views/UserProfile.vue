<template>
    <main class="user-profile-page">
        <RouterLink class="back-link" to="/friends"><AppIcon name="back" /> Amigos</RouterLink>

        <p v-if="loading" class="state-message">Cargando perfil...</p>
        <p v-if="error" class="error-message" role="alert">{{ error }}</p>

        <section v-if="profile" class="profile-card">
            <UserAvatar :user="profile" size="xl" />
            <div class="profile-card-copy">
                <h1>@{{ profile.username }}</h1>
                <p v-if="profile.is_admin"><span class="tag tag--warn">Administrador</span></p>
                <p class="muted">Miembro desde {{ since }}</p>

                <p v-if="message" :class="isError ? 'error-message' : 'success-message'" role="status">{{ message }}</p>

                <div class="profile-actions">
                    <RouterLink v-if="profile.relation === 'self'" class="secondary-button" to="/profile">Editar mi perfil</RouterLink>
                    <button v-else-if="profile.can_request" class="primary-button" type="button" :disabled="busy" @click="sendRequest">Enviar solicitud de amistad</button>
                    <span v-else-if="profile.relation === 'friends'" class="tag tag--ok">Sois amigos</span>
                    <span v-else-if="profile.relation === 'pending_sent'" class="tag tag--warn">Solicitud enviada</span>
                    <template v-else-if="profile.relation === 'pending_received'">
                        <button class="primary-button" type="button" :disabled="busy" @click="respond('accept')">Aceptar solicitud</button>
                        <button class="secondary-button" type="button" :disabled="busy" @click="respond('reject')">Rechazar</button>
                    </template>
                    <p v-else class="muted">No puedes enviar solicitudes de amistad a esta cuenta.</p>
                </div>
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';
import UserAvatar from '../components/UserAvatar.vue';

const route = useRoute();
const profile = ref(null);
const loading = ref(true);
const error = ref('');
const busy = ref(false);
const message = ref('');
const isError = ref(false);

const since = computed(() => profile.value
    ? new Intl.DateTimeFormat('es-ES', { month: 'long', year: 'numeric' }).format(new Date(profile.value.member_since))
    : '');

async function load() {
    loading.value = true; error.value = ''; profile.value = null;
    try {
        const { data } = await api.get(`/users/${route.params.username}`);
        profile.value = data;
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar el perfil.';
    } finally { loading.value = false; }
}

async function sendRequest() {
    busy.value = true; message.value = '';
    try {
        const { data } = await api.post('/friend-requests', { user_id: profile.value.id });
        isError.value = false; message.value = data.message;
        await load();
    } catch (err) {
        isError.value = true; message.value = err.response?.data?.message || 'No se pudo enviar la solicitud.';
    } finally { busy.value = false; }
}

async function respond(action) {
    busy.value = true; message.value = '';
    try {
        const { data } = await api.post(`/friend-requests/${profile.value.friendship_id}/${action}`);
        isError.value = false; message.value = data.message;
        await load();
    } catch (err) {
        isError.value = true; message.value = err.response?.data?.message || 'No se pudo responder.';
    } finally { busy.value = false; }
}

watch(() => route.params.username, load);
onMounted(load);
</script>
