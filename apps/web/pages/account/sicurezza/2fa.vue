<script setup>
/**
 * Pagina setup 2FA admin (TOTP - Time-based One-Time Password).
 * Wizard 3 step: Enable (QR) → Confirm (6 cifre) → Recovery codes.
 * Stato iniziale: status corrente (attivo/non attivo) con disable.
 */
import QRCode from 'qrcode';

definePageMeta({
	middleware: ['app-auth'],
});

useSeoMeta({
	title: 'Sicurezza account · 2FA',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();
const { user, refreshIdentity } = useSanctumAuth();
const { message: feedback, showSuccess, showError } = useFlashMessage();

// Stato locale
const wizardStep = ref('initial'); // initial | qr | verify | recovery | disable
const isLoading = ref(false);
const qrSecret = ref('');
const qrUrl = ref('');
const qrSvg = ref('');
const inputCode = ref('');
const recoveryCodes = ref([]);
const showSecretText = ref(false);
const disablePassword = ref('');

const has2faActive = computed(() => Boolean(user.value?.two_factor_confirmed_at));

const isAdmin = computed(() => user.value?.role === 'Admin');

// Inizia il wizard: chiama /api/2fa/enable
const startEnableFlow = async () => {
	isLoading.value = true;
	try {
		const res = await sanctum('/api/2fa/enable', { method: 'POST' });
		qrSecret.value = res.secret;
		qrUrl.value = res.qr_url;
		qrSvg.value = await QRCode.toString(res.qr_url, { type: 'svg', width: 240, margin: 1, color: { dark: '#095866', light: '#ffffff' } });
		wizardStep.value = 'qr';
	} catch (e) {
		showError(e, 'Errore durante l\'attivazione del 2FA.');
	} finally {
		isLoading.value = false;
	}
};

const goToVerifyStep = () => {
	wizardStep.value = 'verify';
	inputCode.value = '';
};

const confirmCode = async () => {
	if (inputCode.value.length !== 6) {
		showError(null, 'Il codice deve essere di 6 cifre.');
		return;
	}
	isLoading.value = true;
	try {
		const res = await sanctum('/api/2fa/confirm', {
			method: 'POST',
			body: { code: inputCode.value },
		});
		recoveryCodes.value = res.recovery_codes || [];
		wizardStep.value = 'recovery';
		await refreshIdentity();
		showSuccess('2FA attivato con successo.');
	} catch (e) {
		showError(e, 'Codice non valido o scaduto. Riprova.');
		inputCode.value = '';
	} finally {
		isLoading.value = false;
	}
};

const finishWizard = () => {
	wizardStep.value = 'initial';
	qrSecret.value = '';
	qrUrl.value = '';
	qrSvg.value = '';
	inputCode.value = '';
	recoveryCodes.value = [];
};

const startDisableFlow = () => {
	wizardStep.value = 'disable';
	disablePassword.value = '';
};

const confirmDisable = async () => {
	if (!disablePassword.value) {
		showError(null, 'Inserisci la password.');
		return;
	}
	isLoading.value = true;
	try {
		await sanctum('/api/2fa/disable', {
			method: 'POST',
			body: { current_password: disablePassword.value },
		});
		await refreshIdentity();
		showSuccess('2FA disabilitato.');
		wizardStep.value = 'initial';
		disablePassword.value = '';
	} catch (e) {
		showError(e, 'Password errata o errore durante la disabilitazione.');
	} finally {
		isLoading.value = false;
	}
};

const copySecret = async () => {
	try {
		await navigator.clipboard.writeText(qrSecret.value);
		showSuccess('Codice segreto copiato negli appunti.');
	} catch {
		showError(null, 'Copia non riuscita. Selezionalo manualmente.');
	}
};

const copyAllRecoveryCodes = async () => {
	try {
		await navigator.clipboard.writeText(recoveryCodes.value.join('\n'));
		showSuccess('Codici di recupero copiati negli appunti.');
	} catch {
		showError(null, 'Copia non riuscita.');
	}
};

const downloadRecoveryCodes = () => {
	const text = `SpediamoFacile · Codici di recupero 2FA\nGenerati: ${new Date().toLocaleString('it-IT')}\nUtente: ${user.value?.email}\n\n${recoveryCodes.value.join('\n')}\n\nConserva questi codici in un luogo sicuro. Ognuno è utilizzabile UNA SOLA VOLTA per accedere all'account in caso di smarrimento del dispositivo 2FA.`;
	const blob = new Blob([text], { type: 'text/plain' });
	const url = URL.createObjectURL(blob);
	const link = document.createElement('a');
	link.href = url;
	link.download = `recovery-codes-2fa-${new Date().toISOString().slice(0, 10)}.txt`;
	link.click();
	URL.revokeObjectURL(url);
};
</script>

<template>
	<AccountPageSection>
		<AccountPageHeader
			eyebrow="Sicurezza account"
			title="Autenticazione a due fattori (2FA)"
			description="Aggiungi un livello di protezione: oltre alla password serve un codice generato da un'app come Google Authenticator, Authy o 1Password."
			:crumbs="[{ label: 'Account', to: '/account' }, { label: 'Profilo', to: '/account/profilo' }, { label: 'Sicurezza' }]"
			back-to="/account/profilo"
			back-label="Torna al profilo" />

		<SfActionBanner :message="feedback" />

		<!-- STATO INITIAL: status + CTA -->
		<SfCard v-if="wizardStep === 'initial'" padding="lg">
			<template #header>
				<div class="flex flex-col tablet:flex-row tablet:items-start tablet:justify-between gap-3">
					<div class="flex items-start gap-3">
						<div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-control" :class="has2faActive ? 'bg-brand-success-bg text-brand-success-fg' : 'bg-brand-bg-alt text-brand-text-muted'">
							<UIcon :name="has2faActive ? 'mdi:shield-check' : 'mdi:shield-off-outline'" class="h-6 w-6" />
						</div>
						<div>
							<p class="sf-section-kicker mb-1">Stato attuale</p>
							<h2 class="font-display text-xl font-bold text-brand-text">
								{{ has2faActive ? '2FA attivo' : '2FA non attivo' }}
							</h2>
							<p class="mt-1 text-sm text-brand-text-secondary">
								<template v-if="has2faActive">
									Il tuo account è protetto. Per accedere serve un codice dall'app autenticatore.
								</template>
								<template v-else>
									Il tuo account è protetto solo dalla password. Attiva il 2FA per maggiore sicurezza.
								</template>
							</p>
						</div>
					</div>
				</div>
			</template>

			<div v-if="!has2faActive" class="flex flex-col tablet:flex-row gap-3 mt-2">
				<SfButton size="lg" :loading="isLoading" @click="startEnableFlow">
					<template #leading><UIcon name="mdi:shield-key" class="h-5 w-5" /></template>
					Attiva 2FA ora
				</SfButton>
				<SfButton variant="secondary" size="lg" to="/account/profilo">
					Più tardi
				</SfButton>
			</div>

			<div v-else class="flex flex-col tablet:flex-row gap-3 mt-2">
				<SfButton variant="secondary" size="lg" @click="startDisableFlow">
					<template #leading><UIcon name="mdi:shield-off" class="h-5 w-5" /></template>
					Disabilita 2FA
				</SfButton>
			</div>

			<!-- Info admin se non Pro -->
			<div v-if="isAdmin && !has2faActive" class="mt-5 rounded-card border border-brand-accent/20 bg-brand-accent/5 p-4">
				<div class="flex items-start gap-3">
					<UIcon name="mdi:alert" class="h-5 w-5 shrink-0 text-brand-accent mt-0.5" />
					<div class="text-sm leading-relaxed text-brand-text">
						<strong>Account amministratore:</strong> il 2FA è obbligatorio per te. Senza 2FA non potrai accedere alle pagine di amministrazione una volta riattivata la protezione di sistema.
					</div>
				</div>
			</div>
		</SfCard>

		<!-- STEP QR: mostra QR code + secret -->
		<SfCard v-else-if="wizardStep === 'qr'" padding="lg">
			<template #header>
				<div class="flex items-center gap-3">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-primary text-white text-sm font-bold">1</span>
					<div>
						<p class="sf-section-kicker mb-1">Passo 1 di 3</p>
						<h2 class="font-display text-xl font-bold text-brand-text">Scansiona il QR code</h2>
					</div>
				</div>
			</template>

			<div class="grid grid-cols-1 desktop:grid-cols-[auto_1fr] gap-6 items-start">
				<!-- QR code -->
				<div class="flex justify-center desktop:justify-start">
					<div class="rounded-card border border-brand-border bg-white p-3 shadow-sf-sm" v-html="qrSvg" />
				</div>

				<!-- Istruzioni -->
				<div class="space-y-4">
					<div>
						<p class="text-sm leading-relaxed text-brand-text-secondary">
							Apri un'app autenticatore sul tuo telefono e scansiona il codice QR a sinistra. Le app più usate sono:
						</p>
						<ul class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-sm text-brand-text">
							<li class="flex items-center gap-2"><UIcon name="mdi:check-circle" class="h-4 w-4 text-brand-success-fg" />Google Authenticator</li>
							<li class="flex items-center gap-2"><UIcon name="mdi:check-circle" class="h-4 w-4 text-brand-success-fg" />Microsoft Authenticator</li>
							<li class="flex items-center gap-2"><UIcon name="mdi:check-circle" class="h-4 w-4 text-brand-success-fg" />Authy</li>
							<li class="flex items-center gap-2"><UIcon name="mdi:check-circle" class="h-4 w-4 text-brand-success-fg" />1Password</li>
						</ul>
					</div>

					<div class="rounded-card border border-brand-border bg-brand-bg-alt p-4">
						<button type="button" class="flex w-full items-center justify-between text-left" @click="showSecretText = !showSecretText">
							<span class="text-sm font-semibold text-brand-text">Non riesci a scansionare?</span>
							<UIcon :name="showSecretText ? 'mdi:chevron-up' : 'mdi:chevron-down'" class="h-5 w-5 text-brand-text-muted" />
						</button>
						<div v-if="showSecretText" class="mt-3 space-y-2">
							<p class="text-xs text-brand-text-secondary">Inserisci manualmente questo codice nell'app:</p>
							<div class="flex items-center gap-2 rounded-control border border-brand-border bg-white px-3 py-2.5 font-mono text-sm tracking-wider text-brand-text">
								<span class="flex-1 break-all">{{ qrSecret }}</span>
								<button type="button" class="shrink-0 text-brand-primary hover:text-brand-primary-hover" :aria-label="'Copia codice'" @click="copySecret">
									<UIcon name="mdi:content-copy" class="h-4 w-4" />
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="mt-6 flex flex-col tablet:flex-row gap-3 justify-end">
				<SfButton variant="secondary" @click="finishWizard">Annulla</SfButton>
				<SfButton @click="goToVerifyStep">
					Ho scansionato, continua
					<template #trailing><UIcon name="mdi:arrow-right" class="h-5 w-5" /></template>
				</SfButton>
			</div>
		</SfCard>

		<!-- STEP VERIFY: input 6 cifre -->
		<SfCard v-else-if="wizardStep === 'verify'" padding="lg">
			<template #header>
				<div class="flex items-center gap-3">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-primary text-white text-sm font-bold">2</span>
					<div>
						<p class="sf-section-kicker mb-1">Passo 2 di 3</p>
						<h2 class="font-display text-xl font-bold text-brand-text">Conferma il codice</h2>
					</div>
				</div>
			</template>

			<div class="space-y-5 max-w-md">
				<p class="text-sm leading-relaxed text-brand-text-secondary">
					Inserisci il codice di <strong>6 cifre</strong> che vedi nell'app autenticatore. Il codice cambia ogni 30 secondi.
				</p>

				<SfFormGroup label="Codice 2FA" hint="6 cifre, solo numeri">
					<SfInput
						id="2fa-code"
						v-model="inputCode"
						type="text"
						inputmode="numeric"
						autocomplete="one-time-code"
						maxlength="6"
						placeholder="123456"
						@keyup.enter="confirmCode" />
				</SfFormGroup>
			</div>

			<div class="mt-6 flex flex-col tablet:flex-row gap-3 justify-end">
				<SfButton variant="secondary" @click="wizardStep = 'qr'">
					<template #leading><UIcon name="mdi:arrow-left" class="h-5 w-5" /></template>
					Indietro
				</SfButton>
				<SfButton :loading="isLoading" :disabled="inputCode.length !== 6" @click="confirmCode">
					Verifica e attiva
					<template #trailing><UIcon name="mdi:check" class="h-5 w-5" /></template>
				</SfButton>
			</div>
		</SfCard>

		<!-- STEP RECOVERY: mostra recovery codes -->
		<SfCard v-else-if="wizardStep === 'recovery'" padding="lg">
			<template #header>
				<div class="flex items-center gap-3">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-success-fg text-white text-sm font-bold">
						<UIcon name="mdi:check" class="h-5 w-5" />
					</span>
					<div>
						<p class="sf-section-kicker mb-1">Passo 3 di 3 · 2FA attivato</p>
						<h2 class="font-display text-xl font-bold text-brand-text">Salva i codici di recupero</h2>
					</div>
				</div>
			</template>

			<div class="space-y-5">
				<div class="rounded-card border border-brand-accent/30 bg-brand-accent/5 p-4">
					<div class="flex items-start gap-3">
						<UIcon name="mdi:alert-circle" class="h-5 w-5 shrink-0 text-brand-accent mt-0.5" />
						<div class="text-sm leading-relaxed text-brand-text">
							<strong>Importante:</strong> conserva questi codici in un luogo sicuro (password manager, file cifrato, stampa cartacea). Ti permetteranno di accedere all'account se perdi il telefono. Ognuno è utilizzabile <strong>una sola volta</strong>.
						</div>
					</div>
				</div>

				<div class="rounded-card border border-brand-border bg-brand-bg-alt p-5">
					<div class="grid grid-cols-2 gap-2 font-mono text-sm tracking-wider text-brand-text">
						<div v-for="(code, i) in recoveryCodes" :key="i" class="rounded-control bg-white px-3 py-2 text-center">
							{{ code }}
						</div>
					</div>
				</div>

				<div class="flex flex-col tablet:flex-row gap-3">
					<SfButton variant="secondary" @click="copyAllRecoveryCodes">
						<template #leading><UIcon name="mdi:content-copy" class="h-5 w-5" /></template>
						Copia tutti
					</SfButton>
					<SfButton variant="secondary" @click="downloadRecoveryCodes">
						<template #leading><UIcon name="mdi:download" class="h-5 w-5" /></template>
						Scarica file .txt
					</SfButton>
				</div>
			</div>

			<div class="mt-6 flex justify-end">
				<SfButton size="lg" @click="finishWizard">
					Ho salvato i codici, ho finito
					<template #trailing><UIcon name="mdi:check" class="h-5 w-5" /></template>
				</SfButton>
			</div>
		</SfCard>

		<!-- STEP DISABLE: input password -->
		<SfCard v-else-if="wizardStep === 'disable'" padding="lg">
			<template #header>
				<div class="flex items-center gap-3">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-error/10 text-brand-error">
						<UIcon name="mdi:shield-off" class="h-5 w-5" />
					</span>
					<div>
						<h2 class="font-display text-xl font-bold text-brand-text">Disabilita 2FA</h2>
						<p class="mt-1 text-sm text-brand-text-secondary">Inserisci la password per confermare. Dopo la disattivazione, l'accesso sarà protetto solo dalla password.</p>
					</div>
				</div>
			</template>

			<div class="space-y-5 max-w-md">
				<SfFormGroup label="Password account">
					<SfInput
						id="2fa-disable-password"
						v-model="disablePassword"
						type="password"
						autocomplete="current-password"
						placeholder="La tua password"
						@keyup.enter="confirmDisable" />
				</SfFormGroup>
			</div>

			<div class="mt-6 flex flex-col tablet:flex-row gap-3 justify-end">
				<SfButton variant="secondary" @click="wizardStep = 'initial'">Annulla</SfButton>
				<SfButton variant="danger" :loading="isLoading" :disabled="!disablePassword" @click="confirmDisable">
					Disabilita 2FA
				</SfButton>
			</div>
		</SfCard>
	</AccountPageSection>
</template>
