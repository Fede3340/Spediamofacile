<!--
  PAGINA: Contatti (contatti.vue)
  Design allineato al Prototipo: icon badge header, due colonne [1fr_1.2fr],
  contact info cards (NO accent bar), form card CON accent bar (azione primaria).
  Form inline, grey block per i campi, button orange rounded-full.

  API: POST /api/contact
-->
<script setup>
useSeoMeta({
	title: 'Contatti | SpediamoFacile - Assistenza e Supporto',
	ogTitle: 'Contatti | SpediamoFacile',
	description: 'Hai bisogno di aiuto? Contatta il team di SpediamoFacile per assistenza sulle tue spedizioni, preventivi personalizzati o informazioni sui nostri servizi.',
	ogDescription: 'Contatta SpediamoFacile per assistenza e supporto sulle tue spedizioni.',
});

useHead({
	script: [{
		type: 'application/ld+json',
		innerHTML: JSON.stringify({
			'@context': 'https://schema.org', '@type': 'ContactPage',
			name: 'Contatti SpediamoFacile', url: 'https://spediamofacile.it/contatti',
			mainEntity: {
				'@type': 'Organization', name: 'SpediamoFacile', url: 'https://spediamofacile.it',
				contactPoint: { '@type': 'ContactPoint', contactType: 'customer service', availableLanguage: 'Italian' },
			},
		}),
	}],
});

const sanctum = useSanctumClient();

const contactForm = ref({
	name: '', surname: '', email: '', telephone_number: '', message: '',
});

const isSubmitting = ref(false);
const submitSuccess = ref(false);
const submitError = ref(null);

const resetForm = () => {
	contactForm.value = { name: '', surname: '', email: '', telephone_number: '', message: '' };
};

const handleSubmit = async () => {
	submitError.value = null;
	isSubmitting.value = true;
	try {
		await sanctum('/sanctum/csrf-cookie');
		await sanctum('/api/contact', { method: 'POST', body: contactForm.value });
		submitSuccess.value = true;
		resetForm();
	} catch (error) {
		const data = error?.response?._data || error?.data;
		if (data?.errors) {
			const firstError = Object.values(data.errors)[0];
			submitError.value = Array.isArray(firstError) ? firstError[0] : firstError;
		} else {
			submitError.value = data?.message || "Errore durante l'invio. Riprova.";
		}
	} finally {
		isSubmitting.value = false;
	}
};

// Contatti di supporto per la colonna sinistra
const contactItems = [
	{
		icon: 'M20,4H4A2,2 0 0,0 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6A2,2 0 0,0 20,4M20,8L12,13L4,8V6L12,11L20,6V8Z',
		label: 'Email',
		value: 'info@spediamofacile.it',
		href: 'mailto:info@spediamofacile.it',
	},
	{
		icon: 'M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z',
		label: 'Telefono',
		value: '+39 02 1234 5678',
		href: 'tel:+390212345678',
	},
	{
		icon: 'M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z',
		label: 'Sede',
		value: 'Via Esempio 42, 20100 Milano',
		href: '#',
	},
	{
		icon: 'M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z',
		label: 'Orari',
		value: 'Lun-Ven 9:00–18:00',
		href: '#',
	},
];
</script>

<template>
	<div class="py-[32px] sm:py-[48px]" style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%); min-height: 100vh">
		<div class="my-container">

			<!-- Header centrato -->
			<div class="text-center max-w-[540px] mx-auto mb-[32px] sm:mb-[40px]">
				<div class="w-[48px] h-[48px] rounded-full flex items-center justify-center mx-auto mb-[14px]"
					style="background: linear-gradient(135deg, #095866, #0a7489); box-shadow: 0 4px 14px rgba(9,88,102,0.2)">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="white">
						<path d="M20,2H4A2,2 0 0,0 2,4V22L6,18H20A2,2 0 0,0 22,16V4A2,2 0 0,0 20,2M20,16H6L4,18V4H20V16Z" />
					</svg>
				</div>
				<h1 class="text-[#1d2738] text-[28px] sm:text-[36px] tracking-[-0.8px] font-montserrat" style="font-weight:800">
					Contattaci
				</h1>
				<p class="text-[#777] text-[15px] sm:text-[16px] mt-[8px] leading-[1.5]">
					Hai domande o bisogno di assistenza? Siamo qui per aiutarti.
				</p>
			</div>

			<!-- Successo invio -->
			<div v-if="submitSuccess"
				class="rounded-[22px] overflow-hidden mb-[24px] max-w-[680px] mx-auto"
				style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)">
				<div class="h-[4px]" style="background: linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)" />
				<div class="p-[24px] sm:p-[32px] flex flex-col sm:flex-row items-start sm:items-center gap-[16px]"
					style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
					<div class="w-[44px] h-[44px] rounded-[12px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
						<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#095866">
							<path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z" />
						</svg>
					</div>
					<div class="flex-1">
						<h2 class="text-[#1d2738] text-[17px]" style="font-weight:700">Messaggio inviato correttamente</h2>
						<p class="text-[#777] text-[14px] mt-[4px]">Grazie! Abbiamo ricevuto la tua richiesta e ti risponderemo al più presto.</p>
					</div>
					<button type="button"
						class="text-[#095866] text-[14px] shrink-0 hover:underline cursor-pointer"
						style="font-weight:600"
						@click="submitSuccess = false">
						Invia un altro
					</button>
				</div>
			</div>

			<!-- Layout due colonne -->
			<div v-else class="grid grid-cols-1 lg:grid-cols-[1fr_1.2fr] gap-[20px]">

				<!-- Colonna sinistra: info contatti (NO accent bar) -->
				<div class="flex flex-col gap-[12px]">
					<a
						v-for="contact in contactItems"
						:key="contact.label"
						:href="contact.href"
						class="rounded-[22px] p-[18px] flex items-center gap-[14px] no-underline transition-all duration-[350ms] hover:ring-[2px] hover:ring-[#095866]/50 hover:shadow-[0_4px_16px_rgba(9,88,102,0.06)]"
						style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04); background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)"
					>
						<div class="w-[44px] h-[44px] rounded-[12px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#095866">
								<path :d="contact.icon" />
							</svg>
						</div>
						<div>
							<span class="text-[#999] text-[11px] uppercase tracking-[0.4px] block" style="font-weight:600">{{ contact.label }}</span>
							<span class="text-[#1d2738] text-[14px] sm:text-[15px]" style="font-weight:600">{{ contact.value }}</span>
						</div>
					</a>

					<!-- Placeholder mappa -->
					<div class="rounded-[22px] flex-1 min-h-[180px] flex items-center justify-center"
						style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04); background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
						<div class="text-center">
							<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#095866" class="opacity-20 mx-auto mb-[6px]">
								<path d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z" />
							</svg>
							<span class="text-[#999] text-[13px]" style="font-weight:500">Milano, Italia</span>
						</div>
					</div>
				</div>

				<!-- Colonna destra: form CON accent bar (azione primaria) -->
				<div class="rounded-[22px] overflow-hidden"
					style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)">
					<!-- Accent bar teal -->
					<div class="h-[4px]" style="background: linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)" />

					<div class="p-[24px] sm:p-[30px]" style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
						<h2 class="text-[#1d2738] text-[18px] sm:text-[20px] tracking-[-0.3px] mb-[20px]" style="font-weight:700">
							Inviaci un messaggio
						</h2>

						<!-- Grey block per i campi del form -->
						<div class="rounded-[16px] p-[16px] sm:p-[20px]"
							style="background: #E6E9EE; box-shadow: inset 0 1px 2px rgba(0,0,0,0.04)">
							<form @submit.prevent="handleSubmit" class="flex flex-col gap-[12px]">
								<!-- Nome + Cognome -->
								<div class="grid grid-cols-1 sm:grid-cols-2 gap-[12px]">
									<div>
										<label class="text-[#777] text-[11px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Nome</label>
										<input
											v-model="contactForm.name"
											type="text"
											placeholder="Il tuo nome"
											required
											class="w-full h-[48px] sm:h-[50px] rounded-[12px] px-[14px] text-[15px] text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 placeholder:text-[#999] outline-none transition-all duration-200"
											style="font-weight:600"
										/>
									</div>
									<div>
										<label class="text-[#777] text-[11px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Cognome</label>
										<input
											v-model="contactForm.surname"
											type="text"
											placeholder="Il tuo cognome"
											class="w-full h-[48px] sm:h-[50px] rounded-[12px] px-[14px] text-[15px] text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 placeholder:text-[#999] outline-none transition-all duration-200"
											style="font-weight:600"
										/>
									</div>
								</div>
								<!-- Email + Telefono -->
								<div class="grid grid-cols-1 sm:grid-cols-2 gap-[12px]">
									<div>
										<label class="text-[#777] text-[11px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Email</label>
										<input
											v-model="contactForm.email"
											type="email"
											placeholder="nome@email.com"
											required
											class="w-full h-[48px] sm:h-[50px] rounded-[12px] px-[14px] text-[15px] text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 placeholder:text-[#999] outline-none transition-all duration-200"
											style="font-weight:600"
										/>
									</div>
									<div>
										<label class="text-[#777] text-[11px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Telefono</label>
										<input
											v-model="contactForm.telephone_number"
											type="tel"
											placeholder="+39 000 0000000"
											class="w-full h-[48px] sm:h-[50px] rounded-[12px] px-[14px] text-[15px] text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 placeholder:text-[#999] outline-none transition-all duration-200"
											style="font-weight:600"
										/>
									</div>
								</div>
								<!-- Messaggio -->
								<div>
									<label class="text-[#777] text-[11px] uppercase tracking-[0.4px] mb-[6px] block" style="font-weight:700">Messaggio</label>
									<textarea
										v-model="contactForm.message"
										rows="5"
										placeholder="Scrivi il tuo messaggio..."
										required
										class="w-full rounded-[12px] px-[14px] py-[14px] text-[15px] text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 placeholder:text-[#999] outline-none transition-all duration-200 resize-none"
										style="font-weight:600"
									></textarea>
								</div>

								<!-- Errore -->
								<div v-if="submitError"
									class="flex items-center gap-[8px] rounded-[10px] px-[12px] py-[10px] bg-[#FFF5F2] text-[#E44203] text-[13px]"
									style="font-weight:600">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
										<path d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z" />
									</svg>
									{{ submitError }}
								</div>

								<!-- Bottone invio -->
								<button
									type="submit"
									:disabled="isSubmitting"
									class="w-full h-[52px] sm:h-[54px] rounded-full text-white text-[15px] flex items-center justify-center gap-[8px] cursor-pointer transition-all duration-[350ms] hover:-translate-y-[2px] hover:shadow-[0_10px_32px_rgba(228,66,3,0.28)] active:scale-[0.985] disabled:opacity-70 disabled:cursor-wait disabled:hover:translate-y-0"
									:style="isSubmitting
										? 'font-weight:700; background: linear-gradient(135deg, #095866, #0a7489); box-shadow: 0 4px 14px rgba(9,88,102,0.2)'
										: 'font-weight:700; background: linear-gradient(135deg, #E44203, #c73600); box-shadow: 0 6px 24px rgba(228,66,3,0.22)'"
								>
									<svg v-if="isSubmitting" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
									<svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
									</svg>
									{{ isSubmitting ? 'Invio in corso...' : 'Invia messaggio' }}
								</button>
							</form>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</template>
