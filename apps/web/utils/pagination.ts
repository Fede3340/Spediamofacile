/**
 * Helper paginazione lato frontend — pure functions, zero deps Vue.
 *
 * Pattern usato dalle pagine admin (ordini, utenti) e da liste paginate generiche
 * con `last_page` totale + `current_page` corrente.
 */

/**
 * Costruisce la lista visibile di numeri pagina con ellipsis (es. `[1, '…', 4, 5, 6, '…', 12]`).
 *
 * @param current  pagina corrente (1-based)
 * @param total    totale pagine
 * @param max      massimo numeri visibili senza ellipsis (default 5)
 */
export function buildPaginationItems(current: number, total: number, max = 5): Array<number | '…'> {
	if (total <= 1) return [1];
	if (total <= max) return Array.from({ length: total }, (_, i) => i + 1);

	const half = Math.floor(max / 2);
	let start = Math.max(1, current - half);
	const end = Math.min(total, start + max - 1);
	start = Math.max(1, end - max + 1);

	const items: Array<number | '…'> = [];
	if (start > 1) {
		items.push(1);
		if (start > 2) items.push('…');
	}
	for (let i = start; i <= end; i++) items.push(i);
	if (end < total) {
		if (end < total - 1) items.push('…');
		items.push(total);
	}
	return items;
}

/**
 * Indici "Da X a Y" mostrati in pie' di tabella paginata.
 * Ritorna { from: 0, to: 0 } se total === 0.
 */
export function paginationRange(currentPage: number, perPage: number, total: number): { from: number; to: number } {
	if (total === 0) return { from: 0, to: 0 };
	const from = (currentPage - 1) * perPage + 1;
	const to = Math.min(currentPage * perPage, total);
	return { from, to };
}
