<script setup>
import { computed } from 'vue';

const props = defineProps({
	paymentSuccess: { type: Boolean, default: false },
	paymentSummaryExpanded: { type: Boolean, default: false },
	trattaLabel: { type: String, default: '' },
	colloLabel: { type: String, default: '' },
	confirmationPickupDate: { type: String, default: '' },
	paymentDeliveryLabel: { type: String, default: '' },
	displayTotalText: { type: String, default: '' },
	finalTotalFormatted: { type: String, default: '' },
	summaryTotalPrice: { type: String, default: '' },
	summaryPackageLabel: { type: String, default: '' },
	summaryDimensionsLabel: { type: String, default: '' },
	confirmationOriginContact: { type: String, default: '' },
	confirmationDestinationContact: { type: String, default: '' },
	originAddress: { type: Object, required: true },
	destinationAddress: { type: Object, required: true },
	deliveryMode: { type: String, required: true },
	paymentSummaryServicesLabel: { type: String, default: '' },
	resolvedContentDescription: { type: String, default: '' },
	subtotalFormatted: { type: String, default: '' },
	discountFormatted: { type: String, default: '' },
	couponApplied: { type: [Object, null], default: null },
});
const emit = defineEmits(['update:paymentSummaryExpanded', 'edit-packages', 'edit-addresses', 'edit-services']);

const paymentSummaryToggleLabel = computed(() => {
	if (props.paymentSummaryExpanded) return 'Nascondi dettagli ordine';
	return props.paymentSuccess ? 'Vedi dettagli ordine' : 'Vedi dettagli ordine e modifica';
});

const sanitizeSummaryText = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();

const isMeaningfulSummaryText = (value) => {
	const normalized = sanitizeSummaryText(value);
	if (!normalized) return false;
	const lowered = normalized.toLowerCase();
	return !['n/d', 'nd', '—', '-', 'null', 'undefined'].includes(lowered);
};

const pickFirstMeaningfulSummaryText = (...candidates) => {
	for (const candidate of candidates) {
		if (isMeaningfulSummaryText(candidate)) return sanitizeSummaryText(candidate);
	}
	return '';
};

const buildAddressStreetLine = (address, fallback) => {
	const line = [
		sanitizeSummaryText(address?.address),
		sanitizeSummaryText(address?.address_number),
	].filter(Boolean).join(' ').trim();
	return line || fallback;
};

const buildAddressLocalityLine = (address, fallback) => {
	const line = [
		sanitizeSummaryText(address?.postal_code),
		sanitizeSummaryText(address?.city),
		sanitizeSummaryText(address?.province),
	].filter(Boolean).join(' ').trim();
	return line || fallback;
};

const hasResolvedRouteLabel = computed(() => {
	const normalized = sanitizeSummaryText(props.trattaLabel);
	if (!isMeaningfulSummaryText(normalized)) return false;
	const lowered = normalized.toLowerCase();
	return (
		!lowered.includes('da definire')
		&& lowered !== 'mittente e destinatario'
		&& lowered !== 'mittente e punto brt'
		&& !lowered.includes('destinazione da completare')
	);
});

const resolvedOriginContact = computed(() => (
	pickFirstMeaningfulSummaryText(
		props.confirmationOriginContact,
		props.originAddress?.full_name,
		props.originAddress?.name,
	) || 'Mittente da completare'
));

const resolvedDestinationContact = computed(() => (
	pickFirstMeaningfulSummaryText(
		props.confirmationDestinationContact,
		props.destinationAddress?.full_name,
		props.destinationAddress?.name,
		props.deliveryMode === 'pudo' ? props.destinationAddress?.city : '',
	) || (props.deliveryMode === 'pudo' ? 'Punto BRT da selezionare' : 'Destinatario da completare')
));

const resolvedTrattaLabel = computed(() => {
	if (hasResolvedRouteLabel.value) return sanitizeSummaryText(props.trattaLabel);
	const originCity = pickFirstMeaningfulSummaryText(props.originAddress?.city);
	const destinationLabel = props.deliveryMode === 'pudo'
		? pickFirstMeaningfulSummaryText(props.destinationAddress?.name, props.destinationAddress?.city, resolvedDestinationContact.value)
		: pickFirstMeaningfulSummaryText(props.destinationAddress?.city, resolvedDestinationContact.value);
	if (originCity && destinationLabel) return `${originCity} -> ${destinationLabel}`;
	if (originCity) return `${originCity} -> Destinazione da completare`;
	return 'Tratta da definire';
});

const resolvedOriginStreetLine = computed(() => buildAddressStreetLine(props.originAddress, 'Indirizzo da completare'));
const resolvedDestinationStreetLine = computed(() => buildAddressStreetLine(
	props.destinationAddress,
	props.deliveryMode === 'pudo' ? 'Consegna presso punto BRT' : 'Indirizzo da completare',
));

const resolvedPaymentSummaryServicesLabel = computed(() => (
	pickFirstMeaningfulSummaryText(props.paymentSummaryServicesLabel) || 'Nessun extra selezionato'
));

const resolvedOriginLocalityDisplay = computed(() => buildAddressLocalityLine(
	props.originAddress,
	'Località da completare',
));

const resolvedDestinationLocalityDisplay = computed(() => buildAddressLocalityLine(
	props.destinationAddress,
	props.deliveryMode === 'pudo' ? 'Punto BRT da selezionare' : 'Località da completare',
));

defineExpose({
	resolvedTrattaLabel,
	resolvedOriginContact,
	resolvedDestinationContact,
});
</script>

<template>
	<div class="payment-summary-card">
		<div class="flex flex-col gap-[10px] lg:flex-row lg:items-start lg:justify-between">
			<div class="min-w-0 flex items-start gap-[12px]">
				<span class="inline-flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-[14px] bg-[#F3FAFB] text-[#095866] border border-[#D7E4E7]">
					<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M3 7l9-4 9 4v10l-9 4-9-4z" />
						<path d="M3 7l9 4 9-4" />
						<path d="M12 11v10" />
					</svg>
				</span>
				<div class="min-w-0">
					<div class="flex flex-wrap items-center gap-[8px]">
						<p class="text-[11px] uppercase tracking-[0.16em] text-[#7C8594]" style="font-weight:800">Riepilogo ordine</p>
						<span class="inline-flex items-center rounded-full border border-[#D7E4E7] bg-[#F5F7FA] px-[10px] py-[5px] text-[11px] text-[#5C6473]" style="font-weight:800">
							{{ colloLabel }}
						</span>
					</div>
					<div class="mt-[6px] flex flex-wrap items-center gap-x-[10px] gap-y-[6px] text-[13px] text-[#1d2738]" style="font-weight:800">
						<span>{{ resolvedTrattaLabel }}</span>
						<span class="text-[#C0C5CC]">•</span>
						<span class="text-[#5C6473]" style="font-weight:700">Ritiro {{ confirmationPickupDate }}</span>
						<span class="text-[#C0C5CC]">•</span>
						<span class="text-[#5C6473]" style="font-weight:700">{{ paymentDeliveryLabel }}</span>
					</div>
				</div>
			</div>

			<div class="flex items-center gap-[10px] lg:justify-end">
				<p
					class="leading-none text-[#1d2738]"
					style="font-weight:800; font-size: clamp(28px, 4vw, 36px); letter-spacing: -0.02em;">
					{{ displayTotalText }}
				</p>
			</div>
		</div>

		<button
			type="button"
			class="payment-summary-card__toggle"
			:aria-expanded="paymentSummaryExpanded ? 'true' : 'false'"
			@click="emit('update:paymentSummaryExpanded', !paymentSummaryExpanded)">
			<span>{{ paymentSummaryToggleLabel }}</span>
			<span
				class="payment-summary-card__toggle-chevron"
				:class="{ 'payment-summary-card__toggle-chevron--open': paymentSummaryExpanded }"
				aria-hidden="true">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M6 9l6 6 6-6" />
				</svg>
			</span>
		</button>

		<Transition name="payment-panel">
			<div v-if="paymentSummaryExpanded" class="payment-summary-card__details">
				<article class="payment-summary-section">
					<header class="payment-summary-section__header">
						<div class="min-w-0">
							<p class="payment-summary-section__eyebrow">Colli</p>
							<p class="payment-summary-section__title">{{ summaryPackageLabel || 'Tipo collo da scegliere' }}</p>
							<p class="payment-summary-section__body">{{ summaryDimensionsLabel || 'Misure da completare' }}</p>
						</div>
						<button
							v-if="!paymentSuccess"
							type="button"
							class="payment-summary-section__edit"
							@click="emit('edit-packages')">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 20h9" />
								<path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
							</svg>
							<span>Modifica</span>
						</button>
					</header>
				</article>

				<article class="payment-summary-section">
					<header class="payment-summary-section__header">
						<div class="min-w-0">
							<p class="payment-summary-section__eyebrow">Indirizzi</p>
							<div class="payment-summary-section__route">
								<div class="payment-summary-section__route-item">
									<span class="payment-summary-section__route-label">Partenza</span>
									<span class="payment-summary-section__title">{{ resolvedOriginContact }}</span>
									<span class="payment-summary-section__body">{{ resolvedOriginStreetLine }}</span>
									<span class="payment-summary-section__body">{{ resolvedOriginLocalityDisplay }}</span>
								</div>
								<div class="payment-summary-section__route-item">
									<span class="payment-summary-section__route-label">{{ deliveryMode === 'pudo' ? 'Destinazione (Punto BRT)' : 'Destinazione' }}</span>
									<span class="payment-summary-section__title">{{ resolvedDestinationContact }}</span>
									<span class="payment-summary-section__body">{{ resolvedDestinationStreetLine }}</span>
									<span class="payment-summary-section__body">{{ resolvedDestinationLocalityDisplay }}</span>
								</div>
							</div>
						</div>
						<button
							v-if="!paymentSuccess"
							type="button"
							class="payment-summary-section__edit"
							@click="emit('edit-addresses')">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 20h9" />
								<path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
							</svg>
							<span>Modifica</span>
						</button>
					</header>
				</article>

				<article class="payment-summary-section">
					<header class="payment-summary-section__header">
						<div class="min-w-0">
							<p class="payment-summary-section__eyebrow">Servizi</p>
							<p class="payment-summary-section__title">{{ resolvedPaymentSummaryServicesLabel }}</p>
							<p class="payment-summary-section__body">Contenuto: {{ resolvedContentDescription || 'non specificato' }}</p>
						</div>
						<button
							v-if="!paymentSuccess"
							type="button"
							class="payment-summary-section__edit"
							@click="emit('edit-services')">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 20h9" />
								<path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
							</svg>
							<span>Modifica</span>
						</button>
					</header>
				</article>

				<div class="payment-summary-card__breakdown">
					<div v-if="couponApplied && subtotalFormatted" class="payment-summary-card__row">
						<span>Subtotale</span>
						<span>{{ subtotalFormatted }}</span>
					</div>
					<div v-if="couponApplied && discountFormatted" class="payment-summary-card__row payment-summary-card__row--discount">
						<span>Sconto{{ couponApplied.code ? ` (${couponApplied.code})` : '' }}</span>
						<span>-{{ discountFormatted }}</span>
					</div>
					<div class="payment-summary-card__row payment-summary-card__row--total">
						<span>Totale spedizione</span>
						<strong>{{ finalTotalFormatted || summaryTotalPrice || 'Da definire' }}</strong>
					</div>
				</div>
			</div>
		</Transition>
	</div>
</template>
