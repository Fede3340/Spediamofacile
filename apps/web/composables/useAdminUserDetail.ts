/**
 * @file useAdminUserDetail — Composable useAdminUserDetail.
 */
import { ref, computed, onBeforeUnmount } from 'vue';

/**
 * Stato + mutazioni per il drawer dettaglio utente admin.
 * Centralizza fetch del singolo utente, salvataggio profilo, ban/unban,
 * reset password, cambio email e impersonate.
 */
export function useAdminUserDetail(emit: (e: 'updated' | 'impersonate', payload?) => void) {
	const sanctum = useSanctumClient();
	const { showSuccess, showError } = useAdmin();

	const loading = ref(false);
	const saving = ref(false);
	const user = ref(null);
	const orders = ref([]);
	const addresses = ref([]);
	const walletTx = ref([]);
	const auditLog = ref([]);

	const form = ref({
		role: 'User',
		status: 'active',
		is_pro,
	});

	const isBanned = computed(() => form.value.status === 'banned');

	const fetchDetail = async (userId) => {
		if (!userId) return;
		loading.value = true;
		try {
			const res = await sanctum(`/api/admin/users/${userId}`);
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

	const saveProfile = async () => {
		if (!user.value) return;
		saving.value = true;
		try {
			await sanctum(`/api/admin/users/${user.value.id}`, {
				method: 'PATCH',
				body: { ...form.value },
			});
			showSuccess('Profilo aggiornato correttamente.');
			emit('updated');
			await fetchDetail(user.value.id);
		} catch (e) {
			showError(e, "Errore durante l'aggiornamento del profilo.");
		} finally {
			saving.value = false;
		}
	};

	const resetPassword = async () => {
		if (!user.value) return false;
		saving.value = true;
		try {
			await sanctum(`/api/admin/users/${user.value.id}/reset-password`, { method: 'POST' });
			showSuccess('Email di reset password inviata.');
			return true;
		} catch (e) {
			showError(e, "Errore durante l'invio del reset password.");
			return false;
		} finally {
			saving.value = false;
		}
	};

	const toggleBan = async () => {
		if (!user.value) return false;
		saving.value = true;
		try {
			const next = isBanned.value ? 'active' : 'banned';
			await sanctum(`/api/admin/users/${user.value.id}`, {
				method: 'PATCH',
				body: { status: next },
			});
			showSuccess(next === 'banned' ? 'Utente bannato.' : 'Ban rimosso.');
			emit('updated');
			await fetchDetail(user.value.id);
			return true;
		} catch (e) {
			showError(e, "Errore durante l'operazione di ban.");
			return false;
		} finally {
			saving.value = false;
		}
	};

	const changeEmail = async (newEmail) => {
		if (!user.value || !newEmail) return false;
		saving.value = true;
		try {
			await sanctum(`/api/admin/users/${user.value.id}`, {
				method: 'PATCH',
				body: { email: newEmail },
			});
			showSuccess('Email aggiornata.');
			emit('updated');
			await fetchDetail(user.value.id);
			return true;
		} catch (e) {
			showError(e, 'Errore durante il cambio email.');
			return false;
		} finally {
			saving.value = false;
		}
	};

	// Track del timer di redirect post-impersonate per cleanup su unmount
	// (se l'utente naviga via prima del redirect, evita callback "zombie").
	let impersonateRedirectTimer | null = null;

	const impersonate = async () => {
		if (!user.value) return false;
		saving.value = true;
		try {
			await sanctum(`/api/admin/users/${user.value.id}/impersonate`, { method: 'POST' });
			showSuccess('Sessione impersonata avviata. Verrai reindirizzato.');
			emit('impersonate', user.value);
			if (impersonateRedirectTimer) clearTimeout(impersonateRedirectTimer);
			impersonateRedirectTimer = setTimeout(() => {
				impersonateRedirectTimer = null;
				window.location.href = '/account';
			}, 600);
			return true;
		} catch (e) {
			showError(e, "Errore durante l'impersona.");
			return false;
		} finally {
			saving.value = false;
		}
	};

	onBeforeUnmount(() => {
		if (impersonateRedirectTimer) {
			clearTimeout(impersonateRedirectTimer);
			impersonateRedirectTimer = null;
		}
	});

	return {
		loading,
		saving,
		user,
		orders,
		addresses,
		walletTx,
		auditLog,
		form,
		isBanned,
		fetchDetail,
		saveProfile,
		resetPassword,
		toggleBan,
		changeEmail,
		impersonate,
	};
}
