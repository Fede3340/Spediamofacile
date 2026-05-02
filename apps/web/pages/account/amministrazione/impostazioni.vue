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
	} catch {
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
	<section class="sf-account-shell min-h-[600px] py-6 md:py-8">
		<div class="max-w-7xl mx-auto px-4 md:px-6 space-y-6 md:space-y-8">
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

			<div class="grid grid-cols-1 desktop:grid-cols-2 gap-4 md:gap-6">
				<SfCard padding="md">
					<template #header>
						<div class="flex items-center gap-3">
							<div class="w-10 h-10 rounded-control bg-brand-soft-bg flex items-center justify-center shrink-0">
								<UIcon name="mdi:credit-card-outline" class="w-5 h-5 text-brand-primary" />
							</div>
							<div>
								<h2 class="font-display text-lg font-bold text-brand-text">Configurazione Stripe</h2>
								<p class="text-sm text-brand-text-secondary mt-0.5">
									Le chiavi qui sotto pilotano checkout, ricariche wallet e salvataggio carte.
								</p>
							</div>
						</div>
					</template>

					<p class="mb-4 text-xs text-brand-text-muted leading-relaxed">
						Webhook Stripe da configurare:
						<code class="font-mono text-[0.6875rem] bg-brand-bg-alt px-1.5 py-0.5 rounded break-all">{{ stripeWebhookUrl }}</code>
					</p>

					<div class="space-y-4 max-w-[640px]">
						<SfFormGroup label="Public Key">
							<SfInput v-model="settingsData.stripe_public_key" type="text" placeholder="pk_..." />
						</SfFormGroup>
						<SfFormGroup label="Secret Key">
							<SfInput v-model="settingsData.stripe_secret_key" type="password" placeholder="sk_..." />
						</SfFormGroup>
						<SfFormGroup label="Webhook Secret">
							<SfInput v-model="settingsData.stripe_webhook_secret" type="password" placeholder="whsec_..." />
						</SfFormGroup>
					</div>
				</SfCard>

				<SfCard padding="md">
					<template #header>
						<div class="flex items-center gap-3">
							<div class="w-10 h-10 rounded-control bg-brand-soft-bg flex items-center justify-center shrink-0">
								<UIcon name="mdi:truck-outline" class="w-5 h-5 text-brand-primary" />
							</div>
							<div>
								<h2 class="font-display text-lg font-bold text-brand-text">Configurazione BRT</h2>
								<p class="text-sm text-brand-text-secondary mt-0.5">
									Credenziali operative del corriere reale della piattaforma.
								</p>
							</div>
						</div>
					</template>

					<p class="mb-4 text-xs text-brand-text-muted leading-relaxed">
						Queste credenziali impattano creazione spedizioni, tracking ed etichette.
					</p>

					<div class="space-y-4 max-w-[640px]">
						<SfFormGroup label="Customer ID">
							<SfInput v-model="settingsData.brt_customer_id" type="text" />
						</SfFormGroup>
						<SfFormGroup label="Username">
							<SfInput v-model="settingsData.brt_username" type="text" />
						</SfFormGroup>
						<SfFormGroup label="Password">
							<SfInput v-model="settingsData.brt_password" type="password" />
						</SfFormGroup>
					</div>
				</SfCard>

				<SfCard padding="md" class="desktop:col-span-2">
					<template #header>
						<div class="flex items-center gap-3">
							<div class="w-10 h-10 rounded-control bg-brand-soft-bg flex items-center justify-center shrink-0">
								<UIcon name="mdi:cog-outline" class="w-5 h-5 text-brand-primary" />
							</div>
							<div>
								<h2 class="font-display text-lg font-bold text-brand-text">Impostazioni generali</h2>
								<p class="text-sm text-brand-text-secondary mt-0.5">
									Parametri davvero utili al team: nome sito, contatto supporto e sovrapprezzo contrassegno.
								</p>
							</div>
						</div>
					</template>

					<div class="grid grid-cols-1 desktop:grid-cols-3 gap-4 max-w-[1100px]">
						<SfFormGroup label="Nome sito">
							<SfInput v-model="settingsData.site_name" type="text" />
						</SfFormGroup>
						<SfFormGroup label="Email supporto">
							<SfInput v-model="settingsData.support_email" type="email" />
						</SfFormGroup>
						<SfFormGroup label="Sovrapprezzo contrassegno (&euro;)">
							<SfInput v-model="settingsData.cod_surcharge" type="text" placeholder="3.50" />
						</SfFormGroup>
					</div>
				</SfCard>
			</div>

			<SfCard padding="md">
				<div class="flex flex-col gap-3 tablet:flex-row tablet:items-center tablet:justify-between">
					<div>
						<p class="text-sm font-semibold text-brand-text">Salvataggio finale</p>
						<p class="text-sm text-brand-text-secondary mt-0.5">
							Salva dopo aver verificato tutte le sezioni. Le chiavi restano lato server e non vanno duplicate altrove.
						</p>
					</div>
					<SfButton :loading="savingSettings" :disabled="savingSettings" @click="saveSettings">
						<template #leading>
							<UIcon v-if="!savingSettings" name="mdi:content-save" class="w-4 h-4" />
						</template>
						{{ savingSettings ? 'Salvataggio...' : 'Salva impostazioni' }}
					</SfButton>
				</div>
			</SfCard>
		</div>
	</section>
</template>
