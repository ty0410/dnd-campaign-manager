<template>
    <main class="character-create">
        <header class="page-header">
            <h1>Crear personaje</h1>
            <p>
                Crea tu personaje y guárdalo en tu colección. Podrás asignarlo a
                tus campañas más adelante.
            </p>
        </header>

        <p v-if="error" class="error-message" role="alert">
            {{ error }}
        </p>

        <form class="character-form" @submit.prevent="createCharacter">
            <h2>Información básica</h2>

            <div class="form-group character-image-group">
                <label for="character-image">
                    Retrato del personaje (opcional)
                </label>

                <div class="character-image-preview">
                    <img
                        v-if="imagePreview"
                        :src="imagePreview"
                        alt="Vista previa del retrato del personaje"
                    />
                    <span v-else aria-hidden="true">🧙</span>
                </div>

                <input
                    id="character-image"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    @change="onImageSelected"
                />

                <small>Formatos JPG, PNG o WebP. Tamaño máximo: 2 MB.</small>

                <button
                    v-if="selectedImage"
                    type="button"
                    class="secondary-button"
                    @click="removeSelectedImage"
                >
                    Quitar imagen
                </button>
            </div>

            <div class="form-group">
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

            <div class="form-group">
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

            <div class="form-group">
                <label for="character-subrace">Subraza</label>
                <select
                    id="character-subrace"
                    v-model="newCharacter.subrace_index"
                    :disabled="!newCharacter.race_index || loadingSubraces"
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
                <small v-if="loadingSubraces">Cargando subrazas...</small>
            </div>

            <div class="form-group">
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

            <h2>Personalidad</h2>

            <div class="form-group">
                <label for="personality_trait">Rasgo de personalidad</label>
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
                    placeholder="Información adicional sobre tu personaje..."
                ></textarea>
            </div>

            <section class="ability-score-section">
                <h2>Características</h2>

                <div class="form-group">
                    <label for="ability-score-method">
                        Método de asignación
                    </label>
                    <select
                        id="ability-score-method"
                        v-model="newCharacter.ability_score_method"
                        @change="onAbilityMethodChange"
                    >
                        <option value="manual">Asignación manual</option>
                        <option value="standard_array">Array estándar</option>
                        <option value="point_buy">Compra de puntos</option>
                    </select>
                </div>

                <p v-if="newCharacter.ability_score_method === 'manual'">
                    Introduce cada puntuación entre 1 y 20.
                </p>

                <p
                    v-else-if="
                        newCharacter.ability_score_method === 'standard_array'
                    "
                >
                    El array estándar utiliza 15, 14, 13, 12, 10 y 8. Puedes
                    modificar las puntuaciones después de aplicarlo.
                </p>

                <template
                    v-else-if="
                        newCharacter.ability_score_method === 'point_buy'
                    "
                >
                    <p>
                        Las puntuaciones pueden ir de 1 a 15. Los valores del 1
                        al 8 cuestan 0 puntos. Dispones de un máximo de 27
                        puntos.
                    </p>

                    <p>
                        Puntos utilizados:
                        <strong>
                            {{ pointBuyTotal === null ? "—" : pointBuyTotal }}
                            / 27
                        </strong>
                    </p>

                    <p
                        v-if="pointBuyTotal !== null && pointBuyTotal > 27"
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
                        <h3>{{ ability.label }}</h3>

                        <div class="form-group">
                            <label :for="`score-${ability.key}`">
                                Puntuación base
                            </label>
                            <input
                                :id="`score-${ability.key}`"
                                v-model.number="newCharacter[ability.key]"
                                type="number"
                                min="1"
                                :max="
                                    newCharacter.ability_score_method ===
                                    'point_buy'
                                        ? 15
                                        : 20
                                "
                                required
                            />
                        </div>

                        <div class="form-group">
                            <label :for="`bonus-${ability.key}`">
                                Bono personalizado
                            </label>
                            <input
                                :id="`bonus-${ability.key}`"
                                v-model.number="newCharacter[ability.bonus]"
                                type="number"
                                min="-5"
                                max="5"
                                required
                            />
                        </div>

                        <p>
                            Puntuación efectiva:
                            <strong>
                                {{ effectiveScore(ability.key, ability.bonus) }}
                            </strong>
                        </p>

                        <p>
                            Modificador:
                            <strong>
                                {{
                                    abilityModifier(
                                        newCharacter[ability.key],
                                        newCharacter[ability.bonus],
                                    )
                                }}
                            </strong>
                        </p>
                    </div>
                </div>
            </section>

            <div class="form-group">
                <label for="character-description">Descripción</label>
                <textarea
                    id="character-description"
                    v-model="newCharacter.description"
                    rows="4"
                    placeholder="Historia, apariencia y otros detalles..."
                ></textarea>
            </div>

            <div class="form-actions">
                <button
                    type="submit"
                    :disabled="creatingCharacter || characterCreated"
                >
                    {{
                        creatingCharacter
                            ? "Creando personaje..."
                            : characterCreated
                              ? "Personaje creado"
                              : "Crear personaje"
                    }}
                </button>

                <RouterLink to="/characters" class="back-link">
                    Cancelar
                </RouterLink>

                <RouterLink
                    v-if="characterCreated"
                    to="/characters"
                    class="back-link"
                >
                    Ir a mis personajes
                </RouterLink>
            </div>

            <p
                v-if="characterMessage"
                aria-live="polite"
                :class="messageIsError ? 'error-message' : 'success-message'"
            >
                {{ characterMessage }}
            </p>
        </form>
    </main>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { RouterLink, useRouter } from "vue-router";
import api from "../services/api";

const router = useRouter();

const error = ref("");
const creatingCharacter = ref(false);
const characterCreated = ref(false);
const characterMessage = ref("");
const messageIsError = ref(false);

const selectedImage = ref(null);
const imagePreview = ref("");

const allowedImageTypes = ["image/jpeg", "image/png", "image/webp"];

const races = ref([]);
const backgrounds = ref([]);
const availableSubraces = ref([]);
const loadingSubraces = ref(false);

const abilities = [
    { key: "strength", bonus: "strength_bonus", label: "Fuerza" },
    { key: "dexterity", bonus: "dexterity_bonus", label: "Destreza" },
    { key: "constitution", bonus: "constitution_bonus", label: "Constitución" },
    { key: "intelligence", bonus: "intelligence_bonus", label: "Inteligencia" },
    { key: "wisdom", bonus: "wisdom_bonus", label: "Sabiduría" },
    { key: "charisma", bonus: "charisma_bonus", label: "Carisma" },
];

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

const createEmptyCharacter = () => ({
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

const newCharacter = ref(createEmptyCharacter());

const pointBuyTotal = computed(() => {
    if (newCharacter.value.ability_score_method !== "point_buy") {
        return null;
    }

    let total = 0;

    for (const ability of abilities) {
        const score = newCharacter.value[ability.key];

        if (score === null || score === "" || score === undefined) {
            continue;
        }

        const numericScore = Number(score);

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

const effectiveScore = (key, bonusKey) => {
    const score = newCharacter.value[key];

    if (score === null || score === "" || score === undefined) {
        return "—";
    }

    return Number(score) + Number(newCharacter.value[bonusKey] || 0);
};

const abilityModifier = (score, bonus) => {
    if (score === null || score === "" || score === undefined) {
        return "—";
    }

    const modifier = Math.floor((Number(score) + Number(bonus || 0) - 10) / 2);

    return modifier >= 0 ? `+${modifier}` : String(modifier);
};

const onAbilityMethodChange = () => {
    if (newCharacter.value.ability_score_method === "standard_array") {
        const standardArray = [15, 14, 13, 12, 10, 8];

        abilities.forEach((ability, index) => {
            newCharacter.value[ability.key] = standardArray[index];
        });
    }
};

const onImageSelected = (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    if (!allowedImageTypes.includes(file.type)) {
        characterMessage.value =
            "Formato no válido. Selecciona una imagen JPG, PNG o WebP.";
        messageIsError.value = true;
        event.target.value = "";
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        characterMessage.value = "La imagen no puede superar los 2 MB.";
        messageIsError.value = true;
        event.target.value = "";
        return;
    }

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }

    characterMessage.value = "";
    messageIsError.value = false;
    selectedImage.value = file;
    imagePreview.value = URL.createObjectURL(file);
};

const removeSelectedImage = () => {
    selectedImage.value = null;

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = "";
    }

    const input = document.getElementById("character-image");

    if (input) {
        input.value = "";
    }

    characterMessage.value = "";
    messageIsError.value = false;
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
    } catch {
        error.value =
            "No se pudieron cargar las razas o los trasfondos. Recarga la página para intentarlo de nuevo.";
    }
};

const onRaceChange = async () => {
    newCharacter.value.subrace_index = "";
    newCharacter.value.race = "";
    availableSubraces.value = [];

    const raceIndex = newCharacter.value.race_index;

    if (!raceIndex) {
        return;
    }

    const selectedRace = races.value.find((race) => race.index === raceIndex);

    newCharacter.value.race = selectedRace?.name ?? "";
    loadingSubraces.value = true;

    try {
        const response = await api.get(`/dnd/races/${raceIndex}`);
        availableSubraces.value = response.data.subraces ?? [];
    } catch {
        characterMessage.value = "No se pudieron cargar las subrazas.";
        messageIsError.value = true;
    } finally {
        loadingSubraces.value = false;
    }
};

const createCharacter = async () => {
    if (creatingCharacter.value || characterCreated.value) {
        return;
    }

    characterMessage.value = "";
    messageIsError.value = false;
    error.value = "";

    if (
        newCharacter.value.ability_score_method === "point_buy" &&
        (pointBuyTotal.value === null || pointBuyTotal.value > 27)
    ) {
        characterMessage.value =
            "Revisa las puntuaciones: la compra de puntos debe ser válida y no superar los 27 puntos.";
        messageIsError.value = true;
        return;
    }

    creatingCharacter.value = true;

    try {
        const payload = {
            name: newCharacter.value.name,
            race: newCharacter.value.race || null,
            race_index: newCharacter.value.race_index || null,
            subrace_index: newCharacter.value.subrace_index || null,
            background_index: newCharacter.value.background_index || null,

            level: newCharacter.value.level,
            description: newCharacter.value.description || null,

            personality_trait: newCharacter.value.personality_trait || null,
            ideal: newCharacter.value.ideal || null,
            bond: newCharacter.value.bond || null,
            flaw: newCharacter.value.flaw || null,
            notes: newCharacter.value.notes || null,

            ability_score_method: newCharacter.value.ability_score_method,

            strength: newCharacter.value.strength,
            dexterity: newCharacter.value.dexterity,
            constitution: newCharacter.value.constitution,
            intelligence: newCharacter.value.intelligence,
            wisdom: newCharacter.value.wisdom,
            charisma: newCharacter.value.charisma,

            strength_bonus: newCharacter.value.strength_bonus,
            dexterity_bonus: newCharacter.value.dexterity_bonus,
            constitution_bonus: newCharacter.value.constitution_bonus,
            intelligence_bonus: newCharacter.value.intelligence_bonus,
            wisdom_bonus: newCharacter.value.wisdom_bonus,
            charisma_bonus: newCharacter.value.charisma_bonus,
        };

        // Primero creamos el personaje sin la imagen.
        const response = await api.post("/characters", payload);
        const character = response.data.character ?? response.data;
        const characterId = character.id;

        if (!characterId) {
            characterMessage.value =
                "El personaje se ha enviado, pero el servidor no devolvió su ID. Comprueba la respuesta de la API.";
            messageIsError.value = true;
            return;
        }

        characterCreated.value = true;

        // Si se seleccionó una imagen, la subimos con el ID obtenido.
        if (selectedImage.value) {
            const formData = new FormData();
            formData.append("image", selectedImage.value);

            try {
                await api.post(`/characters/${characterId}/image`, formData, {
                    headers: {
                        "Content-Type": undefined,
                    },
                });
            } catch (imageError) {
                const serverMessage =
                    imageError.response?.data?.errors?.image?.[0] ||
                    imageError.response?.data?.message;

                characterMessage.value =
                    "El personaje se ha creado, pero no se pudo subir el retrato. " +
                    (serverMessage ||
                        "Puedes ir a tus personajes y comprobarlo.");

                messageIsError.value = true;
                return;
            }
        }

        await router.push("/characters");
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

        messageIsError.value = true;
    } finally {
        creatingCharacter.value = false;
    }
};

onMounted(() => {
    loadCharacterCatalogs();
});

onBeforeUnmount(() => {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }
});
</script>
