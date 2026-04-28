<!-- COMPONENTE: AddressCard -->
<script setup>
import '~/assets/css/account.css';

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

const personalLabel = computed(() => props.address?.label || (isOrigin.value ? 'Indirizzo di partenza' : 'Indirizzo di destinazione'));
</script>

<template>
	<article
		:class="[
			'sf-address-card',
			isOrigin ? 'sf-address-card--origin' : 'sf-address-card--destination',
			address.default ? 'sf-address-card--default' : '',
		]"
	>
		<!-- Riga superiore: chip tipo + label personale -->
		<header class="sf-address-card__head">
			<div class="sf-address-card__chips">
				<span :class="['sf-address-chip', isOrigin ? 'sf-address-chip--origin' : 'sf-address-chip--destination']">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
						<path v-if="isOrigin" d="M12,2A7,7 0 0,1 19,9C19,14.25 12,22 12,22C12,22 5,14.25 5,9A7,7 0 0,1 12,2M12,4A5,5 0 0,0 7,9C7,10 7,12 12,18.71C17,12 17,10 17,9A5,5 0 0,0 12,4M12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5Z" />
						<path v-else d="M12,2L4.5,20.29L5.21,21L12,18L18.79,21L19.5,20.29L12,2Z" />
					</svg>
					{{ typeLabel }}
				</span>
				<span v-if="address.default" class="sf-address-chip sf-address-chip--default">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
						<path d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z" />
					</svg>
					Predefinito
				</span>
				<span v-if="isCompany" class="sf-address-chip sf-address-chip--neutral">{{ profileLabel }}</span>
			</div>
		</header>

		<!-- P14: titolo "Indirizzo di destinazione" rimosso (era ridondante col chip MITTENTE/DESTINAZIONE sopra) -->

		<p class="sf-address-card__recipient">
			<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
				<path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
			</svg>
			<span>{{ recipientName }}</span>
		</p>

		<div class="sf-address-card__body">
			<p class="sf-address-card__street">{{ formattedStreet || '—' }}</p>
			<p class="sf-address-card__city">{{ formattedCity || '—' }}</p>
		</div>

		<dl v-if="address.telephone_number || address.email" class="sf-address-card__contacts">
			<div v-if="address.telephone_number">
				<dt>Telefono</dt>
				<dd>{{ address.telephone_number }}</dd>
			</div>
			<div v-if="address.email">
				<dt>Email</dt>
				<dd>{{ address.email }}</dd>
			</div>
		</dl>

		<!-- Footer azioni -->
		<footer class="sf-address-card__actions">
			<template v-if="!confirmDelete">
				<button
					type="button"
					class="sf-address-action sf-address-action--edit"
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
					class="sf-address-action sf-address-action--default"
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
					class="sf-address-action sf-address-action--delete"
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
				<p class="sf-address-card__confirm-text">Eliminare questo indirizzo?</p>
				<button
					type="button"
					class="sf-address-action sf-address-action--confirm"
					:disabled="deleting"
					@click="emit('confirm-delete', address.id)"
				>
					{{ deleting ? 'Eliminazione…' : 'Sì, elimina' }}
				</button>
				<button
					type="button"
					class="sf-address-action sf-address-action--cancel"
					:disabled="deleting"
					@click="emit('cancel-delete')"
				>
					Annulla
				</button>
			</template>
		</footer>
	</article>
</template>
