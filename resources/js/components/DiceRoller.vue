<template>
    <Transition name="modal">
        <div v-if="diceState.open && result" class="modal-backdrop dice-backdrop" @click.self="closeDice">
            <section class="modal dice-modal" role="dialog" aria-modal="true" aria-labelledby="dice-title" aria-live="polite">
                <button ref="closeButton" class="modal-close" type="button" aria-label="Cerrar" @click="closeDice"><AppIcon name="x" /></button>

                <h2 id="dice-title">{{ result.label }}</h2>

                <div :key="rollKey" class="dice-total" :class="criticalClass">
                    <span class="dice-total-value">{{ result.total }}</span>
                    <span v-if="result.critical" class="dice-critical">{{ result.critical === 'success' ? '¡Crítico!' : '¡Pifia!' }}</span>
                </div>

                <!-- Desglose: de dónde sale el total -->
                <p class="dice-breakdown">
                    <template v-if="result.kind === 'd20'">
                        <template v-if="result.dice.length > 1">
                            <span v-for="(die, index) in result.dice" :key="index" class="dice-chip" :class="{ 'is-discarded': die !== result.natural || (index === 1 && result.dice[0] === result.dice[1]) }">{{ die }}</span>
                            <span class="dice-note">({{ result.mode === 'advantage' ? 'ventaja' : 'desventaja' }}, cuenta {{ result.natural }})</span>
                        </template>
                        <span v-else class="dice-chip">{{ result.natural }}</span>
                    </template>
                    <template v-else>
                        <span v-for="(die, index) in result.dice" :key="index" class="dice-chip">{{ die }}</span>
                    </template>
                    <template v-if="result.modifier !== 0">
                        <span class="dice-operator">{{ result.modifier > 0 ? '+' : '−' }}</span>
                        <span class="dice-chip dice-chip--mod">{{ Math.abs(result.modifier) }}</span>
                    </template>
                    <span class="dice-operator">=</span>
                    <strong>{{ result.total }}</strong>
                </p>
                <p class="dice-detail">
                    <template v-if="result.kind === 'd20'">Dado: {{ result.natural }}<template v-if="result.modifier !== 0"> · Modificador: {{ signed(result.modifier) }}</template></template>
                    <template v-else>Suma de dados: {{ result.sum }}<template v-if="result.modifier !== 0"> · Modificador: {{ signed(result.modifier) }}</template></template>
                </p>

                <div class="modal-actions dice-actions">
                    <template v-if="result.kind === 'd20'">
                        <button class="secondary-button" type="button" @click="reroll('disadvantage')">Desventaja</button>
                        <button class="secondary-button" type="button" @click="reroll('advantage')">Ventaja</button>
                    </template>
                    <button class="primary-button" type="button" @click="reroll('normal')">Volver a tirar</button>
                </div>
            </section>
        </div>
    </Transition>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch, nextTick } from 'vue';
import AppIcon from './AppIcon.vue';
import { diceState, closeDice, reroll, signed } from '../services/dice';

const result = computed(() => diceState.result);
const closeButton = ref(null);

// Cambia en cada tirada para repetir la animación del número.
const rollKey = ref(0);
watch(() => diceState.result, () => { rollKey.value += 1; });
watch(() => diceState.open, async (open) => { if (open) { await nextTick(); closeButton.value?.focus(); } });

const criticalClass = computed(() => (result.value?.critical ? `is-${result.value.critical}` : ''));

function onKeydown(event) { if (event.key === 'Escape' && diceState.open) closeDice(); }
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>
