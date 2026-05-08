<script setup>
defineProps({
	couponPanelOpen: { type: Boolean, default: false },
	couponApplied: { type: [Object, null], default: null },
	couponCode: { type: String, default: '' },
	couponLoading: { type: Boolean, default: false },
	couponError: { type: String, default: '' },
	validateCoupon: { type: Function, required: true },
	removeCoupon: { type: Function, required: true },
});
const emit = defineEmits(['update:couponPanelOpen', 'update:couponCode']);
</script>

<template>
	<div class="rounded-[18px] border border-[#DFE2E7] bg-white px-[16px] py-[14px]" style="box-shadow: 0 8px 26px rgba(15,23,42,0.04)">
		<button
			type="button"
			class="w-full flex items-center justify-between gap-[12px] text-left"
			@click="emit('update:couponPanelOpen', !couponPanelOpen)">
			<div>
				<p class="text-[11px] uppercase tracking-[0.14em] text-[#7C8594]" style="font-weight:800">Codice promozionale</p>
				<p class="mt-[6px] text-[14px] text-[var(--color-brand-text)]" style="font-weight:700">
					{{ couponApplied ? 'Coupon applicato' : 'Hai un codice o un invito?' }}
				</p>
			</div>
			<span
				class="shrink-0 text-[#C0C5CC]"
				:style="{ transform: couponPanelOpen ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.3s cubic-bezier(0.22,1,0.36,1)' }"
				aria-hidden="true">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M6 9l6 6 6-6" />
				</svg>
			</span>
		</button>

		<Transition name="payment-panel">
			<div v-if="couponPanelOpen" class="mt-[12px] flex flex-col gap-[8px]">
				<div class="flex flex-col gap-[10px] sm:flex-row">
					<input
						:value="couponCode"
						type="text"
						placeholder="Inserisci il codice"
						class="flex-1 h-[46px] rounded-card border border-[#D9E1EA] bg-[#F8F9FB] px-[16px] text-[15px] text-[var(--color-brand-text)] outline-none focus:border-[#0b7d92] focus:ring-[3px] focus:ring-[rgba(11,125,146,0.12)]"
						@input="emit('update:couponCode', $event.target.value)">
					<SfButton
						v-if="!couponApplied"
						size="lg"
						:loading="couponLoading"
						@click="validateCoupon">
						{{ couponLoading ? 'Verifica...' : 'Applica' }}
					</SfButton>
					<SfButton
						v-else
						variant="secondary"
						size="lg"
						@click="removeCoupon">
						Rimuovi
					</SfButton>
				</div>
				<p v-if="couponError" class="text-[13px] leading-[1.55] text-[#A64016]" style="font-weight:700">{{ couponError }}</p>
				<p v-if="couponApplied" class="text-[13px] leading-[1.55] text-[var(--color-brand-success-fg)]" style="font-weight:700">
					{{ couponApplied.code || couponCode }} attivo.
				</p>
			</div>
		</Transition>
	</div>
</template>
