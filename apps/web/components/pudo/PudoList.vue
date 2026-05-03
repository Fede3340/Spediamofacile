<script setup>
const props = defineProps({
  items: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  selectedKey: { type: [String, Number, null], default: null },
  hasReference: { type: Boolean, default: false },
});
const emit = defineEmits(['select']);
const isSelected = (p) => props.selectedKey != null && String(props.selectedKey) === String(p.ui_key);
const formatDistance = (meters) => {
    const v = Number(meters);
    if (!Number.isFinite(v))
        return '';
    return v >= 1000 ? `${(v / 1000).toFixed(1)} km` : `${Math.round(v)} m`;
};
const todayHoursPreview = (raw) => {
    if (!raw)
        return '';
    if (typeof raw === 'string')
        return raw.split(/[\n|;]/g)[0]?.trim() || '';
    if (Array.isArray(raw))
        return String(raw[0] || '').trim();
    if (typeof raw === 'object') {
        const entries = Object.entries(raw);
        return entries[0] ? `${entries[0][0]}: ${entries[0][1]}` : '';
    }
    return '';
};
const hasOpenInfo = (raw) => Boolean(todayHoursPreview(raw));
const pudoTypeLabel = (p) => {
    const name = (p.name || '').toLowerCase();
    if (name.includes('fermo deposito'))
        return 'Fermo Deposito';
    if (name.includes('fermo posta'))
        return 'Fermo Posta';
    if (name.includes('locker'))
        return 'Locker BRT';
    return 'Punto BRT';
};
const itemsCount = computed(() => props.items.length);
</script>

<template>
	<div class="flex flex-col h-full">
		<!-- Loading skeletons -->
		<template v-if="loading && itemsCount === 0">
			<div class="space-y-[10px] p-[14px]">
				<div
					v-for="n in 4"
					:key="n"
					class="h-[110px] rounded-[14px] bg-[#F2F8F9] border border-[var(--color-brand-border,#E9EBEC)] animate-pulse"/>
			</div>
		</template>

		<!-- Empty state -->
		<template v-else-if="itemsCount === 0">
			<div class="flex flex-col items-center justify-center text-center px-[20px] py-[40px] text-[var(--color-brand-text-secondary,#4b5563)]">
				<div class="w-[48px] h-[48px] rounded-full bg-[#F2F8F9] flex items-center justify-center mb-[12px]">
					<svg
width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
						stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="11" cy="11" r="8"/>
						<path d="m21 21-4.35-4.35"/>
					</svg>
				</div>
				<p class="text-[0.875rem] font-semibold text-[var(--color-brand-text,#0f172a)]">
					Nessun punto trovato
				</p>
				<p class="text-[0.8125rem] mt-[6px] max-w-[260px]">
					Inserisci un CAP, una citta o un indirizzo per cercare i punti BRT piu vicini.
				</p>
			</div>
		</template>

		<!-- Results list -->
		<ul v-else class="flex-1 overflow-y-auto divide-y divide-[var(--color-brand-border,#E9EBEC)]">
			<li
				v-for="p in items"
				:key="String(p.ui_key)">
				<button
					type="button"
					:aria-pressed="isSelected(p)"
					class="w-full text-left px-[16px] py-[14px] cursor-pointer transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand-primary,#095866)]"
					:class="isSelected(p)
						? 'bg-[#F2F8F9]'
						: 'bg-white hover:bg-[#FAFCFC]'"
					@click="emit('select', p)">
					<!-- Riga 1: titolo + distanza -->
					<div class="flex items-start justify-between gap-[10px]">
						<div class="min-w-0 flex-1">
							<div class="flex items-center gap-[6px] mb-[2px]">
								<span class="inline-flex items-center text-[0.6875rem] font-semibold uppercase tracking-wide text-[var(--color-brand-primary,#095866)] bg-[#E6F0F2] rounded-full px-[8px] py-[2px]">
									{{ pudoTypeLabel(p) }}
								</span>
							</div>
							<h3 class="text-[0.9375rem] font-semibold text-[var(--color-brand-text,#0f172a)] leading-tight truncate">
								{{ p.name || 'Punto di ritiro BRT' }}
							</h3>
						</div>
						<span
							v-if="hasReference && p.distance_meters != null"
							class="shrink-0 text-[0.75rem] font-semibold text-[var(--color-brand-primary,#095866)] bg-[#E6F0F2] rounded-full px-[8px] py-[2px] whitespace-nowrap">
							{{ formatDistance(p.distance_meters) }}
						</span>
					</div>

					<!-- Riga 2: indirizzo -->
					<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary,#4b5563)] mt-[6px] leading-snug">
						{{ p.address }}<template v-if="p.address && (p.zip_code || p.city)">, </template>{{ p.zip_code }} {{ p.city }}<span v-if="p.province"> ({{ p.province }})</span>
					</p>

					<!-- Riga 3: orari oggi + chip indicatore aperto -->
					<div class="flex items-center gap-[8px] mt-[8px] flex-wrap">
						<span
							v-if="hasOpenInfo(p.opening_hours)"
							class="inline-flex items-center gap-[5px] text-[0.75rem] text-[var(--color-brand-text-secondary,#4b5563)]">
							<svg
width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<circle cx="12" cy="12" r="10"/>
								<polyline points="12 6 12 12 16 14"/>
							</svg>
							{{ todayHoursPreview(p.opening_hours) }}
						</span>
						<span
							v-else
							class="inline-flex items-center gap-[5px] text-[0.75rem] text-[var(--color-brand-text-secondary,#4b5563)]">
							Orari su dettaglio
						</span>
					</div>
				</button>
			</li>
		</ul>
	</div>
</template>
