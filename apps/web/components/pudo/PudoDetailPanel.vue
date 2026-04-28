<script setup>import { computed } from 'vue';
const props = defineProps({
  pudo: {  },
  open: { type: Boolean, default: false },
});
const emit = defineEmits();
// ── Helpers orari ──
const dayNames = [
    { key: 'mon', label: 'Lunedi', tokens: ['lun', 'lunedi', 'mon'] },
    { key: 'tue', label: 'Martedi', tokens: ['mar', 'martedi', 'tue'] },
    { key: 'wed', label: 'Mercoledi', tokens: ['mer', 'mercoledi', 'wed'] },
    { key: 'thu', label: 'Giovedi', tokens: ['gio', 'giovedi', 'thu'] },
    { key: 'fri', label: 'Venerdi', tokens: ['ven', 'venerdi', 'fri'] },
    { key: 'sat', label: 'Sabato', tokens: ['sab', 'sabato', 'sat'] },
    { key: 'sun', label: 'Domenica', tokens: ['dom', 'domenica', 'sun'] },
];
const splitHoursParts = (raw) => {
    if (!raw)
        return [];
    if (Array.isArray(raw))
        return raw.map((s) => String(s || '').trim()).filter(Boolean);
    if (typeof raw === 'object')
        return Object.entries(raw).map(([k, v]) => `${k}: ${v}`);
    return String(raw).split(/\n|\||;/g).map((s) => s.trim()).filter(Boolean);
};
const weekHours = computed(() => {
    const parts = splitHoursParts(props.pudo?.opening_hours);
    if (!parts.length)
        return [];
    return dayNames.map((d) => {
        const found = parts.find((p) => d.tokens.some((t) => p.toLowerCase().includes(t)));
        return { label: d.label, value: found ? found.replace(/^[^:]+:\s*/i, '').trim() || found : 'Chiuso' };
    });
});
const fullAddress = computed(() => {
    const p = props.pudo;
    if (!p)
        return '';
    return [p.address, [p.zip_code, p.city].filter(Boolean).join(' '), p.province ? `(${p.province})` : '']
        .filter(Boolean)
        .join(', ');
});
const mapsHref = computed(() => {
    const p = props.pudo;
    if (!p)
        return '#';
    if (p.latitude != null && p.longitude != null) {
        return `https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}`;
    }
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(fullAddress.value)}`;
});
const pudoTypeLabel = computed(() => {
    const name = (props.pudo?.name || '').toLowerCase();
    if (name.includes('fermo deposito'))
        return 'Fermo Deposito';
    if (name.includes('fermo posta'))
        return 'Fermo Posta';
    if (name.includes('locker'))
        return 'Locker BRT';
    return 'Punto BRT';
});
const distanceText = computed(() => {
    const v = Number(props.pudo?.distance_meters);
    if (!Number.isFinite(v))
        return '';
    return v >= 1000 ? `${(v / 1000).toFixed(1)} km` : `${Math.round(v)} m`;
});
const handleChoose = () => {
    if (props.pudo)
        emit('pudo-selected', props.pudo);
};
</script>

<template>
	<Teleport to="body">
		<transition name="pudo-panel">
			<div
				v-if="open && pudo"
				class="fixed inset-0 z-[1100] flex items-end tablet:items-stretch tablet:justify-end bg-[rgba(9,88,102,0.32)] backdrop-blur-[2px]"
				role="dialog"
				aria-modal="true"
				:aria-label="`Dettagli ${pudo.name}`"
				@click.self="emit('close')">
				<div class="w-full tablet:w-[420px] max-h-[88vh] tablet:max-h-none tablet:h-full bg-white rounded-t-[20px] tablet:rounded-none shadow-[0_-12px_40px_rgba(9,88,102,0.18)] tablet:shadow-[-12px_0_32px_rgba(9,88,102,0.12)] flex flex-col overflow-hidden">
					<!-- Header -->
					<div class="px-[20px] pt-[18px] pb-[14px] border-b border-[var(--color-brand-border,#E9EBEC)]">
						<div class="flex items-start justify-between gap-[12px]">
							<div class="min-w-0 flex-1">
								<span class="inline-flex items-center text-[0.6875rem] font-semibold uppercase tracking-wide text-[var(--color-brand-primary,#095866)] bg-[#E6F0F2] rounded-full px-[8px] py-[2px] mb-[6px]">
									{{ pudoTypeLabel }}
								</span>
								<h2 class="text-[1.0625rem] font-bold text-[var(--color-brand-text,#0f172a)] leading-tight">
									{{ pudo.name }}
								</h2>
								<p v-if="distanceText" class="text-[0.8125rem] text-[var(--color-brand-primary,#095866)] font-semibold mt-[4px]">
									{{ distanceText }} dalla tua posizione
								</p>
							</div>
							<button
								type="button"
								class="shrink-0 w-[32px] h-[32px] inline-flex items-center justify-center rounded-full text-[var(--color-brand-text-secondary,#4b5563)] hover:bg-[#F2F8F9] hover:text-[var(--color-brand-primary,#095866)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand-primary,#095866)] cursor-pointer"
								aria-label="Chiudi dettagli"
								@click="emit('close')">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
									stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<line x1="18" y1="6" x2="6" y2="18"></line>
									<line x1="6" y1="6" x2="18" y2="18"></line>
								</svg>
							</button>
						</div>
					</div>

					<!-- Body scrollable -->
					<div class="flex-1 overflow-y-auto px-[20px] py-[18px] space-y-[20px]">
						<!-- Indirizzo -->
						<section>
							<h3 class="text-[0.75rem] font-semibold uppercase tracking-wide text-[var(--color-brand-text-secondary,#4b5563)] mb-[8px]">
								Indirizzo
							</h3>
							<p class="text-[0.9375rem] text-[var(--color-brand-text,#0f172a)] leading-snug">
								{{ fullAddress }}
							</p>
							<a
								:href="mapsHref"
								target="_blank"
								rel="noopener"
								class="inline-flex items-center gap-[6px] mt-[10px] text-[0.875rem] font-semibold text-[var(--color-brand-primary,#095866)] hover:text-[var(--color-brand-primary-hover,#074a56)] underline-offset-2 hover:underline">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
									stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
								</svg>
								Indicazioni stradali
							</a>
						</section>

						<!-- Orari settimanali -->
						<section v-if="weekHours.length">
							<h3 class="text-[0.75rem] font-semibold uppercase tracking-wide text-[var(--color-brand-text-secondary,#4b5563)] mb-[8px]">
								Orari di apertura
							</h3>
							<dl class="rounded-[12px] border border-[var(--color-brand-border,#E9EBEC)] overflow-hidden divide-y divide-[var(--color-brand-border,#E9EBEC)]">
								<div
									v-for="row in weekHours"
									:key="row.label"
									class="grid grid-cols-[110px_1fr] text-[0.8125rem]">
									<dt class="bg-[#FAFCFC] px-[12px] py-[8px] font-semibold text-[var(--color-brand-text,#0f172a)]">
										{{ row.label }}
									</dt>
									<dd class="px-[12px] py-[8px] text-[var(--color-brand-text-secondary,#4b5563)]">
										{{ row.value }}
									</dd>
								</div>
							</dl>
						</section>

						<!-- Servizi -->
						<section>
							<h3 class="text-[0.75rem] font-semibold uppercase tracking-wide text-[var(--color-brand-text-secondary,#4b5563)] mb-[8px]">
								Servizi disponibili
							</h3>
							<div class="flex flex-wrap gap-[8px]">
								<span class="inline-flex items-center gap-[6px] text-[0.8125rem] font-medium text-[var(--color-brand-text,#0f172a)] bg-[#F2F8F9] border border-[var(--color-brand-border,#E9EBEC)] rounded-full px-[10px] py-[5px]">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
										<polyline points="20 6 9 17 4 12"></polyline>
									</svg>
									Ritiro pacchi
								</span>
								<span class="inline-flex items-center gap-[6px] text-[0.8125rem] font-medium text-[var(--color-brand-text,#0f172a)] bg-[#F2F8F9] border border-[var(--color-brand-border,#E9EBEC)] rounded-full px-[10px] py-[5px]">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
										<polyline points="20 6 9 17 4 12"></polyline>
									</svg>
									Consegna pacchi
								</span>
							</div>
						</section>

						<!-- Note -->
						<section v-if="pudo.localization_hint">
							<h3 class="text-[0.75rem] font-semibold uppercase tracking-wide text-[var(--color-brand-text-secondary,#4b5563)] mb-[8px]">
								Note
							</h3>
							<p class="text-[0.875rem] text-[var(--color-brand-text-secondary,#4b5563)] leading-relaxed">
								{{ pudo.localization_hint }}
							</p>
						</section>
					</div>

					<!-- Footer azione -->
					<div class="px-[20px] py-[14px] border-t border-[var(--color-brand-border,#E9EBEC)] bg-white">
						<button
							type="button"
							class="w-full inline-flex items-center justify-center gap-[8px] h-[46px] rounded-[14px] bg-[var(--color-brand-primary,#095866)] text-white text-[0.9375rem] font-semibold hover:bg-[var(--color-brand-primary-hover,#074a56)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E44203] cursor-pointer transition-colors"
							@click="handleChoose">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<polyline points="20 6 9 17 4 12"></polyline>
							</svg>
							Scegli questo punto
						</button>
					</div>
				</div>
			</div>
		</transition>
	</Teleport>
</template>

<!-- Stili estratti in assets/css/tracking.css (importato da main.css). -->
