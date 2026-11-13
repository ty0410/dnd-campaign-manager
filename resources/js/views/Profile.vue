<template>
    <main class="profile-page">
        <header class="page-header">
            <h1>Mi perfil</h1>
        </header>

        <p v-if="loading" class="state-message">Cargando perfil...</p>

        <form v-if="user" class="edit-panel" @submit.prevent="save">
            <div class="portrait-editor">
                <UserAvatar :user="{ ...user, avatar_url: previewUrl }" size="xl" />
                <div class="portrait-actions">
                    <label for="avatar">Foto de perfil</label>
                    <input id="avatar" type="file" accept="image/jpeg,image/png,image/webp" @change="selectImage">
                    <small>JPG, PNG o WebP. Máximo 2 MB.</small>
                    <div class="image-actions">
                        <button v-if="selectedImage" class="secondary-button" type="button" @click="clearSelection">Cancelar selección</button>
                        <button v-if="user.avatar_url && !selectedImage" class="danger-button" type="button" @click="removeAvatar">Quitar foto</button>
                    </div>
                </div>
            </div>

            <label for="username">Nombre de usuario público</label>
            <div class="username-field"><span aria-hidden="true">@</span><input id="username" v-model.trim="form.username" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_]+" autocomplete="off"></div>
            <small>Entre 3 y 30 caracteres: letras, números y guion bajo.</small>

            <label for="name">Nombre (privado)</label>
            <input id="name" v-model.trim="form.name" required maxlength="255">

            <p v-if="user.role === 'admin'" class="info-message">Tienes una cuenta de <strong>administrador</strong>.</p>

            <p v-if="message" :class="messageIsError ? 'error-message' : 'success-message'" role="alert">{{ message }}</p>
            <div class="form-actions">
                <RouterLink class="secondary-button" :to="`/users/${user.username}`">Ver mi perfil público</RouterLink>
                <button class="primary-button" type="submit" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar cambios' }}</button>
            </div>
        </form>
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import { getCurrentUser } from '../services/auth';
import UserAvatar from '../components/UserAvatar.vue';

const user = ref(null);
const form = ref({ username: '', name: '' });
const loading = ref(true);
const saving = ref(false);
const message = ref('');
const messageIsError = ref(false);
const selectedImage = ref(null);
const localPreview = ref('');
const previewUrl = computed(() => localPreview.value || user.value?.avatar_url || null);

async function load() {
    try {
        user.value = await getCurrentUser();
        form.value = { username: user.value.username || '', name: user.value.name || '' };
    } catch {
        message.value = 'No se pudo cargar el perfil.'; messageIsError.value = true;
    } finally { loading.value = false; }
}

function selectImage(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { fail('Selecciona una imagen JPG, PNG o WebP.'); event.target.value = ''; return; }
    if (file.size > 2 * 1024 * 1024) { fail('La imagen no puede superar los 2 MB.'); event.target.value = ''; return; }
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

function fail(text) { message.value = text; messageIsError.value = true; }

async function save() {
    saving.value = true; message.value = '';
    try {
        await api.patch('/profile', form.value);
        if (selectedImage.value) {
            const data = new FormData();
            data.append('avatar', selectedImage.value);
            await api.post('/profile/avatar', data, { headers: { 'Content-Type': undefined } });
        }
        clearSelection();
        await load();
        window.dispatchEvent(new Event('auth-changed')); // refresca la foto del menú
        messageIsError.value = false; message.value = 'Perfil actualizado.';
    } catch (err) {
        fail(Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo guardar el perfil.');
    } finally { saving.value = false; }
}

async function removeAvatar() {
    try {
        await api.delete('/profile/avatar');
        await load();
        window.dispatchEvent(new Event('auth-changed'));
    } catch (err) { fail(err.response?.data?.message || 'No se pudo quitar la foto.'); }
}

onMounted(load);
onBeforeUnmount(clearSelection);
</script>
