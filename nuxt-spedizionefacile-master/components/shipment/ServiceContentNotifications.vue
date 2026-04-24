<script setup>
defineProps({
	contentDescription: { type: String, default: '' },
	contentError: { type: [String, Object], default: null },
	contentFieldHint: { type: String, default: '' },
	smsEmailNotification: { type: Boolean, default: false },
	notificationPriceLabel: { type: String, default: '' },
});

defineEmits(['update:content-description', 'update:content-error', 'update:sms-email-notification']);
</script>

<template>
	<section>

		<!-- Section header -->
		<div class="flex items-center gap-[10px] mb-[12px]">
			<div class="w-[32px] h-[32px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
					<path d="M13.73 21a2 2 0 0 1-3.46 0"/>
				</svg>
			</div>
			<span class="text-[16px] sm:text-[17px] text-[#1d2738] font-[700]">Dettagli</span>
		</div>

		<!-- Divider -->
		<div class="h-[1px] bg-[#D5D9E0] mb-[14px]"></div>

		<!-- Grid -->
		<div class="grid grid-cols-1 sm:grid-cols-2 gap-[10px]">

			<!-- Contenuto pacco field -->
			<div>
				<label
					for="content_description"
					class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block font-[700]">
					Contenuto pacco
				</label>
				<div class="service-content-field__control">
					<input
						type="text"
						id="content_description"
						:value="contentDescription"
						@input="
							$emit('update:content-description', $event.target.value);
							$emit('update:content-error', null);
						"
						placeholder="Abbigliamento, documenti..."
						maxlength="255"
						:class="[
							'h-[48px] sm:h-[50px] w-full rounded-[16px] px-[14px] bg-white outline-none text-[#1d2738] text-[14px] transition-all duration-[250ms] placeholder:text-[#b0b5be]',
							contentError
								? 'ring-[2px] ring-[#ef4444]'
								: 'ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60'
						]" />
				</div>
				<div class="service-content-field__feedback">
					<p v-if="contentError" class="text-[0.8125rem] font-[600] text-[#c85a1a]">
						{{ contentFieldHint }}
					</p>
				</div>
			</div>

			<!-- SMS / Email notification toggle -->
			<div>
				<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block font-[700]">
					Notifiche
				</label>
				<button
					type="button"
					class="w-full h-[48px] sm:h-[50px] rounded-[16px] px-[14px] flex items-center gap-[10px] text-left transition-all duration-[350ms] cursor-pointer bg-white"
					:class="smsEmailNotification
						? 'ring-[2.5px] ring-[#095866] shadow-[0_2px_10px_rgba(9,88,102,0.1)]'
						: 'ring-[1.5px] ring-[#DFE2E7] hover:ring-[2px] hover:ring-[#095866]/50 hover:bg-[rgba(9,88,102,0.03)]'"
					:aria-label="smsEmailNotification ? 'Rimuovi notifiche spedizione' : 'Attiva notifiche spedizione'"
					:aria-pressed="smsEmailNotification ? 'true' : 'false'"
					@click="$emit('update:sms-email-notification', !smsEmailNotification)">

					<!-- Bell icon -->
					<svg
						width="16"
						height="16"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
						:class="smsEmailNotification ? 'text-[#095866]' : 'text-[var(--color-brand-text-muted)]'"
						aria-hidden="true">
						<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
						<path d="M13.73 21a2 2 0 0 1-3.46 0"/>
					</svg>

					<!-- Label -->
					<span class="flex-1 text-[14px] text-[#1d2738] font-[600]">{{ notificationPriceLabel }}</span>

					<!-- Checkmark box -->
					<span
						class="w-[22px] h-[22px] rounded-[6px] flex items-center justify-center transition-all duration-[350ms] shrink-0"
						:class="smsEmailNotification
							? 'bg-[#095866]'
							: 'bg-[#E6E9EE] ring-[1.5px] ring-[#C0C5CC]'">
						<svg
							v-if="smsEmailNotification"
							width="11"
							height="11"
							viewBox="0 0 24 24"
							fill="none"
							stroke="white"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
							aria-hidden="true">
							<polyline points="20 6 9 17 4 12"/>
						</svg>
					</span>
				</button>
			</div>

		</div>
	</section>
</template>

<style scoped>
.service-content-field__control {
	min-width: 0;
}

.service-content-field__feedback {
	min-height: 1.75rem;
	display: flex;
	align-items: flex-start;
	padding-top: 0.4rem;
}
</style>
