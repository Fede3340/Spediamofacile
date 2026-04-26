import { buildSecondStepPayload, toStepAddressPayload } from "~/composables/useShipmentStepDraftPayload";

export const useShipmentStepSubmit = ({
	destinationAddress,
	editablePackages,
	editCartId,
	focusFirstFormError,
	focusPickupDateSection,
	formRef,
	navigateToRiepilogo = true,
	normalizeLocationText,
	originAddress,
	persistSecondStep,
	routeConsistencyState,
	smsEmailNotification,
	services,
	submitError,
	uiFeedback,
	shipmentFlowStore,
	validateForm,
}) => {
	const isSubmitting = ref(false);

	const continueToCart = async () => {
		if (isSubmitting.value) return;
		isSubmitting.value = true;
		submitError.value = null;

		try {
			if (!(await validateForm())) {
				nextTick(() => {
					if (services.value.date) {
						focusFirstFormError();
						return;
					}
					focusPickupDateSection();
				});
				return;
			}

			if (!formRef.value || !formRef.value.checkValidity()) {
				formRef.value?.reportValidity();
				return;
			}

			const packages = editablePackages.value;
			if (!packages.length) {
				submitError.value = "Nessun collo disponibile. Torna al preventivo rapido.";
				return;
			}

			if (shipmentFlowStore?.deliveryMode === "pudo" && !shipmentFlowStore?.selectedPudo) {
				submitError.value = "Seleziona un Punto BRT per la consegna prima di procedere.";
				return;
			}

			if (shipmentFlowStore?.deliveryMode === "pudo" && shipmentFlowStore?.selectedPudo) {
				const recipientNameNorm = normalizeLocationText(destinationAddress.value.full_name || "");
				const pudoNameNorm = normalizeLocationText(shipmentFlowStore?.selectedPudo?.name || "");
				if (recipientNameNorm && pudoNameNorm && recipientNameNorm === pudoNameNorm) {
					submitError.value = "Nel campo Nome e Cognome inserisci il destinatario (persona), non il nome del Punto BRT.";
					nextTick(() => {
						document.getElementById("dest_name")?.focus();
					});
					return;
				}
			}

			if (routeConsistencyState.value.blocking) {
				submitError.value = routeConsistencyState.value.message;
				nextTick(() => {
					const focusId = shipmentFlowStore?.deliveryMode === "pudo" ? "dest_name" : "dest_address";
					document.getElementById(focusId)?.focus();
				});
				return;
			}

			const payload = {
				...buildSecondStepPayload({
					shipmentFlowStore,
					services,
					smsEmailNotification,
					originAddress: { value: toStepAddressPayload(originAddress.value) },
					destinationAddress: { value: toStepAddressPayload(destinationAddress.value) },
					includeAddresses: true,
				}),
				packages,
			};

			if (typeof persistSecondStep === "function") {
				const persisted = await persistSecondStep(payload);
				if (persisted === false) {
					return;
				}
			}

			shipmentFlowStore.pendingShipment = payload;
			shipmentFlowStore.originAddressData = { ...originAddress.value };
			shipmentFlowStore.destinationAddressData = { ...destinationAddress.value };
			shipmentFlowStore.pickupDate = services.value.date || "";
			shipmentFlowStore.smsEmailNotification = smsEmailNotification.value;

			if (editCartId) {
				shipmentFlowStore.editingCartItemId = editCartId;
			}

			uiFeedback.success("Dati salvati", "Apertura del riepilogo...", { timeout: 1800 });

			if (navigateToRiepilogo) {
				await navigateTo("/riepilogo", { replace: true });
			}
		} finally {
			isSubmitting.value = false;
		}
	};

	return {
		continueToCart,
		isSubmitting,
	};
};
