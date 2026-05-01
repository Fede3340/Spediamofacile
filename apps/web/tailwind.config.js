// tailwind.config.js — design tokens SpediamoFacile
//
// I token sono mappati alle CSS variables definite in `assets/css/main.css :root`,
// così Tailwind utility (bg-brand-primary) e CSS legacy (var(--color-brand-primary))
// puntano allo STESSO valore. Single source of truth.
//
// Palette brand: teal primary + arancione accent. NO blu (regola prodotto).
// Pattern uso: bg-brand-primary, text-brand-accent, border-brand-border, ecc.

export default {
	theme: {
		extend: {
			colors: {
				brand: {
					// Primary teal (var --color-brand-primary)
					primary: 'var(--color-brand-primary)',
					'primary-hover': 'var(--color-brand-primary-hover)',
					'primary-light': 'var(--color-brand-primary-light)',

					// Accent arancione (var --color-brand-accent)
					accent: 'var(--color-brand-accent)',
					'accent-hover': 'var(--color-brand-accent-hover)',
					'accent-dark': 'var(--color-accent-dark)',
					'accent-surface': 'var(--color-accent-surface)',
					'accent-surface-hover': 'var(--color-accent-surface-hover)',

					// Soft secondary (info bg/border)
					'soft-bg': 'var(--color-brand-secondary-soft-bg)',
					'soft-border': 'var(--color-brand-secondary-soft-border)',
					'soft-border-strong': 'var(--color-brand-secondary-soft-border-strong)',
					'soft-text': 'var(--color-brand-secondary-soft-text)',
					'soft-text-strong': 'var(--color-brand-secondary-soft-text-strong)',

					// Semantic
					success: 'var(--color-brand-success)',
					'success-fg': 'var(--color-brand-success-fg)',
					'success-bg': 'var(--color-brand-success-bg)',
					error: 'var(--color-brand-error)',

					// Text scale (3 livelli)
					text: 'var(--color-brand-text)',
					'text-secondary': 'var(--color-brand-text-secondary)',
					'text-muted': 'var(--color-brand-text-muted)',

					// Surfaces
					bg: 'var(--color-brand-bg)',
					'bg-alt': 'var(--color-brand-bg-alt)',
					card: 'var(--color-brand-card)',
					border: 'var(--color-brand-border)',

					// Backward-compat aliases (legacy "teal-*" utility)
					teal: 'var(--color-brand-primary)',
					'teal-hover': 'var(--color-brand-primary-hover)',
					'teal-light': 'var(--color-brand-primary-light)',
					'teal-soft-bg': 'var(--color-brand-secondary-soft-bg)',
					'teal-soft-border': 'var(--color-brand-secondary-soft-border)',
					'teal-soft-border-strong': 'var(--color-brand-secondary-soft-border-strong)',
					'teal-soft-text': 'var(--color-brand-secondary-soft-text)',
					'teal-soft-text-strong': 'var(--color-brand-secondary-soft-text-strong)',
				},

				// Surface semantic (Ondata 9)
				surface: {
					raised: 'var(--surface-raised)',
					sunken: 'var(--surface-sunken)',
					overlay: 'var(--surface-overlay)',
					divider: 'var(--surface-divider)',
				},

				// Status (badge ordine)
				status: {
					'pending-fg': 'var(--status-pending-fg)',
					'pending-bg': 'var(--status-pending-bg)',
					'paid-fg': 'var(--status-paid-fg)',
					'paid-bg': 'var(--status-paid-bg)',
					'failed-fg': 'var(--status-failed-fg)',
					'failed-bg': 'var(--status-failed-bg)',
					'refunded-fg': 'var(--status-refunded-fg)',
					'refunded-bg': 'var(--status-refunded-bg)',
					'info-fg': 'var(--status-info-fg)',
					'info-bg': 'var(--status-info-bg)',
					'neutral-fg': 'var(--status-neutral-fg)',
					'neutral-bg': 'var(--status-neutral-bg)',
				},
			},

			fontFamily: {
				sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
				display: ['Montserrat', 'Inter', 'system-ui', 'sans-serif'],
			},

			borderRadius: {
				button: 'var(--sf-radius-button)',
				control: 'var(--sf-radius-control)',
				card: 'var(--sf-radius-card)',
				pill: 'var(--sf-radius-pill)',
			},

			boxShadow: {
				sf: 'var(--sf-shadow-md)',
				'sf-sm': 'var(--sf-shadow-sm)',
				'sf-lg': 'var(--sf-shadow-lg)',
				'sf-focus': 'var(--sf-shadow-focus-ring)',
			},

			backgroundImage: {
				'page-gradient': 'var(--color-brand-page-gradient)',
			},
		},
	},
};
