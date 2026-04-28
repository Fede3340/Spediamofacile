<script setup>
const props = defineProps({
	balance: { type: Object, default: null },
	isPro: { type: Boolean, default: false },
	isLoadingBalance: { type: Boolean, default: true },
	balanceError: { type: String, default: '' },
	defaultPaymentMethodLabel: { type: String, default: 'Da aggiungere' },
	movementCountLabel: { type: String, default: 'Ancora nessuno' },
	stripeConfigured: { type: Boolean, default: false },
});

const emit = defineEmits(['retry-balance']);

const hasBalanceData = computed(() => props.balance?.balance != null || props.balance?.commission_balance != null);
const hasBlockingError = computed(() => Boolean(props.balanceError) && !hasBalanceData.value);
const hasStaleData = computed(() => Boolean(props.balanceError) && hasBalanceData.value);

const statusLabel = computed(() => {
	if (props.isLoadingBalance && !hasBalanceData.value) return 'Caricamento';
	if (hasBlockingError.value) return 'Da verificare';
	if (hasStaleData.value) return 'Ultimo dato disponibile';
	return 'Aggiornato';
});

const overviewCards = computed(() => {
	const cards = [
		{
			label: 'Saldo disponibile',
			value: props.isLoadingBalance && !hasBalanceData.value ? 'Caricamento' : `EUR ${formatEuro(props.balance?.balance || 0)}`,
			description: 'Usabile subito per pagamenti e spedizioni.',
			tone: 'bg-[#F3FAFB] border-[#D8EBEF] text-[var(--color-brand-primary)]',
		},
		{
			label: 'Carta predefinita',
			value: props.defaultPaymentMethodLabel,
			description: props.stripeConfigured ? 'Pronta per le prossime ricariche.' : 'Attiva una carta per ricaricare.',
			tone: 'bg-[#FFFFFF] border-[#E6EDF0] text-[var(--color-brand-text)]',
		},
		{
			label: 'Storico',
			value: props.movementCountLabel,
			description: 'Movimenti e rimborsi sempre nello stesso flusso.',
			tone: 'bg-[#F8FAFB] border-[#E8EEF1] text-[var(--color-brand-text)]',
		},
	];

	if (props.isPro) {
		cards.push({
			label: 'Commissioni Pro',
			value: props.isLoadingBalance && !hasBalanceData.value ? 'Caricamento' : `EUR ${formatEuro(props.balance?.commission_balance || 0)}`,
			description: 'Restano separate dal saldo wallet.',
			tone: 'bg-[#F4FBF6] border-[#DCEFE2] text-[#15803D]',
		});
	}

	return cards;
});

const quickLinks = computed(() => {
	const links = [
		{
			label: 'Carte e pagamenti',
			description: 'Gestisci metodo predefinito e ricariche.',
			to: '/account/carte',
			icon: 'card',
		},
		{
			label: 'Le tue spedizioni',
			description: 'Controlla ordini pagati e tracking.',
			to: '/account/spedizioni',
			icon: 'shipping',
		},
	];

	// -- ARCHIVIATO 2026-04-20: link quick "Prelievi" (_archive/frontend-simplification-2026-04-20/features/prelievi-dedicati) --
	// if (props.isPro) {
	// 	links.push({
	// 		label: 'Prelievi',
	// 		description: 'Richiedi il trasferimento commissioni.',
	// 		to: '/account/prelievi',
	// 		icon: 'withdraw',
	// 	});
	// }

	return links;
});
</script>

<template>
	<div class="rounded-[16px] border border-[rgba(9,88,102,0.12)] bg-white px-[16px] py-[14px]" style="box-shadow: 0 2px 8px rgba(9,88,102,0.04);">
		<div
			v-if="balanceError"
			:class="[
				'mb-[16px] flex flex-col gap-[10px] rounded-[16px] border px-[12px] py-[11px] text-[0.8125rem] tablet:flex-row tablet:items-center tablet:justify-between',
				hasBlockingError ? 'border-[#F3C1C1] bg-[#FEF2F2] text-[#B42318]' : 'border-[#F3D1A7] bg-[#FFF7E8] text-[#B45309]',
			]">
			<p class="leading-[1.5]">
				{{
					hasBlockingError
						? balanceError
						: 'Non sono riuscito ad aggiornare il saldo in tempo reale. Ti mostro l ultimo valore disponibile.'
				}}
			</p>
			<SfButton variant="secondary" size="sm" @click="emit('retry-balance')">Riprova saldo</SfButton>
		</div>

		<!-- P15: "Panoramica rapida" + 2 sub-card overview RIMOSSE.
		     Saldo+Carta sono già nei chip header + hero wallet sopra. Era duplicazione
		     che rendeva la pagina un disastro disordinato come segnalato da utente. -->
		<div class="mb-[12px] flex items-center justify-between">
			<h2 class="font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)]">Accesso rapido</h2>
			<span class="inline-flex items-center rounded-full bg-[#F5F6F9] px-[10px] py-[3px] text-[0.6875rem] font-semibold text-[var(--color-brand-text-secondary)]">
				{{ statusLabel }}
			</span>
		</div>

		<div class="mt-[14px] grid gap-[10px] tablet:grid-cols-2">
			<NuxtLink
				v-for="link in quickLinks"
				:key="link.to"
				:to="link.to"
				class="flex items-start gap-[10px] rounded-[16px] border border-[#E6EDF0] bg-[#FCFDFD] px-[14px] py-[13px] transition-colors hover:border-[#BFD8DE] hover:bg-white">
				<div class="mt-[1px] flex h-[34px] w-[34px] items-center justify-center rounded-full bg-[#EDF7F8] text-[var(--color-brand-primary)]">
					<svg
						v-if="link.icon === 'card'"
						aria-hidden="true"
						width="16"
						height="16"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round">
						<rect x="1" y="4" width="22" height="16" rx="2" />
						<line x1="1" y1="10" x2="23" y2="10" />
					</svg>
					<svg
						v-else-if="link.icon === 'shipping'"
						aria-hidden="true"
						width="16"
						height="16"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round">
						<path d="M1 3h15v13H1z" />
						<path d="M16 8h4l3 3v5h-7V8z" />
						<circle cx="5.5" cy="18.5" r="2.5" />
						<circle cx="18.5" cy="18.5" r="2.5" />
					</svg>
					<svg
						v-else
						aria-hidden="true"
						width="16"
						height="16"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round">
						<path d="M5 12h14" />
						<path d="M12 5l7 7-7 7" />
					</svg>
				</div>
				<div class="min-w-0">
					<p class="text-[0.875rem] font-[700] text-[var(--color-brand-text)]">{{ link.label }}</p>
					<p class="mt-[4px] text-[0.78rem] leading-[1.45] text-[var(--color-brand-text-secondary)]">{{ link.description }}</p>
				</div>
			</NuxtLink>
		</div>
	</div>
</template>
