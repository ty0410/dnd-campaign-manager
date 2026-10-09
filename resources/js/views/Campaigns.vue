<template>
    <main>
        <h1>Mis campañas</h1>

        <section>
            <h2>Crear campaña</h2>

            <form @submit.prevent="createCampaign">
                <div>
                    <label for="name">Nombre de la campaña</label>

                    <input
                        id="name"
                        v-model="newCampaign.name"
                        type="text"
                        placeholder="Ej. La Mina Perdida de Phandelver"
                        required
                        maxlength="255"
                    />
                </div>

                <div>
                    <label for="description">Descripción</label>

                    <textarea
                        id="description"
                        v-model="newCampaign.description"
                        placeholder="Describe brevemente la aventura..."
                        rows="4"
                    ></textarea>
                </div>

                <button type="submit" :disabled="creating">
                    {{ creating ? "Creando..." : "Crear campaña" }}
                </button>
            </form>

            <p v-if="formMessage">
                {{ formMessage }}
            </p>
        </section>

        <hr />

        <section>
            <h2>Mis campañas</h2>

            <p v-if="loading">Cargando campañas...</p>

            <p v-if="error">
                {{ error }}
            </p>

            <p v-if="!loading && !campaigns.length && !error">
                No tienes campañas.
            </p>

            <article
    v-for="campaign in campaigns"
    :key="campaign.id"
>
    <template v-if="editingCampaignId !== campaign.id">
        <h3>{{ campaign.name }}</h3>

        <p v-if="campaign.description">
            {{ campaign.description }}
        </p>

        <p v-else>
            Esta campaña no tiene descripción.
        </p>

        <div class="campaign-actions">
            <RouterLink :to="`/campaigns/${campaign.id}`">
                Ver campaña
            </RouterLink>

            <button type="button" @click="startEditing(campaign)">
                Editar
            </button>

            <button
    type="button"
    @click="deleteCampaign(campaign)"
>
    Eliminar
</button>
        </div>
    </template>

    <form v-else @submit.prevent="updateCampaign">
        <h3>Editar campaña</h3>

        <div>
            <label :for="`edit-name-${campaign.id}`">
                Nombre
            </label>

            <input
                :id="`edit-name-${campaign.id}`"
                v-model="editForm.name"
                type="text"
                maxlength="255"
                required
            >
        </div>

        <div>
            <label :for="`edit-description-${campaign.id}`">
                Descripción
            </label>

            <textarea
                :id="`edit-description-${campaign.id}`"
                v-model="editForm.description"
                rows="4"
            ></textarea>
        </div>

        <button type="submit" :disabled="savingEdit">
            {{ savingEdit ? "Guardando..." : "Guardar cambios" }}
        </button>

        <button type="button" @click="cancelEditing">
            Cancelar
        </button>
    </form>
</article>
        </section>
    </main>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import api from "../services/api";

const campaigns = ref([]);
const loading = ref(true);
const error = ref("");

const creating = ref(false);
const formMessage = ref("");

const newCampaign = ref({
    name: "",
    description: "",
});

const loadCampaigns = async () => {
    loading.value = true;
    error.value = "";

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

const createCampaign = async () => {
    creating.value = true;
    formMessage.value = "";
    error.value = "";

    try {
        const response = await api.post("/campaigns", {
            name: newCampaign.value.name,
            description: newCampaign.value.description || null,
        });

        formMessage.value =
            response.data.message || "Campaña creada correctamente.";

        newCampaign.value = {
            name: "",
            description: "",
        };

        await loadCampaigns();
    } catch (err) {
        if (err.response?.data?.errors) {
            formMessage.value = Object.values(err.response.data.errors)
                .flat()
                .join(" ");
        } else if (err.response) {
            formMessage.value =
                err.response.data.message || "No se pudo crear la campaña.";
        } else {
            formMessage.value = "No se pudo conectar con el servidor.";
        }
    } finally {
        creating.value = false;
    }
};

onMounted(() => {
    loadCampaigns();
});

const editingCampaignId = ref(null);
const savingEdit = ref(false);

const editForm = ref({
    name: "",
    description: "",
});

const startEditing = (campaign) => {
    editingCampaignId.value = campaign.id;

    editForm.value = {
        name: campaign.name,
        description: campaign.description || "",
    };

    formMessage.value = "";
    error.value = "";
};

const cancelEditing = () => {
    editingCampaignId.value = null;

    editForm.value = {
        name: "",
        description: "",
    };
};

const updateCampaign = async () => {
    if (editingCampaignId.value === null) {
        return;
    }

    savingEdit.value = true;
    formMessage.value = "";

    try {
        const response = await api.put(
            `/campaigns/${editingCampaignId.value}`,
            {
                name: editForm.value.name,
                description: editForm.value.description || null,
            }
        );

        formMessage.value =
            response.data.message || "Campaña actualizada correctamente.";

        cancelEditing();
        await loadCampaigns();
    } catch (err) {
        if (err.response?.data?.errors) {
            formMessage.value = Object.values(
                err.response.data.errors
            )
                .flat()
                .join(" ");
        } else if (err.response) {
            formMessage.value =
                err.response.data.message ||
                "No se pudo actualizar la campaña.";
        } else {
            formMessage.value =
                "No se pudo conectar con el servidor.";
        }
    } finally {
        savingEdit.value = false;
    }
};

const deleteCampaign = async (campaign) => {
    const confirmed = window.confirm(
        `¿Seguro que quieres eliminar la campaña "${campaign.name}"? Esta acción no se puede deshacer.`
    );

    if (!confirmed) {
        return;
    }

    formMessage.value = "";
    error.value = "";

    try {
        await api.delete(`/campaigns/${campaign.id}`);

        formMessage.value = "Campaña eliminada correctamente.";

        if (editingCampaignId.value === campaign.id) {
            cancelEditing();
        }

        await loadCampaigns();
    } catch (err) {
        if (err.response) {
            formMessage.value =
                err.response.data.message ||
                "No se pudo eliminar la campaña.";
        } else {
            formMessage.value =
                "No se pudo conectar con el servidor.";
        }
    }
};
</script>
