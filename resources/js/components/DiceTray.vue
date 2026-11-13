<template>
    <div ref="root" class="dice-tray" :class="{ 'is-open': open, 'is-dragging': dragging }" :style="{ left: `${pos.x}px`, top: `${pos.y}px` }" role="region" aria-label="Bandeja de dados">
        <!-- Cerrada: botón flotante -->
        <button v-if="!open" type="button" class="dice-tray-fab" aria-label="Abrir bandeja de dados" title="Dados (arrastra para mover)" @pointerdown="startDrag" @click="onFabClick">
            <span aria-hidden="true">d20</span>
        </button>

        <!-- Abierta: panel -->
        <section v-else class="dice-tray-panel">
            <header class="dice-tray-header" @pointerdown="startDrag">
                <span class="dice-tray-grip" aria-hidden="true">⠿</span>
                <strong>Dados</strong>
                <button type="button" class="icon-button" aria-label="Minimizar bandeja de dados" @pointerdown.stop @click="toggle(false)"><AppIcon name="chevron" /></button>
            </header>

            <div class="dice-tray-body">
                <div class="dice-tray-grid" role="group" aria-label="Elige el dado">
                    <button v-for="sides in DICE" :key="sides" type="button" class="die-button" :class="{ 'is-selected': selectedDie === sides }" :aria-pressed="selectedDie === sides" @click="selectedDie = sides">d{{ sides }}</button>
                </div>

                <div class="dice-tray-row">
                    <label>Cantidad
                        <span class="stepper stepper--small">
                            <button type="button" class="icon-button" :disabled="count <= 1" aria-label="Menos dados" @click="count -= 1">−</button>
                            <output class="stepper-value">{{ count }}</output>
                            <button type="button" class="icon-button" :disabled="count >= 10" aria-label="Más dados" @click="count += 1">+</button>
                        </span>
                    </label>
                    <label>Modificador
                        <input v-model.number="modifier" type="number" min="-99" max="99" aria-label="Modificador">
                    </label>
                </div>

                <div v-if="selectedDie === 20 && count === 1" class="player-tabs" role="group" aria-label="Tipo de tirada de d20">
                    <button v-for="option in MODES" :key="option.key" type="button" class="chip-button" :class="{ 'is-active': mode === option.key }" :aria-pressed="mode === option.key" @click="mode = option.key">{{ option.label }}</button>
                </div>

                <button class="primary-button dice-tray-roll" type="button" @click="roll">Tirar {{ count }}d{{ selectedDie }}<template v-if="modifier"> {{ modifier > 0 ? '+' : '−' }} {{ Math.abs(modifier) }}</template></button>

                <ol v-if="history.length" class="dice-tray-history" aria-label="Últimas tiradas">
                    <li v-for="(entry, index) in history" :key="index"><span>{{ entry.label }}</span><strong>{{ entry.total }}</strong></li>
                </ol>
            </div>
        </section>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from './AppIcon.vue';
import { rollD20, rollDice } from '../services/dice';

const DICE = [4, 6, 8, 10, 12, 20, 100];
const MODES = [{ key: 'disadvantage', label: 'Desventaja' }, { key: 'normal', label: 'Normal' }, { key: 'advantage', label: 'Ventaja' }];
const STORAGE_KEY = 'dnd_dice_tray';

const root = ref(null);
const open = ref(false);
const pos = ref({ x: 0, y: 0 });
const dragging = ref(false);
const selectedDie = ref(20);
const count = ref(1);
const modifier = ref(0);
const mode = ref('normal');
const history = ref([]);

let moved = false;
let offset = { x: 0, y: 0 };
let start = { x: 0, y: 0 };

// Mantiene la bandeja dentro de la pantalla.
function clamp() {
    const width = root.value?.offsetWidth || 64;
    const height = root.value?.offsetHeight || 64;
    pos.value = {
        x: Math.min(Math.max(8, pos.value.x), Math.max(8, window.innerWidth - width - 8)),
        y: Math.min(Math.max(8, pos.value.y), Math.max(8, window.innerHeight - height - 8)),
    };
}

function save() {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify({ ...pos.value, open: open.value })); } catch { /* sin almacenamiento */ }
}

function startDrag(event) {
    if (event.button !== undefined && event.button !== 0) return;
    const rect = root.value.getBoundingClientRect();
    offset = { x: event.clientX - rect.left, y: event.clientY - rect.top };
    start = { x: event.clientX, y: event.clientY };
    moved = false;
    dragging.value = true;
    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', stopDrag, { once: true });
}

function onMove(event) {
    if (Math.abs(event.clientX - start.x) + Math.abs(event.clientY - start.y) > 4) moved = true;
    pos.value = { x: event.clientX - offset.x, y: event.clientY - offset.y };
    clamp();
}

function stopDrag() {
    dragging.value = false;
    window.removeEventListener('pointermove', onMove);
    save();
}

// Un arrastre no debe contar como pulsación.
function onFabClick() {
    if (!moved) toggle(true);
    moved = false;
}

async function toggle(next) {
    open.value = next;
    await nextTick();
    clamp();
    save();
}

function roll() {
    const modifierValue = Number(modifier.value) || 0;
    let result;

    if (selectedDie.value === 20 && count.value === 1) {
        result = rollD20({ label: 'Tirada libre', modifier: modifierValue, mode: mode.value });
    } else {
        result = rollDice({ label: `${count.value}d${selectedDie.value}`, count: count.value, sides: selectedDie.value, modifier: modifierValue });
    }

    history.value = [{ label: result.kind === 'd20' ? `d20${modifierValue ? (modifierValue > 0 ? `+${modifierValue}` : modifierValue) : ''}` : `${count.value}d${selectedDie.value}${modifierValue ? (modifierValue > 0 ? `+${modifierValue}` : modifierValue) : ''}`, total: result.total }, ...history.value].slice(0, 6);
}

function onResize() { clamp(); }

onMounted(async () => {
    let saved = null;
    try { saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null'); } catch { /* ignora datos corruptos */ }

    pos.value = saved && Number.isFinite(saved.x) && Number.isFinite(saved.y)
        ? { x: saved.x, y: saved.y }
        : { x: window.innerWidth - 88, y: window.innerHeight - 96 };
    open.value = !!saved?.open;

    await nextTick();
    clamp();
    window.addEventListener('resize', onResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', onResize);
    window.removeEventListener('pointermove', onMove);
});
</script>
