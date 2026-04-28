/**
 * @file useAdminUtenti — gestione richieste Pro lato admin (sub-tab della pagina utenti).
 *
 * Esposto: `activeSubTab`, `proRequests`, `pendingProRequestsCount`, `fetchProRequests`,
 * `approveProRequest`, `rejectProRequest`, `proRequestStatusConfig`.
 *
 * Usato in `pages/account/amministrazione/utenti.vue`. Il composable era stato
 * rimosso durante il cleanup "orphan" del 27 apr: in realta' la pagina utenti
 * lo importa e crashava in 500 SSR ("useAdminUtenti is not defined").
 */
import { computed, ref } from 'vue';

export const proRequestStatusConfig = {
	pending: { label: 'In attesa', tone: 'warning' },
	approved: { label: 'Approvata', tone: 'success' },
	rejected: { label: 'Rifiutata', tone: 'danger' },
};

export function useAdminUtenti() {
	const sanctum = useSanctumClient();
	// Default tab "users" deve combaciare col v-if del template (non "utenti").
	const activeSubTab = ref('users');
	const proRequests = ref([]);

	const pendingProRequestsCount = computed(
		() => proRequests.value.filter((r) => (r?.status || 'pending') === 'pending').length,
	);

	const fetchProRequests = async () => {
		try {
			const res = await sanctum('/api/admin/pro-requests');
			const list = Array.isArray(res?.data) ? res.data : Array.isArray(res) ? res : [];
			proRequests.value = list;
		} catch {
			proRequests.value = [];
		}
	};

	const approveProRequest = async (id) => {
		if (!id) return false;
		try {
			await sanctum(`/api/admin/pro-requests/${id}/approve`, { method: 'PATCH' });
			await fetchProRequests();
			return true;
		} catch {
			return false;
		}
	};

	const rejectProRequest = async (id, reason = '') => {
		if (!id) return false;
		try {
			await sanctum(`/api/admin/pro-requests/${id}/reject`, {
				method: 'PATCH',
				body: { reason },
			});
			await fetchProRequests();
			return true;
		} catch {
			return false;
		}
	};

	return {
		activeSubTab,
		proRequests,
		pendingProRequestsCount,
		fetchProRequests,
		approveProRequest,
		rejectProRequest,
		proRequestStatusConfig,
	};
}
