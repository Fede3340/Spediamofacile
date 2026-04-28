<script setup>
import '~/assets/css/cookie-banner.css';

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
			:class="[
				'cookie-banner',
				isCompactBanner ? 'cookie-banner--compact' : '',
			]"
			:role="bannerRole"
			aria-label="Gestione cookie">
			<div class="cookie-banner__inner">
				<div class="cookie-banner__content">
					<div class="cookie-banner__icon" aria-hidden="true">
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
					<div class="cookie-banner__text">
						<p class="cookie-banner__eyebrow">Cookie e privacy</p>
						<h3 class="cookie-banner__title">{{ bannerTitle }}</h3>
						<p class="cookie-banner__message">
							{{ bannerMessage }}
							<NuxtLink to="/cookie-policy" class="cookie-banner__link">Scopri di più</NuxtLink>
						</p>
					</div>
				</div>

				<div v-if="showPreferences" class="cookie-banner__preferences">
					<p class="cookie-banner__panel-title">Preferenze cookie</p>
					<label class="cookie-pref">
						<input type="checkbox" checked disabled class="cookie-pref__check" />
						<span class="cookie-pref__label">Necessari <span class="cookie-pref__hint">(sempre attivi)</span></span>
					</label>
					<label class="cookie-pref">
						<input v-model="preferences.functional" type="checkbox" class="cookie-pref__check" />
						<span class="cookie-pref__label">Funzionali</span>
					</label>
					<label class="cookie-pref">
						<input v-model="preferences.analytics" type="checkbox" class="cookie-pref__check" />
						<span class="cookie-pref__label">Analitici</span>
					</label>
					<label class="cookie-pref">
						<input v-model="preferences.marketing" type="checkbox" class="cookie-pref__check" />
						<span class="cookie-pref__label">Marketing</span>
					</label>
					<div class="cookie-banner__pref-actions">
						<button
							type="button"
							@click="acceptCustom"
							class="cookie-banner__btn-primary cookie-banner__btn-primary--full btn-cta">
							Salva preferenze
						</button>
						<div class="cookie-banner__btns-row">
							<button
								type="button"
								@click="showPreferences = false"
								class="cookie-banner__btn-secondary btn-secondary">
								Indietro
							</button>
							<button
								type="button"
								@click="accept('essential')"
								class="cookie-banner__btn-secondary btn-secondary">
								Rifiuta tutti
							</button>
						</div>
					</div>
				</div>

				<div v-else class="cookie-banner__actions">
					<button
						type="button"
						@click="openPreferencesPanel"
						class="cookie-banner__btn-secondary btn-secondary">
						Personalizza
					</button>
					<button
						type="button"
						@click="accept('all')"
						class="cookie-banner__btn-primary btn-cta">
						Accetta tutti
					</button>
				</div>
			</div>
		</div>
	</Transition>
</template>
