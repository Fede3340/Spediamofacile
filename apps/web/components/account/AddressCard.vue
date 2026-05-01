<script setup>
const props = defineProps({
	address: { type: Object, required: true },
	confirmDelete: { type: Boolean, default: false },
	deleting: { type: Boolean, default: false },
});

const emit = defineEmits(['edit', 'set-default', 'request-delete', 'confirm-delete', 'cancel-delete']);

const isOrigin = computed(() => String(props.address?.type || '').toLowerCase() === 'origin');
const isCompany = computed(() => String(props.address?.profile || '').toLowerCase() === 'company');

const typeLabel = computed(() => (isOrigin.value ? 'Partenza' : 'Destinazione'));
const profileLabel = computed(() => (isCompany.value ? 'Azienda' : 'Privato'));

const formattedStreet = computed(() => {
	const parts = [props.address?.address, props.address?.address_number].filter(Boolean);
	return parts.join(', ');
});

const formattedCity = computed(() => {
	const province = props.address?.province ? `(${String(props.address.province).slice(0, 2)})` : '';
	return [props.address?.postal_code, props.address?.city, province].filter(Boolean).join(' ');
});

const recipientName = computed(() => {
	if (isCompany.value && props.address?.company_name) return props.address.company_name;
	return props.address?.name || 'Senza nome';
});
</script>

<template>
	<article
		:class="[
			'relative flex flex-col gap-3 rounded-card border p-4 shadow-sf-sm transition-colors',
			address.default
				? 'border-brand-primary/25 bg-brand-primary/[0.02] hover:border-brand-primary/30'
				: 'border-brand-primary/10 bg-brand-card hover:border-brand-primary/20',
		]"
	>
		<header class="flex items-center justify-between gap-2">
			<div class="flex flex-wrap gap-1.5">
				<span :class="[
					'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[0.6875rem] font-bold uppercase leading-none tracking-wide',
					isOrigin ? 'bg-brand-primary/10 text-brand-primary' : 'bg-brand-accent/10 text-brand-accent-dark',
				]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
						<path v-if="isOrigin" d="M12,2A7,7 0 0,1 19,9C19,14.25 12,22 12,22C12,22 5,14.25 5,9A7,7 0 0,1 12,2M12,4A5,5 0 0,0 7,9C7,10 7,12 12,18.71C17,12 17,10 17,9A5,5 0 0,0 12,4M12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5Z" />
						<path v-else d="M12,2L4.5,20.29L5.21,21L12,18L18.79,21L19.5,20.29L12,2Z" />
					</svg>
					{{ typeLabel }}
				</span>
				<span v-if="address.default" class="inline-flex items-center gap-1 rounded-full bg-gradient-to-br from-brand-primary to-[#0b6e7d] px-2.5 py-1 text-[0.6875rem] font-bold uppercase leading-none tracking-wide text-white">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
						<path d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z" />
					</svg>
					Predefinito
				</span>
				<span v-if="isCompany" class="inline-flex items-center rounded-full bg-brand-bg-alt px-2.5 py-1 text-[0.6875rem] font-bold uppercase leading-none tracking-wide text-brand-text-secondary">{{ profileLabel }}</span>
			</div>
		</header>

		<p class="m-0 flex items-center gap-1.5 text-sm font-semibold text-brand-text">
			<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" class="shrink-0 text-brand-primary">
				<path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
			</svg>
			<span>{{ recipientName }}</span>
		</p>

		<div class="flex flex-col gap-0.5 rounded-xl border border-brand-primary/[0.06] bg-surface-raised px-3 py-2.5">
			<p class="m-0 text-sm font-bold text-brand-text">{{ formattedStreet || '—' }}</p>
			<p class="m-0 text-[0.8125rem] text-brand-text-secondary">{{ formattedCity || '—' }}</p>
		</div>

		<dl v-if="address.telephone_number || address.email" class="m-0 flex flex-wrap gap-3 text-xs text-brand-text-secondary">
			<div v-if="address.telephone_number">
				<dt class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Telefono</dt>
				<dd class="m-0 mt-0.5 break-words text-brand-text">{{ address.telephone_number }}</dd>
			</div>
			<div v-if="address.email">
				<dt class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Email</dt>
				<dd class="m-0 mt-0.5 break-words text-brand-text">{{ address.email }}</dd>
			</div>
		</dl>

		<footer class="mt-auto flex flex-wrap items-center gap-1.5 border-t border-brand-primary/[0.08] pt-3">
			<template v-if="!confirmDelete">
				<button
					type="button"
					class="inline-flex items-center gap-1 rounded-[10px] border border-brand-primary/15 bg-brand-primary/[0.06] px-2.5 py-1.5 text-xs font-semibold text-brand-primary transition hover:border-brand-primary/25 hover:bg-brand-primary/10 disabled:cursor-not-allowed disabled:opacity-60"
					aria-label="Modifica indirizzo"
					@click="emit('edit', address)"
				>
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
						<path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z" />
					</svg>
					<span>Modifica</span>
				</button>
				<button
					v-if="!address.default"
					type="button"
					class="inline-flex items-center gap-1 rounded-[10px] border border-brand-accent/15 bg-brand-accent/[0.06] px-2.5 py-1.5 text-xs font-semibold text-brand-accent-dark transition hover:bg-brand-accent/10 disabled:cursor-not-allowed disabled:opacity-60"
					aria-label="Imposta come predefinito"
					@click="emit('set-default', address)"
				>
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
						<path d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z" />
					</svg>
					<span>Predefinito</span>
				</button>
				<button
					type="button"
					class="inline-flex items-center gap-1 rounded-[10px] border border-status-failed-fg/20 bg-transparent px-2.5 py-1.5 text-xs font-semibold text-status-failed-fg transition hover:border-status-failed-fg/35 hover:bg-status-failed-fg/[0.08] disabled:cursor-not-allowed disabled:opacity-60"
					aria-label="Elimina indirizzo"
					@click="emit('request-delete', address.id)"
				>
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
						<path d="M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19M8,9H16V19H8V9M15.5,4L14.5,3H9.5L8.5,4H5V6H19V4H15.5Z" />
					</svg>
					<span>Elimina</span>
				</button>
			</template>
			<template v-else>
				<p class="m-0 mb-1 flex-[1_1_100%] text-[0.8125rem] font-semibold text-status-failed-fg">Eliminare questo indirizzo?</p>
				<button
					type="button"
					class="inline-flex items-center gap-1 rounded-[10px] border border-status-failed-fg bg-status-failed-fg px-2.5 py-1.5 text-xs font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
					:disabled="deleting"
					@click="emit('confirm-delete', address.id)"
				>
					{{ deleting ? 'Eliminazione…' : 'Sì, elimina' }}
				</button>
				<button
					type="button"
					class="inline-flex items-center gap-1 rounded-[10px] border border-black/[0.06] bg-brand-bg-alt px-2.5 py-1.5 text-xs font-semibold text-brand-text-secondary transition hover:bg-brand-border disabled:cursor-not-allowed disabled:opacity-60"
					:disabled="deleting"
					@click="emit('cancel-delete')"
				>
					Annulla
				</button>
			</template>
		</footer>
	</article>
</template>
