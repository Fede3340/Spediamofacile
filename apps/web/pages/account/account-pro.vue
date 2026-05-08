<script setup>
definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'Area Partner Pro',
	ogTitle: 'Area Partner Pro',
	description: 'Gestisci richiesta Partner Pro, link invito, commissioni e saldo dalla tua area account SpediamoFacile.',
	ogDescription: 'Area Partner Pro con richiesta accesso, link invito, commissioni e saldo su SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const { user } = useSanctumAuth();
const sanctum = useSanctumClient();
const { authCookie } = useAuthUiSnapshotPersistence();
const { message: pageFlash, showError: showPageError, clear: clearPageFlash } = useFlashMessage();
const accountProUiReady = ref(false);

const effectiveRole = computed(() => user.value?.role || authCookie.value?.role || null);
const isPro = computed(() => effectiveRole.value === 'Partner Pro');

/* === Richiesta Pro (non Pro) === */
const proRequestStatus = ref(null);
const proRequestLoading = ref(false);
const proRequestForm = ref({ company_name: '', vat_number: '', message: '' });
const proRequestError = ref(null);
const proRequestSuccess = ref(false);
const proRequestStatusLoading = ref(false);
const hasLoadedPartnerArea = ref(false);

const normalizedProRequestStatus = computed(() => {
	const raw = proRequestStatus.value;
	if (!raw) return null;

	return {
		...raw,
		status: raw.status || raw.data?.status || null,
		has_request: Boolean(raw.has_request ?? raw.data?.has_request ?? raw.status ?? raw.data?.status),
	};
});

const requestStatusLabel = computed(() => {
	if (isPro.value) return 'Partner Pro attivo';
	switch (String(normalizedProRequestStatus.value?.status || '')) {
		case 'pending':
			return 'Richiesta in revisione';
		case 'approved':
			return 'Accesso approvato';
		case 'rejected':
			return 'Da aggiornare';
		default:
			return 'Richiedi accesso';
	}
});

const partnerHeaderDescription = computed(() => {
	if (isPro.value) {
		return 'Condividi il tuo link, monitora utilizzi e tieni sotto controllo commissioni e saldo da un unico pannello ordinato.';
	}

	switch (String(normalizedProRequestStatus.value?.status || '')) {
		case 'pending':
			return 'La richiesta è in revisione. Qui restano visibili vantaggi, stato e prossimi passi senza uscire dall’account.';
		case 'approved':
			return 'La richiesta è stata approvata. Aggiorna la sessione per entrare nella dashboard Partner Pro completa.';
		case 'rejected':
			return 'Aggiorna i dati aziendali e reinvia la richiesta per attivare inviti, commissioni e saldo prelevabile.';
		default:
			return 'Attiva Partner Pro per invitare clienti, accumulare commissioni tracciate e sbloccare il saldo prelevabile.';
	}
});

const canSubmitProRequest = computed(() => {
	const currentStatus = normalizedProRequestStatus.value?.status;
	return !['pending', 'approved'].includes(String(currentStatus || ''));
});

const fetchProRequestStatus = async () => {
	proRequestStatusLoading.value = true;
	try {
		proRequestStatus.value = await sanctum('/api/pro-request/status');
		clearPageFlash();
	} catch (e) {
		showPageError(e, 'Non riesco a caricare lo stato Partner Pro. Riprova.');
	} finally {
		proRequestStatusLoading.value = false;
	}
};

const submitProRequest = async () => {
	proRequestError.value = null;
	if (!canSubmitProRequest.value) return;
	proRequestLoading.value = true;
	try {
		await sanctum('/api/pro-request', { method: 'POST', body: proRequestForm.value });
		proRequestSuccess.value = true;
		clearPageFlash();
		await fetchProRequestStatus();
	} catch (e) {
		const data = e?.response?._data || e?.data;
		proRequestError.value = data?.message || "Errore nell'invio della richiesta. Riprova.";
	} finally {
		proRequestLoading.value = false;
	}
};

/* === Dati Partner Pro === */
const referralData = ref(null);
const earnings = ref(null);
const isLoading = ref(true);

const fetchData = async () => {
	if (!isPro.value) {
		isLoading.value = false;
		return;
	}

	isLoading.value = true;
	try {
		const [refData, earningsData] = await Promise.all([sanctum('/api/referral/my-code'), sanctum('/api/referral/earnings')]);
		referralData.value = refData;
		earnings.value = earningsData;
		clearPageFlash();
	} catch (e) {
		showPageError(e, 'Non riesco a caricare inviti e commissioni. Riprova.');
	} finally {
		isLoading.value = false;
		hasLoadedPartnerArea.value = true;
	}
};

const hydratePartnerArea = async () => {
	proRequestSuccess.value = false;
	proRequestError.value = null;

	if (isPro.value) {
		proRequestStatus.value = null;
		await fetchData();
		return;
	}

	referralData.value = null;
	earnings.value = null;
	hasLoadedPartnerArea.value = false;
	await fetchProRequestStatus();
};

onMounted(async () => {
	accountProUiReady.value = true;
	await hydratePartnerArea();
});

watch(
	() => effectiveRole.value,
	async (nextRole, previousRole) => {
		if (!accountProUiReady.value) return;
		if (nextRole === previousRole && (nextRole || hasLoadedPartnerArea.value)) return;
		await hydratePartnerArea();
	},
);

/* === Clipboard helpers === */
const copied = ref(false);
const copiedAccountCode = ref(false);
const copiedLink = ref(false);

const fallbackCopy = (text) => {
	const el = document.createElement('textarea');
	el.value = text;
	document.body.appendChild(el);
	el.select();
	document.execCommand('copy');
	document.body.removeChild(el);
};

const clipboardWrite = async (text, flag) => {
	try {
		await navigator.clipboard.writeText(text);
	} catch {
		fallbackCopy(text);
	}
	flag.value = true;
	setTimeout(() => {
		flag.value = false;
	}, 2000);
};

const copyCode = () => {
	if (referralData.value?.referral_code) clipboardWrite(referralData.value.referral_code, copied);
};
const copyReferralLink = () => {
	if (referralData.value?.referral_link) clipboardWrite(referralData.value.referral_link, copiedLink);
};
const copyAccountCode = () => {
	clipboardWrite(`SF-PRO-${user.value?.id?.toString().padStart(6, '0')}`, copiedAccountCode);
};
const shareWhatsApp = () => {
	if (referralData.value?.whatsapp_link) window.open(referralData.value.whatsapp_link, '_blank');
};
</script>

<template>
	<AccountPageSection v-if="accountProUiReady" spacing="space-y-[20px] tablet:space-y-[22px]" max-width="">
			<AccountPageHeader
				eyebrow="Account"
				title="Area Partner Pro"
				:description="partnerHeaderDescription"
				:crumbs="[{ label: 'Account', to: '/account' }, { label: 'Area Partner Pro' }]">
				<template #meta>
					<span class="inline-flex items-center gap-1 rounded-full border border-brand-primary/15 bg-brand-primary/[0.06] px-2.5 py-1.5 text-xs font-bold leading-none text-brand-primary">{{ requestStatusLabel }}</span>
				</template>
				<template #actions>
					<NuxtLink to="/preventivo" class="btn-primary btn-compact inline-flex items-center justify-center">Nuova spedizione</NuxtLink>
				</template>
			</AccountPageHeader>

			<SfActionBanner :message="pageFlash" />

			<AccountProRequestForm
				v-if="!isPro"
				class="sf-animate-in sf-animate-in-1"
				:pro-request-status="normalizedProRequestStatus"
				:pro-request-form="proRequestForm"
				:pro-request-error="proRequestError"
				:pro-request-success="proRequestSuccess"
				:pro-request-loading="proRequestLoading"
				:pro-request-status-loading="proRequestStatusLoading"
				:can-submit="canSubmitProRequest"
				@update:pro-request-form="proRequestForm = $event"
				@submit="submitProRequest" />

			<AccountProDashboard
				v-else
				class="sf-animate-in sf-animate-in-1"
				:user="user"
				:referral-data="referralData"
				:earnings="earnings"
				:copied="copied"
				:copied-account-code="copiedAccountCode"
				:copied-link="copiedLink"
				@copy-code="copyCode"
				@copy-link="copyReferralLink"
				@copy-account-code="copyAccountCode"
				@share-whatsapp="shareWhatsApp" />
	</AccountPageSection>

	<AccountPageSection v-else max-width="" padding="py-[18px] tablet:py-6 desktop:py-7" spacing="">
		<div class="rounded-card border border-brand-border bg-brand-card p-[18px] shadow-sf desktop:p-5">
			<div class="animate-pulse space-y-[14px]">
				<div class="h-[18px] w-[200px] rounded-full bg-[var(--color-brand-border)]"/>
				<div class="h-[14px] w-[320px] rounded-full bg-[#F0F2F4]"/>
				<div class="grid gap-[12px] desktop:grid-cols-3 mt-[18px]">
					<div v-for="n in 3" :key="n" class="h-[90px] rounded-[18px] bg-[#F5F7F8]"/>
				</div>
			</div>
		</div>
	</AccountPageSection>
</template>

