<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <form class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="attack-title" @submit.prevent="save">
            <button class="modal-close" type="button" aria-label="Cerrar" @click="$emit('close')"><AppIcon name="x" /></button>
            <h2 id="attack-title">{{ attack ? 'Editar ataque' : 'Nuevo ataque' }}</h2>

            <label for="attack-name">Nombre</label>
            <input id="attack-name" v-model.trim="form.name" maxlength="120" required placeholder="Descarga sobrenatural">

            <div class="player-tabs" role="group" aria-label="Tipo de ataque">
                <button type="button" class="chip-button" :class="{ 'is-active': form.kind === 'attack' }" :aria-pressed="form.kind === 'attack'" @click="form.kind = 'attack'">Tirada de ataque</button>
                <button type="button" class="chip-button" :class="{ 'is-active': form.kind === 'save' }" :aria-pressed="form.kind === 'save'" @click="form.kind = 'save'">Salvación del objetivo</button>
            </div>

            <div class="field-row field-row--two">
                <div>
                    <label for="attack-ability">{{ form.kind === 'attack' ? 'Modificador de' : 'CD según' }}</label>
                    <select id="attack-ability" v-model="form.ability">
                        <option v-for="(label, key) in ABILITIES" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div v-if="form.kind === 'save'">
                    <label for="attack-save">Salvación del objetivo</label>
                    <select id="attack-save" v-model="form.save_ability">
                        <option v-for="(label, key) in ABILITIES" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label for="attack-bonus">Bonificador extra</label>
                    <input id="attack-bonus" v-model.number="form.bonus" type="number" min="-20" max="30">
                </div>
            </div>
            <label v-if="form.kind === 'attack'" class="check-row"><input v-model="form.add_proficiency" type="checkbox"> Sumar competencia</label>

            <div class="field-group">
                <legend>Daño</legend>
                <div class="field-row field-row--dice">
                    <div><label for="damage-count">Dados</label><input id="damage-count" v-model.number="form.damage_count" type="number" min="0" max="20" placeholder="0"></div>
                    <div><label for="damage-sides">Tipo de dado</label>
                        <select id="damage-sides" v-model.number="form.damage_sides"><option v-for="sides in DICE" :key="sides" :value="sides">d{{ sides }}</option></select>
                    </div>
                    <div><label for="damage-bonus">Bonificador</label><input id="damage-bonus" v-model.number="form.damage_bonus" type="number" min="-20" max="50"></div>
                </div>
                <label class="check-row"><input v-model="form.damage_add_ability" type="checkbox"> Sumar el modificador de {{ ABILITIES[form.ability].toLowerCase() }}</label>
                <label for="damage-type">Tipo de daño</label>
                <input id="damage-type" v-model.trim="form.damage_type" maxlength="60" placeholder="Fuerza, fuego, cortante...">
            </div>

            <label for="attack-notes">Notas</label>
            <input id="attack-notes" v-model.trim="form.notes" maxlength="500">

            <p class="attack-preview" aria-live="polite">{{ preview }}</p>

            <p v-if="error" class="error-message" role="alert">{{ error }}</p>
            <div class="modal-actions">
                <button class="secondary-button" type="button" @click="$emit('close')">Cancelar</button>
                <button class="primary-button" type="submit" :disabled="saving || !form.name">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../services/api';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    characterId: { type: [Number, String], required: true },
    attack: { type: Object, default: null },
    sheet: { type: Object, required: true },
});
const emit = defineEmits(['close', 'saved']);

const ABILITIES = { strength: 'Fuerza', dexterity: 'Destreza', constitution: 'Constitución', intelligence: 'Inteligencia', wisdom: 'Sabiduría', charisma: 'Carisma' };
const DICE = [4, 6, 8, 10, 12, 20, 100];

function initialForm() {
    const attack = props.attack;
    const match = /^(\d+)d(\d+)$/.exec(attack?.damage_dice || '');

    return {
        name: attack?.name || '',
        kind: attack?.kind || 'attack',
        ability: attack?.ability || 'strength',
        add_proficiency: attack ? !!attack.add_proficiency : true,
        save_ability: attack?.save_ability || 'dexterity',
        bonus: attack?.bonus ?? 0,
        damage_count: match ? Number(match[1]) : '',
        damage_sides: match ? Number(match[2]) : 8,
        damage_bonus: attack?.damage_bonus ?? 0,
        damage_add_ability: !!attack?.damage_add_ability,
        damage_type: attack?.damage_type || '',
        notes: attack?.notes || '',
    };
}

const form = ref(initialForm());
const saving = ref(false);
const error = ref('');

const signed = (value) => (value >= 0 ? `+${value}` : `${value}`);
const abilityMod = (key) => props.sheet.ability_scores[key]?.modifier ?? 0;

// Vista previa con los números reales de la ficha.
const preview = computed(() => {
    const f = form.value;
    const bonus = Number(f.bonus || 0);
    const parts = [];

    if (f.kind === 'attack') {
        parts.push(`Ataque ${signed(abilityMod(f.ability) + (f.add_proficiency ? props.sheet.proficiency_bonus : 0) + bonus)}`);
    } else {
        parts.push(`CD ${8 + props.sheet.proficiency_bonus + abilityMod(f.ability) + bonus}`);
    }

    if (Number(f.damage_count) > 0) {
        const modifier = Number(f.damage_bonus || 0) + (f.damage_add_ability ? abilityMod(f.ability) : 0);
        parts.push(`Daño ${f.damage_count}d${f.damage_sides}${modifier ? ` ${signed(modifier)}` : ''}`);
    }

    return parts.join(' · ');
});

async function save() {
    saving.value = true; error.value = '';
    const f = form.value;
    const payload = {
        name: f.name,
        kind: f.kind,
        ability: f.ability,
        add_proficiency: f.kind === 'attack' ? f.add_proficiency : true,
        save_ability: f.kind === 'save' ? f.save_ability : null,
        bonus: Number(f.bonus || 0),
        damage_dice: Number(f.damage_count) > 0 ? `${f.damage_count}d${f.damage_sides}` : null,
        damage_bonus: Number(f.damage_bonus || 0),
        damage_add_ability: f.damage_add_ability,
        damage_type: f.damage_type || null,
        notes: f.notes || null,
    };

    try {
        if (props.attack) await api.put(`/characters/${props.characterId}/attacks/${props.attack.id}`, payload);
        else await api.post(`/characters/${props.characterId}/attacks`, payload);
        emit('saved');
    } catch (err) {
        error.value = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'No se pudo guardar el ataque.';
    } finally { saving.value = false; }
}

function onKeydown(event) { if (event.key === 'Escape') emit('close'); }
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
