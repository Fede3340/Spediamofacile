<script setup lang="ts">
/**
 * Pagina showcase del design system Sf* (dev-only).
 *
 * Solo accessibile in dev/staging. Non indicizzabile, non in sitemap.
 * Tieni qui un esempio dal vivo per ogni componente Sf* + sezione token.
 */

definePageMeta({
	layout: 'default',
	middleware: process.env.NODE_ENV === 'production' ? ['admin'] : [],
});
useHead({
	title: 'Design System — SpediamoFacile',
	meta: [{ name: 'robots', content: 'noindex, nofollow' }],
});

const checkbox = ref(false);
const radio = ref('card');
const segmented = ref('home');
const inputValue = ref('');
const selectValue = ref('');
const tab = ref('profile');
const page = ref(2);

const tableRows = ref([
	{ id: 1, status: 'paid', total: 4500 },
	{ id: 2, status: 'pending', total: 1200 },
	{ id: 3, status: 'shipped', total: 8900 },
]);
const tableColumns = [
	{ key: 'id', label: 'ID', sortable: true, width: '80px' },
	{ key: 'status', label: 'Stato' },
	{ key: 'total', label: 'Totale', align: 'right' as const },
];
</script>

<template>
	<div class="max-w-5xl mx-auto px-4 py-12 space-y-12">
		<header class="space-y-2">
			<h1 class="font-display text-4xl font-bold text-brand-text">Design System Sf*</h1>
			<p class="text-brand-text-secondary">Showcase componenti riutilizzabili. Solo dev/staging.</p>
		</header>

		<!-- Buttons -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfButton</h2>
			<div class="flex flex-wrap gap-3">
				<SfButton>Primary</SfButton>
				<SfButton variant="secondary">Secondary</SfButton>
				<SfButton variant="danger">Danger</SfButton>
				<SfButton variant="ghost">Ghost</SfButton>
				<SfButton size="sm">Small</SfButton>
				<SfButton size="lg">Large</SfButton>
				<SfButton :loading="true">Loading</SfButton>
			</div>
		</section>

		<!-- Form controls -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfFormGroup + SfInput/Textarea/Select</h2>
			<div class="grid md:grid-cols-2 gap-4 max-w-2xl">
				<SfFormGroup label="Nome" required hint="Come ti chiami">
					<SfInput v-model="inputValue" placeholder="Mario Rossi" />
				</SfFormGroup>
				<SfFormGroup label="Categoria" :error="'Campo obbligatorio'">
					<SfSelect v-model="selectValue" placeholder="Seleziona…" :options="[
						{ value: 'a', label: 'Opzione A' },
						{ value: 'b', label: 'Opzione B' },
					]" />
				</SfFormGroup>
				<SfFormGroup label="Note" class="md:col-span-2">
					<SfTextarea placeholder="Scrivi qualcosa…" />
				</SfFormGroup>
			</div>
			<div class="flex flex-wrap gap-4">
				<SfCheckbox v-model="checkbox" label="Accetto le condizioni" />
				<SfRadio v-model="radio" value="card" label="Carta" />
				<SfRadio v-model="radio" value="bonifico" label="Bonifico" />
			</div>
			<SfSegmented v-model="segmented" :options="[
				{ value: 'home', label: 'Casa', icon: 'mdi:home' },
				{ value: 'pudo', label: 'Punto BRT', icon: 'mdi:map-marker' },
			]" />
		</section>

		<!-- Cards & Stats -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfCard + SfStatCard</h2>
			<div class="grid md:grid-cols-3 gap-4">
				<SfStatCard label="Ordini" :value="42" trend="up" trend-label="+8% mese" icon="mdi:package" tone="primary" />
				<SfStatCard label="Spedizioni" :value="38" trend="down" trend-label="-2% mese" icon="mdi:truck" tone="accent" />
				<SfStatCard label="Wallet" value="125,40 €" trend="up" trend-label="+15 €" icon="mdi:wallet" tone="success" />
			</div>
			<SfCard title="Profilo cliente" description="Dati personali e fatturazione">
				<template #icon>
					<UIcon name="mdi:account" class="h-6 w-6 text-brand-primary" />
				</template>
				<p class="text-brand-text-secondary">Body content della card.</p>
				<template #footer>
					<SfButton variant="secondary">Annulla</SfButton>
					<SfButton>Salva</SfButton>
				</template>
			</SfCard>
		</section>

		<!-- Badges & Status -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfBadge + SfStatusPill + SfAddressChip</h2>
			<div class="flex flex-wrap gap-2">
				<SfBadge tone="neutral">Neutral</SfBadge>
				<SfBadge tone="primary">Primary</SfBadge>
				<SfBadge tone="accent">Accent</SfBadge>
				<SfBadge tone="success" icon="mdi:check">Success</SfBadge>
				<SfBadge tone="warning" icon="mdi:alert">Warning</SfBadge>
				<SfBadge tone="danger" icon="mdi:close">Danger</SfBadge>
				<SfBadge tone="info">Info</SfBadge>
			</div>
			<div class="flex flex-wrap gap-2">
				<SfStatusPill status="pending" />
				<SfStatusPill status="paid" />
				<SfStatusPill status="shipped" />
				<SfStatusPill status="delivered" />
				<SfStatusPill status="failed" />
				<SfStatusPill status="refunded" />
				<SfStatusPill status="cancelled" />
			</div>
			<div class="flex flex-wrap gap-2">
				<SfAddressChip variant="origin" />
				<SfAddressChip variant="destination" />
				<SfAddressChip variant="default" />
			</div>
		</section>

		<!-- Avatars -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfAvatar</h2>
			<div class="flex items-center gap-3">
				<SfAvatar size="xs" name="Mario Rossi" />
				<SfAvatar size="sm" name="Anna Bianchi" />
				<SfAvatar size="md" name="Luca Verdi" />
				<SfAvatar size="lg" name="Elena Neri" />
				<SfAvatar size="xl" />
			</div>
		</section>

		<!-- Alerts -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfAlert</h2>
			<div class="space-y-3">
				<SfAlert tone="info" title="Informazione">Il tuo ordine è in elaborazione.</SfAlert>
				<SfAlert tone="success" title="Pagamento riuscito">Riceverai email di conferma.</SfAlert>
				<SfAlert tone="warning" title="Attenzione" dismissible>Hai 3 ordini in attesa.</SfAlert>
				<SfAlert tone="danger" title="Errore">Impossibile contattare il server.</SfAlert>
			</div>
		</section>

		<!-- Tabs -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfTabs</h2>
			<SfTabs v-model="tab" :items="[
				{ id: 'profile', label: 'Profilo', icon: 'mdi:account' },
				{ id: 'security', label: 'Sicurezza', icon: 'mdi:lock', count: 2 },
				{ id: 'billing', label: 'Fatturazione', icon: 'mdi:receipt' },
			]" />
			<SfTabs v-model="tab" variant="pills" :items="[
				{ id: 'profile', label: 'Profilo' },
				{ id: 'security', label: 'Sicurezza' },
				{ id: 'billing', label: 'Fatturazione' },
			]" />
		</section>

		<!-- Empty state -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfEmptyState</h2>
			<SfCard padding="none">
				<SfEmptyState
					icon="mdi:package-variant-closed"
					title="Nessun ordine ancora"
					description="Crea il tuo primo preventivo per iniziare a spedire."
				>
					<template #cta>
						<SfButton>Nuovo preventivo</SfButton>
					</template>
				</SfEmptyState>
			</SfCard>
			<SfEmptyState
				variant="compact"
				icon="mdi:filter-off"
				title="Nessun risultato"
				description="Prova a modificare i filtri."
			/>
		</section>

		<!-- Table -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfTable</h2>
			<SfTable :rows="tableRows" :columns="tableColumns" hoverable>
				<template #status="{ row }">
					<SfStatusPill :status="row.status" />
				</template>
				<template #total="{ row }">
					<strong>{{ (row.total / 100).toFixed(2) }} €</strong>
				</template>
			</SfTable>
		</section>

		<!-- Breadcrumbs + Pagination -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfBreadcrumbs + SfPagination</h2>
			<SfBreadcrumbs :items="[
				{ label: 'Account', to: '/account', icon: 'mdi:account' },
				{ label: 'Ordini', to: '/account/spedizioni' },
				{ label: 'Ordine #123' },
			]" />
			<SfPagination v-model="page" :total="200" :per-page="20" />
		</section>

		<!-- Tooltip + Dropdown -->
		<section class="space-y-4">
			<h2 class="font-display text-xl font-bold border-b border-brand-border pb-2">SfTooltip + SfDropdown</h2>
			<div class="flex items-center gap-4">
				<SfTooltip text="Tooltip in alto" position="top">
					<SfButton variant="secondary">Hover top</SfButton>
				</SfTooltip>
				<SfTooltip text="Tooltip a destra" position="right">
					<SfButton variant="secondary">Hover right</SfButton>
				</SfTooltip>
				<SfDropdown :items="[
					{ label: 'Modifica', icon: 'mdi:pencil', onClick: () => {} },
					{ label: 'Duplica', icon: 'mdi:content-copy', onClick: () => {} },
					{ label: 'Elimina', icon: 'mdi:delete', tone: 'danger', onClick: () => {} },
				]">
					<SfButton variant="secondary">Azioni ▾</SfButton>
				</SfDropdown>
			</div>
		</section>
	</div>
</template>
