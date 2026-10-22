<template>
    <main>
        <p v-if="loading">Cargando campaña...</p>

        <p v-if="error" class="error-message">
            {{ error }}
        </p>

        <template v-if="campaign">
            <section class="campaign-header">
                <h1>{{ campaign.name }}</h1>

                <p v-if="campaign.description">
                    {{ campaign.description }}
                </p>
                <p v-else>Esta campaña no tiene descripción.</p>

                <p class="campaign-id">ID de campaña: {{ campaign.id }}</p>
            </section>

            <section class="campaign-section">
                <h2>Personajes</h2>

                <form class="character-form" @submit.prevent="createCharacter">
                    <h3>Crear personaje</h3>

                    <div>
                        <label for="character-name">Nombre del personaje</label>
                        <input
                            id="character-name"
                            v-model="newCharacter.name"
                            type="text"
                            maxlength="255"
                            placeholder="Ej. Arthas"
                            required
                        />
                    </div>

                    <div>
                        <label for="character-race">Raza</label>
                        <select
                            id="character-race"
                            v-model="newCharacter.race_index"
                            @change="onRaceChange"
                            required
                        >
                            <option value="">Selecciona una raza</option>
                            <option
                                v-for="race in races"
                                :key="race.index"
                                :value="race.index"
                            >
                                {{ race.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="character-subrace">Subraza</label>
                        <select
                            id="character-subrace"
                            v-model="newCharacter.subrace_index"
                            :disabled="
                                !newCharacter.race_index || loadingSubraces
                            "
                        >
                            <option value="">Sin subraza</option>
                            <option
                                v-for="subrace in availableSubraces"
                                :key="subrace.index"
                                :value="subrace.index"
                            >
                                {{ subrace.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="character-background">Trasfondo</label>
                        <select
                            id="character-background"
                            v-model="newCharacter.background_index"
                        >
                            <option value="">Selecciona un trasfondo</option>
                            <option
                                v-for="background in backgrounds"
                                :key="background.index"
                                :value="background.index"
                            >
                                {{ background.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="personality_trait">
                            Rasgo de personalidad
                        </label>
                        <textarea
                            id="personality_trait"
                            v-model="newCharacter.personality_trait"
                            rows="2"
                            placeholder="¿Cómo se comporta tu personaje?"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="ideal">Ideal</label>
                        <textarea
                            id="ideal"
                            v-model="newCharacter.ideal"
                            rows="2"
                            placeholder="¿Qué principio guía a tu personaje?"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="bond">Vínculo</label>
                        <textarea
                            id="bond"
                            v-model="newCharacter.bond"
                            rows="2"
                            placeholder="¿Qué persona, lugar u objeto es importante?"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="flaw">Defecto</label>
                        <textarea
                            id="flaw"
                            v-model="newCharacter.flaw"
                            rows="2"
                            placeholder="¿Qué debilidad tiene tu personaje?"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notas adicionales</label>
                        <textarea
                            id="notes"
                            v-model="newCharacter.notes"
                            rows="4"
                            placeholder="Añade información adicional sobre tu personaje..."
                        ></textarea>
                    </div>

                    <div>
                        <label for="character-level">Nivel</label>
                        <input
                            id="character-level"
                            v-model.number="newCharacter.level"
                            type="number"
                            min="1"
                            max="20"
                            required
                        />
                    </div>

                    <div class="ability-score-section">
                        <h4>Características</h4>

                        <label for="ability-score-method">
                            Método de asignación
                        </label>

                        <select
                            id="ability-score-method"
                            v-model="newCharacter.ability_score_method"
                        >
                            <option value="manual">Asignación manual</option>
                            <option value="standard_array">
                                Array estándar
                            </option>
                            <option value="point_buy">Compra de puntos</option>
                        </select>

                        <p
                            v-if="
                                newCharacter.ability_score_method === 'manual'
                            "
                        >
                            Introduce cada puntuación entre 1 y 20.
                        </p>

                        <p
                            v-else-if="
                                newCharacter.ability_score_method ===
                                'standard_array'
                            "
                        >
                            Puedes modificar las seis puntuaciones entre 1 y
                            20. Los valores iniciales son 15, 14, 13, 12, 10 y
                            8.
                        </p>

                        <template
                            v-else-if="
                                newCharacter.ability_score_method ===
                                'point_buy'
                            "
                        >
                            <p>
                                Asigna puntuaciones entre 1 y 15. Los valores
                                entre 1 y 8 cuestan 0 puntos. El máximo es de
                                27 puntos.
                            </p>

                            <p>
                                Puntos utilizados:
                                <strong>
                                    {{
                                        pointBuyTotal === null
                                            ? "—"
                                            : pointBuyTotal
                                    }}
                                    / 27
                                </strong>
                            </p>

                            <p
                                v-if="
                                    pointBuyTotal !== null &&
                                    pointBuyTotal > 27
                                "
                                class="error-message"
                            >
                                Has superado el límite de puntos.
                            </p>
                        </template>

                        <div class="ability-score-grid">
                            <div
                                v-for="ability in abilities"
                                :key="ability.key"
                                class="ability-score-card"
                            >
                                <label :for="`score-${ability.key}`">
                                    {{ ability.label }}
                                </label>

                                <input
                                    :id="`score-${ability.key}`"
                                    v-model.number="newCharacter[ability.key]"
                                    type="number"
                                    :min="1"
                                    :max="
                                        newCharacter.ability_score_method ===
                                        'point_buy'
                                            ? 15
                                            : 20
                                    "
                                    required
                                />

                                <label :for="`bonus-${ability.key}`">
                                    Bono personalizado
                                </label>

                                <input
                                    :id="`bonus-${ability.key}`"
                                    v-model.number="
                                        newCharacter[ability.bonus]
                                    "
                                    type="number"
                                    min="-5"
                                    max="5"
                                    required
                                />

                                <p>
                                    Puntuación efectiva:
                                    {{
                                        newCharacter[ability.key] === null ||
                                        newCharacter[ability.key] === ""
                                            ? "—"
                                            : Number(
                                                  newCharacter[ability.key],
                                              ) +
                                              Number(
                                                  newCharacter[ability.bonus] ||
                                                      0,
                                              )
                                    }}
                                </p>

                                <p>
                                    Modificador:
                                    {{
                                        abilityModifier(
                                            newCharacter[ability.key],
                                            newCharacter[ability.bonus],
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="character-description">Descripción</label>
                        <textarea
                            id="character-description"
                            v-model="newCharacter.description"
                            rows="3"
                            placeholder="Historia, personalidad y trasfondo..."
                        ></textarea>
                    </div>

                    <button type="submit" :disabled="creatingCharacter">
                        {{
                            creatingCharacter
                                ? "Creando..."
                                : "Crear personaje"
                        }}
                    </button>

                    <p v-if="characterMessage" aria-live="polite">
                        {{ characterMessage }}
                    </p>
                </form>

                <div v-if="characters.length" class="item-grid">
                    <article
                        v-for="character in characters"
                        :key="character.id"
                        class="campaign-item"
                    >
                        <h3>{{ character.name }}</h3>
                        <p v-if="character.race">Raza: {{ character.race }}</p>
                        <p>Nivel: {{ character.level }}</p>
                        <p v-if="character.description">
                            {{ character.description }}
                        </p>
                    </article>
                </div>

                <p v-else class="empty-message">
                    Todavía no hay personajes en esta campaña.
                </p>
            </section>

            <section class="campaign-section">
                <h2>NPCs</h2>

                <div v-if="npcs.length" class="item-grid">
                    <article
                        v-for="npc in npcs"
                        :key="npc.id"
                        class="campaign-item"
                    >
                        <h3>{{ npc.name }}</h3>
                        <p v-if="npc.role">Papel: {{ npc.role }}</p>
                        <p v-if="npc.race">Raza: {{ npc.race }}</p>
                        <p v-if="npc.description">{{ npc.description }}</p>
                    </article>
                </div>

                <p v-else class="empty-message">
                    Todavía no hay NPCs en esta campaña.
                </p>
            </section>

            <section class="campaign-section">
                <h2>Lugares</h2>

                <div v-if="locations.length" class="item-grid">
                    <article
                        v-for="location in locations"
                        :key="location.id"
                        class="campaign-item"
                    >
                        <h3>{{ location.name }}</h3>
                        <p v-if="location.type">Tipo: {{ location.type }}</p>
                        <p v-if="location.description">
                            {{ location.description }}
                        </p>
                    </article>
                </div>

                <p v-else class="empty-message">
                    Todavía no hay lugares en esta campaña.
                </p>
            </section>

            <section class="campaign-section">
                <h2>Sesiones</h2>

                <div v-if="sessions.length" class="item-grid">
                    <article
                        v-for="session in sessions"
                        :key="session.id"
                        class="campaign-item"
                    >
                        <h3>{{ session.title }}</h3>

                        <p v-if="session.session_date">
                            Fecha: {{ formatDate(session.session_date) }}
                        </p>

                        <p v-if="session.summary">{{ session.summary }}</p>
                    </article>
                </div>

                <p v-else class="empty-message">
                    Todavía no hay sesiones en esta campaña.
                </p>
            </section>
        </template>

        <RouterLink class="back-link" to="/campaigns">
            Volver a mis campañas
        </RouterLink>
    </main>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
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

const creatingCharacter = ref(false);
const characterMessage = ref("");

const newCharacter = ref({
    name: "",
    race: "",
    race_index: "",
    subrace_index: "",
    background_index: "",
    background: "",
    level: 1,
    description: "",
    personality_trait: "",
    ideal: "",
    bond: "",
    flaw: "",
    notes: "",
    ability_score_method: "manual",

    strength: null,
    dexterity: null,
    constitution: null,
    intelligence: null,
    wisdom: null,
    charisma: null,

    strength_bonus: 0,
    dexterity_bonus: 0,
    constitution_bonus: 0,
    intelligence_bonus: 0,
    wisdom_bonus: 0,
    charisma_bonus: 0,
});

const races = ref([]);
const backgrounds = ref([]);
const availableSubraces = ref([]);
const loadingSubraces = ref(false);

const abilities = [
    { key: "strength", bonus: "strength_bonus", label: "Fuerza" },
    { key: "dexterity", bonus: "dexterity_bonus", label: "Destreza" },
    {
        key: "constitution",
        bonus: "constitution_bonus",
        label: "Constitución",
    },
    {
        key: "intelligence",
        bonus: "intelligence_bonus",
        label: "Inteligencia",
    },
    { key: "wisdom", bonus: "wisdom_bonus", label: "Sabiduría" },
    { key: "charisma", bonus: "charisma_bonus", label: "Carisma" },
];

// Costes de compra de puntos.
// Las puntuaciones entre 1 y 8 no consumen puntos.
const pointCosts = {
    1: 0,
    2: 0,
    3: 0,
    4: 0,
    5: 0,
    6: 0,
    7: 0,
    8: 0,
    9: 1,
    10: 2,
    11: 3,
    12: 4,
    13: 5,
    14: 7,
    15: 9,
};

const pointBuyTotal = computed(() => {
    if (newCharacter.value.ability_score_method !== "point_buy") {
        return null;
    }

    let total = 0;

    for (const ability of abilities) {
        const score = newCharacter.value[ability.key];

        // Si aún no se ha introducido una puntuación, no se suma.
        if (score === null || score === "" || score === undefined) {
            continue;
        }

        const numericScore = Number(score);

        // No calcular el coste de valores inválidos.
        if (
            !Number.isInteger(numericScore) ||
            numericScore < 1 ||
            numericScore > 15
        ) {
            return null;
        }

        total += pointCosts[numericScore];
    }

    return total;
});

const abilityModifier = (score, bonus) => {
    if (score === null || score === "" || score === undefined) {
        return "—";
    }

    const effectiveScore = Number(score) + Number(bonus || 0);
    const modifier = Math.floor((effectiveScore - 10) / 2);

    return modifier >= 0 ? `+${modifier}` : String(modifier);
};

const loadCharacterCatalogs = async () => {
    try {
        const [racesResponse, backgroundsResponse] = await Promise.all([
            api.get("/dnd/races"),
            api.get("/dnd/backgrounds"),
        ]);

        races.value = racesResponse.data.results ?? racesResponse.data;
        backgrounds.value =
            backgroundsResponse.data.results ?? backgroundsResponse.data;
    } catch (err) {
        characterMessage.value =
            "No se pudieron cargar las razas o los trasfondos.";
    }
};

const onRaceChange = async () => {
    newCharacter.value.subrace_index = "";
    availableSubraces.value = [];

    const raceIndex = newCharacter.value.race_index;

    if (!raceIndex) {
        newCharacter.value.race = "";
        return;
    }

    const selectedRace = races.value.find(
        (race) => race.index === raceIndex,
    );

    newCharacter.value.race = selectedRace?.name ?? "";

    loadingSubraces.value = true;

    try {
        const response = await api.get(`/dnd/races/${raceIndex}`);
        availableSubraces.value = response.data.subraces ?? [];
    } catch (err) {
        characterMessage.value = "No se pudieron cargar las subrazas.";
    } finally {
        loadingSubraces.value = false;
    }
};

const loadCampaign = async () => {
    loading.value = true;
    error.value = "";

    try {
        const campaignId = route.params.id;

        const response = await api.get(`/campaigns/${campaignId}`);
        campaign.value = response.data;

        const [
            charactersResponse,
            npcsResponse,
            locationsResponse,
            sessionsResponse,
        ] = await Promise.all([
            api.get(`/campaigns/${campaignId}/characters`),
            api.get(`/campaigns/${campaignId}/npcs`),
            api.get(`/campaigns/${campaignId}/locations`),
            api.get(`/campaigns/${campaignId}/sessions`),
        ]);

        characters.value = charactersResponse.data;
        npcs.value = npcsResponse.data;
        locations.value = locationsResponse.data;
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

const createCharacter = async () => {
    creatingCharacter.value = true;
    characterMessage.value = "";

    try {
        const response = await api.post(
            `/campaigns/${route.params.id}/characters`,
            {
                name: newCharacter.value.name,

                // Raza, subraza y trasfondo.
                race: newCharacter.value.race || null,
                race_index: newCharacter.value.race_index || null,
                subrace_index: newCharacter.value.subrace_index || null,
                background_index:
                    newCharacter.value.background_index || null,

                level: newCharacter.value.level,
                description: newCharacter.value.description || null,

                // Rasgos de personalidad, ideales, vínculos y defectos.
                personality_trait:
                    newCharacter.value.personality_trait || null,
                ideal: newCharacter.value.ideal || null,
                bond: newCharacter.value.bond || null,
                flaw: newCharacter.value.flaw || null,
                notes: newCharacter.value.notes || null,

                // Método de asignación de características.
                ability_score_method:
                    newCharacter.value.ability_score_method,

                // Características base.
                strength: newCharacter.value.strength,
                dexterity: newCharacter.value.dexterity,
                constitution: newCharacter.value.constitution,
                intelligence: newCharacter.value.intelligence,
                wisdom: newCharacter.value.wisdom,
                charisma: newCharacter.value.charisma,

                // Bonificaciones personalizadas.
                strength_bonus: newCharacter.value.strength_bonus,
                dexterity_bonus: newCharacter.value.dexterity_bonus,
                constitution_bonus: newCharacter.value.constitution_bonus,
                intelligence_bonus: newCharacter.value.intelligence_bonus,
                wisdom_bonus: newCharacter.value.wisdom_bonus,
                charisma_bonus: newCharacter.value.charisma_bonus,
            },
        );

        characterMessage.value =
            response.data.message || "Personaje creado correctamente.";

        // Reiniciar el formulario.
        newCharacter.value = {
            name: "",
            race: "",
            race_index: "",
            subrace_index: "",
            background_index: "",
            background: "",
            level: 1,
            description: "",
            personality_trait: "",
            ideal: "",
            bond: "",
            flaw: "",
            notes: "",
            ability_score_method: "manual",

            strength: null,
            dexterity: null,
            constitution: null,
            intelligence: null,
            wisdom: null,
            charisma: null,

            strength_bonus: 0,
            dexterity_bonus: 0,
            constitution_bonus: 0,
            intelligence_bonus: 0,
            wisdom_bonus: 0,
            charisma_bonus: 0,
        };

        availableSubraces.value = [];
        loadingSubraces.value = false;

        const charactersResponse = await api.get(
            `/campaigns/${route.params.id}/characters`,
        );

        characters.value = charactersResponse.data;
    } catch (err) {
        if (err.response?.data?.errors) {
            characterMessage.value = Object.values(err.response.data.errors)
                .flat()
                .join(" ");
        } else if (err.response) {
            characterMessage.value =
                err.response.data.message || "No se pudo crear el personaje.";
        } else {
            characterMessage.value = "No se pudo conectar con el servidor.";
        }
    } finally {
        creatingCharacter.value = false;
    }
};

const formatDate = (date) => {
    if (!date) {
        return "";
    }

    // Evitar que la zona horaria cambie el día de la fecha.
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(date);

    if (!match) {
        return date;
    }

    const [, year, month, day] = match;

    const formattedDate = new Date(
        Number(year),
        Number(month) - 1,
        Number(day),
    );

    return new Intl.DateTimeFormat("es-ES", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(formattedDate);
};

onMounted(() => {
    loadCampaign();
    loadCharacterCatalogs();
});
</script>