<template>
    <main class="campaign-dashboard">
        <div v-if="loading && !campaign" aria-busy="true" aria-label="Cargando campaña">
            <div class="skeleton skeleton-hero"></div>
            <div class="skeleton-panels">
                <div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div>
            </div>
        </div>
        <p v-if="error" class="error-message" role="alert">{{ error }}</p>
        <p v-if="flash" class="success-message" role="status">{{ flash }}</p>

        <template v-if="campaign">
            <header class="campaign-hero" :style="campaign.image_url ? { '--cover': `url('${campaign.image_url}')` } : {}">
                <div class="hero-copy">
                    <RouterLink to="/campaigns" class="back-link"><AppIcon name="back" /> Mis campañas</RouterLink>
                    <h1>{{ campaign.name }}</h1>
                    <p class="hero-description">{{ campaign.description || 'Esta campaña todavía no tiene descripción.' }}</p>
                    <div v-if="campaign.master" class="hero-meta">
                        <span class="hero-chip"><AppIcon name="crown" /> Dungeon Master: {{ campaign.master.name }}</span>
                    </div>
                </div>
                <div v-if="isMaster" class="hero-actions">
                    <button class="ghost-button" :class="{ 'is-active': editMode }" type="button" :aria-pressed="editMode" @click="editMode = !editMode"><AppIcon name="edit" /> {{ editMode ? 'Listo' : 'Editar campaña' }}</button>
                    <Transition name="tools">
                        <div v-if="editMode" class="hero-edit-tools">
                            <button class="ghost-button" type="button" @click="showInfo = true">Datos y portada</button>
                            <button class="ghost-button danger-button" type="button" @click="deleteCampaign"><AppIcon name="trash" /> Eliminar</button>
                        </div>
                    </Transition>
                </div>
            </header>

            <div class="campaign-layout">
                <div class="campaign-panels stagger">
                    <section class="panel">
                        <header class="panel-heading">
                            <div><h2>Sesiones</h2></div>
                            <div class="panel-heading-tools">
                                <button class="add-button" type="button" aria-label="Añadir sesión" @click="openAdd('session')"><AppIcon name="plus" /></button>
                                <Transition name="tools">
                                <div v-if="editMode && selectedFor('session')" class="panel-edit-tools">
                                    <button type="button" class="add-button" aria-label="Editar la sesión seleccionada" title="Editar" @click="openEdit('session', selectedFor('session').item)"><AppIcon name="edit" /></button>
                                    <button type="button" class="add-button add-button--danger" aria-label="Eliminar el elemento seleccionado" title="Eliminar" @click="deleteItem('session', selectedFor('session').item)"><AppIcon name="trash" /></button>
                                </div>
                            </Transition>
                            </div>
                        </header>
                        <div v-if="sessions.length" class="item-list">
                            <article v-for="session in sessions" :key="session.id" class="dashboard-item selectable" :class="{ 'is-selected': isSelected('session', session.id) }" role="button" tabindex="0" :aria-pressed="isSelected('session', session.id)" @click="select('session', session.id)" @keydown.enter.prevent="select('session', session.id)" @keydown.space.prevent="select('session', session.id)">
                                <div class="item-symbol"><AppIcon name="clock" /></div>
                                <div class="item-copy">
                                    <h3>{{ session.title }}</h3>
                                    <p v-if="session.session_date" class="item-meta">{{ formatDate(session.session_date) }}</p>
                                    <p v-if="session.summary" class="item-excerpt">{{ session.summary }}</p>
                                </div>
                            </article>
                        </div>
                        <p v-else class="empty-hint">Todavía no hay sesiones. Añade la primera con el botón +.</p>
                    </section>

                    <section class="panel">
                        <header class="panel-heading">
                            <div><h2>Lugares</h2></div>
                            <div class="panel-heading-tools">
                                <button class="add-button" type="button" aria-label="Añadir lugar" @click="openAdd('location')"><AppIcon name="plus" /></button>
                                <Transition name="tools">
                                <div v-if="editMode && selectedFor('location')" class="panel-edit-tools">
                                    <button type="button" class="add-button" aria-label="Editar el lugar seleccionado" title="Editar" @click="openEdit('location', selectedFor('location').item)"><AppIcon name="edit" /></button>
                                    <button type="button" class="add-button add-button--danger" aria-label="Eliminar el elemento seleccionado" title="Eliminar" @click="deleteItem('location', selectedFor('location').item)"><AppIcon name="trash" /></button>
                                </div>
                            </Transition>
                            </div>
                        </header>
                        <div v-if="locations.length" class="item-list">
                            <article v-for="location in locations" :key="location.id" class="dashboard-item selectable" :class="{ 'is-selected': isSelected('location', location.id) }" role="button" tabindex="0" :aria-pressed="isSelected('location', location.id)" @click="select('location', location.id)" @keydown.enter.prevent="select('location', location.id)" @keydown.space.prevent="select('location', location.id)">
                                <div class="item-symbol"><AppIcon name="pin" /></div>
                                <div class="item-copy">
                                    <h3>{{ location.name }}</h3>
                                    <p v-if="location.type" class="item-meta">{{ location.type }}</p>
                                    <p v-if="location.description" class="item-excerpt">{{ location.description }}</p>
                                </div>
                            </article>
                        </div>
                        <p v-else class="empty-hint">Todavía no hay lugares registrados.</p>
                    </section>

                    <section class="panel">
                        <header class="panel-heading">
                            <div><h2>Personajes no jugadores</h2></div>
                            <div class="panel-heading-tools">
                                <button class="add-button" type="button" aria-label="Añadir PNJ" @click="openAdd('npc')"><AppIcon name="plus" /></button>
                                <Transition name="tools">
                                <div v-if="editMode && selectedFor('npc')" class="panel-edit-tools">
                                    <button type="button" class="add-button" aria-label="Editar el PNJ seleccionado" title="Editar" @click="openEdit('npc', selectedFor('npc').item)"><AppIcon name="edit" /></button>
                                    <button type="button" class="add-button add-button--danger" aria-label="Eliminar el elemento seleccionado" title="Eliminar" @click="deleteItem('npc', selectedFor('npc').item)"><AppIcon name="trash" /></button>
                                </div>
                            </Transition>
                            </div>
                        </header>
                        <div v-if="npcs.length" class="item-list">
                            <article v-for="npc in npcs" :key="npc.id" class="dashboard-item selectable" :class="{ 'is-selected': isSelected('npc', npc.id) }" role="button" tabindex="0" :aria-pressed="isSelected('npc', npc.id)" @click="select('npc', npc.id)" @keydown.enter.prevent="select('npc', npc.id)" @keydown.space.prevent="select('npc', npc.id)">
                                <div class="item-symbol" :class="{ 'item-symbol--photo': npc.image_url }"><img v-if="npc.image_url" :src="npc.image_url" :alt="`Retrato de ${npc.name}`" loading="lazy"><AppIcon v-else name="npc" /></div>
                                <div class="item-copy">
                                    <h3>{{ npc.name }}</h3>
                                    <p v-if="npc.race || npc.role" class="item-meta">{{ [npc.race, npc.role].filter(Boolean).join(' · ') }}</p>
                                    <p v-if="npc.description" class="item-excerpt">{{ npc.description }}</p>
                                </div>
                            </article>
                        </div>
                        <p v-else class="empty-hint">Todavía no hay PNJ en esta campaña.</p>
                    </section>

                    <section class="panel">
                        <header class="panel-heading">
                            <div><h2>Personajes</h2></div>
                            <div class="panel-heading-tools">
                                <button class="add-button" type="button" aria-label="Añadir personaje existente" @click="openCharacterModal"><AppIcon name="plus" /></button>
                                <Transition name="tools">
                                    <div v-if="editMode && selectedFor('character')" class="panel-edit-tools">
                                        <RouterLink v-if="isMine(selectedFor('character').item)" class="add-button" :to="`/characters/${selectedFor('character').item.id}`" aria-label="Abrir la ficha del personaje seleccionado" title="Abrir ficha"><AppIcon name="edit" /></RouterLink>
                                        <button v-if="canRemove(selectedFor('character').item)" type="button" class="add-button add-button--danger" aria-label="Retirar el personaje seleccionado" title="Retirar de la campaña" @click="removeCharacter(selectedFor('character').item)"><AppIcon name="trash" /></button>
                                    </div>
                                </Transition>
                            </div>
                        </header>
                        <div v-if="characters.length" class="character-list">
                            <div v-for="character in characters" :key="character.id" class="character-row" :class="{ 'is-selected': isSelected('character', character.id) }">
                                <div class="character-link selectable" role="button" tabindex="0" :aria-pressed="isSelected('character', character.id)" @click="select('character', character.id)" @keydown.enter.prevent="select('character', character.id)" @keydown.space.prevent="select('character', character.id)">
                                    <span class="character-avatar">
                                        <img v-if="character.image_url" :src="character.image_url" :alt="`Retrato de ${character.name}`" loading="lazy">
                                        <template v-else>{{ initial(character.name) }}</template>
                                    </span>
                                    <span class="character-info">
                                        <strong>{{ character.name }}</strong>
                                        <small>
                                            {{ character.race || 'Sin raza' }} · Nivel {{ character.level }}
                                            <span v-if="ownerLabel(character)" class="owner-chip" :class="{ 'owner-chip--mine': isMine(character) }">{{ ownerLabel(character) }}</span>
                                        </small>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="empty-hint">Todavía no hay personajes asignados a esta campaña.</p>
                        <RouterLink class="create-character-link" :to="{ name: 'character-create', query: { campaign_id: campaign.id } }">Crear un personaje nuevo <AppIcon name="external" /></RouterLink>
                    </section>

                    <section v-if="isMaster" class="panel panel-wide recruit-panel">
                        <header class="panel-heading">
                            <div><h2>Código y solicitudes</h2></div>
                        </header>
                        <div class="recruit-grid">
                            <div class="invite-box">
                                <p class="invite-label">Código de la campaña</p>
                                <div class="invite-code-row">
                                    <code class="invite-code">{{ campaign.invite_code || '—' }}</code>
                                    <button class="secondary-button" type="button" :disabled="!campaign.invite_code" @click="copyCode">{{ copied ? 'Copiado' : 'Copiar' }}</button>
                                </div>
                                <p class="invite-help">Compártelo con tus jugadores: lo usan en «Mis personajes → Unirse a campaña». Tú apruebas cada solicitud.</p>
                                <button class="danger-button" type="button" @click="renewCode">Generar código nuevo</button>
                            </div>
                            <div class="pending-box">
                                <p class="invite-label">Solicitudes pendientes</p>
                                <ul v-if="pendingRequests.length" class="request-list">
                                    <li v-for="entry in pendingRequests" :key="entry.id" class="request-row">
                                        <div class="request-copy">
                                            <template v-if="entry.direction === 'player_request'">
                                                <strong>{{ entry.requester?.name || 'Un jugador' }} quiere añadir a «{{ entry.character?.name }}»</strong>
                                                <small>Nivel {{ entry.character?.level }} · Esperando tu aprobación.</small>
                                            </template>
                                            <template v-else>
                                                <strong>Invitación a «{{ entry.character?.name }}»</strong>
                                                <small>Esperando que {{ entry.character?.user?.name || 'su dueño' }} la acepte.</small>
                                            </template>
                                        </div>
                                        <div class="request-actions">
                                            <template v-if="entry.direction === 'player_request'">
                                                <button class="primary-button" type="button" :disabled="busyRequest === entry.id" @click="answerRequest(entry, 'approve')">Aprobar</button>
                                                <button class="secondary-button" type="button" :disabled="busyRequest === entry.id" @click="answerRequest(entry, 'reject')">Rechazar</button>
                                            </template>
                                            <button v-else class="danger-button" type="button" :disabled="busyRequest === entry.id" @click="cancelInvitation(entry)">Retirar</button>
                                        </div>
                                    </li>
                                </ul>
                                <p v-else class="empty-hint">No hay solicitudes pendientes.</p>
                            </div>
                        </div>
                    </section>

                    <section class="panel panel-wide">
                        <header class="panel-heading">
                            <div><h2>Encuentros de combate</h2></div>
                            <div class="panel-heading-tools">
                                <button class="add-button" type="button" aria-label="Crear encuentro de combate" @click="showWizard = true"><AppIcon name="plus" /></button>
                                <Transition name="tools">
                                <div v-if="editMode && selectedFor('encounter')" class="panel-edit-tools">
                                    <button type="button" class="add-button" aria-label="Editar el encuentro seleccionado" title="Editar" @click="openEdit('encounter', selectedFor('encounter').item)"><AppIcon name="edit" /></button>
                                    <button type="button" class="add-button add-button--danger" aria-label="Eliminar el elemento seleccionado" title="Eliminar" @click="deleteItem('encounter', selectedFor('encounter').item)"><AppIcon name="trash" /></button>
                                </div>
                            </Transition>
                            </div>
                        </header>
                        <div v-if="encounters.length" class="encounter-grid">
                            <article v-for="encounter in encounters" :key="encounter.id" class="encounter-card selectable" :class="{ 'is-selected': isSelected('encounter', encounter.id) }" role="button" tabindex="0" :aria-pressed="isSelected('encounter', encounter.id)" @click="select('encounter', encounter.id)" @keydown.enter.prevent="select('encounter', encounter.id)" @keydown.space.prevent="select('encounter', encounter.id)">
                                <header>
                                    <span class="encounter-title"><AppIcon name="swords" /><h3>{{ encounter.name }}</h3></span>
                                    <span class="status-badge" :class="`status-badge--${encounter.status || 'planned'}`">{{ encounterStatus(encounter.status) }}</span>
                                </header>
                                <p class="item-excerpt">{{ encounter.description || 'Sin descripción.' }}</p>
                                <footer>{{ combatantsLabel(encounter) }}</footer>
                            </article>
                        </div>
                        <p v-else class="empty-hint">Todavía no hay encuentros de combate preparados.</p>
                    </section>
                </div>

                <!-- Espacio de la derecha: aquí se colocan las hojas con el detalle -->
                <aside class="sheet-slot" aria-live="polite">
                    <div v-if="sheetEntries.length" class="sheet-backdrop" @click="closeAllSheets"></div>
                    <template v-if="sheetEntries.length">
                        <div v-if="sheetEntries.length > 1" class="sheet-toolbar">
                            <span>{{ sheetEntries.length }} hojas abiertas</span>
                            <button class="secondary-button" type="button" @click="closeAllSheets">Cerrar todas</button>
                        </div>
                        <TransitionGroup name="sheet" tag="div" class="sheet-stack" :data-count="sheetEntries.length > 1 ? 'many' : 'one'">
                            <CampaignSheet v-for="entry in sheetEntries" :key="entry.key" :type="entry.type" :item="entry.item" :collapsed="collapsedKeys.includes(entry.key)" :campaign-characters="characters" :campaign-id="route.params.id" @close="closeSheet(entry.type, entry.item.id)" @toggle="toggleCollapse(entry.key)" @changed="loadCampaign" />
                        </TransitionGroup>
                    </template>
                    <div v-else class="sheet-placeholder">
                        <AppIcon name="search" />
                        <p>Pulsa una sesión, un lugar, un PNJ, un personaje o un encuentro para leer aquí su detalle. Puedes tener abiertas hasta dos hojas por categoría.</p>
                    </div>
                </aside>
            </div>
        </template>

        <Transition name="modal">
            <CampaignItemModal v-if="itemModal" :key="`${itemModal.type}-${itemModal.item?.id || 'new'}`" :type="itemModal.type" :item="itemModal.item || null" :campaign-id="route.params.id" @close="itemModal = null" @saved="onItemSaved" />
        </Transition>

        <Transition name="modal">
            <CampaignInfoModal v-if="showInfo && campaign" :campaign="campaign" @close="showInfo = false" @saved="onInfoSaved" />
        </Transition>

        <Transition name="modal">
            <EncounterWizard v-if="showWizard" :campaign-id="route.params.id" :campaign-characters="characters" @close="showWizard = false" @saved="onWizardSaved" />
        </Transition>

        <Transition name="modal">
            <div v-if="characterModal" class="modal-backdrop" @click.self="closeCharacterModal">
                <section class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="character-modal-title">
                    <button class="modal-close" type="button" aria-label="Cerrar" @click="closeCharacterModal"><AppIcon name="x" /></button>
                    <h2 id="character-modal-title">Añadir personaje</h2>
                    <p class="modal-lead">{{ isMaster ? 'Tus personajes entran directamente. Los de otros jugadores reciben una invitación y solo entran si su dueño la acepta. Elige a un participante o busca a otro jugador por su correo exacto.' : 'Tu solicitud la aprobará el Dungeon Master antes de que el personaje entre en la campaña.' }}</p>

                    <div v-if="isMaster" class="player-picker">
                        <div class="player-tabs">
                            <button type="button" class="chip-button" :class="{ 'is-active': !viewedPlayer || viewedPlayer.is_me }" @click="loadAvailableCharacters()">Mis personajes</button>
                            <button v-for="player in participants" :key="player.id" type="button" class="chip-button" :class="{ 'is-active': viewedPlayer?.id === player.id }" @click="loadAvailableCharacters({ user_id: player.id })">{{ player.name }}</button>
                        </div>
                        <form class="player-lookup" @submit.prevent="lookupPlayer">
                            <input v-model.trim="playerEmail" type="email" placeholder="Correo exacto de otro jugador" aria-label="Correo del jugador">
                            <button class="secondary-button" type="submit" :disabled="!playerEmail || loadingCharacters"><AppIcon name="search" /> Buscar</button>
                        </form>
                    </div>

                    <p v-if="viewedPlayer && !viewedPlayer.is_me" class="viewing-label">Personajes de <strong>{{ viewedPlayer.name }}</strong></p>
                    <p v-if="loadingCharacters" class="empty-hint">Buscando personajes...</p>
                    <p v-else-if="availableCharacters.length === 0" class="empty-hint">{{ viewedPlayer && !viewedPlayer.is_me ? 'Este jugador no tiene personajes disponibles.' : 'No tienes personajes disponibles para añadir. Puedes crear uno nuevo.' }}</p>
                    <div v-else class="available-characters">
                        <button v-for="character in availableCharacters" :key="character.id" type="button" class="available-character" :disabled="assigningCharacter === character.id" @click="assignCharacter(character)">
                            <span class="character-info">
                                <strong>{{ character.name }}</strong>
                                <small>
                                    {{ character.race || 'Sin raza' }} · Nivel {{ character.level }}
                                    <span class="owner-chip" :class="{ 'owner-chip--mine': character.is_mine }">{{ character.is_mine ? 'Tuyo' : `de ${viewedPlayer?.name || 'otro jugador'}` }}</span>
                                </small>
                            </span>
                            <span class="action-label">{{ actionLabel(character) }}</span>
                        </button>
                    </div>
                    <p v-if="modalError" class="error-message" role="alert">{{ modalError }}</p>
                    <RouterLink class="secondary-button create-new-character" :to="{ name: 'character-create', query: { campaign_id: campaign.id } }">Crear personaje nuevo</RouterLink>
                </section>
            </div>
        </Transition>
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { getCurrentUser } from '../services/auth';
import AppIcon from '../components/AppIcon.vue';
import CampaignSheet from '../components/CampaignSheet.vue';
import CampaignItemModal from '../components/CampaignItemModal.vue';
import EncounterWizard from '../components/EncounterWizard.vue';
import CampaignInfoModal from '../components/CampaignInfoModal.vue';
import { ENCOUNTER_STATUSES, ITEM_TYPES, formatDate } from '../services/campaignItems';

const route = useRoute();
const router = useRouter();
const campaign = ref(null);
const currentUser = ref(null);
const loading = ref(true);
const error = ref('');
const characters = ref([]);
const npcs = ref([]);
const locations = ref([]);
const sessions = ref([]);
const encounters = ref([]);

const itemModal = ref(null); // { type, item? }
const showWizard = ref(false);
const showInfo = ref(false);
const editMode = ref(route.query.edit === '1');
const flash = ref('');
const pendingRequests = ref([]);
const busyRequest = ref(null);
const copied = ref(false);
const openSheets = ref([]); // {type, id}, de la más antigua a la más reciente
const collapsedKeys = ref([]);

const characterModal = ref(false);
const modalError = ref('');
const availableCharacters = ref([]);
const loadingCharacters = ref(false);
const viewedPlayer = ref(null);
const participants = ref([]);
const playerEmail = ref('');
const assigningCharacter = ref(null);
const removingCharacter = ref(null);

const isMaster = computed(() => !!currentUser.value && !!campaign.value && Number(campaign.value.master_id) === Number(currentUser.value.id));

async function loadCampaign() {
    loading.value = true; error.value = '';
    const id = route.params.id;
    try {
        const [campaignRes, charsRes, npcsRes, locationsRes, sessionsRes, encountersRes] = await Promise.all([
            api.get(`/campaigns/${id}`), api.get(`/campaigns/${id}/characters`), api.get(`/campaigns/${id}/npcs`),
            api.get(`/campaigns/${id}/locations`), api.get(`/campaigns/${id}/sessions`), api.get(`/campaigns/${id}/encounters`),
        ]);
        campaign.value = campaignRes.data;
        characters.value = charsRes.data;
        npcs.value = npcsRes.data;
        locations.value = locationsRes.data;
        sessions.value = sessionsRes.data;
        encounters.value = encountersRes.data;
        if (isMaster.value) await loadRequests();
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar la campaña.';
    } finally { loading.value = false; }
}

async function loadCurrentUser() {
    try { currentUser.value = await getCurrentUser(); } catch { /* el interceptor y el guard de rutas gestionan la sesión */ }
}

// Hoja de detalle ----------------------------------------------------
const collections = { session: sessions, location: locations, npc: npcs, character: characters, encounter: encounters };

const MAX_SHEETS_PER_TYPE = 2;
const keyOf = (type, id) => `${type}-${id}`;

// Se calcula a partir de las listas: si se recargan, las hojas se actualizan solas.
// La más reciente va arriba.
const sheetEntries = computed(() => [...openSheets.value].reverse()
    .map((entry) => {
        const item = collections[entry.type].value.find((candidate) => candidate.id === entry.id);
        return item ? { key: keyOf(entry.type, entry.id), type: entry.type, item } : null;
    })
    .filter(Boolean));

const isSelected = (type, id) => openSheets.value.some((entry) => entry.type === type && entry.id === id);

// Abre una hoja (o la cierra si ya estaba abierta). Cada categoría admite dos
// a la vez: al abrir una tercera se retira la más antigua de esa categoría.
function select(type, id) {
    if (isSelected(type, id)) { closeSheet(type, id); return; }

    const sameType = openSheets.value.filter((entry) => entry.type === type);
    let next = openSheets.value;
    if (sameType.length >= MAX_SHEETS_PER_TYPE) {
        const oldest = sameType[0];
        next = next.filter((entry) => entry !== oldest);
        collapsedKeys.value = collapsedKeys.value.filter((key) => key !== keyOf(oldest.type, oldest.id));
    }
    openSheets.value = [...next, { type, id }];
}

function closeSheet(type, id) {
    openSheets.value = openSheets.value.filter((entry) => !(entry.type === type && entry.id === id));
    collapsedKeys.value = collapsedKeys.value.filter((key) => key !== keyOf(type, id));
}

function closeAllSheets() { openSheets.value = []; collapsedKeys.value = []; }

function toggleCollapse(key) {
    collapsedKeys.value = collapsedKeys.value.includes(key)
        ? collapsedKeys.value.filter((candidate) => candidate !== key)
        : [...collapsedKeys.value, key];
}

// Alta de elementos ----------------------------------------------------
function openAdd(type) { itemModal.value = { type }; }
function openEdit(type, item) { itemModal.value = { type, item }; }

async function deleteItem(type, item) {
    const config = ITEM_TYPES[type];
    const name = item[config.nameKey];
    if (!window.confirm(`¿Eliminar «${name}»? Esta acción no se puede deshacer.`)) return;
    error.value = '';
    try {
        await api.delete(`/${config.endpoint}/${item.id}`);
        closeSheet(type, item.id);
        await loadCampaign();
    } catch (err) { error.value = err.response?.data?.message || 'No se pudo eliminar.'; }
}

// Elemento seleccionado de cada panel: la hoja más reciente de ese tipo.
const selectedFor = (type) => sheetEntries.value.find((entry) => entry.type === type) ?? null;

async function onInfoSaved(options = {}) {
    if (!options.keepOpen) showInfo.value = false;
    await loadCampaign();
}

async function deleteCampaign() {
    if (!window.confirm(`¿Eliminar la campaña «${campaign.value.name}» con todo su contenido? No se puede deshacer.`)) return;
    try {
        await api.delete(`/campaigns/${campaign.value.id}`);
        await router.replace('/campaigns');
    } catch (err) { error.value = err.response?.data?.message || 'No se pudo eliminar la campaña.'; }
}

async function onWizardSaved() {
    showWizard.value = false;
    await loadCampaign();
}

async function onItemSaved() {
    itemModal.value = null;
    await loadCampaign();
}

// Personajes -------------------------------------------------------------
function openCharacterModal() {
    characterModal.value = true; modalError.value = ''; playerEmail.value = '';
    viewedPlayer.value = null; availableCharacters.value = [];
    loadAvailableCharacters();
}
function closeCharacterModal() { characterModal.value = false; modalError.value = ''; }

// Sin parámetros: tus personajes. Con user_id o email (solo Dungeon Master): los de un jugador.
async function loadAvailableCharacters(params = {}) {
    loadingCharacters.value = true; modalError.value = '';
    try {
        const response = await api.get(`/campaigns/${route.params.id}/available-characters`, { params });
        availableCharacters.value = response.data.characters;
        participants.value = response.data.participants || [];
        viewedPlayer.value = response.data.player;
    } catch (err) {
        modalError.value = err.response?.data?.message || 'No se pudieron cargar los personajes.';
    } finally { loadingCharacters.value = false; }
}

function lookupPlayer() {
    if (playerEmail.value) loadAvailableCharacters({ email: playerEmail.value });
}

function actionLabel(character) {
    if (!isMaster.value) return 'Solicitar';
    return character.is_mine ? 'Añadir' : 'Invitar';
}

async function assignCharacter(character) {
    assigningCharacter.value = character.id; modalError.value = '';
    try {
        const { data } = await api.post(`/campaigns/${route.params.id}/characters/${character.id}`);
        flashMessage(data.message);
        await loadCampaign(); closeCharacterModal();
    } catch (err) { modalError.value = err.response?.data?.message || 'No se pudo añadir el personaje.'; }
    finally { assigningCharacter.value = null; }
}

let flashTimer = null;
function flashMessage(text) {
    flash.value = text;
    clearTimeout(flashTimer);
    flashTimer = setTimeout(() => { flash.value = ''; }, 7000);
}

// Reclutamiento (solo Dungeon Master) -------------------------------
async function loadRequests() {
    try {
        const { data } = await api.get(`/campaigns/${route.params.id}/requests`);
        pendingRequests.value = data;
    } catch { /* no bloquea el resto del panel */ }
}

async function answerRequest(entry, action) {
    busyRequest.value = entry.id; error.value = '';
    try {
        const { data } = await api.post(`/campaign-requests/${entry.id}/${action}`);
        flashMessage(data.message);
        await loadCampaign();
    } catch (err) { error.value = err.response?.data?.message || 'No se pudo responder a la solicitud.'; }
    finally { busyRequest.value = null; }
}

async function cancelInvitation(entry) {
    busyRequest.value = entry.id; error.value = '';
    try {
        const { data } = await api.delete(`/campaign-requests/${entry.id}`);
        flashMessage(data.message);
        await loadRequests();
    } catch (err) { error.value = err.response?.data?.message || 'No se pudo retirar la invitación.'; }
    finally { busyRequest.value = null; }
}

async function copyCode() {
    try {
        await navigator.clipboard.writeText(campaign.value.invite_code);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 1800);
    } catch { flashMessage('No se pudo copiar. Selecciona el código y cópialo a mano.'); }
}

async function renewCode() {
    if (!window.confirm('¿Generar un código nuevo? El anterior dejará de funcionar.')) return;
    try {
        const { data } = await api.post(`/campaigns/${route.params.id}/invite-code`);
        campaign.value = { ...campaign.value, invite_code: data.invite_code };
        flashMessage(data.message);
    } catch (err) { error.value = err.response?.data?.message || 'No se pudo renovar el código.'; }
}

async function removeCharacter(character) {
    if (!window.confirm(`¿Retirar a «${character.name}» de la campaña? La ficha se conserva.`)) return;
    removingCharacter.value = character.id; error.value = '';
    try {
        await api.delete(`/campaigns/${route.params.id}/characters/${character.id}`);
        await loadCampaign();
    } catch (err) { error.value = err.response?.data?.message || 'No se pudo retirar el personaje.'; }
    finally { removingCharacter.value = null; }
}

const isMine = (character) => !!currentUser.value && Number(character.user_id) === Number(currentUser.value.id);
const canRemove = (character) => isMaster.value || isMine(character);
function ownerLabel(character) {
    if (isMine(character)) return 'Tuyo';
    return character.user?.name ? `de ${character.user.name}` : '';
}
const initial = (name) => name?.slice(0, 1)?.toUpperCase() || '?';

const encounterStatus = (status) => ENCOUNTER_STATUSES[status] || ENCOUNTER_STATUSES.planned;
function combatantsLabel(encounter) {
    const total = encounter.combatants?.length || 0;
    return total === 0 ? 'Sin combatientes' : `${total} ${total === 1 ? 'combatiente' : 'combatientes'}`;
}

function onKeydown(event) {
    if (event.key !== 'Escape') return;
    if (characterModal.value) closeCharacterModal();
    else if (!itemModal.value && !showWizard.value && !showInfo.value && sheetEntries.value.length) {
        const newest = sheetEntries.value[0];
        closeSheet(newest.type, newest.item.id);
    }
}

onMounted(async () => {
    window.addEventListener('keydown', onKeydown);
    await loadCurrentUser(); // hace falta para saber si eres el Dungeon Master
    loadCampaign();
});
onBeforeUnmount(() => { window.removeEventListener('keydown', onKeydown); clearTimeout(flashTimer); });
</script>
