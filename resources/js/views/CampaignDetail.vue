<template>
    <main>
        <p v-if="loading">Cargando campaña...</p>

        <p v-if="error">
            {{ error }}
        </p>

        <section v-if="campaign">
            <h1>{{ campaign.name }}</h1>

            <p v-if="campaign.description">
                {{ campaign.description }}
            </p>

            <p v-else>Esta campaña no tiene descripción.</p>

            <p>ID de campaña: {{ campaign.id }}</p>

            <h2>Personajes</h2>

            <ul>
                <li v-for="character in characters" :key="character.id">
                    {{ character.name }}
                </li>
            </ul>

            <p v-if="!characters.length">Esta campaña no tiene personajes.</p>
        <h2>NPCs</h2>

<ul>
    <li
        v-for="npc in npcs"
        :key="npc.id"
    >
        {{ npc.name }}
    </li>
</ul>

<p v-if="!npcs.length">
    Esta campaña no tiene NPCs.
</p>

<h2>Lugares</h2>

<ul>
    <li
        v-for="location in locations"
        :key="location.id"
    >
        {{ location.name }}
    </li>
</ul>

<p v-if="!locations.length">
    Esta campaña no tiene lugares.
</p>

<h2>Sesiones</h2>

<ul>
    <li
        v-for="session in sessions"
        :key="session.id"
    >
        {{ session.title }}
    </li>
</ul>

<p v-if="!sessions.length">
    Esta campaña no tiene sesiones.
</p>
        </section>

        <RouterLink to="/campaigns"> Volver a mis campañas </RouterLink>
    </main>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import api from "../services/api";

const route = useRoute();

const campaign = ref(null);
const loading = ref(true);
const error = ref("");
const characters = ref([]);
const npcs = ref([]);
const locations = ref([]);
const sessions = ref([]);

const loadCampaign = async () => {
    try {
        const response = await api.get(`/campaigns/${route.params.id}`);
        // Cargamos los personajes de la campaña
        campaign.value = response.data;

        const charactersResponse = await api.get(
            `/campaigns/${route.params.id}/characters`,
        );

        characters.value = charactersResponse.data;
        
        // Cargamos los NPCs, ubicaciones y sesiones de la campaña
        const npcsResponse = await api.get(
            `/campaigns/${route.params.id}/npcs`,
        );

        npcs.value = npcsResponse.data;

        const locationsResponse = await api.get(
            `/campaigns/${route.params.id}/locations`,
        );

        locations.value = locationsResponse.data;

        const sessionsResponse = await api.get(
            `/campaigns/${route.params.id}/sessions`,
        );

        sessions.value = sessionsResponse.data;
    } catch (err) {
        if (err.response) {
            error.value =
                err.response.data.message || "No se pudo cargar la campaña.";
        } else {
            error.value = "No se pudo conectar con el servidor.";
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadCampaign();
});
</script>
