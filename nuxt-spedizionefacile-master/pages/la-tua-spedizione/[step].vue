<script setup>
const userStore = useUserStore();
const route = useRoute();
const { openAuthModal } = useAuthModal();
const isAuthenticated = useAuthUiState().isAuthenticatedForUi;
definePageMeta({ middleware: ['shipment-validation'] });

useSeoMeta({
	title: 'Configura la tua Spedizione — SpedizioneFacile',
	description: 'Scegli servizi, date di ritiro e indirizzi per configurare la tua spedizione in pochi passaggi.',
	ogTitle: 'Configura la tua Spedizione — SpedizioneFacile',
	ogDescription: 'Scegli servizi, date di ritiro e indirizzi per configurare la tua spedizione in pochi passaggi.',
});
const { session, status, refresh } = useSession({ server: true });
const sanctumClient = useSanctumClient();
const dateError = ref(null);
const submitError = ref(null);
const contentError = ref(null);
const formRef = ref(null);
const stepsRef = ref(null);
const pickupDateSectionRef = ref(null);
const servicesStageRef = ref(null);
const addressStageRef = ref(null);
const deliveryMode = computed({
	get: () => userStore.deliveryMode,
	set: (v) => {
		userStore.deliveryMode = v;
	},
});
const SERVICE_ICON_FILTER_IDLE =
	'brightness(0) saturate(100%) invert(23%) sepia(23%) saturate(1100%) hue-rotate(151deg) brightness(92%) contrast(88%)';
const SERVICE_ICON_FILTER_ACTIVE =
	'brightness(0) invert(1)';

const {
	chooseDate,
	chooseService,
	daysInMonth,
	ensureServiceSelected,
	expandedServiceKey,
	featuredService,
	regularServices,
	removeService,
	resetServicesState,
	serviceData,
	services,
	servicesList,
	smsEmailNotification,
	notificationPriceLabel,
	syncSelectedServicesVisual,
	toggleServiceDetails,
	toggleServiceSelection,
} = useShipmentStepServices({ userStore, dateError });

const { editCartId, editablePackages, loadCartItemForEdit, loadingEditData } = useShipmentStepCartEdit({
	sanctumClient,
	session,
	syncSelectedServicesVisual,
	userStore,
});

const {
	serviceCardErrors,
	clearServiceCardErrors,
	normalizeCurrencyInput,
	contrassegnoIncassoOptions,
	contrassegnoRimborsoOptions,
	requiresContrassegnoDettaglio,
	insurancePackages,
	validateInlineServiceDetails,
	isServiceExpanded,
	canConfigureService,
	getServiceConfigureLabel,
	handleServicePrimaryAction,
	removeConfiguredService,
	toggleRegularService,
	toggleFeaturedService,
	activateConfiguredService,
} = useShipmentStepServiceCards({
	editablePackages,
	ensureServiceSelected,
	expandedServiceKey,
	featuredService,
	chooseService,
	removeService,
	resetServicesState,
	serviceData,
	servicesList,
	smsEmailNotification,
	submitError,
	toggleServiceDetails,
	toggleServiceSelection,
	userStore,
});

const {
	applySavedAddress,
	destSelectorRef,
	destinationAddress,
	loadingSavedAddresses,
	originAddress,
	originSelectorRef,
	saveAddressToBook,
	savedAddresses,
	showDestAddressSelector,
	showOriginAddressSelector,
	shouldAutoShowAddressFields,
	toggleAddressSelector,
	canSaveOriginAddress,
	canSaveDestAddress,
	savingOriginAddress,
	savingDestAddress,
} = useShipmentStepAddresses({ userStore, session, route, isAuthenticated, sanctumClient, deliveryMode, submitError });

const { persistShipmentFlowState } = useShipmentStepSessionPersistence({
	sanctumClient,
	refresh,
	session,
	submitError,
	userStore,
	services,
	smsEmailNotification,
	originAddress,
	destinationAddress,
});

const {
	applyFieldAssist,
	contentFieldHint,
	destCapSuggestions,
	destCitySuggestions,
	destProvinceSuggestions,
	fieldClass,
	fieldErrorText,
	focusContentDescriptionField,
	focusFirstFormError,
	formatCapSuggestionLabel,
	formatCitySuggestionLabel,
	getFieldAssist,
	getFieldError,
	normalizeLocationText,
	onCapFocus,
	onCapInput,
	onCityFocus,
	onCityInput,
	onNameInput,
	onProvinciaInput,
	onProvinceFocus,
	onTelefonoInput,
	originCapSuggestions,
	originCitySuggestions,
	originProvinceSuggestions,
	selectCap,
	selectCity,
	selectProvincia,
	smartBlur,
	softenErrorMessage,
	sv,
	validateForm,
} = useShipmentStepValidation({
	contentError,
	dateError,
	deliveryMode,
	destinationAddress,
	originAddress,
	sanctumClient,
	services,
	userStore,
});

const focusPickupDateSection = () => {
	nextTick(() => {
		const sectionRoot =
			pickupDateSectionRef.value?.$el instanceof HTMLElement ? pickupDateSectionRef.value.$el : pickupDateSectionRef.value;
		const firstDateButton = sectionRoot?.querySelector?.('[data-pickup-day]') || document.querySelector('[data-pickup-day], [id^="date-"]');

		sectionRoot?.scrollIntoView?.({ block: 'center', behavior: 'smooth' });
		firstDateButton?.focus?.({ preventScroll: true });
	});
};

const {
	activeAccordionStep,
	addressReadinessItems,
	goBackToServices,
	onPudoDeselected,
	onPudoSelected,
	openAddressFields,
	showAddressFields,
} = useShipmentStepFlow({
	contentError,
	dateError,
	deliveryMode,
	destinationAddress,
	focusContentDescriptionField,
	focusPickupDateSection,
	normalizeLocationText,
	persistServicesStep: () => persistShipmentFlowState({ includeAddresses: false }),
	session,
	services,
	shouldAutoShowAddressFields,
	sv,
	userStore,
});

const { currentStep, initOnMounted, showInitialStepLoading } = useShipmentStepPageState({
	destinationAddress,
	editCartId,
	isAuthenticated,
	loadCartItemForEdit,
	loadingEditData,
	originAddress,
	refresh,
	resetServicesState,
	services,
	session,
	showAddressFields,
	smsEmailNotification,
	status,
	userStore,
});

const {
	canExpandSummaryDimensions,
	canExpandSummaryServices,
	currentShipmentStep,
	goToSummaryMiniStep,
	routeConsistencyState,
	routeWarningMessage,
	showSummaryMiniSteps,
	summaryDetailPanel,
	summaryDimensionsItems,
	summaryDimensionsLabel,
	summaryDestinationCity,
	summaryExpanded,
	summaryMiniSteps,
	summaryOriginCity,
	summaryPackageLabel,
	summaryPackageTypeInfo,
	summaryRouteLabel,
	summaryServicesItems,
	summaryServicesLabel,
	summaryTotalPrice,
	toggleSummaryDetailPanel,
} = useShipmentStepSummary({
	destinationAddress,
	editablePackages,
	normalizeLocationText,
	originAddress,
	session,
	showAddressFields,
	status,
	stepsRef,
	userStore,
});

useCart();

const uiFeedback = useUiFeedback();
const { continueToCart: persistAndContinueToCart, isSubmitting } = useShipmentStepSubmit({
	destinationAddress,
	editablePackages,
	editCartId,
	focusFirstFormError,
	focusPickupDateSection,
	formRef,
	normalizeLocationText,
	originAddress,
	persistSecondStep: (p) => persistShipmentFlowState({ includeAddresses: true, payload: p }),
	routeConsistencyState,
	smsEmailNotification,
	services,
	submitError,
	uiFeedback,
	userStore,
	validateForm,
});

const focusFirstInvalidServiceField = () => {
	nextTick(() => {
		const expandedCard = document.querySelector('.service-option--expanded');
		if (!expandedCard) return;

		const focusTarget = expandedCard.querySelector('.service-inline-field__input, .service-inline-choice, .service-inline-panel__submit');

		focusTarget?.focus?.({ preventScroll: true });
	});
};

const continueToCart = async () => {
	if (!validateInlineServiceDetails()) {
		focusFirstInvalidServiceField();
		return;
	}

	await persistAndContinueToCart();
};

const resolvedContentDescription = computed(() =>
	String(userStore.contentDescription || session.value?.data?.content_description || '').trim(),
);

const selectedServiceSummary = computed(() => {
	const selectedLabels = [];

	if (featuredService.value?.isSelected && featuredService.value?.name) {
		selectedLabels.push(String(featuredService.value.name).trim());
	}

	for (const service of regularServices.value || []) {
		if (!service?.isSelected || !service?.name) continue;
		selectedLabels.push(String(service.name).trim());
	}

	if (smsEmailNotification.value) {
		selectedLabels.push('Notifiche');
	}

	const normalized = [...new Set(selectedLabels.filter(Boolean))];
	if (!normalized.length) return '';

	const visible = normalized.slice(0, 2);
	const remaining = normalized.length - visible.length;
	const summary = visible.join(', ');

	return remaining > 0 ? `${summary} +${remaining}` : summary;
});

const servicesAccordionSummary = computed(() => {
	const parts = [];
	const selectedDate = String(services.value?.date || session.value?.data?.pickup_date || session.value?.data?.services?.date || '').trim();
	if (selectedDate) {
		parts.push(selectedDate);
	}

	const serviceLabel = selectedServiceSummary.value;
	if (serviceLabel) {
		parts.push(serviceLabel);
	}

	const contentLabel = resolvedContentDescription.value;
	if (contentLabel) {
		parts.push(contentLabel.length > 24 ? `${contentLabel.slice(0, 21)}...` : contentLabel);
	}

	return parts.join(' · ') || 'Pronto';
});

const showServicesReadinessNote = computed(() => {
	const hasServiceCardErrors = Object.values(serviceCardErrors || {}).some(Boolean);
	return !(contentError.value || dateError.value || hasServiceCardErrors);
});

const isServicesAccordionOpen = computed(() => activeAccordionStep.value === 'services');
const isAddressAccordionOpen = computed(() => activeAccordionStep.value === 'addresses');

// --- PROVIDE: funzioni form/validazione iniettate nei componenti figli ---
// Evita prop drilling di 19+ funzioni attraverso StepAddressSection → AddressFormFields
provide('shipmentFormHandlers', {
	fieldClass, getFieldError, fieldErrorText, getFieldAssist, applyFieldAssist, smartBlur,
	onNameInput, onCityInput, onCityFocus, onProvinciaInput, onProvinceFocus,
	onCapInput, onCapFocus, onTelefonoInput,
	selectCity, selectProvincia, selectCap,
	formatCitySuggestionLabel, formatCapSuggestionLabel, sv,
});
provide('shipmentSuggestions', {
	originCitySuggestions, originProvinceSuggestions, originCapSuggestions,
	destCitySuggestions, destProvinceSuggestions, destCapSuggestions,
});

const servicesPendingSummary = computed(() => {
	const pendingItems = addressReadinessItems.value.filter((item) => !item.done).map((item) => item.label.toLowerCase());

	if (!pendingItems.length) {
		return 'Apri il prossimo step per completare partenza e destinazione.';
	}

	if (pendingItems.length === 1) {
		return `Completa ${pendingItems[0]} per passare agli indirizzi.`;
	}

	return `Completa ${pendingItems[0]} e ${pendingItems[1]} per passare agli indirizzi.`;
});

const addressAccordionSummary = computed(() => {
	const parts = [];
	const hasRoutePreview =
		summaryOriginCity.value && summaryOriginCity.value !== '—' && summaryDestinationCity.value && summaryDestinationCity.value !== '—';

	if (hasRoutePreview) {
		parts.push(summaryRouteLabel.value);
	} else {
		parts.push('Partenza e destinazione');
	}

	parts.push(deliveryMode.value === 'pudo' ? 'Punto BRT' : 'Consegna a domicilio');
	return parts.join(' · ');
});

const resolveStageElement = (stageRef) => {
	const rawRef = stageRef?.value;
	if (!rawRef) return null;
	return rawRef?.$el instanceof HTMLElement ? rawRef.$el : rawRef;
};

const scrollAccordionStageIntoView = (stageRef, focusSelector) => {
	nextTick(() => {
		const stageElement = resolveStageElement(stageRef);
		if (!stageElement) return;

		window.setTimeout(() => {
			stageElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
			if (focusSelector) {
				const focusTarget = stageElement.querySelector(focusSelector);
				focusTarget?.focus?.({ preventScroll: true });
			}
		}, 120);
	});
};

const openShipmentAuthModal = (tab = 'login') => {
	openAuthModal({ redirect: route.fullPath, tab });
};

const openAddressAccordion = async () => {
	submitError.value = null;
	dateError.value = null;
	contentError.value = null;
	clearServiceCardErrors();

	if (!validateInlineServiceDetails()) {
		focusFirstInvalidServiceField();
		return;
	}

	expandedServiceKey.value = '';
	const opened = await openAddressFields();
	if (opened === false) return;

	scrollAccordionStageIntoView(addressStageRef, '#name, #dest_name, input:not([readonly])');
};

const collapseAddressAccordion = async () => {
	const collapsed = await goBackToServices();
	if (collapsed === false) return;

	scrollAccordionStageIntoView(servicesStageRef, '[data-accordion-trigger="services"]');
};

const clearAccordionPanelTransitionStyles = (el) => {
	el.style.height = '';
	el.style.opacity = '';
	el.style.transform = '';
	el.style.overflow = '';
	el.style.transition = '';
};

const bindAccordionPanelTransitionEnd = (el, done) => {
	const onTransitionEnd = (event) => {
		if (event.target !== el || event.propertyName !== 'height') return;
		el.removeEventListener('transitionend', onTransitionEnd);
		done();
	};

	el.addEventListener('transitionend', onTransitionEnd);
};

const onAccordionPanelBeforeEnter = (el) => {
	el.style.height = '0px';
	el.style.opacity = '0';
	el.style.overflow = 'hidden';
};

const onAccordionPanelEnter = (el, done) => {
	el.style.transition = 'height 350ms cubic-bezier(0.22,1,0.36,1), opacity 250ms ease';
	void el.offsetHeight;
	bindAccordionPanelTransitionEnd(el, done);

	requestAnimationFrame(() => {
		el.style.height = `${el.scrollHeight}px`;
		el.style.opacity = '1';
	});
};

const onAccordionPanelAfterEnter = (el) => {
	clearAccordionPanelTransitionStyles(el);
};

const onAccordionPanelBeforeLeave = (el) => {
	el.style.height = `${el.scrollHeight}px`;
	el.style.opacity = '1';
	el.style.overflow = 'hidden';
};

const onAccordionPanelLeave = (el, done) => {
	el.style.height = `${el.scrollHeight}px`;
	el.style.transition = 'height 300ms cubic-bezier(0.22,1,0.36,1), opacity 200ms ease';
	void el.offsetHeight;
	bindAccordionPanelTransitionEnd(el, done);

	requestAnimationFrame(() => {
		el.style.height = '0px';
		el.style.opacity = '0';
	});
};

const onAccordionPanelAfterLeave = (el) => {
	clearAccordionPanelTransitionStyles(el);
};

/* ══════════════════════════════════════════════════════════
   EXTENDED 5-STEP ACCORDION — Steps 1, 4, 5
   Steps 2/3 keep their existing composable-driven state.
   ══════════════════════════════════════════════════════════ */
const extraStepOpen = ref(0);
const isStep1Open = computed(() => extraStepOpen.value === 1);
const isStep4Open = computed(() => extraStepOpen.value === 4);
const isStep5Open = computed(() => extraStepOpen.value === 5);

watch([isServicesAccordionOpen, isAddressAccordionOpen], () => {
	extraStepOpen.value = 0;
});

const toggleExtraStep = (n) => {
	extraStepOpen.value = extraStepOpen.value === n ? 0 : n;
};

/* ── Colli / route helpers ── */
const packageItems = computed(() => {
	if (editablePackages.value && editablePackages.value.length > 0) return editablePackages.value;
	const sp = session.value?.data?.packages;
	if (Array.isArray(sp) && sp.length > 0) return sp;
	return [];
});
const colloLabel = computed(() => {
	const c = packageItems.value.length || 1;
	return c + ' coll' + (c === 1 ? 'o' : 'i');
});
const trattaLabel = computed(() => (summaryOriginCity.value || '—') + ' → ' + (summaryDestinationCity.value || '—'));

/* ── Step 5 — Pagamento reactive state ── */
const payMethod = ref('carta');
const docType = ref('ricevuta');
const fatturaType = ref('azienda');
const promoOpen = ref(false);
const promoCode = ref('');
const promoApplied = ref(false);
const termsAccepted = ref(false);
const payProcessing = ref(false);
const cardName = ref('');
const cardNumber = ref('');
const cardExpiry = ref('');
const cardCvv = ref('');
const showCvv = ref(false);
const saveCard = ref(true);
const ragioneSociale = ref('');
const partitaIva = ref('');
const codiceFiscaleAz = ref('');
const codiceSDI = ref('');
const pecAzienda = ref('');
const indirizzoAz = ref('');
const capAz = ref('');
const cittaAz = ref('');
const provinciaAz = ref('');
const nomePrivato = ref('');
const cognomePrivato = ref('');
const codiceFiscalePriv = ref('');
const indirizzoPriv = ref('');
const capPriv = ref('');
const cittaPriv = ref('');
const provinciaPriv = ref('');
const fmtCard = (v) => v.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
const fmtExp = (v) => { const n = v.replace(/\D/g, '').slice(0, 4); return n.length > 2 ? n.slice(0, 2) + '/' + n.slice(2) : n; };

/* ── Input class constant (gold standard) ── */
const INPUT_CLS = 'h-[48px] sm:h-[50px] px-[14px] text-[14px] rounded-[12px] ring-[1.5px] ring-[#DFE2E7] bg-white focus:ring-[3px] focus:ring-[#095866]/60 focus:outline-none transition-all duration-200 text-[#1d2738] w-full';

/* ── Services summary for Step 4 price breakdown ── */
const selectedServicesForSummary = computed(() => {
	const items = [];
	if (featuredService.value?.isSelected) items.push({ label: featuredService.value.name || 'Senza Etichetta', price: featuredService.value.price || '' });
	for (const s of regularServices.value || []) {
		if (s?.isSelected) items.push({ label: s.name || '', price: s.price || '' });
	}
	if (smsEmailNotification.value) items.push({ label: 'Notifiche SMS', price: notificationPriceLabel.value || '' });
	return items;
});

onMounted(initOnMounted);
</script>

<template>
	<section class="min-h-screen pb-[96px] sm:pb-[120px] pt-[28px] sm:pt-[40px]" style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
		<div class="w-full max-w-[1280px] mx-auto px-[14px] sm:px-[40px]">
			<div v-if="showInitialStepLoading" class="min-h-[560px] bg-[#E4E4E4] rounded-[18px] animate-pulse" />
			<form v-else ref="formRef" @submit.prevent="continueToCart">
				<div ref="stepsRef" class="mb-[6px] tablet:mb-[8px]">
					<Steps :current-step="currentShipmentStep - 1" />
				</div>
				<ShipmentStepSummaryCard
					v-if="currentStep === 2"
					:expanded="summaryExpanded"
					:compact-mobile="true"
					:detail-panel="summaryDetailPanel"
					:show-mini-steps="showSummaryMiniSteps"
					:summary-mini-steps="summaryMiniSteps"
					:summary-package-label="summaryPackageLabel"
					:summary-package-type-info="summaryPackageTypeInfo"
					:summary-dimensions-label="summaryDimensionsLabel"
					:summary-route-label="summaryRouteLabel"
					:summary-total-price="summaryTotalPrice"
					:route-warning-message="routeWarningMessage"
					:summary-origin-city="summaryOriginCity"
					:summary-destination-city="summaryDestinationCity"
					:can-expand-summary-dimensions="canExpandSummaryDimensions"
					:can-expand-summary-services="canExpandSummaryServices"
					:summary-services-label="summaryServicesLabel"
					:summary-dimensions-items="summaryDimensionsItems"
					:summary-services-items="summaryServicesItems"
					@go-mini-step="goToSummaryMiniStep"
					@toggle-detail-panel="toggleSummaryDetailPanel"
					@update:expanded="summaryExpanded = $event" />

				<div class="flex flex-col gap-[14px] mt-[16px]">

					<!-- ═══════════════════════════════════════════
					     STEP 1 — Colli e tratta (summary)
					     ═══════════════════════════════════════════ -->
					<section
						id="step-1"
						class="rounded-[22px] overflow-hidden"
						:style="{
							boxShadow: isStep1Open
								? '0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)'
								: '0 0 0 1px rgba(0,0,0,0.04), 0 1px 6px rgba(0,0,0,0.03)',
							transition: 'box-shadow 0.45s cubic-bezier(0.22,1,0.36,1)',
						}">
						<!-- Top accent bar -->
						<div
							class="h-[4px] transition-all duration-[450ms]"
							:style="{ background: isStep1Open ? 'linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)' : '#E6E9EE' }" />
						<!-- Header -->
						<button
							type="button"
							class="w-full px-[20px] sm:px-[28px] py-[18px] sm:py-[20px] flex items-center gap-[14px] text-left cursor-pointer transition-all duration-[350ms] hover:bg-[#EEF0F3]"
							style="background:#F8F9FB"
							@click="toggleExtraStep(1)">
							<div
								class="w-[36px] h-[36px] rounded-full flex items-center justify-center shrink-0 text-[14px] text-white transition-colors duration-[450ms]"
								:style="{ fontWeight: 800, background: isStep1Open ? 'linear-gradient(135deg, #095866, #0b7d92)' : '#C0C5CC' }">
								1
							</div>
							<div class="flex-1 min-w-0">
								<h2 class="text-[#1d2738] text-[18px] sm:text-[20px] tracking-[-0.4px]" style="font-weight:800">Colli e tratta</h2>
								<p v-if="!isStep1Open" class="text-[#999] text-[13px] mt-[2px] truncate" style="font-weight:500">
									{{ trattaLabel }} · {{ colloLabel }}
								</p>
							</div>
							<span v-if="!isStep1Open" class="shrink-0 hidden sm:block">
								<span class="text-[13px] text-[#777] bg-[#F0F1F4] px-[12px] py-[5px] rounded-full" style="font-weight:600">{{ colloLabel }}</span>
							</span>
							<span
								class="shrink-0 text-[#C0C5CC]"
								:style="{ transform: isStep1Open ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.3s cubic-bezier(0.22,1,0.36,1)' }"
								aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6" /></svg>
							</span>
						</button>
						<!-- Body -->
						<Transition
							@before-enter="onAccordionPanelBeforeEnter"
							@enter="onAccordionPanelEnter"
							@after-enter="onAccordionPanelAfterEnter"
							@before-leave="onAccordionPanelBeforeLeave"
							@leave="onAccordionPanelLeave"
							@after-leave="onAccordionPanelAfterLeave">
							<div
								v-if="isStep1Open"
								class="px-[20px] sm:px-[28px] pb-[24px] sm:pb-[28px]"
								style="background:linear-gradient(180deg,#F8F9FB 0%,#EEF0F3 100%)">
								<div class="pt-[18px] flex flex-col gap-[14px]">
									<!-- Route grey block -->
									<div class="rounded-[16px] p-[16px] sm:p-[18px]" style="background:#E6E9EE; box-shadow:inset 0 1px 2px rgba(0,0,0,0.04)">
										<div class="flex items-center gap-[12px]">
											<div class="w-[38px] h-[38px] bg-white rounded-[10px] flex items-center justify-center border border-[#DFE2E7] shrink-0">
												<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2" /><path d="M16 8h4l3 3v5h-7V8z" /><circle cx="5.5" cy="18.5" r="2.5" /><circle cx="18.5" cy="18.5" r="2.5" /></svg>
											</div>
											<div class="flex-1 min-w-0">
												<div class="text-[14px] text-[#1d2738]" style="font-weight:700">{{ trattaLabel }}</div>
												<div class="text-[12px] text-[#777]" style="font-weight:500">{{ colloLabel }}</div>
											</div>
											<NuxtLink
												to="/"
												class="h-[34px] px-[14px] rounded-full bg-white text-[#777] text-[13px] cursor-pointer hover:bg-[#095866] hover:text-white transition-all duration-[350ms] flex items-center gap-[5px] ring-[1px] ring-[#DFE2E7] hover:ring-[#095866]"
												style="font-weight:600">
												<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" /></svg>
												Modifica
											</NuxtLink>
										</div>
										<template v-if="packageItems.length > 0">
											<div class="mt-[12px] pt-[12px] border-t border-[#D5D9E0] flex flex-col gap-[6px]">
												<div v-for="(pkg, i) in packageItems" :key="i" class="flex justify-between items-center">
													<span class="text-[13px] text-[#777]" style="font-weight:500">
														{{ pkg.package_type || pkg.tipo || 'Collo' }} #{{ i + 1 }} — {{ pkg.weight || pkg.peso || '?' }}kg · {{ pkg.length || pkg.lunghezza || '?' }}×{{ pkg.width || pkg.larghezza || '?' }}×{{ pkg.height || pkg.altezza || '?' }}cm
													</span>
													<span class="text-[13px] text-[#1d2738]" style="font-weight:700">{{ summaryTotalPrice || '—' }}</span>
												</div>
											</div>
										</template>
									</div>
									<!-- CTA Continue -->
									<div class="flex justify-end pt-[12px]">
										<button
											type="button"
											class="h-[48px] px-[26px] rounded-full text-white text-[14px] cursor-pointer transition-all duration-[350ms] flex items-center gap-[8px] focus:outline-none focus:ring-[2px] focus:ring-[#095866]/60"
											style="font-weight:700; background:linear-gradient(135deg,#095866,#0a7489); box-shadow:0 3px 14px rgba(9,88,102,0.2)"
											@click="extraStepOpen = 0; !isServicesAccordionOpen && collapseAddressAccordion()">
											Continua
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
										</button>
									</div>
								</div>
							</div>
						</Transition>
					</section>

					<!-- Step 2 — Servizi -->
					<section
						ref="servicesStageRef"
						class="rounded-[22px] overflow-hidden"
						:style="{
							boxShadow: isServicesAccordionOpen
								? '0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)'
								: '0 0 0 1px rgba(0,0,0,0.04), 0 1px 6px rgba(0,0,0,0.03)',
							transition: 'box-shadow 0.45s cubic-bezier(0.22,1,0.36,1)',
						}">

						<!-- Top accent bar -->
						<div
							class="h-[4px] transition-all duration-[450ms]"
							:style="{ background: isServicesAccordionOpen ? 'linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)' : '#E6E9EE' }" />

						<!-- Header button -->
						<button
							type="button"
							class="w-full px-[20px] sm:px-[28px] py-[18px] sm:py-[20px] flex items-center gap-[14px] text-left cursor-pointer transition-all duration-[350ms] hover:bg-[#EEF0F3]"
							style="background: #F8F9FB"
							data-accordion-trigger="services"
							:aria-expanded="isServicesAccordionOpen ? 'true' : 'false'"
							@click="extraStepOpen = 0; !isServicesAccordionOpen && collapseAddressAccordion()">

							<!-- Step number circle -->
							<div
								class="w-[36px] h-[36px] rounded-full flex items-center justify-center shrink-0 text-[14px] text-white transition-colors duration-[450ms]"
								:style="{ fontWeight: 800, background: isServicesAccordionOpen ? 'linear-gradient(135deg, #095866, #0b7d92)' : '#C0C5CC' }">
								2
							</div>

							<!-- Text -->
							<div class="flex-1 min-w-0">
								<h2 class="text-[#1d2738] text-[18px] sm:text-[20px] tracking-[-0.4px]" style="font-weight:800">Servizi</h2>
								<p class="text-[#999] text-[13px] mt-[2px] truncate" style="font-weight:500">
									{{ isServicesAccordionOpen ? 'Ritiro, extra e contenuto del pacco.' : servicesAccordionSummary }}
								</p>
							</div>

							<!-- Chevron -->
							<span
								class="shrink-0 text-[#C0C5CC]"
								:style="{ transform: isServicesAccordionOpen ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.3s cubic-bezier(0.22,1,0.36,1)' }"
								aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M6 9l6 6 6-6" />
								</svg>
							</span>
						</button>

						<!-- Body panel -->
						<Transition
							@before-enter="onAccordionPanelBeforeEnter"
							@enter="onAccordionPanelEnter"
							@after-enter="onAccordionPanelAfterEnter"
							@before-leave="onAccordionPanelBeforeLeave"
							@leave="onAccordionPanelLeave"
							@after-leave="onAccordionPanelAfterLeave">
							<div
								v-if="isServicesAccordionOpen"
								class="px-[20px] sm:px-[28px] pb-[24px] sm:pb-[28px]"
								style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
								<div class="pt-[18px] flex flex-col gap-[24px]">
									<ShipmentStepPickupDate
										ref="pickupDateSectionRef"
										:date-error="dateError"
										:days-in-month="daysInMonth"
										:services="services"
										@choose-date="chooseDate" />
									<ShipmentStepServicesGrid
										:featured-service="featuredService"
										:regular-services="regularServices"
										:service-data="serviceData"
										:service-card-errors="serviceCardErrors"
										:is-service-expanded="isServiceExpanded"
										:can-configure-service="canConfigureService"
										:get-service-configure-label="getServiceConfigureLabel"
										:contrassegno-incasso-options="contrassegnoIncassoOptions"
										:contrassegno-rimborso-options="contrassegnoRimborsoOptions"
										:requires-contrassegno-dettaglio="requiresContrassegnoDettaglio"
										:insurance-packages="insurancePackages"
										:normalize-currency-input="normalizeCurrencyInput"
										:service-icon-filter-idle="SERVICE_ICON_FILTER_IDLE"
										:service-icon-filter-active="SERVICE_ICON_FILTER_ACTIVE"
										@toggle-featured-service="toggleFeaturedService"
										@toggle-regular-service="toggleRegularService"
										@handle-service-primary-action="handleServicePrimaryAction"
										@activate-configured-service="activateConfiguredService"
										@remove-configured-service="removeConfiguredService" />
									<ShipmentServiceContentNotifications
										:content-description="resolvedContentDescription"
										:content-error="contentError"
										:content-field-hint="contentFieldHint"
										:sms-email-notification="smsEmailNotification"
										:notification-price-label="notificationPriceLabel"
										@update:content-description="userStore.contentDescription = $event"
										@update:content-error="contentError = $event"
										@update:sms-email-notification="smsEmailNotification = $event" />

									<!-- CTA -->
									<div class="flex justify-end pt-[12px]">
										<button
											type="button"
											class="h-[48px] px-[26px] rounded-full text-white text-[14px] cursor-pointer transition-all duration-[350ms] flex items-center gap-[8px] focus:outline-none focus:ring-[2px] focus:ring-[#095866]/60"
											style="font-weight:700; background:linear-gradient(135deg,#095866,#0a7489); box-shadow:0 3px 14px rgba(9,88,102,0.2)"
											@click="openAddressAccordion">
											Continua agli indirizzi
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
												<path d="M5 12h14M12 5l7 7-7 7" />
											</svg>
										</button>
									</div>

									<!-- Submit error -->
									<div
										v-if="submitError"
										class="rounded-[12px] bg-red-50 border border-red-200 px-[16px] py-[12px] text-red-700 text-[13px]"
										style="font-weight:500">
										{{ submitError }}
									</div>
								</div>
							</div>
						</Transition>
					</section>

					<!-- Step 3 — Indirizzi -->
					<section
						ref="addressStageRef"
						class="rounded-[22px] overflow-hidden"
						:style="{
							boxShadow: isAddressAccordionOpen
								? '0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)'
								: '0 0 0 1px rgba(0,0,0,0.04), 0 1px 6px rgba(0,0,0,0.03)',
							transition: 'box-shadow 0.45s cubic-bezier(0.22,1,0.36,1)',
						}">

						<!-- Top accent bar -->
						<div
							class="h-[4px] transition-all duration-[450ms]"
							:style="{ background: isAddressAccordionOpen ? 'linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)' : '#E6E9EE' }" />

						<!-- Header button -->
						<button
							type="button"
							class="w-full px-[20px] sm:px-[28px] py-[18px] sm:py-[20px] flex items-center gap-[14px] text-left cursor-pointer transition-all duration-[350ms] hover:bg-[#EEF0F3]"
							style="background: #F8F9FB"
							data-accordion-trigger="addresses"
							:aria-expanded="isAddressAccordionOpen ? 'true' : 'false'"
							@click="extraStepOpen = 0; !isAddressAccordionOpen && openAddressAccordion()">

							<!-- Step number circle -->
							<div
								class="w-[36px] h-[36px] rounded-full flex items-center justify-center shrink-0 text-[14px] text-white transition-colors duration-[450ms]"
								:style="{ fontWeight: 800, background: isAddressAccordionOpen ? 'linear-gradient(135deg, #095866, #0b7d92)' : '#C0C5CC' }">
								3
							</div>

							<!-- Text -->
							<div class="flex-1 min-w-0">
								<h2 class="text-[#1d2738] text-[18px] sm:text-[20px] tracking-[-0.4px]" style="font-weight:800">Indirizzi</h2>
								<p class="text-[#999] text-[13px] mt-[2px] truncate" style="font-weight:500">
									{{ isAddressAccordionOpen ? 'Partenza, destinazione e consegna.' : addressAccordionSummary }}
								</p>
							</div>

							<!-- Chevron -->
							<span
								class="shrink-0 text-[#C0C5CC]"
								:style="{ transform: isAddressAccordionOpen ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.3s cubic-bezier(0.22,1,0.36,1)' }"
								aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M6 9l6 6 6-6" />
								</svg>
							</span>
						</button>

						<!-- Body panel -->
						<Transition
							@before-enter="onAccordionPanelBeforeEnter"
							@enter="onAccordionPanelEnter"
							@after-enter="onAccordionPanelAfterEnter"
							@before-leave="onAccordionPanelBeforeLeave"
							@leave="onAccordionPanelLeave"
							@after-leave="onAccordionPanelAfterLeave">
							<div
								v-if="isAddressAccordionOpen"
								class="px-[20px] sm:px-[28px] pb-[24px] sm:pb-[28px]"
								style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
								<div class="pt-[18px] flex flex-col gap-[16px]">
									<ShipmentStepAddressSection
										:is-open="showAddressFields"
										:origin-address="originAddress"
										:destination-address="destinationAddress"
										:delivery-mode="deliveryMode"
										:saved-addresses="savedAddresses"
										:loading-saved-addresses="loadingSavedAddresses"
										:show-origin-address-selector="showOriginAddressSelector"
										:show-dest-address-selector="showDestAddressSelector"
										:is-authenticated="isAuthenticated"
										:can-save-origin-address="canSaveOriginAddress"
										:can-save-dest-address="canSaveDestAddress"
										:saving-origin-address="savingOriginAddress"
										:saving-dest-address="savingDestAddress"
										:selected-pudo="userStore.selectedPudo"
										v-model:origin-selector-ref="originSelectorRef"
										v-model:dest-selector-ref="destSelectorRef"
										@update:delivery-mode="(v) => (deliveryMode = v)"
										@save-address="saveAddressToBook"
										@toggle-address-selector="toggleAddressSelector"
										@apply-saved-address="applySavedAddress"
										@open-auth-modal="openShipmentAuthModal"
										@pudo-selected="onPudoSelected"
										@pudo-deselected="onPudoDeselected" />
									<ShipmentStepNavigation
										:show-address-fields="showAddressFields"
										:address-readiness-items="addressReadinessItems"
										:show-readiness-note="false"
										:show-desktop-advance-button="false"
										:is-submitting="isSubmitting"
										:edit-cart-id="editCartId"
										:summary-total-price="summaryTotalPrice"
										:submit-error="submitError"
										:soften-error-message="softenErrorMessage"
										@go-back-to-services="collapseAddressAccordion"
										@open-address-fields="openAddressAccordion" />
								</div>
							</div>
						</Transition>
					</section>

					<!-- ═══════════════════════════════════════════
					     STEP 4 — Riepilogo
					     ═══════════════════════════════════════════ -->
					<section
						id="step-4"
						class="rounded-[22px] overflow-hidden"
						:style="{
							boxShadow: isStep4Open
								? '0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)'
								: '0 0 0 1px rgba(0,0,0,0.04), 0 1px 6px rgba(0,0,0,0.03)',
							transition: 'box-shadow 0.45s cubic-bezier(0.22,1,0.36,1)',
						}">
						<div
							class="h-[4px] transition-all duration-[450ms]"
							:style="{ background: isStep4Open ? 'linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)' : '#E6E9EE' }" />
						<button
							type="button"
							class="w-full px-[20px] sm:px-[28px] py-[18px] sm:py-[20px] flex items-center gap-[14px] text-left cursor-pointer transition-all duration-[350ms] hover:bg-[#EEF0F3]"
							style="background:#F8F9FB"
							@click="toggleExtraStep(4)">
							<div
								class="w-[36px] h-[36px] rounded-full flex items-center justify-center shrink-0 text-[14px] text-white transition-colors duration-[450ms]"
								:style="{ fontWeight: 800, background: isStep4Open ? 'linear-gradient(135deg, #095866, #0b7d92)' : '#C0C5CC' }">
								4
							</div>
							<div class="flex-1 min-w-0">
								<h2 class="text-[#1d2738] text-[18px] sm:text-[20px] tracking-[-0.4px]" style="font-weight:800">Riepilogo</h2>
								<p v-if="!isStep4Open" class="text-[#999] text-[13px] mt-[2px] truncate" style="font-weight:500">
									Totale: {{ summaryTotalPrice || '—' }}
								</p>
							</div>
							<span
								class="shrink-0 text-[#C0C5CC]"
								:style="{ transform: isStep4Open ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.3s cubic-bezier(0.22,1,0.36,1)' }"
								aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6" /></svg>
							</span>
						</button>
						<Transition
							@before-enter="onAccordionPanelBeforeEnter"
							@enter="onAccordionPanelEnter"
							@after-enter="onAccordionPanelAfterEnter"
							@before-leave="onAccordionPanelBeforeLeave"
							@leave="onAccordionPanelLeave"
							@after-leave="onAccordionPanelAfterLeave">
							<div
								v-if="isStep4Open"
								class="px-[20px] sm:px-[28px] pb-[24px] sm:pb-[28px]"
								style="background:linear-gradient(180deg,#F8F9FB 0%,#EEF0F3 100%)">
								<div class="pt-[18px] flex flex-col gap-[16px]">

									<!-- Route + date summary card -->
									<div
										class="flex items-center gap-[12px] rounded-[14px] p-[16px] bg-white ring-[1px] ring-[#DFE2E7]"
										style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
										<div class="w-[38px] h-[38px] bg-[#095866]/[0.08] rounded-[10px] flex items-center justify-center shrink-0">
											<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2" /><path d="M16 8h4l3 3v5h-7V8z" /><circle cx="5.5" cy="18.5" r="2.5" /><circle cx="18.5" cy="18.5" r="2.5" /></svg>
										</div>
										<div class="flex-1 min-w-0">
											<div class="text-[14px] text-[#1d2738]" style="font-weight:700">{{ trattaLabel }}</div>
											<div class="flex items-center gap-[6px] text-[12px] text-[#777] mt-[2px]" style="font-weight:500">
												<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
												Ritiro: {{ services?.date || '—' }}
											</div>
										</div>
									</div>

									<!-- Individual colli cards -->
									<div v-for="(pkg, i) in packageItems" :key="'pkg-' + i"
										class="rounded-[14px] p-[16px] bg-white ring-[1px] ring-[#DFE2E7]"
										style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
										<div class="flex items-center justify-between">
											<div class="flex items-center gap-[10px]">
												<div class="w-[32px] h-[32px] rounded-full bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
													<span class="text-[#095866] text-[13px]" style="font-weight:700">{{ i + 1 }}</span>
												</div>
												<div>
													<span class="text-[#1d2738] text-[14px]" style="font-weight:700">
														{{ pkg.package_type || pkg.tipo || 'Collo' }}{{ (pkg.quantity || pkg.quantita || 1) > 1 ? ' ×' + (pkg.quantity || pkg.quantita) : '' }}
													</span>
													<p class="text-[#777] text-[12px]" style="font-weight:400">
														{{ pkg.weight || pkg.peso || '?' }} kg · {{ pkg.length || pkg.lunghezza || '?' }}×{{ pkg.width || pkg.larghezza || '?' }}×{{ pkg.height || pkg.altezza || '?' }} cm
													</p>
												</div>
											</div>
											<span class="text-[#095866] text-[16px] tracking-tight" style="font-weight:800">
												{{ summaryTotalPrice || '—' }}
											</span>
										</div>
									</div>

									<!-- Addresses side by side -->
									<div class="grid grid-cols-1 sm:grid-cols-2 gap-[10px]">
										<!-- From (mittente) -->
										<div class="rounded-[14px] p-[16px] bg-white ring-[1px] ring-[#DFE2E7]" style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
											<div class="flex items-center gap-[6px] mb-[8px]">
												<div class="w-[8px] h-[8px] rounded-full bg-[#E44203]" />
												<span class="text-[11px] text-[#E44203] uppercase tracking-wider" style="font-weight:700">Da</span>
											</div>
											<p class="text-[13px] text-[#1d2738]" style="font-weight:700">{{ originAddress?.name || '—' }}</p>
											<p class="text-[12px] text-[#777] mt-[2px]">{{ originAddress?.address || '—' }} {{ originAddress?.street_number || '' }}</p>
											<p class="text-[12px] text-[#777]">{{ originAddress?.zip || '' }} {{ originAddress?.city || '' }} {{ originAddress?.province || '' }}</p>
										</div>
										<!-- To (destinatario) -->
										<div class="rounded-[14px] p-[16px] bg-white ring-[1px] ring-[#DFE2E7]" style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
											<div class="flex items-center gap-[6px] mb-[8px]">
												<div class="w-[8px] h-[8px] rounded-full bg-[#095866]" />
												<span class="text-[11px] text-[#095866] uppercase tracking-wider" style="font-weight:700">
													{{ deliveryMode === 'pudo' ? 'Punto BRT' : 'A' }}
												</span>
											</div>
											<p class="text-[13px] text-[#1d2738]" style="font-weight:700">{{ destinationAddress?.name || '—' }}</p>
											<p class="text-[12px] text-[#777] mt-[2px]">{{ destinationAddress?.address || '—' }} {{ destinationAddress?.street_number || '' }}</p>
											<p class="text-[12px] text-[#777]">{{ destinationAddress?.zip || '' }} {{ destinationAddress?.city || '' }} {{ destinationAddress?.province || '' }}</p>
										</div>
									</div>

									<!-- Price breakdown grey block -->
									<div
										class="flex flex-col gap-[6px] rounded-[16px] p-[18px]"
										style="background:#E6E9EE; box-shadow:inset 0 1px 2px rgba(0,0,0,0.04)">
										<!-- Base package lines -->
										<div v-for="(pkg, i) in packageItems" :key="'sum-' + i" class="flex justify-between items-center">
											<span class="text-[13px] text-[#777]" style="font-weight:500">{{ pkg.package_type || pkg.tipo || 'Collo' }} #{{ i + 1 }} — {{ pkg.weight || pkg.peso || '?' }}kg</span>
											<span class="text-[13px] text-[#1d2738]" style="font-weight:700">{{ summaryTotalPrice || '—' }}</span>
										</div>
										<!-- Service lines -->
										<div v-for="svc in selectedServicesForSummary" :key="svc.label" class="flex justify-between items-center">
											<span class="text-[13px] text-[#777]" style="font-weight:500">{{ svc.label }}</span>
											<span class="text-[13px] text-[#1d2738]" style="font-weight:700">{{ svc.price || '+—' }}</span>
										</div>
										<!-- Divider + total -->
										<div class="h-[1px] bg-[#F0F1F4] my-[6px]" />
										<div class="flex justify-between items-center">
											<span class="text-[15px] text-[#1d2738]" style="font-weight:800">Totale</span>
											<span class="text-[20px] text-[#095866] tracking-tight" style="font-weight:800">{{ summaryTotalPrice || '—' }}</span>
										</div>
									</div>

									<!-- CTA: orange Procedi al pagamento -->
									<div class="flex justify-end pt-[4px]">
										<button
											type="button"
											class="h-[50px] px-[28px] rounded-full text-white text-[14px] cursor-pointer transition-all duration-[350ms] flex items-center gap-[8px] focus:outline-none focus:ring-[2px] focus:ring-[#095866]/60 hover:shadow-[0_8px_24px_rgba(228,66,3,0.25)] active:scale-[0.97]"
											style="font-weight:700; background:linear-gradient(135deg,#E44203,#c73600); box-shadow:0 4px 16px rgba(228,66,3,0.2)"
											@click="extraStepOpen = 5; $nextTick(() => document.getElementById('step-5')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
											Procedi al pagamento
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
										</button>
									</div>
								</div>
							</div>
						</Transition>
					</section>

					<!-- ═══════════════════════════════════════════
					     STEP 5 — Pagamento
					     ═══════════════════════════════════════════ -->
					<section
						id="step-5"
						class="rounded-[22px] overflow-hidden"
						:style="{
							boxShadow: isStep5Open
								? '0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)'
								: '0 0 0 1px rgba(0,0,0,0.04), 0 1px 6px rgba(0,0,0,0.03)',
							transition: 'box-shadow 0.45s cubic-bezier(0.22,1,0.36,1)',
						}">
						<div
							class="h-[4px] transition-all duration-[450ms]"
							:style="{ background: isStep5Open ? 'linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)' : '#E6E9EE' }" />
						<button
							type="button"
							class="w-full px-[20px] sm:px-[28px] py-[18px] sm:py-[20px] flex items-center gap-[14px] text-left cursor-pointer transition-all duration-[350ms] hover:bg-[#EEF0F3]"
							style="background:#F8F9FB"
							@click="toggleExtraStep(5)">
							<div
								class="w-[36px] h-[36px] rounded-full flex items-center justify-center shrink-0 text-[14px] text-white transition-colors duration-[450ms]"
								:style="{ fontWeight: 800, background: isStep5Open ? 'linear-gradient(135deg, #095866, #0b7d92)' : '#C0C5CC' }">
								5
							</div>
							<div class="flex-1 min-w-0">
								<h2 class="text-[#1d2738] text-[18px] sm:text-[20px] tracking-[-0.4px]" style="font-weight:800">Pagamento</h2>
								<p v-if="!isStep5Open" class="text-[#999] text-[13px] mt-[2px] truncate" style="font-weight:500">
									Totale: {{ summaryTotalPrice || '—' }}
								</p>
							</div>
							<span
								class="shrink-0 text-[#C0C5CC]"
								:style="{ transform: isStep5Open ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.3s cubic-bezier(0.22,1,0.36,1)' }"
								aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6" /></svg>
							</span>
						</button>
						<Transition
							@before-enter="onAccordionPanelBeforeEnter"
							@enter="onAccordionPanelEnter"
							@after-enter="onAccordionPanelAfterEnter"
							@before-leave="onAccordionPanelBeforeLeave"
							@leave="onAccordionPanelLeave"
							@after-leave="onAccordionPanelAfterLeave">
							<div
								v-if="isStep5Open"
								class="px-[20px] sm:px-[28px] pb-[24px] sm:pb-[28px]"
								style="background:linear-gradient(180deg,#F8F9FB 0%,#EEF0F3 100%)">
								<div class="pt-[18px] flex flex-col gap-[18px]">

									<!-- Order summary mini card -->
									<div class="rounded-[14px] p-[18px] bg-white ring-[1px] ring-[#DFE2E7]" style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
										<div class="flex items-center justify-between mb-[10px]">
											<div class="flex items-center gap-[8px]">
												<div class="w-[28px] h-[28px] rounded-[8px] bg-[#095866]/[0.08] flex items-center justify-center">
													<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" /><path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12" /></svg>
												</div>
												<span class="text-[#1d2738] text-[14px]" style="font-weight:700">Riepilogo ordine</span>
												<span class="text-[#999] text-[12px]">{{ colloLabel }}</span>
											</div>
											<span class="text-[#095866] text-[18px] tracking-tight" style="font-weight:800">{{ summaryTotalPrice || '—' }}</span>
										</div>
										<div class="flex items-center gap-[8px] text-[12px] text-[#777]" style="font-weight:500">
											<div class="flex items-center gap-[4px]"><div class="w-[6px] h-[6px] rounded-full bg-[#E44203]" /> {{ summaryOriginCity || '—' }}</div>
											<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#C0C5CC" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
											<div class="flex items-center gap-[4px]"><div class="w-[6px] h-[6px] rounded-full bg-[#095866]" /> {{ summaryDestinationCity || '—' }}</div>
											<span class="text-[#DFE2E7]">·</span>
											<span>Ritiro: {{ services?.date || '—' }}</span>
										</div>
									</div>

									<!-- Promo code expandable -->
									<div class="rounded-[14px] p-[16px] bg-white ring-[1px] ring-[#DFE2E7]" style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
										<button type="button" class="w-full flex items-center justify-between cursor-pointer" @click="promoOpen = !promoOpen">
											<div class="flex items-center gap-[8px]">
												<div class="w-[28px] h-[28px] rounded-[8px] bg-[#E44203]/[0.08] flex items-center justify-center">
													<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2"><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6" /><path d="M2 8h20v4H2z" /><path d="M12 2v6" /><path d="M12 2a3 3 0 0 0-3 3h6a3 3 0 0 0-3-3z" /></svg>
												</div>
												<span class="text-[#1d2738] text-[14px]" style="font-weight:600">Codice promozionale</span>
											</div>
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#C0C5CC" stroke-width="2"
												:class="promoOpen ? 'rotate-180' : ''" class="transition-transform duration-300"><path d="M6 9l6 6 6-6" /></svg>
										</button>
										<Transition
											@before-enter="onAccordionPanelBeforeEnter"
											@enter="onAccordionPanelEnter"
											@after-enter="onAccordionPanelAfterEnter"
											@before-leave="onAccordionPanelBeforeLeave"
											@leave="onAccordionPanelLeave"
											@after-leave="onAccordionPanelAfterLeave">
											<div v-if="promoOpen">
												<div class="flex gap-[8px] mt-[14px]">
													<template v-if="promoApplied">
														<div class="flex-1 flex items-center gap-[8px] bg-[#095866]/[0.06] rounded-[12px] px-[14px] h-[48px] ring-[1px] ring-[#095866]/20">
															<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="3"><path d="M20 6 9 17l-5-5" /></svg>
															<span class="text-[#095866] text-[13px]" style="font-weight:600">Codice applicato</span>
														</div>
													</template>
													<template v-else>
														<input
															v-model="promoCode"
															type="text"
															placeholder="Inserisci codice..."
															:class="INPUT_CLS + ' flex-1 uppercase tracking-wider placeholder:normal-case'"
															style="font-weight:600" />
														<button
															type="button"
															class="h-[48px] sm:h-[50px] px-[20px] rounded-full text-white text-[13px] cursor-pointer shrink-0"
															style="font-weight:700; background:linear-gradient(135deg,#095866,#0a7489)"
															@click="promoCode.length > 0 && (promoApplied = true)">
															Applica
														</button>
													</template>
												</div>
											</div>
										</Transition>
									</div>

									<!-- Payment methods -->
									<div>
										<div class="flex items-center gap-[10px] mb-[14px]">
											<div class="w-[32px] h-[32px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" /><path d="M1 10h22" /></svg>
											</div>
											<span class="text-[#1d2738] text-[16px] sm:text-[17px] tracking-[-0.2px]" style="font-weight:700">Metodo di pagamento</span>
										</div>
										<div class="grid grid-cols-3 gap-[10px] mb-[14px]">
											<button
												v-for="m in [
													{ id: 'carta', label: 'Carta', desc: 'Visa, Mastercard', badge: 'Più usato', icon: 'card' },
													{ id: 'bonifico', label: 'Bonifico', desc: '1-2 gg lavorativi', icon: 'bank' },
													{ id: 'wallet', label: 'Wallet', desc: 'Saldo prepagato', icon: 'wallet' },
												]"
												:key="m.id"
												type="button"
												class="relative flex flex-col items-center gap-[6px] py-[16px] px-[8px] rounded-[14px] cursor-pointer transition-all duration-[350ms] bg-white"
												:class="payMethod === m.id
													? 'ring-[2.5px] ring-[#095866] shadow-[0_4px_16px_rgba(9,88,102,0.1)]'
													: 'ring-[1.5px] ring-[#DFE2E7] hover:ring-[2px] hover:ring-[#095866]/50 hover:bg-[#FAFBFC] hover:shadow-[0_4px_16px_rgba(9,88,102,0.06)]'"
												@click="payMethod = m.id">
												<span v-if="m.badge" class="absolute -top-[8px] left-1/2 -translate-x-1/2 text-[9px] px-[8px] py-[2px] rounded-full bg-[#095866] text-white whitespace-nowrap z-10" style="font-weight:700">{{ m.badge }}</span>
												<div :class="'w-[40px] h-[40px] rounded-[12px] flex items-center justify-center transition-all ' + (payMethod === m.id ? 'bg-[#095866] text-white' : 'bg-[#F0F1F4] text-[#777]')">
													<svg v-if="m.icon === 'card'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" /><path d="M1 10h22" /></svg>
													<svg v-else-if="m.icon === 'bank'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V12M18 22V12M2 22h20M12 2l10 7H2l10-7zM2 12h20" /></svg>
													<svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4" /><path d="M3 5v14a2 2 0 0 0 2 2h16v-5" /><path d="M18 12a2 2 0 0 0 0 4h4v-4h-4z" /></svg>
												</div>
												<span :class="'text-[12px] ' + (payMethod === m.id ? 'text-[#095866]' : 'text-[#1d2738]')" style="font-weight:600">{{ m.label }}</span>
											</button>
										</div>

										<!-- Card form -->
										<Transition
											@before-enter="onAccordionPanelBeforeEnter"
											@enter="onAccordionPanelEnter"
											@after-enter="onAccordionPanelAfterEnter"
											@before-leave="onAccordionPanelBeforeLeave"
											@leave="onAccordionPanelLeave"
											@after-leave="onAccordionPanelAfterLeave">
											<div v-if="payMethod === 'carta'" key="pay-carta"
												class="rounded-[14px] bg-white ring-[1px] ring-[#DFE2E7] p-[18px] space-y-[12px]"
												style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
												<p class="text-[#1d2738] text-[13px]" style="font-weight:600">Inserisci i dati della carta</p>
												<div class="grid grid-cols-1 sm:grid-cols-2 gap-[10px]">
													<div>
														<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Intestatario</label>
														<input type="text" :value="cardName" @input="cardName = $event.target.value" placeholder="Nome e cognome" :class="INPUT_CLS" style="font-weight:600" />
													</div>
													<div>
														<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Numero carta</label>
														<input type="text" :value="cardNumber" @input="cardNumber = fmtCard($event.target.value)" placeholder="1234 5678 9012 3456" maxlength="19"
															:class="INPUT_CLS" style="font-weight:600; font-family:monospace; letter-spacing:1px" />
													</div>
												</div>
												<div class="grid grid-cols-2 gap-[10px]">
													<div>
														<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Scadenza</label>
														<input type="text" :value="cardExpiry" @input="cardExpiry = fmtExp($event.target.value)" placeholder="MM/AA" maxlength="5"
															:class="INPUT_CLS" style="font-weight:600; font-family:monospace" />
													</div>
													<div>
														<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">CVV</label>
														<div class="relative">
															<input :type="showCvv ? 'text' : 'password'" :value="cardCvv" @input="cardCvv = $event.target.value.replace(/\D/g, '').slice(0, 4)" placeholder="•••" maxlength="4"
																:class="INPUT_CLS + ' pr-[42px]'" style="font-weight:600; font-family:monospace" />
															<button type="button" class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#C0C5CC] hover:text-[#777] cursor-pointer transition-colors" @click="showCvv = !showCvv">
																<svg v-if="showCvv" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" /><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" /><path d="M1 1l22 22" /><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" /></svg>
																<svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
															</button>
														</div>
													</div>
												</div>
												<button type="button" class="flex items-center gap-[8px] cursor-pointer pt-[4px]" @click="saveCard = !saveCard">
													<div :class="'w-[20px] h-[20px] rounded-[5px] flex items-center justify-center transition-all ' + (saveCard ? 'bg-[#095866]' : 'bg-[#E6E9EE] ring-[1.5px] ring-[#C0C5CC]')">
														<svg v-if="saveCard" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6 9 17l-5-5" /></svg>
													</div>
													<span class="text-[#777] text-[12px]" style="font-weight:500">Salva per i prossimi pagamenti</span>
												</button>
											</div>
										</Transition>

										<!-- Bonifico info -->
										<Transition
											@before-enter="onAccordionPanelBeforeEnter"
											@enter="onAccordionPanelEnter"
											@after-enter="onAccordionPanelAfterEnter"
											@before-leave="onAccordionPanelBeforeLeave"
											@leave="onAccordionPanelLeave"
											@after-leave="onAccordionPanelAfterLeave">
											<div v-if="payMethod === 'bonifico'" key="pay-bonifico"
												class="rounded-[14px] bg-white ring-[1px] ring-[#DFE2E7] p-[18px] space-y-[8px]"
												style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
												<div v-for="r in [{ l: 'IBAN', v: 'IT00 X000 0000 0000 0000 0000 000' }, { l: 'Intestatario', v: 'SpediamoFacile S.r.l.' }, { l: 'Causale', v: 'Ordine spedizione' }]"
													:key="r.l" class="flex justify-between text-[13px]">
													<span class="text-[#777]">{{ r.l }}</span>
													<span class="text-[#1d2738] font-mono" style="font-weight:700">{{ r.v }}</span>
												</div>
												<div class="flex items-start gap-[6px] pt-[10px] border-t border-[#F0F1F4]">
													<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2" class="mt-[1px] shrink-0"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
													<p class="text-[#777] text-[12px]">Spedizione attivata dopo ricezione del bonifico (1-2 gg)</p>
												</div>
											</div>
										</Transition>

										<!-- Wallet -->
										<Transition
											@before-enter="onAccordionPanelBeforeEnter"
											@enter="onAccordionPanelEnter"
											@after-enter="onAccordionPanelAfterEnter"
											@before-leave="onAccordionPanelBeforeLeave"
											@leave="onAccordionPanelLeave"
											@after-leave="onAccordionPanelAfterLeave">
											<div v-if="payMethod === 'wallet'" key="pay-wallet"
												class="rounded-[14px] bg-white ring-[1px] ring-[#DFE2E7] p-[22px] text-center"
												style="box-shadow:0 1px 4px rgba(0,0,0,0.03)">
												<span class="text-[#095866] text-[24px] block" style="font-weight:800">0,00€</span>
												<p class="text-[#777] text-[12px] mt-[2px]">
													Saldo insufficiente · <span class="text-[#095866] cursor-pointer" style="font-weight:600">Ricarica</span>
												</p>
											</div>
										</Transition>
									</div>

									<!-- Fiscal document -->
									<div>
										<div class="flex items-center gap-[10px] mb-[14px]">
											<div class="w-[32px] h-[32px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2"><path d="M6 22V12M18 22V12M2 22h20M12 2l10 7H2l10-7zM2 12h20" /></svg>
											</div>
											<span class="text-[#1d2738] text-[16px] sm:text-[17px] tracking-[-0.2px]" style="font-weight:700">Documento fiscale</span>
										</div>
										<!-- Ricevuta / Fattura toggle -->
										<div class="flex p-[3px] bg-[#F0F1F4] rounded-full gap-[2px] mb-[12px]">
											<button
												v-for="d in ['ricevuta', 'fattura']" :key="d"
												type="button"
												class="flex-1 h-[42px] rounded-full text-[13px] cursor-pointer transition-all duration-[350ms]"
												:class="docType === d ? 'bg-[#095866] text-white shadow-[0_1px_4px_rgba(9,88,102,0.2)]' : 'text-[#777] hover:text-[#1d2738]'"
												:style="{ fontWeight: docType === d ? 700 : 500 }"
												@click="docType = d">
												{{ d === 'ricevuta' ? 'Ricevuta' : 'Fattura' }}
											</button>
										</div>
										<span v-if="docType === 'ricevuta'" class="text-[#777] text-[12px] flex items-center gap-[6px]">
											<span class="w-[6px] h-[6px] rounded-full bg-[#095866] shrink-0 inline-block" />
											Usiamo i dati del checkout per la ricevuta.
										</span>
										<!-- Fattura forms -->
										<Transition
											@before-enter="onAccordionPanelBeforeEnter"
											@enter="onAccordionPanelEnter"
											@after-enter="onAccordionPanelAfterEnter"
											@before-leave="onAccordionPanelBeforeLeave"
											@leave="onAccordionPanelLeave"
											@after-leave="onAccordionPanelAfterLeave">
											<div v-if="docType === 'fattura'" class="space-y-[12px] pt-[4px]">
												<!-- Azienda / Privato sub-toggle -->
												<div class="flex gap-[6px]">
													<button
														v-for="ft in ['azienda', 'privato']" :key="ft"
														type="button"
														class="h-[36px] px-[16px] rounded-full text-[12px] cursor-pointer transition-all ring-[1.5px]"
														:class="fatturaType === ft ? 'ring-[#095866] bg-[#095866] text-white' : 'ring-[#DFE2E7] bg-white text-[#777]'"
														style="font-weight:600"
														@click="fatturaType = ft">
														{{ ft === 'azienda' ? 'Azienda' : 'Privato' }}
													</button>
												</div>
												<!-- Azienda form -->
												<div v-if="fatturaType === 'azienda'" class="space-y-[12px]">
													<div class="grid grid-cols-1 sm:grid-cols-3 gap-[10px]">
														<div>
															<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Ragione Sociale</label>
															<input type="text" v-model="ragioneSociale" placeholder="SpediamoFacile S.r.l." :class="INPUT_CLS" style="font-weight:600" />
														</div>
														<div>
															<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Partita IVA</label>
															<input type="text" v-model="partitaIva" placeholder="IT 01234567890" :class="INPUT_CLS" style="font-weight:600" />
														</div>
														<div>
															<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Codice Fiscale</label>
															<input type="text" v-model="codiceFiscaleAz" placeholder="01234567890" :class="INPUT_CLS" style="font-weight:600" />
														</div>
													</div>
													<div class="grid grid-cols-1 sm:grid-cols-2 gap-[10px]">
														<div>
															<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Codice SDI</label>
															<input type="text" v-model="codiceSDI" placeholder="XXXXXXX" maxlength="7" :class="INPUT_CLS" style="font-weight:600" />
														</div>
														<div>
															<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">PEC</label>
															<input type="email" v-model="pecAzienda" placeholder="fattura@pec.it" :class="INPUT_CLS" style="font-weight:600" />
														</div>
													</div>
													<div class="h-[1px] bg-[#D5D9E0]" />
													<span class="text-[#777] text-[11px] uppercase tracking-[0.5px] block" style="font-weight:700">Sede legale</span>
													<div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_80px_100px] gap-[10px]">
														<input type="text" v-model="indirizzoAz" placeholder="Indirizzo" :class="INPUT_CLS" style="font-weight:600" />
														<input type="text" v-model="cittaAz" placeholder="Città" :class="INPUT_CLS" style="font-weight:600" />
														<input type="text" v-model="provinciaAz" placeholder="Prov." maxlength="2" :class="INPUT_CLS" style="font-weight:600" />
														<input type="text" v-model="capAz" placeholder="CAP" maxlength="5" :class="INPUT_CLS" style="font-weight:600" />
													</div>
												</div>
												<!-- Privato form -->
												<div v-else class="space-y-[12px]">
													<div class="grid grid-cols-1 sm:grid-cols-3 gap-[10px]">
														<div>
															<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Nome</label>
															<input type="text" v-model="nomePrivato" placeholder="Mario" :class="INPUT_CLS" style="font-weight:600" />
														</div>
														<div>
															<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Cognome</label>
															<input type="text" v-model="cognomePrivato" placeholder="Rossi" :class="INPUT_CLS" style="font-weight:600" />
														</div>
														<div>
															<label class="text-[#777] text-[12px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Codice Fiscale</label>
															<input type="text" v-model="codiceFiscalePriv" placeholder="RSSMRA85M01H501Z" maxlength="16" :class="INPUT_CLS" style="font-weight:600; text-transform:uppercase" />
														</div>
													</div>
													<div class="h-[1px] bg-[#D5D9E0]" />
													<span class="text-[#777] text-[11px] uppercase tracking-[0.5px] block" style="font-weight:700">Residenza</span>
													<div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_80px_100px] gap-[10px]">
														<input type="text" v-model="indirizzoPriv" placeholder="Indirizzo" :class="INPUT_CLS" style="font-weight:600" />
														<input type="text" v-model="cittaPriv" placeholder="Città" :class="INPUT_CLS" style="font-weight:600" />
														<input type="text" v-model="provinciaPriv" placeholder="Prov." maxlength="2" :class="INPUT_CLS" style="font-weight:600" />
														<input type="text" v-model="capPriv" placeholder="CAP" maxlength="5" :class="INPUT_CLS" style="font-weight:600" />
													</div>
												</div>
											</div>
										</Transition>
									</div>

									<!-- Total grey block -->
									<div class="rounded-[16px] p-[18px]" style="background:#E6E9EE; box-shadow:inset 0 1px 2px rgba(0,0,0,0.04)">
										<div class="flex justify-between items-center mb-[6px]">
											<span class="text-[#777] text-[11px] uppercase tracking-wider" style="font-weight:700">Totale da pagare</span>
											<span class="text-[#1d2738] text-[24px] tracking-tight" style="font-weight:800">{{ summaryTotalPrice || '—' }}</span>
										</div>
										<div class="flex items-center gap-[6px]">
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
											<span class="text-[#777] text-[11px]" style="font-weight:500">Pagamento sicuro e protetto</span>
										</div>
									</div>

									<!-- Terms checkbox -->
									<label class="flex items-start gap-[10px] cursor-pointer">
										<button type="button" @click="termsAccepted = !termsAccepted"
											class="mt-[2px] w-[22px] h-[22px] rounded-[6px] shrink-0 flex items-center justify-center transition-all duration-[350ms] cursor-pointer"
											:class="termsAccepted ? 'bg-[#095866]' : 'bg-[#E6E9EE] ring-[1.5px] ring-[#C0C5CC]'">
											<svg v-if="termsAccepted" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6 9 17l-5-5" /></svg>
										</button>
										<span class="text-[#777] text-[12px] leading-[1.6]" style="font-weight:400">
											Ho letto e accetto i
											<NuxtLink to="/termini-condizioni" class="text-[#095866] underline" style="font-weight:600">termini e condizioni</NuxtLink>
											e la
											<NuxtLink to="/privacy-policy" class="text-[#095866] underline" style="font-weight:600">privacy policy</NuxtLink>
										</span>
									</label>

									<!-- Pay CTA (orange) -->
									<button
										type="button"
										:disabled="payProcessing"
										class="w-full h-[54px] rounded-full text-white text-[15px] flex items-center justify-center gap-[8px] cursor-pointer hover:shadow-[0_8px_24px_rgba(228,66,3,0.28)] active:scale-[0.98] transition-all"
										style="font-weight:700; background:linear-gradient(135deg,#E44203 0%,#c73600 100%); box-shadow:0 6px 24px rgba(228,66,3,0.22)"
										@click="continueToCart">
										<template v-if="payProcessing">
											<div class="w-[18px] h-[18px] border-[2px] border-white/25 border-t-white rounded-full animate-spin" />
											Elaborazione...
										</template>
										<template v-else>
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
											Completa il pagamento · {{ summaryTotalPrice || '—' }}
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
										</template>
									</button>

									<!-- Security badges -->
									<div class="flex items-center justify-center gap-[16px] pt-[2px]">
										<div v-for="badge in [
											{ icon: 'shield', label: 'SSL 256-bit' },
											{ icon: 'lock', label: 'PCI-DSS' },
											{ icon: 'card', label: '3D Secure' },
										]" :key="badge.label" class="flex items-center gap-[4px] text-[#b0b5be] text-[10px]" style="font-weight:600">
											<svg v-if="badge.icon === 'shield'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
											<svg v-else-if="badge.icon === 'lock'" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
											<svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" /><path d="M1 10h22" /></svg>
											{{ badge.label }}
										</div>
									</div>
								</div>
							</div>
						</Transition>
					</section>

				</div>
			</form>
		</div>

		<!-- ══════════════════════════════════════
		     Bottom sticky bar — total + navigation
		     ══════════════════════════════════════ -->
		<div
			class="fixed bottom-0 left-0 right-0 z-50 border-t border-[#DFE2E7]"
			style="background:rgba(255,255,255,0.92); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px)">
			<div class="max-w-[1280px] mx-auto px-[14px] sm:px-[40px] h-[58px] flex items-center justify-between">
				<NuxtLink
					to="/"
					class="h-[38px] px-[14px] rounded-full text-[#777] text-[13px] flex items-center gap-[5px] cursor-pointer hover:bg-[#F0F1F4] hover:text-[#1d2738] transition-all duration-[350ms]"
					style="font-weight:600">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7" /></svg>
					Indietro
				</NuxtLink>
				<div class="flex items-center gap-[12px]">
					<span class="text-[18px] sm:text-[20px] text-[#1d2738] tracking-tight" style="font-weight:800">{{ summaryTotalPrice || '—' }}</span>
					<span class="text-[11px] text-[#999]" style="font-weight:500">IVA incl.</span>
				</div>
			</div>
		</div>
	</section>
</template>
