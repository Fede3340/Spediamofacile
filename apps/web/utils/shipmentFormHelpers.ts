/**
 * @file shipmentFormHelpers — costanti + helpers puri per shipment form.
 * Estratto da composables/useShipmentForm.js. FIELD_ERROR_*, softenErrorMessage,
 * normalizeSimpleText, buildEmailSuggestion, extractAddressAndNumber.
 */

export const FIELD_ERROR_ORDER = [
	'origin_full_name',
	'origin_address',
	'origin_address_number',
	'origin_city',
	'origin_province',
	'origin_postal_code',
	'origin_telephone_number',
	'origin_email',
	'dest_full_name',
	'dest_address',
	'dest_address_number',
	'dest_city',
	'dest_province',
	'dest_postal_code',
	'dest_telephone_number',
	'dest_email',
];

/** @type {Record<string, string>} */
export const FIELD_ERROR_LABELS = {
	origin_full_name: 'Nome e Cognome partenza',
	origin_address: 'Indirizzo partenza',
	origin_address_number: 'Numero civico partenza',
	origin_city: 'Città partenza',
	origin_province: 'Provincia partenza',
	origin_postal_code: 'CAP partenza',
	origin_telephone_number: 'Telefono partenza',
	origin_email: 'Email partenza',
	dest_full_name: 'Nome e Cognome destinazione',
	dest_address: 'Indirizzo destinazione',
	dest_address_number: 'Numero civico destinazione',
	dest_city: 'Città destinazione',
	dest_province: 'Provincia destinazione',
	dest_postal_code: 'CAP destinazione',
	dest_telephone_number: 'Telefono destinazione',
	dest_email: 'Email destinazione',
};

/** @type {Record<string, string>} */
export const FIELD_ERROR_IDS = {
	origin_full_name: 'name',
	origin_address: 'address',
	origin_address_number: 'address_number',
	origin_city: 'city',
	origin_province: 'province',
	origin_postal_code: 'postal_code',
	origin_telephone_number: 'telephone',
	origin_email: 'email',
	dest_full_name: 'dest_name',
	dest_address: 'dest_address',
	dest_address_number: 'dest_address_number',
	dest_city: 'dest_city',
	dest_province: 'dest_province',
	dest_postal_code: 'dest_postal_code',
	dest_telephone_number: 'dest_telephone',
	dest_email: 'dest_email',
};

/** Riformula i messaggi tecnici in linguaggio user-friendly italiano. */
export const softenErrorMessage = (message) => {
	const raw = String(message || '').trim();
	if (!raw) return '';

	const exactMap = {
		'Telefono è obbligatorio': 'Inserisci il numero di telefono per continuare.',
		'Solo numeri consentiti': 'Usa solo cifre nel numero di telefono.',
		'Numero troppo corto': 'Il numero sembra incompleto: aggiungi qualche cifra.',
		'Numero troppo lungo': 'Il numero sembra troppo lungo: controlla le cifre.',
		'CAP è obbligatorio': 'Inserisci il CAP per continuare.',
		'Il CAP deve essere di 5 cifre': 'Il CAP deve contenere 5 cifre.',
		'CAP non valido': 'Controlla il CAP inserito.',
		'Inserisci un indirizzo email valido': 'Controlla il formato email (es. nome@email.it).',
		'Nome e Cognome è obbligatorio': 'Inserisci nome e cognome.',
		'Il nome non può contenere numeri': 'Nel nome evita numeri e simboli.',
		'Provincia è obbligatoria': 'Inserisci la sigla della provincia (es. RM, MI).',
		'Inserisci la sigla (2 lettere)': 'Usa la sigla provincia con 2 lettere (es. RM).',
		'Provincia non valida': 'Controlla la sigla provincia inserita.',
		'Città è obbligatoria': 'Inserisci la città.',
		'Campo obbligatorio': 'Completa questo campo per continuare.',
	};

	if (exactMap[raw]) return exactMap[raw];

	if (/^CAP\s+\d{5}\s+non trovato/i.test(raw)) {
		return `${raw}. Verifica il CAP oppure scegli un suggerimento qui sotto.`;
	}
	if (/non coerente con città\/provincia/i.test(raw)) {
		return `${raw}. Ti proponiamo una correzione veloce.`;
	}
	if (/Per CAP\s+\d{5}\s+la città corretta è/i.test(raw)) {
		return raw.replace(/^Per CAP/i, 'Per questo CAP');
	}

	return raw;
};

export const normalizeSimpleText = (value) => String(value || '').replace(/\s+/g, ' ').trim();

/** @returns {string|null} email con dominio corretto o null se non ci sono fix applicabili */
export const buildEmailSuggestion = (email) => {
	const raw = String(email || '').trim().toLowerCase();
	if (!raw.includes('@')) return null;
	const [local, domain] = raw.split('@');
	if (!local || !domain) return null;

	const commonFixes = {
		'gmial.com': 'gmail.com',
		'gamil.com': 'gmail.com',
		'gnail.com': 'gmail.com',
		'gmai.com': 'gmail.com',
		'hotnail.com': 'hotmail.com',
		'hotmai.com': 'hotmail.com',
		'outlok.com': 'outlook.com',
		'outllok.com': 'outlook.com',
		'icloud.con': 'icloud.com',
		'yaho.com': 'yahoo.com',
	};

	const fixedDomain = commonFixes[domain];
	if (!fixedDomain) return null;
	return `${local}@${fixedDomain}`;
};

/** @returns {{street: string, number: string}|null} */
export const extractAddressAndNumber = (value) => {
	const raw = normalizeSimpleText(value);
	if (!raw) return null;
	const match = raw.match(/^(.*?)[,\s]+(\d[a-z0-9\-/]*)$/i);
	if (!match) return null;
	const street = normalizeSimpleText(match[1]).replace(/[,\s]+$/g, '');
	const number = normalizeSimpleText(match[2]);
	if (!street || !number) return null;
	return { street, number };
};
