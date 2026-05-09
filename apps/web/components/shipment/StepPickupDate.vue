<script setup>
const props = defineProps({
	dateError: { type: String, default: null },
	daysInMonth: { type: Array, required: true },
	services: { type: Object, required: true },
});

const emit = defineEmits(['choose-date']);

const isSelectedDay = (day) => props.services.date === day.formattedDate;
const trackRef = ref(null);
const showTrackNav = computed(() => props.daysInMonth.length > 6);

const scrollTrack = (direction) => {
	trackRef.value?.scrollBy?.({
		left: direction * 288,
		behavior: 'smooth',
	});
};
</script>

<template>
	<div class="scroll-mt-[88px] w-full">

		<!-- Error alert -->
		<div
			v-if="dateError"
			data-pickup-date-alert
			role="alert"
			aria-live="polite"
			class="mb-3 rounded-card p-3 bg-red-50 border border-red-200">
			<p class="text-sm font-semibold text-brand-error">{{ dateError }}</p>
		</div>

		<!-- Section header — coerente con .service-stage-section__header (icon + title + border-bottom) -->
		<div class="service-stage-section__header flex items-center justify-between">
			<div class="flex items-center gap-3">
				<div class="service-stage-section__icon">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-brand-primary">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
						<line x1="16" y1="2" x2="16" y2="6"/>
						<line x1="8" y1="2" x2="8" y2="6"/>
						<line x1="3" y1="10" x2="21" y2="10"/>
					</svg>
				</div>
				<h4 class="service-stage-section__title font-display m-0">Data di ritiro</h4>
			</div>

			<!-- Scroll arrows -->
			<div v-if="showTrackNav" class="flex items-center gap-1.5" role="group" aria-label="Scorri i giorni disponibili">
				<button
					type="button"
					aria-label="Giorni precedenti"
					class="w-9 h-9 rounded-full bg-brand-card border border-brand-border text-brand-text-muted flex items-center justify-center hover:bg-brand-primary hover:text-white hover:border-brand-primary transition focus-visible:ring-2 focus-visible:ring-brand-primary/30 active:scale-[0.97]"
					@click="scrollTrack(-1)">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="m15 18-6-6 6-6"/>
					</svg>
				</button>
				<button
					type="button"
					aria-label="Giorni successivi"
					class="w-9 h-9 rounded-full bg-brand-card border border-brand-border text-brand-text-muted flex items-center justify-center hover:bg-brand-primary hover:text-white hover:border-brand-primary transition focus-visible:ring-2 focus-visible:ring-brand-primary/30 active:scale-[0.97]"
					@click="scrollTrack(1)">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="m9 18 6-6-6-6"/>
					</svg>
				</button>
			</div>
		</div>

		<!-- Card container -->
		<div class="rounded-card border border-brand-border bg-brand-bg-alt p-4 md:p-5 relative shadow-sf-sm">

			<!-- Scrollable row -->
			<div
				ref="trackRef"
				class="flex gap-2 overflow-x-auto snap-x snap-mandatory px-2.5 pb-1.5 pt-2.5"
				style="scrollbar-width:none; -webkit-overflow-scrolling:touch; scroll-padding-inline: 10px"
				aria-label="Giorni disponibili per il ritiro">

				<button
					v-for="(day, dayIdx) in props.daysInMonth"
					:id="`date-${day.formattedDate}`"
					:key="day.date.toISOString()"
					type="button"
					:data-pickup-day="day.formattedDate"
					class="snap-start shrink-0 w-[78px] sm:w-[84px] h-[92px] sm:h-[98px] rounded-card flex flex-col items-center justify-center cursor-pointer transition focus-visible:ring-2 focus-visible:ring-brand-primary/30 relative"
					:class="isSelectedDay(day)
						? 'ring-2 ring-brand-primary bg-brand-primary/[0.06] shadow-sf'
						: 'border border-brand-border bg-brand-card hover:border-brand-primary/40 hover:shadow-sf'"
					:aria-pressed="isSelectedDay(day) ? 'true' : 'false'"
					:aria-label="`Seleziona ${day.weekday} ${day.dayNumber} ${day.monthAbbr}`"
					@click="emit('choose-date', day)">

					<!-- Badge "Primo" sulla PRIMA card disponibile, ancorato al top del bottone (etichetta data),
					     non più floating sopra il container. Cavalca il top-border del tile, centrato. -->
					<span
						v-if="dayIdx === 0"
						class="absolute -top-[8px] left-1/2 -translate-x-1/2 inline-block px-2 py-[2px] rounded-pill text-[9px] uppercase tracking-[0.04em] bg-brand-primary text-white font-bold leading-none shadow-sf-sm pointer-events-none">
						Primo
					</span>

					<span
						class="text-[11px] uppercase tracking-[0.5px] font-bold"
						:class="isSelectedDay(day) ? 'text-brand-primary' : 'text-brand-text-muted'">
						{{ day.weekday }}
					</span>
					<span
						class="text-[20px] sm:text-[22px] font-extrabold leading-none mt-0.5 mb-px tracking-tight"
						:class="isSelectedDay(day) ? 'text-brand-primary' : 'text-brand-text'">
						{{ day.dayNumber }}
					</span>
					<span
						class="text-[11px] font-semibold"
						:class="isSelectedDay(day) ? 'text-brand-primary' : 'text-brand-text-muted'">
						{{ day.monthAbbr }}
					</span>
				</button>

			</div>
		</div>

	</div>
</template>
