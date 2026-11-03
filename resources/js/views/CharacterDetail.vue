<template>
    <section class="character-sheet-page">
        <div class="sheet-topbar">
            <RouterLink class="sheet-back-link" to="/characters">← Mis personajes</RouterLink>
            <span v-if="character?.campaigns?.length" class="sheet-campaign">
                {{ character.campaigns.map(campaign => campaign.name).join(', ') }}
            </span>
        </div>

        <p v-if="loading" class="sheet-state">Cargando ficha del personaje...</p>
        <div v-else-if="error" class="error-message">
            <p>{{ error }}</p>
            <button type="button" @click="loadCharacter">Reintentar</button>
        </div>

        <template v-else-if="character">
            <header class="character-sheet-header">
                <div class="sheet-portrait">
                    <img v-if="character.image_url" :src="character.image_url" :alt="`Retrato de ${character.name}`">
                    <span v-else aria-hidden="true">Sin retrato</span>
                </div>
                <div class="sheet-identity">
                    <p class="sheet-eyebrow">FICHA DE PERSONAJE</p>
                    <h1>{{ character.name }}</h1>
                    <p class="sheet-subtitle">
                        {{ character.race || 'Raza sin especificar' }}
                        <span v-if="character.background"> · {{ character.background }}</span>
                    </p>
                    <div class="sheet-class-list">
                        <span v-for="characterClass in character.character_classes" :key="characterClass.id" class="sheet-chip">
                            {{ characterClass.class }} · nivel {{ characterClass.level }}
                        </span>
                        <span v-if="!character.character_classes?.length" class="sheet-chip">
                            Nivel {{ sheet.total_level }} · Clase sin especificar
                        </span>
                    </div>
                </div>
                <div class="sheet-level-box">
                    <strong>{{ sheet.total_level }}</strong>
                    <span>NIVEL TOTAL</span>
                </div>
            </header>

            <section class="sheet-combat-grid" aria-label="Resumen de combate">
                <article class="sheet-stat-box"><span>Puntos de golpe</span><strong>{{ value(character.current_hit_points) }} / {{ value(character.maximum_hit_points) }}</strong><small v-if="character.temporary_hit_points">{{ character.temporary_hit_points }} temporales</small></article>
                <article class="sheet-stat-box"><span>Clase de armadura</span><strong>—</strong><small>No registrada</small></article>
                <article class="sheet-stat-box"><span>Iniciativa</span><strong>{{ signed(sheet.ability_scores.dexterity.modifier) }}</strong><small>Modificador de Destreza</small></article>
                <article class="sheet-stat-box"><span>Bonificador competencia</span><strong>{{ signed(sheet.proficiency_bonus) }}</strong><small>Según nivel total</small></article>
                <article class="sheet-stat-box"><span>Experiencia</span><strong>{{ value(character.experience_points) }}</strong><small v-if="character.inspiration">Inspiración disponible</small><small v-else>Sin inspiración marcada</small></article>
            </section>

            <section class="sheet-section">
                <div class="sheet-section-heading"><div><p class="sheet-eyebrow">ATRIBUTOS</p><h2>Características</h2></div></div>
                <div class="sheet-ability-grid">
                    <article v-for="(ability, key) in sheet.ability_scores" :key="key" class="sheet-ability-card">
                        <span>{{ ability.name }}</span>
                        <strong>{{ value(ability.effective_score) }}</strong>
                        <small>{{ signed(ability.modifier) }}</small>
                        <em v-if="ability.custom_bonus">Base {{ ability.base_score }} {{ signed(ability.custom_bonus) }}</em>
                    </article>
                </div>
            </section>

            <div class="sheet-two-columns">
                <section class="sheet-section">
                    <div class="sheet-section-heading"><div><p class="sheet-eyebrow">TIRADAS</p><h2>Salvaciones</h2></div></div>
                    <div class="sheet-list">
                        <div v-for="(save, key) in sheet.saving_throws" :key="key" class="sheet-list-row">
                            <span>{{ save.name }}</span><strong>{{ signed(save.modifier) }}</strong>
                            <small>{{ save.proficiency_level ? 'Competente' : 'Sin competencia' }}</small>
                        </div>
                    </div>
                </section>

                <section class="sheet-section">
                    <div class="sheet-section-heading"><div><p class="sheet-eyebrow">APTITUDES</p><h2>Habilidades</h2></div></div>
                    <div class="sheet-list">
                        <div v-for="skill in Object.values(sheet.skills)" :key="skill.index" class="sheet-list-row">
                            <span>{{ skill.name }} <small>({{ skill.ability }})</small></span>
                            <strong>{{ signed(skill.modifier) }}</strong>
                            <small>{{ skill.proficiency_label }}</small>
                        </div>
                    </div>
                </section>
            </div>

            <section class="sheet-section">
                <div class="sheet-section-heading"><div><p class="sheet-eyebrow">PROGRESIÓN</p><h2>Clases y niveles</h2></div></div>
                <div v-if="character.character_classes?.length" class="sheet-class-grid">
                    <article v-for="characterClass in character.character_classes" :key="characterClass.id" class="sheet-detail-card">
                        <h3>{{ characterClass.class }}</h3>
                        <p>Nivel {{ characterClass.level }} <span v-if="characterClass.is_primary">· Clase principal</span></p>
                        <p v-if="characterClass.hit_die">Dado de golpe: d{{ characterClass.hit_die }}</p>
                        <p v-if="characterClass.subclass_name">Subclase: <strong>{{ characterClass.subclass_name }}</strong></p>
                        <div v-if="classFeatures[characterClass.id]?.length" class="sheet-class-features">
                            <h4>Rasgos desbloqueados</h4>
                            <ul><li v-for="(feature, index) in classFeatures[characterClass.id]" :key="`${characterClass.id}-${index}`"><strong>{{ feature.name }}</strong><span v-if="feature.subclass"> · subclase</span><span v-if="feature.level"> · nivel {{ feature.level }}</span><p v-if="feature.desc">{{ Array.isArray(feature.desc) ? feature.desc.join(' ') : feature.desc }}</p></li></ul>
                        </div>
                        <div v-if="sheet.total_level < 20 && characterClass.level < 20" class="sheet-progression-tools">
                            <p>La subida se realiza mediante un asistente independiente, nivel a nivel.</p>
                            <button type="button" class="secondary-button" @click="openLevelUpWizard(characterClass)">Abrir menú de subida de nivel</button>
                        </div>
                        <details v-if="classMagic[characterClass.id]?.spellcasting" class="sheet-spell-manager">
                            <summary>Gestionar conjuros de {{ characterClass.class }}</summary>
                            <p>Selecciona los conjuros que conoce esta clase. En clases que preparan conjuros, marca aparte cuáles están preparados.</p>
                            <div class="sheet-spell-options">
                                <label v-for="spell in classMagic[characterClass.id].spells" :key="spell.index" class="sheet-spell-option">
                                    <input type="checkbox" :value="spell.index" v-model="classMagic[characterClass.id].selectedSpells">
                                    <span>{{ spell.name }} <small>{{ spell.level === 0 ? 'Truco' : `Nivel ${spell.level}` }}<template v-if="spell.school"> · {{ spell.school }}</template></small></span>
                                    <input v-if="classMagic[characterClass.id].spell_type === 'prepared' && spell.level > 0" type="checkbox" :value="spell.index" v-model="classMagic[characterClass.id].preparedSpells" title="Preparado" aria-label="Preparado">
                                </label>
                            </div>
                            <button type="button" class="secondary-button" :disabled="savingSpells === characterClass.id" @click="saveSpells(characterClass)">{{ savingSpells === characterClass.id ? 'Guardando...' : 'Guardar selección de conjuros' }}</button>
                        </details>
                    </article>
                </div>
                <p v-else class="sheet-empty">Este personaje todavía no tiene clases registradas.</p>

                <div v-if="sheet.total_level < 20" class="sheet-multiclass-panel">
                    <h3>Adquirir una nueva clase</h3>
                    <p>Se comprobarán los requisitos de multiclase de todas las clases del personaje. La nueva clase comenzará en nivel 1.</p>
                    <div class="sheet-multiclass-controls">
                        <select v-model="newClassIndex" aria-label="Nueva clase" @change="loadNewClassDetails">
                            <option value="">Selecciona una clase</option>
                            <option v-for="item in availableClasses" :key="item.index" :value="item.index" :disabled="character.character_classes?.some(current => current.class_index === item.index)">{{ item.name }}</option>
                        </select>
                        <label>Método de puntos de golpe
                            <select v-model="newClassHpMethod">
                                <option value="average">Promedio</option>
                                <option value="manual">Tirada manual</option>
                            </select>
                        </label>
                        <label v-if="newClassHpMethod === 'manual'">Resultado del dado
                            <select v-model.number="newClassHpRoll"><option v-for="pip in newClassHitDie" :key="pip" :value="pip">{{ pip }}</option></select>
                        </label>
                        <button type="button" class="secondary-button" :disabled="!newClassIndex || addingClass" @click="addMulticlass">{{ addingClass ? 'Añadiendo...' : 'Añadir multiclase' }}</button>
                    </div>
                </div>
                <p v-if="progressionError" class="error-message" role="alert">{{ progressionError }}</p>
                <div v-if="character.level_ups?.length" class="sheet-level-history">
                    <h3>Historial de subidas de nivel</h3>
                    <div v-for="levelUp in character.level_ups" :key="levelUp.id" class="sheet-list-row">
                        <span>
                            {{ levelUp.character_class?.class || 'Clase' }} · nivel {{ levelUp.class_level }}
                            <small v-if="levelUp.feat_name"> · Dote: {{ levelUp.feat_name }}</small>
                            <small v-if="levelUp.ability_score_increases?.length"> · {{ levelUp.ability_score_increases.map(item => `${item.ability} +${item.increase}`).join(', ') }}</small>
                        </span>
                        <strong>+{{ levelUp.total_hp_gained }} PG</strong>
                    </div>
                </div>
                <p class="sheet-note">Las subclases, rasgos y conjuros se consultan por nivel individual de clase. Los espacios de conjuro se calculan con las reglas de lanzador multiclase de 2014.</p>
            </section>

            <div class="sheet-two-columns">
                <section class="sheet-section">
                    <div class="sheet-section-heading"><div><p class="sheet-eyebrow">EQUIPO</p><h2>Inventario</h2></div></div>
                    <div v-if="character.inventory?.length" class="sheet-list">
                        <div v-for="item in character.inventory" :key="item.id" class="sheet-list-row sheet-inventory-row">
                            <span>{{ item.item_name }} <small>× {{ item.quantity }}</small><small v-if="item.is_equipped"> · Equipado</small></span>
                            <strong v-if="item.weight !== null">{{ item.weight }} kg</strong>
                        </div>
                    </div>
                    <p v-else class="sheet-empty">No hay objetos registrados.</p>
                    <div v-if="character.currency" class="sheet-currency">
                        <span v-for="coin in currencyEntries" :key="coin.key">{{ coin.value }} {{ coin.label }}</span>
                    </div>
                </section>

                <section class="sheet-section">
                    <div class="sheet-section-heading"><div><p class="sheet-eyebrow">MAGIA</p><h2>Conjuros</h2></div></div>
                    <div v-if="character.spells?.length" class="sheet-list">
                        <div v-for="spell in character.spells" :key="spell.id" class="sheet-list-row">
                            <span>{{ spell.spell_name }} <small v-if="spell.source_class_index">· {{ spell.source_class_index }}</small></span>
                            <small>{{ spell.is_prepared ? 'Preparado' : (spell.is_known ? 'Conocido' : 'No preparado') }}</small>
                        </div>
                    </div>
                    <p v-else class="sheet-empty">No hay conjuros registrados.</p>
                    <div v-if="spellcastingSummary" class="sheet-slots">
                        <h3>Espacios de conjuro multiclase</h3>
                        <p v-if="spellcastingSummary.multiclass_caster_level">Nivel efectivo de lanzador: {{ spellcastingSummary.multiclass_caster_level }}</p>
                        <div v-for="slot in spellcastingSummary.saved_slots || []" :key="slot.id" class="sheet-list-row">
                            <span>{{ slot.slot_type === 'pact' ? `Pacto · nivel ${slot.spell_level}` : `Nivel ${slot.spell_level}` }}</span>
                            <strong>{{ slot.total_slots - slot.used_slots }} / {{ slot.total_slots }}</strong>
                            <button type="button" class="secondary-button" @click="changeSlotUse(slot, -1)" :disabled="slot.used_slots <= 0">−</button>
                            <button type="button" class="secondary-button" @click="changeSlotUse(slot, 1)" :disabled="slot.used_slots >= slot.total_slots">+</button>
                        </div>
                    </div>
                </section>
            </div>

            <section class="sheet-section">
                <div class="sheet-section-heading"><div><p class="sheet-eyebrow">COMPETENCIAS</p><h2>Otras competencias</h2></div></div>
                <div class="sheet-proficiency-groups">
                    <div v-for="group in otherProficiencies" :key="group.key">
                        <h3>{{ group.label }}</h3>
                        <p v-if="!group.items.length" class="sheet-empty">Sin datos registrados.</p>
                        <ul v-else><li v-for="item in group.items" :key="item.id">{{ item.proficiency_name }}</li></ul>
                    </div>
                </div>
            </section>

            <section class="sheet-section">
                <div class="sheet-section-heading"><div><p class="sheet-eyebrow">TRASFONDO</p><h2>Personalidad e historia</h2></div></div>
                <div class="sheet-story-grid">
                    <article v-for="field in storyFields" :key="field.key" class="sheet-detail-card">
                        <h3>{{ field.label }}</h3><p>{{ character[field.key] || 'Sin especificar.' }}</p>
                    </article>
                </div>
                <article v-if="character.description" class="sheet-description"><h3>Descripción</h3><p>{{ character.description }}</p></article>
                <article v-if="character.notes" class="sheet-description"><h3>Notas</h3><p>{{ character.notes }}</p></article>
            </section>
        </template>
        <LevelUpWizard
            v-if="activeLevelUpClass && character"
            :character="character"
            :character-class="activeLevelUpClass"
            @close="activeLevelUpClass = null"
            @completed="completeLevelUp"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import LevelUpWizard from '../components/LevelUpWizard.vue';
import api from '../services/api';

const route = useRoute();
const character = ref(null);
const sheet = ref(null);
const loading = ref(true);
const error = ref('');
const availableClasses = ref([]);
const classFeatures = ref({});
const newClassIndex = ref('');
const newClassHitDie = ref(8);
const newClassHpMethod = ref('average');
const newClassHpRoll = ref(4);
const addingClass = ref(false);
const levelingClass = ref(null);
const activeLevelUpClass = ref(null);
const progressionError = ref('');
const classMagic = ref({});
const savingSubclass = ref(null);
const savingSpells = ref(null);
const hpMethods = ref({});
const hpRolls = ref({});
const spellcastingSummary = ref(null);

const currencyEntries = computed(() => {
    if (!character.value?.currency) return [];
    return [
        { key: 'copper', label: 'PC', value: character.value.currency.copper },
        { key: 'silver', label: 'PP', value: character.value.currency.silver },
        { key: 'electrum', label: 'PE', value: character.value.currency.electrum },
        { key: 'gold', label: 'PO', value: character.value.currency.gold },
        { key: 'platinum', label: 'Platino', value: character.value.currency.platinum },
    ];
});

const otherProficiencies = computed(() => {
    const labels = [
        { key: 'weapon', label: 'Armas' },
        { key: 'armor', label: 'Armaduras' },
        { key: 'tool', label: 'Herramientas' },
        { key: 'language', label: 'Idiomas' },
    ];
    return labels.map(group => ({
        ...group,
        items: (character.value?.proficiencies || []).filter(item => item.category === group.key && item.is_active),
    }));
});

const storyFields = [
    { key: 'personality_trait', label: 'Rasgos de personalidad' },
    { key: 'ideal', label: 'Ideales' },
    { key: 'bond', label: 'Vínculos' },
    { key: 'flaw', label: 'Defectos' },
];

function value(input) { return input === null || input === undefined || input === '' ? '—' : input; }
function signed(input) { return input === null || input === undefined ? '—' : `${input >= 0 ? '+' : ''}${input}`; }

async function loadCharacter() {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get(`/characters/${route.params.id}`);
        character.value = response.data;
        sheet.value = response.data.sheet;
        await Promise.all([loadClassFeatures(), loadClassMagic(), loadSpellcasting()]);
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar la ficha del personaje.';
    } finally {
        loading.value = false;
    }
}

async function loadClassFeatures() {
    const featureMap = {};
    for (const characterClass of character.value?.character_classes || []) {
        if (!characterClass.class_index) continue;
        const requests = Array.from({ length: Number(characterClass.level) }, (_, index) => index + 1);
        const results = await Promise.allSettled(requests.map(level =>
            api.get(`/dnd/classes/${characterClass.class_index}/levels/${level}`).then(response => ({ level, data: response.data }))
        ));
        let features = results.flatMap(result => result.status === 'fulfilled'
            ? (result.value.data.features || []).map(feature => ({ ...feature, level: result.value.level })) : []);
        if (characterClass.subclass_index) {
            const subclassResults = await Promise.allSettled(requests.map(level =>
                api.get(`/dnd/subclasses/${characterClass.subclass_index}/levels/${level}`).then(response => ({ level, data: response.data }))
            ));
            features = features.concat(subclassResults.flatMap(result => result.status === 'fulfilled'
                ? (result.value.data.features || []).map(feature => ({ ...feature, level: result.value.level, subclass: true })) : []));
        }
        featureMap[characterClass.id] = features.sort((a, b) => a.level - b.level);
    }
    classFeatures.value = featureMap;
}

async function loadClassMagic() {
    const result = {};
    for (const characterClass of character.value?.character_classes || []) {
        try {
            const subclassResponse = await api.get(`/characters/${character.value.id}/classes/${characterClass.id}/subclass-options`);
            const spellResponse = await api.get(`/characters/${character.value.id}/classes/${characterClass.id}/spell-options`);
            const selected = spellResponse.data.selected || [];
            result[characterClass.id] = {
                subclasses: subclassResponse.data.subclasses || [],
                subclass_level: subclassResponse.data.subclass_level || 3,
                pendingSubclass: '',
                spellcasting: !!spellResponse.data.spellcasting,
                spell_type: spellResponse.data.spell_type || (['bard', 'ranger', 'sorcerer', 'warlock'].includes(characterClass.class_index) ? 'known' : 'prepared'),
                spells: spellResponse.data.spells || [],
                selectedSpells: selected.map(item => item.spell_index),
                preparedSpells: selected.filter(item => item.is_prepared).map(item => item.spell_index),
            };
            hpMethods.value[characterClass.id] = 'average';
            hpRolls.value[characterClass.id] = Math.floor(Number(characterClass.hit_die) / 2);
        } catch (_) {
            result[characterClass.id] = { subclasses: [], spellcasting: false, spells: [], selectedSpells: [], preparedSpells: [] };
        }
    }
    classMagic.value = result;
}

async function loadSpellcasting() {
    try {
        const response = await api.get(`/characters/${character.value.id}/spellcasting`);
        spellcastingSummary.value = response.data;
    } catch (_) { spellcastingSummary.value = null; }
}

async function saveSubclass(characterClass) {
    const state = classMagic.value[characterClass.id];
    if (!state?.pendingSubclass) return;
    savingSubclass.value = characterClass.id;
    progressionError.value = '';
    try {
        await api.put(`/characters/${character.value.id}/classes/${characterClass.id}/subclass`, { subclass_index: state.pendingSubclass });
        await loadCharacter();
    } catch (err) {
        progressionError.value = Object.values(err.response?.data?.errors || {}).flat()[0] || err.response?.data?.message || 'No se pudo guardar la subclase.';
    } finally { savingSubclass.value = null; }
}

async function saveSpells(characterClass) {
    const state = classMagic.value[characterClass.id];
    savingSpells.value = characterClass.id;
    progressionError.value = '';
    try {
        const spells = state.selectedSpells.map(index => ({ index, prepared: state.spell_type !== 'known' && state.preparedSpells.includes(index) }));
        await api.put(`/characters/${character.value.id}/classes/${characterClass.id}/spells`, { spells });
        await loadCharacter();
    } catch (err) {
        progressionError.value = Object.values(err.response?.data?.errors || {}).flat()[0] || err.response?.data?.message || 'No se pudieron guardar los conjuros.';
    } finally { savingSpells.value = null; }
}

async function changeSlotUse(slot, delta) {
    const used = Number(slot.used_slots) + delta;
    try {
        await api.patch(`/characters/${character.value.id}/spell-slots/${slot.id}`, { used_slots: used });
        await loadSpellcasting();
    } catch (err) {
        progressionError.value = err.response?.data?.message || 'No se pudo actualizar el espacio de conjuro.';
    }
}

async function loadAvailableClasses() {
    try {
        const response = await api.get('/dnd/classes');
        availableClasses.value = response.data.results || response.data;
    } catch (_) {
        availableClasses.value = [];
    }
}

async function loadNewClassDetails() {
    if (!newClassIndex.value) return;
    try {
        const response = await api.get(`/dnd/classes/${newClassIndex.value}`);
        newClassHitDie.value = Number(response.data.hit_die || 8);
        newClassHpRoll.value = Math.floor(newClassHitDie.value / 2);
    } catch (_) {
        newClassHitDie.value = 8;
        newClassHpRoll.value = 4;
    }
}

async function addMulticlass() {
    if (!newClassIndex.value) return;
    addingClass.value = true;
    progressionError.value = '';
    try {
        await api.post(`/characters/${character.value.id}/classes`, {
            class_index: newClassIndex.value,
            hp_method: newClassHpMethod.value,
            ...(newClassHpMethod.value === 'manual' ? { base_hp_gained: newClassHpRoll.value } : {}),
        });
        newClassIndex.value = '';
        await loadCharacter();
    } catch (err) {
        progressionError.value = Object.values(err.response?.data?.errors || {}).flat()[0]
            || err.response?.data?.message || 'No se pudo añadir la nueva clase.';
    } finally {
        addingClass.value = false;
    }
}

function openLevelUpWizard(characterClass) {
    progressionError.value = '';
    activeLevelUpClass.value = characterClass;
}

async function completeLevelUp() {
    activeLevelUpClass.value = null;
    await loadCharacter();
}

async function levelUpClass(characterClass) {
    levelingClass.value = characterClass.id;
    progressionError.value = '';
    try {
        const state = classMagic.value[characterClass.id];
        const nextLevel = Number(characterClass.level) + 1;
        const needsSubclass = !characterClass.subclass_index && nextLevel >= (state?.subclass_level || 3);
        if (needsSubclass && !state?.pendingSubclass) {
            progressionError.value = 'Selecciona la subclase antes de alcanzar este nivel.';
            levelingClass.value = null;
            return;
        }
        const method = hpMethods.value[characterClass.id] || 'average';
        await api.post(`/characters/${character.value.id}/classes/${characterClass.id}/level-up`, {
            hp_method: method,
            ...(method === 'manual' ? { base_hp_gained: hpRolls.value[characterClass.id] } : {}),
            ...(needsSubclass ? { subclass_index: state.pendingSubclass } : {}),
        });
        await loadCharacter();
    } catch (err) {
        progressionError.value = Object.values(err.response?.data?.errors || {}).flat()[0]
            || err.response?.data?.message || 'No se pudo subir de nivel esta clase.';
    } finally {
        levelingClass.value = null;
    }
}

onMounted(async () => {
    await Promise.all([loadCharacter(), loadAvailableClasses()]);
});
</script>
