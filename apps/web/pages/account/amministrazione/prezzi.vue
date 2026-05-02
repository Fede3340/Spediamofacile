<script setup>
definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Prezzi admin',
	ogTitle: 'Prezzi admin',
	description: 'Configura tariffe, supplementi e regole di pricing dal pannello admin SpediamoFacile.',
	ogDescription: 'Pannello admin prezzi con tariffe, supplementi e regole commerciali di SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const {
	isLoading, saving, seeding,
	weightBands, volumeBands, bandsFromDb,
	extraRules, supplementRules,
	pricingVersion, europePricing,
	adminView, compactEuropeView,
	europeSearch, europeStatusFilter, europeBandFilter, europeSort,
	serviceSearch, serviceFilter,
	promoLoading, promoSaving, promoImageUploading, promo,
	editingCell, editValue, actionMessage, hasChanges,
	servicePricingEntries, automaticSupplementEntries, operationalFeeEntries, filteredServiceEntries,
	europeBandFilters, filteredEuropeBands, extraRuleExamples, pricingPreviewCases,
	centsToEuro, euroToCents, effectivePrice, discountInfo, formatApplicationLabel,
	startEdit, confirmEdit, cancelEdit, toggleShowDiscount,
	addBand, removeBand, moveBand, addSupplement, removeSupplement,
	supplementAmountToEuro, updateSupplementAmountFromEuro,
	keyedRuleAmountToEuro, updateKeyedRuleAmountFromEuro,
	keyedRuleMinFeeToEuro, updateKeyedRuleMinFeeFromEuro,
	updateArrayField, addTierRow, removeTierRow,
	updateEuropeRateAmountFromEuro, toggleEuropeRateQuote,
	fetchPriceBands, fetchPromoSettings, seedBands,
	savePriceBands, savePromo, uploadPromoImage,
} = useAdminPricing();

const nationalBandCount = computed(() => (weightBands.value?.length || 0) + (volumeBands.value?.length || 0));
const europeCountryCount = computed(() =>
	(Array.isArray(filteredEuropeBands.value) ? filteredEuropeBands.value : []).reduce(
		(total, band) => total + (Array.isArray(band?.rates) ? band.rates.length : 0),
		0,
	),
);
const serviceRuleCount = computed(
	() =>
		(Array.isArray(servicePricingEntries.value) ? servicePricingEntries.value.length : 0)
		+ (Array.isArray(automaticSupplementEntries.value) ? automaticSupplementEntries.value.length : 0)
		+ (Array.isArray(operationalFeeEntries.value) ? operationalFeeEntries.value.length : 0),
);
const pricingStatusLabel = computed(() => (hasChanges.value ? 'Modifiche da salvare' : 'Versione allineata'));
const activeViewHelper = computed(() => {
	if (adminView.value === 'europa') return 'Tariffe paese per paese per il solo flusso Europa monocollo.';
	if (adminView.value === 'servizi') return 'Servizi utente, supplementi automatici e fee operative nello stesso schema.';
	return 'Fasce nazionali, volume e CAP nella vista principale di controllo.';
});

const tabs = [
	{ id: 'nazionale', label: 'Nazionale' },
	{ id: 'europa', label: 'Europa monocollo' },
	{ id: 'servizi', label: 'Servizi e supplementi' },
];

const inputClass = 'h-10 px-3 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20';

onMounted(() => {
	fetchPriceBands();
	fetchPromoSettings();
	if (window.innerWidth < 1280) {
		compactEuropeView.value = true;
	}
});
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-6 tablet:py-7">
		<div class="my-container space-y-5">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Prezzi"
				description="Listini nazionali, Europa, servizi e promozione in una regia unica, piu pulita e coerente."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Prezzi' },
				]"
				back-to="/account/amministrazione"
				back-label="Torna all'amministrazione" />

			<div class="grid grid-cols-2 desktop:grid-cols-4 gap-3">
				<SfStatCard label="Fasce nazionali" :value="nationalBandCount" tone="primary" icon="mdi:weight-kilogram" trend-label="Peso, volume e regole oltre soglia." />
				<SfStatCard label="Tariffe Europa" :value="europeCountryCount" tone="primary" icon="mdi:earth" trend-label="Paesi configurati flusso monocollo BRT." />
				<SfStatCard label="Regole servizi" :value="serviceRuleCount" tone="primary" icon="mdi:cog-outline" trend-label="Servizi utente + supplementi + fee." />
				<SfStatCard :label="'Stato listino'" :value="pricingStatusLabel" :tone="hasChanges ? 'warning' : 'success'" icon="mdi:database-check" :trend-label="pricingVersion ? `Versione ${pricingVersion}` : 'Configurazione pronta.'" />
			</div>

			<SfCard padding="md">
				<div class="grid gap-3.5">
					<SfTabs v-model="adminView" :items="tabs" variant="pills" />

					<div class="flex flex-wrap items-center justify-between gap-2.5">
						<p class="text-sm text-brand-text-secondary flex-1">{{ activeViewHelper }}</p>
						<span :class="['inline-flex items-center px-3 py-1 rounded-pill border text-xs font-bold uppercase tracking-wider', hasChanges ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-brand-success-bg text-brand-success-fg border-brand-success/30']">
							{{ hasChanges ? 'Modifiche da salvare' : 'Versione sincronizzata' }}
						</span>
					</div>

					<div
						v-if="adminView === 'europa'"
						class="grid w-full grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-[minmax(0,1fr)_160px_160px_180px_auto] gap-2.5">
						<input v-model="europeSearch" type="text" placeholder="Cerca paese o codice..." aria-label="Cerca paese o codice Europa" :class="inputClass">
						<select v-model="europeStatusFilter" aria-label="Filtra per stato tariffa Europa" :class="inputClass">
							<option value="all">Tutti</option>
							<option value="active">Prezzo attivo</option>
							<option value="quote_required">Solo preventivo</option>
						</select>
						<select v-model="europeBandFilter" aria-label="Filtra per fascia Europa" :class="inputClass">
							<option v-for="option in europeBandFilters" :key="option.value" :value="option.value">{{ option.label }}</option>
						</select>
						<select v-model="europeSort" aria-label="Ordina tariffe Europa" :class="inputClass">
							<option value="country_asc">Ordina per paese</option>
							<option value="price_asc">Prezzo crescente</option>
							<option value="price_desc">Prezzo decrescente</option>
							<option value="status">Per stato</option>
						</select>
						<label class="inline-flex min-h-10 items-center gap-2 whitespace-nowrap text-sm text-brand-text-secondary desktop:justify-self-end">
							<input v-model="compactEuropeView" type="checkbox" class="rounded border-brand-border text-brand-primary focus:ring-brand-primary">
							Vista compatta
						</label>
					</div>

					<div
						v-else-if="adminView === 'servizi'"
						class="grid w-full grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-[minmax(0,1fr)_190px_auto] gap-2.5">
						<input v-model="serviceSearch" type="text" placeholder="Cerca regola o supplemento..." aria-label="Cerca regola o supplemento" :class="inputClass">
						<select v-model="serviceFilter" aria-label="Filtra per sezione servizi" :class="inputClass">
							<option value="all">Tutte le sezioni</option>
							<option value="service_pricing">Servizi utente</option>
							<option value="automatic_supplements">Supplementi automatici</option>
							<option value="operational_fees">Fee operative</option>
						</select>
						<div class="inline-flex min-h-10 items-center gap-2 px-3 py-2.5 rounded-control bg-brand-soft-bg text-sm text-brand-primary desktop:justify-self-end">
							{{ filteredServiceEntries.length }} regole visibili
						</div>
					</div>

					<div
						v-else
						class="inline-flex min-h-10 items-center gap-2 rounded-control bg-brand-bg-alt px-3 py-2.5 text-sm text-brand-text-secondary">
						Base nazionale: fasce peso, volume, oltre soglia e supplementi CAP.
					</div>
				</div>
			</SfCard>

			<div class="grid grid-cols-1 tablet:grid-cols-3 gap-3 text-sm text-brand-text-secondary">
				<div class="p-3.5 rounded-card bg-brand-bg-alt border border-brand-border">
					<strong class="text-brand-text">Base:</strong> MAX tra prezzo peso e prezzo volume, poi CAP e regole operative.
				</div>
				<div class="p-3.5 rounded-card bg-brand-bg-alt border border-brand-border">
					<strong class="text-brand-text">Volume:</strong> (L x P x H) / 5000, con fasce allineate al listino nazionale.
				</div>
				<div class="p-3.5 rounded-card bg-brand-bg-alt border border-brand-border">
					<strong class="text-brand-text">Sconti:</strong> il prezzo effettivo e i badge pubblici seguono solo le fasce visibili.
				</div>
			</div>

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<div v-if="isLoading" class="py-8 flex justify-center">
				<UIcon name="mdi:loading" class="w-10 h-10 text-brand-primary animate-spin" />
			</div>

			<template v-else>
				<div class="space-y-6">
					<AdminPrezziNazionale
						v-if="adminView === 'nazionale'"
						v-model:edit-value="editValue"
						:weight-bands="weightBands"
						:volume-bands="volumeBands"
						:extra-rules="extraRules"
						:supplement-rules="supplementRules"
						:bands-from-db="bandsFromDb"
						:seeding="seeding"
						:editing-cell="editingCell"
						:extra-rule-examples="extraRuleExamples"
						:pricing-preview-cases="pricingPreviewCases"
						:cents-to-euro="centsToEuro"
						:euro-to-cents="euroToCents"
						:effective-price="effectivePrice"
						:discount-info="discountInfo"
						:start-edit="startEdit"
						:confirm-edit="confirmEdit"
						:cancel-edit="cancelEdit"
						:toggle-show-discount="toggleShowDiscount"
						:add-band="addBand"
						:remove-band="removeBand"
						:move-band="moveBand"
						:seed-bands="seedBands"
						:add-supplement="addSupplement"
						:remove-supplement="removeSupplement"
						:supplement-amount-to-euro="supplementAmountToEuro"
						:update-supplement-amount-from-euro="updateSupplementAmountFromEuro"
						@update:extra-rules="extraRules = $event"
						@update:supplement-rules="supplementRules = $event" />

					<AdminPrezziEuropa
						v-if="adminView === 'europa'"
						:europe-pricing="europePricing"
						:filtered-europe-bands="filteredEuropeBands"
						:compact-europe-view="compactEuropeView"
						:cents-to-euro="centsToEuro"
						:update-europe-rate-amount-from-euro="updateEuropeRateAmountFromEuro"
						:toggle-europe-rate-quote="toggleEuropeRateQuote" />

					<AdminPrezziServizi
						v-if="adminView === 'servizi'"
						:service-pricing-entries="servicePricingEntries"
						:automatic-supplement-entries="automaticSupplementEntries"
						:operational-fee-entries="operationalFeeEntries"
						:filtered-service-entries="filteredServiceEntries"
						:euro-to-cents="euroToCents"
						:format-application-label="formatApplicationLabel"
						:keyed-rule-amount-to-euro="keyedRuleAmountToEuro"
						:update-keyed-rule-amount-from-euro="updateKeyedRuleAmountFromEuro"
						:keyed-rule-min-fee-to-euro="keyedRuleMinFeeToEuro"
						:update-keyed-rule-min-fee-from-euro="updateKeyedRuleMinFeeFromEuro"
						:update-array-field="updateArrayField"
						:add-tier-row="addTierRow"
						:remove-tier-row="removeTierRow" />

					<div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-card border border-brand-border bg-brand-card shadow-sf-lg">
						<div class="flex flex-wrap items-center gap-2 text-xs">
							<span
								v-if="pricingVersion"
								class="inline-flex items-center gap-1 px-2 py-0.5 rounded-pill bg-brand-soft-bg text-brand-primary border border-brand-soft-border">
								Versione {{ pricingVersion }}
							</span>
							<span
								v-if="hasChanges"
								class="inline-flex items-center gap-1 px-2.5 py-1 rounded-pill bg-amber-50 text-amber-700 font-medium border border-amber-200">
								<UIcon name="mdi:alert" class="w-3.5 h-3.5" />
								Modifiche non salvate
							</span>
						</div>
						<SfButton :loading="saving" :disabled="saving || !hasChanges" @click="savePriceBands">
							<template #leading><UIcon name="mdi:content-save" class="w-[18px] h-[18px]" /></template>
							{{ saving ? 'Salvataggio...' : 'Salva prezzi' }}
						</SfButton>
					</div>

					<AdminPrezziPromo
						:promo="promo"
						:promo-loading="promoLoading"
						:promo-saving="promoSaving"
						:promo-image-uploading="promoImageUploading"
						:save-promo="savePromo"
						:upload-promo-image="uploadPromoImage"
						@update:promo="promo = $event" />
				</div>
			</template>
		</div>
	</section>
</template>
