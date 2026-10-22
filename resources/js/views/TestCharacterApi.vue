
<script setup>
import { ref } from 'vue';
import api from '../services/api';

const resultado = ref(null);
const error = ref('');
const cargando = ref(false);

async function consultarPersonaje() {
    cargando.value = true;
    resultado.value = null;
    error.value = '';

    try {
        const respuesta = await api.get('/characters/2');
        resultado.value = respuesta.data;
    } catch (e) {
        error.value =
            e.response?.data?.message ??
            `Error al consultar la API: ${e.message}`;
    } finally {
        cargando.value = false;
    }
}
</script>

<template>
    <main class="pagina-prueba">
        <h1>Prueba de la API de personajes</h1>

        <button
            type="button"
            @click="consultarPersonaje"
            :disabled="cargando"
        >
            {{ cargando ? 'Consultando...' : 'Consultar personaje 2' }}
        </button>

        <p v-if="error" class="error">
            {{ error }}
        </p>

        <section v-if="resultado">
            <h2>Respuesta de Laravel</h2>
            <pre>{{ JSON.stringify(resultado, null, 2) }}</pre>
        </section>
    </main>
</template>

<style scoped>
.pagina-prueba {
    max-width: 1000px;
    margin: 40px auto;
    padding: 20px;
    color: #eee;
}

button {
    padding: 10px 16px;
    cursor: pointer;
}

pre {
    padding: 16px;
    overflow-x: auto;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    background: #222;
    border-radius: 8px;
}

.error {
    color: #ff7777;
}
</style>
