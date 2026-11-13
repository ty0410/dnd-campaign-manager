<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <form class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="info-title" @submit.prevent="save">
            <button class="modal-close" type="button" aria-label="Cerrar" @click="$emit('close')"><AppIcon name="x" /></button>
            <h2 id="info-title">Datos de la campaña</h2>

            <div class="cover-preview" :style="previewUrl ? { backgroundImage: `linear-gradient(0deg,rgba(11,10,17,.8),rgba(11,10,17,.05)),url('${previewUrl}')` } : {}">
                {{ form.name || 'Tu campaña' }}
            </div>
            <div class="image-actions">
                <label class="secondary-button file-button">
                    {{ previewUrl ? 'Cambiar portada' : 'Subir portada' }}
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="visually-hidden" @change="selectImage">
                </label>
                <button v-if="campaign.image_url && !selectedImage" class="danger-button" type="button" @click="removeImage">Quitar portada</button>
                <button v-if="selectedImage" class="secondary-button" type="button" @click="clearSelection">Cancelar selección</button>
            </div>

            <label for="info-name">Nombre</label>
            <input id="info-name" v-model.trim="form.name" maxlength="255" required>
            <label for="info-description">Descripción</label>
            <textarea id="info-description" v-model="form.description" rows="5"></textarea>

            <p v-if="error" class="error-message" role="alert">{{ error }}</p>
            <div class="modal-actions">
                <button class="secondary-button" type="button" @click="$emit('close')">Cancelar</button>
                <button class="primary-button" type="submit" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';

const props = defineProps({ campaign: { type: Object, required: true } });
const emit = defineEmits(['close', 'saved']);

const form = ref({ name: props.campaign.name, description: props.campaign.description || '' });
const selectedImage = ref(null);
const localPreview = ref('');
const removed = ref(false);
const saving = ref(false);
const error = ref('');

const previewUrl = computed(() => localPreview.value || (removed.value ? '' : props.campaign.image_url) || '');

function selectImage(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) { error.value = 'La imagen no puede superar los 2 MB.'; return; }
    clearSelection();
    error.value = '';
    selectedImage.value = file;
    localPreview.value = URL.createObjectURL(file);
}

function clearSelection() {
    selectedImage.value = null;
    if (localPreview.value) URL.revokeObjectURL(localPreview.value);
    localPreview.value = '';
}

async function removeImage() {
    error.value = '';
    try {
        await api.delete(`/campaigns/${props.campaign.id}/image`);
        removed.value = true;
        emit('saved', { keepOpen: true });
    } catch (err) { error.value = err.response?.data?.message || 'No se pudo quitar la portada.'; }
}

async function save() {
    saving.value = true; error.value = '';
    try {
        await api.put(`/campaigns/${props.campaign.id}`, { name: form.value.name, description: form.value.description || null });
        if (selectedImage.value) {
            const data = new FormData();
            data.append('image', selectedImage.value);
            await api.post(`/campaigns/${props.campaign.id}/image`, data, { headers: { 'Content-Type': undefined } });
        }
        emit('saved');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo guardar.';
    } finally { saving.value = false; }
}

function onKeydown(event) { if (event.key === 'Escape') emit('close'); }
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => { window.removeEventListener('keydown', onKeydown); clearSelection(); });
</script>
