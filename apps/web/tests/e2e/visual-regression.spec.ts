import { test, expect } from '@playwright/test';
import { authStateFiles } from './utils/authState';

/**
 * Visual Regression — Sprint 9.5 baseline
 * -------------------------------------------------
 * 15 pagine critiche × 3 viewport (desktop/tablet/mobile) = 45 snapshot.
 * Tolerance conservativa (0.1%) per evitare flakiness.
 *
 * Update baseline (UI change intenzionale):
 *   npx playwright test tests/e2e/visual-regression.spec.ts --update-snapshots
 *
 * Docs: docs/VISUAL_REGRESSION.md
 */

type CriticalPage = {
	url: string;
	name: string;
	authAs?: 'customer' | 'admin';
	waitFor?: number;
};

const CRITICAL_PAGES: readonly CriticalPage[] = [
	{ url: '/', name: 'homepage' },
	{ url: '/preventivo', name: 'preventivo-redirect' },
	{ url: '/la-tua-spedizione/2', name: 'funnel-colli' },
	{ url: '/la-tua-spedizione/2?step=servizi', name: 'funnel-servizi' },
	{ url: '/la-tua-spedizione/2?step=indirizzi', name: 'funnel-indirizzi' },
	{ url: '/la-tua-spedizione/2?step=pagamento', name: 'funnel-pagamento' },
	{ url: '/servizi', name: 'servizi-index' },
	{ url: '/guide', name: 'guide-index' },
	{ url: '/contatti', name: 'contatti' },
	{ url: '/faq', name: 'faq' },
	{ url: '/chi-siamo', name: 'chi-siamo' },
	{ url: '/traccia-spedizione', name: 'traccia-spedizione' },
	{ url: '/account', name: 'account-dashboard', authAs: 'customer' },
	{ url: '/account/amministrazione', name: 'admin-console', authAs: 'admin' },
	{ url: '/account/amministrazione/utenti', name: 'admin-utenti', authAs: 'admin' },
] as const;

const SCREENSHOT_OPTS = {
	fullPage: true,
	maxDiffPixels: 100,
	threshold: 0.001, // 0.1%
	animations: 'disabled' as const,
	caret: 'hide' as const,
} satisfies Parameters<ReturnType<typeof expect>['toHaveScreenshot']>[1];

test.describe('Visual Regression — 15 pagine critiche', () => {
	for (const page of CRITICAL_PAGES) {
		test(`${page.name} baseline visuale`, async ({ page: browserPage, browser }) => {
			const context = page.authAs
				? await browser.newContext({
					storageState: page.authAs === 'admin' ? authStateFiles.admin : authStateFiles.customer,
				})
				: null;
			const pw = context ? await context.newPage() : browserPage;

			await pw.goto(page.url);
			await pw.waitForLoadState('networkidle').catch(() => {
				/* SPA routes may not emit networkidle reliably */
			});
			if (page.waitFor) {
				await pw.waitForTimeout(page.waitFor);
			}

			// Freeze animations and disable transitions to reduce flakiness
			await pw.addStyleTag({
				content: `*,*::before,*::after{animation-duration:0s!important;animation-delay:0s!important;transition-duration:0s!important;transition-delay:0s!important;}`,
			});

			await expect(pw).toHaveScreenshot(`${page.name}.png`, SCREENSHOT_OPTS);

			if (context) {
				await context.close();
			}
		});
	}
});
