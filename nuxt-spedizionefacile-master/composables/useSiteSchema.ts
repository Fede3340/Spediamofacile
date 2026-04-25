// Vincolo: non inserire dati inventati nello schema (rating, recensioni, indirizzi reali) — solo fatti verificabili.

interface SiteSchemaBundle {
	organizationSchema: Record<string, unknown>
	websiteSchema: Record<string, unknown>
}

/**
 * Costruisce gli oggetti JSON-LD senza iniettarli nell'head.
 * Esposto separatamente per riuso in test o pagine che vogliono comporre
 * altri schema (es. BreadcrumbList) accanto ai globali.
 */
export const buildSiteSchema = (overrideBaseUrl?: string): SiteSchemaBundle => {
	const runtimeConfig = useRuntimeConfig()
	const baseUrl = (
		overrideBaseUrl
		|| (runtimeConfig.public?.siteUrl as string)
		|| 'https://spediamofacile.it'
	).replace(/\/+$/, '')

	const organizationSchema = {
		'@context': 'https://schema.org',
		'@type': 'Organization',
		'@id': `${baseUrl}/#organization`,
		name: 'SpediamoFacile',
		url: baseUrl,
		logo: {
			'@type': 'ImageObject',
			url: `${baseUrl}/img/logo.svg`,
		},
		contactPoint: [
			{
				'@type': 'ContactPoint',
				contactType: 'customer service',
				areaServed: 'IT',
				availableLanguage: ['it', 'en'],
				url: `${baseUrl}/contatti`,
			},
		],
		// sameAs: profili ufficiali. Mantenere solo URL realmente attivi —
		// Google verifica e penalizza riferimenti morti.
		sameAs: [
			'https://www.facebook.com/spedizionefacile',
			'https://www.instagram.com/spedizionefacile',
			'https://www.linkedin.com/company/spedizionefacile',
		],
	}

	const websiteSchema = {
		'@context': 'https://schema.org',
		'@type': 'WebSite',
		'@id': `${baseUrl}/#website`,
		url: baseUrl,
		name: 'SpediamoFacile',
		inLanguage: 'it-IT',
		publisher: { '@id': `${baseUrl}/#organization` },
		potentialAction: {
			'@type': 'SearchAction',
			target: {
				'@type': 'EntryPoint',
				urlTemplate: `${baseUrl}/faq?q={search_term_string}`,
			},
			'query-input': 'required name=search_term_string',
		},
	}

	return { organizationSchema, websiteSchema }
}

/**
 * Inietta Organization + WebSite schema nell'head come due script JSON-LD distinti
 * (Google indicizza meglio entita' separate vs un singolo @graph aggregato).
 */
export const useSiteSchema = (): SiteSchemaBundle => {
	const bundle = buildSiteSchema()

	useHead({
		script: [
			{
				key: 'site-schema-organization',
				type: 'application/ld+json',
				innerHTML: JSON.stringify(bundle.organizationSchema),
			},
			{
				key: 'site-schema-website',
				type: 'application/ld+json',
				innerHTML: JSON.stringify(bundle.websiteSchema),
			},
		],
	})

	return bundle
}
