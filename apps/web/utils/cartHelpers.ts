/**
 * cartHelpers — funzioni pure di supporto al cart/checkout.
 *
 * Estratto da composables/useCart.js (split atomico Pinia 2026-04-26).
 * Tutte le funzioni qui dentro sono prive di stato e SSR-safe.
 *
 * @typedef {object} DisplayGroupEntry
 * @property {'group'} type
 * @property {number} groupIndex
 * @property {object} group
 * @property {Array<object>} items
 * @property {number} totalCents
 * @property {string} color
 *
 * @typedef {object} DisplaySingleEntry
 * @property {'single'} type
 * @property {number} groupIndex
 * @property {object} item
 *
 * @typedef {DisplayGroupEntry|DisplaySingleEntry} DisplayEntry
 */

const NBSP = '\u00A0'
const EURO = '\u20AC'

/** Converte centesimi in euro, sempre >= 0. Per leggere campi `_cents`. */
export function centsToEuro(value) {
	const cents = Number(value)
	return Number.isFinite(cents) ? Math.max(0, cents) / 100 : 0
}

/** Formatter "0,00 €" (NBSP prima del simbolo). Accetta euro-float. */
export function formatEuroAmount(num) {
	const n = Number(num)
	if (!Number.isFinite(n)) return `0,00${NBSP}${EURO}`
	return n.toFixed(2).replace('.', ',') + NBSP + EURO
}

/** Prezzo unitario (totale / quantita'). */
export function unitPrice(item) {
	const total = Number(item?.single_price) || 0
	const qty = Math.max(1, Number(item?.quantity) || 1)
	return total / qty
}

/** Mapping package_type -> icona nel carrello. */
export function getPackageIcon(item) {
	const type = String(item?.package_type || '').toLowerCase()
	if (type.includes('pallet')) return '/img/quote/first-step/pallet.png'
	if (type.includes('busta')) return '/img/quote/first-step/envelope.png'
	if (type.includes('valigia')) return '/img/quote/first-step/suitcase.png'
	return '/img/quote/first-step/pack.png'
}

/** Palette colori per highlight gruppi indirizzi. */
export const CART_GROUP_COLORS = Object.freeze([
	'#095866',
	'#E44203',
	'#6B21A8',
	'#0369A1',
	'#B45309',
])

/** Costanti CSS bottoni quantita' carrello (riusati in PudoSelector). */
export const QUANTITY_BUTTON_CLASS =
	'w-[32px] h-[32px] tablet:w-[24px] tablet:h-[24px] flex items-center justify-center rounded-full bg-[#EEF2F3] text-[#252B42] text-[0.875rem] tablet:text-[0.75rem] font-bold hover:bg-[#DDE5E7] disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-[background-color,transform] duration-200 active:scale-[0.97]'

export const QUANTITY_BUTTON_COMPACT_CLASS =
	'w-[22px] h-[22px] flex items-center justify-center rounded-full bg-[#EEF2F3] text-[#252B42] text-[0.75rem] font-bold hover:bg-[#DDE5E7] disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-[background-color,transform] duration-200 active:scale-[0.97]'

export const QUANTITY_BUTTON_MOBILE_CLASS =
	'w-[36px] h-[36px] flex items-center justify-center rounded-full bg-[#EEF2F3] text-[#252B42] text-[0.875rem] font-bold hover:bg-[#DDE5E7] disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-[background-color,transform] duration-200 active:scale-[0.97]'

/**
 * Builder delle voci di display per il carrello: aggrega items per gruppo
 * indirizzo (multi-package) o singolo (orphan / solo).
 *
 * @param {Array<object>} items pacchi filtrati attualmente visibili
 * @param {Array<object>} addressGroups meta.address_groups del backend
 * @returns {DisplayEntry[]}
 */
export function buildDisplayEntries(items, addressGroups) {
	if (!items?.length) return []

	const filteredIds = new Set(items.map((i) => i.id))
	const usedIds = new Set()
	const entries = []

	for (let gIdx = 0; gIdx < addressGroups.length; gIdx++) {
		const group = addressGroups[gIdx]
		if (!group) continue
		const groupItems = (group.package_ids || [])
			.filter((id) => filteredIds.has(id) && !usedIds.has(id))
			.map((id) => items.find((i) => i.id === id))
			.filter(Boolean)

		if (groupItems.length === 0) continue
		groupItems.forEach((i) => usedIds.add(i.id))

		const firstItem = groupItems[0]
		if (groupItems.length > 1) {
			const groupTotal = groupItems.reduce(
				(sum, i) => sum + (Number(i.single_price) || 0),
				0,
			)
			entries.push({
				type: 'group',
				groupIndex,
				group,
				items,
				totalCents: groupTotal,
				color: CART_GROUP_COLORS[gIdx % CART_GROUP_COLORS.length] ?? '#095866',
			})
		} else if (firstItem) {
			entries.push({
				type: 'single',
				groupIndex,
				item: firstItem,
			})
		}
	}

	for (const item of items) {
		if (!usedIds.has(item.id)) {
			entries.push({
				type: 'single',
				groupIndex: -1,
				item,
			})
		}
	}

	return entries
}

/**
 * Filtri base sui pacchi del carrello: provenienza (citta' origine) e
 * riferimento (id, nome mittente, nome destinatario).
 */
export function applyCartFilters(items, { provenienza, riferimento }) {
	let result = [...items]

	if (provenienza) {
		const needle = provenienza.toLowerCase()
		result = result.filter((item) => (item.origin_address?.city || '').toLowerCase().includes(needle))
	}

	if (riferimento) {
		const needle = riferimento.toLowerCase()
		result = result.filter((item) =>
			String(item.id).includes(riferimento)
			|| (item.origin_address?.name || '').toLowerCase().includes(needle)
			|| (item.destination_address?.name || '').toLowerCase().includes(needle),
		)
	}

	return result
}
