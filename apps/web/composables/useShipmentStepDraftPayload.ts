/**
 * @file useShipmentStepDraftPayload — Composable useShipmentStepDraftPayload.
 */
// Boundary canonico del draft spedizione.
//
// Qui vivono solo helper puri per normalizzare il payload condiviso tra:
// - persistenza step 2
// - creazione ordine
// - bootstrap pagamento
//
// Nessuna dipendenza da route, rendering o side effect UI.

export const normalizePostalCodeForStep = (addressData = {}) => {
	const country = String(addressData?.country || 'Italia')
		.trim()
		.toLowerCase();
	const rawPostalCode = String(addressData?.postal_code || '');

	if (country === 'italia') {
		return rawPostalCode.replace(/[^0-9]/g, '') || '00000';
	}

	return (
		rawPostalCode
			.toUpperCase()
			.replace(/[^A-Z0-9-\s]/g, '')
			.trim() || 'N/D'
	);
};

export const toStepAddressPayload = (addressData = {}) => ({
	type: addressData.type || 'Partenza',
	name: (addressData.full_name || 'N/D').trim(),
	additional_information: addressData.additional_information || '',
	address: (addressData.address || 'N/D').trim(),
	number_type: 'Numero Civico',
	address_number: (addressData.address_number || 'SNC').trim(),
	intercom_code: addressData.intercom_code || '',
	country: addressData.country || 'Italia',
	city: (addressData.city || 'N/D').trim(),
	postal_code: normalizePostalCodeForStep(addressData),
	province: (addressData.province || 'N/D').trim(),
	telephone_number: String(addressData.telephone_number || '0000000000').trim(),
	email: addressData.email || '',
});

export const DEFAULT_PICKUP_TIME_SLOT = '09:00-18:00';

export const normalizePickupRequestDate = (value = '') => {
	const rawValue = String(value || '').trim();
	if (!rawValue) return '';

	if (/^\d{4}-\d{2}-\d{2}$/.test(rawValue)) {
		return rawValue;
	}

	const localMatch = rawValue.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
	if (localMatch) {
		const [, day, month, year] = localMatch;
		return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
	}

	const parsed = new Date(rawValue);
	if (!Number.isNaN(parsed.getTime())) {
		return parsed.toISOString().slice(0, 10);
	}

	return rawValue;
};

export const buildPickupRequestPayload = ({ shipmentFlowStore, services } = {}) => {
	const rawPickupDate = String(
		services?.value?.date
		|| shipmentFlowStore?.pickupDate
		|| shipmentFlowStore?.serviceData?.pickup_request?.date
		|| '',
	).trim();

	const normalizedPickupDate = normalizePickupRequestDate(rawPickupDate);
	const pickupTimeSlot =
		String(
			services?.value?.time
			|| shipmentFlowStore?.serviceData?.pickup_request?.time_slot
			|| DEFAULT_PICKUP_TIME_SLOT,
		).trim() || DEFAULT_PICKUP_TIME_SLOT;

	return {
		enabled: Boolean(normalizedPickupDate),
		date: normalizedPickupDate,
		time_slot: pickupTimeSlot,
		notes: String(shipmentFlowStore?.serviceData?.pickup_request?.notes || '').trim(),
	};
};

export const buildSecondStepPayload = ({
	shipmentFlowStore,
	services,
	smsEmailNotification,
	originAddress,
	destinationAddress,
	includeAddresses = false,
	payload = null,
} = {}) => {
	if (payload) return payload;

	const selectedServiceKeys = new Set(
		String(shipmentFlowStore?.servicesArray || [])
			.split(',')
			.map((item) => String(item || '').trim().toLowerCase())
			.filter(Boolean),
	);

	const rawServiceData = shipmentFlowStore?.serviceData && typeof shipmentFlowStore?.serviceData === 'object'
		? shipmentFlowStore?.serviceData
		: {};
	const pickupRequest = buildPickupRequestPayload({
		shipmentFlowStore,
		services,
	});
	const normalizedServiceData = {
		pickup_request: pickupRequest,
		sms_email_notification: Boolean(smsEmailNotification.value),
		delivery_mode: shipmentFlowStore?.deliveryMode,
		...(shipmentFlowStore?.deliveryMode === 'pudo' && shipmentFlowStore?.selectedPudo
			? { pudo: shipmentFlowStore?.selectedPudo }
			: {}),
		...(rawServiceData.requires_manual_quote ? { requires_manual_quote: true } : {}),
		...(rawServiceData.telefono_notifica
			? { telefono_notifica: String(rawServiceData.telefono_notifica).trim() }
			: {}),
		...(selectedServiceKeys.has('contrassegno') && rawServiceData.contrassegno
			? { contrassegno: { ...rawServiceData.contrassegno } }
			: {}),
		...(selectedServiceKeys.has('assicurazione') && rawServiceData.assicurazione
			? { assicurazione: { ...rawServiceData.assicurazione } }
			: {}),
		...(selectedServiceKeys.has('sponda idraulica') || selectedServiceKeys.has('sponda_idraulica')
			? { sponda_idraulica: { ...(rawServiceData.sponda_idraulica || {}) } }
			: {}),
	};

	return {
		services: {
			service_type: shipmentFlowStore?.servicesArray.join(', '),
			date: services.value.date || '',
			time: pickupRequest.time_slot,
			serviceData: normalizedServiceData,
			sms_email_notification: Boolean(smsEmailNotification.value),
		},
		content_description: shipmentFlowStore?.contentDescription || '',
		pickup_date: services.value.date || '',
		sms_email_notification: Boolean(smsEmailNotification.value),
		origin_address: includeAddresses ? toStepAddressPayload(originAddress.value) : null,
		destination_address: includeAddresses ? toStepAddressPayload(destinationAddress.value) : null,
		delivery_mode: shipmentFlowStore?.deliveryMode,
		selected_pudo: shipmentFlowStore?.deliveryMode === 'pudo' ? shipmentFlowStore?.selectedPudo : null,
		client_submission_id: shipmentFlowStore?.pendingShipment?.client_submission_id || undefined,
	};
};

export const normalizeShipmentPayloadForComparison = (payload = {}) => {
	if (!payload || typeof payload !== 'object') return '';
	const clone = JSON.parse(JSON.stringify(payload));
	delete clone.client_submission_id;
	return JSON.stringify(clone);
};
