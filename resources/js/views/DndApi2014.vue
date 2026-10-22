<template>
    <section>
        <h2>API 2014</h2>
        <p>Catálogo de referencia de Dungeons & Dragons 5.ª edición.</p>
        <div class="catalog-grid">
            <article v-for="category in categories" :key="category.title" class="catalog-card">
                <h3>{{ category.title }}</h3>
                <p>{{ category.description }}</p>
                <span class="catalog-status">Disponible para conectar</span>
            </article>
        </div>
        <p class="notice">El proyecto ya dispone de endpoints para varias categorías. En el siguiente paso los conectaremos a listados seleccionables y fichas completas de cada elemento.</p>
        <RouterLink to="/dashboard">Volver al dashboard</RouterLink>
    </section>
</template>

<script setup>
import { RouterLink } from 'vue-router';

const categories = [
    { title: 'Razas', description: 'Razas y subrazas jugables.' },
    { title: 'Clases', description: 'Clases de personaje y sus detalles.' },
    { title: 'Conjuros', description: 'Conjuros, niveles y descripciones.' },
    { title: 'Monstruos', description: 'Criaturas, estadísticas y capacidades.' },
    { title: 'Equipo', description: 'Armas, armaduras y objetos.' },
    { title: 'Trasfondos', description: 'Trasfondos y características asociadas.' },
];
</script>
