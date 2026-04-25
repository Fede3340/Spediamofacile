<script setup>
// Pagina Contatti — redesign editoriale (hero + strip canali + form centrato + FAQ grid + quick next)
import '~/assets/css/contatti.css'

useSeoMeta({
	title: 'Contatti | SpediamoFacile - Assistenza e Supporto',
	ogTitle: 'Contatti | SpediamoFacile',
	description: 'Hai bisogno di aiuto? Contatta il team di SpediamoFacile per assistenza sulle tue spedizioni, preventivi personalizzati o informazioni sui nostri servizi.',
	ogDescription: 'Contatta SpediamoFacile per assistenza e supporto sulle tue spedizioni.',
})

useHead({
	script: [{
		type: 'application/ld+json',
		innerHTML: JSON.stringify({
			'@context': 'https://schema.org',
			'@type': 'ContactPage',
			name: 'Contatti SpediamoFacile',
			url: 'https://spediamofacile.it/contatti',
			mainEntity: {
				'@type': 'Organization',
				name: 'SpediamoFacile',
				url: 'https://spediamofacile.it',
				contactPoint: {
					'@type': 'ContactPoint',
					contactType: 'customer service',
					availableLanguage: 'Italian',
				},
			},
		}),
	}],
})

// Breadcrumb: Home › Contatti
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Contatti' },
])

const sanctum = useSanctumClient()

const contactForm = ref({
	name: '',
	surname: '',
	email: '',
	telephone_number: '',
	message: '',
})

const isSubmitting = ref(false)
const submitSuccess = ref(false)
const submitError = ref(null)

// Cloudflare Turnstile (CAPTCHA) — gate frontend anti-bot.
const turnstile = useTurnstile()

const resetForm = () => {
	contactForm.value = {
		name: '',
		surname: '',
		email: '',
		telephone_number: '',
		message: '',
	}
	turnstile.reset()
}

const handleSubmit = async () => {
	submitError.value = null
	if (!turnstile.isReady.value) {
		submitError.value = 'Conferma di non essere un bot per inviare il messaggio.'
		return
	}
	isSubmitting.value = true

	try {
		await sanctum('/sanctum/csrf-cookie')
		await sanctum('/api/contact', {
			method: 'POST',
			body: { ...contactForm.value, ...turnstile.payload() },
		})
		submitSuccess.value = true
		resetForm()
	} catch (error) {
		const data = error?.response?._data || error?.data
		if (data?.errors) {
			const firstError = Object.values(data.errors)[0]
			submitError.value = Array.isArray(firstError) ? firstError[0] : firstError
		} else {
			submitError.value = data?.message || "Errore durante l'invio. Riprova."
		}
		turnstile.reset()
	} finally {
		isSubmitting.value = false
	}
}

// Strip canali: 4 tessere icona+label+valore (email, telefono, sede, orari)
const channels = [
	{
		label: 'Email',
		value: 'info@spediamofacile.it',
		href: 'mailto:info@spediamofacile.it',
		icon: 'M20,4H4A2,2 0 0,0 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6A2,2 0 0,0 20,4M20,8L12,13L4,8V6L12,11L20,6V8Z',
	},
	{
		label: 'Telefono',
		value: '+39 02 8295 4130',
		href: 'tel:+390282954130',
		icon: 'M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z',
	},
	{
		label: 'Sede',
		value: 'Via Torino 2, Milano (MI)',
		href: 'https://maps.google.com/?q=Via+Torino+2,+Milano',
		icon: 'M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z',
	},
	{
		label: 'Orari',
		value: 'Lun-Ven 9:00-18:00',
		href: null,
		icon: 'M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z',
	},
]

// Quick actions: 3 strip orizzontali (preventivo, tracking, guide) come CTA finali
const quickActions = [
	{
		title: 'Calcola un preventivo',
		text: 'Parti dal prezzo in 30 secondi, senza chiamare.',
		href: '/preventivo',
		icon: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2a3 3 0 0 0 6 0h6a3 3 0 0 0 6 0h2v-5l-3-4Zm-2 9.5a1.5 1.5 0 1 1 .001-2.999A1.5 1.5 0 0 1 18 17.5Zm-12 0a1.5 1.5 0 1 1 .001-2.999A1.5 1.5 0 0 1 6 17.5ZM19.5 12H17V9.5h2.5L21 12h-1.5Z',
	},
	{
		title: 'Traccia una spedizione',
		text: 'Stato BRT in tempo reale con il numero.',
		href: '/traccia-spedizione',
		icon: 'M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3Z',
	},
	{
		title: 'Leggi le guide',
		text: 'Imballaggio, documenti, normative: risposte veloci.',
		href: '/guide',
		icon: 'M19 3H5c-1.1 0-2 .9-2 2v14a2 2 0 0 0 2 2h11l5-5V5c0-1.1-.9-2-2-2Zm0 12h-4v4H5V5h14v10Z',
	},
]
</script>

<template>
	<div class="contatti-page">
		<!-- ── HERO editoriale: accent bar + titolo + claim + pill canali rapidi ── -->
		<PublicPageHeader
			eyebrow="Assistenza e supporto"
			title="Parla con noi"
			description="Rispondiamo in giornata a email, telefono o messaggio. Nessun bot, solo persone che conoscono il mondo BRT."
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Contatti' }]">
			<div class="contatti-hero__pills">
				<a href="mailto:info@spediamofacile.it" class="contatti-hero__pill">
					<span class="contatti-hero__pill-label">Email</span>
					<span class="contatti-hero__pill-arrow" aria-hidden="true">→</span>
					<span class="contatti-hero__pill-value">info@spediamofacile.it</span>
				</a>
				<a href="tel:+390282954130" class="contatti-hero__pill">
					<span class="contatti-hero__pill-label">Telefono</span>
					<span class="contatti-hero__pill-arrow" aria-hidden="true">→</span>
					<span class="contatti-hero__pill-value">+39 02 8295 4130</span>
				</a>
				<span class="contatti-hero__pill contatti-hero__pill--static">
					<span class="contatti-hero__pill-label">Sede</span>
					<span class="contatti-hero__pill-arrow" aria-hidden="true">→</span>
					<span class="contatti-hero__pill-value">Milano (MI)</span>
				</span>
			</div>
		</PublicPageHeader>

		<!-- ── STRIP canali: 4 card icona con valore ── -->
		<section class="contact-strip" aria-label="Canali di contatto">
			<div class="my-container">
				<div class="contact-strip__grid">
					<component
						:is="channel.href ? 'a' : 'div'"
						v-for="channel in channels"
						:key="channel.label"
						:href="channel.href || undefined"
						:target="channel.href && channel.href.startsWith('http') ? '_blank' : undefined"
						:rel="channel.href && channel.href.startsWith('http') ? 'noopener noreferrer' : undefined"
						class="contact-strip__card">
						<span class="contact-strip__icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
								<path :d="channel.icon" />
							</svg>
						</span>
						<span class="contact-strip__label">{{ channel.label }}</span>
						<span class="contact-strip__value">{{ channel.value }}</span>
					</component>
				</div>
			</div>
		</section>

		<!-- ── FORM centrato (max 640px) ── -->
		<section class="contact-form-centered" aria-labelledby="contact-form-title">
			<div class="my-container">
				<div v-if="submitSuccess" class="contact-form-centered__success">
					<div class="contact-form-centered__success-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
							<path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z" />
						</svg>
					</div>
					<h2 class="contact-form-centered__title">Messaggio inviato</h2>
					<p class="contact-form-centered__success-text">Ti risponderemo in giornata. Nel frattempo puoi tracciare una spedizione o calcolare un preventivo.</p>
					<SfButton @click="submitSuccess = false">
						Invia un altro messaggio
					</SfButton>
				</div>

				<div v-else class="contact-form-centered__wrap">
					<header class="contact-form-centered__header">
						<span class="contact-form-centered__accent" aria-hidden="true"></span>
						<h2 id="contact-form-title" class="contact-form-centered__title">Scrivici</h2>
						<p class="contact-form-centered__lead">Descrivi la richiesta con peso, tratta e urgenza: piu contesto, risposta piu precisa.</p>
					</header>

					<form class="contact-form" novalidate @submit.prevent="handleSubmit">
						<div class="contact-form__grid contact-form__grid--two">
							<div class="contact-field">
								<label for="cf-name" class="contact-field__label">Nome</label>
								<input id="cf-name" v-model="contactForm.name" type="text" required autocomplete="given-name" class="contact-field__input" placeholder="Es. Mario" />
							</div>
							<div class="contact-field">
								<label for="cf-surname" class="contact-field__label">Cognome</label>
								<input id="cf-surname" v-model="contactForm.surname" type="text" required autocomplete="family-name" class="contact-field__input" placeholder="Es. Rossi" />
							</div>
						</div>

						<div class="contact-form__grid contact-form__grid--two">
							<div class="contact-field">
								<label for="cf-email" class="contact-field__label">Email</label>
								<input id="cf-email" v-model="contactForm.email" type="email" required autocomplete="email" class="contact-field__input" placeholder="nome@email.it" />
							</div>
							<div class="contact-field">
								<label for="cf-phone" class="contact-field__label">Telefono <span class="contact-field__hint">(opzionale)</span></label>
								<input id="cf-phone" v-model="contactForm.telephone_number" type="tel" autocomplete="tel" class="contact-field__input" placeholder="+39 ..." />
							</div>
						</div>

						<div class="contact-field">
							<label for="cf-message" class="contact-field__label">Messaggio <span class="contact-field__hint">(per reclami: indica "Reclamo" e numero spedizione BRT)</span></label>
							<textarea id="cf-message" v-model="contactForm.message" required rows="6" maxlength="1500" class="contact-field__textarea" placeholder="Racconta la richiesta con dettagli utili (tratta, peso, urgenza). Per un reclamo: inizia con &quot;Reclamo&quot; e allega numero spedizione."></textarea>
						</div>

						<div class="contact-form__turnstile" aria-label="Verifica anti-bot">
							<NuxtTurnstile v-model="turnstile.token.value" @expired="turnstile.onExpire" @error="turnstile.onError" />
						</div>

						<p v-if="submitError" class="contact-form__error" role="alert">{{ submitError }}</p>

						<SfButton type="submit" size="lg" class="contact-form__cta" :loading="isSubmitting" :disabled="!turnstile.isReady.value">
							<span v-if="!isSubmitting">Invia richiesta</span>
							<span v-else>Invio in corso...</span>
						</SfButton>
					</form>
				</div>
			</div>
		</section>

		<!-- ── FAQ grid editoriale ── -->
		<section class="contact-faq-grid" aria-labelledby="contact-faq-title">
			<div class="my-container">
				<header class="contact-faq-grid__header">
					<span class="contact-faq-grid__accent" aria-hidden="true"></span>
					<h2 id="contact-faq-title" class="contact-faq-grid__title">Domande frequenti</h2>
					<p class="contact-faq-grid__lead">Prima di scriverci, dai un'occhiata: molte risposte sono qui.</p>
				</header>
				<ContactFAQ />
			</div>
		</section>

		<!-- ── Quick next: 3 strip orizzontali per azione rapida ── -->
		<section class="contact-quick-next" aria-label="Prossime azioni utili">
			<div class="my-container">
				<div class="contact-quick-next__grid">
					<NuxtLink
						v-for="action in quickActions"
						:key="action.href"
						:to="action.href"
						class="contact-quick-next__strip">
						<span class="contact-quick-next__icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
								<path :d="action.icon" />
							</svg>
						</span>
						<span class="contact-quick-next__body">
							<span class="contact-quick-next__title">{{ action.title }}</span>
							<span class="contact-quick-next__text">{{ action.text }}</span>
						</span>
						<span class="contact-quick-next__arrow" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M5 12h14" />
								<path d="m12 5 7 7-7 7" />
							</svg>
						</span>
					</NuxtLink>
				</div>
			</div>
		</section>
	</div>
</template>
