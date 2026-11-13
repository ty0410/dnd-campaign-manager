<template>
    <main class="campaign-edit-page">
        <RouterLink class="back-link" :to="`/campaigns/${route.params.id}`"><AppIcon name="back" /> Volver a la campaña</RouterLink>
        <header class="page-heading"><p class="eyebrow">Configuración</p><h1>Editar campaña</h1><p class="muted">Modifica la información de tu aventura y todo lo que contiene.</p></header>
        <p v-if="loading" class="state-message">Cargando campaña...</p>
        <p v-if="error" class="error-message">{{ error }}</p>
        <form v-if="!loading && campaign" class="edit-panel" @submit.prevent="saveCampaign">
            <div class="cover-preview" :style="previewUrl ? { backgroundImage: `linear-gradient(0deg,rgba(11,10,17,.8),rgba(11,10,17,.05)),url('${previewUrl}')` } : {}">
                <span v-if="!previewUrl">Vista previa de portada</span>
                <strong>{{ form.name || campaign.name }}</strong>
            </div>
            <label for="campaign-cover">Imagen de portada</label>
            <input id="campaign-cover" type="file" accept="image/jpeg,image/png,image/webp" @change="selectImage">
            <small>JPG, PNG o WebP. Tamaño máximo: 5 MB. Se utilizará como fondo de la tarjeta de campaña.</small>
            <div class="image-actions" v-if="selectedImage || campaign.image_url">
                <button v-if="selectedImage" class="secondary-button" type="button" @click="clearSelection">Cancelar selección</button>
                <button v-if="campaign.image_url && !selectedImage" class="danger-button" type="button" @click="removeImage">Eliminar imagen actual</button>
            </div>
            <label for="campaign-name">Nombre de la campaña</label>
            <input id="campaign-name" v-model.trim="form.name" maxlength="255" required>
            <label for="campaign-description">Descripción</label>
            <textarea id="campaign-description" v-model="form.description" rows="6"></textarea>
            <p v-if="message" :class="messageIsError ? 'error-message' : 'success-message'">{{ message }}</p>
            <div class="form-actions"><RouterLink class="secondary-button" :to="`/campaigns/${route.params.id}`">Cancelar</RouterLink><button class="primary-button" type="submit" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar cambios' }}</button></div>
        </form>

        <CampaignContentManager v-if="!loading && campaign" :campaign-id="route.params.id" />
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';
import CampaignContentManager from '../components/CampaignContentManager.vue';

const route = useRoute();
const router = useRouter();
const campaign = ref(null);
const form = ref({ name: '', description: '' });
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const message = ref('');
const messageIsError = ref(false);
const selectedImage = ref(null);
const localPreview = ref('');
const previewUrl = computed(() => localPreview.value || campaign.value?.image_url || '');

async function loadCampaign() {
    loading.value = true;
    try {
        const response = await api.get(`/campaigns/${route.params.id}`);
        campaign.value = response.data;
        form.value = { name: response.data.name, description: response.data.description || '' };
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar la campaña.';
    } finally { loading.value = false; }
}

function selectImage(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        message.value = 'Selecciona una imagen JPG, PNG o WebP.'; messageIsError.value = true; event.target.value = ''; return;
    }
    if (file.size > 5 * 1024 * 1024) {
        message.value = 'La imagen no puede superar los 5 MB.'; messageIsError.value = true; event.target.value = ''; return;
    }
    selectedImage.value = file;
    localPreview.value = URL.createObjectURL(file);
    message.value = '';
}

function clearSelection() {
    selectedImage.value = null;
    if (localPreview.value) URL.revokeObjectURL(localPreview.value);
    localPreview.value = '';
}

async function saveCampaign() {
    saving.value = true; message.value = '';
    try {
        await api.put(`/campaigns/${route.params.id}`, { name: form.value.name, description: form.value.description || null });
        if (selectedImage.value) {
            const data = new FormData(); data.append('image', selectedImage.value);
            await api.post(`/campaigns/${route.params.id}/image`, data, { headers: { 'Content-Type': undefined } });
        }
        await router.push(`/campaigns/${route.params.id}`);
    } catch (err) {
        message.value = err.response?.data?.message || Object.values(err.response?.data?.errors || {}).flat().join(' ') || 'No se pudieron guardar los cambios.';
        messageIsError.value = true;
    } finally { saving.value = false; }
}

async function removeImage() {
    if (!window.confirm('¿Quieres eliminar la imagen de portada de esta campaña?')) return;
    try {
        const response = await api.delete(`/campaigns/${route.params.id}/image`);
        campaign.value = response.data.campaign;
        message.value = response.data.message; messageIsError.value = false;
    } catch (err) { message.value = err.response?.data?.message || 'No se pudo eliminar la imagen.'; messageIsError.value = true; }
}

onMounted(loadCampaign);
</script>
