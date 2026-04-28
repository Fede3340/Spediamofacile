/**
 * @file account — Utility account.
 */
// === utils/account.js — Helper navigation account ===
// Consolidamento di:
//   - utils/accountNavigation.ts        (icons + createAccountSections)
//   - utils/accountNavigationGroups.ts  (adminNavGroups / clientNavGroups / proNavGroups)
// Tutti gli export originali sono preservati identici.

// ─────────────────────────────────────────────────────────────────
// SEZIONE 1 — ex utils/accountNavigation.ts
// ─────────────────────────────────────────────────────────────────

/**
 * @typedef {'truck-fast'
 *   | 'package'
 *   | 'credit-card'
 *   | 'wallet'
 *   | 'bank-transfer'
 *   | 'account'
 *   | 'map-marker'
 *   | 'headset'
 *   | 'chart-box'
 *   | 'clipboard-list'
 *   | 'truck-delivery'
 *   | 'account-group'
 *   | 'share-variant'
 *   | 'services-cog'
 *   | 'tag-multiple'
 *   | 'email'
 *   | 'cog-outline'} AccountIconKey
 */

/** @type {Record<AccountIconKey, string>} */
export const accountCardIcons = {
	'truck-fast':
		'<path d="M3,4A2,2 0 0,0 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8H17V4M10,6L14,10L10,14V11H4V9H10M17,9.5H19.5L21.47,12H17M6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5M18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5Z"/>',
	package:
		'<path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L6.04,7.5L12,10.85L17.96,7.5L12,4.15M5,15.91L11,19.29V12.58L5,9.21V15.91M19,15.91V9.21L13,12.58V19.29L19,15.91Z"/>',
	'credit-card':
		'<path d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"/>',
	wallet:
		'<path d="M5,3C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V16.72C21.59,16.37 22,15.74 22,15V9C22,8.26 21.59,7.63 21,7.28V5A2,2 0 0,0 19,3H5M5,5H19V7H13A2,2 0 0,0 11,9V15A2,2 0 0,0 13,17H19V19H5V5M13,9H20V15H13V9M16,10.5A1.5,1.5 0 0,0 14.5,12A1.5,1.5 0 0,0 16,13.5A1.5,1.5 0 0,0 17.5,12A1.5,1.5 0 0,0 16,10.5Z"/>',
	'bank-transfer': '<path d="M2,5H22V7H2V5M15,10H22V12H15V10M15,16H22V18H15V16M2,10H13L8,15H2V10M2,16H8L13,21H2V16Z"/>',
	account:
		'<path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,6A2,2 0 0,0 10,8A2,2 0 0,0 12,10A2,2 0 0,0 14,8A2,2 0 0,0 12,6M12,13C14.67,13 20,14.33 20,17V20H4V17C4,14.33 9.33,13 12,13M12,14.9C9.03,14.9 5.9,16.36 5.9,17V18.1H18.1V17C18.1,16.36 14.97,14.9 12,14.9Z"/>',
	'map-marker':
		'<path d="M12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5M12,2A7,7 0 0,1 19,9C19,14.25 12,22 12,22C12,22 5,14.25 5,9A7,7 0 0,1 12,2M12,4A5,5 0 0,0 7,9C7,10 7,12 12,18.71C17,12 17,10 17,9A5,5 0 0,0 12,4Z"/>',
	headset:
		'<path d="M12,1C7,1 3,5 3,10V17A3,3 0 0,0 6,20H9V12H5V10A7,7 0 0,1 12,3A7,7 0 0,1 19,10V12H15V20H18A3,3 0 0,0 21,17V10C21,5 16.97,1 12,1Z"/>',
	'chart-box':
		'<path d="M9,17H7V10H9M13,17H11V7H13M17,17H15V13H17M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z"/>',
	'clipboard-list':
		'<path d="M13,12H20V13.5H13M13,9.5H20V11H13M13,14.5H20V16H13M21,4H3A2,2 0 0,0 1,6V19A2,2 0 0,0 3,21H21A2,2 0 0,0 23,19V6A2,2 0 0,0 21,4M21,19H12V6H21"/>',
	'truck-delivery':
		'<path d="M3,4A2,2 0 0,0 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8H17V4M10,6L14,10L10,14V11H4V9H10M17,9.5H19.5L21.47,12H17M6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5M18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5Z"/>',
	'account-group':
		'<path d="M16,13C15.71,13 15.38,13 15.03,13.05C16.19,13.89 17,15 17,16.5V18H22V16.5C22,14.17 18.33,13 16,13M8,13C5.67,13 2,14.17 2,16.5V18H14V16.5C14,14.17 10.33,13 8,13M8,11A3,3 0 0,0 11,8A3,3 0 0,0 8,5A3,3 0 0,0 5,8A3,3 0 0,0 8,11M16,11A3,3 0 0,0 19,8A3,3 0 0,0 16,5A3,3 0 0,0 13,8A3,3 0 0,0 16,11Z"/>',
	'share-variant':
		'<path d="M18,16.08C17.24,16.08 16.56,16.38 16.04,16.85L8.91,12.7C8.96,12.47 9,12.24 9,12C9,11.76 8.96,11.53 8.91,11.3L15.96,7.19C16.5,7.69 17.21,8 18,8A3,3 0 0,0 21,5A3,3 0 0,0 18,2A3,3 0 0,0 15,5C15,5.24 15.04,5.47 15.09,5.7L8.04,9.81C7.5,9.31 6.79,9 6,9A3,3 0 0,0 3,12A3,3 0 0,0 6,15C6.79,15 7.5,14.69 8.04,14.19L15.16,18.34C15.11,18.55 15.08,18.77 15.08,19C15.08,20.61 16.39,21.91 18,21.91C19.61,21.91 20.92,20.61 20.92,19A2.92,2.92 0 0,0 18,16.08Z"/>',
	'services-cog':
		'<path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.21,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.21,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.04 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.67 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.04 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>',
	'tag-multiple':
		'<path d="M5.5,9A1.5,1.5 0 0,0 7,7.5A1.5,1.5 0 0,0 5.5,6A1.5,1.5 0 0,0 4,7.5A1.5,1.5 0 0,0 5.5,9M17.41,11.58C17.77,11.94 18,12.44 18,13C18,13.55 17.78,14.05 17.41,14.41L12.41,19.41C12.05,19.77 11.55,20 11,20C10.45,20 9.95,19.78 9.58,19.41L2.59,12.42C2.22,12.05 2,11.55 2,11V6C2,4.89 2.89,4 4,4H9C9.55,4 10.05,4.22 10.41,4.58L17.41,11.58M13.54,5.71L14.54,4.71L21.41,11.58C21.78,11.94 22,12.45 22,13C22,13.55 21.78,14.05 21.42,14.41L16.04,19.79L15.04,18.79L20.75,13L13.54,5.71Z"/>',
	email: '<path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"/>',
	'cog-outline':
		'<path d="M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8M12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12A2,2 0 0,0 12,10M10,22C9.75,22 9.54,21.82 9.5,21.58L9.13,18.93C8.5,18.68 7.96,18.34 7.44,17.94L4.95,18.95C4.73,19.03 4.46,18.95 4.34,18.73L2.34,15.27C2.21,15.05 2.27,14.78 2.46,14.63L4.57,12.97C4.53,12.65 4.5,12.33 4.5,12C4.5,11.67 4.53,11.34 4.57,11L2.46,9.37C2.27,9.22 2.21,8.95 2.34,8.73L4.34,5.27C4.46,5.05 4.73,4.96 4.95,5.05L7.44,6.05C7.96,5.66 8.5,5.32 9.13,5.07L9.5,2.42C9.54,2.18 9.75,2 10,2H14C14.25,2 14.46,2.18 14.5,2.42L14.87,5.07C15.5,5.32 16.04,5.66 16.56,6.05L19.05,5.05C19.27,4.96 19.54,5.05 19.66,5.27L21.66,8.73C21.79,8.95 21.73,9.22 21.54,9.37L19.43,11C19.47,11.34 19.5,11.67 19.5,12C19.5,12.33 19.47,12.65 19.43,12.97L21.54,14.63C21.73,14.78 21.79,15.05 21.66,15.27L19.66,18.73C19.54,18.95 19.27,19.04 19.05,18.95L16.56,17.94C16.04,18.34 15.5,18.68 14.87,18.93L14.5,21.58C14.46,21.82 14.25,22 14,22H10Z"/>',
}

/**
 * @typedef {Object} AccountTone
 * @property {string} iconBg
 * @property {string} iconColor
 * @property {string} iconBorder
 */

/** @type {AccountTone} */
const shippingTone = {
	iconBg: '#ECF8F8',
	iconColor: '#0F766E',
	iconBorder: 'rgba(15, 118, 110, 0.14)',
}

/** @type {AccountTone} */
const paymentTone = {
	iconBg: '#FFF4EE',
	iconColor: '#E44203',
	iconBorder: 'rgba(228, 66, 3, 0.14)',
}

/** @type {AccountTone} */
const proTone = {
	iconBg: '#FFF6EF',
	iconColor: '#C2410C',
	iconBorder: 'rgba(194, 65, 12, 0.14)',
}

/** @type {AccountTone} */
const profileTone = {
	iconBg: '#F4F6F8',
	iconColor: '#52606D',
	iconBorder: 'rgba(82, 96, 109, 0.14)',
}

/** @type {AccountTone} */
const adminTone = {
	iconBg: '#eef7f8',
	iconColor: '#095866',
	iconBorder: 'rgba(9, 88, 102, 0.14)',
}

/**
 * @typedef {Object} AccountPage
 * @property {string} iconBg
 * @property {string} iconColor
 * @property {string} iconBorder
 * @property {string} title
 * @property {string} [description]
 * @property {string} url
 * @property {boolean} visible
 * @property {AccountIconKey} iconKey
 */

/**
 * @typedef {Object} AccountSection
 * @property {string} title
 * @property {AccountPage[]} pages
 */

/**
 * @typedef {Object} CreateAccountSectionsOptions
 * @property {boolean} [isAdmin]
 * @property {boolean} [isPro]
 */

/**
 * Crea le sezioni di navigazione della pagina Account in funzione di ruolo admin/pro.
 * @param {CreateAccountSectionsOptions} [options]
 * @returns {AccountSection[]}
 */
export const createAccountSections = (
	{ isAdmin = false, isPro = false } = {},
) => {
	return [
		{
			title: 'Spedizioni',
			pages: [
				{
					title: 'Spedizioni',
					description: 'Stato ordini, tracking e dettagli recenti.',
					url: '/spedizioni',
					visible: true,
					iconKey: 'truck-fast',
					...shippingTone,
				},
			],
		},
		{
			title: 'Pagamenti',
			pages: [
				{
					title: 'Carte',
					description: 'Metodi di pagamento salvati e carta predefinita.',
					url: '/carte',
					visible: true,
					iconKey: 'credit-card',
					...paymentTone,
				},
				{
					title: 'Portafoglio',
					description: 'Saldo disponibile, ricariche e movimenti.',
					url: '/portafoglio',
					visible: true,
					iconKey: 'wallet',
					...paymentTone,
				},
				{
					title: 'Fatture',
					description: 'Storico fatture PDF e stato SDI.',
					url: '/fatture',
					visible: true,
					iconKey: 'clipboard-list',
					...paymentTone,
				},
			],
		},
		{
			title: 'Partner Pro',
			pages: [
				{
					title: 'Partner Pro',
					description: 'Inviti, commissioni, richiesta accesso.',
					url: '/account-pro',
					visible: true,
					iconKey: 'share-variant',
					...proTone,
				},
			],
		},
		{
			title: 'Profilo',
			pages: [
				{
					title: 'Profilo',
					description: 'Dati personali, email e informazioni account.',
					url: '/profilo',
					visible: true,
					iconKey: 'account',
					...profileTone,
				},
				{
					title: 'Indirizzi',
					description: 'Mittenti, destinatari e indirizzi salvati.',
					url: '/indirizzi',
					visible: true,
					iconKey: 'map-marker',
					...profileTone,
				},
				{
					title: 'Assistenza',
					description: 'Supporto, contatti utili e richieste aperte.',
					url: '/assistenza',
					visible: true,
					iconKey: 'headset',
					...profileTone,
				},
			],
		},
		{
			title: 'Amministrazione',
			pages: [
				{
					title: 'Dashboard',
					url: '/amministrazione',
					visible: isAdmin,
					iconKey: 'chart-box',
					...adminTone,
				},
				{
					title: 'Ordini',
					url: '/amministrazione/ordini',
					visible: isAdmin,
					iconKey: 'clipboard-list',
					...adminTone,
				},
				{
					title: 'Tracking BRT',
					url: '/amministrazione/spedizioni',
					visible: isAdmin,
					iconKey: 'truck-delivery',
					iconBg: '#ECF8F8',
					iconColor: '#0F766E',
					iconBorder: 'rgba(15, 118, 110, 0.14)',
				},
				{
					title: 'Utenti',
					url: '/amministrazione/utenti',
					visible: isAdmin,
					iconKey: 'account-group',
					iconBg: '#F4F5F7',
					iconColor: '#475569',
					iconBorder: 'rgba(71, 85, 105, 0.14)',
				},
				{
					title: 'Prezzi',
					url: '/amministrazione/prezzi',
					visible: isAdmin,
					iconKey: 'tag-multiple',
					iconBg: '#FFF4EE',
					iconColor: '#E44203',
					iconBorder: 'rgba(228, 66, 3, 0.14)',
				},
				{
					title: 'Servizi',
					url: '/amministrazione/servizi',
					visible: isAdmin,
					iconKey: 'services-cog',
					...shippingTone,
				},
				{
					title: 'Impostazioni',
					url: '/amministrazione/impostazioni',
					visible: isAdmin,
					iconKey: 'cog-outline',
					iconBg: '#F4F5F7',
					iconColor: '#475569',
					iconBorder: 'rgba(71, 85, 105, 0.14)',
				},
			],
		},
	]
}

// ─────────────────────────────────────────────────────────────────
// SEZIONE 2 — ex utils/accountNavigationGroups.ts
// ─────────────────────────────────────────────────────────────────

/**
 * @typedef {'admin' | 'pro' | 'client'} AccountNavTone
 */

/**
 * @typedef {Object} AccountNavItem
 * @property {string} label
 * @property {string} to
 * @property {AccountIconKey} iconKey
 * @property {boolean} [exact]
 * @property {number | string} [badge]
 */

/**
 * @typedef {Object} AccountNavGroup
 * @property {string} [key]
 * @property {string} [title]
 * @property {AccountNavTone} tone
 * @property {AccountNavItem[]} items
 */

/** @type {AccountNavGroup[]} */
export const adminNavGroups = [
	// Voce "Dashboard" rimossa: era doppione del bottone "Console" sopra (entrambi → /account/amministrazione).
	{
		key: 'operativo',
		title: 'Operativo',
		tone: 'admin',
		items: [
			{ label: 'Ordini', to: '/account/amministrazione/ordini', iconKey: 'clipboard-list' },
			// "Coda BRT" invece di "Spedizioni" per evitare confusione con "Le mie spedizioni" sotto.
			{ label: 'Coda BRT', to: '/account/amministrazione/spedizioni', iconKey: 'truck-delivery' },
			{ label: 'Bonifici', to: '/account/amministrazione/bonifici', iconKey: 'bank-transfer' },
		],
	},
	{
		key: 'clienti',
		title: 'Clienti',
		tone: 'client',
		items: [
			{ label: 'Utenti', to: '/account/amministrazione/utenti', iconKey: 'account-group' },
		],
	},
	{
		key: 'finanza',
		title: 'Finanza',
		tone: 'pro',
		items: [
			{ label: 'Prezzi', to: '/account/amministrazione/prezzi', iconKey: 'tag-multiple' },
		],
	},
	{
		key: 'contenuti',
		title: 'Contenuti',
		tone: 'admin',
		items: [
			{ label: 'Servizi', to: '/account/amministrazione/servizi', iconKey: 'services-cog' },
			// Guide nel menu sidebar admin: stesso modello di Servizi (CMS articoli pubblici).
			{ label: 'Guide', to: '/guide', iconKey: 'clipboard-list' },
		],
	},
	{
		key: 'sistema',
		title: 'Sistema',
		tone: 'admin',
		items: [
			{ label: 'Impostazioni', to: '/account/amministrazione/impostazioni', iconKey: 'cog-outline' },
		],
	},
	{
		key: 'account-personale',
		title: 'Il tuo account',
		tone: 'admin',
		items: [
			// L'admin è anche un utente: vede il SUO account privato qui.
			// "Le mie ..." per distinguere chiaramente da "Coda BRT" / "Ordini" admin.
			{ label: 'Le mie spedizioni', to: '/account/spedizioni', iconKey: 'truck-fast' },
			{ label: 'Le mie fatture', to: '/account/fatture', iconKey: 'clipboard-list' },
			{ label: 'Profilo', to: '/account/profilo', iconKey: 'account' },
			{ label: 'Indirizzi', to: '/account/indirizzi', iconKey: 'map-marker' },
			{ label: 'Portafoglio', to: '/account/portafoglio', iconKey: 'wallet' },
			{ label: 'Carte', to: '/account/carte', iconKey: 'credit-card' },
			{ label: 'Assistenza', to: '/account/assistenza', iconKey: 'headset' },
		],
	},
]

/** @type {AccountNavGroup[]} */
export const clientNavGroups = [
	{
		tone: 'client',
		items: [
			{ label: 'Dashboard', to: '/account', iconKey: 'chart-box', exact: true },
			{ label: 'Spedizioni', to: '/account/spedizioni', iconKey: 'truck-fast' },
			{ label: 'Fatture', to: '/account/fatture', iconKey: 'clipboard-list' },
			{ label: 'Portafoglio', to: '/account/portafoglio', iconKey: 'wallet' },
			{ label: 'Carte', to: '/account/carte', iconKey: 'credit-card' },
			{ label: 'Profilo', to: '/account/profilo', iconKey: 'account' },
			{ label: 'Indirizzi', to: '/account/indirizzi', iconKey: 'map-marker' },
			{ label: 'Assistenza', to: '/account/assistenza', iconKey: 'headset' },
		],
	},
]

/** @type {AccountNavGroup[]} */
export const proNavGroups = [
	{
		tone: 'client',
		items: [
			{ label: 'Dashboard', to: '/account', iconKey: 'chart-box', exact: true },
			{ label: 'Spedizioni', to: '/account/spedizioni', iconKey: 'truck-fast' },
			{ label: 'Fatture', to: '/account/fatture', iconKey: 'clipboard-list' },
			{ label: 'Portafoglio', to: '/account/portafoglio', iconKey: 'wallet' },
			{ label: 'Carte', to: '/account/carte', iconKey: 'credit-card' },
			{ label: 'Profilo', to: '/account/profilo', iconKey: 'account' },
			{ label: 'Indirizzi', to: '/account/indirizzi', iconKey: 'map-marker' },
			{ label: 'Assistenza', to: '/account/assistenza', iconKey: 'headset' },
		],
	},
	{
		title: 'Strumenti Pro',
		tone: 'pro',
		items: [
			{ label: 'Partner Pro', to: '/account/account-pro', iconKey: 'share-variant' },
		],
	},
]
