/**
 * Utility condivise per normalizzazione, matching e gestione località italiane.
 *
 * Le località italiane hanno: place_name (città), postal_code (CAP),
 * province/province_name (sigla provincia).
 *
 * Sezioni:
 *   1. Normalizzazione (case + accent insensitive)
 *   2. Deduplicazione (location key composito)
 *   3. Suggestion BRT (label, sanitize input)
 *   4. Validazione coerenza city/province
 *   5. Lista province italiane per dropdown
 */

export type LocationRecord = {
	place_name?: string
	postal_code?: string
	province?: string
	province_name?: string
	country_name?: string
	[key: string]: unknown
}

// ─── 1. Normalizzazione ───────────────────────────────────────────

/**
 * Normalizza testo località per confronti case/accent-insensitive.
 * Rimuove accenti, converte in lowercase, collassa spazi multipli.
 */
export const normalizeLocationText = (value: unknown = ""): string =>
	String(value)
		.toLowerCase()
		.normalize("NFD")
		.replace(/[\u0300-\u036F]/g, "")
		.replace(/\s+/g, " ")
		.trim();

/**
 * Restituisce la sigla provincia in uppercase da un oggetto località.
 * Supporta sia `province` che `province_name` come campo sorgente.
 */
export const getProvinceLabel = (location: LocationRecord | null | undefined): string =>
	String(location?.province ?? location?.province_name ?? "")
		.toUpperCase()
		.trim();

// ─── 2. Deduplicazione ────────────────────────────────────────────

/**
 * Genera una chiave univoca per una località (CAP|città|provincia).
 * Usata per deduplicazione e confronti.
 */
export const locationKey = (location: LocationRecord | null | undefined): string => [
	String(location?.postal_code || "").trim(),
	normalizeLocationText(location?.place_name),
	getProvinceLabel(location),
].join("|");

/**
 * Rimuove località duplicate da un array, basandosi su locationKey.
 * Ignora entries senza place_name o postal_code.
 */
export const dedupeLocations = (locations: unknown[] = []): LocationRecord[] => {
	const map = new Map<string, LocationRecord>();
	for (const location of locations) {
		const candidate = location && typeof location === "object" ? location as LocationRecord : null;
		if (!candidate?.place_name || !candidate?.postal_code) continue;
		const key = locationKey(candidate);
		if (!map.has(key)) map.set(key, candidate);
	}
	return Array.from(map.values());
};

// ─── 3. Suggestion BRT (sanitize + format) ────────────────────────

/**
 * Pulizia carattere-by-carattere del campo "Nome e Cognome" digitato a mano.
 * Rimuove cifre + caratteri non alfabetici/spazio/apostrofo, poi applica
 * `autoCapitalize` esterno (Title Case con eccezioni "di"/"de"/"da" passato dal caller).
 */
export const sanitizeFullName = (value: unknown, autoCapitalize?: (value: string) => string): string => {
	const cleaned = String(value || '')
		.replace(/\d/g, '')
		.replace(/[^\p{L}'’`.\-\s]/gu, ' ')
		.replace(/\s+/g, ' ')
		.trim();
	return typeof autoCapitalize === 'function' ? autoCapitalize(cleaned) : cleaned;
};

/** Etichetta suggestion city: "Milano (MI) - 20121" o "Milano - 20121" se no provincia. */
export const formatCitySuggestionLabel = (location: LocationRecord): string => {
	const province = getProvinceLabel(location);
	if (province) return `${location.place_name} (${province}) - ${location.postal_code}`;
	return `${location.place_name} - ${location.postal_code}`;
};

/** Etichetta suggestion cap: "20121 - Milano (MI)" o "20121 - Milano". */
export const formatCapSuggestionLabel = (location: LocationRecord): string => {
	const province = getProvinceLabel(location);
	if (province) return `${location.postal_code} - ${location.place_name} (${province})`;
	return `${location.postal_code} - ${location.place_name}`;
};

// ─── 4. Validazione coerenza ──────────────────────────────────────

/**
 * Verifica che `location` abbia city/province coerenti con quelle digitate.
 * Confronto normalizzato (case + accenti). City/province vuote = nessun vincolo.
 */
export const isLocationCoherent = (location: LocationRecord, city: unknown, province: unknown): boolean => {
	const cityNorm = normalizeLocationText(city || '');
	const provinceNorm = normalizeLocationText(province || '');
	const locCityNorm = normalizeLocationText(location?.place_name || '');
	const locProvinceNorm = normalizeLocationText(getProvinceLabel(location) || '');

	if (cityNorm && locCityNorm !== cityNorm) return false;
	if (provinceNorm && locProvinceNorm !== provinceNorm) return false;
	return true;
};

/**
 * Estrae lista province uniche (sortita) da un insieme di locations,
 * deduplicandole prima per evitare doppi (place_name + postal_code univoco).
 */
export const extractUniqueProvinces = (locations: unknown[], max = 20): string[] => {
	const provinces = [...new Set(
		dedupeLocations(locations)
			.map((loc) => getProvinceLabel(loc))
			.filter(Boolean),
	)].sort();
	return provinces.slice(0, max);
};

// ─── 5. Lista province italiane (formato "SIGLA - Nome") ──────────

export const provinceList = [
	"AG - Agrigento", "AL - Alessandria", "AN - Ancona", "AO - Aosta",
	"AP - Ascoli Piceno", "AQ - L'Aquila", "AR - Arezzo", "AT - Asti",
	"AV - Avellino", "BA - Bari", "BG - Bergamo", "BI - Biella",
	"BL - Belluno", "BN - Benevento", "BO - Bologna", "BR - Brindisi",
	"BS - Brescia", "BT - Barletta-Andria-Trani", "BZ - Bolzano/Bozen",
	"CA - Cagliari", "CB - Campobasso", "CE - Caserta", "CH - Chieti",
	"CI - Sulcis Iglesiente", "CL - Caltanissetta", "CN - Cuneo",
	"CO - Como", "CR - Cremona", "CS - Cosenza", "CT - Catania",
	"CZ - Catanzaro", "EN - Enna", "FC - Forlì-Cesena", "FE - Ferrara",
	"FG - Foggia", "FI - Firenze", "FM - Fermo", "FR - Frosinone",
	"GE - Genova", "GO - Gorizia", "GR - Grosseto", "IM - Imperia",
	"IS - Isernia", "KR - Crotone", "LC - Lecco", "LE - Lecce",
	"LI - Livorno", "LO - Lodi", "LT - Latina", "LU - Lucca",
	"MB - Monza e Brianza", "MC - Macerata", "ME - Messina", "MI - Milano",
	"MN - Mantova", "MO - Modena", "MS - Massa-Carrara", "MT - Matera",
	"NA - Napoli", "NO - Novara", "NU - Nuoro", "OR - Oristano",
	"PA - Palermo", "PC - Piacenza", "PD - Padova", "PE - Pescara",
	"PG - Perugia", "PI - Pisa", "PN - Pordenone", "PO - Prato",
	"PR - Parma", "PT - Pistoia", "PU - Pesaro e Urbino", "PV - Pavia",
	"PZ - Potenza", "RA - Ravenna", "RC - Reggio Calabria",
	"RE - Reggio Emilia", "RG - Ragusa", "RI - Rieti", "RM - Roma",
	"RN - Rimini", "RO - Rovigo", "SA - Salerno", "SI - Siena",
	"SO - Sondrio", "SP - La Spezia", "SR - Siracusa", "SS - Sassari",
	"SV - Savona", "TA - Taranto", "TE - Teramo", "TN - Trento",
	"TO - Torino", "TP - Trapani", "TR - Terni", "TS - Trieste",
	"TV - Treviso", "UD - Udine", "VA - Varese",
	"VB - Verbano-Cusio-Ossola", "VC - Vercelli", "VE - Venezia",
	"VI - Vicenza", "VR - Verona", "VT - Viterbo", "VV - Vibo Valentia",
	"VS - Medio Campidano",
];
