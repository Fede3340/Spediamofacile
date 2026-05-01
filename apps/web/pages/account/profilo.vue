<script setup>
import { useAuthUiSnapshotPersistence } from '~/composables/useAuth';

definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'Profilo account',
	ogTitle: 'Profilo account',
		description: 'Aggiorna dati personali, sicurezza e fatturazione dalla tua area account SpediamoFacile.',
	ogDescription: 'Profilo personale e dati account su SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const { refreshIdentity, user, logout } = useSanctumAuth();
const { uiSnapshot } = useAuthUiState();
const { clearSnapshot } = useAuthUiSnapshotPersistence();
const { openAuthModal } = useAuthStore();
const profileUiReady = ref(true);

const messageError = ref(null);
const messageSuccess = ref(null);
const messageLoading = ref(null);
const showEditForm = ref(true);

const pickIdentityValue = (...candidates) => {
	for (const candidate of candidates) {
		const value = String(candidate || '').trim();
		if (value) return value;
	}

	return '';
};

const userInfo = ref({
	name: pickIdentityValue(uiSnapshot.value?.name),
	surname: pickIdentityValue(uiSnapshot.value?.surname),
	email: pickIdentityValue(uiSnapshot.value?.email),
	password: '',
	password_confirmation: '',
	telephone_number: '',
	user_type: pickIdentityValue(uiSnapshot.value?.userType) || 'privato',
	company_name: '',
	vat_number: '',
	fiscal_code: '',
	pec: '',
	sdi_code: '',
	billing_name: '',
	billing_address: '',
	billing_city: '',
	billing_postal_code: '',
	billing_province: '',
});

const syncUserInfoFromIdentity = (liveUser, snapshot) => {
		const nextName = pickIdentityValue(liveUser?.name, snapshot?.name);
		const nextSurname = pickIdentityValue(liveUser?.surname, liveUser?.last_name, snapshot?.surname);
		const nextEmail = pickIdentityValue(liveUser?.email, snapshot?.email);
		const nextUserType = pickIdentityValue(liveUser?.user_type, snapshot?.userType) || 'privato';

		if (!userInfo.value.name && nextName) userInfo.value.name = nextName;
		if (!userInfo.value.surname && nextSurname) userInfo.value.surname = nextSurname;
		if (!userInfo.value.email && nextEmail) userInfo.value.email = nextEmail;
		if (!userInfo.value.user_type && nextUserType) userInfo.value.user_type = nextUserType;
		if (!userInfo.value.telephone_number && liveUser?.telephone_number) userInfo.value.telephone_number = liveUser.telephone_number;
		if (!userInfo.value.company_name && liveUser?.company_name) userInfo.value.company_name = liveUser.company_name;
		if (!userInfo.value.vat_number && liveUser?.vat_number) userInfo.value.vat_number = liveUser.vat_number;
		if (!userInfo.value.fiscal_code && liveUser?.fiscal_code) userInfo.value.fiscal_code = liveUser.fiscal_code;
		if (!userInfo.value.pec && liveUser?.pec) userInfo.value.pec = liveUser.pec;
		if (!userInfo.value.sdi_code && liveUser?.sdi_code) userInfo.value.sdi_code = liveUser.sdi_code;
		if (!userInfo.value.billing_name && liveUser?.billing_name) userInfo.value.billing_name = liveUser.billing_name;
		if (!userInfo.value.billing_address && liveUser?.billing_address) userInfo.value.billing_address = liveUser.billing_address;
		if (!userInfo.value.billing_city && liveUser?.billing_city) userInfo.value.billing_city = liveUser.billing_city;
		if (!userInfo.value.billing_postal_code && liveUser?.billing_postal_code) userInfo.value.billing_postal_code = liveUser.billing_postal_code;
		if (!userInfo.value.billing_province && liveUser?.billing_province) userInfo.value.billing_province = liveUser.billing_province;
};

onMounted(() => {
	syncUserInfoFromIdentity(user.value, uiSnapshot.value);
});

watch(
	() => [user.value, uiSnapshot.value],
	([liveUser, snapshot]) => {
		syncUserInfoFromIdentity(liveUser, snapshot);
	},
);

const sanctum = useSanctumClient();

// Auto-dismiss centralizzato per il flash di successo: previene leak su navigazione mid-message.
let messageSuccessTimer = null;
const dismissMessageSuccessAfter = (ms = 4000) => {
	if (messageSuccessTimer) clearTimeout(messageSuccessTimer);
	messageSuccessTimer = setTimeout(() => { messageSuccess.value = null; messageSuccessTimer = null; }, ms);
};
onBeforeUnmount(() => { if (messageSuccessTimer) clearTimeout(messageSuccessTimer); });

const updateInfo = async () => {
	messageError.value = null;
	messageSuccess.value = null;
	messageLoading.value = 'Salvataggio in corso...';
	try {
		await sanctum(`/api/users/${user.value.id}`, { method: 'PATCH', body: userInfo.value });
		await refreshIdentity();
		messageSuccess.value = 'Dati aggiornati con successo!';
		dismissMessageSuccessAfter(4000);
	} catch (error) {
		if (error?.statusCode === 401) {
			clearSnapshot();
			messageError.value = 'Sessione scaduta. Accedi di nuovo per continuare a modificare il profilo.';
			openAuthModal({
				redirect: '/account/profilo',
				tab: 'login',
			});
			return;
		}
		const data = error?.data || error?.response?._data;
		if (data?.errors) {
			const firstError = Object.values(data.errors)[0];
			messageError.value = Array.isArray(firstError) ? firstError[0] : firstError;
		} else {
			messageError.value = "Errore durante l'aggiornamento. Riprova.";
		}
	} finally {
		messageLoading.value = null;
	}
};

const handleLogout = async () => {
	try {
		clearSnapshot();
		await logout();
		await navigateTo('/');
	} catch {
		navigateTo('/');
	}
};

</script>

<template>
	<section v-if="profileUiReady" class="w-full min-h-[600px] py-5 tablet:py-6 desktop:py-7">
		<div class="my-container max-w-7xl">
			<AccountPageHeader
				eyebrow="Profilo"
				title="Il mio profilo"
				description="Gestisci dati personali, sicurezza e fatturazione dell'account."
				current="Profilo"/>

			<!-- Messaggi -->
			<div v-if="messageLoading" class="mb-[10px] ux-alert ux-alert--info">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="18"
					height="18"
					viewBox="0 0 24 24"
					fill="none"
					class="ux-alert__icon animate-spin"
					aria-hidden="true">
					<path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z" fill="currentColor" />
				</svg>
				<span>{{ messageLoading }}</span>
			</div>
			<div v-if="messageSuccess" class="mb-[10px] ux-alert ux-alert--success">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="18"
					height="18"
					viewBox="0 0 24 24"
					fill="currentColor"
					class="ux-alert__icon shrink-0"
					aria-hidden="true">
					<path
						d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" />
				</svg>
				<span>{{ messageSuccess }}</span>
			</div>
			<div v-if="messageError" class="mb-[10px] ux-alert ux-alert--critical">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="18"
					height="18"
					viewBox="0 0 24 24"
					fill="currentColor"
					class="ux-alert__icon shrink-0"
					aria-hidden="true">
					<path
						d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z" />
				</svg>
				<span>{{ messageError }}</span>
			</div>

			<!-- Vista profilo -->
			<div class="sf-animate-in sf-animate-in-2">
				<AccountProfiloView v-if="!showEditForm" :user="user" @edit="showEditForm = true" @logout="handleLogout" />
			</div>

			<!-- Form modifica -->
			<AccountProfiloEditForm
				v-if="showEditForm"
				v-model="userInfo"
				:loading="messageLoading"
				@submit="updateInfo"
				@cancel="showEditForm = false" />
		</div>
	</section>

	<!-- Skeleton -->
	<section v-else class="w-full min-h-[600px] py-5 tablet:py-6 desktop:py-7">
		<div class="my-container max-w-7xl space-y-[8px]">
			<div class="space-y-[8px] mb-[10px]">
				<div class="h-[14px] w-[80px] rounded-full bg-[#EEF3F7] animate-pulse"/>
				<div class="flex items-center gap-[12px]">
					<div class="w-[44px] h-[44px] rounded-[14px] bg-[#EEF3F7] animate-pulse"/>
					<div class="space-y-[5px]">
						<div class="h-[22px] w-[200px] rounded-[10px] bg-[#EEF3F7] animate-pulse"/>
						<div class="h-[13px] w-[260px] rounded-full bg-[#F2F5F8] animate-pulse"/>
					</div>
				</div>
			</div>
			<div class="rounded-card border border-brand-border bg-brand-card p-[18px] shadow-sf">
				<div class="grid grid-cols-1 tablet:grid-cols-2 gap-[12px]">
					<div
						v-for="index in 6"
						:key="`skel-${index}`"
						class="h-[64px] rounded-[14px] bg-[#F5F6F9] animate-pulse"/>
				</div>
			</div>
		</div>
	</section>
</template>
