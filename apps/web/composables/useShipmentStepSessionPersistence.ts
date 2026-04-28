/**
 * @file useShipmentStepSessionPersistence — Composable useShipmentStepSessionPersistence.
 */
import { buildSecondStepPayload } from "~/composables/useShipmentStepDraftPayload";

export const useShipmentStepSessionPersistence = ({
	sanctumClient,
	refresh,
	session,
	submitError,
	shipmentFlowStore,
	services,
	smsEmailNotification,
	originAddress,
	destinationAddress,
}) => {
	const persistShipmentFlowState = async ({ includeAddresses = false, payload = null } = {}) => {
		try {
			await sanctumClient("/api/session/second-step", {
				method: "POST",
				body: buildSecondStepPayload({
					shipmentFlowStore,
					services,
					smsEmailNotification,
					originAddress,
					destinationAddress,
					includeAddresses,
					payload,
				}),
			});
			await refresh().catch(() => session.value);
			return true;
		} catch (error) {
			submitError.value = error?.data?.message || "Errore nel salvataggio del flusso spedizione. Riprova.";
			return false;
		}
	};

	return {
		persistShipmentFlowState,
	};
};
