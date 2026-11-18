<template>
    <main class="character-create">
        <header class="page-header">
            <h1>Crear personaje</h1>
            <p>
                Sigue los pasos de arriba abajo: quién es, de dónde viene, qué sabe hacer y cómo es.{{ route.query.campaign_id ? ' Al terminar se pedirá añadirlo a la campaña seleccionada (si no eres su Dungeon Master, tendrá que aprobarlo).' : ' Podrás asignarlo a tus campañas más adelante.' }}
            </p>
        </header>

        <p v-if="error" class="error-message" role="alert">{{ error }}</p>

        <div class="creator-layout">
            <form class="character-form creator-form" @submit.prevent="createCharacter">

                <!-- 1. Identidad -->
                <section class="creator-section">
                    <header class="creator-section-header">
                        <span class="creator-step">1</span>
                        <div><h2>Identidad</h2><p>El nombre y el aspecto de tu personaje.</p></div>
                    </header>
                    <div class="creator-grid creator-grid--identity">
                        <div class="creator-portrait">
                            <div class="character-image-preview">
                                <img v-if="imagePreview" :src="imagePreview" alt="Vista previa del retrato del personaje" />
                                <span v-else aria-hidden="true">?</span>
                            </div>
                            <label for="character-image">Retrato (opcional)</label>
                            <input id="character-image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" @change="onImageSelected" />
                            <small>JPG, PNG o WebP. Máximo 2 MB.</small>
                            <button v-if="selectedImage" type="button" class="secondary-button" @click="removeSelectedImage">Quitar imagen</button>
                        </div>
                        <div class="form-group">
                            <label for="character-name">Nombre del personaje</label>
                            <input id="character-name" v-model="newCharacter.name" type="text" maxlength="255" placeholder="Ej. Arthas" required />
                        </div>
                    </div>
                </section>

                <!-- 2. Linaje -->
                <section class="creator-section">
                    <header class="creator-section-header">
                        <span class="creator-step">2</span>
                        <div><h2>Linaje</h2><p>Su raza y, si la tiene, su subraza.</p></div>
                    </header>
                    <div class="creator-grid">
                        <div class="form-group">
                            <label for="character-race">Raza</label>
                            <select id="character-race" v-model="newCharacter.race_index" @change="onRaceChange" required>
                                <option value="">Selecciona una raza</option>
                                <option v-for="race in races" :key="race.index" :value="race.index">{{ race.name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="character-subrace">Subraza</label>
                            <select id="character-subrace" v-model="newCharacter.subrace_index" :disabled="!newCharacter.race_index || loadingSubraces">
                                <option value="">Sin subraza</option>
                                <option v-for="subrace in availableSubraces" :key="subrace.index" :value="subrace.index">{{ subrace.name }}</option>
                            </select>
                            <small v-if="loadingSubraces">Cargando subrazas...</small>
                        </div>
                    </div>
                </section>

                <!-- 3. Clases -->
                <section class="creator-section">
                    <header class="creator-section-header">
                        <span class="creator-step">3</span>
                        <div><h2>Clases</h2><p>La principal empieza en nivel 1; añade niveles y otras clases si quieres.</p></div>
                    </header>

                    <div class="class-plan">
                        <div class="class-plan-row">
                            <div class="form-group">
                                <label for="character-class">Clase principal</label>
                                <select id="character-class" v-model="newCharacter.class_index" @change="onClassChange" required>
                                    <option value="">Selecciona una clase</option>
                                    <option v-for="item in classes" :key="item.index" :value="item.index" :disabled="extraClasses.some((entry) => entry.class_index === item.index)">{{ item.name }}</option>
                                </select>
                            </div>
                            <div class="class-level" role="group" aria-label="Nivel de la clase principal">
                                <button type="button" class="icon-button" :disabled="mainLevel <= 1" aria-label="Bajar un nivel" @click="mainLevel -= 1">−</button>
                                <output>Nivel {{ mainLevel }}</output>
                                <button type="button" class="icon-button" :disabled="totalLevel >= 20" aria-label="Subir un nivel" @click="mainLevel += 1">+</button>
                            </div>
                        </div>

                        <div v-for="(entry, index) in extraClasses" :key="index" class="class-plan-row">
                            <div class="form-group">
                                <label :for="`extra-class-${index}`">Otra clase</label>
                                <select :id="`extra-class-${index}`" v-model="entry.class_index">
                                    <option value="">Selecciona una clase</option>
                                    <option v-for="item in classes" :key="item.index" :value="item.index" :disabled="item.index === newCharacter.class_index || extraClasses.some((other, i) => i !== index && other.class_index === item.index)">{{ item.name }}</option>
                                </select>
                            </div>
                            <div class="class-level" role="group" :aria-label="`Nivel de la clase ${index + 2}`">
                                <button type="button" class="icon-button" :disabled="entry.level <= 1" aria-label="Bajar un nivel" @click="entry.level -= 1">−</button>
                                <output>Nivel {{ entry.level }}</output>
                                <button type="button" class="icon-button" :disabled="totalLevel >= 20" aria-label="Subir un nivel" @click="entry.level += 1">+</button>
                            </div>
                            <button type="button" class="icon-button danger" :aria-label="`Quitar la clase ${index + 2}`" @click="extraClasses.splice(index, 1)"><AppIcon name="x" /></button>
                        </div>

                        <div class="class-plan-footer">
                            <button type="button" class="secondary-button" :disabled="totalLevel >= 20 || !newCharacter.class_index || extraClasses.length + 1 >= classes.length" @click="extraClasses.push({ class_index: '', level: 1 })"><AppIcon name="plus" /> Añadir otra clase</button>
                            <span class="class-total">Nivel total {{ totalLevel }}</span>
                        </div>

                        <p v-if="classWarnings.length" class="warning-message" role="status">
                            <strong>Requisitos de multiclase sin cumplir</strong>
                            <span v-for="warning in classWarnings" :key="warning">{{ warning }}</span>
                            <small>Es solo un aviso: puedes continuar.</small>
                        </p>
                    </div>

                    <p v-if="loadingClassDetails" class="muted">Cargando competencias de clase...</p>
                    <div v-if="selectedClassDetails" class="class-details">
                        <p><strong>Dado de golpe:</strong> d{{ selectedClassDetails.hit_die }}</p>
                        <div class="class-proficiencies-summary">
                            <h3>Competencias automáticas</h3>
                            <p><strong>Tiradas de salvación:</strong> {{ (selectedClassDetails.saving_throws || []).map(item => item.name).join(', ') || 'Ninguna' }}</p>
                            <p><strong>Armaduras, armas y herramientas:</strong> {{ (selectedClassDetails.proficiencies || []).map(item => item.name).join(', ') || 'Ninguna' }}</p>
                        </div>
                        <fieldset v-for="(choice, choiceIndex) in classChoiceGroups" :key="choiceIndex" class="choice-group">
                            <legend>Elige {{ choice.choose }} competencia(s) <small>({{ (selectedClassProficiencies[choiceIndex] || []).length }} / {{ choice.choose }})</small></legend>
                            <div class="choice-options">
                                <label v-for="option in choice.options" :key="option.index" class="proficiency-option">
                                    <input type="checkbox" :checked="(selectedClassProficiencies[choiceIndex] || []).includes(option.index)" @change="toggleClassProficiency(choiceIndex, option.index, $event.target.checked, choice.choose)" />
                                    {{ option.name }}
                                </label>
                            </div>
                        </fieldset>
                    </div>
                    <p v-if="classLoadingError" class="error-message">{{ classLoadingError }}</p>
                </section>

                <!-- 4. Trasfondo -->
                <section class="creator-section">
                    <header class="creator-section-header">
                        <span class="creator-step">4</span>
                        <div><h2>Trasfondo</h2><p>Lo que hacía tu personaje antes de la aventura.</p></div>
                    </header>
                    <div class="form-group">
                        <label for="character-background">Trasfondo</label>
                        <select id="character-background" v-model="newCharacter.background_index">
                            <option value="">Selecciona un trasfondo</option>
                            <option v-for="background in backgrounds" :key="background.index" :value="background.index">{{ background.name }}</option>
                        </select>
                    </div>
                </section>

                <!-- 5. Características -->
                <section class="creator-section ability-score-section">
                    <header class="creator-section-header">
                        <span class="creator-step">5</span>
                        <div><h2>Características</h2><p>Las seis puntuaciones que definen lo que mejor sabe hacer.</p></div>
                    </header>

                    <div class="form-group method-group">
                        <label for="ability-score-method">Método de asignación</label>
                        <select id="ability-score-method" v-model="newCharacter.ability_score_method" @change="onAbilityMethodChange">
                            <option value="manual">Asignación manual</option>
                            <option value="standard_array">Array estándar</option>
                            <option value="point_buy">Compra de puntos</option>
                        </select>
                    </div>

                    <p v-if="newCharacter.ability_score_method === 'manual'" class="method-help">Introduce cada puntuación entre 1 y 20.</p>
                    <div v-else-if="newCharacter.ability_score_method === 'standard_array'" class="standard-pool">
                        <p class="method-help">Reparte estos seis valores como quieras, <strong>uno por característica</strong>. Los que ya has usado se atenúan.</p>
                        <div class="pool-chips" aria-label="Valores del array estándar">
                            <span v-for="value in STANDARD_ARRAY" :key="value" class="pool-chip" :class="{ 'is-used': !remainingStandard.includes(value) }">{{ value }}</span>
                        </div>
                        <div class="pool-actions">
                            <button type="button" class="secondary-button" :disabled="!newCharacter.class_index" title="Reparto rápido recomendado para la clase en el manual del jugador" @click="suggestStandardArray">Recomendado por clase</button>
                            <button type="button" class="secondary-button" @click="clearStandardArray">Vaciar</button>
                        </div>
                    </div>
                    <template v-else-if="newCharacter.ability_score_method === 'point_buy'">
                        <p class="method-help">Sube o baja cada característica con − y +. Todas empiezan en 8, tienes 27 puntos para repartir y el máximo es 15.</p>
                        <p class="points-used" :class="{ 'is-over': pointBuyTotal !== null && pointBuyTotal > 27 }">
                            Puntos utilizados: <strong>{{ pointBuyTotal === null ? "—" : pointBuyTotal }} / 27</strong> · Restantes: <strong>{{ pointsLeft }}</strong>
                        </p>
                        <p v-if="pointBuyTotal !== null && pointBuyTotal > 27" class="error-message">Has superado el límite de puntos.</p>
                    </template>

                    <div class="ability-score-grid">
                        <div v-for="ability in abilities" :key="ability.key" class="ability-score-card">
                            <h3>{{ ability.label }}</h3>
                            <div class="ability-inputs">
                                <div class="form-group">
                                    <label :for="`score-${ability.key}`">Base</label>

                                    <select v-if="newCharacter.ability_score_method === 'standard_array'" :id="`score-${ability.key}`" v-model.number="newCharacter[ability.key]" required>
                                        <option value="">—</option>
                                        <option v-for="value in STANDARD_ARRAY" :key="value" :value="value" :disabled="isStandardValueTaken(value, ability.key)">{{ value }}</option>
                                    </select>

                                    <div v-else-if="newCharacter.ability_score_method === 'point_buy'" class="stepper">
                                        <button type="button" class="icon-button" :disabled="Number(newCharacter[ability.key]) <= 8" :aria-label="`Bajar ${ability.label}`" @click="adjustPoint(ability.key, -1)">−</button>
                                        <output :id="`score-${ability.key}`" class="stepper-value">{{ newCharacter[ability.key] }}</output>
                                        <button type="button" class="icon-button" :disabled="!canRaise(ability.key)" :aria-label="`Subir ${ability.label}`" @click="adjustPoint(ability.key, 1)">+</button>
                                    </div>

                                    <input v-else :id="`score-${ability.key}`" v-model.number="newCharacter[ability.key]" type="number" min="1" max="20" required />
                                </div>
                                <div class="form-group">
                                    <label :for="`bonus-${ability.key}`">Bono</label>
                                    <input :id="`bonus-${ability.key}`" v-model.number="newCharacter[ability.bonus]" type="number" min="-5" max="5" required />
                                </div>
                            </div>
                            <p class="ability-result">
                                <span>Efectiva <strong>{{ effectiveScore(ability.key, ability.bonus) }}</strong></span>
                                <span>Mod. <strong>{{ abilityModifier(newCharacter[ability.key], newCharacter[ability.bonus]) }}</strong></span>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- 6. Personalidad -->
                <section class="creator-section">
                    <header class="creator-section-header">
                        <span class="creator-step">6</span>
                        <div><h2>Personalidad</h2><p>Cómo piensa y cómo actúa.</p></div>
                    </header>
                    <div class="creator-grid">
                        <div class="form-group">
                            <label for="personality_trait">Rasgo de personalidad</label>
                            <textarea id="personality_trait" v-model="newCharacter.personality_trait" rows="3" placeholder="¿Cómo se comporta tu personaje?"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="ideal">Ideal</label>
                            <textarea id="ideal" v-model="newCharacter.ideal" rows="3" placeholder="¿Qué principio guía a tu personaje?"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="bond">Vínculo</label>
                            <textarea id="bond" v-model="newCharacter.bond" rows="3" placeholder="¿Qué persona, lugar u objeto es importante?"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="flaw">Defecto</label>
                            <textarea id="flaw" v-model="newCharacter.flaw" rows="3" placeholder="¿Qué debilidad tiene tu personaje?"></textarea>
                        </div>
                    </div>
                </section>

                <!-- 7. Historia -->
                <section class="creator-section">
                    <header class="creator-section-header">
                        <span class="creator-step">7</span>
                        <div><h2>Historia y notas</h2><p>Todo lo demás que quieras recordar.</p></div>
                    </header>
                    <div class="form-group">
                        <label for="character-description">Descripción</label>
                        <textarea id="character-description" v-model="newCharacter.description" rows="4" placeholder="Historia, apariencia y otros detalles..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notas adicionales</label>
                        <textarea id="notes" v-model="newCharacter.notes" rows="4" placeholder="Información adicional sobre tu personaje..."></textarea>
                    </div>
                </section>

                <p v-if="characterMessage" aria-live="polite" :class="messageIsError ? 'error-message' : 'success-message'">{{ characterMessage }}</p>

                <div class="creator-actions">
                    <RouterLink to="/characters" class="secondary-button">Cancelar</RouterLink>
                    <RouterLink v-if="characterCreated" to="/characters" class="secondary-button">Ir a mis personajes</RouterLink>
                    <button type="submit" class="primary-button" :disabled="creatingCharacter || characterCreated">
                        {{ creatingCharacter ? "Creando personaje..." : characterCreated ? "Personaje creado" : "Crear personaje" }}
                    </button>
                </div>
            </form>

            <!-- Resumen en vivo -->
            <aside class="creator-summary" aria-label="Resumen del personaje">
                <h2>Resumen</h2>
                <div class="summary-portrait">
                    <img v-if="imagePreview" :src="imagePreview" alt="" />
                    <span v-else aria-hidden="true">{{ summary.name?.slice(0, 1)?.toUpperCase() || '?' }}</span>
                </div>
                <p class="summary-name">{{ summary.name || 'Sin nombre' }}</p>
                <dl class="summary-list">
                    <div><dt>Raza</dt><dd>{{ summary.race || '—' }}<template v-if="summary.subrace"> ({{ summary.subrace }})</template></dd></div>
                    <div><dt>Clase</dt><dd>{{ summary.className || '—' }}</dd></div>
                    <div><dt>Nivel</dt><dd>{{ summary.level || '—' }}</dd></div>
                    <div><dt>Trasfondo</dt><dd>{{ summary.background || '—' }}</dd></div>
                </dl>
                <ul class="summary-abilities">
                    <li v-for="ability in abilities" :key="ability.key">
                        <span>{{ ability.label.slice(0, 3).toUpperCase() }}</span>
                        <strong>{{ effectiveScore(ability.key, ability.bonus) }}</strong>
                        <small>{{ abilityModifier(newCharacter[ability.key], newCharacter[ability.bonus]) }}</small>
                    </li>
                </ul>
            </aside>
        </div>
    </main>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import api from "../services/api";
import AppIcon from "../components/AppIcon.vue";
import { multiclassWarnings } from "../services/multiclass";

const router = useRouter();
const route = useRoute();

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
const classes = ref([]);
const selectedClassDetails = ref(null);
const selectedClassProficiencies = ref([]);
const loadingClassDetails = ref(false);
const classLoadingError = ref("");
const availableSubraces = ref([]);
const loadingSubraces = ref(false);

// Reparto de niveles: la clase principal (nivel mínimo 1) y, opcionalmente, otras clases.
const mainLevel = ref(1);
const extraClasses = ref([]);
const totalLevel = computed(() => mainLevel.value + extraClasses.value.reduce((sum, entry) => sum + entry.level, 0));

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
    class_index: "",
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

const classChoiceGroups = computed(() => {
    const groups = selectedClassDetails.value?.proficiency_choices ?? [];
    return groups.map((group) => ({
        choose: Number(group.choose || 0),
        options: (group.from?.options || group.from?.option_set?.options || [])
            .map((entry) => entry.item || entry)
            .filter((item) => item?.index),
    }));
});

const toggleClassProficiency = (groupIndex, proficiencyIndex, checked, maxChoices) => {
    const current = [...(selectedClassProficiencies.value[groupIndex] || [])];
    if (checked && current.length < maxChoices && !current.includes(proficiencyIndex)) {
        current.push(proficiencyIndex);
    } else if (!checked) {
        const position = current.indexOf(proficiencyIndex);
        if (position !== -1) current.splice(position, 1);
    }
    selectedClassProficiencies.value[groupIndex] = current;
};

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

// Array estándar: cada valor se usa una sola vez y se reparte como se quiera.
const STANDARD_ARRAY = [15, 14, 13, 12, 10, 8];

const remainingStandard = computed(() => {
    const used = abilities.map((ability) => newCharacter.value[ability.key]);
    return STANDARD_ARRAY.filter((value) => !used.includes(value));
});

const isStandardValueTaken = (value, key) =>
    abilities.some((ability) => ability.key !== key && newCharacter.value[ability.key] === value);

// Prioridad de características por clase (reparto rápido del manual del jugador).
const CLASS_PRIORITY = {
    barbarian: ["strength", "constitution", "dexterity", "wisdom", "charisma", "intelligence"],
    bard: ["charisma", "dexterity", "constitution", "wisdom", "intelligence", "strength"],
    cleric: ["wisdom", "constitution", "strength", "charisma", "dexterity", "intelligence"],
    druid: ["wisdom", "constitution", "dexterity", "intelligence", "charisma", "strength"],
    fighter: ["strength", "constitution", "dexterity", "wisdom", "charisma", "intelligence"],
    monk: ["dexterity", "wisdom", "constitution", "strength", "intelligence", "charisma"],
    paladin: ["strength", "charisma", "constitution", "wisdom", "dexterity", "intelligence"],
    ranger: ["dexterity", "wisdom", "constitution", "strength", "intelligence", "charisma"],
    rogue: ["dexterity", "intelligence", "charisma", "constitution", "wisdom", "strength"],
    sorcerer: ["charisma", "constitution", "dexterity", "wisdom", "intelligence", "strength"],
    warlock: ["charisma", "constitution", "dexterity", "wisdom", "intelligence", "strength"],
    wizard: ["intelligence", "constitution", "dexterity", "wisdom", "charisma", "strength"],
};

// Reparte 15, 14, 13, 12, 10 y 8 según la prioridad de la clase elegida.
const suggestStandardArray = () => {
    const priority = CLASS_PRIORITY[newCharacter.value.class_index];

    if (!priority) {
        return;
    }

    priority.forEach((key, position) => {
        newCharacter.value[key] = STANDARD_ARRAY[position];
    });
};

const clearStandardArray = () => {
    abilities.forEach((ability) => {
        newCharacter.value[ability.key] = "";
    });
};

// Compra de puntos: − y + con el coste a la vista.
const pointsLeft = computed(() => 27 - (pointBuyTotal.value ?? 0));

const canRaise = (key) => {
    const current = Number(newCharacter.value[key]);

    if (!Number.isInteger(current) || current >= 15) {
        return false;
    }

    return pointCosts[current + 1] - pointCosts[current] <= pointsLeft.value;
};

const adjustPoint = (key, delta) => {
    const current = Number(newCharacter.value[key]);
    const next = current + delta;

    if (next < 8 || next > 15) {
        return;
    }

    if (delta > 0 && !canRaise(key)) {
        return;
    }

    newCharacter.value[key] = next;
};

const onAbilityMethodChange = () => {
    const method = newCharacter.value.ability_score_method;

    if (method === "standard_array") {
        clearStandardArray();
    } else if (method === "point_buy") {
        abilities.forEach((ability) => {
            newCharacter.value[ability.key] = 8;
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
        const [racesResponse, backgroundsResponse, classesResponse] = await Promise.all([
            api.get("/dnd/races"),
            api.get("/dnd/backgrounds"),
            api.get("/dnd/classes"),
        ]);

        races.value = racesResponse.data.results ?? racesResponse.data;
        backgrounds.value = backgroundsResponse.data.results ?? backgroundsResponse.data;
        classes.value = classesResponse.data.results ?? classesResponse.data;
    } catch {
        error.value =
            "No se pudieron cargar las razas, los trasfondos o las clases. Recarga la página para intentarlo de nuevo.";
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

const onClassChange = async () => {
    selectedClassDetails.value = null;
    selectedClassProficiencies.value = [];
    classLoadingError.value = "";
    if (!newCharacter.value.class_index) return;

    loadingClassDetails.value = true;
    try {
        const response = await api.get(`/dnd/classes/${newCharacter.value.class_index}`);
        selectedClassDetails.value = response.data;
        selectedClassProficiencies.value = (response.data.proficiency_choices || []).map(() => []);
    } catch {
        classLoadingError.value = "No se pudieron cargar las competencias de esta clase.";
    } finally {
        loadingClassDetails.value = false;
    }
};

// Reparto enviado al servidor: la principal primero; solo con más de un nivel.
function planForRequest() {
    if (totalLevel.value <= 1) return undefined;

    return [
        { class_index: newCharacter.value.class_index, level: mainLevel.value },
        ...extraClasses.value.filter((entry) => entry.class_index).map((entry) => ({ class_index: entry.class_index, level: entry.level })),
    ];
}

const createCharacter = async () => {
    if (creatingCharacter.value || characterCreated.value) {
        return;
    }

    characterMessage.value = "";
    messageIsError.value = false;
    error.value = "";

    if (!newCharacter.value.class_index || !selectedClassDetails.value) {
        characterMessage.value = "Selecciona una clase principal válida.";
        messageIsError.value = true;
        return;
    }

    if (
        newCharacter.value.ability_score_method === "standard_array" &&
        remainingStandard.value.length > 0
    ) {
        characterMessage.value =
            "Reparte los seis valores del array estándar entre las características.";
        messageIsError.value = true;
        return;
    }

    const missingClassChoices = classChoiceGroups.value.some((choice, index) =>
        (selectedClassProficiencies.value[index] || []).length !== choice.choose
    );
    if (missingClassChoices) {
        characterMessage.value = "Completa todas las elecciones de competencias de la clase.";
        messageIsError.value = true;
        return;
    }

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
            class_index: newCharacter.value.class_index,
            class_proficiencies: selectedClassProficiencies.value.flat(),

            level: totalLevel.value,
            level_plan: planForRequest(),
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

        const campaignId = route.query.campaign_id;
        if (campaignId) {
            await api.post(`/campaigns/${campaignId}/characters/${characterId}`);
        }

        // Por encima del nivel 1 se sigue con la subida de nivel paso a paso.
        if (Number(newCharacter.value.level) > 1) {
            await router.push({ name: "character-detail", params: { id: characterId }, query: { levelup: "1" } });
        } else if (campaignId) {
            await router.push(`/campaigns/${campaignId}`);
        } else {
            await router.push("/characters");
        }
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

// El nivel total se refleja en el resumen y se envía como nivel objetivo.
watch(totalLevel, (value) => { newCharacter.value.level = value; }, { immediate: true });

// Aviso (no prohibición) de los requisitos de característica de la multiclase.
const classWarnings = computed(() => multiclassWarnings(
    [newCharacter.value.class_index, ...extraClasses.value.map((entry) => entry.class_index)],
    (key) => Number(newCharacter.value[key] || 0) + Number(newCharacter.value[`${key}_bonus`] || 0),
));

// Resumen en vivo de lo elegido hasta ahora.
const nameOf = (list, index) => list.find((entry) => entry.index === index)?.name || '';
const summary = computed(() => ({
    name: newCharacter.value.name,
    race: nameOf(races.value, newCharacter.value.race_index),
    subrace: nameOf(availableSubraces.value, newCharacter.value.subrace_index),
    className: nameOf(classes.value, newCharacter.value.class_index),
    level: newCharacter.value.level,
    background: nameOf(backgrounds.value, newCharacter.value.background_index),
}));

onMounted(() => {
    loadCharacterCatalogs();
});

onBeforeUnmount(() => {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }
});
</script>
