
<template>
    <section class="characters-page">
        <header class="page-header">
            <div>
                <h1>Mis personajes</h1>
                <p>Consulta y gestiona los personajes que has creado.</p>
            </div>

            <RouterLink class="create-button" to="/characters/create">
                Crear personaje
            </RouterLink>
        </header>

        <p v-if="loading">Cargando tus personajes...</p>

        <div v-else-if="error" class="error-message">
            <p>{{ error }}</p>
            <button type="button" @click="loadCharacters">
                Reintentar
            </button>
        </div>

        <template v-else>
            <div v-if="characters.length === 0" class="empty-message">
                Todavía no has creado ningún personaje.
                ¡Crea tu primer aventurero!
            </div>

            <div v-else class="characters-grid">
                <article
                    v-for="character in characters"
                    :key="character.id"
                    class="character-card"
                >
                    <div class="character-portrait">
                        <img
                            v-if="character.image_url"
                            :src="character.image_url"
                            :alt="`Retrato de ${character.name}`"
                            loading="lazy"
                        />

                        <div
                            v-else
                            class="character-portrait-placeholder"
                            aria-hidden="true"
                        >
                            🧙
                        </div>
                    </div>

                    <div class="character-card-content">
                        <h2>{{ character.name }}</h2>

                        <p>
                            <strong>Raza:</strong>
                            {{ character.race || 'Sin especificar' }}
                        </p>

                        <p>
                            <strong>Nivel:</strong>
                            {{ character.level }}
                        </p>

                        <p>
                            <strong>Campaña:</strong>
                            {{ character.campaign?.name || 'Sin campaña' }}
                        </p>

                        <p
                            v-if="character.description"
                            class="character-description"
                        >
                            {{ character.description }}
                        </p>
                    </div>
                </article>
            </div>
        </template>

        <RouterLink class="back-link" to="/dashboard">
            Volver al dashboard
        </RouterLink>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';

const characters = ref([]);
const loading = ref(true);
const error = ref('');

async function loadCharacters() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/characters');
        characters.value = response.data;
    } catch (err) {
        error.value =
            err.response?.data?.message ||
            'No se pudieron cargar tus personajes.';
    } finally {
        loading.value = false;
    }
}

onMounted(loadCharacters);
</script>
