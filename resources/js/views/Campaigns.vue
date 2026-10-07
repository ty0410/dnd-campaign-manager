<template>
    <main>
        <h1>Mis campañas</h1>

        <p v-if="loading">Cargando campañas...</p>

        <p v-if="error">
            {{ error }}
        </p>

        <p v-if="!loading && !campaigns.length && !error">
            No tienes campañas.
        </p>

        <section v-if="campaigns.length">
            <article v-for="campaign in campaigns" :key="campaign.id">
                <h2>
                    {{ campaign.name }}
                </h2>

                <p v-if="campaign.description">
                    {{ campaign.description }}
                </p>

                <p v-else>Esta campaña no tiene descripción.</p>

                <RouterLink :to="`/campaigns/${campaign.id}`">
                    Ver campaña
                </RouterLink>
            </article>
        </section>
    </main>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "../services/api";
import { RouterLink } from "vue-router";
const campaigns = ref([]);
const loading = ref(true);
const error = ref("");

const loadCampaigns = async () => {
    try {
        const response = await api.get("/campaigns");

        campaigns.value = response.data;
    } catch (err) {
        if (err.response) {
            error.value =
                err.response.data.message ||
                "No se pudieron cargar las campañas.";
        } else {
            error.value = "No se pudo conectar con el servidor.";
        }
    } finally {
        loading.value = false;
    }
};


onMounted(() => {
    loadCampaigns();
});
</script>
