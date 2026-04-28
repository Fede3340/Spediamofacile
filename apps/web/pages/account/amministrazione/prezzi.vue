<script setup>
// CSS split route-specific: admin-prezzi.css usato solo qui.
import '~/assets/css/admin.css';

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
	isLoading,
	saving,
	seeding,
	weightBands,
	volumeBands,
	bandsFromDb,
	extraRules,
	supplementRules,
	pricingVersion,
	europePricing,
	adminView,
	compactEuropeView,
	europeSearch,
	europeStatusFilter,
	europeBandFilter,
	europeSort,
	serviceSearch,
	serviceFilter,
	promoLoading,
	promoSaving,
	promoImageUploading,
	promo,
	editingCell,
	editValue,
	actionMessage,
	hasChanges,
	servicePricingEntries,
	automaticSupplementEntries,
	operationalFeeEntries,
	filteredServiceEntries,
	europeBandFilters,
	filteredEuropeBands,
	extraRuleExamples,
	pricingPreviewCases,
	centsToEuro,
	euroToCents,
	effectivePrice,
	discountInfo,
	formatApplicationLabel,
	startEdit,
	confirmEdit,
	cancelEdit,
	toggleShowDiscount,
	addBand,
	removeBand,
	moveBand,
	addSupplement,
	removeSupplement,
	supplementAmountToEuro,
	updateSupplementAmountFromEuro,
	keyedRuleAmountToEuro,
	updateKeyedRuleAmountFromEuro,
	keyedRuleMinFeeToEuro,
	updateKeyedRuleMinFeeFromEuro,
	updateArrayField,
	addTierRow,
	removeTierRow,
	updateEuropeRateAmountFromEuro,
	toggleEuropeRateQuote,
	fetchPriceBands,
	fetchPromoSettings,
	seedBands,
	savePriceBands,
	savePromo,
	uploadPromoImage,
} = useAdminPricing();

const nationalBandCount = computed(() => (weightBands.value?.length || 0) + (volumeBands.value?.length || 0));
const europeCountryCount = computed(() =>
	(Array.isArray(filteredEuropeBands.value) ? filteredEuropeBands.value : []).reduce(
		(total, band) => total + (Array.isArray(band?.rates) ? band.rates.length : 0),
		0
	)
);
const serviceRuleCount = computed(
	() =>
		(Array.isArray(servicePricingEntries.value) ? servicePricingEntries.value.length : 0) +
		(Array.isArray(automaticSupplementEntries.value) ? automaticSupplementEntries.value.length : 0) +
		(Array.isArray(operationalFeeEntries.value) ? operationalFeeEntries.value.length : 0)
);
const pricingStatusLabel = computed(() => (hasChanges.value ? 'Modifiche da salvare' : 'Versione allineata'));
const activeViewHelper = computed(() => {
	if (adminView.value === 'europa') {
		return 'Tariffe paese per paese per il solo flusso Europa monocollo.';
	}
	if (adminView.value === 'servizi') {
		return 'Servizi utente, supplementi automatici e fee operative nello stesso schema.';
	}

	return 'Fasce nazionali, volume e CAP nella vista principale di controllo.';
});

onMounted(() => {
	fetchPriceBands();
	fetchPromoSettings();
	if (window.innerWidth < 1280) {
		compactEuropeView.value = true;
	}
});
</script>

<template>
	<section class="sf-account-shell admin-prezzi-section">
		<div class="my-container sf-stack-section">
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

			<div class="admin-prezzi-overview-grid">
				<article class="admin-prezzi-overview-card">
					<p class="admin-prezzi-overview-card__eyebrow">Fasce nazionali</p>
					<p class="admin-prezzi-overview-card__value">{{ nationalBandCount }}</p>
					<p class="admin-prezzi-overview-card__meta">Peso, volume e regole oltre soglia nello stesso listino.</p>
				</article>
				<article class="admin-prezzi-overview-card">
					<p class="admin-prezzi-overview-card__eyebrow">Tariffe Europa</p>
					<p class="admin-prezzi-overview-card__value">{{ europeCountryCount }}</p>
					<p class="admin-prezzi-overview-card__meta">Paesi configurati nel flusso monocollo BRT.</p>
				</article>
				<article class="admin-prezzi-overview-card">
					<p class="admin-prezzi-overview-card__eyebrow">Regole servizi</p>
					<p class="admin-prezzi-overview-card__value">{{ serviceRuleCount }}</p>
					<p class="admin-prezzi-overview-card__meta">Servizi utente, supplementi automatici e fee operative.</p>
				</article>
				<article class="admin-prezzi-overview-card">
					<p class="admin-prezzi-overview-card__eyebrow">Stato listino</p>
					<p class="admin-prezzi-overview-card__value">{{ pricingStatusLabel }}</p>
					<p class="admin-prezzi-overview-card__meta">
						{{ pricingVersion ? `Versione ${pricingVersion}` : 'Configurazione pronta per il salvataggio.' }}
					</p>
				</article>
			</div>

			<div class="admin-prezzi-tabs-card sf-section-block">
				<div class="grid gap-[14px]">
					<div class="grid grid-cols-1 tablet:grid-cols-3 gap-[10px] tablet:gap-[12px] desktop:max-w-[800px] desktop:w-full">
						<button
							v-for="view in [
								{ id: 'nazionale', label: 'Nazionale' },
								{ id: 'europa', label: 'Europa monocollo' },
								{ id: 'servizi', label: 'Servizi e supplementi' },
							]"
							:key="view.id"
							type="button"
							@click="adminView = view.id"
							:class="
								adminView === view.id
									? 'bg-[var(--color-brand-primary)] text-white border-transparent shadow-[0_2px_8px_rgba(9,88,102,0.25)]'
									: 'bg-transparent text-[#425466] border-[#D8E3E8] hover:border-[var(--color-brand-primary)] hover:text-[var(--color-brand-primary)]'
							"
							class="admin-prezzi-tab-btn"
						>
							{{ view.label }}
						</button>
					</div>

					<div class="admin-prezzi-context-row">
						<p class="admin-prezzi-context-copy">{{ activeViewHelper }}</p>
						<span :class="hasChanges ? 'admin-prezzi-status-pill admin-prezzi-status-pill--warning' : 'admin-prezzi-status-pill'">
							{{ hasChanges ? 'Modifiche da salvare' : 'Versione sincronizzata' }}
						</span>
					</div>

					<div class="admin-prezzi-filters-row">
						<div
							v-if="adminView === 'europa'"
							class="grid w-full grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-[minmax(0,1fr)_160px_160px_180px_auto] gap-[10px]"
						>
							<input v-model="europeSearch" type="text" placeholder="Cerca paese o codice..." aria-label="Cerca paese o codice Europa" class="admin-prezzi-input" />
							<select v-model="europeStatusFilter" aria-label="Filtra per stato tariffa Europa" class="admin-prezzi-input">
								<option value="all">Tutti</option>
								<option value="active">Prezzo attivo</option>
								<option value="quote_required">Solo preventivo</option>
							</select>
							<select v-model="europeBandFilter" aria-label="Filtra per fascia Europa" class="admin-prezzi-input">
								<option v-for="option in europeBandFilters" :key="option.value" :value="option.value">{{ option.label }}</option>
							</select>
							<select v-model="europeSort" aria-label="Ordina tariffe Europa" class="admin-prezzi-input">
								<option value="country_asc">Ordina per paese</option>
								<option value="price_asc">Prezzo crescente</option>
								<option value="price_desc">Prezzo decrescente</option>
								<option value="status">Per stato</option>
							</select>
							<label class="inline-flex min-h-[42px] items-center gap-[8px] whitespace-nowrap text-[0.8125rem] text-[#4F5D75] desktop:justify-self-end">
								<input v-model="compactEuropeView" type="checkbox" class="rounded border-[#E9EBEC] text-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)]" />
								Vista compatta
							</label>
						</div>

						<div
							v-else-if="adminView === 'servizi'"
							class="grid w-full grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-[minmax(0,1fr)_190px_auto] gap-[10px]"
						>
							<input v-model="serviceSearch" type="text" placeholder="Cerca regola o supplemento..." aria-label="Cerca regola o supplemento" class="admin-prezzi-input" />
							<select v-model="serviceFilter" aria-label="Filtra per sezione servizi" class="admin-prezzi-input">
								<option value="all">Tutte le sezioni</option>
								<option value="service_pricing">Servizi utente</option>
								<option value="automatic_supplements">Supplementi automatici</option>
								<option value="operational_fees">Fee operative</option>
							</select>
							<div class="inline-flex min-h-[42px] items-center gap-[8px] px-[12px] py-[10px] rounded-[12px] bg-[#F4FAFC] text-[0.8125rem] text-[var(--color-brand-primary)] desktop:justify-self-end">
								{{ filteredServiceEntries.length }} regole visibili
							</div>
						</div>

						<div
							v-else
							class="inline-flex min-h-[42px] items-center gap-[8px] rounded-[12px] bg-[#F8FBFC] px-[12px] py-[10px] text-[0.8125rem] text-[#5B6B7D]"
						>
							Base nazionale: fasce peso, volume, oltre soglia e supplementi CAP.
						</div>
					</div>
				</div>
			</div>

			<div class="admin-prezzi-guidelines sf-section-block">
				<div class="admin-prezzi-guideline">
					<strong>Base:</strong>
					MAX tra prezzo peso e prezzo volume, poi CAP e regole operative.
				</div>
				<div class="admin-prezzi-guideline">
					<strong>Volume:</strong>
					(L x P x H) / 5000, con fasce allineate al listino nazionale.
				</div>
				<div class="admin-prezzi-guideline">
					<strong>Sconti:</strong>
					il prezzo effettivo e i badge pubblici seguono solo le fasce visibili.
				</div>
			</div>

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<div v-if="isLoading" class="py-[32px] flex justify-center">
				<div class="w-[40px] h-[40px] border-3 border-[#E9EBEC] border-t-[var(--color-brand-primary)] rounded-full animate-spin"></div>
			</div>

			<template v-else>
				<div class="space-y-[24px]">
					<AdminPrezziNazionale
						v-if="adminView === 'nazionale'"
						:weight-bands="weightBands"
						:volume-bands="volumeBands"
						:extra-rules="extraRules"
						:supplement-rules="supplementRules"
						:bands-from-db="bandsFromDb"
						:seeding="seeding"
						:editing-cell="editingCell"
						v-model:edit-value="editValue"
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
					/>

					<AdminPrezziEuropa
						v-if="adminView === 'europa'"
						:europe-pricing="europePricing"
						:filtered-europe-bands="filteredEuropeBands"
						:compact-europe-view="compactEuropeView"
						:cents-to-euro="centsToEuro"
						:update-europe-rate-amount-from-euro="updateEuropeRateAmountFromEuro"
						:toggle-europe-rate-quote="toggleEuropeRateQuote"
					/>

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
						:remove-tier-row="removeTierRow"
					/>

					<div class="admin-prezzi-save-bar">
						<div class="flex flex-wrap items-center gap-[8px] text-[0.75rem]">
							<span
								v-if="pricingVersion"
								class="inline-flex items-center gap-[4px] px-[8px] py-[3px] rounded-[999px] bg-[#F4FAFC] text-[var(--color-brand-primary)] border border-[#D8E9F0]"
							>
								Versione {{ pricingVersion }}
							</span>
							<span
								v-if="hasChanges"
								class="inline-flex items-center gap-[4px] px-[10px] py-[4px] rounded-full bg-[#FFF7F2] text-[#A34B18] font-medium border border-[#F2D6C6]"
							>
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[14px] h-[14px]" fill="currentColor">
									<path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" />
								</svg>
								Modifiche non salvate
							</span>
						</div>
						<button type="button" @click="savePriceBands" :disabled="saving || !hasChanges" class="admin-prezzi-save-btn">
							<svg v-if="saving" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] animate-spin" fill="currentColor">
								<path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z" />
							</svg>
							<svg v-else aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px]" fill="currentColor">
								<path d="M15,9H5V5H15M12,19A3,3 0 0,1 9,16A3,3 0 0,1 12,13A3,3 0 0,1 15,16A3,3 0 0,1 12,19M17,3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3Z" />
							</svg>
							{{ saving ? 'Salvataggio...' : 'Salva prezzi' }}
						</button>
					</div>

					<AdminPrezziPromo
						:promo="promo"
						:promo-loading="promoLoading"
						:promo-saving="promoSaving"
						:promo-image-uploading="promoImageUploading"
						:save-promo="savePromo"
						:upload-promo-image="uploadPromoImage"
					/>
				</div>
			</template>
		</div>
	</section>
</template>
