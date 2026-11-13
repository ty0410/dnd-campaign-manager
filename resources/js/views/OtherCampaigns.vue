<template>
    <main class="other-campaigns-page">
        <header class="page-header page-header--split">
            <h1>Otras campañas</h1>
            <p class="list-count" aria-live="polite">{{ campaigns.length }} {{ campaigns.length === 1 ? 'campaña' : 'campañas' }}</p>
        </header>

        <div class="oc-layout">
            <div class="oc-main">
                <div v-if="loading" class="skeleton-grid" aria-busy="true" aria-label="Cargando campañas">
                    <div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>
                </div>
                <p v-else-if="error" class="error-message" role="alert">{{ error }}</p>

                <template v-else-if="campaigns.length">
                    <div class="list-toolbar">
                        <div class="search-field">
                            <AppIcon name="search" />
                            <input v-model.trim="search" type="search" placeholder="Buscar campaña, Dungeon Master o personaje..." aria-label="Buscar campañas">
                        </div>
                        <p class="list-count" aria-live="polite">{{ filtered.length }} de {{ campaigns.length }}</p>
                    </div>

                    <div v-if="filtered.length" class="oc-grid stagger">
                        <article v-for="campaign in filtered" :key="campaign.id" class="campaign-card campaign-card--tall" :style="campaign.image_url ? { '--cover': `url('${campaign.image_url}')` } : {}">
                            <div class="campaign-card-content">
                                <p v-if="campaign.master" class="campaign-master"><UserAvatar :user="campaign.master" size="sm" /> {{ campaign.master.username ? `@${campaign.master.username}` : campaign.master.name }}</p>
                                <h2>{{ campaign.name }}</h2>
                                <p class="campaign-description">{{ campaign.description || 'Sin descripción.' }}</p>
                                <ul v-if="campaign.characters?.length" class="campaign-characters">
                                    <li v-for="character in campaign.characters" :key="character.id" class="hero-chip">{{ character.name }} · {{ character.level }}</li>
                                </ul>
                                <div class="campaign-card-actions">
                                    <RouterLink class="primary-button" :to="`/campaigns/${campaign.id}`">Abrir campaña</RouterLink>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div v-else class="empty-state">
                        <h2>Sin resultados</h2>
                        <button class="secondary-button" type="button" @click="search = ''">Limpiar búsqueda</button>
                    </div>
                </template>

                <div v-else class="empty-state">
                    <div class="empty-icon" aria-hidden="true">✦</div>
                    <h2>Aún no juegas en campañas de otros</h2>
                    <p>Cuando un Dungeon Master apruebe a uno de tus personajes, aparecerá aquí.</p>
                </div>
            </div>

            <aside class="oc-side">
                <section class="join-panel" aria-labelledby="join-heading">
                    <h2 id="join-heading">Unirse con código</h2>
                    <form class="join-form" @submit.prevent="submitJoin">
                        <input v-model.trim="joinCode" class="code-input" maxlength="12" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="CÓDIGO" aria-label="Código de la campaña" required>
                        <select v-model="joinCharacterId" aria-label="Personaje" required>
                            <option value="" disabled>{{ myCharacters.length ? 'Elige un personaje' : 'No tienes personajes' }}</option>
                            <option v-for="character in myCharacters" :key="character.id" :value="character.id">{{ character.name }} · nivel {{ character.level }}</option>
                        </select>
                        <button class="primary-button" type="submit" :disabled="sending || !joinCode || !joinCharacterId">{{ sending ? 'Enviando...' : 'Enviar solicitud' }}</button>
                    </form>
                    <p v-if="joinMessage" :class="joinIsError ? 'error-message' : 'success-message'" role="alert">{{ joinMessage }}</p>
                    <p v-if="!myCharacters.length && !loading" class="muted"><RouterLink to="/characters/create">Crea un personaje</RouterLink> para poder unirte.</p>
                </section>

                <section v-if="pending.length" class="join-panel" aria-label="Solicitudes enviadas">
                    <h2>Pendientes</h2>
                    <ul class="request-list">
                        <li v-for="entry in pending" :key="entry.id" class="request-row">
                            <div class="request-copy">
                                <strong>{{ entry.character?.name }}</strong>
                                <small>→ {{ entry.campaign?.name }}</small>
                            </div>
                            <div class="request-actions"><button class="danger-button" type="button" @click="cancel(entry)">Cancelar</button></div>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';
import UserAvatar from '../components/UserAvatar.vue';

const campaigns = ref([]);
const myCharacters = ref([]);
const pending = ref([]);
const loading = ref(true);
const error = ref('');
const search = ref('');

const joinCode = ref('');
const joinCharacterId = ref('');
const sending = ref(false);
const joinMessage = ref('');
const joinIsError = ref(false);

// Búsqueda sin distinguir mayúsculas ni acentos.
const normalize = (text) => String(text ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
const filtered = computed(() => {
    const term = normalize(search.value);
    if (!term) return campaigns.value;
    return campaigns.value.filter((campaign) => normalize(`${campaign.name} ${campaign.description} ${campaign.master?.name} ${campaign.master?.username} ${(campaign.characters || []).map((c) => c.name).join(' ')}`).includes(term));
});

async function loadAll() {
    loading.value = true; error.value = '';
    try {
        const [joined, characters, requests] = await Promise.all([
            api.get('/campaigns/joined'), api.get('/characters'), api.get('/campaign-requests/mine'),
        ]);
        campaigns.value = joined.data;
        myCharacters.value = characters.data;
        pending.value = requests.data.requests;
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudieron cargar las campañas.';
    } finally { loading.value = false; }
}

async function submitJoin() {
    sending.value = true; joinMessage.value = '';
    try {
        const { data } = await api.post('/campaign-join', { code: joinCode.value, character_id: joinCharacterId.value });
        joinIsError.value = false; joinMessage.value = data.message;
        joinCode.value = ''; joinCharacterId.value = '';
        await loadAll();
    } catch (err) {
        joinIsError.value = true;
        joinMessage.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo enviar la solicitud.';
    } finally { sending.value = false; }
}

async function cancel(entry) {
    try { await api.delete(`/campaign-requests/${entry.id}`); await loadAll(); }
    catch (err) { joinIsError.value = true; joinMessage.value = err.response?.data?.message || 'No se pudo cancelar.'; }
}

onMounted(loadAll);
</script>
