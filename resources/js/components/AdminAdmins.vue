<template>
    <div class="admin-section admin-admins">
        <section class="admin-card">
            <h2>Crear administrador</h2>
            <p class="muted">Las cuentas de administrador solo se pueden crear desde aquí, desde la consola o desde la base de datos. El registro público crea siempre usuarios normales.</p>
            <form @submit.prevent="create">
                <label for="admin-name">Nombre</label>
                <input id="admin-name" v-model.trim="form.name" required maxlength="255" autocomplete="off">
                <label for="admin-username">Nombre de usuario público</label>
                <input id="admin-username" v-model.trim="form.username" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_]+" autocomplete="off">
                <label for="admin-email">Correo electrónico</label>
                <input id="admin-email" v-model.trim="form.email" type="email" required maxlength="255" autocomplete="off">
                <label for="admin-password">Contraseña (mínimo 8 caracteres)</label>
                <input id="admin-password" v-model="form.password" type="password" required minlength="8" autocomplete="new-password">
                <label for="admin-password2">Repite la contraseña</label>
                <input id="admin-password2" v-model="form.password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                <p v-if="message" :class="messageIsError ? 'error-message' : 'success-message'" role="alert">{{ message }}</p>
                <button class="primary-button" type="submit" :disabled="saving">{{ saving ? 'Creando...' : 'Crear administrador' }}</button>
            </form>
        </section>

        <section class="admin-card">
            <h2>Administradores</h2>
            <p v-if="loading" class="empty-hint">Cargando...</p>
            <p v-if="error" class="error-message" role="alert">{{ error }}</p>
            <ul v-if="admins.length" class="admin-detail-list admin-list">
                <li v-for="admin in admins" :key="admin.id"><strong>{{ admin.name }}</strong><small> · @{{ admin.username }} · {{ admin.email }}</small></li>
            </ul>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../services/api';

const admins = ref([]);
const loading = ref(true);
const error = ref('');
const saving = ref(false);
const message = ref('');
const messageIsError = ref(false);
const form = ref({ name: '', username: '', email: '', password: '', password_confirmation: '' });

async function load() {
    loading.value = true; error.value = '';
    try {
        const { data } = await api.get('/admin/admins');
        admins.value = data;
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar la lista.';
    } finally { loading.value = false; }
}

async function create() {
    saving.value = true; message.value = ''; messageIsError.value = false;
    try {
        const { data } = await api.post('/admin/admins', form.value);
        message.value = `${data.message} (${data.user.email})`;
        form.value = { name: '', username: '', email: '', password: '', password_confirmation: '' };
        await load();
    } catch (err) {
        messageIsError.value = true;
        message.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo crear la cuenta.';
    } finally { saving.value = false; }
}

onMounted(load);
</script>
