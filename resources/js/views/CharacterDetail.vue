<template>
    <section class="character-sheet-page">
        <div class="sheet-topbar">
            <RouterLink class="sheet-back-link" to="/characters">← Mis personajes</RouterLink>
            <span v-if="character?.campaigns?.length" class="sheet-campaign">
                {{ character.campaigns.map(campaign => campaign.name).join(', ') }}
            </span>
        </div>

        <p v-if="flash" class="success-message" role="status">{{ flash }}</p>
        <p v-if="actionError" class="error-message" role="alert">{{ actionError }}</p>

        <p v-if="loading" class="sheet-state">Cargando ficha...</p>
        <div v-else-if="error" class="error-message">
            <p>{{ error }}</p>
            <button type="button" @click="loadCharacter()">Reintentar</button>
        </div>

        <template v-else-if="character && sheet">
            <!-- Cabecera: identidad, acciones y estadísticas principales -->
            <header class="character-sheet-header sheet-hero">
                <div class="sheet-portrait-wrap">
                    <div class="sheet-portrait">
                        <img v-if="character.image_url" :src="character.image_url" :alt="`Retrato de ${character.name}`">
                        <span v-else aria-hidden="true">{{ character.name?.slice(0, 1)?.toUpperCase() }}</span>
                    </div>
                    <button v-if="isOwner" type="button" class="portrait-join" aria-label="Unirse a una campaña" title="Unirse a una campaña" @click="showJoin = true"><AppIcon name="party" /></button>
                </div>

                <div class="sheet-hero-body">
                    <div class="sheet-hero-top">
                        <div class="sheet-identity">
                            <input v-if="editMode" v-model.trim="nameDraft" class="name-input" maxlength="255" aria-label="Nombre" @change="savePatch({ name: nameDraft })">
                            <h1 v-else>{{ character.name }}</h1>
                            <p class="sheet-subtitle">{{ [character.race, character.background].filter(Boolean).join(' · ') }}</p>
                            <div class="sheet-class-chips">
                                <span v-for="characterClass in character.character_classes" :key="characterClass.id" class="sheet-chip">{{ characterClass.class }} {{ characterClass.level }}</span>
                                <label v-if="editMode" class="sheet-chip sheet-chip--toggle"><input type="checkbox" :checked="character.inspiration" @change="savePatch({ inspiration: $event.target.checked })"> Inspiración</label>
                                <span v-else-if="character.inspiration" class="sheet-chip">★ Inspiración</span>
                            </div>
                        </div>

                        <div class="sheet-actions">
                            <button v-if="isOwner" type="button" class="secondary-button" :class="{ 'is-active': editMode }" :aria-pressed="editMode" @click="toggleEdit"><AppIcon name="edit" /> {{ editMode ? 'Listo' : 'Editar' }}</button>
                            <button v-if="isOwner && sheet.total_level < 20" type="button" class="primary-button" @click="openLevelChooser">Subir de nivel</button>
                            <button type="button" class="secondary-button" @click="exportJson">Exportar JSON</button>
                        </div>
                    </div>

                    <div class="sheet-stats">
                        <div class="sheet-stat"><strong>{{ sheet.total_level }}</strong><span>Nivel</span></div>

                        <div class="sheet-stat sheet-stat--hp">
                            <template v-if="editMode">
                                <div class="stat-inputs">
                                    <input type="number" min="0" max="9999" :value="character.current_hit_points ?? ''" aria-label="PG actuales" @change="savePatch({ current_hit_points: numberOrNull($event) })">
                                    <span aria-hidden="true">/</span>
                                    <input type="number" min="0" max="9999" :value="character.maximum_hit_points ?? ''" aria-label="PG máximos" @change="savePatch({ maximum_hit_points: numberOrNull($event) })">
                                    <input type="number" min="0" max="9999" :value="character.temporary_hit_points ?? ''" aria-label="PG temporales" placeholder="+" @change="savePatch({ temporary_hit_points: numberOrNull($event) })">
                                </div>
                            </template>
                            <template v-else>
                                <strong>{{ character.current_hit_points ?? '—' }} / {{ character.maximum_hit_points ?? '—' }}</strong>
                                <small v-if="character.temporary_hit_points">+{{ character.temporary_hit_points }} temp.</small>
                                <div v-if="isOwner" class="hp-controls">
                                    <button type="button" class="icon-button" aria-label="Quitar 1 punto de golpe" @click="changeHp(-1)">−</button>
                                    <button type="button" class="icon-button" aria-label="Recuperar 1 punto de golpe" @click="changeHp(1)">+</button>
                                </div>
                            </template>
                            <span>PG</span>
                        </div>

                        <div class="sheet-stat">
                            <strong>{{ sheet.armor_class.value }}</strong>
                            <small>{{ sheet.armor_class.base_label }}<template v-if="sheet.armor_class.shield"> + escudo</template></small>
                            <span>CA</span>
                            <input v-if="editMode" class="stat-bonus" type="number" min="-10" max="20" :value="character.ac_bonus" aria-label="Bonificador manual a la CA" title="Bonificador manual" @change="savePatch({ ac_bonus: numberOrZero($event) })">
                        </div>

                        <div class="sheet-stat is-rollable" role="button" tabindex="0" title="Tirar iniciativa" @click="rollCheck('Iniciativa', sheet.ability_scores.dexterity.modifier)" @keydown.enter.prevent="rollCheck('Iniciativa', sheet.ability_scores.dexterity.modifier)" @keydown.space.prevent="rollCheck('Iniciativa', sheet.ability_scores.dexterity.modifier)">
                            <strong>{{ signed(sheet.ability_scores.dexterity.modifier) }}</strong><span>Iniciativa</span>
                        </div>

                        <div class="sheet-stat"><strong>{{ signed(sheet.proficiency_bonus) }}</strong><span>Competencia</span></div>

                        <div class="sheet-stat">
                            <input v-if="editMode" class="stat-xp" type="number" min="0" :value="character.experience_points ?? 0" aria-label="Experiencia" @change="savePatch({ experience_points: numberOrZero($event) })">
                            <strong v-else>{{ character.experience_points ?? 0 }}</strong>
                            <span>XP</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="sheet-layout">
                <!-- Columna izquierda: características, salvaciones y habilidades -->
                <div class="sheet-col sheet-col--stats">
                    <section class="sheet-section">
                        <div class="sheet-section-heading"><h2>Características</h2></div>
                        <div class="sheet-ability-grid">
                            <article v-for="(ability, key) in sheet.ability_scores" :key="key" class="sheet-ability-card" :class="{ 'is-rollable': !editMode }" role="button" tabindex="0" :title="editMode ? '' : 'Tirar 1d20'" @click="!editMode && rollCheck(ability.name, ability.modifier)" @keydown.enter.prevent="!editMode && rollCheck(ability.name, ability.modifier)" @keydown.space.prevent="!editMode && rollCheck(ability.name, ability.modifier)">
                                <span>{{ ability.name }}</span>
                                <strong>{{ value(ability.effective_score) }}</strong>
                                <small>{{ signed(ability.modifier) }}</small>
                                <div v-if="editMode" class="ability-edit" @click.stop>
                                    <label>Base<input type="number" min="1" max="30" :value="ability.base_score" :aria-label="`${ability.name}: puntuación base`" @change="savePatch({ [key]: numberOrNull($event) ?? ability.base_score })"></label>
                                    <label>Bono<input type="number" min="-10" max="10" :value="ability.custom_bonus" :aria-label="`${ability.name}: bonificador`" @change="savePatch({ [`${key}_bonus`]: numberOrZero($event) })"></label>
                                </div>
                                <em v-else-if="ability.custom_bonus">{{ ability.base_score }} {{ signed(ability.custom_bonus) }}</em>
                            </article>
                        </div>
                    </section>

                    <section class="sheet-section">
                        <div class="sheet-section-heading"><h2>Salvaciones</h2></div>
                        <div class="sheet-list">
                            <div v-for="(save, key) in sheet.saving_throws" :key="key" class="sheet-list-row sheet-prof-row" :class="{ 'is-rollable': !editMode, 'is-editable': editMode }" role="button" tabindex="0" :title="editMode ? 'Cambiar competencia' : 'Tirar 1d20'" @click="onSaveClick(key, save)" @keydown.enter.prevent="onSaveClick(key, save)" @keydown.space.prevent="onSaveClick(key, save)">
                                <span class="prof-mark" aria-hidden="true">{{ stars(save.proficiency_level, editMode) }}</span>
                                <span class="prof-name">{{ save.name }}<span v-if="save.proficiency_level" class="visually-hidden"> (competente)</span></span>
                                <strong>{{ signed(save.modifier) }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="sheet-section">
                        <div class="sheet-section-heading"><h2>Habilidades</h2></div>
                        <div class="sheet-list">
                            <div v-for="skill in Object.values(sheet.skills)" :key="skill.index" class="sheet-list-row sheet-prof-row" :class="{ 'is-rollable': !editMode, 'is-editable': editMode }" role="button" tabindex="0" :title="editMode ? 'Cambiar competencia' : 'Tirar 1d20'" @click="onSkillClick(skill)" @keydown.enter.prevent="onSkillClick(skill)" @keydown.space.prevent="onSkillClick(skill)">
                                <span class="prof-mark" aria-hidden="true">{{ stars(skill.proficiency_level, editMode) }}</span>
                                <span class="prof-name">{{ skill.name }} <small>({{ skill.ability }})</small><span v-if="skill.proficiency_level" class="visually-hidden">{{ skill.proficiency_level >= 2 ? ' (pericia)' : ' (competente)' }}</span></span>
                                <strong>{{ signed(skill.modifier) }}</strong>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Columna central: clases, ataques e historia -->
                <div class="sheet-col sheet-col--main">
                    <section class="sheet-section">
                        <div class="sheet-section-heading"><h2>Clases</h2></div>
                        <div v-if="character.character_classes?.length" class="sheet-class-grid">
                    <article v-for="characterClass in character.character_classes" :key="characterClass.id" class="sheet-detail-card">
                        <h3>{{ characterClass.class }}</h3>
                        <p>Nivel {{ characterClass.level }}</p>
                        <p v-if="characterClass.subclass_name">Subclase: <strong>{{ characterClass.subclass_name }}</strong></p>
                        <div v-if="classFeatures[characterClass.id]?.length" class="sheet-class-features">
                            <h4>Rasgos</h4>
                            <ul><li v-for="(feature, index) in classFeatures[characterClass.id]" :key="`${characterClass.id}-${index}`"><strong>{{ feature.name }}</strong><span v-if="feature.subclass"> · subclase</span><span v-if="feature.level"> · nivel {{ feature.level }}</span><p v-if="feature.desc">{{ Array.isArray(feature.desc) ? feature.desc.join(' ') : feature.desc }}</p></li></ul>
                        </div>
                        <details v-if="classMagic[characterClass.id]?.spellcasting" class="sheet-spell-manager">
                            <summary>Gestionar conjuros de {{ characterClass.class }}</summary>
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
                        <p v-else class="sheet-empty">Sin clases registradas.</p>
                    </section>

                    <section class="sheet-section">
                        <div class="sheet-section-heading sheet-section-heading--action">
                            <h2>Ataques</h2>
                            <button v-if="isOwner" type="button" class="icon-button add-button" aria-label="Añadir ataque" title="Añadir ataque" @click="openAttack()"><AppIcon name="plus" /></button>
                        </div>
                        <ul v-if="character.attacks?.length" class="attack-list">
                            <li v-for="attack in character.attacks" :key="attack.id" class="attack-row">
                                <div class="attack-copy">
                                    <strong>{{ attack.name }}</strong>
                                    <small>{{ attackSummary(attack) }}</small>
                                </div>
                                <div class="attack-actions">
                                    <button v-if="attack.kind === 'attack'" type="button" class="secondary-button" @click="rollAttack(attack)">Ataque {{ signed(attackBonus(attack)) }}</button>
                                    <button v-if="attack.damage_dice" type="button" class="secondary-button" @click="rollDamage(attack)">{{ damageLabel(attack) }}</button>
                                    <button v-if="attack.damage_dice && attack.kind === 'attack'" type="button" class="secondary-button" title="Daño crítico: dados dobles" @click="rollDamage(attack, true)">Crít.</button>
                                    <template v-if="editMode && isOwner">
                                        <button type="button" class="icon-button" :aria-label="`Editar ${attack.name}`" @click="openAttack(attack)"><AppIcon name="edit" /></button>
                                        <button type="button" class="icon-button danger" :aria-label="`Eliminar ${attack.name}`" @click="removeAttack(attack)"><AppIcon name="trash" /></button>
                                    </template>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="sheet-empty">Sin ataques.</p>
                    </section>

                    <section class="sheet-section">
                        <div class="sheet-section-heading"><h2>Historia</h2></div>
                        <div class="sheet-story-grid">
                            <article v-for="field in storyFields" :key="field.key" class="sheet-detail-card">
                                <h3>{{ field.label }}</h3>
                                <textarea v-if="editMode" rows="3" maxlength="5000" :value="character[field.key] ?? ''" :aria-label="field.label" @change="savePatch({ [field.key]: $event.target.value })"></textarea>
                                <p v-else>{{ character[field.key] || '—' }}</p>
                            </article>
                        </div>
                        <article v-if="editMode || character.description" class="sheet-description">
                            <h3>Descripción</h3>
                            <textarea v-if="editMode" rows="4" maxlength="5000" :value="character.description ?? ''" aria-label="Descripción" @change="savePatch({ description: $event.target.value })"></textarea>
                            <p v-else>{{ character.description }}</p>
                        </article>
                        <article v-if="editMode || character.notes" class="sheet-description">
                            <h3>Notas</h3>
                            <textarea v-if="editMode" rows="4" maxlength="10000" :value="character.notes ?? ''" aria-label="Notas" @change="savePatch({ notes: $event.target.value })"></textarea>
                            <p v-else>{{ character.notes }}</p>
                        </article>
                    </section>
                </div>

                <!-- Columna derecha: magia, equipo y competencias -->
                <div class="sheet-col sheet-col--side">
                    <section class="sheet-section">
                        <div class="sheet-section-heading"><h2>Magia</h2></div>

                        <div v-if="slotRows.length" class="slot-list">
                            <div v-for="slot in slotRows" :key="slot.id" class="slot-row">
                                <span>{{ slot.label }}</span>
                                <div class="slot-dots" role="group" :aria-label="`Espacios de ${slot.label}`">
                                    <button v-for="n in slot.total_slots" :key="n" type="button" class="slot-dot" :class="{ 'is-full': n <= slot.available }" :aria-pressed="n <= slot.available" :aria-label="`Espacio ${n} de ${slot.label}: ${n <= slot.available ? 'disponible' : 'gastado'}`" :disabled="!isOwner" @click="setSlotAvailable(slot, n <= slot.available ? n - 1 : n)"></button>
                                </div>
                            </div>
                        </div>

                        <div v-if="spellGroups.length" class="spell-groups">
                            <div v-for="group in spellGroups" :key="group.level" class="spell-group">
                                <h3>{{ group.label }}</h3>
                                <ul class="spell-list">
                                    <li v-for="spell in group.spells" :key="spell.id" :class="{ 'is-unprepared': spell.spell_type === 'prepared' && !spell.is_prepared }">
                                        <span class="spell-mark" aria-hidden="true">{{ spell.spell_type === 'prepared' ? (spell.is_prepared ? '◆' : '◇') : '' }}</span>
                                        {{ spell.spell_name }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p v-else class="sheet-empty">Sin conjuros.</p>
                    </section>

                    <section class="sheet-section sheet-inventory">
                        <div class="sheet-section-heading sheet-section-heading--action">
                            <h2>Equipo</h2>
                            <button v-if="isOwner" type="button" class="icon-button add-button" aria-label="Añadir objeto" title="Añadir objeto" @click="showAddItem = true"><AppIcon name="plus" /></button>
                        </div>
                        <div v-if="character.inventory?.length" class="sheet-list">
                            <div v-for="item in character.inventory" :key="item.id" class="sheet-list-row sheet-inventory-row">
                                <span class="inventory-name">{{ item.item_name }}<small v-if="isStackable(item) || item.quantity > 1"> × {{ item.quantity }}</small></span>
                                <div v-if="isOwner" class="inventory-actions">
                                    <template v-if="isStackable(item)">
                                        <button type="button" class="icon-button" :aria-label="`Quitar una unidad de ${item.item_name}`" :disabled="item.quantity <= 1" @click="changeQuantity(item, -1)">−</button>
                                        <button type="button" class="icon-button" :aria-label="`Añadir una unidad de ${item.item_name}`" @click="changeQuantity(item, 1)">+</button>
                                    </template>
                                    <label v-if="isEquippable(item)" class="check-row inventory-equip"><input type="checkbox" :checked="item.is_equipped" @change="toggleEquipped(item, $event.target.checked)"> Equipado</label>
                                    <button v-if="editMode" type="button" class="icon-button danger" :aria-label="`Eliminar ${item.item_name}`" title="Eliminar" @click="removeItem(item)"><AppIcon name="trash" /></button>
                                </div>
                                <small v-else-if="item.is_equipped">Equipado</small>
                            </div>
                        </div>
                        <p v-else class="sheet-empty">Sin objetos.</p>
                        <p v-if="inventoryError" class="error-message" role="alert">{{ inventoryError }}</p>
                        <div v-if="character.currency" class="sheet-currency">
                            <span v-for="coin in currencyEntries" :key="coin.key">{{ coin.value }} {{ coin.label }}</span>
                        </div>
                    </section>

                    <section class="sheet-section">
                        <div class="sheet-section-heading"><h2>Competencias</h2></div>
                        <div v-for="group in otherProficiencies" :key="group.key" class="proficiency-group">
                            <h3>{{ group.label }}</h3>
                            <ul v-if="group.items.length" class="proficiency-chips">
                                <li v-for="item in group.items" :key="item.id" class="proficiency-chip">
                                    <span aria-hidden="true">★</span> {{ item.proficiency_name }}
                                    <button v-if="editMode" type="button" class="chip-remove" :aria-label="`Quitar ${item.proficiency_name}`" @click="removeProficiency(item)">×</button>
                                </li>
                            </ul>
                            <p v-else class="sheet-empty">—</p>
                            <form v-if="editMode" class="proficiency-add" @submit.prevent="addProficiency(group.key)">
                                <input v-model.trim="profDrafts[group.key]" maxlength="120" :placeholder="`Añadir a ${group.label.toLowerCase()}`" :aria-label="`Añadir a ${group.label.toLowerCase()}`">
                                <button type="submit" class="icon-button" aria-label="Añadir" :disabled="!profDrafts[group.key]"><AppIcon name="plus" /></button>
                            </form>
                        </div>
                    </section>
                </div>
            </div>
        </template>

        <Transition name="modal">
            <div v-if="showLevelChooser && character && sheet" class="modal-backdrop" @click.self="showLevelChooser = false">
                <section class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="levelup-title">
                    <button class="modal-close" type="button" aria-label="Cerrar" @click="showLevelChooser = false"><AppIcon name="x" /></button>
                    <h2 id="levelup-title">Subir de nivel</h2>
                    <p class="modal-lead">Nivel {{ sheet.total_level }} de 20</p>

                    <div v-if="levelableClasses.length" class="levelup-options">
                        <button v-for="characterClass in levelableClasses" :key="characterClass.id" type="button" class="levelup-option" @click="chooseLevelUpClass(characterClass)">
                            <strong>{{ characterClass.class }}</strong>
                            <small>Nivel {{ characterClass.level }} → {{ characterClass.level + 1 }}</small>
                        </button>
                    </div>

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
                        <button type="button" class="secondary-button" :disabled="!newClassIndex || addingClass" @click="addMulticlassAndClose">{{ addingClass ? 'Añadiendo...' : 'Añadir multiclase' }}</button>
                    </div>
                </div>
                    <p v-if="progressionError" class="error-message" role="alert">{{ progressionError }}</p>
                </section>
            </div>
        </Transition>

        <Transition name="modal">
            <AddItemModal v-if="showAddItem && character" :character-id="character.id" @close="showAddItem = false" @saved="onItemSaved" />
        </Transition>
        <LevelUpWizard
            v-if="activeLevelUpClass && character"
            :character="character"
            :character-class="activeLevelUpClass"
            @close="activeLevelUpClass = null"
            @completed="completeLevelUp"
        />
        <Transition name="modal">
            <JoinCampaignModal v-if="showJoin && character" :character-id="character.id" :character-name="character.name" @close="showJoin = false" @sent="onJoinSent" />
        </Transition>

        <Transition name="modal">
            <AttackModal v-if="showAttack && character && sheet" :character-id="character.id" :attack="editingAttack" :sheet="sheet" @close="showAttack = false" @saved="onAttackSaved" />
        </Transition>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import LevelUpWizard from '../components/LevelUpWizard.vue';
import AppIcon from '../components/AppIcon.vue';
import AddItemModal from '../components/AddItemModal.vue';
import JoinCampaignModal from '../components/JoinCampaignModal.vue';
import AttackModal from '../components/AttackModal.vue';
import api from '../services/api';
import { getCurrentUser } from '../services/auth';
import { rollD20, rollDice } from '../services/dice';

const route = useRoute();
const character = ref(null);
const me = ref(null);

// Tirada de 1d20 con el modificador indicado; el resultado sale en un diálogo.
const rollCheck = (label, modifier) => rollD20({ label: `${label} — ${character.value?.name || ''}`.replace(/ — $/, ''), modifier });
const isOwner = computed(() => !!me.value && !!character.value && Number(me.value.id) === Number(character.value.user_id));
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
const showLevelChooser = ref(false);
const showAddItem = ref(false);
const showJoin = ref(false);
const showAttack = ref(false);
const editingAttack = ref(null);
const editMode = ref(false);
const nameDraft = ref('');
const flash = ref('');
const actionError = ref('');
const profDrafts = ref({ weapon: '', armor: '', tool: '', language: '' });
const inventoryError = ref('');
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

async function loadCharacter(silent = false) {
    // En recargas silenciosas la ficha no se oculta (p. ej. al editar el inventario).
    if (silent !== true) loading.value = true;
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

const levelableClasses = computed(() => (sheet.value?.total_level < 20
    ? (character.value?.character_classes || []).filter((entry) => entry.level < 20)
    : []));

function openLevelChooser() {
    progressionError.value = '';
    showLevelChooser.value = true;
}

function chooseLevelUpClass(characterClass) {
    showLevelChooser.value = false;
    openLevelUpWizard(characterClass);
}

// Misma lógica de multiclase de siempre; al terminar bien se cierra la ventana.
async function addMulticlassAndClose() {
    progressionError.value = '';
    await addMulticlass();
    if (!progressionError.value) showLevelChooser.value = false;
}

// Exporta los datos del personaje (sin datos privados del usuario) a un archivo JSON.
function exportJson() {
    const { user, ...data } = character.value;
    const payload = {
        format: 'dnd-campaign-manager/character@1',
        exported_at: new Date().toISOString(),
        owner: user?.username || null,
        character: data,
        sheet: sheet.value,
    };

    const slug = String(character.value.name || 'personaje').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'personaje';
    const url = URL.createObjectURL(new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `${slug}.json`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

// Inventario ---------------------------------------------------------------
async function inventoryAction(request) {
    inventoryError.value = '';
    try { await request(); await loadCharacter(true); }
    catch (err) { inventoryError.value = err.response?.data?.message || 'No se pudo actualizar el inventario.'; }
}

const changeQuantity = (item, delta) => inventoryAction(() => api.patch(`/characters/${character.value.id}/inventory/${item.id}`, { quantity: Math.max(1, item.quantity + delta) }));
const toggleEquipped = (item, equipped) => inventoryAction(() => api.patch(`/characters/${character.value.id}/inventory/${item.id}`, { is_equipped: equipped }));
const removeItem = (item) => (window.confirm(`¿Eliminar «${item.item_name}» del inventario?`)
    ? inventoryAction(() => api.delete(`/characters/${character.value.id}/inventory/${item.id}`))
    : null);

async function onItemSaved() {
    showAddItem.value = false;
    await loadCharacter(true);
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
    getCurrentUser().then((user) => { me.value = user; }).catch(() => {});
    await Promise.all([loadCharacter(), loadAvailableClasses()]);
});

// Edición en la propia ficha ------------------------------------------------
const numberOrNull = (event) => (event.target.value === '' ? null : Number(event.target.value));
const numberOrZero = (event) => (event.target.value === '' ? 0 : Number(event.target.value));

function toggleEdit() {
    editMode.value = !editMode.value;
    nameDraft.value = character.value?.name || '';
}

function fail(err, fallback) {
    actionError.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || fallback;
}

async function savePatch(patch) {
    actionError.value = '';
    try {
        await api.patch(`/characters/${character.value.id}/details`, patch);
    } catch (err) {
        fail(err, 'No se pudo guardar el cambio.');
    }
    await loadCharacter(true);
}

function changeHp(delta) {
    const max = character.value.maximum_hit_points ?? 9999;
    const current = character.value.current_hit_points ?? max;
    savePatch({ current_hit_points: Math.min(max, Math.max(0, current + delta)) });
}

// Competencias: ★ competente, ★★ pericia (doble competencia).
const stars = (level, editable) => (level >= 2 ? '★★' : level === 1 ? '★' : (editable ? '☆' : ''));

async function setProficiencyLevel(category, index, level) {
    actionError.value = '';
    try {
        await api.put(`/characters/${character.value.id}/proficiency-level`, { category, index, level });
    } catch (err) {
        fail(err, 'No se pudo cambiar la competencia.');
    }
    await loadCharacter(true);
}

function onSaveClick(key, save) {
    if (editMode.value) setProficiencyLevel('saving_throw', key, save.proficiency_level ? 0 : 1);
    else rollCheck(`Salvación de ${save.name}`, save.modifier);
}

function onSkillClick(skill) {
    if (editMode.value) setProficiencyLevel('skill', skill.index, (skill.proficiency_level + 1) % 3);
    else rollCheck(skill.name, skill.modifier);
}

async function addProficiency(category) {
    actionError.value = '';
    try {
        await api.post(`/characters/${character.value.id}/proficiencies`, { category, name: profDrafts.value[category] });
        profDrafts.value[category] = '';
    } catch (err) {
        fail(err, 'No se pudo añadir la competencia.');
    }
    await loadCharacter(true);
}

async function removeProficiency(item) {
    actionError.value = '';
    try {
        await api.delete(`/characters/${character.value.id}/proficiencies/${item.id}`);
    } catch (err) {
        fail(err, 'No se pudo quitar la competencia.');
    }
    await loadCharacter(true);
}

// Equipo ---------------------------------------------------------------------
const STACKABLE_NAME = /(arrow|bolt|bullet|dart|needle|potion|vial|flask|ration|torch|oil|candle|chalk|ink|parchment|paper|piton|caltrops|antitoxin|flecha|virote|poci)/i;

// Solo se cuentan los objetos que suelen poseerse en cantidad (munición, pociones...).
const isStackable = (item) => (item.equipment_data ? !!item.equipment_data.stackable : (item.quantity > 1 || STACKABLE_NAME.test(item.item_name)));
const isEquippable = (item) => item.equipment_data == null
    || ['Armor', 'Weapon'].includes(item.equipment_data.category)
    || !!item.equipment_data.armor_category;

// Ataques ----------------------------------------------------------------------
const ABILITY_SHORT = { strength: 'FUE', dexterity: 'DES', constitution: 'CON', intelligence: 'INT', wisdom: 'SAB', charisma: 'CAR' };
const abilityMod = (key) => sheet.value.ability_scores[key]?.modifier ?? 0;
const attackBonus = (attack) => abilityMod(attack.ability) + (attack.add_proficiency ? sheet.value.proficiency_bonus : 0) + Number(attack.bonus || 0);
const attackDc = (attack) => 8 + sheet.value.proficiency_bonus + abilityMod(attack.ability) + Number(attack.bonus || 0);

function damageParts(attack) {
    const match = /^(\d+)d(\d+)$/.exec(attack.damage_dice || '');
    if (!match) return null;
    return { count: Number(match[1]), sides: Number(match[2]), modifier: Number(attack.damage_bonus || 0) + (attack.damage_add_ability ? abilityMod(attack.ability) : 0) };
}

function damageLabel(attack) {
    const parts = damageParts(attack);
    return parts ? `Daño ${attack.damage_dice}${parts.modifier ? ` ${signed(parts.modifier)}` : ''}` : 'Daño';
}

function attackSummary(attack) {
    const parts = [];
    if (attack.kind === 'save') parts.push(`Salvación ${ABILITY_SHORT[attack.save_ability] || ''} · CD ${attackDc(attack)}`);
    const damage = damageParts(attack);
    if (damage) parts.push(`${attack.damage_dice}${damage.modifier ? ` ${signed(damage.modifier)}` : ''} ${attack.damage_type || ''}`.trim());
    return parts.join(' · ') || ABILITY_SHORT[attack.ability];
}

const rollAttack = (attack) => rollD20({ label: `${attack.name} · ataque`, modifier: attackBonus(attack) });

function rollDamage(attack, critical = false) {
    const parts = damageParts(attack);
    if (!parts) return;
    rollDice({ label: `${attack.name} · daño${critical ? ' crítico' : ''}`, count: parts.count * (critical ? 2 : 1), sides: parts.sides, modifier: parts.modifier });
}

function openAttack(attack = null) {
    editingAttack.value = attack;
    showAttack.value = true;
}

async function onAttackSaved() {
    showAttack.value = false;
    await loadCharacter(true);
}

async function removeAttack(attack) {
    if (!window.confirm(`¿Eliminar el ataque «${attack.name}»?`)) return;
    actionError.value = '';
    try {
        await api.delete(`/characters/${character.value.id}/attacks/${attack.id}`);
    } catch (err) {
        fail(err, 'No se pudo eliminar el ataque.');
    }
    await loadCharacter(true);
}

// Magia: espacios (círculos) y listado por nivel ---------------------------------
const slotRows = computed(() => (spellcastingSummary.value?.saved_slots || [])
    .filter((slot) => slot.total_slots > 0)
    .map((slot) => ({ ...slot, available: slot.total_slots - slot.used_slots, label: slot.slot_type === 'pact' ? `Pacto ${slot.spell_level}` : `Nv ${slot.spell_level}` }))
    .sort((a, b) => a.spell_level - b.spell_level));

async function setSlotAvailable(slot, available) {
    actionError.value = '';
    try {
        await api.patch(`/characters/${character.value.id}/spell-slots/${slot.id}`, { used_slots: slot.total_slots - available });
        await loadSpellcasting();
    } catch (err) {
        fail(err, 'No se pudo actualizar el espacio de conjuro.');
    }
}

const spellGroups = computed(() => {
    const groups = new Map();
    for (const spell of character.value?.spells || []) {
        const level = spell.spell_level ?? 0;
        if (!groups.has(level)) groups.set(level, []);
        groups.get(level).push(spell);
    }
    return [...groups.entries()]
        .sort((a, b) => a[0] - b[0])
        .map(([level, spells]) => ({
            level,
            label: level === 0 ? 'Trucos' : `Nivel ${level}`,
            spells: spells.sort((a, b) => a.spell_name.localeCompare(b.spell_name, 'es')),
        }));
});

function onJoinSent(message) {
    showJoin.value = false;
    flash.value = message;
    setTimeout(() => { flash.value = ''; }, 7000);
}
</script>
