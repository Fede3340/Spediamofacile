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

import { nextTick, type Ref } from 'vue';

export interface FunnelNavigationReturn {
	resolveStageElement: (stageRef: Ref<any> | null | undefined) => HTMLElement | null;
	scrollAccordionStageIntoView: (stageRef: Ref<any>, focusSelector?: string) => void;
	focusPickupDateSection: (pickupDateSectionRef: Ref<any>) => void;
	dismissActiveFieldFocusImmediately: () => void;
	dismissActiveFieldFocus: () => Promise<void>;
	onAccordionPanelBeforeEnter: (el: Element) => void;
	onAccordionPanelEnter: (el: Element, done: () => void) => void;
	onAccordionPanelAfterEnter: (el: Element) => void;
	onAccordionPanelBeforeLeave: (el: Element) => void;
	onAccordionPanelLeave: (el: Element, done: () => void) => void;
	onAccordionPanelAfterLeave: (el: Element) => void;
}

export function useFunnelNavigation(): FunnelNavigationReturn {
	const resolveStageElement = (stageRef: Ref<any> | null | undefined): HTMLElement | null => {
		const rawRef = stageRef?.value;
		if (!rawRef) return null;
		return rawRef?.$el instanceof HTMLElement ? rawRef.$el : rawRef;
	};

	const scrollAccordionStageIntoView = (stageRef: Ref<any>, focusSelector?: string) => {
		nextTick(() => {
			const stageElement = resolveStageElement(stageRef);
			if (!stageElement) return;

			// Aspetta la fine dell'animazione accordion (440ms) prima di scrollare,
			// così rect.top è stabile e il browser non sovrappone scroll implicito
			// causato dal cambio layout. Senza questo delay, scroll smooth veniva
			// "deviato" e l'utente atterrava a metà dell'accordion espanso
			// (es. sezione Destinazione invece del trigger Indirizzi).
			window.setTimeout(() => {
				const triggerInStage = stageElement.querySelector('[data-accordion-trigger]') as HTMLElement | null;
				const triggerInDoc = stageElement.getAttribute('data-accordion-id')
					? document.querySelector(`[data-accordion-trigger="${stageElement.getAttribute('data-accordion-id')}"]`) as HTMLElement | null
					: null;
				const scrollTarget = triggerInStage || triggerInDoc || stageElement;
				const rect = scrollTarget.getBoundingClientRect();
				const offset = 100;
				const absoluteTop = rect.top + window.pageYOffset - offset;
				window.scrollTo({ top: Math.max(0, absoluteTop), behavior: 'smooth' });

				if (focusSelector) {
					const focusTarget = stageElement.querySelector(focusSelector) as HTMLElement | null;
					focusTarget?.focus?.({ preventScroll: true });
				}
			}, 480);
		});
	};

	const focusPickupDateSection = (pickupDateSectionRef: Ref<any>) => {
		nextTick(() => {
			const sectionRoot =
				pickupDateSectionRef.value?.$el instanceof HTMLElement
					? pickupDateSectionRef.value.$el
					: pickupDateSectionRef.value;
			const firstDateButton =
				sectionRoot?.querySelector?.('[data-pickup-day]') ||
				document.querySelector('[data-pickup-day], [id^="date-"]');

			sectionRoot?.scrollIntoView?.({ block: 'center', behavior: 'smooth' });
			(firstDateButton as HTMLElement | null)?.focus?.({ preventScroll: true });
		});
	};

	const dismissActiveFieldFocusImmediately = () => {
		if (document.activeElement instanceof HTMLElement) {
			document.activeElement.blur();
		}
	};

	const dismissActiveFieldFocus = async (): Promise<void> => {
		if (!(document.activeElement instanceof HTMLElement)) return;
		document.activeElement.blur();
		await nextTick();
		await new Promise((resolve) => setTimeout(resolve, 24));
	};

	/* -- Accordion-panel Transition hooks ------------------------------------ */

	const clearAccordionPanelTransitionStyles = (el: HTMLElement) => {
		el.style.height = '';
		el.style.opacity = '';
		el.style.transform = '';
		el.style.overflow = '';
		el.style.transition = '';
		el.style.willChange = '';
	};

	const bindAccordionPanelTransitionEnd = (el: HTMLElement, done: () => void) => {
		const onTransitionEnd = (event: TransitionEvent) => {
			if (event.target !== el || event.propertyName !== 'height') return;
			el.removeEventListener('transitionend', onTransitionEnd);
			done();
		};

		el.addEventListener('transitionend', onTransitionEnd);
	};

	const onAccordionPanelBeforeEnter = (el: Element) => {
		const target = el as HTMLElement;
		target.style.height = '0px';
		target.style.opacity = '0';
		target.style.transform = 'translateY(10px)';
		target.style.overflow = 'hidden';
		target.style.willChange = 'height, opacity, transform';
	};

	const onAccordionPanelEnter = (el: Element, done: () => void) => {
		const target = el as HTMLElement;
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

	const onAccordionPanelAfterEnter = (el: Element) => {
		clearAccordionPanelTransitionStyles(el as HTMLElement);
	};

	const onAccordionPanelBeforeLeave = (el: Element) => {
		const target = el as HTMLElement;
		target.style.height = `${target.scrollHeight}px`;
		target.style.opacity = '1';
		target.style.transform = 'translateY(0)';
		target.style.overflow = 'hidden';
		target.style.willChange = 'height, opacity, transform';
	};

	const onAccordionPanelLeave = (el: Element, done: () => void) => {
		const target = el as HTMLElement;
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

	const onAccordionPanelAfterLeave = (el: Element) => {
		clearAccordionPanelTransitionStyles(el as HTMLElement);
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
