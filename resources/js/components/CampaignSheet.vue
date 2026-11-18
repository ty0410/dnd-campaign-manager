<template>
    <article class="sheet" :class="{ 'is-collapsed': collapsed }" :aria-label="title">
        <header class="sheet-header">
            <div class="sheet-heading">
                <h2>{{ title }}</h2>
                <p v-if="subtitle" class="sheet-subtitle">{{ subtitle }}</p>
            </div>
            <button class="modal-close sheet-collapse" type="button" :aria-label="collapsed ? 'Desplegar detalle' : 'Plegar detalle'" :aria-expanded="!collapsed" @click="$emit('toggle')"><AppIcon name="chevron" /></button>
            <button class="modal-close sheet-close" type="button" aria-label="Cerrar detalle" @click="$emit('close')"><AppIcon name="x" /></button>
        </header>

        <div class="sheet-body">
            <!-- Sesión -->
            <template v-if="type === 'session'">
                <section class="sheet-block"><h3>Resumen</h3><p v-if="item.summary" class="sheet-text">{{ item.summary }}</p><p v-else class="sheet-empty">Sin resumen.</p></section>
                <section v-if="item.notes" class="sheet-block"><h3>Notas del Dungeon Master</h3><p class="sheet-text">{{ item.notes }}</p></section>
            </template>

            <!-- Lugar -->
            <template v-else-if="type === 'location'">
                <section class="sheet-block"><h3>Descripción</h3><p v-if="item.description" class="sheet-text">{{ item.description }}</p><p v-else class="sheet-empty">Sin descripción.</p></section>
            </template>

            <!-- PNJ -->
            <template v-else-if="type === 'npc'">
                <dl class="sheet-facts">
                    <div v-if="item.race"><dt>Raza</dt><dd>{{ item.race }}</dd></div>
                    <div v-if="item.role"><dt>Papel</dt><dd>{{ item.role }}</dd></div>
                    <div v-if="item.location"><dt>Lugar</dt><dd>{{ item.location }}</dd></div>
                </dl>
                <section class="sheet-block"><h3>Descripción</h3><p v-if="item.description" class="sheet-text">{{ item.description }}</p><p v-else class="sheet-empty">Sin descripción.</p></section>
            </template>

            <!-- Encuentro -->
            <template v-else-if="type === 'encounter'">
                <section class="sheet-block"><h3>Descripción</h3><p v-if="item.description" class="sheet-text">{{ item.description }}</p><p v-else class="sheet-empty">Sin descripción.</p></section>
                <section class="sheet-block">
                    <h3>Monstruos</h3>
                    <ul v-if="monsterGroups.length" class="sheet-list">
                        <li v-for="group in monsterGroups" :key="group.key"><span>{{ group.name }}</span><small>× {{ group.count }}</small></li>
                    </ul>
                    <p v-else class="sheet-empty">Sin monstruos.</p>
                </section>
            </template>

            <!-- Personaje -->
            <template v-else-if="type === 'character'">
                <dl class="sheet-facts">
                    <div><dt>Raza</dt><dd>{{ item.race || 'Sin raza' }}</dd></div>
                    <div><dt>Nivel</dt><dd>{{ item.level }}</dd></div>
                    <div v-if="classesLabel"><dt>Clase</dt><dd>{{ classesLabel }}</dd></div>
                    <div v-if="item.user?.name"><dt>Jugador</dt><dd>{{ item.user.name }}</dd></div>
                </dl>
                <section v-if="item.description" class="sheet-block"><h3>Descripción</h3><p class="sheet-text">{{ item.description }}</p></section>
            </template>
        </div>

        <footer v-if="type === 'character'" class="sheet-footer">
            <RouterLink class="primary-button" :to="`/characters/${item.id}`">Abrir ficha completa <AppIcon name="arrow" /></RouterLink>
        </footer>
        <footer v-else class="sheet-footer sheet-footer--actions">
            <RouterLink v-if="pagePath" class="primary-button" :to="pagePath" target="_blank" rel="noopener" title="Abre todos los detalles en una ventana nueva"><AppIcon name="external" /> Abrir ventana</RouterLink>
        </footer>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import AppIcon from './AppIcon.vue';
import { groupMonsters } from '../services/monsters';
import { ENCOUNTER_STATUSES, ITEM_TYPES, itemSubtitle, formatDate } from '../services/campaignItems';

const props = defineProps({
    type: { type: String, required: true },
    item: { type: Object, required: true },
    collapsed: { type: Boolean, default: false },
    campaignCharacters: { type: Array, default: () => [] },
    campaignId: { type: [Number, String], default: null },
});
defineEmits(['close', 'toggle', 'edit', 'delete', 'changed']);

const kickers = { session: 'Sesión', location: 'Lugar', npc: 'Personaje no jugador', encounter: 'Encuentro de combate', character: 'Personaje' };

const pagePath = computed(() => (props.campaignId && ITEM_TYPES[props.type]
    ? `/campaigns/${props.campaignId}/${ITEM_TYPES[props.type].endpoint}/${props.item.id}`
    : null));

const monsterGroups = computed(() => (props.type === 'encounter' ? groupMonsters(props.item.combatants) : []));

const kicker = computed(() => kickers[props.type]);
const title = computed(() => props.type === 'character' ? props.item.name : props.item[ITEM_TYPES[props.type].nameKey]);
const subtitle = computed(() => {
    if (props.type === 'session') return formatDate(props.item.session_date);
    if (props.type === 'location') return props.item.type || '';
    if (props.type === 'npc' || props.type === 'encounter' || props.type === 'character') return '';
    return itemSubtitle(props.type, props.item);
});
const statusLabel = computed(() => ENCOUNTER_STATUSES[props.item.status] || ENCOUNTER_STATUSES.planned);
const classesLabel = computed(() => (props.item.character_classes || [])
    .map((entry) => `${entry.class} ${entry.level}`)
    .join(' / '));
</script>
