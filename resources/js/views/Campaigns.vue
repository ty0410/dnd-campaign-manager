<template>
    <main class="campaigns-page">
        <header class="page-header page-header--split">
            <div>
                <h1>Mis campañas</h1>
            </div>
            <button class="primary-button" type="button" @click="showCreate = true">
                <AppIcon name="plus" /> Nueva campaña
            </button>
        </header>

        <div v-if="loading" class="skeleton-grid" aria-busy="true" aria-label="Cargando campañas">
            <div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>
        </div>
        <div v-else-if="error" class="error-message" role="alert">
            {{ error }}
            <button type="button" class="secondary-button" @click="loadCampaigns">Reintentar</button>
        </div>
        <template v-else-if="campaigns.length">
            <div class="list-toolbar">
                <div class="search-field">
                    <AppIcon name="search" />
                    <input v-model.trim="search" type="search" placeholder="Buscar por nombre, descripción o Dungeon Master..." aria-label="Buscar campañas">
                </div>
                <p class="list-count" aria-live="polite">{{ filteredCampaigns.length }} de {{ campaigns.length }}</p>
            </div>

            <div v-if="filteredCampaigns.length" class="campaign-grid stagger">
            <article v-for="campaign in filteredCampaigns" :key="campaign.id" class="campaign-card"
                :style="campaign.image_url ? { '--cover': `url('${campaign.image_url}')` } : {}">
                <div class="campaign-card-content">
                    <p v-if="campaign.master" class="campaign-kicker">Dirigida por {{ campaign.master.name }}</p>
                    <h2>{{ campaign.name }}</h2>
                    <p class="campaign-description">{{ campaign.description || 'Sin descripción todavía.' }}</p>
                    <div class="campaign-card-actions">
                        <RouterLink class="primary-button" :to="`/campaigns/${campaign.id}`">Abrir campaña</RouterLink>
                        <RouterLink class="ghost-button" :to="`/campaigns/${campaign.id}/edit`">Editar</RouterLink>
                        <button class="ghost-button danger-button" type="button" @click="deleteCampaign(campaign)">Eliminar</button>
                    </div>
                </div>
            </article>
            </div>
            <div v-else class="empty-state">
                <h2>Sin resultados</h2>
                <p>Ninguna campaña coincide con «{{ search }}».</p>
                <button class="secondary-button" type="button" @click="search = ''">Limpiar búsqueda</button>
            </div>
        </template>
        <div v-else class="empty-state">
            <div class="empty-icon" aria-hidden="true">✦</div>
            <h2>Aún no tienes campañas</h2>
            <p>Crea tu primera aventura y empieza a preparar tu mundo.</p>
            <button class="primary-button" type="button" @click="showCreate = true">Crear primera campaña</button>
        </div>

        <Transition name="modal">
            <div v-if="showCreate" class="modal-backdrop" @click.self="closeCreate">
                <form class="modal" role="dialog" aria-modal="true" aria-labelledby="create-title" @submit.prevent="createCampaign">
                    <button class="modal-close" type="button" aria-label="Cerrar" @click="closeCreate"><AppIcon name="x" /></button>
                    <h2 id="create-title">Crear campaña</h2>
                    <label for="campaign-name">Nombre</label>
                    <input id="campaign-name" v-model.trim="newCampaign.name" maxlength="255" required placeholder="Ej. La Mina Perdida de Phandelver">
                    <label for="campaign-description">Descripción</label>
                    <textarea id="campaign-description" v-model="newCampaign.description" rows="4" placeholder="Describe brevemente la aventura..."></textarea>
                    <label for="campaign-image">Imagen de portada (opcional)</label>
                    <input id="campaign-image" type="file" accept="image/jpeg,image/png,image/webp" @change="onCoverSelected">
                    <small>Se verá de fondo detrás del título de la campaña. JPG, PNG o WebP, máximo 2 MB.</small>
                    <div v-if="coverPreview" class="cover-preview cover-preview--small" :style="{ backgroundImage: `linear-gradient(0deg,rgba(11,10,17,.8),rgba(11,10,17,.05)),url('${coverPreview}')` }">{{ newCampaign.name || 'Tu campaña' }}</div>
                    <p v-if="formMessage" class="error-message" role="alert">{{ formMessage }}</p>
                    <div class="modal-actions">
                        <button class="secondary-button" type="button" @click="closeCreate">Cancelar</button>
                        <button class="primary-button" type="submit" :disabled="creating">{{ creating ? 'Creando...' : 'Crear campaña' }}</button>
                    </div>
                </form>
            </div>
        </Transition>
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';

const campaigns = ref([]);
const loading = ref(true);
const error = ref('');
const search = ref('');

// Búsqueda sin distinguir mayúsculas ni acentos.
const normalize = (text) => String(text ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
const filteredCampaigns = computed(() => {
    const term = normalize(search.value);
    if (!term) return campaigns.value;
    return campaigns.value.filter((campaign) => normalize(`${campaign.name} ${campaign.description} ${campaign.master?.name}`).includes(term));
});

const showCreate = ref(false);
const creating = ref(false);
const formMessage = ref('');
const newCampaign = ref({ name: '', description: '' });

async function loadCampaigns() {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/campaigns');
        campaigns.value = response.data;
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudieron cargar las campañas.';
    } finally {
        loading.value = false;
    }
}

const coverFile = ref(null);
const coverPreview = ref('');

function onCoverSelected(event) {
    const file = event.target.files?.[0] || null;
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverFile.value = null;
    coverPreview.value = '';

    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        formMessage.value = 'La imagen no puede superar los 2 MB.';
        event.target.value = '';
        return;
    }

    formMessage.value = '';
    coverFile.value = file;
    coverPreview.value = URL.createObjectURL(file);
}

function closeCreate() {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverFile.value = null;
    coverPreview.value = '';
    showCreate.value = false;
    formMessage.value = '';
    newCampaign.value = { name: '', description: '' };
}

async function createCampaign() {
    creating.value = true;
    formMessage.value = '';
    try {
        const { data } = await api.post('/campaigns', {
            name: newCampaign.value.name,
            description: newCampaign.value.description || null,
        });

        // La portada se sube una vez creada la campaña.
        const created = data.campaign ?? data;
        if (coverFile.value && created?.id) {
            const upload = new FormData();
            upload.append('image', coverFile.value);
            try {
                await api.post(`/campaigns/${created.id}/image`, upload, { headers: { 'Content-Type': undefined } });
            } catch {
                // La campaña ya existe: la portada se puede subir después desde «Editar».
            }
        }

        closeCreate();
        await loadCampaigns();
    } catch (err) {
        formMessage.value = err.response?.data?.message || Object.values(err.response?.data?.errors || {}).flat().join(' ') || 'No se pudo crear la campaña.';
    } finally {
        creating.value = false;
    }
}

async function deleteCampaign(campaign) {
    if (!window.confirm(`¿Seguro que quieres eliminar «${campaign.name}»? Esta acción no se puede deshacer.`)) return;
    try {
        await api.delete(`/campaigns/${campaign.id}`);
        await loadCampaigns();
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo eliminar la campaña.';
    }
}

function onKeydown(event) { if (event.key === 'Escape' && showCreate.value) closeCreate(); }

onMounted(() => { window.addEventListener('keydown', onKeydown); loadCampaigns(); });
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
