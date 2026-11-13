<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <form class="modal" role="dialog" aria-modal="true" aria-labelledby="item-modal-title" @submit.prevent="save">
            <button class="modal-close" type="button" aria-label="Cerrar" @click="$emit('close')"><AppIcon name="x" /></button>
            <h2 id="item-modal-title">{{ item ? config.label : `Nuevo: ${config.label.toLowerCase()}` }}</h2>
            <p v-if="type === 'encounter'" class="modal-lead">Por ahora los encuentros son siempre de combate.</p>

            <template v-for="field in config.fields" :key="field.key">
                <label :for="`field-${field.key}`">{{ field.label }}</label>
                <textarea v-if="field.type === 'textarea'" :id="`field-${field.key}`" v-model="form[field.key]" rows="5" :maxlength="field.max"></textarea>
                <select v-else-if="field.type === 'select'" :id="`field-${field.key}`" v-model="form[field.key]">
                    <option v-for="(label, value) in field.options" :key="value" :value="value">{{ label }}</option>
                </select>
                <input v-else :id="`field-${field.key}`" v-model="form[field.key]" :type="field.type || 'text'" :required="field.required" :maxlength="field.max" :placeholder="field.placeholder">
                <small v-if="field.type === 'textarea'" class="field-counter">{{ (form[field.key] || '').length }} / {{ field.max }}</small>
            </template>

            <template v-if="type === 'npc'">
                <label for="field-image">Foto (opcional)</label>
                <input id="field-image" type="file" accept="image/jpeg,image/png,image/webp" @change="onImage">
                <small>JPG, PNG o WebP. Máximo 2 MB.</small>
            </template>

            <p v-if="error" class="error-message" role="alert">{{ error }}</p>
            <div class="modal-actions">
                <button class="secondary-button" type="button" @click="$emit('close')">Cancelar</button>
                <button class="primary-button" type="submit" :disabled="saving">{{ saving ? 'Guardando...' : (item ? 'Guardar cambios' : 'Añadir') }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';
import { ITEM_TYPES, formFromItem } from '../services/campaignItems';

const props = defineProps({
    type: { type: String, required: true },
    campaignId: { type: [Number, String], required: true },
    item: { type: Object, default: null },
});
const emit = defineEmits(['close', 'saved']);

const config = computed(() => ITEM_TYPES[props.type]);
const form = ref(props.item ? formFromItem(props.type, props.item) : config.value.blank());
const saving = ref(false);
const imageFile = ref(null);

function onImage(event) {
    const file = event.target.files?.[0] || null;
    if (file && file.size > 2 * 1024 * 1024) {
        error.value = 'La imagen no puede superar los 2 MB.';
        event.target.value = '';
        imageFile.value = null;
        return;
    }
    error.value = '';
    imageFile.value = file;
}
const error = ref('');

async function save() {
    saving.value = true;
    error.value = '';

    const payload = { ...form.value, ...(config.value.fixed || {}) };

    // La API de actualización restablece estos campos si no se envían.
    for (const key of config.value.preserve || []) {
        if (props.item) payload[key] = props.item[key] ?? null;
    }

    try {
        let savedId = props.item?.id;

        if (props.item) {
            await api.put(`/${config.value.endpoint}/${props.item.id}`, payload);
        } else {
            const { data } = await api.post(`/campaigns/${props.campaignId}/${config.value.endpoint}`, payload);
            savedId = data.npc?.id ?? data.id;
        }

        // La foto de un PNJ se sube una vez guardado.
        if (props.type === 'npc' && imageFile.value && savedId) {
            const upload = new FormData();
            upload.append('image', imageFile.value);
            await api.post(`/npcs/${savedId}/image`, upload, { headers: { 'Content-Type': undefined } });
        }

        emit('saved');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ')
            || err.response?.data?.message
            || 'No se pudo guardar.';
    } finally {
        saving.value = false;
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') emit('close');
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
