<!-- app.vue — entry root. Ripristina sessione preventivo da /api/session nello
     shipmentFlowStore (watch gestisce arrivo async post-mount) + SEO globali. -->
<script setup>
import {
	buildPendingShipmentFromSession,
	extractShipmentServicesArray,
	getShipmentFlowStepNumber,
	resolveShipmentFlowState,
	toStepAddressState,
} from '~/utils/shipment';

const shipmentFlowStore = useShipmentFlowStore();
const route = useRoute();

// === SEO TECNICA GLOBALE ===
// useHead + useSeoMeta forniscono i meta che devono valere su OGNI pagina.
// I meta specifici (title/description per route) vengono sovrascritti dalle
// singole pagine senza toccare questi default globali.
useHead({
	htmlAttrs: { lang: 'it' },
	meta: [
		{ name: 'theme-color', content: '#095866' },
		{ name: 'viewport', content: 'width=device-width, initial-scale=1, viewport-fit=cover' },
	],
});

// Canonical dinamico basato sull'URL effettivamente richiesto.
// useRequestURL() funziona sia in SSR che client e restituisce origin+pathname reali.
const requestUrl = useRequestURL();
const runtimeConfig = useRuntimeConfig();
const siteOrigin = String(runtimeConfig.public?.siteUrl || requestUrl.origin || 'https://spediamofacile.it').replace(/\/+$/, '');
const canonicalHref = computed(() => `${siteOrigin}${route.path === '/' ? '' : route.path}`);

// OG image di default: le singole pagine possono sovrascrivere con useSeoMeta({ ogImage }).
// Il fallback qui evita che preview "nudi" vengano mostrati quando una pagina
// dimentica di specificare l'immagine (es. 404, preview dinamici).
const defaultOgImage = `${siteOrigin}/og/default.png`;
useSeoMeta({
	ogSiteName: 'SpediamoFacile',
	ogLocale: 'it_IT',
	ogType: 'website',
	twitterCard: 'summary_large_image',
	ogUrl: () => canonicalHref.value,
	ogImage: defaultOgImage,
	ogImageWidth: 1200,
	ogImageHeight: 630,
	ogImageType: 'image/png',
	ogImageAlt: 'SpediamoFacile — Spedizioni BRT al miglior prezzo',
	twitterImage: defaultOgImage,
	twitterImageAlt: 'SpediamoFacile — Spedizioni BRT al miglior prezzo',
});

useHead({
	link: [
		{ rel: 'canonical', href: () => canonicalHref.value, key: 'canonical-global' },
	],
});

// Schema.org globali (Organization + WebSite con SearchAction).
useSiteSchema();
const QUOTE_SESSION_ROUTE_PREFIXES = ['/carrello'];
const quoteTransitionLock = useState('shipment-flow-quote-transition-lock', () => false);
const restoredQuoteSession = useState('shipment-flow-quote-restored', () => false);

// Root restore: usato solo come fallback su /carrello.
// Home e funnel hanno una propria orchestrazione di bootstrap e ripristinare qui
// lo shipmentFlowStore dopo il mount crea collisioni tardive con i loro watcher
// e componenti client-side.
const shouldRestoreQuoteSession = computed(() =>
	QUOTE_SESSION_ROUTE_PREFIXES.some((prefix) => route.path.startsWith(prefix))
);
const appToaster = computed(() => ({
	position: 'bottom-right',
	duration: QUOTE_SESSION_ROUTE_PREFIXES.some((prefix) => route.path.startsWith(prefix)) ? 3800 : 4500,
	max: 4,
}));
const { session, status } = useSession();
const restoredQuoteRoute = useState('shipment-flow-quote-restored-route', () => '');

const hasLocalQuoteState = () => {
	const details = shipmentFlowStore.shipmentDetails || {};
	return Boolean(shipmentFlowStore.pendingShipment)
		|| Boolean(shipmentFlowStore.pickupDate)
		|| Boolean(shipmentFlowStore.contentDescription?.trim?.())
		|| (Array.isArray(shipmentFlowStore.packages) && shipmentFlowStore.packages.length > 0)
		|| ['origin_city', 'origin_postal_code', 'destination_city', 'destination_postal_code', 'date']
			.some((key) => String(details?.[key] || '').trim());
};

const restoreSession = (data) => {
	if (!data?.shipment_details && !data?.packages?.length) return;
	const flowState = resolveShipmentFlowState(data);

	const currentDetails = shipmentFlowStore.shipmentDetails || {};
	const remoteDetails = data?.shipment_details || {};
	const pickDetail = (key, fallback = '') => {
		const localValue = String(currentDetails?.[key] || '').trim();
		if (localValue) return localValue;
		return String(remoteDetails?.[key] || fallback).trim();
	};
	const mergedDetails = {
		origin_city: pickDetail('origin_city'),
		origin_postal_code: pickDetail('origin_postal_code'),
		origin_country_code: pickDetail('origin_country_code', 'IT') || 'IT',
		origin_country: pickDetail('origin_country', 'Italia') || 'Italia',
		destination_city: pickDetail('destination_city'),
		destination_postal_code: pickDetail('destination_postal_code'),
		destination_country_code: pickDetail('destination_country_code', 'IT') || 'IT',
		destination_country: pickDetail('destination_country', 'Italia') || 'Italia',
		date: pickDetail('date'),
	};

	Object.assign(shipmentFlowStore.shipmentDetails, mergedDetails);

	if ((!Array.isArray(shipmentFlowStore.packages) || shipmentFlowStore.packages.length === 0) && Array.isArray(data?.packages) && data.packages.length > 0) {
		shipmentFlowStore.packages = [...data.packages];
	}

	if (!shipmentFlowStore.totalPrice && Number(data?.total_price || 0) > 0) {
		shipmentFlowStore.totalPrice = Number(data.total_price);
	}

	if (!shipmentFlowStore.isQuoteStarted && flowState.quote_ready) {
		shipmentFlowStore.isQuoteStarted = true;
	}

	if (!shipmentFlowStore.servicesArray.length) {
		const services = extractShipmentServicesArray(data);
		if (services.length) {
			shipmentFlowStore.servicesArray = [...services];
		}
	}

	if (!shipmentFlowStore.contentDescription && String(data?.content_description || '').trim()) {
		shipmentFlowStore.contentDescription = String(data.content_description).trim();
	}

	if (!shipmentFlowStore.pickupDate && String(data?.pickup_date || data?.services?.date || '').trim()) {
		shipmentFlowStore.pickupDate = String(data.pickup_date || data?.services?.date || '').trim();
	}

	const remoteSmsNotification = Boolean(
		data?.sms_email_notification
		?? data?.services?.sms_email_notification
		?? data?.service_data?.sms_email_notification
	);

	if (!shipmentFlowStore.smsEmailNotification && remoteSmsNotification) {
		shipmentFlowStore.smsEmailNotification = true;
	}

	if (!Object.keys(shipmentFlowStore.serviceData || {}).length) {
		const remoteServiceData = data?.service_data || data?.services?.serviceData || null;
		if (remoteServiceData && typeof remoteServiceData === 'object') {
			shipmentFlowStore.serviceData = { ...remoteServiceData };
		}
	}

	if (!shipmentFlowStore.originAddressData && data?.origin_address) {
		shipmentFlowStore.originAddressData = toStepAddressState(data.origin_address);
	}

	if (!shipmentFlowStore.destinationAddressData && data?.destination_address) {
		shipmentFlowStore.destinationAddressData = toStepAddressState(data.destination_address);
	}

	if (shipmentFlowStore.deliveryMode === 'home' && String(data?.delivery_mode || '').trim() === 'pudo') {
		shipmentFlowStore.deliveryMode = 'pudo';
	}

	if (!shipmentFlowStore.selectedPudo && data?.selected_pudo) {
		shipmentFlowStore.selectedPudo = data.selected_pudo;
	}

	if (!shipmentFlowStore.pendingShipment) {
		const pendingShipment = buildPendingShipmentFromSession(data);
		if (pendingShipment) {
			shipmentFlowStore.pendingShipment = pendingShipment;
		}
	}

	if (!shipmentFlowStore.stepNumber) {
		shipmentFlowStore.stepNumber = getShipmentFlowStepNumber(flowState);
	}
};

const restoreSessionIfNeeded = (data) => {
	if (!shouldRestoreQuoteSession.value) return;
	if (restoredQuoteSession.value) return;
	if (hasLocalQuoteState()) return;
	if (quoteTransitionLock.value) return;
	if (status.value === 'pending') return;
	restoreSession(data);
	restoredQuoteSession.value = true;
	restoredQuoteRoute.value = route.fullPath;
};

// Ripristina solo quando arrivano davvero dati sessione utili; evitiamo di
// riapplicare il restore nel primo frame o durante un cambio route in corso,
// che erano una fonte concreta di flicker/remount percepito nel funnel.
watch(
	() => session.value?.data,
	(data) => {
		if (!data) return;
		restoreSessionIfNeeded(data);
	},
	{ flush: 'post' },
);

watch(
	() => route.fullPath,
	() => {
		if (!shouldRestoreQuoteSession.value) {
			restoredQuoteSession.value = false;
			restoredQuoteRoute.value = '';
			return;
		}

		if (restoredQuoteRoute.value !== route.fullPath) {
			restoredQuoteSession.value = false;
		}

		if (session.value?.data) {
			restoreSessionIfNeeded(session.value.data);
		}
	},
	{ flush: 'post' },
);

onMounted(() => {
	if (session.value?.data) {
		nextTick(() => restoreSessionIfNeeded(session.value.data));
	}
});

// Failsafe: evita lock scroll globale se una route preview lascia classi/stili sul body.
if (import.meta.client) {
	const unlockGlobalScroll = () => {
		const isPreviewRoute = route.path.startsWith('/preview/home-hero');
		if (isPreviewRoute) return;
		document.documentElement.classList.remove('hero-preview-body');
		document.body.classList.remove('hero-preview-body');
		document.documentElement.style.overflow = '';
		document.documentElement.style.overflowY = '';
		document.body.style.overflow = '';
		document.body.style.overflowY = '';
	};

	onMounted(unlockGlobalScroll);
	watch(() => route.path, unlockGlobalScroll);
}
</script>

<template>
	<UApp :toaster="appToaster">
		<NuxtLayout>
			<NuxtPage />
		</NuxtLayout>
		<ShipmentFlowAdminGateModal />
		<!-- Singleton globale del dialog di conferma pilotato da useConfirmDialog() -->
		<SfConfirmDialog />
	</UApp>
</template>
