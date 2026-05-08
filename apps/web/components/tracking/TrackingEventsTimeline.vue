<!-- COMPONENTE: TrackingEventsTimeline (tracking/TrackingEventsTimeline.vue) -->
<script setup>
const props = defineProps({
	events: {
		type: Array,
		default: () => [],
	},
	emptyMessage: {
		type: String,
		default: 'Nessun evento di tracking ancora disponibile. Gli aggiornamenti arriveranno appena il corriere prende in carico la spedizione.',
	},
	loading: {
		type: Boolean,
		default: false,
	},
});

// Ordine cronologico inverso (il più recente in cima)
const sortedEvents = computed(() => {
	if (!props.events?.length) return [];
	return [...props.events].sort((a, b) => {
		const da = new Date(a.at).getTime() || 0;
		const db = new Date(b.at).getTime() || 0;
		return db - da;
	});
});

const formatAbs = (iso) => {
	if (!iso) return '';
	try {
		const d = new Date(iso);
		return d.toLocaleString('it-IT', {
			day: '2-digit',
			month: 'short',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
			timeZone: 'Europe/Rome',
		});
	} catch {
		return '';
	}
};

const formatRel = (iso) => {
	if (!iso) return '';
	const d = new Date(iso).getTime();
	if (Number.isNaN(d)) return '';
	const diffMs = Date.now() - d;
	const sec = Math.round(diffMs / 1000);
	if (sec < 60) return 'pochi secondi fa';
	const min = Math.round(sec / 60);
	if (min < 60) return `${min} ${min === 1 ? 'minuto' : 'minuti'} fa`;
	const h = Math.round(min / 60);
	if (h < 24) return `${h} ${h === 1 ? 'ora' : 'ore'} fa`;
	const days = Math.round(h / 24);
	if (days < 30) return `${days} ${days === 1 ? 'giorno' : 'giorni'} fa`;
	const months = Math.round(days / 30);
	return `${months} ${months === 1 ? 'mese' : 'mesi'} fa`;
};

const iconForCode = (code) => {
	// Mapping codici BRT/SF → icona semantica. Default = pacco generico.
	const c = (code || '').toLowerCase();
	if (c.includes('deliver') || c.includes('consegn')) return 'check';
	if (c.includes('out_for') || c.includes('in_consegna')) return 'truck';
	if (c.includes('transit') || c.includes('viaggio')) return 'route';
	if (c.includes('giacenza') || c.includes('hold')) return 'pause';
	if (c.includes('return') || c.includes('reso') || c.includes('refus')) return 'undo';
	if (c.includes('label') || c.includes('etichetta')) return 'label';
	if (c.includes('pick') || c.includes('ritir')) return 'pickup';
	return 'box';
};
</script>

<template>
	<div class="tracking-events">
		<!-- Loading skeleton -->
		<div v-if="loading" class="grid gap-[12px]" aria-busy="true" aria-live="polite">
			<div v-for="i in 4" :key="i" class="flex gap-[14px] items-start animate-pulse">
				<div class="w-[36px] h-[36px] rounded-full bg-[#E6E9EE] shrink-0"/>
				<div class="flex-1 grid gap-[6px]">
					<div class="h-[14px] rounded bg-[#E6E9EE] w-[60%]"/>
					<div class="h-[10px] rounded bg-[#EEF1F5] w-[40%]"/>
				</div>
			</div>
		</div>

		<!-- Empty -->
		<div
			v-else-if="!sortedEvents.length"
			class="rounded-control p-[18px] text-center"
			data-surface="grey-inset"
			role="status"
		>
			<div class="w-[44px] h-[44px] rounded-full mx-auto mb-[10px] flex items-center justify-center" style="background: rgba(9, 88, 102, 0.08)">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10" />
					<polyline points="12 6 12 12 16 14" />
				</svg>
			</div>
			<p class="text-[13px] text-[var(--color-brand-text-secondary)] leading-[1.55] max-w-[44ch] mx-auto m-0">{{ emptyMessage }}</p>
		</div>

		<!-- Lista eventi -->
		<ol v-else class="grid gap-0 m-0 p-0 list-none" aria-label="Eventi spedizione, dal più recente">
			<li
				v-for="(ev, idx) in sortedEvents"
				:key="`${ev.at}-${idx}`"
				class="flex gap-[14px]"
			>
				<!-- Icona + linea -->
				<div class="flex flex-col items-center">
					<div
						class="inline-flex items-center justify-center w-[36px] h-[36px] rounded-full shrink-0 transition-all duration-200"
						:class="idx === 0
							? 'bg-[var(--color-brand-primary)] text-white shadow-[0_2px_6px_rgba(9,88,102,0.25)]'
							: 'bg-white text-[var(--color-brand-primary)] shadow-[inset_0_0_0_1.5px_rgba(9,88,102,0.35)]'"
						aria-hidden="true"
					>
						<!-- check -->
						<svg v-if="iconForCode(ev.code) === 'check'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="20 6 9 17 4 12" />
						</svg>
						<!-- truck -->
						<svg v-else-if="iconForCode(ev.code) === 'truck'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="1" y="3" width="15" height="13" />
							<polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
							<circle cx="5.5" cy="18.5" r="2.5" />
							<circle cx="18.5" cy="18.5" r="2.5" />
						</svg>
						<!-- route -->
						<svg v-else-if="iconForCode(ev.code) === 'route'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="6" cy="19" r="3" />
							<path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15" />
							<circle cx="18" cy="5" r="3" />
						</svg>
						<!-- pause / giacenza -->
						<svg v-else-if="iconForCode(ev.code) === 'pause'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10" />
							<line x1="10" y1="9" x2="10" y2="15" />
							<line x1="14" y1="9" x2="14" y2="15" />
						</svg>
						<!-- undo / reso -->
						<svg v-else-if="iconForCode(ev.code) === 'undo'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="1 4 1 10 7 10" />
							<path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
						</svg>
						<!-- label -->
						<svg v-else-if="iconForCode(ev.code) === 'label'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
							<line x1="7" y1="7" x2="7.01" y2="7" />
						</svg>
						<!-- pickup -->
						<svg v-else-if="iconForCode(ev.code) === 'pickup'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
							<polyline points="3.27 6.96 12 12.01 20.73 6.96" />
							<line x1="12" y1="22.08" x2="12" y2="12" />
						</svg>
						<!-- box default -->
						<svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
						</svg>
					</div>
					<div
						v-if="idx < sortedEvents.length - 1"
						class="w-[2px] flex-1 min-h-[20px] rounded-[1px] mt-[4px]"
						:class="idx === 0 ? 'bg-[rgba(9,88,102,0.45)]' : 'bg-[#DFE2E7]'"
						aria-hidden="true"
					/>
				</div>

				<!-- Contenuto -->
				<div class="pb-[16px] flex-1 min-w-0" :class="{ 'pb-0': idx === sortedEvents.length - 1 }">
					<p class="text-[14px] text-[var(--color-brand-text)] m-0 leading-[1.4]" style="font-weight:600">
						{{ ev.label }}
					</p>
					<div class="flex flex-wrap items-center gap-x-[10px] gap-y-[2px] mt-[4px]">
						<span
							class="text-[12px] text-[var(--color-brand-text-secondary)]"
							:title="formatAbs(ev.at)"
						>
							{{ formatRel(ev.at) }}
						</span>
						<span class="text-[11px] text-[#7a8493]" style="font-variant-numeric: tabular-nums">
							{{ formatAbs(ev.at) }}
						</span>
					</div>
					<p
						v-if="ev.location"
						class="text-[12px] text-[var(--color-brand-text-secondary)] mt-[4px] m-0 inline-flex items-center gap-[4px]"
					>
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
							<circle cx="12" cy="10" r="3" />
						</svg>
						{{ ev.location }}
					</p>
				</div>
			</li>
		</ol>
	</div>
</template>

