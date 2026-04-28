<!-- AdminUserTable.vue — Tabella utenti admin (mobile cards + desktop table) -->
<script setup>
import '~/assets/css/admin.css';
import AdminTableLayout from './AdminTableLayout.vue';
import AdminStatusBadge from './AdminStatusBadge.vue';

const props = defineProps({
	users: { type: Array, default: () => [] },
	actionLoading: { type: [String, Number, null], default: null },
	canImpersonate: { type: Boolean, default: false },
	formatDate: { type: Function, required: true },
});

const emit = defineEmits(['view', 'edit', 'impersonate']);

const columns = [
	{ key: 'user', label: 'Utente', width: '30%' },
	{ key: 'type', label: 'Tipo', width: '10%' },
	{ key: 'role', label: 'Ruolo', width: '12%' },
	{ key: 'orders', label: 'Ordini', width: '8%' },
	{ key: 'last', label: 'Ultimo', width: '12%' },
	{ key: 'status', label: 'Stato', width: '10%' },
	{ key: 'actions', label: 'Azioni', width: '18%' },
];

const initials = (user) => {
	const first = (user?.name?.[0] || '').toUpperCase();
	const last = (user?.surname?.[0] || '').toUpperCase();
	return `${first}${last}` || '?';
};

const userTypeLabel = (u) => {
	const t = (u?.user_type || 'privato').toLowerCase();
	if (t === 'commerciante' || t === 'azienda') return 'Azienda';
	return 'Privato';
};

/* Mappa stato -> { tone, label } per chip locale (status non e nel ROLE_MAP del badge) */
const STATUS_MAP = {
	active: { tone: 'success', label: 'Attivo' },
	banned: { tone: 'danger', label: 'Bannato' },
	'pending-verification': { tone: 'warning', label: 'In verifica' },
	pending: { tone: 'warning', label: 'In verifica' },
};

const userStatus = (u) => {
	if (u?.status && STATUS_MAP[u.status]) return { key: u.status, ...STATUS_MAP[u.status] };
	if (u?.banned_at) return { key: 'banned', ...STATUS_MAP.banned };
	if (!u?.email_verified_at) return { key: 'pending-verification', ...STATUS_MAP['pending-verification'] };
	return { key: 'active', ...STATUS_MAP.active };
};

const lastLogin = (u) => u?.last_login_at || u?.last_seen_at || u?.updated_at;
</script>

<template>
	<AdminTableLayout :items="users" :columns="columns" row-key="id">
		<template #empty>
			<div class="admin-user-table-empty">
				<div class="admin-user-table-empty__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
				</div>
				<h3 class="admin-user-table-empty__title">Nessun utente trovato</h3>
				<p class="admin-user-table-empty__text">Modifica i filtri o la ricerca per ampliare i risultati.</p>
			</div>
		</template>

		<!-- Mobile cards (< 720px) -->
		<template #mobile-card="{ item }">
			<article class="admin-card admin-user-card">
				<header class="admin-user-card__head">
					<div class="admin-user-card__avatar" aria-hidden="true">
						<span>{{ initials(item) }}</span>
					</div>
					<div class="admin-user-card__identity">
						<p class="admin-user-card__name">{{ item.name }} {{ item.surname }}</p>
						<p class="admin-user-card__email">{{ item.email }}</p>
					</div>
					<AdminStatusBadge :status="item.role || 'User'" type="role" />
				</header>

				<dl class="admin-user-card__grid">
					<div class="admin-user-card__cell">
						<dt>Tipo</dt>
						<dd>{{ userTypeLabel(item) }}</dd>
					</div>
					<div class="admin-user-card__cell">
						<dt>Ordini</dt>
						<dd>{{ item.orders_count ?? 0 }}</dd>
					</div>
					<div class="admin-user-card__cell">
						<dt>Ultimo accesso</dt>
						<dd>{{ formatDate(lastLogin(item)) }}</dd>
					</div>
					<div class="admin-user-card__cell">
						<dt>Stato</dt>
						<dd>
							<span :class="['admin-user-chip', `admin-user-chip--${userStatus(item).tone}`]">
								{{ userStatus(item).label }}
							</span>
						</dd>
					</div>
				</dl>

				<footer class="admin-user-card__actions">
					<button
						type="button"
						class="admin-user-btn admin-user-btn--primary"
						@click="emit('view', item)">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
						Dettaglio
					</button>
					<button
						type="button"
						class="admin-user-btn admin-user-btn--ghost"
						@click="emit('edit', item)">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z"/></svg>
						Modifica
					</button>
					<button
						v-if="canImpersonate"
						type="button"
						class="admin-user-btn admin-user-btn--accent"
						:disabled="actionLoading === `imp-${item.id}`"
						@click="emit('impersonate', item)">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M10,17V14H3V10H10V7L15,12L10,17M10,2H19A2,2 0 0,1 21,4V20A2,2 0 0,1 19,22H10A2,2 0 0,1 8,20V18H10V20H19V4H10V6H8V4A2,2 0 0,1 10,2Z"/></svg>
						Impersona
					</button>
				</footer>
			</article>
		</template>

		<!-- Desktop row (>= 720px) -->
		<template #desktop-row="{ item }">
			<tr class="admin-row admin-user-row">
				<td class="admin-user-row__user">
					<div class="admin-user-row__user-cell">
						<span class="admin-user-row__avatar" aria-hidden="true">{{ initials(item) }}</span>
						<div class="admin-user-row__user-copy">
							<p class="admin-user-row__name" :title="`${item.name} ${item.surname}`">{{ item.name }} {{ item.surname }}</p>
							<p class="admin-user-row__email" :title="item.email">{{ item.email }}</p>
						</div>
					</div>
				</td>
				<td>
					<span class="admin-user-chip admin-user-chip--neutral">{{ userTypeLabel(item) }}</span>
				</td>
				<td>
					<AdminStatusBadge :status="item.role || 'User'" type="role" />
				</td>
				<td class="admin-user-row__num">{{ item.orders_count ?? 0 }}</td>
				<td class="admin-user-row__date">{{ formatDate(lastLogin(item)) }}</td>
				<td>
					<span :class="['admin-user-chip', `admin-user-chip--${userStatus(item).tone}`]">
						{{ userStatus(item).label }}
					</span>
				</td>
				<td class="admin-user-row__actions">
					<button
						type="button"
						class="admin-user-icon-btn admin-user-icon-btn--primary"
						:title="`Dettaglio ${item.name}`"
						:aria-label="`Dettaglio ${item.name}`"
						@click="emit('view', item)">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
					</button>
					<button
						type="button"
						class="admin-user-icon-btn"
						:title="`Modifica ${item.name}`"
						:aria-label="`Modifica ${item.name}`"
						@click="emit('edit', item)">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z"/></svg>
					</button>
					<button
						v-if="canImpersonate"
						type="button"
						class="admin-user-icon-btn admin-user-icon-btn--accent"
						:disabled="actionLoading === `imp-${item.id}`"
						:title="`Impersona ${item.name}`"
						:aria-label="`Impersona ${item.name}`"
						@click="emit('impersonate', item)">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M10,17V14H3V10H10V7L15,12L10,17M10,2H19A2,2 0 0,1 21,4V20A2,2 0 0,1 19,22H10A2,2 0 0,1 8,20V18H10V20H19V4H10V6H8V4A2,2 0 0,1 10,2Z"/></svg>
					</button>
				</td>
			</tr>
		</template>
	</AdminTableLayout>
</template>
