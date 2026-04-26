import { computed, watch } from "vue";

export const useShipmentStepPageState = ({
	destinationAddress,
	editCartId,
	ensurePackagesIdentity,
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
	shipmentFlowStore,
}) => {
	const route = useRoute();
	const currentStep = computed(() => Number(route.params.step));
	const quoteTransitionLock = useState("shipment-flow-quote-transition-lock", () => false);
	const parsePersistedServices = (serviceType) =>
		String(serviceType || "")
			.split(",")
			.map((service) => service.trim())
			.filter(Boolean);

	const hydratePersistedStepState = () => {
		const sessionData = session.value?.data;
		if (!sessionData) return;

		const persistedPackages = Array.isArray(sessionData.packages)
			? sessionData.packages.map((pack) => ({ ...pack }))
			: [];
		if (!shipmentFlowStore?.packages?.length && persistedPackages.length) {
			shipmentFlowStore.packages = persistedPackages;
			ensurePackagesIdentity?.();
		}

		const persistedServices = parsePersistedServices(sessionData.services?.service_type);
		if (!shipmentFlowStore?.servicesArray.length && persistedServices.length) {
			shipmentFlowStore.servicesArray = [...persistedServices];
		}

		const persistedContent = String(sessionData.content_description || "").trim();
		if (!String(shipmentFlowStore?.contentDescription || "").trim() && persistedContent) {
			shipmentFlowStore.contentDescription = persistedContent;
		}

		const persistedPickupDate = String(sessionData.pickup_date || sessionData.services?.date || "").trim();
		if (!String(shipmentFlowStore?.pickupDate || "").trim() && persistedPickupDate) {
			shipmentFlowStore.pickupDate = persistedPickupDate;
		}
		if (!String(services.value?.date || "").trim() && persistedPickupDate) {
			services.value.date = persistedPickupDate;
		}

		const persistedServiceType = String(sessionData.services?.service_type || "").trim();
		if (!String(services.value?.service_type || "").trim() && persistedServiceType) {
			services.value.service_type = persistedServiceType;
		}

		const persistedPickupTime = String(
			sessionData.services?.time ||
				sessionData.service_data?.pickup_request?.time_slot ||
				sessionData.services?.serviceData?.pickup_request?.time_slot ||
				"",
		).trim();
		if (!String(services.value?.time || "").trim() && persistedPickupTime) {
			services.value.time = persistedPickupTime;
		}

		const persistedSmsNotification = Boolean(sessionData.sms_email_notification ?? sessionData.services?.sms_email_notification ?? false);
		if (!smsEmailNotification.value && persistedSmsNotification) {
			smsEmailNotification.value = true;
		}

		const persistedServiceData = sessionData.service_data || sessionData.services?.serviceData || {};
		if (persistedServiceData && typeof persistedServiceData === "object" && !Object.keys(shipmentFlowStore?.serviceData || {}).length) {
			shipmentFlowStore.serviceData = { ...persistedServiceData };
		}

		const persistedDeliveryMode = String(sessionData.delivery_mode || "").trim();
		if (persistedDeliveryMode && shipmentFlowStore?.deliveryMode !== persistedDeliveryMode) {
			shipmentFlowStore.deliveryMode = persistedDeliveryMode;
		}

		if (!shipmentFlowStore?.selectedPudo && sessionData.selected_pudo) {
			shipmentFlowStore.selectedPudo = sessionData.selected_pudo;
		}
	};

	const hasPersistedServiceSelection = computed(() => {
		const serviceType = String(session.value?.data?.services?.service_type || "").trim();
		const notificationsEnabled = Boolean(
			session.value?.data?.sms_email_notification ?? session.value?.data?.services?.sms_email_notification ?? false,
		);
		return Boolean(serviceType) || notificationsEnabled;
	});

	const showInitialStepLoading = computed(() => loadingEditData.value);

	watch(
		() => [currentStep.value, status.value, shipmentFlowStore?.editingCartItemId, hasPersistedServiceSelection.value],
		([step, sessionStatus, editingCartItemId, persistedSelection]) => {
			if (step !== 2) return;
			if (sessionStatus === "pending") return;
			if (editingCartItemId) return;
			if (persistedSelection) return;
			if (!shipmentFlowStore?.servicesArray.length && !smsEmailNotification.value) return;

			resetServicesState();
		},
		{ immediate: true },
	);

	watch(
		() => [currentStep.value, status.value, session.value?.data],
		([step, sessionStatus]) => {
			if (step !== 2) return;
			if (sessionStatus === "pending") return;
			hydratePersistedStepState();
		},
		{ immediate: true, deep: true },
	);

	const initOnMounted = () => {
		const hasSessionSnapshot =
			Boolean(session.value?.data?.shipment_details) ||
			(Array.isArray(session.value?.data?.packages) && session.value.data.packages.length > 0);
		const hasLocalSnapshot = Boolean(shipmentFlowStore?.pendingShipment) || (Array.isArray(shipmentFlowStore?.packages) && shipmentFlowStore?.packages.length > 0);

		if (status.value === "idle" && !quoteTransitionLock.value && !hasSessionSnapshot && !hasLocalSnapshot) {
			refresh().catch(() => {});
		}

		hydratePersistedStepState();

		if (editCartId && isAuthenticated.value) {
			loadCartItemForEdit({ originAddress, destinationAddress, services, showAddressFields });
		} else if (editCartId && !isAuthenticated.value) {
			loadingEditData.value = false;
		}
	};

	return {
		currentStep,
		hasPersistedServiceSelection,
		initOnMounted,
		showInitialStepLoading,
	};
};
