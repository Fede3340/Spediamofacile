/**
 * useStatusBadge — palette unica per badge stato ordini/spedizioni.
 *
 * Sostituisce 2 implementazioni locali duplicate:
 *  - pages/account/spedizioni/[id].vue (orderMetaPillStyle by label IT)
 *  - pages/account/amministrazione/index.vue (statusBadgeStyle by enum key)
 *
 * Tre famiglie semantiche di colori (P5 design system):
 *  - warning  → giallo/ambra (in attesa, in giacenza, reso, rimborsato)
 *  - info     → teal brand (in lavorazione, in transito, in consegna)
 *  - success  → verde (completato, pagato, consegnato)
 *  - danger   → rosso (fallito, rifiutato, payment_failed)
 *  - neutral  → grigio (annullato, cancelled)
 */

interface PaletteEntry {
	color: string
	bg: string
}

type PaletteKey =
	| 'warning'
	| 'warningSoft'
	| 'info'
	| 'infoSoft'
	| 'success'
	| 'successAlt'
	| 'danger'
	| 'dangerAlt'
	| 'neutral'
	| 'neutralBrand'

interface BadgeStyle {
	color: string
	background: string
}

const PALETTE: Record<PaletteKey, PaletteEntry> = {
	warning: { color: '#B45309', bg: 'rgba(180,83,9,0.08)' },
	warningSoft: { color: '#C2410C', bg: '#FFF7ED' },
	info: { color: '#095866', bg: 'rgba(9,88,102,0.08)' },
	infoSoft: { color: '#074a56', bg: '#dff0f3' },
	success: { color: '#047857', bg: '#ECFDF3' },
	successAlt: { color: '#059669', bg: 'rgba(5,150,105,0.08)' },
	danger: { color: '#B91C1C', bg: '#FEF2F2' },
	dangerAlt: { color: '#dc2626', bg: 'rgba(220,38,38,0.08)' },
	neutral: { color: '#475569', bg: 'rgba(71,85,105,0.08)' },
	neutralBrand: { color: '#4B5563', bg: 'var(--color-brand-bg-alt, #f3f4f6)' },
}

/* Mappa enum_key → famiglia palette */
const KEY_TO_PALETTE: Record<string, PaletteKey> = {
	pending: 'warning',
	awaiting_bank_transfer: 'warning',
	in_giacenza: 'warningSoft',
	returned: 'warningSoft',
	refunded: 'warningSoft',
	processing: 'info',
	label_generated: 'info',
	in_transit: 'info',
	out_for_delivery: 'infoSoft',
	completed: 'success',
	delivered: 'success',
	payed: 'successAlt',
	paid: 'successAlt',
	payment_failed: 'dangerAlt',
	refused: 'dangerAlt',
	failed: 'danger',
	cancelled: 'neutral',
	canceled: 'neutral',
}

/* Mappa label IT (humanReadable) → famiglia palette */
const LABEL_TO_PALETTE: Record<string, PaletteKey> = {
	'In attesa': 'warning',
	'In giacenza': 'warningSoft',
	Reso: 'warningSoft',
	Rimborsato: 'warningSoft',
	'In lavorazione': 'info',
	'Etichetta generata': 'info',
	'In transito': 'info',
	'In consegna': 'infoSoft',
	Completato: 'success',
	Consegnato: 'success',
	Pagato: 'successAlt',
	Fallito: 'danger',
	Rifiutato: 'dangerAlt',
	Annullato: 'neutralBrand',
}

/**
 * Restituisce style inline { color, background } per uno status (key o label).
 * @param status enum key (es. "completed") o label IT (es. "Completato").
 */
export function useStatusBadgeStyle(status?: string): BadgeStyle {
	if (!status) return { color: PALETTE.neutral.color, background: PALETTE.neutral.bg }
	const key = KEY_TO_PALETTE[status] || LABEL_TO_PALETTE[status] || 'neutral'
	const p = PALETTE[key]
	return { color: p.color, background: p.bg }
}

export function useStatusBadge() {
	return { getStyle: useStatusBadgeStyle }
}
