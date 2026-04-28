<script setup>
/**
 * ShipmentStepProgress — barra progresso 4 step funnel preventivo.
 *
 * P8 plan: Baymard pattern checkout multi-step.
 * - Desktop: 4 step orizzontali con label visibile.
 * - Mobile: solo numero step attivo + "X di 4" + barra lineare.
 *
 * L'utente vede sempre dove sta nel funnel (riduce ansia + abbandono).
 */
const props = defineProps({
	currentStep: { type: Number, required: true },
});

const steps = [
	{ n: 1, label: 'Colli' },
	{ n: 2, label: 'Servizi' },
	{ n: 3, label: 'Indirizzi' },
	{ n: 4, label: 'Pagamento' },
];

// Percentuale coerente con "Step X di 4": ogni step copre 25%
// (step 1 -> 25%, step 2 -> 50%, step 3 -> 75%, step 4 -> 100%).
// Prima usavo (step-1)/3 che dava 33% allo step 2, confondente per l'utente
// che leggeva "Step 2 di 4 · 33%" pensando di essere meno avanti del reale.
const progressPct = computed(() => {
	const safe = Math.max(1, Math.min(4, Number(props.currentStep) || 1));
	return Math.round((safe / 4) * 100);
});

const stateOf = (n) => {
	if (n < props.currentStep) return 'done';
	if (n === props.currentStep) return 'active';
	return 'todo';
};
</script>

<template>
	<nav
		class="shipment-step-progress"
		aria-label="Avanzamento preventivo">
		<!-- Desktop: 4 step con label -->
		<ol class="shipment-step-progress__desktop">
			<li
				v-for="step in steps"
				:key="step.n"
				class="shipment-step-progress__item"
				:data-state="stateOf(step.n)">
				<span class="shipment-step-progress__dot" aria-hidden="true">
					<svg v-if="stateOf(step.n) === 'done'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
					<span v-else>{{ step.n }}</span>
				</span>
				<span class="shipment-step-progress__label">{{ step.label }}</span>
			</li>
		</ol>

		<!-- Mobile: numero + label corrente + barra % -->
		<div class="shipment-step-progress__mobile">
			<div class="shipment-step-progress__mobile-row">
				<span class="shipment-step-progress__mobile-current">
					Step {{ currentStep }} di 4 · {{ steps[currentStep - 1]?.label || '' }}
				</span>
				<span class="shipment-step-progress__mobile-pct">{{ progressPct }}%</span>
			</div>
			<div class="shipment-step-progress__mobile-bar" aria-hidden="true">
				<div class="shipment-step-progress__mobile-fill" :style="{ width: `${progressPct}%` }" />
			</div>
		</div>
	</nav>
</template>

<style scoped>
.shipment-step-progress {
	width: 100%;
	max-width: 1280px;
	margin: 0 auto;
	padding: 0 14px;
}

@media (min-width: 720px) {
	.shipment-step-progress { padding: 0 40px; }
}

/* === Desktop view === */
.shipment-step-progress__desktop {
	display: none;
	list-style: none;
	margin: 0;
	padding: 12px 0;
	gap: 0;
}

@media (min-width: 720px) {
	.shipment-step-progress__desktop {
		display: flex;
		align-items: center;
	}
	.shipment-step-progress__mobile {
		display: none;
	}
}

.shipment-step-progress__item {
	display: flex;
	align-items: center;
	gap: 10px;
	flex: 1;
	min-width: 0;
	position: relative;
}
.shipment-step-progress__item:not(:last-child)::after {
	content: '';
	flex: 1;
	height: 2px;
	background: var(--color-brand-border, #E9EBEC);
	margin: 0 12px;
	min-width: 24px;
}
.shipment-step-progress__item[data-state="done"]:not(:last-child)::after {
	background: var(--color-brand-primary, #095866);
}
.shipment-step-progress__dot {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 32px;
	height: 32px;
	min-width: 32px;
	border-radius: 999px;
	background: #fff;
	border: 2px solid var(--color-brand-border, #E9EBEC);
	color: var(--color-brand-text-muted, #6b7280);
	font-weight: 700;
	font-size: 14px;
	transition: all 200ms ease;
}
.shipment-step-progress__item[data-state="active"] .shipment-step-progress__dot {
	background: var(--color-brand-primary, #095866);
	border-color: var(--color-brand-primary, #095866);
	color: #fff;
	box-shadow: 0 0 0 4px rgba(9, 88, 102, 0.15);
}
.shipment-step-progress__item[data-state="done"] .shipment-step-progress__dot {
	background: var(--color-brand-primary, #095866);
	border-color: var(--color-brand-primary, #095866);
	color: #fff;
}
.shipment-step-progress__label {
	font-size: 14px;
	font-weight: 600;
	color: var(--color-brand-text-secondary, #5A6474);
	white-space: nowrap;
}
.shipment-step-progress__item[data-state="active"] .shipment-step-progress__label {
	color: var(--color-brand-text, #1D2738);
	font-weight: 700;
}

/* === Mobile view === */
.shipment-step-progress__mobile {
	display: block;
	padding: 12px 0;
}
.shipment-step-progress__mobile-row {
	display: flex;
	justify-content: space-between;
	align-items: baseline;
	margin-bottom: 8px;
}
.shipment-step-progress__mobile-current {
	font-size: 13px;
	font-weight: 700;
	color: var(--color-brand-text, #1D2738);
}
.shipment-step-progress__mobile-pct {
	font-size: 12px;
	font-weight: 600;
	color: var(--color-brand-text-muted, #6b7280);
}
.shipment-step-progress__mobile-bar {
	height: 4px;
	background: var(--color-brand-border, #E9EBEC);
	border-radius: 999px;
	overflow: hidden;
}
.shipment-step-progress__mobile-fill {
	height: 100%;
	background: linear-gradient(90deg, var(--color-brand-primary, #095866), var(--color-brand-accent, #E44203));
	border-radius: 999px;
	transition: width 300ms ease;
}
</style>
