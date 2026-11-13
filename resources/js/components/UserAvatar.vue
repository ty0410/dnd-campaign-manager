<template>
    <span class="user-avatar" :class="`user-avatar--${size}`" :aria-hidden="decorative ? 'true' : null">
        <img v-if="user?.avatar_url" :src="user.avatar_url" :alt="decorative ? '' : `Foto de ${user.username || 'usuario'}`" loading="lazy">
        <template v-else>{{ initial }}</template>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
    size: { type: String, default: 'md' },
    decorative: { type: Boolean, default: true },
});

const initial = computed(() => (props.user?.username || props.user?.name || '?').slice(0, 1).toUpperCase());
</script>
