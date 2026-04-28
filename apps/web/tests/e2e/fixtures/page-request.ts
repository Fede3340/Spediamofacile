import type { Page, Route } from '@playwright/test';

/**
 * Helper per eseguire richieste HTTP intercettabili da `page.route`.
 *
 * Playwright `page.route` intercetta SOLO richieste generate dal contesto della
 * pagina (fetch/XHR browser-side). `page.request` (APIRequestContext) bypassa
 * questa intercettazione. I test Sprint 9.1 usano mock route-based, quindi tutte
 * le richieste devono passare da `fetch` lato pagina.
 */

export interface PageFetchResponse {
	status: number;
	ok: boolean;
	headers: Record<string, string>;
	body: unknown;
}

const STUB_BASE = 'http://stub.local/';
const STUB_HTML = '<!doctype html><html><head><title>stub</title></head><body></body></html>';

/**
 * Apre una pagina su un'origine stub mockata, cosi' `fetch` lato pagina ha
 * un origine valido e tutte le route successive (/api/*) vengono intercettate.
 * Chiamare una sola volta per test PRIMA di installare altri mock specifici,
 * oppure in qualsiasi ordine perche' questo handler e' one-shot (times: 1).
 */
const initializedPages = new WeakSet<Page>();

export const initStubOrigin = async (page: Page): Promise<void> => {
	if (initializedPages.has(page)) {
		return;
	}
	// Registra handler stub permanente per l'origin di test.
	await page.route(STUB_BASE, async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'text/html',
			body: STUB_HTML,
		});
	});
	await page.goto(STUB_BASE, { waitUntil: 'domcontentloaded' });
	initializedPages.add(page);
};

export const pageFetch = async (
	page: Page,
	path: string,
	init: { method?: string; body?: unknown; headers?: Record<string, string> } = {},
): Promise<PageFetchResponse> => {
	await initStubOrigin(page);
	// Risolvo sempre a URL assoluto per evitare ambiguita'.
	const absoluteUrl = path.startsWith('http') ? path : new URL(path, STUB_BASE).toString();

	const result = (await page.evaluate(async ({ url, init }) => {
		const response = await fetch(url, {
			method: init.method || 'GET',
			headers: {
				'Content-Type': 'application/json',
				...(init.headers || {}),
			},
			body: init.body !== undefined ? JSON.stringify(init.body) : undefined,
		});
		const text = await response.text();
		let body: unknown = text;
		try {
			body = JSON.parse(text);
		} catch {
			body = text;
		}
		const headersObj: Record<string, string> = {};
		response.headers.forEach((value, key) => {
			headersObj[key] = value;
		});
		return {
			status: response.status,
			ok: response.ok,
			headers: headersObj,
			body,
		};
	}, { url: absoluteUrl, init })) as PageFetchResponse;

	return result;
};
