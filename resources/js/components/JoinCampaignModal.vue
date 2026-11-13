<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <form class="modal" role="dialog" aria-modal="true" aria-labelledby="join-title" @submit.prevent="submit">
            <button class="modal-close" type="button" aria-label="Cerrar" @click="$emit('close')"><AppIcon name="x" /></button>
            <h2 id="join-title">Unirse a una campaña</h2>
            <p class="modal-lead">{{ characterName }}</p>
            <label for="join-code">Código de la campaña</label>
            <input id="join-code" ref="input" v-model.trim="code" class="code-input" maxlength="12" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="ABCD2345" required>
            <p v-if="error" class="error-message" role="alert">{{ error }}</p>
            <div class="modal-actions">
                <button class="secondary-button" type="button" @click="$emit('close')">Cancelar</button>
                <button class="primary-button" type="submit" :disabled="sending || !code">{{ sending ? 'Enviando...' : 'Enviar solicitud' }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    characterId: { type: [Number, String], required: true },
    characterName: { type: String, default: '' },
});
const emit = defineEmits(['close', 'sent']);

const code = ref('');
const sending = ref(false);
const error = ref('');
const input = ref(null);

async function submit() {
    sending.value = true; error.value = '';
    try {
        const { data } = await api.post('/campaign-join', { code: code.value, character_id: props.characterId });
        emit('sent', data.message);
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo enviar la solicitud.';
    } finally { sending.value = false; }
}

function onKeydown(event) { if (event.key === 'Escape') emit('close'); }
onMounted(() => { window.addEventListener('keydown', onKeydown); input.value?.focus(); });
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
