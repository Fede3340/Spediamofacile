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

const { message: flashMessage, showSuccess: showFlashSuccess, showError: showFlashError, clear: clearFlash } = useFlashMessage();
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

const updateInfo = async () => {
	clearFlash();
	messageLoading.value = 'Salvataggio in corso...';
	try {
		await sanctum(`/api/users/${user.value.id}`, { method: 'PATCH', body: userInfo.value });
		await refreshIdentity();
		showFlashSuccess('Dati aggiornati con successo!');
	} catch (error) {
		if (error?.statusCode === 401) {
			clearSnapshot();
			showFlashError(error, 'Sessione scaduta. Accedi di nuovo per continuare a modificare il profilo.');
			openAuthModal({
				redirect: '/account/profilo',
				tab: 'login',
			});
			return;
		}
		const data = error?.data || error?.response?._data;
		if (data?.errors) {
			const firstError = Object.values(data.errors)[0];
			showFlashError(null, Array.isArray(firstError) ? firstError[0] : firstError);
		} else {
			showFlashError(error, "Errore durante l'aggiornamento. Riprova.");
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
	<AccountPageSection v-if="profileUiReady" spacing="space-y-4">
		<AccountPageHeader
			eyebrow="Profilo"
			title="Il mio profilo"
			description="Gestisci dati personali, sicurezza e fatturazione dell'account."
			current="Profilo"/>

		<SfAlert v-if="messageLoading" tone="info">{{ messageLoading }}</SfAlert>
		<SfActionBanner :message="flashMessage" />

		<div class="sf-animate-in sf-animate-in-2">
			<AccountProfiloView v-if="!showEditForm" :user="user" @edit="showEditForm = true" @logout="handleLogout" />
		</div>

		<AccountProfiloEditForm
			v-if="showEditForm"
			v-model="userInfo"
			:loading="messageLoading"
			@submit="updateInfo"
			@cancel="showEditForm = false" />
	</AccountPageSection>

	<AccountPageSection v-else spacing="space-y-3">
		<div class="space-y-2">
			<SfSkeleton width="80px" height="14px" rounded="9999px" />
			<div class="flex items-center gap-3">
				<SfSkeleton width="44px" height="44px" rounded="14px" />
				<div class="space-y-1.5">
					<SfSkeleton width="200px" height="22px" rounded="10px" />
					<SfSkeleton width="260px" height="13px" rounded="9999px" />
				</div>
			</div>
		</div>
		<div class="rounded-card border border-brand-border bg-brand-card p-[18px] shadow-sf">
			<div class="grid grid-cols-1 tablet:grid-cols-2 gap-3">
				<SfSkeleton v-for="index in 6" :key="`skel-${index}`" height="64px" rounded="14px" />
			</div>
		</div>
	</AccountPageSection>
</template>
