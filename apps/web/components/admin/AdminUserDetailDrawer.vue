<!-- AdminUserDetailDrawer.vue — Drawer dettaglio utente admin.
     Orchestratore: usa i sub-component in `components/admin/user-detail/`. -->
<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
	open: { type: Boolean, default: false },
	userId: { type: [Number, String, null], default: null },
	canMaster: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open', 'updated', 'impersonate']);

const sanctum = useSanctumClient();
const { showSuccess, showError, formatDate, formatPrice } = useAdmin();

const loading = ref(false);
const saving = ref(false);
const user = ref(null);
const orders = ref([]);
const addresses = ref([]);
const walletTx = ref([]);
const auditLog = ref([]);
const activeTab = ref('orders');

const form = ref({ role: 'User', status: 'active', is_pro: false });

const showBanConfirm = ref(false);
const showResetConfirm = ref(false);
const showEmailModal = ref(false);
const showImpersonateConfirm = ref(false);
const newEmail = ref('');

const fullName = computed(() => user.value
	? `${user.value.name || ''} ${user.value.surname || ''}`.trim()
	: '');
const isBanned = computed(() => form.value.status === 'banned');
const tabs = [
	{ id: 'orders', label: 'Ordini' },
	{ id: 'addresses', label: 'Indirizzi' },
	{ id: 'wallet', label: 'Wallet' },
	{ id: 'audit', label: 'Audit log' },
];

const formatTxAmount = (cents) => {
	if (typeof formatPrice === 'function') return formatPrice(cents);
	const n = Number(cents) / 100;
	return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n);
};

const fetchDetail = async () => {
	if (!props.userId) return;
	loading.value = true;
	try {
		const res = await sanctum(`/api/admin/users/${props.userId}`);
		const data = res?.data ?? res ?? null;
		user.value = data;
		form.value = {
			role: data?.role || 'User',
			status: data?.status || (data?.banned_at ? 'banned' : (data?.email_verified_at ? 'active' : 'pending-verification')),
			is_pro: Boolean(data?.is_pro),
		};
		orders.value = Array.isArray(data?.orders) ? data.orders : [];
		addresses.value = Array.isArray(data?.addresses) ? data.addresses : [];
		walletTx.value = Array.isArray(data?.wallet_transactions) ? data.wallet_transactions : [];
		auditLog.value = Array.isArray(data?.audit_log) ? data.audit_log : [];
	} catch (e) {
		showError(e, 'Errore nel caricamento del dettaglio utente.');
		user.value = null;
	} finally {
		loading.value = false;
	}
};

watch(() => [props.open, props.userId], ([isOpen, id]) => {
	if (isOpen && id) {
		activeTab.value = 'orders';
		fetchDetail();
	}
});

const saveProfile = async () => {
	if (!user.value) return;
	saving.value = true;
	try {
		await sanctum(`/api/admin/users/${user.value.id}`, { method: 'PATCH', body: { ...form.value } });
		showSuccess('Profilo aggiornato correttamente.');
		emit('updated');
		await fetchDetail();
	} catch (e) {
		showError(e, "Errore durante l'aggiornamento del profilo.");
	} finally {
		saving.value = false;
	}
};

const doResetPassword = async () => {
	if (!user.value) return;
	saving.value = true;
	try {
		await sanctum(`/api/admin/users/${user.value.id}/reset-password`, { method: 'POST' });
		showSuccess('Email di reset password inviata.');
		showResetConfirm.value = false;
	} catch (e) {
		showError(e, "Errore durante l'invio del reset password.");
	} finally {
		saving.value = false;
	}
};

const doBanToggle = async () => {
	if (!user.value) return;
	saving.value = true;
	try {
		const next = isBanned.value ? 'active' : 'banned';
		await sanctum(`/api/admin/users/${user.value.id}`, { method: 'PATCH', body: { status: next } });
		showSuccess(next === 'banned' ? 'Utente bannato.' : 'Ban rimosso.');
		showBanConfirm.value = false;
		emit('updated');
		await fetchDetail();
	} catch (e) {
		showError(e, "Errore durante l'operazione di ban.");
	} finally {
		saving.value = false;
	}
};

const askChangeEmail = () => {
	newEmail.value = user.value?.email || '';
	showEmailModal.value = true;
};

const doChangeEmail = async () => {
	if (!user.value || !newEmail.value) return;
	saving.value = true;
	try {
		await sanctum(`/api/admin/users/${user.value.id}`, { method: 'PATCH', body: { email: newEmail.value } });
		showSuccess('Email aggiornata.');
		showEmailModal.value = false;
		emit('updated');
		await fetchDetail();
	} catch (e) {
		showError(e, "Errore durante il cambio email.");
	} finally {
		saving.value = false;
	}
};

const doImpersonate = async () => {
	if (!user.value) return;
	saving.value = true;
	try {
		await sanctum(`/api/admin/users/${user.value.id}/impersonate`, { method: 'POST' });
		showSuccess('Sessione impersonata avviata. Verrai reindirizzato.');
		showImpersonateConfirm.value = false;
		emit('impersonate', user.value);
		setTimeout(() => { window.location.href = '/account'; }, 600);
	} catch (e) {
		showError(e, "Errore durante l'impersona.");
	} finally {
		saving.value = false;
	}
};

const close = () => emit('update:open', false);
</script>

<template>
	<Teleport to="body">
		<Transition
			enter-active-class="transition-opacity duration-200"
			leave-active-class="transition-opacity duration-200"
			enter-from-class="opacity-0"
			leave-to-class="opacity-0">
			<div v-if="open" class="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm flex justify-end" @click.self="close">
				<aside class="bg-brand-card w-full max-w-[640px] h-screen flex flex-col shadow-sf-lg overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
					<UserDetailHeader :user="user" @close="close" />

					<div class="flex-1 overflow-y-auto p-5 flex flex-col gap-4">
						<div v-if="loading" class="flex flex-col items-center gap-3 py-16 text-brand-text-secondary">
							<UIcon name="mdi:loading" class="w-8 h-8 text-brand-primary animate-spin" />
							<p>Caricamento dettaglio in corso...</p>
						</div>

						<template v-else-if="user">
							<UserDetailProfileSection :user="user" :format-date="formatDate" />

							<UserDetailPermissionsForm v-model="form" :saving="saving" @save="saveProfile" />

							<section class="flex flex-col gap-3">
								<SfTabs v-model="activeTab" :items="tabs" />

								<UserDetailTabOrders v-if="activeTab === 'orders'" :orders="orders" :format-date="formatDate" :format-price="formatTxAmount" />
								<UserDetailTabAddresses v-if="activeTab === 'addresses'" :addresses="addresses" />
								<UserDetailTabWallet v-if="activeTab === 'wallet'" :transactions="walletTx" :format-date="formatDate" :format-price="formatTxAmount" />
								<UserDetailTabAuditLog v-if="activeTab === 'audit'" :events="auditLog" :format-date="formatDate" />
							</section>

							<UserDetailActionsMenu
								:is-banned="isBanned"
								:can-master="canMaster"
								@reset-password="showResetConfirm = true"
								@toggle-ban="showBanConfirm = true"
								@change-email="askChangeEmail"
								@impersonate="showImpersonateConfirm = true" />
						</template>

						<div v-else class="px-6 py-10 text-center text-brand-text-muted text-sm bg-brand-bg-alt rounded-control">
							Impossibile caricare l'utente.
						</div>
					</div>
				</aside>
			</div>
		</Transition>
	</Teleport>

	<AccountConfirmDialog
		v-model:open="showResetConfirm"
		title="Invia reset password"
		:description="`Stai per inviare a ${user?.email} un'email per reimpostare la password.`"
		confirm-label="Invia email"
		tone="primary"
		:loading="saving"
		@confirm="doResetPassword" />

	<AccountConfirmDialog
		v-model:open="showBanConfirm"
		:title="isBanned ? 'Rimuovi ban' : 'Banna utente'"
		:description="isBanned
			? `Stai per ripristinare l'accesso di ${fullName}.`
			: `Stai per bannare ${fullName}. L'utente non potra piu accedere finche non rimuovi il ban.`"
		:confirm-label="isBanned ? 'Rimuovi ban' : 'Banna'"
		:tone="isBanned ? 'primary' : 'danger'"
		:loading="saving"
		@confirm="doBanToggle" />

	<AccountConfirmDialog
		v-model:open="showImpersonateConfirm"
		title="Impersona utente"
		:description="`Stai per accedere come ${fullName}. Tutte le azioni saranno tracciate.`"
		confirm-label="Impersona ora"
		tone="primary"
		:loading="saving"
		@confirm="doImpersonate" />

	<UserDetailChangeEmailModal
		v-if="canMaster"
		v-model:open="showEmailModal"
		v-model:email="newEmail"
		:full-name="fullName"
		:saving="saving"
		@confirm="doChangeEmail" />
</template>
