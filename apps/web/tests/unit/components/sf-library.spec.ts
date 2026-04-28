/**
 * Smoke + contract tests per la libreria sf/* (Sprint 4.8).
 *
 * Nota: il repo non installa @vue/test-utils e vitest.config non ha
 * @vitejs/plugin-vue. Scrivere test "mount" richiederebbe installare deps +
 * modificare vitest.config. In attesa di quella modifica infrastrutturale,
 * validiamo per via statica (lettura del file SFC) che ogni componente
 * espone le props, i token design system e gli attributi a11y chiave
 * previsti dal piano. I test falliscono se un componente viene rotto
 * accidentalmente (refactor che rimuove una prop o un aria-* richiesto).
 */
import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(__dirname, '..', '..', '..');
function readSfc(name: string): string {
	return readFileSync(resolve(root, 'components', 'sf', `${name}.vue`), 'utf-8');
}

const COMPONENTS = [
	'SfButton', 'SfCard', 'SfInput', 'SfBadge', 'SfIcon',
	'SfEmptyState', 'SfSkeleton', 'SfToast', 'SfTooltip', 'SfModal',
];

describe('sf/* library – smoke presence', () => {
	it.each(COMPONENTS)('%s SFC is readable and non-empty', (name) => {
		const src = readSfc(name);
		expect(src.length).toBeGreaterThan(200);
		expect(src).toContain('<template>');
		expect(src).toContain('<script setup');
	});

	it.each(COMPONENTS)('%s uses Composition API with TypeScript', (name) => {
		const src = readSfc(name);
		expect(src).toMatch(/<script setup lang="ts">/);
	});

	it.each(COMPONENTS)('%s has no hardcoded hex colors (uses design tokens)', (name) => {
		const src = readSfc(name);
		// Permettiamo bianco puro e neri comuni (non brand). Tutti gli altri hex devono essere var(--*).
		const hexMatches = src.match(/#[0-9a-fA-F]{3,8}\b/g) || [];
		const nonAllowed = hexMatches.filter((h) => !['#fff', '#ffffff', '#000', '#000000'].includes(h.toLowerCase()));
		expect(nonAllowed, `${name} should not hardcode brand hex: ${nonAllowed.join(',')}`).toHaveLength(0);
	});

	it.each(COMPONENTS)('%s never uses blue/indigo palette', (name) => {
		const src = readSfc(name).toLowerCase();
		expect(src).not.toMatch(/\b(blue-|indigo-|#3b82f6|#2563eb|#1d4ed8|#4f46e5)\b/);
	});
});

describe('SfButton contract', () => {
	const src = readSfc('SfButton');
	it('exposes required props', () => {
		expect(src).toContain("variant");
		expect(src).toContain("size");
		expect(src).toContain("loading");
		expect(src).toContain("disabled");
		expect(src).toContain("icon");
		expect(src).toContain("iconPosition");
	});
	it('declares all 5 variants', () => {
		for (const v of ['primary', 'secondary', 'tertiary', 'ghost', 'cta']) expect(src).toContain(v);
	});
	it('uses button-height tokens for 4 sizes', () => {
		for (const s of ['xs', 'sm', 'md', 'lg']) expect(src).toContain(`--button-height-${s}`);
	});
	it('sets aria-busy on loading', () => { expect(src).toContain('aria-busy'); });
});

describe('SfCard contract', () => {
	const src = readSfc('SfCard');
	it('supports 4 variants', () => {
		for (const v of ['base', 'featured', 'kpi', 'flat']) expect(src).toContain(v);
	});
	it('uses radius-md and radius-lg tokens', () => {
		expect(src).toContain('--radius-md');
		expect(src).toContain('--radius-lg');
	});
});

describe('SfInput contract', () => {
	const src = readSfc('SfInput');
	it('has accessible attrs aria-invalid + aria-describedby', () => {
		expect(src).toContain('aria-invalid');
		expect(src).toContain('aria-describedby');
	});
	it('renders label and error with role alert', () => {
		expect(src).toContain('<label');
		expect(src).toMatch(/role="alert"/);
	});
	it('uses focus ring token', () => { expect(src).toContain('--shadow-focus'); });
});

describe('SfBadge contract', () => {
	const src = readSfc('SfBadge');
	it('has 6 variants mapped to admin-status tokens', () => {
		for (const v of ['success', 'warning', 'info', 'neutral', 'danger', 'accent']) expect(src).toContain(v);
		expect(src).toContain('--admin-status-success-bg');
		expect(src).toContain('--admin-status-danger-bg');
	});
});

describe('SfIcon contract', () => {
	const src = readSfc('SfIcon');
	it('has 5 size tiers', () => {
		for (const s of ['micro', 'small', 'medium', 'large', 'xlarge']) expect(src).toContain(`--icon-${s}`);
	});
	it('is aria-hidden by default and exposes ariaLabel opt-in', () => {
		expect(src).toContain('aria-hidden');
		expect(src).toContain('ariaLabel');
	});
});

describe('SfEmptyState contract', () => {
	const src = readSfc('SfEmptyState');
	it('requires title and supports description/icon/action slot', () => {
		expect(src).toContain('title');
		expect(src).toContain('description');
		expect(src).toMatch(/name="action"/);
	});
});

describe('SfSkeleton contract', () => {
	const src = readSfc('SfSkeleton');
	it('exposes width/height/rounded/count props', () => {
		for (const p of ['width', 'height', 'rounded', 'count']) expect(src).toContain(p);
	});
	it('respects prefers-reduced-motion', () => {
		expect(src).toContain('prefers-reduced-motion');
	});
});

describe('SfToast contract', () => {
	const src = readSfc('SfToast');
	it('has 4 types + aria-live', () => {
		for (const t of ['success', 'error', 'info', 'warning']) expect(src).toContain(t);
		expect(src).toContain('aria-live');
		expect(src).toContain('role="status"');
	});
	it('supports duration auto-dismiss', () => {
		expect(src).toContain('duration');
		expect(src).toContain('setTimeout');
	});
});

describe('SfTooltip contract', () => {
	const src = readSfc('SfTooltip');
	it('has 4 positions + delay + aria-describedby', () => {
		for (const p of ['top', 'bottom', 'left', 'right']) expect(src).toContain(p);
		expect(src).toContain('delay');
		expect(src).toContain('aria-describedby');
	});
});

describe('SfModal contract', () => {
	const src = readSfc('SfModal');
	it('has aria-modal + role dialog + focus trap', () => {
		expect(src).toContain('role="dialog"');
		expect(src).toContain('aria-modal="true"');
		expect(src).toMatch(/Escape/);
		expect(src).toMatch(/Tab/);
	});
	it('supports 4 sizes + persistent + closeOnBackdrop', () => {
		for (const s of ['sm', 'md', 'lg', 'xl']) expect(src).toContain(`--${s}`);
		expect(src).toContain('persistent');
		expect(src).toContain('closeOnBackdrop');
	});
	it('locks body scroll', () => {
		expect(src).toContain('overflow');
	});
});
