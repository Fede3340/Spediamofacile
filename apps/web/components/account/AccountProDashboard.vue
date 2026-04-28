<!--
  Dashboard Partner Pro: link invito, codice account, statistiche e storico commissioni.
-->
<script setup>
import { formatDateIt } from '~/utils/date.js';
import { formatEuro, formatPrice } from '~/utils/price.js';

const props = defineProps({
	user: { type: Object, default: null },
	referralData: { type: Object, default: null },
	earnings: { type: Object, default: null },
	copied: { type: Boolean, default: false },
	copiedAccountCode: { type: Boolean, default: false },
	copiedLink: { type: Boolean, default: false },
});

const emit = defineEmits(['copy-code', 'copy-link', 'copy-account-code', 'share-whatsapp']);

const formatDate = (dateStr) => formatDateIt(dateStr);

const referralCode = computed(() => props.referralData?.referral_code || '--------');
const referralLink = computed(() => props.referralData?.referral_link || '');
const totalEarnings = computed(() => formatEuro(props.referralData?.total_earnings || 0));
const totalUsages = computed(() => props.referralData?.total_usages || 0);
const commissionBalance = computed(() => formatEuro(props.earnings?.commission_balance || 0));
const historyItems = computed(() => props.earnings?.data || []);
</script>

<template>
	<div class="space-y-[20px]">
		<div class="sf-account-panel rounded-[16px] p-[18px] desktop:p-[22px]">
			<div class="grid gap-[18px] desktop:grid-cols-[minmax(0,1.35fr)_320px] desktop:gap-[20px]">
				<div>
					<p class="text-[0.75rem] uppercase tracking-[0.08em] text-[var(--color-brand-text-secondary)] font-semibold mb-[6px]">Programma attivo</p>
					<h2 class="font-montserrat text-[1.3rem] desktop:text-[1.55rem] font-[800] text-[var(--color-brand-text)]">
						Condividi il link e tieni tutto sotto controllo
					</h2>
					<p class="text-[0.9375rem] leading-[1.6] text-[var(--color-brand-text-secondary)] mt-[8px] max-w-[700px]">
						Il link invito resta la prima azione, mentre utilizzi, commissioni e saldo restano leggibili senza aprire viste secondarie.
					</p>

					<div class="mt-[18px] rounded-[16px] border border-[rgba(9,88,102,0.08)] bg-[#F8FAFB] p-[14px] desktop:p-[16px]">
						<p class="text-[0.75rem] uppercase tracking-[0.08em] text-[var(--color-brand-text-secondary)] font-semibold mb-[6px]">Link invito</p>
						<p class="text-[0.9375rem] desktop:text-[1rem] text-[var(--color-brand-text)] break-all leading-[1.6]">
							{{ referralLink || 'Link disponibile al prossimo aggiornamento dati.' }}
						</p>
					</div>

					<div class="mt-[12px] flex flex-wrap gap-[8px]">
						<SfButton variant="primary" size="sm" @click="emit('copy-link')">{{ copiedLink ? 'Link copiato' : 'Copia link' }}</SfButton>
						<SfButton variant="secondary" size="sm" @click="emit('share-whatsapp')">WhatsApp</SfButton>
						<SfButton variant="secondary" size="sm" @click="emit('copy-code')">{{ copied ? 'Codice copiato' : 'Copia codice' }}</SfButton>
					</div>
				</div>

				<div class="grid gap-[10px] sm:grid-cols-3 desktop:grid-cols-1">
					<div class="sf-account-stat-card">
						<p class="text-[0.6875rem] uppercase tracking-[0.8px] text-[var(--color-brand-text-secondary)] font-medium">Commissioni</p>
						<p class="text-[1.4rem] font-[800] text-[var(--color-brand-text)] mt-[6px]">&euro;{{ totalEarnings }}</p>
					</div>
					<div class="sf-account-stat-card">
						<p class="text-[0.6875rem] uppercase tracking-[0.8px] text-[var(--color-brand-text-secondary)] font-medium">Utilizzi</p>
						<p class="text-[1.4rem] font-[800] text-[var(--color-brand-text)] mt-[6px]">{{ totalUsages }}</p>
					</div>
					<div class="sf-account-stat-card">
						<p class="text-[0.6875rem] uppercase tracking-[0.8px] text-[var(--color-brand-text-secondary)] font-medium">Saldo</p>
						<p class="text-[1.4rem] font-[800] text-[var(--color-brand-primary)] mt-[6px]">&euro;{{ commissionBalance }}</p>
					</div>
				</div>
			</div>
		</div>

		<div class="sf-account-stat-grid">
			<div class="sf-account-stat-card">
				<p class="text-[0.6875rem] uppercase tracking-[0.8px] text-[var(--color-brand-text-secondary)] font-medium">Codice account</p>
				<p class="text-[0.95rem] font-semibold text-[var(--color-brand-text)] mt-[6px] font-mono break-all">
					SF-PRO-{{ user?.id?.toString().padStart(6, '0') }}
				</p>
				<div class="mt-[10px] inline-flex">
					<SfButton variant="secondary" size="sm" @click="emit('copy-account-code')">{{ copiedAccountCode ? 'Copiato' : 'Copia codice' }}</SfButton>
				</div>
			</div>

			<div class="sf-account-stat-card">
				<p class="text-[0.6875rem] uppercase tracking-[0.8px] text-[var(--color-brand-text-secondary)] font-medium">Codice invito</p>
				<p class="text-[1rem] font-semibold text-[var(--color-brand-text)] mt-[6px] font-mono break-all">
					{{ referralCode }}
				</p>
				<p class="text-[0.8125rem] leading-[1.55] text-[var(--color-brand-text-secondary)] mt-[8px]">
					Usalo quando devi condividere il programma in forma breve.
				</p>
			</div>

			<!-- -- ARCHIVIATO 2026-04-20: card Prelievi (_archive/frontend-simplification-2026-04-20/features/prelievi-dedicati) -- -->
		</div>

		<div class="sf-account-panel rounded-[16px] p-[18px] desktop:p-[22px]">
			<div class="flex flex-col gap-[6px] desktop:flex-row desktop:items-end desktop:justify-between mb-[16px] desktop:mb-[20px]">
				<div>
					<p class="text-[0.75rem] uppercase tracking-[0.08em] text-[var(--color-brand-text-secondary)] font-semibold">Storico commissioni</p>
					<h2 class="font-montserrat text-[1.125rem] font-[800] text-[var(--color-brand-text)] mt-[6px]">Utilizzi recenti del programma</h2>
				</div>
				<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">
					Ordini, commissioni e stato restano leggibili in un solo posto.
				</p>
			</div>

			<div v-if="!historyItems.length" class="text-center py-[28px]">
				<div class="w-[64px] h-[64px] mx-auto mb-[16px] rounded-full bg-[#F5F6F9] flex items-center justify-center">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#C8CCD0">
						<path d="M16,11.78L20.24,4.45L21.97,5.45L16.74,14.5L10.23,10.75L5.46,19H22V21H2V3H4V17.54L9.5,8L16,11.78Z" />
					</svg>
				</div>
				<p class="text-[1rem] font-medium text-[var(--color-brand-text)]">Nessuna commissione ancora</p>
				<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)] mt-[6px]">Condividi il link invito per iniziare a raccogliere i primi utilizzi.</p>
			</div>

			<div v-else class="space-y-[12px] desktop:space-y-0">
				<div class="desktop:hidden space-y-[10px]">
					<div v-for="usage in historyItems" :key="usage.id" class="bg-[#F5F6F9] rounded-[16px] p-[14px] border border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.05)]">
						<div class="flex items-start justify-between gap-[12px]">
							<div>
								<p class="text-[0.8125rem] font-semibold text-[var(--color-brand-text)]">{{ usage.buyer?.name || 'â€”' }}</p>
								<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)] mt-[2px]">{{ formatDate(usage.created_at) }}</p>
							</div>
							<span
								:class="[
									'inline-flex items-center gap-[4px] px-[10px] py-[3px] rounded-full text-[0.6875rem] font-medium',
									usage.status === 'confirmed'
										? 'bg-[#f0fdf4] text-[#0a8a7a]'
										: usage.status === 'paid'
											? 'bg-[#eef7f8] text-[var(--color-brand-primary)]'
											: 'bg-amber-50 text-amber-700',
								]">
								{{ usage.status === 'confirmed' ? 'Confermata' : usage.status === 'paid' ? 'Pagata' : 'In attesa' }}
							</span>
						</div>
						<div class="flex items-center justify-between gap-[10px] mt-[10px] text-[0.8125rem]">
							<span class="text-[var(--color-brand-text-secondary)]">Ordine</span>
							<span class="text-[var(--color-brand-text)]">{{ formatPrice(Number(usage.order_amount) * 100) }}</span>
						</div>
						<div class="flex items-center justify-between gap-[10px] mt-[6px] text-[0.8125rem]">
							<span class="text-[var(--color-brand-text-secondary)]">Commissione</span>
							<span class="font-semibold text-[var(--color-brand-primary)]">+{{ formatPrice(Number(usage.commission_amount) * 100) }}</span>
						</div>
					</div>
				</div>

				<div class="hidden desktop:block overflow-x-auto">
					<table class="w-full text-[0.875rem]" style="min-width:520px">
						<thead>
							<tr class="border-b border-[var(--color-brand-border)] text-left text-[var(--color-brand-text-secondary)]">
								<th class="pb-[12px] font-medium">Data</th>
								<th class="pb-[12px] font-medium">Cliente</th>
								<th class="pb-[12px] font-medium text-right">Ordine</th>
								<th class="pb-[12px] font-medium text-right">Commissione</th>
								<th class="pb-[12px] font-medium text-center">Stato</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="usage in historyItems" :key="usage.id" class="border-b border-[#F0F0F0] last:border-0">
								<td class="py-[12px] text-[var(--color-brand-text)]">{{ formatDate(usage.created_at) }}</td>
								<td class="py-[12px] text-[var(--color-brand-text)]">{{ usage.buyer?.name || 'â€”' }}</td>
								<td class="py-[12px] text-right text-[var(--color-brand-text)]">{{ formatPrice(Number(usage.order_amount) * 100) }}</td>
								<td class="py-[12px] text-right font-semibold text-[var(--color-brand-primary)]">+{{ formatPrice(Number(usage.commission_amount) * 100) }}</td>
								<td class="py-[12px] text-center">
									<span
										:class="[
											'inline-block px-[10px] py-[3px] rounded-full text-[0.6875rem] font-medium',
											usage.status === 'confirmed'
												? 'bg-[#f0fdf4] text-[#0a8a7a]'
												: usage.status === 'paid'
													? 'bg-[#eef7f8] text-[var(--color-brand-primary)]'
													: 'bg-amber-50 text-amber-700',
										]">
										{{ usage.status === 'confirmed' ? 'Confermata' : usage.status === 'paid' ? 'Pagata' : 'In attesa' }}
									</span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</template>

