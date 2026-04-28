/**
 * useFunnel — composables consolidati del funnel spedizione.
 * ----------------------------------------------------------------------------
 * Consumer principale: pages/la-tua-spedizione/[step].vue
 * Altri consumer:
 *   - composables/useAuth.js (usa useFunnelAnalytics)
 *   - composables/useShipmentStepPageOrchestration.js (usa useFunnelAnalytics)
 *
 * Le 4 funzioni export pubbliche (`useFunnelAnalytics`, `useFunnelNavigation`,
 * `useFunnelState`, `useFunnelValidation`) mantengono API e comportamento
 * identici alla versione pre-consolidamento (2026-04-20). Sono auto-importate
 * da Nuxt perché vivono in `composables/`.
 *
 * NOTA T3.6.5: la fix payment-route-context (syncPaymentRouteContext /
 * ensurePaymentStageReady / openPaymentAccordion) resta intenzionalmente
 * inline in [step].vue e NON viene spostata qui, per evitare regressioni
 * sull'interazione più critica dell'app.
 * ----------------------------------------------------------------------------
 */

import { computed, nextTick, ref } from 'vue';

// ============================================================================
// SEZIONE 1 — Analytics (ex useFunnelAnalytics)
// ============================================================================

/**
 * Analytics funnel — Plausible unico sink (GA4+Sentry archiviati 2026-04-20).
 *
 * Privacy: MAI trackare PII (email, nome, indirizzi, phone, CF/VAT, stripe_account_id).
 * Importi in cents (integer), mai EUR decimali. order_id ok (riferimento tecnico).
 * Failure mode: wrappato in try/catch — un analytics rotto non blocca il checkout.
 * Sampling 100% (eventi business rari, ~5-10/sessione).
 *
 * @typedef {Record<string, string|number|boolean|null|undefined>} FunnelProps
 * @typedef {'email'|'google'|'apple'|'facebook'} AuthMethod
 * @typedef {'nazionale'|'internazionale'|'pudo'|string} QuoteType
 */

/** Composable analytics funnel verso Plausible. */
export const useFunnelAnalytics = () => {
	/**
	 * Rimuove PII accidentali prima di inviare ai sink.
	 * Whitelist approach: manteniamo solo chiavi "safe".
	 */
	const sanitizeProps = (props) => {
		if (!props) return undefined
		const PII_KEYS = new Set([
			'email', 'email_confirmation', 'password', 'password_confirmation',
			'name', 'surname', 'full_name', 'fiscal_code', 'vat', 'phone',
			'address', 'street', 'postal_code', 'city', 'country',
			'iban', 'card_number', 'stripe_account_id', 'token', 'access_token',
		])
		const clean = {}
		for (const [key, value] of Object.entries(props)) {
			if (PII_KEYS.has(key.toLowerCase())) continue
			if (value === undefined || value === null) continue
			// Non lasciare passare oggetti/array — Plausible accetta solo scalari.
			if (typeof value === 'object') continue
			clean[key] = value
		}
		return clean
	}

	const sendToPlausible = (event, props) => {
		if (typeof window === 'undefined' || typeof window.plausible !== 'function') return
		try {
			window.plausible(event, props ? { props } : undefined)
		} catch {
			// no-op: analytics non deve rompere la pagina.
		}
	}

	// GA4 archiviato — vedi _archive/frontend-simplification-2026-04-20/npm-packages/ga4-duplicato/
	// Sentry archiviato — vedi _archive/frontend-simplification-2026-04-20/npm-packages/sentry-frontend/

	const track = (event, rawProps) => {
		if (typeof window === 'undefined') return
		const props = sanitizeProps(rawProps)
		sendToPlausible(event, props)
		if (import.meta.dev && window.localStorage?.getItem('sf_debug_shipment') === '1') {
			// eslint-disable-next-line no-console
			console.debug(`[Funnel] ${event}`, props || {})
		}
	}

	// ---------------------------------------------------------------------------
	// Eventi funnel preventivo (step 1 → 5)
	// ---------------------------------------------------------------------------

	const trackPreventivoStart = (quoteType) => {
		track('preventivo_start', { quote_type: String(quoteType || 'unknown') })
	}

	const trackServicesSelected = (services) => {
		track('services_selected', {
			count: Array.isArray(services) ? services.length : 0,
			// Nomi servizi OK (non sono PII): sms_email, assicurazione, contrassegno, etc.
			services: Array.isArray(services) ? services.join(',').slice(0, 120) : '',
		})
	}

	const trackAddressesFilled = () => {
		track('addresses_filled')
	}

	const trackPaymentInit = (amountCents) => {
		track('payment_init', { amount_cents: Math.round(Number(amountCents) || 0) })
	}

	const trackPaymentSuccess = (orderId, amountCents) => {
		track('payment_success', {
			order_id: String(orderId || ''),
			amount_cents: Math.round(Number(amountCents) || 0),
		})
	}

	const trackPaymentFail = (reason) => {
		track('payment_fail', { reason: String(reason || 'unknown').slice(0, 80) })
	}

	// ---------------------------------------------------------------------------
	// Eventi auth
	// ---------------------------------------------------------------------------

	const trackAuthLogin = (method) => {
		track('auth_login', { method })
	}

	const trackAuthRegister = (method) => {
		track('auth_register', { method })
	}

	return {
		track,
		trackPreventivoStart,
		trackServicesSelected,
		trackAddressesFilled,
		trackPaymentInit,
		trackPaymentSuccess,
		trackPaymentFail,
		trackAuthLogin,
		trackAuthRegister,
	}
}

// ============================================================================
// SEZIONE 2 — Navigation (ex useFunnelNavigation)
// ============================================================================

/**
 * useFunnelNavigation
 * ----------------------------------------------------------------------------
 * Low-risk navigation helpers for the shipment funnel.
 * Provides scroll/focus utilities and accordion-panel transition hooks.
 *
 * IMPORTANT: fix T3.6.5 (syncPaymentRouteContext / ensurePaymentStageReady /
 * openPaymentAccordion) lives in [step].vue and is NOT relocated here to avoid
 * regressions on the single most business-critical interaction in the app.
 *
 * This composable is a safe-to-extract surface:
 *   - resolveStageElement / scrollAccordionStageIntoView
 *   - accordion panel enter/leave transition hooks
 *   - focus-dismiss helpers
 * ----------------------------------------------------------------------------
 */

/** Composable helper navigazione/transizioni funnel spedizione. */
export function useFunnelNavigation() {
	const resolveStageElement = (stageRef) => {
		const rawRef = stageRef?.value;
		if (!rawRef) return null;
		return rawRef?.$el instanceof HTMLElement ? rawRef.$el : rawRef;
	};

	const scrollAccordionStageIntoView = (stageRef, focusSelector) => {
		nextTick(() => {
			const stageElement = resolveStageElement(stageRef);
			if (!stageElement) return;

			// Aspetta la fine dell'animazione accordion (440ms) prima di scrollare,
			// così rect.top è stabile e il browser non sovrappone scroll implicito
			// causato dal cambio layout. Senza questo delay, scroll smooth veniva
			// "deviato" e l'utente atterrava a metà dell'accordion espanso
			// (es. sezione Destinazione invece del trigger Indirizzi).
			window.setTimeout(() => {
				const triggerInStage = stageElement.querySelector('[data-accordion-trigger]');
				const triggerInDoc = stageElement.getAttribute('data-accordion-id')
					? document.querySelector(`[data-accordion-trigger="${stageElement.getAttribute('data-accordion-id')}"]`)
					: null;
				const scrollTarget = triggerInStage || triggerInDoc || stageElement;
				const rect = scrollTarget.getBoundingClientRect();
				const offset = 100;
				const absoluteTop = rect.top + window.pageYOffset - offset;
				window.scrollTo({ top: Math.max(0, absoluteTop), behavior: 'smooth' });

				if (focusSelector) {
					const focusTarget = stageElement.querySelector(focusSelector);
					focusTarget?.focus?.({ preventScroll: true });
				}
			}, 480);
		});
	};

	const focusPickupDateSection = (pickupDateSectionRef) => {
		nextTick(() => {
			const sectionRoot =
				pickupDateSectionRef.value?.$el instanceof HTMLElement
					? pickupDateSectionRef.value.$el
					: pickupDateSectionRef.value;
			const firstDateButton =
				sectionRoot?.querySelector?.('[data-pickup-day]') ||
				document.querySelector('[data-pickup-day], [id^="date-"]');

			sectionRoot?.scrollIntoView?.({ block: 'center', behavior: 'smooth' });
			firstDateButton?.focus?.({ preventScroll: true });
		});
	};

	const dismissActiveFieldFocusImmediately = () => {
		if (document.activeElement instanceof HTMLElement) {
			document.activeElement.blur();
		}
	};

	const dismissActiveFieldFocus = async () => {
		if (!(document.activeElement instanceof HTMLElement)) return;
		document.activeElement.blur();
		await nextTick();
		await new Promise((resolve) => setTimeout(resolve, 24));
	};

	/* -- Accordion-panel Transition hooks ------------------------------------ */

	const clearAccordionPanelTransitionStyles = (el) => {
		el.style.height = '';
		el.style.opacity = '';
		el.style.transform = '';
		el.style.overflow = '';
		el.style.transition = '';
		el.style.willChange = '';
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
		const target = el;
		target.style.height = '0px';
		target.style.opacity = '0';
		target.style.transform = 'translateY(10px)';
		target.style.overflow = 'hidden';
		target.style.willChange = 'height, opacity, transform';
	};

	const onAccordionPanelEnter = (el, done) => {
		const target = el;
		// UX Polish — apertura organica stile "easeOutExpo": la card si distende
		// con decelerazione molto morbida, senza overshoot. Durata allungata per
		// percepire il rilascio, non per rallentare l'interazione.
		target.style.transition =
			'height 440ms cubic-bezier(0.16,1,0.3,1), opacity 320ms cubic-bezier(0.22,1,0.36,1), transform 420ms cubic-bezier(0.16,1,0.3,1)';
		void target.offsetHeight;
		bindAccordionPanelTransitionEnd(target, done);

		requestAnimationFrame(() => {
			target.style.height = `${target.scrollHeight}px`;
			target.style.opacity = '1';
			target.style.transform = 'translateY(0)';
		});
	};

	const onAccordionPanelAfterEnter = (el) => {
		clearAccordionPanelTransitionStyles(el);
	};

	const onAccordionPanelBeforeLeave = (el) => {
		const target = el;
		target.style.height = `${target.scrollHeight}px`;
		target.style.opacity = '1';
		target.style.transform = 'translateY(0)';
		target.style.overflow = 'hidden';
		target.style.willChange = 'height, opacity, transform';
	};

	const onAccordionPanelLeave = (el, done) => {
		const target = el;
		target.style.height = `${target.scrollHeight}px`;
		// Chiusura: ease-in leggero per dare direzionalità "che sparisce".
		target.style.transition =
			'height 260ms cubic-bezier(0.4,0,1,1), opacity 180ms cubic-bezier(0.4,0,1,1), transform 220ms cubic-bezier(0.4,0,1,1)';
		void target.offsetHeight;
		bindAccordionPanelTransitionEnd(target, done);

		requestAnimationFrame(() => {
			target.style.height = '0px';
			target.style.opacity = '0';
			target.style.transform = 'translateY(-8px)';
		});
	};

	const onAccordionPanelAfterLeave = (el) => {
		clearAccordionPanelTransitionStyles(el);
	};

	return {
		resolveStageElement,
		scrollAccordionStageIntoView,
		focusPickupDateSection,
		dismissActiveFieldFocusImmediately,
		dismissActiveFieldFocus,
		onAccordionPanelBeforeEnter,
		onAccordionPanelEnter,
		onAccordionPanelAfterEnter,
		onAccordionPanelBeforeLeave,
		onAccordionPanelLeave,
		onAccordionPanelAfterLeave,
	};
}

// ============================================================================
// SEZIONE 3 — State (ex useFunnelState)
// ============================================================================

/**
 * useFunnelState
 * ----------------------------------------------------------------------------
 * Aggregates locally-owned refs used across the shipment funnel page
 * (/la-tua-spedizione/[step].vue) that are NOT already provided by existing
 * composables (useShipmentStepServices, useShipmentStepAddresses, useCheckout,
 * useShipmentStepFlow, ...).
 *
 * This composable is intentionally thin: it only extracts reactive state and
 * pure template helpers. Payment route context and accordion orchestration
 * remain in the page to preserve fix T3.6.5 (syncPaymentRouteContext /
 * ensurePaymentStageReady / openPaymentAccordion) exactly as shipped.
 * ----------------------------------------------------------------------------
 */

const THROTTLE_ERROR_PATTERN = /numero massimo di tentativi|hai superato|riprova tra|attendi(?: ancora)?|\d+\s*(second|minut)|too many (attempts|requests)|tentativi troppo frequenti|rate limit|429/i;
const THROTTLE_STATUS_PATTERN = /\b429\b|too[\s_-]*many[\s_-]*requests|throttled|rate[\s_-]*limited/i;

const createFunnelErrorHelpers = () => {
	const normalizeFunnelErrorMessage = (message) => {
		if (!message) return '';
		if (typeof message === 'string') return message.trim();
		if (Array.isArray(message)) {
			return message
				.map((entry) => normalizeFunnelErrorMessage(entry))
				.filter(Boolean)
				.join(' ');
		}
		if (typeof message === 'object') {
			const anyMsg = message;
			return [
				anyMsg.message,
				anyMsg.error,
				anyMsg.errors,
				anyMsg.statusMessage,
				anyMsg.data?.message,
				anyMsg.data?.error,
				anyMsg.data?.errors,
				anyMsg.response?._data?.message,
				anyMsg.response?._data?.error,
				anyMsg.response?._data?.errors,
				anyMsg.response?.data?.message,
				anyMsg.response?.data?.error,
				anyMsg.response?.data?.errors,
			]
				.map((entry) => normalizeFunnelErrorMessage(entry))
				.filter(Boolean)
				.join(' ');
		}

		return String(message).trim();
	};

	const resolveFunnelErrorMessage = (error, fallbackMessage = '') => (
		normalizeFunnelErrorMessage(error) || normalizeFunnelErrorMessage(fallbackMessage)
	);

	const normalizeFunnelErrorStatus = (message) => {
		if (!message || typeof message !== 'object') return '';
		const anyMsg = message;

		return [
			anyMsg.status,
			anyMsg.statusCode,
			anyMsg.code,
			anyMsg.statusText,
			anyMsg.data?.status,
			anyMsg.data?.statusCode,
			anyMsg.data?.code,
			anyMsg.data?.statusText,
			anyMsg.response?.status,
			anyMsg.response?.statusCode,
			anyMsg.response?.statusText,
			anyMsg.response?._data?.status,
			anyMsg.response?._data?.statusCode,
			anyMsg.response?._data?.code,
			anyMsg.response?._data?.statusText,
			anyMsg.response?.data?.status,
			anyMsg.response?.data?.statusCode,
			anyMsg.response?.data?.code,
			anyMsg.response?.data?.statusText,
		]
			.map((entry) => String(entry ?? '').trim())
			.find(Boolean) || '';
	};

	const isThrottleLikeFunnelError = (message) => {
		const normalizedMessage = normalizeFunnelErrorMessage(message);
		if (normalizedMessage && THROTTLE_ERROR_PATTERN.test(normalizedMessage)) return true;

		const normalizedStatus = normalizeFunnelErrorStatus(message);
		return THROTTLE_STATUS_PATTERN.test(normalizedStatus);
	};

	const stripFunnelThrottleMessage = (message) => {
		const normalized = normalizeFunnelErrorMessage(message);
		if (isThrottleLikeFunnelError(message)) return '';
		return normalized;
	};

	return {
		normalizeFunnelErrorMessage,
		resolveFunnelErrorMessage,
		normalizeFunnelErrorStatus,
		isThrottleLikeFunnelError,
		stripFunnelThrottleMessage,
	};
};

/** Composable aggregatore stato locale pagina funnel. */
export function useFunnelState() {
	const errors = {
		dateError: ref(null),
		submitError: ref(null),
		contentError: ref(null),
		paymentBootstrapError: ref(''),
		packagesError: ref(''),
	};

	const templateRefs = {
		formRef: ref(null),
		stepsRef: ref(null),
		pickupDateSectionRef: ref(null),
		packagesStageRef: ref(null),
		servicesStageRef: ref(null),
		addressStageRef: ref(null),
		paymentStageRef: ref(null),
	};

	const ui = {
		paymentBootstrapPending: ref(false),
		paymentSummaryExpanded: ref(false),
		isProceedingToPayment: ref(false),
	};

	const iconFilters = {
		SERVICE_ICON_FILTER_IDLE:
			'brightness(0) saturate(100%) invert(23%) sepia(23%) saturate(1100%) hue-rotate(151deg) brightness(92%) contrast(88%)',
		SERVICE_ICON_FILTER_ACTIVE: 'brightness(0) invert(1)',
	};

	const helpers = createFunnelErrorHelpers();

	const visibleSubmitError = computed(() => helpers.stripFunnelThrottleMessage(errors.submitError.value));
	const visiblePaymentBootstrapError = computed(() =>
		helpers.stripFunnelThrottleMessage(errors.paymentBootstrapError.value),
	);

	// Auto-clear throttle-like messages so we never surface them. Consumers
	// must still pass paymentError (owned by useCheckout) to keep parity.
	watch(
		() => errors.submitError.value,
		(value) => {
			if (!value || !helpers.isThrottleLikeFunnelError(value)) return;
			errors.submitError.value = null;
		},
		{ flush: 'sync' },
	);

	watch(
		() => errors.paymentBootstrapError.value,
		(value) => {
			if (!value || !helpers.isThrottleLikeFunnelError(value)) return;
			errors.paymentBootstrapError.value = '';
		},
		{ flush: 'sync' },
	);

	return {
		errors,
		templateRefs,
		ui,
		iconFilters,
		helpers,
		visibleSubmitError,
		visiblePaymentBootstrapError,
	};
}

// useFunnelValidation + PACKAGE_VALIDATION_* estratti in composables/useFunnelValidation.js
