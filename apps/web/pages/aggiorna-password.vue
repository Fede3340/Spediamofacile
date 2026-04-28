<!-- Aggiorna Password — landing post-link-recupero. Richiede ?token=XXX, precompila email se presente.
     Redirect a /autenticazione dopo successo. -->
<script setup>
import '~/assets/css/autenticazione.css';
import { buildAuthOverlayLocation } from '~/utils/auth';

useSeoMeta({
	title: 'Aggiorna Password',
	ogTitle: 'Aggiorna Password',
	description: 'Imposta una nuova password per completare il recupero del tuo account SpediamoFacile.',
	ogDescription: 'Aggiorna la password del tuo account SpediamoFacile.',
});

const route = useRoute();
const router = useRouter();
const loginOverlayLocation = buildAuthOverlayLocation({
	requestedPath: '/',
	tab: 'login',
});

const data = ref({
	resetToken: '',
	email: '',
	password: '',
	password_confirmation: '',
});

const fieldErrors = ref({});
const messageError = ref(null);
const messageSuccess = ref(null);
const isLoading = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const tokenFromRoute = computed(() => String(route.query.token || '').trim());
const emailFromRoute = computed(() => String(route.query.email || '').trim());

const passwordChecks = computed(() => {
	const pwd = data.value.password || '';
	return {
		minLength: pwd.length >= 8,
		hasLower: /[a-z]/.test(pwd),
		hasUpper: /[A-Z]/.test(pwd),
		hasNumber: /[0-9]/.test(pwd),
		hasSymbol: /[^a-zA-Z0-9\s]/.test(pwd),
	};
});

const passwordStrength = computed(() => Object.values(passwordChecks.value).filter(Boolean).length);

definePageMeta({
	layout: 'auth',
	middleware: ['guest-auth', 'update-password'],
});

const syncRoutePayload = () => {
	data.value.resetToken = tokenFromRoute.value;
	if (emailFromRoute.value) {
		data.value.email = emailFromRoute.value;
	}
};

watch(
	() => [tokenFromRoute.value, emailFromRoute.value],
	() => syncRoutePayload(),
	{ immediate: true }
);

const updatePassword = async () => {
	fieldErrors.value = {};
	messageError.value = null;
	messageSuccess.value = null;
	syncRoutePayload();

	if (!data.value.resetToken) {
		messageError.value = 'Link di recupero non valido o incompleto. Richiedi una nuova email di reset.';
		return;
	}

	if (!data.value.email) {
		fieldErrors.value = { email: ["Inserisci l'email collegata all'account."] };
		return;
	}

	if (!data.value.password || !data.value.password_confirmation) {
		fieldErrors.value = { password: ['Inserisci e conferma la nuova password.'] };
		return;
	}

	if (data.value.password !== data.value.password_confirmation) {
		fieldErrors.value = { password_confirmation: ['Le password non coincidono.'] };
		return;
	}

	isLoading.value = true;

	try {
		const sanctum = useSanctumClient();
		const response = await sanctum('/api/update-password', {
			method: 'POST',
			body: data.value,
		});

		messageSuccess.value = response.message || 'Password aggiornata con successo.';
		setTimeout(() => {
			router.push(loginOverlayLocation);
		}, 1200);
	} catch (error) {
		const backendErrors = error?.response?._data?.errors || error?.data?.errors || null;
		if (backendErrors && typeof backendErrors === 'object') {
			fieldErrors.value = backendErrors;
		}

		messageError.value =
			error?.response?._data?.message ||
			error?.data?.message ||
			'Errore durante la modifica della password.';
	} finally {
		isLoading.value = false;
	}
};
</script>

<template>
	<section class="auth-shell">
		<div class="my-container">
			<div class="auth-shell-frame auth-shell-frame--wide">
				<header class="auth-shell-head">
					<div class="auth-shell-avatar" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="auth-shell-avatar__icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
							<path d="M7 11V7a5 5 0 0 1 10 0v4" />
						</svg>
					</div>
					<h1 class="auth-shell-title">Imposta nuova password</h1>
					<p class="auth-shell-copy">
						Scegli una nuova password sicura per completare il recupero del tuo account.
					</p>
				</header>

				<div v-if="messageSuccess" class="auth-shell-message auth-feedback--success">
					<div class="auth-shell-message__icon" aria-hidden="true">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 6L9 17l-5-5" />
						</svg>
					</div>
					<div class="auth-shell-message__body">
						<p class="auth-shell-message__title">Password aggiornata</p>
						<p class="auth-shell-message__copy">{{ messageSuccess }}</p>
					</div>
					<SfButton :to="loginOverlayLocation" variant="primary" block class="auth-shell-message__action">Torna al login</SfButton>
				</div>

				<form v-else @submit.prevent="updatePassword" class="auth-page-body auth-page-stack">
					<div class="auth-field-group">
						<label for="email" class="auth-field-label">Email</label>
						<input
							id="email"
							v-model="data.email"
							type="email"
							class="form-input"
							required
							autocomplete="email"
						/>
					</div>
					<p v-if="fieldErrors.email" class="auth-feedback auth-feedback--error">
						<span v-for="(error, index) in fieldErrors.email" :key="index" class="block">{{ error }}</span>
					</p>

					<div class="auth-field-group">
						<label for="password" class="auth-field-label">Nuova password</label>
						<div class="auth-password-wrap">
							<input
								id="password"
								v-model="data.password"
								:type="showPassword ? 'text' : 'password'"
								class="form-input auth-field-input--password"
								required
								autocomplete="new-password"
							/>
							<button
								type="button"
								class="auth-password-toggle"
								@click="showPassword = !showPassword"
								:aria-label="showPassword ? 'Nascondi password' : 'Mostra password'"
							>
								<svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
									<line x1="1" y1="1" x2="23" y2="23" />
								</svg>
								<svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12Z" />
									<circle cx="12" cy="12" r="3" />
								</svg>
							</button>
						</div>
						<div v-if="data.password" class="auth-password-meter">
							<div
								v-for="i in 5"
								:key="i"
								class="auth-password-meter__bar"
								:class="passwordStrength >= i ? (passwordStrength <= 2 ? 'auth-password-meter__bar--weak' : passwordStrength <= 3 ? 'auth-password-meter__bar--medium' : 'auth-password-meter__bar--strong') : ''"
							/>
						</div>
						<ul v-if="data.password" class="auth-password-checklist">
							<li :class="passwordChecks.minLength ? 'auth-password-checklist__item auth-password-checklist__item--good' : 'auth-password-checklist__item'">
								<span>{{ passwordChecks.minLength ? '✓' : '•' }}</span> Minimo 8 caratteri
							</li>
							<li :class="passwordChecks.hasLower ? 'auth-password-checklist__item auth-password-checklist__item--good' : 'auth-password-checklist__item'">
								<span>{{ passwordChecks.hasLower ? '✓' : '•' }}</span> Una lettera minuscola
							</li>
							<li :class="passwordChecks.hasUpper ? 'auth-password-checklist__item auth-password-checklist__item--good' : 'auth-password-checklist__item'">
								<span>{{ passwordChecks.hasUpper ? '✓' : '•' }}</span> Una lettera maiuscola
							</li>
							<li :class="passwordChecks.hasNumber ? 'auth-password-checklist__item auth-password-checklist__item--good' : 'auth-password-checklist__item'">
								<span>{{ passwordChecks.hasNumber ? '✓' : '•' }}</span> Un numero
							</li>
							<li :class="passwordChecks.hasSymbol ? 'auth-password-checklist__item auth-password-checklist__item--good' : 'auth-password-checklist__item'">
								<span>{{ passwordChecks.hasSymbol ? '✓' : '•' }}</span> Un simbolo speciale
							</li>
						</ul>
					</div>
					<p v-if="fieldErrors.password" class="auth-feedback auth-feedback--error">
						<span v-for="(error, index) in fieldErrors.password" :key="index" class="block">{{ error }}</span>
					</p>

					<div class="auth-field-group">
						<label for="password_confirmation" class="auth-field-label">Conferma nuova password</label>
						<div class="auth-password-wrap">
							<input
								id="password_confirmation"
								v-model="data.password_confirmation"
								:type="showPasswordConfirmation ? 'text' : 'password'"
								class="form-input auth-field-input--password"
								required
								autocomplete="new-password"
							/>
							<button
								type="button"
								class="auth-password-toggle"
								@click="showPasswordConfirmation = !showPasswordConfirmation"
								:aria-label="showPasswordConfirmation ? 'Nascondi conferma password' : 'Mostra conferma password'"
							>
								<svg v-if="showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
									<line x1="1" y1="1" x2="23" y2="23" />
								</svg>
								<svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12Z" />
									<circle cx="12" cy="12" r="3" />
								</svg>
							</button>
						</div>
					</div>
					<p v-if="fieldErrors.password_confirmation" class="auth-feedback auth-feedback--error">
						<span v-for="(error, index) in fieldErrors.password_confirmation" :key="index" class="block">{{ error }}</span>
					</p>

					<p v-if="messageError" class="auth-feedback auth-feedback--error">{{ messageError }}</p>
					<p v-if="messageSuccess" class="auth-feedback auth-feedback--success">{{ messageSuccess }}</p>

					<SfButton type="submit" variant="primary" block :loading="isLoading" loading-text="Salvataggio...">Aggiorna password</SfButton>
				</form>
			</div>
		</div>
	</section>
</template>
