// https://nuxt.com/docs/api/configuration/nuxt-config
// Meta SEO globali (title, description, og/twitter, schema.org) sono gestiti
// in app.vue via useSeoMeta / useSiteSchema — questo file contiene solo
// configurazione tecnica.

const isProd = process.env.NODE_ENV === 'production'
const apiBase = String(process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8787').trim()

export default defineNuxtConfig({
	compatibilityDate: '2024-04-03',

	devtools: {
		enabled: !isProd && process.env.NUXT_ENABLE_DEVTOOLS === 'true',
	},

	modules: [
		'@nuxt/ui',
		'@nuxt/image',
		'@pinia/nuxt',
		'nuxt-auth-sanctum',
		'nuxt-security',
		'@nuxtjs/sitemap',
		'@nuxtjs/turnstile',
	],

	// Componenti accessibili con solo basename (es. <ServizioGrid>).
	components: [{ path: '~/components', pathPrefix: false }],

	ui: {
		fonts: false, // font locali via @fontsource
	},

	image: {
		format: ['avif', 'webp'],
		quality: 80,
		screens: { mobile: 375, tablet: 720, desktop: 1024, 'desktop-xl': 1440 },
	},

	icon: {
		localApiEndpoint: '/_nuxt_icon',
		clientBundle: { scan: true },
		serverBundle: 'local',
	},

	css: [
		'~/assets/css/main.css',
		'~/assets/css/admin.css',
		'~/assets/css/print.css',
	],

	app: {
		pageTransition: false,
		layoutTransition: false,
		head: {
			htmlAttrs: { lang: 'it' },
			charset: 'utf-8',
			viewport: 'width=device-width, initial-scale=1',
			titleTemplate: '%s | SpediamoFacile',
			title: 'Spedizioni Nazionali e Internazionali a Prezzi Competitivi',
			meta: [
				{ name: 'format-detection', content: 'telephone=no' },
			],
			link: [
				// Preload solo i 4 pesi above-the-fold (P11 perf agent).
				// Inter 500/700 e Montserrat 400 caricati on-demand via font-display: swap.
				{ rel: 'preload', as: 'font', type: 'font/woff2', href: '/fonts/inter-latin-400-normal.woff2', crossorigin: '' },
				{ rel: 'preload', as: 'font', type: 'font/woff2', href: '/fonts/inter-latin-600-normal.woff2', crossorigin: '' },
				{ rel: 'preload', as: 'font', type: 'font/woff2', href: '/fonts/inter-latin-700-normal.woff2', crossorigin: '' },
				{ rel: 'preload', as: 'font', type: 'font/woff2', href: '/fonts/montserrat-latin-600-normal.woff2', crossorigin: '' },
			],
		},
	},

	router: { options: { scrollBehaviorType: 'auto' } },

	runtimeConfig: {
		public: {
			apiBase,
			stripeKey: process.env.NUXT_PUBLIC_STRIPE_KEY || '',
			enableDevTools: process.env.NUXT_PUBLIC_ENABLE_DEV_TOOLS === 'true',
			plausibleDomain: process.env.NUXT_PUBLIC_PLAUSIBLE_DOMAIN || '',
			turnstileSiteKey: process.env.NUXT_PUBLIC_TURNSTILE_SITE_KEY || '1x00000000000000000000AA',
			siteUrl: String(process.env.NUXT_PUBLIC_SITE_URL || 'https://spediamofacile.it').replace(/\/+$/, ''),
		},
	},

	sourcemap: { client: !isProd, server: false },

	// Cloudflare Turnstile (CAPTCHA): `1x00000000000000000000AA` = chiave di TEST.
	// In prod servire NUXT_PUBLIC_TURNSTILE_SITE_KEY.
	turnstile: {
		siteKey: process.env.NUXT_PUBLIC_TURNSTILE_SITE_KEY || '1x00000000000000000000AA',
		addValidateEndpoint: false,
	},

	// Sanctum SPA auth (cookie + CSRF). baseUrl dinamicamente overridden
	// lato client dal plugin 10.bootstrap.client.ts per tunnel Cloudflare.
	sanctum: {
		baseUrl: apiBase,
		endpoints: {
			csrf: '/sanctum/csrf-cookie',
			login: '/api/custom-login',
			logout: '/api/logout',
			user: '/api/user',
		},
		csrf: { cookie: 'XSRF-TOKEN', header: 'X-XSRF-TOKEN' },
		client: { retry: false, initialRequest: false },
		redirect: {
			keepRequestedRoute: true,
			onLogin: '/',
			onLogout: '/',
			onAuthOnly: '/',
			onGuestOnly: '/',
		},
		globalMiddleware: { enabled: false },
		logLevel: 3,
	},

	routeRules: {
		// Asset hashati Vite: cache immutable.
		'/_nuxt/**': { headers: { 'Cache-Control': 'public, max-age=31536000, immutable' } },
		// Pagine dinamiche: no prerender.
		'/account/**': { prerender: false },
		'/autenticazione': { prerender: false },
		'/login': { prerender: false },
		'/registrazione': { prerender: false },
		'/preventivo': { prerender: false },
		'/carrello': { prerender: false },
		'/checkout': { prerender: false },
		'/riepilogo': { prerender: false },
		'/la-tua-spedizione/**': { prerender: false },
		// Redirect legacy.
		'/termini-condizioni': { redirect: { to: '/termini-e-condizioni', statusCode: 301 } },
		'/traccia-spedizione': { redirect: { to: '/traccia', statusCode: 301 } },
	},

	experimental: {
		// Windows/dev: payloadExtraction causa ENOENT su .nuxt/cache/nuxt/payload.
		payloadExtraction: false,
		browserDevtoolsTiming: false,
		asyncContext: true,
	},

	debug: { hooks: false },

	nitro: {
		compressPublicAssets: false, // Windows race condition
		minify: true,
		prerender: {
			crawlLinks: false,
			routes: [
				'/chi-siamo',
				'/faq',
				'/contatti',
				'/privacy-policy',
				'/cookie-policy',
				'/termini-e-condizioni',
			],
			ignore: [
				'/account',
				'/account/**',
				'/autenticazione',
				'/login',
				'/registrazione',
				'/preventivo',
				'/carrello',
				'/checkout',
				'/riepilogo',
				'/la-tua-spedizione/**',
			],
		},
	},

	// CSP defense-in-depth. In dev: 'unsafe-inline' + 'unsafe-eval' per HMR Vite.
	// In prod: solo Stripe + Plausible + Turnstile whitelisted.
	security: {
		headers: {
			contentSecurityPolicy: {
				'default-src': ["'self'"],
				'script-src': isProd
					? ["'self'", 'https://js.stripe.com', 'https://m.stripe.network', 'https://plausible.io', 'https://challenges.cloudflare.com']
					: ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://js.stripe.com', 'https://m.stripe.network', 'https://plausible.io', 'https://challenges.cloudflare.com'],
				'style-src': ["'self'", "'unsafe-inline'"],
				'img-src': ["'self'", 'data:', 'https:'],
				'font-src': ["'self'", 'data:'],
				'connect-src': isProd
					? ["'self'", 'https://api.stripe.com', 'https://m.stripe.network', 'https://nominatim.openstreetmap.org', 'https://plausible.io', 'https://challenges.cloudflare.com']
					: ["'self'", 'https://api.stripe.com', 'https://m.stripe.network', 'https://*.trycloudflare.com', 'https://nominatim.openstreetmap.org', 'https://plausible.io', 'https://challenges.cloudflare.com', 'ws:', 'wss:'],
				'frame-src': ["'self'", 'https://js.stripe.com', 'https://hooks.stripe.com', 'https://challenges.cloudflare.com'],
				'object-src': ["'none'"],
				'base-uri': ["'self'"],
				'form-action': ["'self'"],
				'frame-ancestors': ["'self'"],
				'upgrade-insecure-requests': isProd,
			},
			permissionsPolicy: {
				camera: [],
				microphone: [],
				geolocation: ['self'],
			},
			crossOriginEmbedderPolicy: false,
			strictTransportSecurity: isProd ? { maxAge: 15552000, includeSubdomains: true } : false,
		},
		rateLimiter: false, // gestito lato Laravel/Caddy
	},

	site: { url: 'https://spediamofacile.it' },

	sitemap: {
		exclude: [
			'/account/**',
			'/checkout',
			'/riepilogo',
			'/carrello',
			'/autenticazione',
			'/login',
			'/registrazione',
			'/recupera-password',
			'/aggiorna-password',
			'/verifica-email',
			'/traccia/**', // codici tracking privati
			'/la-tua-spedizione/**',
			'/preview/**',
		],
		sources: [
			'/__sitemap__/guide',
			'/__sitemap__/servizi',
		],
		cacheMaxAgeSeconds: 3600,
	},

	devServer: {
		port: Number(process.env.NUXT_DEV_PORT || 8787),
		host: process.env.NUXT_DEV_HOST || '127.0.0.1',
	},

	vite: {
		optimizeDeps: { include: ['leaflet'] },
		server: {
			allowedHosts: ['.trycloudflare.com', 'localhost', '127.0.0.1'],
			// Vite 7 HMR su porta 5173 confligge con Nuxt dev 8787: off di default.
			hmr: process.env.NUXT_HMR_ENABLED === '1' ? true : false,
		},
		build: {
			cssMinify: 'lightningcss',
			target: 'es2022',
			cssCodeSplit: true,
			rollupOptions: {
				output: {
					// Vendor chunks pesanti: caricati solo dove servono.
					manualChunks(id) {
						if (id.includes('@stripe/stripe-js')) return 'vendor-stripe'
						if (id.includes('pinia')) return 'vendor-pinia'
						if (id.includes('leaflet')) return 'vendor-leaflet'
					},
				},
			},
		},
	},
})
