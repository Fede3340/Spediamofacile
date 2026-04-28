/**
 * useSmartValidation — validatori form per regole italiane (telefono, CAP, provincia, peso, etc).
 * Pattern "touch-first": `onBlur` segna toccato + prima validazione; `onInput` ri-valida solo se toccato.
 * `getError(key)` ritorna null finché il campo non è stato toccato (UX: no errori prematuri).
 */

// Lista completa delle sigle delle province italiane (usata per la validazione della provincia)
const ITALIAN_PROVINCES = [
	'AG','AL','AN','AO','AP','AQ','AR','AT','AV','BA','BG','BI','BL','BN','BO',
	'BR','BS','BT','BZ','CA','CB','CE','CH','CL','CN','CO','CR','CS','CT','CZ',
	'EN','FC','FE','FG','FI','FM','FR','GE','GO','GR','IM','IS','KR','LC','LE',
	'LI','LO','LT','LU','MB','MC','ME','MI','MN','MO','MS','MT','NA','NO','NU',
	'OG','OR','OT','PA','PC','PD','PE','PG','PI','PN','PO','PR','PT','PU','PV',
	'PZ','RA','RC','RE','RG','RI','RM','RN','RO','SA','SI','SO','SP','SR','SS',
	'SU','SV','TA','TE','TN','TO','TP','TR','TS','TV','UD','VA','VB','VC','VE',
	'VI','VR','VT','VV',
];

export function useSmartValidation() {
	// Oggetto reattivo che contiene gli errori di validazione per ogni campo
	// La chiave e' il nome del campo, il valore e' il messaggio di errore
	const errors = ref({});

	// Oggetto reattivo che tiene traccia di quali campi sono stati "toccati" (interagiti) dall'utente
	// Un campo e' "toccato" quando l'utente ci clicca sopra e poi esce (blur)
	const touched = ref({});

	// Segna un campo come "toccato" - da questo momento in poi mostra gli errori
	const markTouched = (key) => {
		touched.value[key] = true;
	};

	// Controlla se un campo e' stato gia' toccato dall'utente
	const isTouched = (key) => !!touched.value[key];

	// Rimuove l'errore da un campo (usato quando il valore diventa valido)
	const clearError = (key) => {
		delete errors.value[key];
	};

	// Imposta un messaggio di errore per un campo specifico
	const setError = (key, msg) => {
		errors.value[key] = msg;
	};

	// Restituisce il messaggio di errore per un campo, ma SOLO se il campo e' stato toccato
	// (non mostra errori su campi che l'utente non ha ancora interagito)
	const getError = (key) => {
		return touched.value[key] ? (errors.value[key] || null) : null;
	};

	// Controlla se un campo ha un errore E e' stato toccato
	const hasError = (key) => {
		return touched.value[key] && !!errors.value[key];
	};

	// --- REGOLE DI VALIDAZIONE ---

	// Valida un numero di telefono italiano
	// Accetta: numeri con prefisso +39, tra 6 e 10 cifre (senza prefisso)
	const validateTelefono = (key, value) => {
		if (!value || !String(value).trim()) {
			setError(key, 'Telefono è obbligatorio');
			return false;
		}
		// Rimuoviamo spazi, trattini e parentesi per ottenere solo il numero
		const cleaned = String(value).replace(/[\s\-\(\)]/g, '');
		if (!/^\+?\d+$/.test(cleaned)) {
			setError(key, 'Solo numeri consentiti');
			return false;
		}
		// Cellulare italiano: 10 cifre (oppure prefisso +39 + 10 cifre)
		// Rimuoviamo il prefisso internazionale per contare le cifre effettive
		const digits = cleaned.replace(/^\+?39/, '');
		if (digits.length < 6) {
			setError(key, 'Numero troppo corto');
			return false;
		}
		if (digits.length > 10) {
			setError(key, 'Numero troppo lungo');
			return false;
		}
		clearError(key);
		return true;
	};

	// Formatta il numero di telefono rimuovendo tutti i caratteri non numerici
	// (tranne il "+" iniziale per il prefisso internazionale)
	const formatTelefono = (value) => {
		if (!value) return value;
		let cleaned = String(value).replace(/[^\d+]/g, '');
		return cleaned;
	};

	// Valida un Codice di Avviamento Postale (CAP) italiano
	// Deve essere esattamente 5 cifre e nel range valido (00010-98168)
	const validateCAP = (key, value, options = {}) => {
		const countryCode = String(options?.countryCode || 'IT').trim().toUpperCase() || 'IT';
		if (!value || !String(value).trim()) {
			setError(key, 'CAP è obbligatorio');
			return false;
		}
		if (countryCode !== 'IT') {
			const cleanedForeign = String(value).trim().toUpperCase().replace(/[^A-Z0-9-\s]/g, '');
			if (cleanedForeign.length < 2) {
				setError(key, 'Inserisci un CAP valido');
				return false;
			}
			clearError(key);
			return true;
		}
		const cleaned = String(value).replace(/[^0-9]/g, '');
		if (cleaned.length !== 5) {
			setError(key, 'Il CAP deve essere di 5 cifre');
			return false;
		}
		// Verifica che il CAP sia nel range valido per l'Italia (da 00010 a 98168)
		const capNum = parseInt(cleaned, 10);
		if (capNum < 10 || capNum > 98168) {
			setError(key, 'CAP non valido');
			return false;
		}
		clearError(key);
		return true;
	};

	// Filtra l'input del CAP: rimuove caratteri non numerici e limita a 5 cifre
	const filterCAP = (value, options = {}) => {
		const countryCode = String(options?.countryCode || 'IT').trim().toUpperCase() || 'IT';
		if (!value) return value;
		if (countryCode !== 'IT') {
			return String(value).toUpperCase().replace(/[^A-Z0-9-\s]/g, '').slice(0, 12);
		}
		return String(value).replace(/[^0-9]/g, '').slice(0, 5);
	};

	// Valida un indirizzo email con espressione regolare
	// Il campo email e' opzionale: se vuoto, non viene segnalato come errore
	const validateEmail = (key, value) => {
		if (!value || !String(value).trim()) {
			clearError(key);
			return true; // optional
		}
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		if (!emailRegex.test(String(value).trim())) {
			setError(key, 'Inserisci un indirizzo email valido');
			return false;
		}
		clearError(key);
		return true;
	};

	// Valida il peso del pacco: deve essere un numero positivo, massimo 1000 kg
	const validatePeso = (key, value) => {
		if (!value && value !== 0) {
			setError(key, 'Peso è obbligatorio');
			return false;
		}
		const num = Number(String(value).replace(/[^0-9.]/g, ''));
		if (isNaN(num) || num <= 0) {
			setError(key, 'Inserisci un peso positivo');
			return false;
		}
		if (num > 1000) {
			setError(key, 'Peso massimo: 1000 kg');
			return false;
		}
		clearError(key);
		return true;
	};

	// Valida una dimensione del pacco (lunghezza, larghezza, altezza)
	// Deve essere un numero positivo, massimo 300 cm
	// Il parametro "label" e' il nome del campo (es. "Lunghezza") per i messaggi di errore
	const validateDimensione = (key, value, label) => {
		if (!value && value !== 0) {
			setError(key, `${label} è obbligatorio`);
			return false;
		}
		const num = Number(String(value).replace(/[^0-9.]/g, ''));
		if (isNaN(num) || num <= 0) {
			setError(key, 'Inserisci un valore positivo');
			return false;
		}
		if (num > 300) {
			setError(key, 'Dimensione massima: 300 cm');
			return false;
		}
		clearError(key);
		return true;
	};

	// Valida il campo Nome e Cognome: obbligatorio e non puo' contenere numeri
	const validateNomeCognome = (key, value) => {
		if (!value || !String(value).trim()) {
			setError(key, 'Nome e Cognome è obbligatorio');
			return false;
		}
		if (/\d/.test(value)) {
			setError(key, 'Il nome non può contenere numeri');
			return false;
		}
		clearError(key);
		return true;
	};

	// Capitalizza automaticamente la prima lettera di ogni parola
	// Esempio: "mario rossi" diventa "Mario Rossi"
	const autoCapitalize = (value) => {
		if (!value) return value;
		return String(value).replace(/\b\w/g, c => c.toUpperCase());
	};

	// Valida la sigla della provincia italiana (es. "MI" per Milano, "RM" per Roma)
	// Deve essere esattamente 2 lettere maiuscole e deve essere nell'elenco ufficiale
	const validateProvincia = (key, value) => {
		if (!value || !String(value).trim()) {
			setError(key, 'Provincia è obbligatoria');
			return false;
		}
		const upper = String(value).toUpperCase().trim();
		if (!/^[A-Z]{2}$/.test(upper)) {
			setError(key, 'Inserisci la sigla (2 lettere)');
			return false;
		}
		if (!ITALIAN_PROVINCES.includes(upper)) {
			setError(key, 'Provincia non valida');
			return false;
		}
		clearError(key);
		return true;
	};

	// Filtra l'input della provincia: rimuove caratteri non alfabetici,
	// limita a 2 caratteri e converte in maiuscolo
	const filterProvincia = (value) => {
		if (!value) return value;
		return String(value).replace(/[^a-zA-Z]/g, '').slice(0, 2).toUpperCase();
	};

	// Restituisce suggerimenti per la provincia in base a cio' che l'utente ha digitato
	// Esempio: se l'utente digita "M", suggerisce MI, MN, MO, MS, MT (massimo 5)
	const getProvinceSuggestions = (input) => {
		if (!input || String(input).length < 1) return [];
		const upper = String(input).toUpperCase();
		return ITALIAN_PROVINCES.filter(p => p.startsWith(upper)).slice(0, 5);
	};

	// Gestore dell'evento "blur" (quando l'utente esce dal campo):
	// segna il campo come toccato e lancia la validazione
	const onBlur = (key, validateFn) => {
		markTouched(key);
		validateFn();
	};

	// Gestore dell'evento "input" (quando l'utente digita):
	// ri-valida il campo SOLO se e' gia' stato toccato (per non mostrare errori troppo presto)
	const onInput = (key, validateFn) => {
		if (isTouched(key)) {
			validateFn();
		}
	};

	// Restituisce le classi CSS per mostrare un bordo rosso e sfondo rosso chiaro
	// quando un campo ha un errore di validazione (stile visivo per l'utente)
	const errorClass = (key, baseClass = '') => {
		if (hasError(key)) {
			return `${baseClass} !border-red-400 !bg-red-50/30`;
		}
		return baseClass;
	};

	// Resetta completamente tutti gli errori e lo stato "toccato" di tutti i campi
	// Utile quando si vuole ricominciare da capo (es. dopo un invio riuscito del form)
	const resetAll = () => {
		errors.value = {};
		touched.value = {};
	};

	return {
		errors,
		touched,
		markTouched,
		isTouched,
		clearError,
		setError,
		getError,
		hasError,
		validateTelefono,
		formatTelefono,
		validateCAP,
		filterCAP,
		validateEmail,
		validatePeso,
		validateDimensione,
		validateNomeCognome,
		autoCapitalize,
		validateProvincia,
		filterProvincia,
		getProvinceSuggestions,
		onBlur,
		onInput,
		errorClass,
		resetAll,
		ITALIAN_PROVINCES,
	};
}
