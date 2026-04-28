<!-- FILE: pages/account/amministrazione/impostazioni.vue -->
<script setup>
definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Impostazioni admin',
	ogTitle: 'Impostazioni admin',
	description: 'Gestisci configurazione Stripe, BRT e parametri generali dal pannello admin SpediamoFacile.',
	ogDescription: 'Configurazione tecnica di Stripe, BRT e impostazioni generali nel pannello admin SpediamoFacile.',
	robots: 'noindex, nofollow',
});

useHead({
	title: 'Impostazioni admin',
});

const sanctum = useSanctumClient();
const { actionMessage, showSuccess, showError } = useAdmin();

const settingsData = ref({});
const savingSettings = ref(false);
const appOrigin = ref('');
const stripeWebhookUrl = computed(() => (appOrigin.value ? `${appOrigin.value}/stripe/webhook` : '/stripe/webhook'));

const fetchSettings = async () => {
	try {
		const res = await sanctum('/api/admin/settings');
		settingsData.value = res?.data || res || {};
	} catch (e) {
		settingsData.value = {};
	}
};

const saveSettings = async () => {
	savingSettings.value = true;
	try {
		await sanctum('/api/admin/settings', { method: 'POST', body: settingsData.value });
		showSuccess('Impostazioni salvate con successo.');
	} catch (e) {
		showError(e, 'Errore durante il salvataggio.');
	} finally {
		savingSettings.value = false;
	}
};

onMounted(() => {
	appOrigin.value = String(window.location.origin || appOrigin.value || '').replace(/\/$/, '');
	fetchSettings();
});
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-[24px] tablet:py-[28px] desktop:py-[28px]">
		<div class="my-container sf-stack-section">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Impostazioni"
				description="Configurazione tecnica di Stripe, BRT e parametri generali in una pagina unica, piu ordinata e meno dispersiva."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Impostazioni' },
				]" />

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<!-- Banner intro rimosso (P13: AccountPageHeader sopra ha già titolo + descrizione + chip).
			     Era duplicazione "meta-page-header doppio" (audit). -->

			<div class="grid grid-cols-1 desktop:grid-cols-2 gap-[16px]">
				<div
					class="rounded-[18px] bg-white ring-[1px] ring-[#DFE2E7] p-[18px] tablet:p-[20px] desktop:p-[22px] overflow-hidden"
					style="box-shadow: 0 2px 12px rgba(9,88,102,0.08)">
					<div class="mb-[18px]">
						<h2 class="text-[16px] font-bold text-[#1d2738] font-['Montserrat',sans-serif] flex items-center gap-[10px]">
							<div class="w-[38px] h-[38px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] text-[#095866]" fill="currentColor">
									<path d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" />
								</svg>
							</div>
							Configurazione Stripe
						</h2>
						<p class="text-[0.875rem] text-[#5A6474] mt-[8px] leading-[1.65]">
							Le chiavi qui sotto pilotano checkout, ricariche wallet e salvataggio carte. Teniamo il contenuto essenziale e la gerarchia molto chiara.
						</p>
					</div>

					<!-- P14: explainer "Controlli rapidi" rimosso (era matryoshka).
					     Webhook URL spostato come hint inline nel form sotto. -->
					<p class="mb-[14px] text-[0.75rem] text-[var(--color-brand-text-muted)] leading-[1.5]">
						Webhook Stripe da configurare:
						<code class="font-mono text-[0.6875rem] bg-[#F5F8FA] px-[6px] py-[2px] rounded-[4px] break-all">{{ stripeWebhookUrl }}</code>
					</p>

					<div class="space-y-[14px] max-w-[640px]">
						<div>
							<label class="form-label">Public Key</label>
							<input
								v-model="settingsData.stripe_public_key"
								type="text"
								class="form-input font-mono"
								placeholder="pk_..." />
						</div>
						<div>
							<label class="form-label">Secret Key</label>
							<input
								v-model="settingsData.stripe_secret_key"
								type="password"
								class="form-input font-mono"
								placeholder="sk_..." />
						</div>
						<div>
							<label class="form-label">Webhook Secret</label>
							<input
								v-model="settingsData.stripe_webhook_secret"
								type="password"
								class="form-input font-mono"
								placeholder="whsec_..." />
						</div>
					</div>
				</div>

				<div
					class="rounded-[18px] bg-white ring-[1px] ring-[#DFE2E7] p-[18px] tablet:p-[20px] desktop:p-[22px] overflow-hidden"
					style="box-shadow: 0 2px 12px rgba(9,88,102,0.08)">
					<div class="mb-[18px]">
						<h2 class="text-[16px] font-bold text-[#1d2738] font-['Montserrat',sans-serif] flex items-center gap-[10px]">
							<div class="w-[38px] h-[38px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] text-[#095866]" fill="currentColor">
									<path d="M18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5M19.5,9.5L21.46,12H17V9.5M6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5M20,8H17V4H3C1.89,4 1,4.89 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8Z" />
								</svg>
							</div>
							Configurazione BRT
						</h2>
						<p class="text-[0.875rem] text-[#5A6474] mt-[8px] leading-[1.65]">
							Questa sezione contiene solo le credenziali operative del corriere reale della piattaforma. Nessun altro corriere deve entrare in questa pagina.
						</p>
					</div>

					<!-- P14: explainer "Prima di salvare" rimosso (era matryoshka). -->
					<p class="mb-[14px] text-[0.75rem] text-[var(--color-brand-text-muted)] leading-[1.5]">
						Queste credenziali impattano creazione spedizioni, tracking ed etichette.
					</p>

					<div class="space-y-[14px] max-w-[640px]">
						<div>
							<label class="form-label">Customer ID</label>
							<input
								v-model="settingsData.brt_customer_id"
								type="text"
								class="form-input" />
						</div>
						<div>
							<label class="form-label">Username</label>
							<input
								v-model="settingsData.brt_username"
								type="text"
								class="form-input" />
						</div>
						<div>
							<label class="form-label">Password</label>
							<input
								v-model="settingsData.brt_password"
								type="password"
								class="form-input" />
						</div>
					</div>
				</div>

				<div
					class="rounded-[18px] bg-white ring-[1px] ring-[#DFE2E7] p-[18px] tablet:p-[20px] desktop:p-[22px] desktop:col-span-2 overflow-hidden"
					style="box-shadow: 0 2px 12px rgba(9,88,102,0.08)">
					<div class="mb-[18px]">
						<h2 class="text-[16px] font-bold text-[#1d2738] font-['Montserrat',sans-serif] flex items-center gap-[10px]">
							<div class="w-[38px] h-[38px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] text-[#095866]" fill="currentColor">
									<path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.21,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.21,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.04 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.67 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.04 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z" />
								</svg>
							</div>
							Impostazioni generali
						</h2>
						<p class="text-[0.875rem] text-[#5A6474] mt-[8px] leading-[1.65]">
							Qui teniamo solo i parametri davvero utili al team: nome sito, contatto supporto e sovrapprezzo contrassegno.
						</p>
					</div>

					<div class="grid grid-cols-1 desktop:grid-cols-3 gap-[14px] max-w-[1100px]">
						<div>
							<label class="form-label">Nome sito</label>
							<input
								v-model="settingsData.site_name"
								type="text"
								class="form-input" />
						</div>
						<div>
							<label class="form-label">Email supporto</label>
							<input
								v-model="settingsData.support_email"
								type="email"
								class="form-input" />
						</div>
						<div>
							<label class="form-label">Sovrapprezzo contrassegno (&euro;)</label>
							<input
								v-model="settingsData.cod_surcharge"
								type="text"
								class="form-input"
								placeholder="3.50" />
						</div>
					</div>
				</div>
			</div>

			<div
				class="rounded-[18px] bg-white ring-[1px] ring-[#DFE2E7] px-[18px] py-[16px] flex flex-col gap-[10px] tablet:flex-row tablet:items-center tablet:justify-between"
				style="box-shadow: 0 2px 12px rgba(9,88,102,0.08)">
				<div>
					<p class="text-[0.875rem] font-semibold text-[#1d2738]">Salvataggio finale</p>
					<p class="text-[0.8125rem] text-[#5A6474] mt-[2px]">Salva dopo aver verificato tutte le sezioni. Le chiavi restano lato server e non vanno duplicate altrove.</p>
				</div>
				<button
					@click="saveSettings"
					:disabled="savingSettings"
					class="inline-flex w-full items-center justify-center gap-[8px] tablet:w-auto h-[40px] px-[18px] rounded-full bg-gradient-to-r from-[#095866] to-[#0a6e7f] text-white text-[13px] font-semibold shadow-[0_2px_8px_rgba(9,88,102,0.25)] hover:shadow-[0_4px_14px_rgba(9,88,102,0.35)] transition-all disabled:opacity-50 cursor-pointer">
					<svg
						aria-hidden="true"
						v-if="savingSettings"
						xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 24 24"
						class="w-[18px] h-[18px] animate-spin"
						fill="currentColor">
						<path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z" />
					</svg>
					<svg aria-hidden="true" v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px]" fill="currentColor">
						<path d="M15,9H5V5H15M12,19A3,3 0 0,1 9,16A3,3 0 0,1 12,13A3,3 0 0,1 15,16A3,3 0 0,1 12,19M17,3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3Z" />
					</svg>
					{{ savingSettings ? 'Salvataggio...' : 'Salva impostazioni' }}
				</button>
			</div>
		</div>
	</section>
</template>
