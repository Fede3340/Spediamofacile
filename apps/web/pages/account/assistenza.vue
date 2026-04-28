<script setup>
import { accountCardIcons } from '~/utils/account';

/* Richiede che l'utente sia autenticato */
definePageMeta({
	middleware: ['app-auth'],
});

useSeoMeta({
	title: 'Assistenza account',
	ogTitle: 'Assistenza account',
	description: 'Apri e consulta richieste di supporto dalla tua area account SpediamoFacile.',
	ogDescription: 'Centro assistenza account con richieste e supporto dedicato su SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const { user } = useSanctumAuth();
const sanctum = useSanctumClient();
const ticketSection = ref(null);
const subjectInput = ref(null);

/* Oggetto della richiesta di assistenza */
const subject = ref('');
/* Testo del messaggio di assistenza */
const message = ref('');
/* Indica se l'invio è in corso */
const isSending = ref(false);
/* Messaggio di conferma o errore dopo l'invio */
const feedback = ref(null);
const feedbackType = ref('success');
const submitDisabled = computed(() => isSending.value || !subject.value.trim() || !message.value.trim());

const supportChecklist = [
	"Indica il riferimento della spedizione o dell'ordine se esiste giÁ .",
	"Descrivi il problema in modo concreto: cosa vedi, cosa ti aspettavi, quando è successo.",
	'Se utile, aggiungi dettagli pratici per velocizzare la risposta del team.',
];

const supportQuickActions = computed(() => [
	{
		title: 'Segui una spedizione',
		description: 'Apri l elenco ordini per tracking, stato e dettagli recenti.',
		iconKey: 'truck-fast',
		actionLabel: 'Apri spedizioni',
		to: '/account/spedizioni',
		iconBg: '#ECF8F8',
		iconColor: '#095866',
	},
	{
		title: 'Problema con un ordine',
		description: 'Apri subito un ticket con riferimento ordine o spedizione.',
		iconKey: 'clipboard-list',
		actionLabel: 'Scrivi al team',
		action: 'ticket',
		iconBg: '#FFF4EE',
		iconColor: '#E44203',
	},
	{
		title: 'Contatto diretto',
		description: 'Rispondiamo via email all indirizzo collegato al tuo account.',
		iconKey: 'email',
		actionLabel: 'assistenza@spediamofacile.it',
		href: 'mailto:assistenza@spediamofacile.it',
		iconBg: '#ECF8F8',
		iconColor: '#095866',
	},
]);

const supportFacts = computed(() => [
	{
		label: 'Canale',
		value: 'Ticket diretto',
	},
	{
		label: 'Operativo',
		value: 'Lun - Ven · 9:00 - 18:00',
	},
]);

const supportReplyEmail = computed(() => user.value?.email || 'Email account non disponibile');

const jumpToTicket = async () => {
	await nextTick();
	ticketSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
	subjectInput.value?.focus?.();
};

/**
 * Invia la richiesta di assistenza autenticata.
 * I ticket vengono salvati nel database e resi visibili nel pannello admin.
 */
const handleSubmit = async () => {
	if (!subject.value.trim() || !message.value.trim()) {
		feedback.value = 'Compila tutti i campi obbligatori.';
		feedbackType.value = 'error';
		return;
	}

	isSending.value = true;
	feedback.value = null;

	try {
		await sanctum('/api/support-tickets', {
			method: 'POST',
			body: {
				subject: subject.value.trim(),
				message: message.value.trim(),
			},
		});
		feedback.value = 'Richiesta inviata con successo. La trovi subito nel pannello assistenza del team.';
		feedbackType.value = 'success';
		subject.value = '';
		message.value = '';
	} catch (error) {
		feedback.value =
			error?.response?._data?.message || error?.data?.message || 'Non siamo riusciti a inviare la richiesta. Riprova tra poco.';
		feedbackType.value = 'error';
	} finally {
		isSending.value = false;
	}
};
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-[20px] tablet:py-[24px] desktop:py-[28px]">
		<div class="my-container">
			<AccountPageHeader
				class="sf-account-shell-hero--compact"
				title="Assistenza"
				description="Apri un ticket dal tuo account o scegli il canale giusto prima di contattare il team."
				current="Assistenza">
				<template #actions>
					<div class="flex flex-wrap gap-[10px]">
						<NuxtLink
							to="/account/spedizioni"
							class="inline-flex min-h-[42px] items-center justify-center rounded-full border border-[rgba(9,88,102,0.12)] bg-white px-[16px] text-[0.8125rem] font-[700] text-[var(--color-brand-primary)] transition-colors hover:border-[rgba(9,88,102,0.22)] hover:bg-[rgba(9,88,102,0.04)]">
							Le mie spedizioni
						</NuxtLink>
						<button
							type="button"
							class="btn-primary btn-compact inline-flex min-h-[42px] items-center justify-center px-[18px] text-[0.8125rem]"
							@click="jumpToTicket">
							Apri ticket
						</button>
					</div>
				</template>
			</AccountPageHeader>

			<div class="grid gap-[18px] desktop:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] desktop:items-start sf-animate-in sf-animate-in-1">
				<div class="space-y-[18px]">
					<div
						class="rounded-[18px] bg-white p-[18px] tablet:p-[20px]"
						style="box-shadow: 0 2px 8px rgba(9,88,102,0.06), 0 0 0 1px rgba(9,88,102,0.04);">
						<div class="flex items-start justify-between gap-[12px]">
							<div>
								<p class="sf-section-kicker mb-[6px]">Supporto rapido</p>
								<h2 class="font-montserrat text-[1.125rem] font-[800] text-[var(--color-brand-text)]">Da dove vuoi partire?</h2>
								<p class="mt-[4px] max-w-[42rem] text-[0.875rem] leading-[1.65] text-[var(--color-brand-text-secondary)]">
									Le richieste più comuni passano da spedizioni, ticket diretto o contatto email: qui trovi il canale più rapido senza girare per l account.
								</p>
							</div>
							<div class="hidden tablet:flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-[14px] bg-[#F0F6F7] text-[var(--color-brand-primary)]">
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[20px] w-[20px]" fill="currentColor" v-html="accountCardIcons.headset"></svg>
							</div>
						</div>

						<div class="mt-[18px] grid gap-[12px]">
							<component
								:is="action.to ? 'NuxtLink' : action.href ? 'a' : 'button'"
								v-for="action in supportQuickActions"
								:key="action.title"
								:to="action.to"
								:href="action.href"
								:type="action.action ? 'button' : undefined"
								class="group rounded-[16px] border border-[rgba(9,88,102,0.08)] bg-[#FBFCFD] p-[16px] text-left transition-all duration-[220ms] hover:-translate-y-[1px] hover:border-[rgba(9,88,102,0.14)] hover:bg-white"
								@click="action.action === 'ticket' ? jumpToTicket() : undefined">
								<div class="flex items-start gap-[14px]">
									<div
										class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-[13px]"
										:style="{ background: action.iconBg, color: action.iconColor }">
										<svg
											aria-hidden="true"
											xmlns="http://www.w3.org/2000/svg"
											viewBox="0 0 24 24"
											class="h-[19px] w-[19px]"
											fill="currentColor"
											v-html="accountCardIcons[action.iconKey]"></svg>
									</div>
									<div class="min-w-0 flex-1">
										<h3 class="font-montserrat text-[0.9375rem] font-[800] text-[var(--color-brand-text)]">{{ action.title }}</h3>
										<p class="mt-[4px] text-[0.8125rem] leading-[1.6] text-[var(--color-brand-text-secondary)]">{{ action.description }}</p>
										<div class="mt-[12px] inline-flex items-center gap-[6px] text-[0.75rem] font-[700] text-[var(--color-brand-primary)]">
											<span>{{ action.actionLabel }}</span>
											<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[14px] w-[14px] transition-transform duration-[180ms] group-hover:translate-x-[1px]" fill="currentColor">
												<path d="M14,3L12.59,4.41L18.17,10H4V12H18.17L12.58,17.59L14,19L22,11L14,3Z" />
											</svg>
										</div>
									</div>
								</div>
							</component>
						</div>
					</div>

					<div
						class="rounded-[18px] bg-white p-[18px] tablet:p-[20px]"
						style="box-shadow: 0 2px 8px rgba(9,88,102,0.06), 0 0 0 1px rgba(9,88,102,0.04);">
						<div class="flex items-center gap-[12px]">
							<div class="flex h-[40px] w-[40px] items-center justify-center rounded-[13px] bg-[#F0F6F7] text-[var(--color-brand-primary)]">
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" v-html="accountCardIcons['clipboard-list']"></svg>
							</div>
							<div>
								<p class="sf-section-kicker mb-[4px]">Prima di inviare</p>
								<h2 class="font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)]">Cosa aiuta davvero il team</h2>
							</div>
						</div>

						<ul class="mt-[16px] space-y-[10px]">
							<li
								v-for="item in supportChecklist"
								:key="item"
								class="flex items-start gap-[10px] text-[0.84375rem] leading-[1.65] text-[var(--color-brand-text)]">
								<span class="mt-[2px] inline-flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full bg-[#F0F6F7] text-[var(--color-brand-primary)]">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[11px] w-[11px]" fill="currentColor">
										<path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" />
									</svg>
								</span>
								{{ item }}
							</li>
						</ul>
					</div>
				</div>

				<div
					ref="ticketSection"
					class="rounded-[18px] bg-white p-[18px] desktop:p-[24px]"
					style="box-shadow: 0 2px 8px rgba(9,88,102,0.06), 0 0 0 1px rgba(9,88,102,0.04);">
					<div class="flex flex-col gap-[16px] border-b border-[rgba(9,88,102,0.08)] pb-[16px]">
						<div class="flex items-start justify-between gap-[12px]">
							<div class="flex items-center gap-[12px]">
								<div class="flex h-[42px] w-[42px] items-center justify-center rounded-[13px] bg-[#F0F6F7] text-[var(--color-brand-primary)]">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[20px] w-[20px]" fill="currentColor" v-html="accountCardIcons.headset"></svg>
								</div>
								<div>
									<h2 class="font-montserrat text-[1.125rem] font-[800] text-[var(--color-brand-text)]">Apri un ticket</h2>
									<p class="mt-[4px] text-[0.84375rem] leading-[1.6] text-[var(--color-brand-text-secondary)]">Oggetto chiaro, contesto essenziale e risposta all email del tuo account.</p>
								</div>
							</div>
							<span class="inline-flex items-center rounded-full bg-[#F6FAFB] px-[10px] py-[6px] text-[0.6875rem] font-[700] text-[var(--color-brand-primary)]">
								Ticket diretto
							</span>
						</div>

						<div class="grid gap-[10px] tablet:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
							<div
								v-for="fact in supportFacts"
								:key="fact.label"
								class="rounded-[14px] border border-[rgba(9,88,102,0.08)] bg-[#FBFCFD] px-[14px] py-[12px]">
								<p class="text-[0.6875rem] font-[700] uppercase tracking-[0.9px] text-[var(--color-brand-text-muted)]">{{ fact.label }}</p>
								<p class="mt-[6px] break-words text-[0.875rem] font-[700] text-[var(--color-brand-text)]">{{ fact.value }}</p>
							</div>
						</div>

						<div class="rounded-[14px] border border-[rgba(9,88,102,0.08)] bg-[#FBFCFD] px-[14px] py-[12px]">
							<p class="text-[0.6875rem] font-[700] uppercase tracking-[0.9px] text-[var(--color-brand-text-muted)]">Risposta</p>
							<p class="mt-[6px] break-all text-[0.875rem] font-[700] text-[var(--color-brand-text)]">{{ supportReplyEmail }}</p>
						</div>
					</div>

					<div class="mt-[18px]">
						<label class="form-label">Oggetto *</label>
						<input
							ref="subjectInput"
							v-model="subject"
							type="text"
							placeholder="Es. Problema con la spedizione #1234"
							class="form-input" />
					</div>

					<div class="mt-[16px]">
						<label class="form-label">Messaggio *</label>
						<textarea
							v-model="message"
							rows="6"
							placeholder="Descrivi in modo semplice cosa succede, quando è successo e quale riferimento dobbiamo controllare."
							class="form-input resize-none"></textarea>
					</div>

					<div v-if="feedback" :class="['mt-[16px] ux-alert', feedbackType === 'success' ? 'ux-alert--success' : 'ux-alert--critical']">
						<svg
							aria-hidden="true"
							v-if="feedbackType === 'success'"
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							class="ux-alert__icon shrink-0"
							fill="currentColor">
							<path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" />
						</svg>
						<svg aria-hidden="true" v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ux-alert__icon shrink-0" fill="currentColor">
							<path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
						</svg>
						<span>{{ feedback }}</span>
					</div>

					<div class="mt-[18px] flex flex-col gap-[12px] tablet:flex-row tablet:items-center tablet:justify-between">
						<p class="text-[0.8125rem] leading-[1.6] text-[var(--color-brand-text-secondary)]">
							Se stai segnalando una spedizione, inserisci sempre il riferimento ordine o tracking per ridurre i tempi di presa in carico.
						</p>

						<button
							@click="handleSubmit"
							:disabled="submitDisabled"
							class="btn-primary btn-compact inline-flex min-h-[46px] w-full items-center justify-center gap-[8px] px-[18px] text-[0.9375rem] tablet:w-auto">
							<svg aria-hidden="true" v-if="!isSending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor">
								<path d="M2,21L23,12L2,3V10L17,12L2,14V21Z" />
							</svg>
							{{ isSending ? 'Invio in corso...' : 'Invia richiesta' }}
						</button>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>

