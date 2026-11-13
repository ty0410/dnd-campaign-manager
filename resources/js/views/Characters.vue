<template>
    <section class="characters-page">
        <header class="page-header page-header--split">
            <div>
                <h1>Mis personajes</h1>
            </div>

            <RouterLink class="primary-button" to="/characters/create">
                <AppIcon name="plus" /> Crear personaje
            </RouterLink>
        </header>

        <p v-if="flash" class="success-message" role="status">{{ flash }}</p>
        <p v-if="actionError" class="error-message" role="alert">{{ actionError }}</p>

        <section v-if="invitations.length || sentRequests.length" class="requests-panel" aria-label="Solicitudes de campaña">
            <h2>Solicitudes de campaña</h2>

            <ul class="request-list">
                <li v-for="invitation in invitations" :key="`i-${invitation.id}`" class="request-row">
                    <div class="request-copy">
                        <strong>{{ invitation.campaign?.master?.name || 'Un Dungeon Master' }} invita a «{{ invitation.character?.name }}»</strong>
                        <small>a la campaña «{{ invitation.campaign?.name }}». El personaje solo entrará si aceptas.</small>
                    </div>
                    <div class="request-actions">
                        <button class="primary-button" type="button" :disabled="busyRequest === invitation.id" @click="respond(invitation, 'approve')">Aceptar</button>
                        <button class="secondary-button" type="button" :disabled="busyRequest === invitation.id" @click="respond(invitation, 'reject')">Rechazar</button>
                    </div>
                </li>

                <li v-for="request in sentRequests" :key="`r-${request.id}`" class="request-row">
                    <div class="request-copy">
                        <strong>«{{ request.character?.name }}» espera respuesta</strong>
                        <small>El Dungeon Master de «{{ request.campaign?.name }}» debe aprobar la solicitud.</small>
                    </div>
                    <div class="request-actions">
                        <button class="danger-button" type="button" :disabled="busyRequest === request.id" @click="cancelRequest(request)">Cancelar</button>
                    </div>
                </li>
            </ul>
        </section>

        <div v-if="loading" class="skeleton-grid" aria-busy="true" aria-label="Cargando tus personajes">
            <div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>
        </div>

        <div v-else-if="error" class="error-message" role="alert">
            <p>{{ error }}</p>
            <button type="button" @click="loadCharacters">Reintentar</button>
        </div>

        <template v-else>
            <div v-if="characters.length === 0" class="empty-state">
                Todavía no has creado ningún personaje.
                ¡Crea tu primer aventurero!
            </div>

            <template v-else>
            <div class="list-toolbar">
                <div class="search-field">
                    <AppIcon name="search" />
                    <input v-model.trim="search" type="search" placeholder="Buscar por nombre, raza o campaña..." aria-label="Buscar personajes">
                </div>
                <p class="list-count" aria-live="polite">{{ filteredCharacters.length }} de {{ characters.length }}</p>
            </div>

            <div v-if="filteredCharacters.length" class="characters-grid stagger">
                <article v-for="character in filteredCharacters" :key="character.id" class="character-card character-card-link">
                    <RouterLink :to="{ name: 'character-detail', params: { id: character.id } }" class="character-card-main">
                        <div class="character-portrait">
                            <img v-if="character.image_url" :src="character.image_url" :alt="`Retrato de ${character.name}`" loading="lazy" />
                            <div v-else class="character-portrait-placeholder" aria-hidden="true">
                                {{ character.name?.slice(0, 1)?.toUpperCase() || '?' }}
                            </div>
                        </div>

                        <div class="character-card-content">
                            <h2>{{ character.name }}</h2>
                            <p><strong>Raza:</strong> {{ character.race || 'Sin especificar' }}</p>
                            <p><strong>Nivel:</strong> {{ character.level }}</p>
                            <p><strong>Campaña:</strong> {{ character.campaigns?.length ? character.campaigns.map(campaign => campaign.name).join(', ') : 'Sin campaña' }}</p>
                            <p v-if="character.description" class="character-description">{{ character.description }}</p>
                        </div>
                    </RouterLink>

                </article>
            </div>
            <div v-else class="empty-state">
                <h2>Sin resultados</h2>
                <p>Ningún personaje coincide con «{{ search }}».</p>
                <button class="secondary-button" type="button" @click="search = ''">Limpiar búsqueda</button>
            </div>
            </template>
        </template>


        <RouterLink class="back-link" to="/dashboard">
            <AppIcon name="back" /> Volver al dashboard
        </RouterLink>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';

const characters = ref([]);
const search = ref('');

// Búsqueda sin distinguir mayúsculas ni acentos.
const normalize = (text) => String(text ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
const filteredCharacters = computed(() => {
    const term = normalize(search.value);
    if (!term) return characters.value;
    return characters.value.filter((character) => normalize(`${character.name} ${character.race} ${character.description} ${(character.campaigns || []).map((campaign) => campaign.name).join(' ')}`).includes(term));
});

const loading = ref(true);
const error = ref('');
const invitations = ref([]);
const sentRequests = ref([]);
const busyRequest = ref(null);
const flash = ref('');
const actionError = ref('');


async function loadCharacters() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/characters');
        characters.value = response.data;
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudieron cargar tus personajes.';
    } finally {
        loading.value = false;
    }
}

async function loadRequests() {
    try {
        const { data } = await api.get('/campaign-requests/mine');
        invitations.value = data.invitations;
        sentRequests.value = data.requests;
    } catch {
        // Las solicitudes son secundarias: si fallan no bloquean el listado.
    }
}

async function respond(entry, action) {
    busyRequest.value = entry.id; actionError.value = ''; flash.value = '';
    try {
        const { data } = await api.post(`/campaign-requests/${entry.id}/${action}`);
        flash.value = data.message;
        await Promise.all([loadRequests(), loadCharacters()]);
    } catch (err) {
        actionError.value = err.response?.data?.message || 'No se pudo responder a la solicitud.';
    } finally { busyRequest.value = null; }
}

async function cancelRequest(entry) {
    busyRequest.value = entry.id; actionError.value = ''; flash.value = '';
    try {
        const { data } = await api.delete(`/campaign-requests/${entry.id}`);
        flash.value = data.message;
        await loadRequests();
    } catch (err) {
        actionError.value = err.response?.data?.message || 'No se pudo cancelar la solicitud.';
    } finally { busyRequest.value = null; }
}

onMounted(() => {
    loadCharacters();
    loadRequests();
});
</script>
