<template>
    <nav v-if="lastPage > 1" class="admin-pager" aria-label="Paginación">
        <button class="secondary-button" type="button" :disabled="page <= 1" @click="$emit('go', page - 1)">Anterior</button>
        <span>Página {{ page }} de {{ lastPage }}</span>
        <button class="secondary-button" type="button" :disabled="page >= lastPage" @click="$emit('go', page + 1)">Siguiente</button>
    </nav>
</template>

<script setup>
defineProps({
    page: { type: Number, required: true },
    lastPage: { type: Number, required: true },
});
defineEmits(['go']);
</script>
