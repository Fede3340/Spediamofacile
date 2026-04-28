/**
 * Boundary canonico del quick quote / preventivo rapido.
 *
 * Qui vivono solo helper puri:
 * - shape comparabile del payload first-step
 * - signature stabile usata per dedup/sync sessione
 * - formatter condivisi tra quick quote e autocomplete
 *
 * Nessun side effect UI, nessuna chiamata rete, nessun accesso a route/store.
 */

export const cloneShipmentDetailsForQuote = (details = {}) => ({
	origin_city: String(details.origin_city || ""),
	origin_postal_code: String(details.origin_postal_code || ""),
	origin_country_code: String(details.origin_country_code || "IT").trim().toUpperCase() || "IT",
	origin_country: String(details.origin_country || "Italia").trim() || "Italia",
	destination_city: String(details.destination_city || ""),
	destination_postal_code: String(details.destination_postal_code || ""),
	destination_country_code: String(details.destination_country_code || "IT").trim().toUpperCase() || "IT",
	destination_country: String(details.destination_country || "Italia").trim() || "Italia",
	date: String(details.date || ""),
});

export const clonePackageForQuote = (pack = {}) => ({
	package_type: String(pack?.package_type || ""),
	quantity: Number(pack?.quantity) || 1,
	weight: String(pack?.weight || ""),
	first_size: String(pack?.first_size || ""),
	second_size: String(pack?.second_size || ""),
	third_size: String(pack?.third_size || ""),
});

export const clonePackagesForQuote = (packages = []) => (
	Array.isArray(packages)
		? packages.map((pack) => clonePackageForQuote(pack))
		: []
);

export const buildQuoteComparableSignature = (payload = {}) => JSON.stringify({
	shipment_details: cloneShipmentDetailsForQuote(payload.shipment_details || {}),
	packages: clonePackagesForQuote(payload.packages || []).map((pack) => ({
		package_type: String(pack?.package_type || ""),
		quantity: Number(pack?.quantity) || 0,
		weight: String(pack?.weight || ""),
		first_size: String(pack?.first_size || ""),
		second_size: String(pack?.second_size || ""),
		third_size: String(pack?.third_size || ""),
	})),
});

export const extractSessionComparablePayload = (sessionData = {}) => ({
	shipment_details: sessionData?.shipment_details || {},
	packages: sessionData?.packages || [],
});

export const formatResolvedLocation = (city = "", cap = "") => {
	const trimmedCity = String(city || "").trim();
	const trimmedCap = String(cap || "").trim();
	if (trimmedCity && trimmedCap) return `${trimmedCity} · ${trimmedCap}`;
	return trimmedCity || trimmedCap || "";
};
