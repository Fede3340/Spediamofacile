/**
 * @file useShipmentFormFieldAssist — auto-completamento e suggerimenti campi shipment.
 * Estratto da composables/useShipmentForm.js. Email suggestions, address parsing.
 */
import { computed } from 'vue';
import { buildEmailSuggestion, extractAddressAndNumber } from '~/utils/shipmentFormHelpers';


/** @returns {{getFieldAssist: Function, applyFieldAssist: Function}} */
export const useShipmentFormFieldAssist = ({
	deliveryMode,
	sv,
	applyLocationToSection,
	getSectionAddress,
	getFieldError,
	locationLinkHints,
	normalizeLocationText,
	originCitySuggestions,
	originCapSuggestions,
	destCitySuggestions,
	destCapSuggestions,
}) => {
	const getBestLocationCandidate = (section) => {
		const addr = getSectionAddress(section);
		const cap = String(addr.postal_code || '').trim();
		const cityNorm = normalizeLocationText(addr.city || '');
		const provinceNorm = normalizeLocationText(addr.province || '');
		const cityList = section === 'origin' ? originCitySuggestions.value : destCitySuggestions.value;
		const capList = section === 'origin' ? originCapSuggestions.value : destCapSuggestions.value;
		const hintList = locationLinkHints[section] || [];

		let pool = dedupeLocations([...(capList || []), ...(cityList || []), ...(hintList || [])]);
		if (!pool.length) return null;

		if (cap.length === 5) {
			const capMatches = pool.filter((loc) => String(loc.postal_code || '') === cap);
			if (capMatches.length) pool = capMatches;
		}

		pool.sort((a, b) => {
			const aCity = normalizeLocationText(a.place_name);
			const bCity = normalizeLocationText(b.place_name);
			const aProv = normalizeLocationText(getProvinceLabel(a));
			const bProv = normalizeLocationText(getProvinceLabel(b));
			const aScore =
				(aCity === cityNorm ? 3 : 0) +
				(aProv === provinceNorm ? 2 : 0) +
				(cap && String(a.postal_code || '') === cap ? 2 : 0);
			const bScore =
				(bCity === cityNorm ? 3 : 0) +
				(bProv === provinceNorm ? 2 : 0) +
				(cap && String(b.postal_code || '') === cap ? 2 : 0);
			if (aScore !== bScore) return bScore - aScore;
			return String(a.postal_code || '').localeCompare(String(b.postal_code || ''));
		});

		return pool[0] || null;
	};

	const buildFieldAssist = (section, field) => {
		const error = getFieldError(section, field);
		if (!error) return null;

		const addr = getSectionAddress(section);
		const key = `${section}_${field}`;
		const isDestPudoAddress = section === 'dest' && deliveryMode.value === 'pudo' && ['address', 'address_number', 'city', 'province', 'postal_code'].includes(field);
		if (isDestPudoAddress) return null;

		if (field === 'full_name') {
			const current = String(addr.full_name || '');
			const cleaned = sv.autoCapitalize(current.replace(/\d/g, '').replace(/\s+/g, ' ').trim());
			if (cleaned && cleaned !== current) {
				return {
					label: `Usa "${cleaned}"`,
					apply: () => {
						addr.full_name = cleaned;
						sv.markTouched(key);
						sv.validateNomeCognome(key, cleaned);
					},
				};
			}
		}

		if (field === 'telephone_number') {
			const current = String(addr.telephone_number || '');
			const onlyDigits = current.replace(/\D/g, '').replace(/^39/, '');
			const candidateDigits = onlyDigits.length > 10 ? onlyDigits.slice(0, 10) : onlyDigits;
			if (candidateDigits.length >= 6 && candidateDigits !== onlyDigits) {
				return {
					label: `Correggi numero in ${candidateDigits}`,
					apply: () => {
						addr.telephone_number = candidateDigits;
						sv.markTouched(key);
						sv.validateTelefono(key, candidateDigits);
					},
				};
			}
		}

		if (field === 'email') {
			const current = String(addr.email || '');
			const suggestion = buildEmailSuggestion(current);
			if (suggestion && suggestion !== current.toLowerCase()) {
				return {
					label: `Usa "${suggestion}"`,
					apply: () => {
						addr.email = suggestion;
						sv.markTouched(key);
						sv.validateEmail(key, suggestion);
					},
				};
			}
		}

		if (field === 'address') {
			const parsed = extractAddressAndNumber(addr.address);
			if (parsed && !normalizeSimpleText(addr.address_number)) {
				return {
					label: `Separa civico: ${parsed.street}, ${parsed.number}`,
					apply: () => {
						addr.address = parsed.street;
						addr.address_number = parsed.number;
						sv.markTouched(`${section}_address`);
						sv.markTouched(`${section}_address_number`);
						sv.clearError(`${section}_address`);
						sv.clearError(`${section}_address_number`);
					},
				};
			}
		}

		if (field === 'address_number') {
			const parsed = extractAddressAndNumber(addr.address);
			if (parsed && !normalizeSimpleText(addr.address_number)) {
				return {
					label: `Imposta civico ${parsed.number}`,
					apply: () => {
						addr.address = parsed.street;
						addr.address_number = parsed.number;
						sv.markTouched(`${section}_address`);
						sv.markTouched(`${section}_address_number`);
						sv.clearError(`${section}_address`);
						sv.clearError(`${section}_address_number`);
					},
				};
			}
		}

		if (['city', 'province', 'postal_code'].includes(field)) {
			const candidate = getBestLocationCandidate(section);
			if (!candidate) return null;
			const city = String(candidate.place_name || '').trim();
			const province = getProvinceLabel(candidate);
			const cap = String(candidate.postal_code || '').trim();

			const cityDiff = city && normalizeLocationText(city) !== normalizeLocationText(addr.city || '');
			const provinceDiff = province && normalizeLocationText(province) !== normalizeLocationText(addr.province || '');
			const capDiff = cap && cap !== String(addr.postal_code || '').trim();

			if (cityDiff || provinceDiff || capDiff) {
				const labelParts = [];
				if (cityDiff) labelParts.push(city);
				if (provinceDiff) labelParts.push(province);
				if (capDiff) labelParts.push(cap);

				return {
					label: `Applica correzione: ${labelParts.join(' · ')}`,
					apply: () => {
						applyLocationToSection(section, candidate);
						sv.markTouched(`${section}_city`);
						sv.markTouched(`${section}_province`);
						sv.markTouched(`${section}_postal_code`);
					},
				};
			}
		}

		return null;
	};

	const fieldAssistMap = computed(() => {
		const map = {};
		const fields = ['full_name', 'address', 'address_number', 'city', 'province', 'postal_code', 'telephone_number', 'email'];
		['origin', 'dest'].forEach((section) => {
			fields.forEach((field) => {
				map[`${section}_${field}`] = buildFieldAssist(section, field);
			});
		});
		return map;
	});

	const getFieldAssist = (section, field) => fieldAssistMap.value[`${section}_${field}`] || null;

	const applyFieldAssist = (section, field) => {
		const suggestion = getFieldAssist(section, field);
		if (!suggestion?.apply) return;
		suggestion.apply();
	};

	return {
		getFieldAssist,
		applyFieldAssist,
	};
};
