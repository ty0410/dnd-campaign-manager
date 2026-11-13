<template>
    <article class="stat-block" :aria-label="`Estadísticas de ${monster.name}`">
        <header class="stat-block-header">
            <h3>{{ monster.name }}</h3>
            <p>{{ [monster.size, monster.type, monster.subtype ? `(${monster.subtype})` : '', monster.alignment].filter(Boolean).join(' ') }}</p>
        </header>

        <dl class="stat-block-main">
            <div><dt>Clase de armadura</dt><dd>{{ armorClass }}</dd></div>
            <div><dt>Puntos de golpe</dt><dd>{{ monster.hit_points }}<template v-if="monster.hit_dice"> ({{ monster.hit_dice }})</template></dd></div>
            <div><dt>Velocidad</dt><dd>{{ speed }}</dd></div>
            <div><dt>Desafío</dt><dd>{{ monster.challenge_rating }}<template v-if="monster.xp"> ({{ monster.xp }} PX)</template></dd></div>
        </dl>

        <ul class="stat-block-abilities">
            <li v-for="ability in abilities" :key="ability.key">
                <span>{{ ability.label }}</span>
                <strong>{{ monster[ability.key] }}</strong>
                <small>{{ modifier(monster[ability.key]) }}</small>
            </li>
        </ul>

        <dl class="stat-block-details">
            <div v-if="saves"><dt>Salvaciones</dt><dd>{{ saves }}</dd></div>
            <div v-if="skills"><dt>Habilidades</dt><dd>{{ skills }}</dd></div>
            <div v-if="list(monster.damage_vulnerabilities)"><dt>Vulnerabilidades</dt><dd>{{ list(monster.damage_vulnerabilities) }}</dd></div>
            <div v-if="list(monster.damage_resistances)"><dt>Resistencias</dt><dd>{{ list(monster.damage_resistances) }}</dd></div>
            <div v-if="list(monster.damage_immunities)"><dt>Inmunidades al daño</dt><dd>{{ list(monster.damage_immunities) }}</dd></div>
            <div v-if="list(monster.condition_immunities)"><dt>Inmunidades a estados</dt><dd>{{ list(monster.condition_immunities) }}</dd></div>
            <div v-if="senses"><dt>Sentidos</dt><dd>{{ senses }}</dd></div>
            <div v-if="monster.languages"><dt>Idiomas</dt><dd>{{ monster.languages }}</dd></div>
        </dl>

        <section v-for="group in groups" :key="group.title" class="stat-block-group">
            <h4>{{ group.title }}</h4>
            <p v-for="entry in group.entries" :key="entry.name"><strong>{{ entry.name }}.</strong> {{ entry.desc }}</p>
        </section>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ monster: { type: Object, required: true } });

const abilities = [
    { key: 'strength', label: 'FUE' }, { key: 'dexterity', label: 'DES' }, { key: 'constitution', label: 'CON' },
    { key: 'intelligence', label: 'INT' }, { key: 'wisdom', label: 'SAB' }, { key: 'charisma', label: 'CAR' },
];

const modifier = (score) => {
    const value = Math.floor((Number(score || 10) - 10) / 2);
    return value >= 0 ? `+${value}` : `${value}`;
};

const armorClass = computed(() => {
    const ac = props.monster.armor_class;
    if (Array.isArray(ac)) return ac.map((entry) => `${entry.value}${entry.type ? ` (${entry.type})` : ''}`).join(', ');
    return ac ?? '—';
});

const speed = computed(() => Object.entries(props.monster.speed || {}).map(([key, value]) => `${key} ${value}`).join(', ') || '—');

const list = (value) => (Array.isArray(value)
    ? value.map((entry) => (typeof entry === 'string' ? entry : entry.name)).filter(Boolean).join(', ')
    : '');

const proficiencies = computed(() => props.monster.proficiencies || []);
const bonusesFor = (prefix) => proficiencies.value
    .filter((entry) => entry.proficiency?.name?.startsWith(prefix))
    .map((entry) => `${entry.proficiency.name.replace(prefix, '')} ${entry.value >= 0 ? '+' : ''}${entry.value}`)
    .join(', ');

const saves = computed(() => bonusesFor('Saving Throw: '));
const skills = computed(() => bonusesFor('Skill: '));

const senses = computed(() => Object.entries(props.monster.senses || {}).map(([key, value]) => `${key.replaceAll('_', ' ')} ${value}`).join(', '));

const groups = computed(() => [
    { title: 'Rasgos', entries: props.monster.special_abilities || [] },
    { title: 'Acciones', entries: props.monster.actions || [] },
    { title: 'Reacciones', entries: props.monster.reactions || [] },
    { title: 'Acciones legendarias', entries: props.monster.legendary_actions || [] },
].filter((group) => group.entries.length));
</script>
