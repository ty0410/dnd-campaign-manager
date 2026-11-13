<template>
    <main class="friends-page">
        <header class="page-header">
            <h1>Amigos</h1>
        </header>

        <p v-if="message" :class="isError ? 'error-message' : 'success-message'" role="status">{{ message }}</p>

        <section class="friends-block" aria-labelledby="search-heading">
            <h2 id="search-heading">Buscar usuarios</h2>
            <div class="search-field">
                <AppIcon name="search" />
                <input v-model.trim="query" type="search" placeholder="Nombre de usuario (mínimo 2 letras)..." aria-label="Buscar usuarios" @input="onSearchInput">
            </div>
            <p v-if="searching" class="empty-hint">Buscando...</p>
            <ul v-if="results.length" class="friend-list">
                <li v-for="user in results" :key="user.id" class="friend-row">
                    <RouterLink class="friend-main" :to="`/users/${user.username}`"><UserAvatar :user="user" /><span><strong>@{{ user.username }}</strong><small v-if="user.is_admin">Administrador</small></span></RouterLink>
                    <span v-if="user.relation === 'friends'" class="tag tag--ok">Amigos</span>
                    <span v-else-if="user.relation === 'pending_sent'" class="tag tag--warn">Enviada</span>
                    <span v-else-if="user.relation === 'pending_received'" class="tag tag--warn">Te ha escrito</span>
                    <button v-else class="secondary-button" type="button" :disabled="busyId === user.id" @click="sendRequest(user)">Añadir</button>
                </li>
            </ul>
            <p v-else-if="query.length >= 2 && !searching" class="empty-hint">No se ha encontrado a nadie con ese nombre.</p>
        </section>

        <section v-if="incoming.length" class="friends-block" aria-labelledby="incoming-heading">
            <h2 id="incoming-heading">Solicitudes recibidas</h2>
            <ul class="friend-list">
                <li v-for="entry in incoming" :key="entry.id" class="friend-row">
                    <RouterLink class="friend-main" :to="`/users/${entry.user.username}`"><UserAvatar :user="entry.user" /><span><strong>@{{ entry.user.username }}</strong><small v-if="entry.user.is_admin">Administrador</small></span></RouterLink>
                    <div class="request-actions">
                        <button class="primary-button" type="button" @click="respond(entry, 'accept')">Aceptar</button>
                        <button class="secondary-button" type="button" @click="respond(entry, 'reject')">Rechazar</button>
                    </div>
                </li>
            </ul>
        </section>

        <section v-if="outgoing.length" class="friends-block" aria-labelledby="outgoing-heading">
            <h2 id="outgoing-heading">Solicitudes enviadas</h2>
            <ul class="friend-list">
                <li v-for="entry in outgoing" :key="entry.id" class="friend-row">
                    <RouterLink class="friend-main" :to="`/users/${entry.user.username}`"><UserAvatar :user="entry.user" /><span><strong>@{{ entry.user.username }}</strong></span></RouterLink>
                    <button class="danger-button" type="button" @click="cancel(entry)">Cancelar</button>
                </li>
            </ul>
        </section>

        <section class="friends-block" aria-labelledby="friends-heading">
            <h2 id="friends-heading">Mis amigos</h2>
            <p v-if="loading" class="empty-hint">Cargando...</p>
            <ul v-else-if="friends.length" class="friend-list">
                <li v-for="entry in friends" :key="entry.friendship_id" class="friend-row">
                    <RouterLink class="friend-main" :to="`/users/${entry.user.username}`"><UserAvatar :user="entry.user" /><span><strong>@{{ entry.user.username }}</strong><small v-if="entry.user.is_admin">Administrador</small></span></RouterLink>
                    <button class="danger-button" type="button" @click="removeFriend(entry)">Eliminar</button>
                </li>
            </ul>
            <p v-else class="empty-hint">Todavía no tienes amigos añadidos.</p>
        </section>
    </main>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';
import UserAvatar from '../components/UserAvatar.vue';

const friends = ref([]);
const incoming = ref([]);
const outgoing = ref([]);
const loading = ref(true);
const message = ref('');
const isError = ref(false);

const query = ref('');
const results = ref([]);
const searching = ref(false);
const busyId = ref(null);

let timer = null;
let requestId = 0;

async function load() {
    try {
        const { data } = await api.get('/friends');
        friends.value = data.friends; incoming.value = data.incoming; outgoing.value = data.outgoing;
    } catch (err) { notify(err.response?.data?.message || 'No se pudo cargar la lista.', true); }
    finally { loading.value = false; }
}

function notify(text, error = false) { message.value = text; isError.value = error; }

function onSearchInput() {
    clearTimeout(timer);
    if (query.value.length < 2) { results.value = []; return; }
    timer = setTimeout(search, 300);
}

async function search() {
    const current = ++requestId;
    searching.value = true;
    try {
        const { data } = await api.get('/friends/search', { params: { q: query.value } });
        if (current === requestId) results.value = data;
    } catch (err) { if (current === requestId) notify(err.response?.data?.message || 'No se pudo buscar.', true); }
    finally { if (current === requestId) searching.value = false; }
}

async function run(action, errorText) {
    try { const { data } = await action(); notify(data.message); await load(); if (query.value.length >= 2) await search(); }
    catch (err) { notify(err.response?.data?.message || errorText, true); }
}

async function sendRequest(user) {
    busyId.value = user.id;
    await run(() => api.post('/friend-requests', { user_id: user.id }), 'No se pudo enviar la solicitud.');
    busyId.value = null;
}
const respond = (entry, action) => run(() => api.post(`/friend-requests/${entry.id}/${action}`), 'No se pudo responder.');
const cancel = (entry) => run(() => api.delete(`/friend-requests/${entry.id}`), 'No se pudo cancelar.');
const removeFriend = (entry) => window.confirm(`¿Eliminar a @${entry.user.username} de tus amigos?`)
    ? run(() => api.delete(`/friends/${entry.user.id}`), 'No se pudo eliminar.')
    : null;

onMounted(load);
onBeforeUnmount(() => clearTimeout(timer));
</script>
