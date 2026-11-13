<template>
    <main class="character-edit-page">
        <RouterLink class="back-link" :to="`/characters/${route.params.id}`"><AppIcon name="back" /> Volver a la ficha</RouterLink>

        <header class="page-heading">
            <p class="eyebrow">Editar personaje</p>
            <h1>{{ character?.name || 'Personaje' }}</h1>
            <p class="muted">Cambia la historia, las notas, los puntos de golpe y el retrato. La clase, el nivel, la raza y las características se cambian con la subida de nivel.</p>
        </header>

        <p v-if="loading" class="state-message">Cargando personaje...</p>
        <p v-if="error" class="error-message" role="alert">{{ error }}</p>

        <form v-if="character" class="edit-panel" @submit.prevent="save">
            <div class="portrait-editor">
                <div class="character-image-preview">
                    <img v-if="previewUrl" :src="previewUrl" :alt="`Retrato de ${form.name}`">
                    <span v-else aria-hidden="true">{{ form.name?.slice(0, 1)?.toUpperCase() || '?' }}</span>
                </div>
                <div class="portrait-actions">
                    <label for="character-portrait">Retrato</label>
                    <input id="character-portrait" type="file" accept="image/jpeg,image/png,image/webp" @change="selectImage">
                    <small>JPG, PNG o WebP. Máximo 2 MB.</small>
                    <button v-if="selectedImage" class="secondary-button" type="button" @click="clearSelection">Cancelar selección</button>
                </div>
            </div>

            <label for="character-name">Nombre</label>
            <input id="character-name" v-model.trim="form.name" required maxlength="255">

            <label for="character-description">Descripción</label>
            <textarea id="character-description" v-model="form.description" rows="4" maxlength="5000"></textarea>

            <fieldset class="field-group">
                <legend>Puntos de golpe</legend>
                <div class="field-row">
                    <div><label for="hp-current">Actuales</label><input id="hp-current" v-model.number="form.current_hit_points" type="number" min="0" max="9999"></div>
                    <div><label for="hp-max">Máximos</label><input id="hp-max" v-model.number="form.maximum_hit_points" type="number" min="0" max="9999"></div>
                    <div><label for="hp-temp">Temporales</label><input id="hp-temp" v-model.number="form.temporary_hit_points" type="number" min="0" max="9999"></div>
                </div>
                <label class="check-row" for="inspiration"><input id="inspiration" v-model="form.inspiration" type="checkbox"> Tiene inspiración</label>
            </fieldset>

            <label for="trait">Rasgo de personalidad</label>
            <textarea id="trait" v-model="form.personality_trait" rows="3" maxlength="5000"></textarea>
            <label for="ideal">Ideal</label>
            <textarea id="ideal" v-model="form.ideal" rows="3" maxlength="5000"></textarea>
            <label for="bond">Vínculo</label>
            <textarea id="bond" v-model="form.bond" rows="3" maxlength="5000"></textarea>
            <label for="flaw">Defecto</label>
            <textarea id="flaw" v-model="form.flaw" rows="3" maxlength="5000"></textarea>
            <label for="notes">Notas</label>
            <textarea id="notes" v-model="form.notes" rows="5" maxlength="10000"></textarea>

            <p v-if="message" :class="messageIsError ? 'error-message' : 'success-message'" role="alert">{{ message }}</p>
            <div class="form-actions">
                <RouterLink class="secondary-button" :to="`/characters/${route.params.id}`">Cancelar</RouterLink>
                <button class="primary-button" type="submit" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar cambios' }}</button>
            </div>
        </form>
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';

const route = useRoute();
const router = useRouter();
const character = ref(null);
const form = ref({});
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const message = ref('');
const messageIsError = ref(false);
const selectedImage = ref(null);
const localPreview = ref('');
const previewUrl = computed(() => localPreview.value || character.value?.image_url || '');

const EDITABLE = ['name', 'description', 'personality_trait', 'ideal', 'bond', 'flaw', 'notes', 'current_hit_points', 'maximum_hit_points', 'temporary_hit_points', 'inspiration'];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get(`/characters/${route.params.id}`);
        character.value = data;
        form.value = Object.fromEntries(EDITABLE.map((key) => [key, data[key] ?? (key === 'inspiration' ? false : '')]));
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar el personaje.';
    } finally { loading.value = false; }
}

function selectImage(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        message.value = 'Selecciona una imagen JPG, PNG o WebP.'; messageIsError.value = true; event.target.value = ''; return;
    }
    if (file.size > 2 * 1024 * 1024) {
        message.value = 'La imagen no puede superar los 2 MB.'; messageIsError.value = true; event.target.value = ''; return;
    }
    clearSelection();
    selectedImage.value = file;
    localPreview.value = URL.createObjectURL(file);
    message.value = '';
}

function clearSelection() {
    selectedImage.value = null;
    if (localPreview.value) URL.revokeObjectURL(localPreview.value);
    localPreview.value = '';
}

// Los números vacíos viajan como null para no guardar cadenas.
const numberOrNull = (value) => (value === '' || value === undefined ? null : value);

async function save() {
    saving.value = true; message.value = '';
    const payload = { ...form.value };
    for (const key of ['current_hit_points', 'maximum_hit_points', 'temporary_hit_points']) payload[key] = numberOrNull(payload[key]);

    try {
        await api.patch(`/characters/${route.params.id}/details`, payload);
        if (selectedImage.value) {
            const data = new FormData();
            data.append('image', selectedImage.value);
            await api.post(`/characters/${route.params.id}/image`, data, { headers: { 'Content-Type': undefined } });
        }
        await router.push(`/characters/${route.params.id}`);
    } catch (err) {
        message.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudieron guardar los cambios.';
        messageIsError.value = true;
    } finally { saving.value = false; }
}

onMounted(load);
onBeforeUnmount(clearSelection);
</script>
