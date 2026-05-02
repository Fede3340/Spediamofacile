/**
 * cartHelpers — funzioni pure di supporto al cart/checkout.
 *
 * Tutte le funzioni qui dentro sono prive di stato e SSR-safe.
 *
 * Sezioni:
 *   1. Cart formatters (cents/euro, unit price, icone, gruppi)
 *   2. Cart filters & display entries
 *   3. Discount preview (coupon/referral)
 *   4. Pending payment (localStorage)
 */

const NBSP = ' '
const EURO = '€'

type CartAddress = {
	city?: string
	name?: string
}
type CartItem = {
	id: string | number
	quantity?: number | string
	single_price?: number | string
	package_type?: string
	origin_address?: CartAddress
	destination_address?: CartAddress
}
type AddressGroup = {
	package_ids?: Array<string | number>
	[key: string]: unknown
}
type CartFilters = {
	provenienza?: string
	riferimento?: string
}
type DisplayGroupEntry = {
	type: 'group'
	groupIndex: number
	group: AddressGroup
	items: CartItem[]
	totalCents: number
	color: string
}
type DisplaySingleEntry = {
	type: 'single'
	groupIndex: number
	item: CartItem
}
type DisplayEntry = DisplayGroupEntry | DisplaySingleEntry

// ─── 1. Cart formatters ───────────────────────────────────────────

/** Converte centesimi in euro, sempre >= 0. Per leggere campi `_cents`. */
export function centsToEuro(value: unknown): number {
	const cents = Number(value)
	return Number.isFinite(cents) ? Math.max(0, cents) / 100 : 0
}

/** Formatter "0,00 €" (NBSP prima del simbolo). Accetta euro-float. */
export function formatEuroAmount(num: unknown): string {
	const n = Number(num)
	if (!Number.isFinite(n)) return `0,00${NBSP}${EURO}`
	return n.toFixed(2).replace('.', ',') + NBSP + EURO
}

/** Prezzo unitario (totale / quantita'). */
export function unitPrice(item: Partial<CartItem>): number {
	const total = Number(item?.single_price) || 0
	const qty = Math.max(1, Number(item?.quantity) || 1)
	return total / qty
}

/** Mapping package_type -> icona nel carrello. */
export function getPackageIcon(item: Partial<CartItem>): string {
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

// ─── 2. Cart filters & display entries ────────────────────────────

/**
 * Builder delle voci di display per il carrello: aggrega items per gruppo
 * indirizzo (multi-package) o singolo (orphan / solo).
 */
export function buildDisplayEntries(items: CartItem[], addressGroups: AddressGroup[] = []): DisplayEntry[] {
	if (!items?.length) return []

	const filteredIds = new Set(items.map((i) => i.id))
	const usedIds = new Set<string | number>()
	const entries: DisplayEntry[] = []

	for (let gIdx = 0; gIdx < addressGroups.length; gIdx++) {
		const group = addressGroups[gIdx]
		if (!group) continue
		const groupItems = (group.package_ids || [])
			.filter((id) => filteredIds.has(id) && !usedIds.has(id))
			.map((id) => items.find((i) => i.id === id))
			.filter((item): item is CartItem => Boolean(item))

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
				groupIndex: gIdx,
				group,
				items: groupItems,
				totalCents: groupTotal,
				color: CART_GROUP_COLORS[gIdx % CART_GROUP_COLORS.length] ?? '#095866',
			})
		} else if (firstItem) {
			entries.push({
				type: 'single',
				groupIndex: gIdx,
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
export function applyCartFilters(items: CartItem[], { provenienza, riferimento }: CartFilters): CartItem[] {
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

// ─── 3. Discount preview (coupon / referral) ──────────────────────

type DiscountPreviewSource = {
	type?: unknown
	code?: unknown
	referral_code?: unknown
	percentage?: unknown
	discount_percent?: unknown
	discount_amount?: unknown
	new_total_raw?: unknown
	new_total?: unknown
	pro_user_name?: unknown
	pro_name?: unknown
}

type BuildPreviewOptions = {
	result?: DiscountPreviewSource | null
	total?: unknown
	codeFallback?: string
	typeFallback?: string
}

type BuildOrderContextOptions = {
	preview?: DiscountPreviewSource | null
	subtotal?: unknown
	finalTotal?: unknown
}

export function parseEuroAmount(value: unknown): number {
	if (typeof value === 'number' && Number.isFinite(value)) return value

	const normalized = String(value ?? '')
		.replace(/€/g, '')
		.replace(/EUR/gi, '')
		.replace(/\s/g, '')
		.replace(/\./g, '')
		.replace(',', '.')

	const parsed = Number(normalized)
	return Number.isFinite(parsed) ? parsed : 0
}

export function calculateDiscountAmount(total: unknown, percentage: unknown): number {
	return Math.round(parseEuroAmount(total) * (Number(percentage || 0) / 100) * 100) / 100
}

export function calculateDiscountedTotal(total: unknown, discountAmount: unknown): number {
	return Math.max(0, Math.round((parseEuroAmount(total) - Number(discountAmount || 0)) * 100) / 100)
}

export function formatPreviewEuroAmount(value: unknown): string {
	return `${parseEuroAmount(value).toFixed(2).replace('.', ',')}\u00A0\u20AC`
}

export function buildDiscountPreviewState({
	result = null,
	total,
	codeFallback = '',
	typeFallback = 'coupon',
}: BuildPreviewOptions = {}) {
	const subtotal = parseEuroAmount(total)
	const percentage = Number(result?.percentage ?? result?.discount_percent ?? 0)
	const discountAmountCandidate = Number(result?.discount_amount)
	const discountAmount = Number.isFinite(discountAmountCandidate)
		? discountAmountCandidate
		: calculateDiscountAmount(subtotal, percentage)
	const finalTotalCandidate = Number(result?.new_total_raw)
	const finalTotal = Number.isFinite(finalTotalCandidate)
		? finalTotalCandidate
		: calculateDiscountedTotal(subtotal, discountAmount)

	return {
		type: String(result?.type || typeFallback),
		code: String(result?.referral_code || result?.code || codeFallback || '').trim().toUpperCase(),
		discount_percent: Number.isFinite(percentage) ? percentage : 0,
		discount_amount: discountAmount,
		new_total_raw: finalTotal,
		new_total: String(result?.new_total || formatPreviewEuroAmount(finalTotal)),
		pro_name: String(result?.pro_user_name || result?.pro_name || ''),
	}
}

export function buildCartDiscountPreviewState(options: BuildPreviewOptions = {}) {
	const preview = buildDiscountPreviewState(options)

	return {
		couponApplied: true,
		couponDiscount: preview.discount_percent || null,
		appliedTotal: preview.new_total,
		preview,
	}
}

export function buildDiscountOrderContext({ preview = null, subtotal, finalTotal }: BuildOrderContextOptions = {}) {
	if (!preview || typeof preview !== 'object') return null

	const code = String(preview.code || preview.referral_code || '').trim().toUpperCase()
	const type = String(preview.type || '').trim().toLowerCase()
	if (!code || !type) return null

	const subtotalAmount = parseEuroAmount(subtotal)
	const discountAmount = Number(preview.discount_amount)
	const normalizedDiscountAmount = Number.isFinite(discountAmount)
		? discountAmount
		: calculateDiscountAmount(subtotalAmount, preview.discount_percent ?? preview.percentage ?? 0)
	const explicitFinalTotal = Number(finalTotal)
	const normalizedFinalTotal = Number.isFinite(explicitFinalTotal)
		? explicitFinalTotal
		: calculateDiscountedTotal(subtotalAmount, normalizedDiscountAmount)
	const discountPercent = Number(preview.discount_percent ?? preview.percentage ?? 0)

	return {
		type,
		code,
		discount_percent: Number.isFinite(discountPercent) ? discountPercent : 0,
		discount_amount: normalizedDiscountAmount,
		subtotal_raw: subtotalAmount,
		final_total_raw: normalizedFinalTotal,
		pro_name: String(preview.pro_name || preview.pro_user_name || '').trim(),
	}
}

// ─── 4. Pending payment (localStorage draft) ──────────────────────

export const PENDING_PAYMENT_KEY = 'sf_pending_payment'
export const PENDING_PAYMENT_TTL_MS = 24 * 60 * 60 * 1000

export type PendingPaymentDraft = {
	orderId: string | number
	paymentMethod?: string
	submissionId?: string
	isExisting?: boolean
	amount?: number
	createdAt?: number
	expiresAt?: number
	[key: string]: unknown
}

export function safeLocalGet<T = unknown>(key: string): T | null {
	if (typeof window === 'undefined') return null
	try {
		const raw = window.localStorage.getItem(key)
		return raw ? JSON.parse(raw) as T : null
	} catch {
		return null
	}
}

export function safeLocalSet(key: string, value: unknown) {
	if (typeof window === 'undefined') return
	try {
		window.localStorage.setItem(key, JSON.stringify(value))
	} catch {
		/* storage full or disabled */
	}
}

export function safeLocalRemove(key: string) {
	if (typeof window === 'undefined') return
	try {
		window.localStorage.removeItem(key)
	} catch {
		/* storage disabled */
	}
}

export function loadPendingPayment(): PendingPaymentDraft | null {
	const data = safeLocalGet<PendingPaymentDraft>(PENDING_PAYMENT_KEY)
	if (!data) return null
	if (data.expiresAt && data.expiresAt < Date.now()) {
		safeLocalRemove(PENDING_PAYMENT_KEY)
		return null
	}
	return data
}

export function clearPendingPayment() {
	safeLocalRemove(PENDING_PAYMENT_KEY)
}
