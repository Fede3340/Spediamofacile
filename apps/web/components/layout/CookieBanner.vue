<script setup>
const client = useSanctumClient();
const visible = ref(false);
const showPreferences = ref(false);
const bannerRef = ref(null);

const preferences = reactive({
	analytics: false,
	marketing: false,
	functional: true,
});

const applyStoredConsent = () => {
	if (import.meta.server) return;

	const stored = localStorage.getItem('cookie_consent');
	preferences.analytics = false;
	preferences.marketing = false;
	preferences.functional = false;

	if (!stored) return;

	if (stored === 'all') {
		preferences.analytics = true;
		preferences.marketing = true;
		preferences.functional = true;
		return;
	}

	if (stored === 'essential') {
		return;
	}

	try {
		const parsed = JSON.parse(stored);
		preferences.analytics = Boolean(parsed?.analytics);
		preferences.marketing = Boolean(parsed?.marketing);
		preferences.functional = Boolean(parsed?.functional);
	} catch {
		// In caso di valore locale corrotto, il banner torna ai default minimi.
	}
};

const reopenCookieBanner = useState('reopenCookieBanner', () => false);
// Prima applicavamo varianti visive diverse per home / account / funnel.
// Gli utenti percepivano il banner come "diverso in home vs altre pagine":
// ora uniformiamo l'aspetto sitewide (una sola versione, sempre uguale).
const isCompactBanner = computed(() => !showPreferences.value);
// Il banner resta 'dialog' (accessibile + focus-friendly) in tutte le pagine.
const bannerRole = 'dialog';

const bannerTitle = computed(() => 'Gestisci i cookie');
const bannerMessage = computed(() => 'Usiamo i cookie necessari per far funzionare il sito e, se vuoi, anche quelli funzionali e analitici.');

const openPreferencesPanel = () => {
	showPreferences.value = true;
};

watch(reopenCookieBanner, (value) => {
	if (!value) return;

	applyStoredConsent();
	showPreferences.value = false;
	visible.value = true;
	reopenCookieBanner.value = false;
});

onMounted(() => {
	applyStoredConsent();
	if (!localStorage.getItem('cookie_consent')) {
		visible.value = true;
	}
});

const sendConsentToBackend = async (consentData) => {
	try {
		await client('/api/cookie-consent', { method: 'POST', body: consentData });
	} catch {
		// Il consenso locale è già salvato: il backend log resta best-effort.
	}
};

const emitConsentChange = () => {
	if (typeof window === 'undefined') return;

	try {
		window.dispatchEvent(new CustomEvent('sf:cookie-consent-changed'));
	} catch {
		// no-op
	}
};

const accept = (type) => {
	localStorage.setItem('cookie_consent', type);
	visible.value = false;
	emitConsentChange();

	if (type === 'all') {
		sendConsentToBackend({ type: 'all' });
	} else if (type === 'essential') {
		sendConsentToBackend({ type: 'necessary' });
	}
};

const acceptCustom = () => {
	const consent = {
		analytics: preferences.analytics,
		marketing: preferences.marketing,
		functional: preferences.functional,
	};

	localStorage.setItem('cookie_consent', JSON.stringify(consent));
	visible.value = false;
	emitConsentChange();
	sendConsentToBackend(consent);
};
</script>

<template>
	<Transition name="cookie-banner">
		<div
			v-if="visible"
			ref="bannerRef"
			class="fixed left-[10px] right-[10px] bottom-[10px] sm:left-[18px] sm:right-auto sm:bottom-[18px] z-[900] sm:w-[min(308px,calc(100vw-36px))] rounded-card bg-white/95 backdrop-blur-md ring-1 ring-[rgba(9,88,102,0.08)] shadow-[0_10px_28px_rgba(15,23,42,0.09),0_2px_6px_rgba(15,23,42,0.04)]"
			:role="bannerRole"
			aria-label="Gestione cookie">
			<div
				class="flex flex-col"
				:class="isCompactBanner ? 'gap-[9px] p-[12px] pb-[max(11px,env(safe-area-inset-bottom))]' : 'gap-[12px] p-[15px] pb-[max(13px,env(safe-area-inset-bottom))]'">
				<div class="flex items-start gap-[12px] min-w-0">
					<div
						v-if="!isCompactBanner"
						class="shrink-0 inline-flex items-center justify-center w-[36px] h-[36px] rounded-[10px] bg-[var(--color-brand-secondary-soft-bg)] ring-1 ring-[var(--color-brand-secondary-soft-border)] text-[var(--color-brand-secondary-soft-text)]"
						aria-hidden="true">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5" />
							<path d="M2 12h.01" />
							<path d="M12 2v.01" />
							<path d="M12 14a2 2 0 0 0 2-2" />
							<circle cx="7.5" cy="10.5" r=".5" fill="currentColor" />
							<circle cx="11" cy="7" r=".5" fill="currentColor" />
							<circle cx="8" cy="16" r=".5" fill="currentColor" />
							<circle cx="14.5" cy="15.5" r=".5" fill="currentColor" />
						</svg>
					</div>
					<div class="min-w-0">
						<p
							v-if="!isCompactBanner"
							class="m-0 mb-[4px] text-[0.7rem] uppercase tracking-[0.08em] text-[var(--color-brand-secondary-soft-text)]"
							style="font-weight:800">
							Cookie e privacy
						</p>
						<h3
							class="m-0 mb-[4px] sm:mb-[6px] font-montserrat leading-[1.25] text-[var(--color-brand-text,#1d2738)]"
							:class="isCompactBanner ? 'text-[0.88rem]' : 'text-[0.95rem]'"
							style="font-weight:800">
							{{ bannerTitle }}
						</h3>
						<p
							class="m-0 text-[var(--color-brand-text-secondary)]"
							:class="isCompactBanner ? 'text-[0.74rem] leading-[1.34]' : 'text-[0.825rem] leading-[1.5]'">
							{{ bannerMessage }}
							<NuxtLink to="/cookie-policy" class="inline-block px-[2px] py-[4px] text-[var(--color-brand-primary)] underline underline-offset-[3px] whitespace-nowrap hover:opacity-80" style="font-weight:600">
								Scopri di più
							</NuxtLink>
						</p>
					</div>
				</div>

				<div v-if="showPreferences" class="flex flex-col items-start gap-[10px]">
					<p class="m-0 mb-[2px] text-[0.72rem] uppercase tracking-[0.08em] text-[var(--color-brand-secondary-soft-text)]" style="font-weight:800">
						Preferenze cookie
					</p>
					<label class="inline-flex items-center gap-[6px] text-[0.8125rem] text-[var(--color-brand-text-secondary)] whitespace-nowrap cursor-not-allowed opacity-65">
						<input type="checkbox" checked disabled class="w-[16px] h-[16px] accent-[var(--color-brand-primary)]" >
						<span>Necessari <span class="text-[0.6875rem] text-[var(--color-brand-text-secondary)]">(sempre attivi)</span></span>
					</label>
					<label class="inline-flex items-center gap-[6px] text-[0.8125rem] text-[var(--color-brand-text-secondary)] whitespace-nowrap cursor-pointer">
						<input v-model="preferences.functional" type="checkbox" class="w-[16px] h-[16px] accent-[var(--color-brand-primary)]" >
						<span>Funzionali</span>
					</label>
					<label class="inline-flex items-center gap-[6px] text-[0.8125rem] text-[var(--color-brand-text-secondary)] whitespace-nowrap cursor-pointer">
						<input v-model="preferences.analytics" type="checkbox" class="w-[16px] h-[16px] accent-[var(--color-brand-primary)]" >
						<span>Analitici</span>
					</label>
					<label class="inline-flex items-center gap-[6px] text-[0.8125rem] text-[var(--color-brand-text-secondary)] whitespace-nowrap cursor-pointer">
						<input v-model="preferences.marketing" type="checkbox" class="w-[16px] h-[16px] accent-[var(--color-brand-primary)]" >
						<span>Marketing</span>
					</label>
					<div class="w-full mt-[6px] flex flex-col gap-[8px]">
						<SfButton
							variant="primary"
							block
							@click="acceptCustom">
							Salva preferenze
						</SfButton>
						<div class="grid grid-cols-2 gap-[8px]">
							<SfButton
								variant="secondary"
								@click="showPreferences = false">
								Indietro
							</SfButton>
							<SfButton
								variant="secondary"
								@click="accept('essential')">
								Rifiuta tutti
							</SfButton>
						</div>
					</div>
				</div>

				<div v-else class="grid grid-cols-2 gap-[8px]">
					<SfButton
						variant="secondary"
						@click="openPreferencesPanel">
						Personalizza
					</SfButton>
					<SfButton
						variant="primary"
						@click="accept('all')">
						Accetta tutti
					</SfButton>
				</div>
			</div>
		</div>
	</Transition>
</template>
