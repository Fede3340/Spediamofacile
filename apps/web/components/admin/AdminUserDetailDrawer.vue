<!-- AdminUserDetailDrawer.vue — Drawer dettaglio utente admin (M9).
     Orchestratore slim: usa i sub-component in `components/admin/user-detail/` per
     header, profilo, permessi, tab e azioni. Logica fetch/save resta qui. -->
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

/* ===== State ===== */
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

/* ===== Computed ===== */
const fullName = computed(() => user.value
	? `${user.value.name || ''} ${user.value.surname || ''}`.trim()
	: '');
const isBanned = computed(() => form.value.status === 'banned');
const tabs = [
	{ key: 'orders', label: 'Ordini' },
	{ key: 'addresses', label: 'Indirizzi' },
	{ key: 'wallet', label: 'Wallet' },
	{ key: 'audit', label: 'Audit log' },
];

/* ===== Helpers ===== */
const formatTxAmount = (cents) => {
	if (typeof formatPrice === 'function') return formatPrice(cents);
	const n = Number(cents) / 100;
	return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n);
};

/* ===== Fetch ===== */
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

/* ===== Save profilo ===== */
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

/* ===== Reset password ===== */
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

/* ===== Ban / Unban ===== */
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

/* ===== Cambia email (admin-master) ===== */
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

/* ===== Impersonate ===== */
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
		<Transition name="drawer-fade">
			<div v-if="open" class="admin-drawer-overlay" @click.self="close">
				<aside class="admin-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
					<UserDetailHeader :user="user" @close="close" />

					<div class="admin-drawer__body">
						<div v-if="loading" class="admin-drawer__loading">
							<div class="admin-drawer__spinner" aria-hidden="true" />
							<p>Caricamento dettaglio in corso...</p>
						</div>

						<template v-else-if="user">
							<UserDetailProfileSection :user="user" :format-date="formatDate" />

							<UserDetailPermissionsForm v-model="form" :saving="saving" @save="saveProfile" />

							<section class="admin-drawer-section">
								<div class="admin-drawer-tabs" role="tablist">
									<button
										v-for="t in tabs"
										:key="t.key"
										type="button"
										role="tab"
										:aria-selected="activeTab === t.key"
										:class="['admin-drawer-tab', activeTab === t.key && 'admin-drawer-tab--active']"
										@click="activeTab = t.key">
										{{ t.label }}
									</button>
								</div>

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

						<div v-else class="admin-drawer-empty admin-drawer-empty--lg">
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
		:description="`Stai per inviare a ${user?.email} un'email per reimpostare la password. L'utente potra creare una nuova password tramite il link.`"
		confirm-label="Invia email"
		tone="primary"
		:loading="saving"
		@confirm="doResetPassword" />

	<AccountConfirmDialog
		v-model:open="showBanConfirm"
		:title="isBanned ? 'Rimuovi ban' : 'Banna utente'"
		:description="isBanned
			? `Stai per ripristinare l'accesso di ${fullName}. L'utente potra di nuovo effettuare il login.`
			: `Stai per bannare ${fullName}. L'utente non potra piu accedere finche non rimuovi il ban.`"
		:confirm-label="isBanned ? 'Rimuovi ban' : 'Banna'"
		:tone="isBanned ? 'primary' : 'danger'"
		:loading="saving"
		@confirm="doBanToggle" />

	<AccountConfirmDialog
		v-model:open="showImpersonateConfirm"
		title="Impersona utente"
		:description="`Stai per accedere come ${fullName}. Tutte le azioni saranno tracciate nell'audit log. Al termine dovrai effettuare il logout per tornare al tuo account.`"
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

<style scoped>
/* sf-admin-user-detail.css — stili overlay drawer + tab generici. */
.admin-drawer-overlay {
	position: fixed; inset: 0; z-index: 100;
	background: rgba(15, 25, 35, 0.36);
	backdrop-filter: blur(4px);
	display: flex; justify-content: flex-end;
}
.admin-drawer {
	background: #fff;
	width: min(640px, 100vw);
	height: 100vh;
	display: flex; flex-direction: column;
	box-shadow: -8px 0 24px rgba(15, 25, 35, 0.12);
	overflow: hidden;
}
.admin-drawer__body {
	flex: 1;
	overflow-y: auto;
	padding: 20px;
	display: flex; flex-direction: column; gap: 18px;
}
.admin-drawer__loading {
	display: flex; flex-direction: column; align-items: center; gap: 12px;
	padding: 60px 20px; color: #5b6b7d;
}
.admin-drawer__spinner {
	width: 32px; height: 32px;
	border: 3px solid #e3eaf0; border-top-color: #095866;
	border-radius: 50%;
	animation: drawer-spin .9s linear infinite;
}
@keyframes drawer-spin { to { transform: rotate(360deg); } }
.admin-drawer-section { background: #fff; border-radius: 14px; }
.admin-drawer-tabs {
	display: flex; gap: 4px;
	border-bottom: 1px solid #e3eaf0;
	margin-bottom: 14px;
}
.admin-drawer-tab {
	padding: 8px 14px;
	background: transparent; border: 0;
	font-size: 13px; font-weight: 600;
	color: #5b6b7d; cursor: pointer;
	border-bottom: 2px solid transparent;
}
.admin-drawer-tab--active { color: #095866; border-bottom-color: #095866; }
.admin-drawer-empty {
	padding: 18px; text-align: center;
	color: #6b7a87; font-size: 13.5px;
	background: #f7f9fb; border-radius: 10px;
}
.admin-drawer-empty--lg { padding: 40px 20px; }

.drawer-fade-enter-active, .drawer-fade-leave-active { transition: opacity 200ms ease; }
.drawer-fade-enter-from, .drawer-fade-leave-to { opacity: 0; }
</style>
