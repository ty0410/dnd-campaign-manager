<template>
    <section>
        <h2>Otras campañas</h2>
        <p>Aquí aparecerán las campañas en las que participas como jugador, separadas de las que diriges como Dungeon Master.</p>
        <p class="notice">En el siguiente paso conectaremos esta vista al backend y aplicaremos permisos de solo lectura para la campaña, permitiendo editar únicamente tu propio personaje.</p>
        <RouterLink to="/dashboard">Volver al dashboard</RouterLink>
    </section>
</template>

<script setup>
import { RouterLink } from 'vue-router';
</script>
