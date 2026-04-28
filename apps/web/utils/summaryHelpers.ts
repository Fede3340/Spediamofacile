/**
 * @file summaryHelpers — helpers puri per useShipmentStepSummary.
 * Pulizia testo, parse/format prezzi, calcolo totali pacchi. Niente ref/state.
 */
import { normalizeLocationText } from '~/utils/location';

/** Normalizza testo display: trim + rimuovi placeholder (n/d, —, null, undefined). */
export const cleanDisplayText = (value) => {
	const raw = String(value ?? '').trim();
	if (!raw) return '';

	const normalized = typeof normalizeLocationText === 'function'
		? normalizeLocationText(raw)
		: raw.replace(/\s+/g, ' ').trim();
	const lowered = normalized.toLowerCase();

	if (
		!normalized
		|| lowered === 'n/d'
		|| lowered === 'nd'
		|| lowered === '—'
		|| lowered === '-'
		|| lowered === 'null'
		|| lowered === 'undefined'
	) {
		return '';
	}

	return normalized;
};

/** Primo valore "non placeholder" tra candidates. */
export const firstMeaningfulValue = (...candidates) => {
	for (const candidate of candidates) {
		const normalized = cleanDisplayText(candidate);
		if (normalized) return normalized;
	}
	return '';
};

/**
 * Parse prezzo da stringa "11,90 €", "11.90", numero, ecc.
 * Supporta sia notazione italiana (1.234,56) sia inglese (1,234.56).
 */
export const parsePriceAmount = (value) => {
	if (value === null || value === undefined) return null;
	if (typeof value === 'number') {
		return Number.isFinite(value) ? value : null;
	}

	const raw = String(value).trim();
	if (!raw) return null;

	let normalized = raw.replace(/[€\s]/g, '');
	if (normalized.includes(',') && normalized.includes('.')) {
		if (normalized.lastIndexOf(',') > normalized.lastIndexOf('.')) {
			normalized = normalized.replace(/\./g, '').replace(',', '.');
		} else {
			normalized = normalized.replace(/,/g, '');
		}
	} else if (normalized.includes(',')) {
		normalized = normalized.replace(',', '.');
	}

	const parsed = Number(normalized);
	return Number.isFinite(parsed) ? parsed : null;
};

/** Format "11,90 €" italiano con simbolo. Restituisce "0,00 €" se input non valido. */
export const formatPriceAmount = (amount) => {
	const n = Number(amount);
	if (!Number.isFinite(n)) return `0,00 €`;
	return n.toFixed(2).replace('.', ',') + ' €';
};

/** Primo amount > 0 tra candidates, altrimenti il primo valido finito, altrimenti 0. */
export const pickBestPriceAmount = (candidates) => {
	const valid = candidates.filter((value) => value !== null && Number.isFinite(value));
	const positive = valid.find((value) => value > 0);
	if (positive !== undefined) return positive;
	return valid.length ? valid[0] : 0;
};

/** Riporta amount in EUR se arriva in cents (>1000 = cents). */
export const normalizePackagePrice = (rawAmount) => {
	const amount = Number(rawAmount) || 0;
	if (!amount) return 0;
	return amount > 1000 ? amount / 100 : amount;
};

/** Riga prezzo per singolo pacco: priorita single_price > single_priceOrig > max(weight, volume) * qty. */
export const getPackageLineAmount = (pack) => {
	const single = parsePriceAmount(pack?.single_price);
	if (single !== null && single > 0) return normalizePackagePrice(single);

	const singleOrig = parsePriceAmount(pack?.single_priceOrig);
	if (singleOrig !== null && singleOrig > 0) return normalizePackagePrice(singleOrig);

	const weightPrice = parsePriceAmount(pack?.weight_price) || 0;
	const volumePrice = parsePriceAmount(pack?.volume_price) || 0;
	const base = Math.max(weightPrice, volumePrice);
	if (base <= 0) return 0;

	const qty = Number(pack?.quantity) || 1;
	return base * qty;
};

/** Totale array pacchi (somma getPackageLineAmount), null se array vuoto/non valido o totale 0. */
export const getPackagesTotal = (packages) => {
	if (!Array.isArray(packages) || !packages.length) return null;
	const total = packages.reduce((sum, pack) => sum + getPackageLineAmount(pack), 0);
	return total > 0 ? total : null;
};
