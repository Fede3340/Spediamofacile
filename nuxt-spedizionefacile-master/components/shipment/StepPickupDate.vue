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
			class="mb-[12px] rounded-[12px] p-[12px] bg-[#fef3f2] border border-[#fecdca]">
			<p class="text-[13px] font-[600] text-[#b91c1c]">{{ dateError }}</p>
		</div>

		<!-- Section header -->
		<div class="flex items-center justify-between mb-[12px]">
			<div class="flex items-center gap-[10px]">
				<div class="w-[32px] h-[32px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
						<line x1="16" y1="2" x2="16" y2="6"/>
						<line x1="8" y1="2" x2="8" y2="6"/>
						<line x1="3" y1="10" x2="21" y2="10"/>
					</svg>
				</div>
				<span class="text-[16px] sm:text-[17px] text-[#1d2738] font-[700]">Data di ritiro</span>
			</div>

			<!-- Scroll arrows -->
			<div v-if="showTrackNav" class="flex items-center gap-[6px]" aria-label="Scorri i giorni disponibili">
				<button
					type="button"
					class="w-[36px] h-[36px] rounded-full bg-white ring-[1px] ring-[#DFE2E7] text-[#777] flex items-center justify-center hover:bg-[#095866] hover:text-white transition-all duration-[350ms] cursor-pointer active:scale-[0.93]"
					@click="scrollTrack(-1)">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
						<path d="m15 18-6-6 6-6"/>
					</svg>
				</button>
				<button
					type="button"
					class="w-[36px] h-[36px] rounded-full bg-white ring-[1px] ring-[#DFE2E7] text-[#777] flex items-center justify-center hover:bg-[#095866] hover:text-white transition-all duration-[350ms] cursor-pointer active:scale-[0.93]"
					@click="scrollTrack(1)">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
						<path d="m9 18 6-6-6-6"/>
					</svg>
				</button>
			</div>
		</div>

		<!-- Grey container with scrollable day track -->
		<div
			class="rounded-[16px] px-[14px] sm:px-[16px] pt-[14px] sm:pt-[16px] pb-[14px] sm:pb-[16px] relative"
			style="background:#E6E9EE; box-shadow:inset 0 1px 2px rgba(0,0,0,0.04)">

			<!-- "Primo" badge above first card -->
			<div
				class="absolute top-[5px] left-[18px] sm:left-[20px] z-10 pointer-events-none flex justify-center"
				style="width:78px">
				<span class="px-[8px] py-[1px] rounded-full text-[9px] bg-[#E44203] text-white font-[700]">Primo</span>
			</div>

			<!-- Scrollable row -->
			<div
				ref="trackRef"
				class="flex gap-[8px] overflow-x-auto snap-x snap-mandatory px-[4px] pb-[4px] pt-[10px]"
				style="scrollbar-width:none; -webkit-overflow-scrolling:touch"
				aria-label="Giorni disponibili per il ritiro">

				<button
					v-for="(day, dayIndex) in props.daysInMonth"
					:key="day.date.toISOString()"
					type="button"
					:id="`date-${day.formattedDate}`"
					:data-pickup-day="day.formattedDate"
					class="snap-start shrink-0 w-[78px] sm:w-[84px] h-[92px] sm:h-[98px] rounded-[14px] flex flex-col items-center justify-center cursor-pointer transition-all duration-[350ms]"
					:class="isSelectedDay(day)
						? 'ring-[2.5px] ring-[#095866] bg-white shadow-[0_4px_16px_rgba(9,88,102,0.15)]'
						: 'ring-[1.5px] ring-[#DFE2E7] bg-white hover:ring-[2px] hover:ring-[#095866]/50 hover:shadow-[0_2px_10px_rgba(9,88,102,0.08)]'"
					:aria-pressed="isSelectedDay(day) ? 'true' : 'false'"
					:aria-label="`Seleziona ${day.weekday} ${day.dayNumber} ${day.monthAbbr}`"
					@click="emit('choose-date', day)">

					<span
						class="text-[11px] uppercase tracking-[0.5px] font-[700]"
						:class="isSelectedDay(day) ? 'text-[#095866]' : 'text-[#999]'">
						{{ day.weekday }}
					</span>
					<span
						class="text-[26px] sm:text-[28px] font-[800] leading-none mt-[2px] mb-[1px] tracking-tight"
						:class="isSelectedDay(day) ? 'text-[#095866]' : 'text-[#1d2738]'">
						{{ day.dayNumber }}
					</span>
					<span
						class="text-[11px] font-[600]"
						:class="isSelectedDay(day) ? 'text-[#095866]' : 'text-[#999]'">
						{{ day.monthAbbr }}
					</span>
				</button>

			</div>
		</div>

	</div>
</template>
