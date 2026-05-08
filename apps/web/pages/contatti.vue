<script setup>
// Pagina Contatti — hero + canali + form + FAQ.
// Niente CTA quick-next: già coperti dalla navbar (Preventivo, Traccia, Guide).

useSeoMeta({
	title: 'Contatti - Assistenza e Supporto',
	ogTitle: 'Contatti',
	description: 'Hai bisogno di aiuto? Contatta il team di SpediamoFacile per assistenza sulle tue spedizioni, preventivi personalizzati o informazioni sui nostri servizi.',
	ogDescription: 'Contatta SpediamoFacile per assistenza e supporto sulle tue spedizioni.',
});

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
});

useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Contatti' },
]);

const sanctum = useSanctumClient();
const turnstile = useTurnstile();

const form = ref({ name: '', surname: '', email: '', telephone_number: '', message: '' });
const isSubmitting = ref(false);
const submitSuccess = ref(false);
const submitError = ref(null);

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
];

const resetForm = () => {
	form.value = { name: '', surname: '', email: '', telephone_number: '', message: '' };
	turnstile.reset();
};

const handleSubmit = async () => {
	submitError.value = null;
	if (!turnstile.isReady.value) {
		submitError.value = 'Conferma di non essere un bot per inviare il messaggio.';
		return;
	}
	isSubmitting.value = true;
	try {
		await sanctum('/sanctum/csrf-cookie');
		await sanctum('/api/contact', {
			method: 'POST',
			body: { ...form.value, ...turnstile.payload() },
		});
		submitSuccess.value = true;
		resetForm();
	} catch (error) {
		const data = error?.response?._data || error?.data;
		const fieldErrors = data?.errors ? Object.values(data.errors)[0] : null;
		submitError.value = (Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors)
			|| data?.message
			|| "Errore durante l'invio. Riprova.";
		turnstile.reset();
	} finally {
		isSubmitting.value = false;
	}
};
</script>

<template>
	<div class="contatti-page">
		<PublicPageHeader
			eyebrow="Assistenza e supporto"
			title="Parla con noi"
			description="Rispondiamo in giornata a email, telefono o messaggio. Nessun bot, solo persone che conoscono il mondo BRT."
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Contatti' }]" />

		<!-- Canali di contatto: 4 card icona + label + valore -->
		<section class="contatti-section" aria-label="Canali di contatto">
			<div class="my-container">
				<div class="contatti-channels">
					<component
						:is="channel.href ? 'a' : 'div'"
						v-for="channel in channels"
						:key="channel.label"
						:href="channel.href || undefined"
						:target="channel.href?.startsWith('http') ? '_blank' : undefined"
						:rel="channel.href?.startsWith('http') ? 'noopener noreferrer' : undefined"
						class="contatti-channel">
						<span class="contatti-channel__icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
								<path :d="channel.icon" />
							</svg>
						</span>
						<span class="contatti-channel__label">{{ channel.label }}</span>
						<span class="contatti-channel__value">{{ channel.value }}</span>
					</component>
				</div>
			</div>
		</section>

		<!-- Form di contatto -->
		<section class="contatti-section" aria-labelledby="contatti-form-title">
			<div class="my-container">
				<div v-if="submitSuccess" class="contatti-card contatti-card--success">
					<div class="contatti-card__icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
							<path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z" />
						</svg>
					</div>
					<h2 class="contatti-card__title">Messaggio inviato</h2>
					<p class="contatti-card__text">Ti risponderemo in giornata. Nel frattempo puoi tracciare una spedizione o calcolare un preventivo.</p>
					<SfButton @click="submitSuccess = false">Invia un altro messaggio</SfButton>
				</div>

				<div v-else class="contatti-card">
					<header class="contatti-card__head">
						<span class="contatti-card__accent" aria-hidden="true" />
						<h2 id="contatti-form-title" class="contatti-card__title">Scrivici</h2>
						<p class="contatti-card__text">Descrivi la richiesta con peso, tratta e urgenza: più contesto, risposta più precisa.</p>
					</header>

					<form class="contatti-form" novalidate @submit.prevent="handleSubmit">
						<div class="contatti-form__row">
							<SfFormGroup label="Nome">
								<SfInput id="cf-name" v-model="form.name" type="text" required autocomplete="given-name" placeholder="Es. Mario" />
							</SfFormGroup>
							<SfFormGroup label="Cognome">
								<SfInput id="cf-surname" v-model="form.surname" type="text" required autocomplete="family-name" placeholder="Es. Rossi" />
							</SfFormGroup>
						</div>

						<div class="contatti-form__row">
							<SfFormGroup label="Email">
								<SfInput id="cf-email" v-model="form.email" type="email" required autocomplete="email" placeholder="nome@email.it" />
							</SfFormGroup>
							<SfFormGroup label="Telefono" hint="(opzionale)">
								<SfInput id="cf-phone" v-model="form.telephone_number" type="tel" autocomplete="tel" placeholder="+39 ..." />
							</SfFormGroup>
						</div>

						<SfFormGroup label="Messaggio" hint='(per reclami: indica "Reclamo" e numero spedizione BRT)'>
							<SfTextarea
								id="cf-message"
								v-model="form.message"
								required
								:rows="6"
								:maxlength="1500"
								placeholder='Racconta la richiesta con dettagli utili (tratta, peso, urgenza). Per un reclamo: inizia con "Reclamo" e allega numero spedizione.' />
						</SfFormGroup>

						<div class="contatti-form__captcha" aria-label="Verifica anti-bot">
							<NuxtTurnstile v-model="turnstile.token.value" @expired="turnstile.onExpire" @error="turnstile.onError" />
						</div>

						<p v-if="submitError" class="contatti-form__error" role="alert">{{ submitError }}</p>

						<SfButton
							type="submit"
							size="lg"
							class="contatti-form__submit"
							:loading="isSubmitting"
							:disabled="!turnstile.isReady.value">
							{{ isSubmitting ? 'Invio in corso...' : 'Invia richiesta' }}
						</SfButton>
					</form>
				</div>
			</div>
		</section>

		<!-- FAQ inline -->
		<section class="contatti-section" aria-labelledby="contatti-faq-title">
			<div class="my-container">
				<header class="contatti-faq-head">
					<span class="contatti-card__accent" aria-hidden="true" />
					<h2 id="contatti-faq-title">Domande frequenti</h2>
					<p>Prima di scriverci, dai un'occhiata: molte risposte sono qui.</p>
				</header>
				<ContactFAQ />
			</div>
		</section>
	</div>
</template>

<style scoped>
.contatti-page {
	min-height: 100vh;
	padding-bottom: 64px;
	background: var(--gradient-page-surface);
}
.contatti-section + .contatti-section { margin-top: 32px; }

/* Canali */
.contatti-channels {
	display: grid;
	gap: 16px;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}
.contatti-channel {
	display: grid;
	gap: 8px;
	padding: 20px;
	background: var(--color-brand-card);
	border: 1px solid rgba(9, 88, 102, 0.12);
	border-radius: 18px;
	color: inherit;
	text-decoration: none;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.04);
	transition: transform 200ms ease, border-color 200ms ease, box-shadow 200ms ease;
}
a.contatti-channel:hover {
	transform: translateY(-2px);
	border-color: rgba(9, 88, 102, 0.3);
	box-shadow: 0 8px 24px rgba(9, 88, 102, 0.08);
}
.contatti-channel__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 44px;
	height: 44px;
	border-radius: 14px;
	color: var(--color-brand-primary);
	background: linear-gradient(135deg, rgba(9, 88, 102, 0.15) 0%, rgba(9, 88, 102, 0.05) 100%);
}
.contatti-channel__label {
	font-size: 0.6875rem;
	font-weight: 700;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color: var(--color-brand-text-secondary);
}
.contatti-channel__value {
	font-size: 0.9375rem;
	font-weight: 600;
	color: var(--color-brand-primary);
	word-break: break-word;
}

/* Card form / success */
.contatti-card {
	width: 100%;
	padding: 32px;
	background: var(--color-brand-card);
	border: 1px solid rgba(9, 88, 102, 0.12);
	border-radius: 18px;
	box-shadow: 0 4px 16px rgba(9, 88, 102, 0.05), 0 12px 32px rgba(9, 88, 102, 0.04);
}
@media (min-width: 768px) {
	.contatti-card { padding: 36px 40px; }
}
.contatti-card--success {
	display: grid;
	gap: 12px;
	justify-items: center;
	text-align: center;
}
.contatti-card__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 56px;
	height: 56px;
	border-radius: 999px;
	color: var(--color-brand-primary);
	background: linear-gradient(135deg, rgba(9, 88, 102, 0.2) 0%, rgba(9, 88, 102, 0.05) 100%);
}
.contatti-card__head {
	display: grid;
	gap: 8px;
	justify-items: center;
	margin-bottom: 24px;
	text-align: center;
}
.contatti-card__accent {
	display: block;
	width: 48px;
	height: 3px;
	border-radius: 999px;
	background: var(--color-brand-accent);
}
.contatti-card__title {
	margin: 0;
	font-family: var(--font-display, 'Montserrat', sans-serif);
	font-size: 1.625rem;
	font-weight: 800;
	line-height: 1.1;
	letter-spacing: -0.025em;
	color: var(--color-brand-primary);
}
.contatti-card__text {
	margin: 0;
	max-width: 48ch;
	font-size: 0.95rem;
	line-height: 1.55;
	color: var(--color-brand-text-secondary);
}

/* Form */
.contatti-form {
	display: grid;
	gap: 24px;
}
.contatti-form__row {
	display: grid;
	gap: 16px;
}
@media (min-width: 768px) {
	.contatti-form__row { grid-template-columns: 1fr 1fr; }
}
.contatti-form__captcha {
	display: flex;
	justify-content: center;
}
.contatti-form__error {
	margin: 0;
	padding: 12px;
	border: 1px solid rgba(228, 66, 3, 0.3);
	background: rgba(228, 66, 3, 0.1);
	border-radius: 14px;
	color: var(--color-brand-error);
	font-size: 0.875rem;
	font-weight: 600;
}
.contatti-form__submit {
	width: 100%;
	justify-content: center;
}
@media (min-width: 768px) {
	.contatti-form__submit {
		width: auto;
		min-width: 220px;
		margin-left: auto;
	}
}

/* FAQ head */
.contatti-faq-head {
	display: grid;
	gap: 10px;
	justify-items: center;
	margin-bottom: 24px;
	text-align: center;
}
.contatti-faq-head h2 {
	margin: 0;
	font-family: var(--font-display, 'Montserrat', sans-serif);
	font-size: 1.75rem;
	font-weight: 800;
	line-height: 1.1;
	letter-spacing: -0.025em;
	color: var(--color-brand-primary);
}
.contatti-faq-head p {
	margin: 0;
	max-width: 56ch;
	font-size: 0.95rem;
	color: var(--color-brand-text-secondary);
}
</style>
