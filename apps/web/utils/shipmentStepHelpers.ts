/**
 * Helper puri usati dalla pagina /la-tua-spedizione/[step].vue.
 * Estratti per ridurre LOC del page orchestrator senza toccare UI/reactivity.
 *
 * NOTA: zero dipendenze Vue/Pinia/Nuxt. Pure functions testabili in isolamento.
 */

type Sentinel = '' | string;

const SENTINEL_VALUES = new Set(['n/d', 'nd', '-', '—', 'null', 'undefined']);

/**
 * Normalizza una stringa di summary pagamento: trim, collapse whitespace,
 * scarta sentinel ("n/d", "—", "null"…). Ritorna stringa vuota se non valido.
 */
export function cleanPaymentSummaryText(value: unknown): string {
	const normalized = String(value ?? '').replace(/\s+/g, ' ').trim();
	if (!normalized) return '';
	return SENTINEL_VALUES.has(normalized.toLowerCase()) ? '' : normalized;
}

/**
 * Formatta una data ordine esistente in formato it-IT (gg/mm/aaaa).
 * Ritorna la stringa raw normalizzata se la data non è parsabile.
 */
export function formatExistingOrderDate(value: unknown): string {
	const raw = cleanPaymentSummaryText(value);
	if (!raw) return '';

	const date = new Date(raw);
	if (!Number.isNaN(date.getTime())) {
		return new Intl.DateTimeFormat('it-IT', {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
		}).format(date);
	}
	return raw;
}

export interface PaymentAddress {
	full_name: string;
	name: string;
	address: string;
	address_number: string;
	postal_code: string;
	city: string;
	province: string;
	country: string;
	telephone_number?: string;
	email?: string;
	additional_information?: string;
}

/**
 * Address vuoto usato come fallback durante hydration SSR (evita
 * mismatch markup quando il summary non è ancora pronto).
 */
export function buildEmptyPaymentAddress(): PaymentAddress {
	return {
		full_name: '',
		name: '',
		address: '',
		address_number: '',
		postal_code: '',
		city: '',
		province: '',
		country: '',
	};
}

/**
 * Normalizza un address proveniente da un Order esistente: tutti i campi
 * passano per cleanPaymentSummaryText, country fallback "Italia".
 */
export function normalizeExistingOrderAddress(address: Partial<PaymentAddress> = {}): PaymentAddress {
	return {
		full_name: cleanPaymentSummaryText((address as any).full_name || (address as any).name),
		name: cleanPaymentSummaryText((address as any).name || (address as any).full_name),
		address: cleanPaymentSummaryText((address as any).address),
		address_number: cleanPaymentSummaryText((address as any).address_number),
		postal_code: cleanPaymentSummaryText((address as any).postal_code),
		city: cleanPaymentSummaryText((address as any).city),
		province: cleanPaymentSummaryText((address as any).province),
		country: cleanPaymentSummaryText((address as any).country || 'Italia'),
		telephone_number: cleanPaymentSummaryText((address as any).telephone_number),
		email: cleanPaymentSummaryText((address as any).email),
		additional_information: cleanPaymentSummaryText((address as any).additional_information),
	};
}

/**
 * Quantità di un collo "ordine esistente": prende quantity o pivot.quantity,
 * minimo 1 (un pacco c'è sempre).
 */
export function getExistingOrderPackageQuantity(pack: any): number {
	return Math.max(1, Number(pack?.quantity ?? pack?.pivot?.quantity) || 1);
}

/**
 * Tipo di un collo (Pacco/Pallet/Valigia). Default "Pacco" se vuoto.
 */
export function getExistingOrderPackageType(pack: any): string {
	return cleanPaymentSummaryText(pack?.package_type) || 'Pacco';
}

/**
 * Dimensioni di un collo come "AxBxC cm". Stringa vuota se manca un lato.
 */
export function getExistingOrderPackageDimensions(pack: any): string {
	const side1 = Number(pack?.first_size ?? pack?.length);
	const side2 = Number(pack?.second_size ?? pack?.width);
	const side3 = Number(pack?.third_size ?? pack?.height);
	return [side1, side2, side3].every((side) => Number.isFinite(side) && side > 0)
		? `${side1}x${side2}x${side3} cm`
		: '';
}

/**
 * Estrae il messaggio di errore da un errore $fetch / Sanctum, con fallback.
 * Pattern: response._data.message → data.message → message → fallback.
 */
export function resolveApiError(err: any, fallback: string): string {
	return err?.response?._data?.message || err?.data?.message || err?.message || fallback;
}
