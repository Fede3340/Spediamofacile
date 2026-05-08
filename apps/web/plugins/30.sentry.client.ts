// Sentry FE — error tracking + Core Web Vitals + Session Replay on error.
// DSN vuoto OR @sentry/vue non installato = no-op (zero richieste, zero costo).
// Backend già configurato (config/sentry.php). Per attivare FE: `npm install @sentry/vue` + DSN in .env.
export default defineNuxtPlugin(async (nuxtApp) => {
	const config = useRuntimeConfig()
	const dsn = config.public.sentryDsn as string

	// Skip se non configurato.
	if (!dsn) return

	// Dynamic import: se @sentry/vue non installato, no-op gracefully.
	// @vite-ignore evita che Vite faccia import-analysis statica e fallisca il build se il pkg manca.
	let Sentry: any = null
	try {
		Sentry = await import(/* @vite-ignore */ '@sentry/vue')
	} catch {
		if (import.meta.dev) console.warn('[sentry] @sentry/vue not installed — skipping FE error tracking')
		return
	}

	Sentry.init({
		app: nuxtApp.vueApp,
		dsn,
		environment: (config.public.sentryEnvironment as string) || 'production',
		release: (config.public.sentryRelease as string) || undefined,
		tracesSampleRate: 0.1,
		replaysSessionSampleRate: 0,
		replaysOnErrorSampleRate: 1.0,
		integrations: [
			Sentry.browserTracingIntegration(),
			Sentry.replayIntegration(),
		],
		// Scrub PII (email, cookie, IP) prima dell'invio.
		beforeSend(event) {
			if (event.request?.cookies) delete event.request.cookies
			if (event.user?.email) event.user.email = '[scrubbed]'
			if (event.user?.ip_address) event.user.ip_address = '[scrubbed]'
			return event
		},
	})
})
