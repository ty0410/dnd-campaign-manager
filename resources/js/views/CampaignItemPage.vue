<template>
    <main class="item-page">
        <RouterLink class="back-link" :to="`/campaigns/${route.params.id}`"><AppIcon name="back" /> {{ campaignName || 'Campaña' }}</RouterLink>

        <p v-if="loading" class="state-message">Cargando...</p>
        <p v-if="error" class="error-message" role="alert">{{ error }}</p>
        <p v-if="flash" class="success-message" role="status">{{ flash }}</p>

        <template v-if="item">
            <header class="page-header page-header--split">
                <div>
                    <h1>{{ title }}</h1>
                    <p v-if="subtitle">{{ subtitle }}</p>
                </div>
                <div class="item-page-actions">
                    <button class="secondary-button" type="button" @click="editing = true"><AppIcon name="edit" /> Editar</button>
                    <button class="danger-button" type="button" @click="remove">Eliminar</button>
                </div>
            </header>

            <!-- Sesión -->
            <div v-if="type === 'session'" class="item-columns item-columns--single">
                <section class="item-card"><h2>Resumen</h2><p v-if="item.summary" class="sheet-text">{{ item.summary }}</p><p v-else class="sheet-empty">Sin resumen.</p></section>
                <section v-if="item.notes" class="item-card"><h2>Notas del Dungeon Master</h2><p class="sheet-text">{{ item.notes }}</p></section>
            </div>

            <!-- Lugar -->
            <div v-else-if="type === 'location'" class="item-columns item-columns--single">
                <section class="item-card"><h2>Descripción</h2><p v-if="item.description" class="sheet-text">{{ item.description }}</p><p v-else class="sheet-empty">Sin descripción.</p></section>
            </div>

            <!-- PNJ -->
            <div v-else-if="type === 'npc'" class="item-columns">
                <aside class="item-side">
                    <div class="npc-portrait">
                        <img v-if="item.image_url" :src="item.image_url" :alt="`Retrato de ${item.name}`">
                        <span v-else aria-hidden="true">{{ item.name?.slice(0, 1)?.toUpperCase() }}</span>
                    </div>
                    <div class="npc-portrait-actions">
                        <label class="secondary-button file-button">
                            {{ item.image_url ? 'Cambiar foto' : 'Subir foto' }}
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="visually-hidden" @change="uploadImage">
                        </label>
                        <button v-if="item.image_url" class="danger-button" type="button" @click="removeImage">Quitar</button>
                    </div>
                    <small>JPG, PNG o WebP. Máximo 2 MB.</small>

                    <dl class="sheet-facts sheet-facts--stack">
                        <div v-if="item.race"><dt>Raza</dt><dd>{{ item.race }}</dd></div>
                        <div v-if="item.role"><dt>Papel</dt><dd>{{ item.role }}</dd></div>
                        <div v-if="item.location"><dt>Lugar</dt><dd>{{ item.location }}</dd></div>
                    </dl>
                </aside>

                <div class="item-main">
                    <section class="item-card"><h2>Descripción</h2><p v-if="item.description" class="sheet-text">{{ item.description }}</p><p v-else class="sheet-empty">Sin descripción.</p></section>

                    <!-- Características personalizadas (bloque de criatura) -->
                    <section class="item-card">
                        <h2>Características</h2>
                        <p v-if="isMaster" class="muted">Añade las que quieras, como en una criatura del bestiario. Marca cuáles ven los jugadores.</p>
                        <ul v-if="attributes.length" class="attribute-list">
                            <li v-for="attribute in attributes" :key="attribute.id" class="attribute-row" :class="{ 'is-hidden': !attribute.is_visible }">
                                <template v-if="editingAttribute?.id === attribute.id">
                                    <input v-model.trim="editingAttribute.label" maxlength="100" aria-label="Nombre">
                                    <textarea v-model="editingAttribute.value" rows="2" maxlength="1000" aria-label="Valor"></textarea>
                                    <label class="check-row"><input v-model="editingAttribute.is_visible" type="checkbox"> Visible para los jugadores</label>
                                    <div class="attribute-actions">
                                        <button class="primary-button" type="button" @click="saveAttribute">Guardar</button>
                                        <button class="secondary-button" type="button" @click="editingAttribute = null">Cancelar</button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="attribute-copy"><strong>{{ attribute.label }}</strong><span>{{ attribute.value }}</span></div>
                                    <div v-if="isMaster" class="attribute-actions">
                                        <span class="tag" :class="attribute.is_visible ? 'tag--ok' : 'tag--off'">{{ attribute.is_visible ? 'Visible' : 'Oculta' }}</span>
                                        <button class="icon-button" type="button" :aria-label="`Editar ${attribute.label}`" @click="editingAttribute = { ...attribute }"><AppIcon name="edit" /></button>
                                        <button class="icon-button danger" type="button" :aria-label="`Eliminar ${attribute.label}`" @click="removeAttribute(attribute)"><AppIcon name="x" /></button>
                                    </div>
                                </template>
                            </li>
                        </ul>
                        <p v-else class="sheet-empty">{{ isMaster ? 'Todavía no hay características.' : 'El Dungeon Master no ha revelado ninguna característica.' }}</p>

                        <form v-if="isMaster" class="attribute-form" @submit.prevent="addAttribute">
                            <input v-model.trim="newAttribute.label" maxlength="100" required placeholder="Nombre (p. ej. Clase de armadura)" aria-label="Nombre de la característica">
                            <input v-model.trim="newAttribute.value" maxlength="1000" required placeholder="Valor (p. ej. 15)" aria-label="Valor">
                            <label class="check-row"><input v-model="newAttribute.is_visible" type="checkbox"> Visible para los jugadores</label>
                            <button class="secondary-button" type="submit" :disabled="savingAttribute"><AppIcon name="plus" /> Añadir característica</button>
                        </form>
                    </section>

                    <!-- Apuntes -->
                    <section class="item-card">
                        <h2>Mis apuntes</h2>
                        <p class="muted">Tus impresiones sobre este personaje. {{ isMaster ? '' : 'Solo las ves tú y el Dungeon Master.' }}</p>
                        <textarea v-model="myNote" rows="5" maxlength="3000" placeholder="¿Qué piensas de este personaje? ¿Te fías de él?" aria-label="Mis apuntes"></textarea>
                        <div class="attribute-actions">
                            <button class="primary-button" type="button" :disabled="savingNote" @click="saveNote">{{ savingNote ? 'Guardando...' : 'Guardar apunte' }}</button>
                            <small class="field-counter">{{ myNote.length }} / 3000</small>
                        </div>
                    </section>

                    <section v-if="isMaster" class="item-card">
                        <h2>Apuntes de los jugadores</h2>
                        <ul v-if="otherNotes.length" class="note-list">
                            <li v-for="note in otherNotes" :key="note.id" class="note-row">
                                <UserAvatar :user="note.user" />
                                <div><strong>@{{ note.user.username }}</strong><p class="sheet-text">{{ note.body }}</p></div>
                            </li>
                        </ul>
                        <p v-else class="sheet-empty">Ningún jugador ha escrito apuntes todavía.</p>
                    </section>
                </div>
            </div>

            <!-- Encuentro -->
            <div v-else-if="type === 'encounter'" class="item-columns item-columns--single">
                <section class="item-card">
                    <p><span class="status-badge" :class="`status-badge--${item.status || 'planned'}`">{{ ENCOUNTER_STATUSES[item.status] || ENCOUNTER_STATUSES.planned }}</span></p>
                    <h2>Descripción</h2>
                    <p v-if="item.description" class="sheet-text">{{ item.description }}</p><p v-else class="sheet-empty">Sin descripción.</p>
                </section>
                <section class="item-card">
                    <h2>Combatientes</h2>
                    <EncounterCombatants :encounter="item" :campaign-characters="campaignCharacters" @changed="load" />
                </section>
                <section v-if="monsterBlocks.length" class="item-card">
                    <h2>Estadísticas de los monstruos</h2>
                    <details v-for="monster in monsterBlocks" :key="monster.index" class="monster-details">
                        <summary>{{ monster.name }} <small>· CR {{ monster.challenge_rating }}</small></summary>
                        <MonsterStatBlock :monster="monster" />
                    </details>
                </section>
            </div>
        </template>

        <Transition name="modal">
            <CampaignItemModal v-if="editing && item" :type="type" :item="item" :campaign-id="route.params.id" @close="editing = false" @saved="onSaved" />
        </Transition>
    </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import AppIcon from '../components/AppIcon.vue';
import UserAvatar from '../components/UserAvatar.vue';
import CampaignItemModal from '../components/CampaignItemModal.vue';
import EncounterCombatants from '../components/EncounterCombatants.vue';
import MonsterStatBlock from '../components/MonsterStatBlock.vue';
import { ENCOUNTER_STATUSES, ITEM_TYPES, formatDate } from '../services/campaignItems';

const route = useRoute();
const router = useRouter();

const SINGULAR = { sessions: 'session', locations: 'location', npcs: 'npc', encounters: 'encounter' };
const type = computed(() => SINGULAR[route.params.type]);
const config = computed(() => ITEM_TYPES[type.value]);

const item = ref(null);
const campaignName = ref('');
const isMaster = ref(false);
const attributes = ref([]);
const notes = ref([]);
const campaignCharacters = ref([]);
const monsterBlocks = ref([]);
const loading = ref(true);
const error = ref('');
const flash = ref('');
const editing = ref(false);

const myNote = ref('');
const savingNote = ref(false);
const newAttribute = ref({ label: '', value: '', is_visible: true });
const editingAttribute = ref(null);
const savingAttribute = ref(false);

const title = computed(() => item.value?.[config.value.nameKey] ?? '');
const subtitle = computed(() => {
    const it = item.value || {};
    if (type.value === 'session') return formatDate(it.session_date);
    if (type.value === 'location') return it.type || '';
    return '';
});
const otherNotes = computed(() => notes.value.filter((note) => !note.mine));

async function load() {
    loading.value = true; error.value = '';
    const id = route.params.itemId;
    try {
        if (type.value === 'npc') {
            const { data } = await api.get(`/npcs/${id}/detail`);
            item.value = data.npc;
            campaignName.value = data.campaign.name;
            isMaster.value = data.is_master;
            attributes.value = data.custom_attributes;
            notes.value = data.notes;
            myNote.value = data.notes.find((note) => note.mine)?.body || '';
        } else {
            const { data } = await api.get(`/${config.value.endpoint}/${id}`);
            item.value = data;
            const campaign = await api.get(`/campaigns/${route.params.id}`);
            campaignName.value = campaign.data.name;
            if (type.value === 'encounter') {
                const characters = await api.get(`/campaigns/${route.params.id}/characters`);
                campaignCharacters.value = characters.data;
                await loadMonsters();
            }
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar esta página.';
    } finally { loading.value = false; }
}

async function loadMonsters() {
    const indexes = [...new Set((item.value?.combatants || []).map((combatant) => combatant.monster_index).filter(Boolean))];
    const results = await Promise.allSettled(indexes.map((index) => api.get(`/dnd/monsters/${index}`)));
    monsterBlocks.value = results.filter((result) => result.status === 'fulfilled').map((result) => result.value.data);
}

const notify = (text) => { flash.value = text; setTimeout(() => { flash.value = ''; }, 5000); };

async function onSaved() { editing.value = false; await load(); }

async function remove() {
    if (!window.confirm(`¿Eliminar «${title.value}»? Esta acción no se puede deshacer.`)) return;
    try {
        await api.delete(`/${config.value.endpoint}/${item.value.id}`);
        await router.replace(`/campaigns/${route.params.id}`);
    } catch (err) { error.value = err.response?.data?.message || 'No se pudo eliminar.'; }
}

// ---- PNJ -----------------------------------------------------------------
async function uploadImage(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) { error.value = 'La imagen no puede superar los 2 MB.'; return; }
    const data = new FormData();
    data.append('image', file);
    try {
        await api.post(`/npcs/${item.value.id}/image`, data, { headers: { 'Content-Type': undefined } });
        await load();
    } catch (err) { error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo subir la foto.'; }
}

async function removeImage() {
    try { await api.delete(`/npcs/${item.value.id}/image`); await load(); }
    catch (err) { error.value = err.response?.data?.message || 'No se pudo quitar la foto.'; }
}

async function saveNote() {
    savingNote.value = true;
    try { await api.put(`/npcs/${item.value.id}/note`, { body: myNote.value }); notify('Apunte guardado.'); await load(); }
    catch (err) { error.value = err.response?.data?.message || 'No se pudo guardar el apunte.'; }
    finally { savingNote.value = false; }
}

async function addAttribute() {
    savingAttribute.value = true;
    try {
        await api.post(`/npcs/${item.value.id}/attributes`, newAttribute.value);
        newAttribute.value = { label: '', value: '', is_visible: true };
        await load();
    } catch (err) { error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo añadir.'; }
    finally { savingAttribute.value = false; }
}

async function saveAttribute() {
    const { id, label, value, is_visible } = editingAttribute.value;
    try { await api.patch(`/npc-attributes/${id}`, { label, value, is_visible }); editingAttribute.value = null; await load(); }
    catch (err) { error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo guardar.'; }
}

async function removeAttribute(attribute) {
    if (!window.confirm(`¿Eliminar la característica «${attribute.label}»?`)) return;
    try { await api.delete(`/npc-attributes/${attribute.id}`); await load(); }
    catch (err) { error.value = err.response?.data?.message || 'No se pudo eliminar.'; }
}

watch(() => [route.params.type, route.params.itemId], load);
onMounted(load);
</script>
