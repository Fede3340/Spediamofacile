<!-- AdminUserTable.vue — Tabella utenti admin (mobile cards + desktop table) -->
<script setup>
import AdminTableLayout from './AdminTableLayout.vue';
import AdminStatusBadge from './AdminStatusBadge.vue';

defineProps({
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

const STATUS_MAP = {
	active: { tone: 'success', label: 'Attivo' },
	banned: { tone: 'danger', label: 'Bannato' },
	'pending-verification': { tone: 'warning', label: 'In verifica' },
	pending: { tone: 'warning', label: 'In verifica' },
};

const STATUS_TONE_CLASS = {
	success: 'bg-brand-success-bg text-brand-success-fg',
	danger: 'bg-red-50 text-red-700',
	warning: 'bg-amber-50 text-amber-700',
	neutral: 'bg-brand-bg-alt text-brand-text-secondary',
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
			<SfEmptyState
				icon="mdi:account-search-outline"
				title="Nessun utente trovato"
				description="Modifica i filtri o la ricerca per ampliare i risultati." />
		</template>

		<template #mobile-card="{ item }">
			<article class="p-4 rounded-card border border-brand-border bg-brand-card transition hover:border-brand-primary/40">
				<header class="flex items-center gap-3 mb-3">
					<div class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-brand-soft-bg text-brand-primary font-extrabold text-sm shrink-0" aria-hidden="true">
						{{ initials(item) }}
					</div>
					<div class="flex-1 min-w-0">
						<p class="m-0 text-sm font-bold text-brand-text truncate">{{ item.name }} {{ item.surname }}</p>
						<p class="m-0 text-xs text-brand-text-secondary truncate">{{ item.email }}</p>
					</div>
					<AdminStatusBadge :status="item.role || 'User'" type="role" />
				</header>

				<dl class="grid grid-cols-2 gap-2 m-0 mb-3 p-3 bg-brand-bg-alt border border-brand-border rounded-control">
					<div>
						<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Tipo</dt>
						<dd class="mt-0.5 text-xs font-semibold text-brand-text">{{ userTypeLabel(item) }}</dd>
					</div>
					<div>
						<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Ordini</dt>
						<dd class="mt-0.5 text-xs font-semibold text-brand-text">{{ item.orders_count ?? 0 }}</dd>
					</div>
					<div>
						<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Ultimo accesso</dt>
						<dd class="mt-0.5 text-xs font-semibold text-brand-text">{{ formatDate(lastLogin(item)) }}</dd>
					</div>
					<div>
						<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Stato</dt>
						<dd class="mt-0.5">
							<span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold', STATUS_TONE_CLASS[userStatus(item).tone]]">
								{{ userStatus(item).label }}
							</span>
						</dd>
					</div>
				</dl>

				<footer class="flex flex-wrap gap-1.5">
					<SfButton size="sm" @click="emit('view', item)">
						<template #leading><UIcon name="mdi:eye-outline" class="w-3.5 h-3.5" /></template>
						Dettaglio
					</SfButton>
					<SfButton variant="secondary" size="sm" @click="emit('edit', item)">
						<template #leading><UIcon name="mdi:pencil" class="w-3.5 h-3.5" /></template>
						Modifica
					</SfButton>
					<SfButton
						v-if="canImpersonate"
						variant="accent"
						size="sm"
						:disabled="actionLoading === `imp-${item.id}`"
						@click="emit('impersonate', item)">
						<template #leading><UIcon name="mdi:account-arrow-right" class="w-3.5 h-3.5" /></template>
						Impersona
					</SfButton>
				</footer>
			</article>
		</template>

		<template #desktop-row="{ item }">
			<tr class="border-b border-brand-border last:border-0 hover:bg-brand-bg-alt transition">
				<td class="py-3 px-3">
					<div class="flex items-center gap-3 min-w-0">
						<span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-soft-bg text-brand-primary font-extrabold text-xs shrink-0" aria-hidden="true">{{ initials(item) }}</span>
						<div class="min-w-0">
							<p class="m-0 text-sm font-bold text-brand-text truncate" :title="`${item.name} ${item.surname}`">{{ item.name }} {{ item.surname }}</p>
							<p class="m-0 text-xs text-brand-text-secondary truncate" :title="item.email">{{ item.email }}</p>
						</div>
					</div>
				</td>
				<td class="py-3 px-3">
					<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold bg-brand-bg-alt text-brand-text-secondary">{{ userTypeLabel(item) }}</span>
				</td>
				<td class="py-3 px-3">
					<AdminStatusBadge :status="item.role || 'User'" type="role" />
				</td>
				<td class="py-3 px-3 text-sm font-semibold text-brand-text tabular-nums">{{ item.orders_count ?? 0 }}</td>
				<td class="py-3 px-3 text-xs text-brand-text-secondary whitespace-nowrap">{{ formatDate(lastLogin(item)) }}</td>
				<td class="py-3 px-3">
					<span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold', STATUS_TONE_CLASS[userStatus(item).tone]]">
						{{ userStatus(item).label }}
					</span>
				</td>
				<td class="py-3 px-3">
					<div class="flex items-center gap-1">
						<button
							type="button"
							class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-primary/40 bg-brand-soft-bg text-brand-primary cursor-pointer transition hover:bg-brand-primary hover:text-white"
							:title="`Dettaglio ${item.name}`"
							:aria-label="`Dettaglio ${item.name}`"
							@click="emit('view', item)">
							<UIcon name="mdi:eye-outline" class="w-4 h-4" />
						</button>
						<button
							type="button"
							class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary cursor-pointer transition hover:bg-brand-bg-alt hover:text-brand-text"
							:title="`Modifica ${item.name}`"
							:aria-label="`Modifica ${item.name}`"
							@click="emit('edit', item)">
							<UIcon name="mdi:pencil" class="w-4 h-4" />
						</button>
						<button
							v-if="canImpersonate"
							type="button"
							class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-accent bg-brand-accent text-white cursor-pointer transition hover:brightness-95 disabled:opacity-50"
							:disabled="actionLoading === `imp-${item.id}`"
							:title="`Impersona ${item.name}`"
							:aria-label="`Impersona ${item.name}`"
							@click="emit('impersonate', item)">
							<UIcon name="mdi:account-arrow-right" class="w-4 h-4" />
						</button>
					</div>
				</td>
			</tr>
		</template>
	</AdminTableLayout>
</template>
