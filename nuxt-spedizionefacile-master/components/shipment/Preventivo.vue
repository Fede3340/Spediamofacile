<!--
	Preventivo.vue — Modulo principale per creare un preventivo di spedizione.
	Usato in: pages/index.vue (homepage), pages/preventivo.vue.
	Logica: composables/usePreventivo.js | Stili: assets/css/preventivo.css
	VINCOLO: la formula prezzo deve restare allineata con SessionController::firstStep.
-->
<script setup>
// CSS split route-specific: preventivo.css viene caricato solo
// nelle route che montano questo componente (homepage + /preventivo).
import '~/assets/css/preventivo.css';

const DEBUG_STATIC_PREVENTIVO = false;
const preventivoDebugStore = useShipmentFlowStore();
const preventivoApi = DEBUG_STATIC_PREVENTIVO
  ? {
    formRef: ref(null),
    messageError: ref(null),
    isCalculating: ref(false),
    isSyncingQuote: ref(false),
    isAdvancingToServices: ref(false),
    shipmentFlowStore: preventivoDebugStore,
    isHomepageLikeRoute: computed(() => true),
    isDestinationItaly: computed(() => true),
    isOriginItaly: computed(() => true),
    originLocationError: ref(''),
    destLocationError: ref(''),
    liveQuotePrice: computed(() => '0,00 €'),
    continueButtonLabel: computed(() => 'Continua'),
    preventivoSubtitle: computed(() => 'Debug preventivo'),
    packageCountLabel: computed(() => '1 collo'),
    originPlaceholder: computed(() => ''),
    destinationPlaceholder: computed(() => ''),
    isStandalonePreventivoRoute: computed(() => false),
    europeCountryOptions: computed(() => []),
    hasFormData: computed(() => false),
    isEuropeMonocollo: computed(() => false),
    europeRestrictionMessage: computed(() => ''),
    originQuery: ref(''),
    originSuggestions: ref([]),
    showOriginSuggestions: ref(false),
    destQuery: ref(''),
    destSuggestions: ref([]),
    showDestSuggestions: ref(false),
    locationKey: () => '',
    getProvinceLabel: () => '',
    selectOriginLocation: () => {},
    selectDestLocation: () => {},
    settleOriginQuery: async () => {},
    settleDestQuery: async () => {},
    onOriginQueryFocus: () => {},
    onOriginQueryInput: () => {},
    onDestQueryFocus: () => {},
    onDestQueryInput: () => {},
    hideOriginSuggestions: () => {},
    hideDestSuggestions: () => {},
    onOriginManualInput: () => {},
    onOriginManualBlur: () => {},
    onDestManualInput: () => {},
    onDestManualBlur: () => {},
    applyOriginCountrySelection: () => {},
    applyDestinationCountrySelection: () => {},
    packageTypeList: [],
    addPackageInline: () => {},
    deletePack: () => {},
    updatePackageType: () => {},
    calcQuantity: () => {},
    incrementQuantity: () => {},
    decrementQuantity: () => {},
    sv: { validatePeso: () => {}, errorClass: () => '' },
    onWeightInput: () => {},
    onWeightBlur: () => {},
    onDimInput: () => {},
    onDimBlur: () => {},
    promoSettings: ref(null),
    continueToNextStep: async () => {},
    resetForm: () => {},
  }
  : usePreventivo();

const {
  formRef, messageError, isCalculating, isSyncingQuote, isAdvancingToServices,
  shipmentFlowStore,
  isHomepageLikeRoute, isDestinationItaly, isOriginItaly,
  originLocationError, destLocationError, liveQuotePrice,
  continueButtonLabel, preventivoSubtitle, packageCountLabel,
  originPlaceholder, destinationPlaceholder, isStandalonePreventivoRoute,
  europeCountryOptions, hasFormData, isEuropeMonocollo, europeRestrictionMessage,
  originQuery, originSuggestions, showOriginSuggestions,
  destQuery, destSuggestions, showDestSuggestions,
  locationKey, getProvinceLabel,
  selectOriginLocation, selectDestLocation,
  settleOriginQuery, settleDestQuery,
  onOriginQueryFocus, onOriginQueryInput,
  onDestQueryFocus, onDestQueryInput,
  hideOriginSuggestions, hideDestSuggestions,
  onOriginManualInput, onOriginManualBlur,
  onDestManualInput, onDestManualBlur,
  applyOriginCountrySelection, applyDestinationCountrySelection,
  packageTypeList, addPackageInline, deletePack, updatePackageType,
  calcQuantity, incrementQuantity, decrementQuantity,
  sv, onWeightInput, onWeightBlur, onDimInput, onDimBlur,
  promoSettings,
  continueToNextStep, resetForm,
} = preventivoApi;

/*
  Validazione peso client-side: il backend richiede packages.*.weight required|numeric|min:0.1.
  Senza questo check, se il campo e' vuoto/zero la POST ritorna 422
  "packages.0.weight field is required". Intercettiamo qui: errore inline + focus sul campo.
*/
const weightError = ref({});

const hasInvalidWeight = (pack) => {
  const raw = pack?.weight;
  if (raw === null || raw === undefined || raw === '') return true;
  const num = Number(String(raw).replace(',', '.'));
  return !Number.isFinite(num) || num < 0.1;
};

const guardPackagesWeightAndSubmit = async () => {
  weightError.value = {};
  const packages = shipmentFlowStore?.packages || [];
  let firstInvalid = -1;
  for (let i = 0; i < packages.length; i++) {
    if (hasInvalidWeight(packages[i])) {
      weightError.value[i] = 'Inserisci un peso valido (minimo 0.1 kg).';
      if (firstInvalid === -1) firstInvalid = i;
    }
  }
  if (firstInvalid !== -1) {
    // Forza anche la validazione interna (sv) cosi l'error state resta coerente.
    sv?.validatePeso?.(`peso_${firstInvalid}`, packages[firstInvalid]?.weight);
    await nextTick();
    const el = document.getElementById(`weight_${firstInvalid}`);
    if (el) {
      el.focus();
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return;
  }
  await continueToNextStep();
};

/* Field configs for the 4 measurement inputs rendered via v-for */
const dimensionFields = [
  { key: 'weight', label: 'Peso', unit: 'kg', svKey: 'peso', svLabel: 'Peso' },
  { key: 'first_size', label: 'Lung.', unit: 'cm', svKey: 'first_size', svLabel: 'Lato 1' },
  { key: 'second_size', label: 'Larg.', unit: 'cm', svKey: 'second_size', svLabel: 'Lato 2' },
  { key: 'third_size', label: 'Alt.', unit: 'cm', svKey: 'third_size', svLabel: 'Lato 3' },
];

function onFieldInput(pack, packIndex, field) {
  if (field.key === 'weight') {
    // resetta l'errore peso quando l'utente digita
    if (weightError.value[packIndex]) {
      const next = { ...weightError.value };
      delete next[packIndex];
      weightError.value = next;
    }
    onWeightInput(pack, packIndex);
  }
  else onDimInput(pack, packIndex, field.key, field.svLabel);
}
function onFieldBlur(pack, packIndex, field) {
  if (field.key === 'weight') onWeightBlur(pack, packIndex);
  else onDimBlur(pack, packIndex, field.key, field.svLabel);
}

// Entrance animations

</script>

<template>
	<div v-if="false" style="padding:40px">
		<div style="max-width:960px;margin:0 auto;background:#fff;border:1px solid #d9e0e6;border-radius:24px;padding:32px">
			<h2 style="font-size:32px;font-weight:800;color:#1d2738">debug preventivo shell</h2>
			<p style="margin-top:12px;color:#5b6472">debug preventivo shell</p>
		</div>
	</div>
	<section :class="isHomepageLikeRoute ? 'mt-[48px] tablet:mt-[64px] desktop:mt-[80px] relative z-10' : 'pt-[24px]'" id="preventivo">
		<div class="my-container">
			<!--
				Coerenza Preventivo homepage vs ventaglio funnel.
				radius 16px -> 22px (stesso del payment step), body gradient #F8F9FB -> #EEF0F3,
				min-height mobile 230px (era 188px), backdrop-blur/shadow identici al ventaglio.
			-->
			<div
				class="preventivo-shell backdrop-blur-[12px] w-full max-w-[1280px] rounded-[22px] relative z-10 overflow-hidden p-[28px_14px_20px] tablet:p-[32px_32px_32px] desktop:p-[34px_40px_36px] mx-auto ring-[1px] ring-[rgba(216,220,227,0.6)]"
				style="background: var(--gradient-page-surface); min-height: 230px;"
				:class="isHomepageLikeRoute
					? 'shadow-[0_4px_20px_rgba(0,0,0,0.04),0_12px_40px_rgba(0,0,0,0.04)]'
					: 'mt-[20px] shadow-[0_4px_20px_rgba(0,0,0,0.04),0_12px_40px_rgba(0,0,0,0.04)]'">
				<div class="preventivo-shell__accent" aria-hidden="true"></div>
				<div class="preventivo-heading">
					<div class="preventivo-heading__copy">
						<div class="preventivo-heading__icon" aria-hidden="true">
							<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
						</div>
						<div class="preventivo-heading__text">
							<h2 class="preventivo-heading__title">Preventivo Rapido</h2>
							<p class="preventivo-heading__subtitle">{{ preventivoSubtitle }}</p>
						</div>
					</div>
					<button v-if="hasFormData" type="button" @click="resetForm" aria-label="Azzera il modulo" class="preventivo-heading__reset flex items-center gap-[4px] cursor-pointer group">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:rotate-[-180deg] transition-transform duration-300"><path d="M2.5 2v6h6"/><path d="M2.66 15.57a10 10 0 1 0 .57-8.38L2.5 8"/></svg>
						<span class="hidden tablet:inline">Azzera</span>
					</button>
				</div>
				<form ref="formRef" class="preventivo-form" novalidate autocomplete="off" @submit.prevent="guardPackagesWeightAndSubmit">
					<div class="preventivo-layout">
						<!-- ── Tratta (partenza / destinazione) ── -->
						<section class="preventivo-section" aria-labelledby="preventivo-tratta-title">
							<h3 id="preventivo-tratta-title" class="preventivo-section__title">Inserisci la tratta</h3>
							<div class="route-composer">
								<div class="route-composer__grid">
									<!-- Partenza -->
									<div class="route-card route-card--origin">
										<div class="route-card__header">
											<div class="route-card__heading">
												<div class="route-card__badge route-card__badge--origin" aria-hidden="true">
													<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-4.35-6-10a6 6 0 1 1 12 0c0 5.65-6 10-6 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
												</div>
												<p class="route-card__title">Partenza</p>
											</div>
											<div class="route-card__header-side route-card__header-side--country">
												<label for="origin_country_code" class="sr-only">Paese di partenza</label>
												<select id="origin_country_code" v-model="shipmentFlowStore.shipmentDetails.origin_country_code" class="route-card__country-chip" @change="applyOriginCountrySelection(true)">
													<option v-for="country in europeCountryOptions" :key="country.code" :value="country.code">{{ country.label }}</option>
												</select>
											</div>
										</div>
										<div class="route-card__field">
											<label for="origin_city" class="sr-only">Città o CAP di ritiro</label>
											<div class="route-card__input-wrap relative" :class="{ 'is-open': showOriginSuggestions && originSuggestions.length }">
												<input id="origin_city" v-model="originQuery" type="text" autocomplete="off" spellcheck="false" :placeholder="originPlaceholder" class="input-preventivo-rapido input-preventivo-rapido--location" @focus="onOriginQueryFocus()" @input="onOriginQueryInput()" @blur="settleOriginQuery()" />
												<input type="hidden" v-model="shipmentFlowStore.shipmentDetails.origin_postal_code" id="origin_postal_code" />
												<ul v-if="showOriginSuggestions && originSuggestions.length" role="listbox" class="location-suggestions-list">
													<li v-for="loc in originSuggestions" :key="locationKey(loc)" role="option" aria-selected="false" @mousedown.prevent="selectOriginLocation(loc)" class="location-suggestion">
														<span class="location-suggestion__city">{{ loc.place_name }}</span>
														<span class="location-suggestion__meta">{{ loc.postal_code }}<template v-if="getProvinceLabel(loc)"> · {{ getProvinceLabel(loc) }}</template></span>
													</li>
												</ul>
											</div>
											<div class="route-card__feedback">
												<p v-if="originLocationError" class="route-card__error" role="alert">{{ originLocationError }}</p>
											</div>
										</div>
									</div>
									<!-- Connettore freccia -->
									<div class="route-composer__connector" aria-hidden="true">
										<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
									</div>
									<!-- Destinazione -->
									<div class="route-card route-card--destination">
										<div class="route-card__header route-card__header--destination">
											<div class="route-card__heading">
												<div class="route-card__badge route-card__badge--destination" aria-hidden="true">
													<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-4.35-6-10a6 6 0 1 1 12 0c0 5.65-6 10-6 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
												</div>
												<p class="route-card__title">Destinazione</p>
											</div>
											<div class="route-card__header-side route-card__header-side--country">
												<label for="destination_country_code" class="sr-only">Paese di destinazione</label>
												<select id="destination_country_code" v-model="shipmentFlowStore.shipmentDetails.destination_country_code" class="route-card__country-chip" @change="applyDestinationCountrySelection(true)">
													<option v-for="country in europeCountryOptions" :key="country.code" :value="country.code">{{ country.label }}</option>
												</select>
											</div>
										</div>
										<div class="route-card__field route-card__field--destination">
											<label for="destination_city" class="sr-only">Città o CAP di consegna</label>
											<div class="route-card__input-wrap relative" :class="{ 'is-open': showDestSuggestions && destSuggestions.length }">
												<input id="destination_city" v-model="destQuery" type="text" autocomplete="off" spellcheck="false" :placeholder="destinationPlaceholder" class="input-preventivo-rapido input-preventivo-rapido--location" @focus="onDestQueryFocus()" @input="onDestQueryInput()" @blur="settleDestQuery()" />
												<input type="hidden" v-model="shipmentFlowStore.shipmentDetails.destination_postal_code" id="destination_postal_code" />
												<ul v-if="showDestSuggestions && destSuggestions.length" role="listbox" class="location-suggestions-list">
													<li v-for="loc in destSuggestions" :key="locationKey(loc)" role="option" aria-selected="false" @mousedown.prevent="selectDestLocation(loc)" class="location-suggestion">
														<span class="location-suggestion__city">{{ loc.place_name }}</span>
														<span class="location-suggestion__meta">{{ loc.postal_code }}<template v-if="getProvinceLabel(loc)"> · {{ getProvinceLabel(loc) }}</template></span>
													</li>
												</ul>
											</div>
											<div class="route-card__feedback">
												<p v-if="destLocationError" class="route-card__error" role="alert">{{ destLocationError }}</p>
											</div>
										</div>
									</div>
								</div>
								<p v-if="!isDestinationItaly" class="route-composer__note" aria-live="polite">
									<span class="route-composer__note-label">Europa monocollo</span>
									<span class="route-composer__note-text">Prezzo basato su paese, peso e volume.</span>
								</p>
							</div>
						</section>
						<!-- ── Misure e peso ── -->
						<section class="preventivo-section preventivo-section--packages" aria-labelledby="preventivo-colli-title">
							<h3 id="preventivo-colli-title" class="preventivo-section__title">Inserisci misure e peso</h3>
							<Transition name="dimensions-section" mode="out-in">
								<div v-if="shipmentFlowStore.packages.length > 0" class="dimensions-wrapper">
									<p v-if="isEuropeMonocollo" class="package-restriction-note">{{ europeRestrictionMessage }}</p>
									<ul class="package-entry-list">
										<li v-for="(pack, packIndex) in shipmentFlowStore.packages" :key="pack._qid || packIndex" class="package-entry">
											<div class="package-entry__header">
											<div
												class="package-type-switcher package-type-switcher--shared sf-shared-segment-strip sf-shared-segment-strip--compact"
												:aria-label="`Tipo collo ${packIndex + 1}`">
													<button
														v-for="packageType in packageTypeList"
														:key="packageType.text"
														type="button"
														@click="updatePackageType(pack, packageType.text)"
														:aria-pressed="pack.package_type === packageType.text"
														:class="[
															'package-type-switcher__button',
															'sf-shared-segment',
															'sf-shared-segment--compact',
															pack.package_type === packageType.text ? 'package-type-switcher__button--active sf-shared-segment--active' : ''
														]"
													>
														<span class="package-type-switcher__icon-wrap sf-shared-segment__icon" aria-hidden="true">
															<img :src="`/img/quote/first-step/${packageType.img}`" :alt="packageType.text" :width="packageType.width" :height="packageType.height" class="package-type-switcher__icon-image" :loading="pack.package_type === packageType.text ? 'eager' : 'lazy'" decoding="async" draggable="false" />
														</span>
														<span class="sf-shared-segment__title">{{ packageType.text }}</span>
													</button>
												</div>
												<button v-if="shipmentFlowStore.packages.length > 1" type="button" class="package-entry__delete" @click="deletePack(pack._qid || packIndex)" :aria-label="'Elimina pacco ' + (packIndex + 1)">
													<NuxtImg src="/img/quote/first-step/trash.png" alt="Elimina" width="18" height="22" class="package-entry__delete-icon" loading="lazy" decoding="async" />
												</button>
											</div>
											<div class="package-entry__grid">
												<!-- Quantità -->
												<div class="package-field-card package-field-card--quantity">
													<label :for="'quantity_' + packIndex" class="package-field-card__label">Q.tà</label>
													<div class="package-field-card__input-wrap package-field-card__input-wrap--stepper">
														<div class="quantity-stepper quantity-stepper--embedded">
															<button type="button" class="quantity-stepper__button" @click="decrementQuantity(pack)" :aria-label="`Riduci quantità collo ${packIndex + 1}`" :disabled="isEuropeMonocollo">
																<span class="quantity-stepper__symbol" aria-hidden="true">−</span>
															</button>
														<input
															:id="'quantity_' + packIndex"
															v-model="pack.quantity"
															:name="`quick-quote-quantity-${pack._qid || packIndex}`"
															type="text"
															inputmode="numeric"
															pattern="[0-9]*"
															autocomplete="new-password"
															autocapitalize="off"
															autocorrect="off"
															spellcheck="false"
															aria-autocomplete="none"
															data-lpignore="true"
															data-1p-ignore="true"
															data-form-type="other"
															class="quantity-stepper__input"
															:aria-describedby="`quantity_help_${packIndex}`"
															:aria-label="`Quantità collo ${packIndex + 1}`"
															:readonly="isEuropeMonocollo"
															@input="calcQuantity(pack)"
															@blur="calcQuantity(pack)" />
															<button type="button" class="quantity-stepper__button" @click="incrementQuantity(pack)" :aria-label="`Aumenta quantità collo ${packIndex + 1}`" :disabled="isEuropeMonocollo">
																<span class="quantity-stepper__symbol" aria-hidden="true">+</span>
															</button>
														</div>
													</div>
													<span :id="`quantity_help_${packIndex}`" class="sr-only">Numero di colli identici da spedire. Il prezzo viene moltiplicato per la quantità.</span>
													<div class="package-field-card__feedback">
														<p v-if="messageError?.[`packages.${packIndex}.quantity`]" class="package-field-card__error" role="alert">{{ messageError[`packages.${packIndex}.quantity`][0] }}</p>
													</div>
												</div>
												<!-- Peso + 3 dimensioni (v-for) -->
												<div v-for="field in dimensionFields" :key="field.key" class="package-field-card">
													<label :for="`${field.key}_${packIndex}`" class="package-field-card__label">{{ field.label }}</label>
													<div class="package-field-card__input-wrap">
														<input
															:type="field.key === 'weight' ? 'number' : 'text'"
															placeholder="0"
															v-model="pack[field.key]"
															:id="`${field.key}_${packIndex}`"
															:name="`quick-quote-${field.key}-${pack._qid || packIndex}`"
															autocomplete="new-password"
															autocapitalize="off"
															autocorrect="off"
															spellcheck="false"
															aria-autocomplete="none"
															data-lpignore="true"
															data-1p-ignore="true"
															data-form-type="other"
															inputmode="decimal"
															:required="field.key === 'weight' || undefined"
															:min="field.key === 'weight' ? '0.1' : undefined"
															:step="field.key === 'weight' ? '0.1' : undefined"
															:max="field.key === 'weight' ? '9999' : undefined"
															:aria-invalid="field.key === 'weight' && weightError[packIndex] ? 'true' : undefined"
															:aria-describedby="field.key === 'weight' && weightError[packIndex] ? `weight_err_${packIndex}` : undefined"
															:class="sv.errorClass(`${field.svKey}_${packIndex}`, 'package-metric-input')"
															@input="onFieldInput(pack, packIndex, field)"
															@blur="onFieldBlur(pack, packIndex, field)" />
														<span class="package-field-card__unit">{{ field.unit }}</span>
													</div>
													<div class="package-field-card__feedback">
														<p v-if="field.key === 'weight' && weightError[packIndex]" :id="`weight_err_${packIndex}`" class="package-field-card__error" role="alert">{{ weightError[packIndex] }}</p>
														<p v-else-if="sv.getError(`${field.svKey}_${packIndex}`)" class="package-field-card__error" role="alert">{{ sv.getError(`${field.svKey}_${packIndex}`) }}</p>
														<p v-else-if="messageError?.[`packages.${packIndex}.${field.key}`]" class="package-field-card__error" role="alert">{{ messageError[`packages.${packIndex}.${field.key}`][0] }}</p>
													</div>
												</div>
											</div>
										</li>
									</ul>
									<div v-if="!isEuropeMonocollo" class="add-package-button-wrapper">
										<button type="button" @click="addPackageInline()" class="btn btn-secondary btn-compact add-package-btn">
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.35" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
											Aggiungi collo
										</button>
									</div>
									<div v-if="messageError?.packages && shipmentFlowStore.packages.length > 0" class="packages-feedback-slot">
										<p class="preventivo-inline-error" role="alert">{{ messageError.packages[0] }}</p>
									</div>
								</div>
							</Transition>
						</section>
					</div>
					<!-- Promo banner -->
					<div v-if="promoSettings?.active && promoSettings?.label_text" class="flex justify-center mt-[20px] desktop:mt-[16px]">
						<span :style="{ backgroundColor: promoSettings.label_color || 'var(--color-brand-accent)' }" class="inline-flex items-center gap-[6px] px-[14px] py-[6px] rounded-[16px] text-white text-[0.875rem] font-bold tracking-wide shadow-sm">
							<img v-if="promoSettings.label_image" :src="promoSettings.label_image" alt="" loading="lazy" decoding="async" width="40" height="18" class="h-[18px] w-auto" />
							{{ promoSettings.label_text }}
						</span>
					</div>
					<!-- CTA continua -->
					<div class="continue-button-wrapper w-full text-white overflow-hidden" :class="['h-[56px] tablet:h-[60px]', promoSettings?.active && promoSettings?.label_text ? 'mt-[12px]' : 'mt-[18px] desktop:mt-[20px]', isStandalonePreventivoRoute ? 'continue-button-wrapper--sticky' : '']">
						<button v-if="!isCalculating" type="button" @click="guardPackagesWeightAndSubmit" :disabled="isCalculating || isAdvancingToServices" class="continue-cta-button w-full h-full cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
							<span class="continue-cta-button__label">{{ continueButtonLabel }}</span>
							<span class="continue-cta-button__tail">
								<span v-if="liveQuotePrice" class="continue-cta-button__price" aria-label="Prezzo aggiornato">{{ liveQuotePrice }}</span>
								<span class="continue-cta-button__arrow-shell">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" class="continue-cta-button__arrow" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
								</span>
							</span>
						</button>
						<p v-if="isCalculating || isAdvancingToServices" class="h-full flex justify-center items-center">
							<svg class="animate-spin h-[60px] w-[60px] text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
						</p>
					</div>
					<!-- Trust badges -->
					<div class="preventivo-trust-row">
						<span class="preventivo-trust-pill">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
							Pagamento sicuro
						</span>
						<span class="preventivo-trust-pill">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
							Corriere BRT
						</span>
						<span class="preventivo-trust-pill">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
							Ritiro 24h
						</span>
					</div>
				</form>
			</div>
		</div>
	</section>
</template>
<!-- Styles in ~/assets/css/preventivo.css (lazy-loaded via <script setup> import — Sprint 5.3) -->
