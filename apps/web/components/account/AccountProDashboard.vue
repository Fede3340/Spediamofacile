<script setup>
import { formatDateIt as formatDate } from '~/utils/date.js';
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

const referralCode = computed(() => props.referralData?.referral_code || '--------');
const referralLink = computed(() => props.referralData?.referral_link || '');
const totalEarnings = computed(() => formatEuro(props.referralData?.total_earnings || 0));
const totalUsages = computed(() => props.referralData?.total_usages || 0);
const commissionBalance = computed(() => formatEuro(props.earnings?.commission_balance || 0));
const historyItems = computed(() => props.earnings?.data || []);
</script>

<template>
	<div class="space-y-5">
		<div class="rounded-card border border-brand-border bg-brand-card p-5 shadow-sf md:p-[22px]">
			<div class="grid gap-[18px] lg:grid-cols-[minmax(0,1.35fr)_320px] lg:gap-5">
				<div>
					<p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-brand-text-secondary">Programma attivo</p>
					<h2 class="font-display text-[1.3rem] font-extrabold text-brand-text lg:text-[1.55rem]">
						Condividi il link e tieni tutto sotto controllo
					</h2>
					<p class="mt-2 max-w-[700px] text-[0.9375rem] leading-relaxed text-brand-text-secondary">
						Il link invito resta la prima azione, mentre utilizzi, commissioni e saldo restano leggibili senza aprire viste secondarie.
					</p>

					<div class="mt-[18px] rounded-card border border-brand-primary/10 bg-brand-bg-alt p-3.5 lg:p-4">
						<p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-brand-text-secondary">Link invito</p>
						<p class="break-all text-[0.9375rem] leading-relaxed text-brand-text lg:text-base">
							{{ referralLink || 'Link disponibile al prossimo aggiornamento dati.' }}
						</p>
					</div>

					<div class="mt-3 flex flex-wrap gap-2">
						<SfButton variant="primary" size="sm" @click="emit('copy-link')">{{ copiedLink ? 'Link copiato' : 'Copia link' }}</SfButton>
						<SfButton variant="secondary" size="sm" @click="emit('share-whatsapp')">WhatsApp</SfButton>
						<SfButton variant="secondary" size="sm" @click="emit('copy-code')">{{ copied ? 'Codice copiato' : 'Copia codice' }}</SfButton>
					</div>
				</div>

				<div class="grid gap-2.5 sm:grid-cols-3 lg:grid-cols-1">
					<div class="rounded-card border border-brand-primary/10 bg-brand-card p-4 shadow-sf-sm">
						<p class="text-[0.6875rem] font-medium uppercase tracking-[0.8px] text-brand-text-secondary">Commissioni</p>
						<p class="mt-1.5 text-[1.4rem] font-extrabold text-brand-text">&euro;{{ totalEarnings }}</p>
					</div>
					<div class="rounded-card border border-brand-primary/10 bg-brand-card p-4 shadow-sf-sm">
						<p class="text-[0.6875rem] font-medium uppercase tracking-[0.8px] text-brand-text-secondary">Utilizzi</p>
						<p class="mt-1.5 text-[1.4rem] font-extrabold text-brand-text">{{ totalUsages }}</p>
					</div>
					<div class="rounded-card border border-brand-primary/10 bg-brand-card p-4 shadow-sf-sm">
						<p class="text-[0.6875rem] font-medium uppercase tracking-[0.8px] text-brand-text-secondary">Saldo</p>
						<p class="mt-1.5 text-[1.4rem] font-extrabold text-brand-primary">&euro;{{ commissionBalance }}</p>
					</div>
				</div>
			</div>
		</div>

		<div class="grid gap-3.5 sm:grid-cols-2">
			<div class="rounded-card border border-brand-primary/10 bg-brand-card p-4 shadow-sf-sm md:p-5">
				<p class="text-[0.6875rem] font-medium uppercase tracking-[0.8px] text-brand-text-secondary">Codice account</p>
				<p class="mt-1.5 break-all font-mono text-[0.95rem] font-semibold text-brand-text">
					SF-PRO-{{ user?.id?.toString().padStart(6, '0') }}
				</p>
				<div class="mt-2.5 inline-flex">
					<SfButton variant="secondary" size="sm" @click="emit('copy-account-code')">{{ copiedAccountCode ? 'Copiato' : 'Copia codice' }}</SfButton>
				</div>
			</div>

			<div class="rounded-card border border-brand-primary/10 bg-brand-card p-4 shadow-sf-sm md:p-5">
				<p class="text-[0.6875rem] font-medium uppercase tracking-[0.8px] text-brand-text-secondary">Codice invito</p>
				<p class="mt-1.5 break-all font-mono text-base font-semibold text-brand-text">
					{{ referralCode }}
				</p>
				<p class="mt-2 text-[0.8125rem] leading-relaxed text-brand-text-secondary">
					Usalo quando devi condividere il programma in forma breve.
				</p>
			</div>
		</div>

		<div class="rounded-card border border-brand-border bg-brand-card p-5 shadow-sf md:p-[22px]">
			<div class="mb-4 flex flex-col gap-1.5 lg:mb-5 lg:flex-row lg:items-end lg:justify-between">
				<div>
					<p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-text-secondary">Storico commissioni</p>
					<h2 class="mt-1.5 font-display text-lg font-extrabold text-brand-text">Utilizzi recenti del programma</h2>
				</div>
				<p class="text-[0.8125rem] text-brand-text-secondary">
					Ordini, commissioni e stato restano leggibili in un solo posto.
				</p>
			</div>

			<div v-if="!historyItems.length" class="py-7 text-center">
				<div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-brand-bg-alt">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#C8CCD0">
						<path d="M16,11.78L20.24,4.45L21.97,5.45L16.74,14.5L10.23,10.75L5.46,19H22V21H2V3H4V17.54L9.5,8L16,11.78Z" />
					</svg>
				</div>
				<p class="text-base font-medium text-brand-text">Nessuna commissione ancora</p>
				<p class="mt-1.5 text-[0.8125rem] text-brand-text-secondary">Condividi il link invito per iniziare a raccogliere i primi utilizzi.</p>
			</div>

			<div v-else class="space-y-3 lg:space-y-0">
				<div class="space-y-2.5 lg:hidden">
					<div v-for="usage in historyItems" :key="usage.id" class="rounded-card border border-transparent bg-brand-bg-alt p-3.5 shadow-sf-sm">
						<div class="flex items-start justify-between gap-3">
							<div>
								<p class="text-[0.8125rem] font-semibold text-brand-text">{{ usage.buyer?.name || 'â€”' }}</p>
								<p class="mt-0.5 text-xs text-brand-text-secondary">{{ formatDate(usage.created_at) }}</p>
							</div>
							<span
								:class="[
									'inline-flex items-center gap-1 rounded-full px-2.5 py-[3px] text-[0.6875rem] font-medium',
									usage.status === 'confirmed'
										? 'bg-brand-success-bg text-brand-success-fg'
										: usage.status === 'paid'
											? 'bg-brand-primary/10 text-brand-primary'
											: 'bg-status-pending-bg text-status-pending-fg',
								]">
								{{ usage.status === 'confirmed' ? 'Confermata' : usage.status === 'paid' ? 'Pagata' : 'In attesa' }}
							</span>
						</div>
						<div class="mt-2.5 flex items-center justify-between gap-2.5 text-[0.8125rem]">
							<span class="text-brand-text-secondary">Ordine</span>
							<span class="text-brand-text">{{ formatPrice(Number(usage.order_amount) * 100) }}</span>
						</div>
						<div class="mt-1.5 flex items-center justify-between gap-2.5 text-[0.8125rem]">
							<span class="text-brand-text-secondary">Commissione</span>
							<span class="font-semibold text-brand-primary">+{{ formatPrice(Number(usage.commission_amount) * 100) }}</span>
						</div>
					</div>
				</div>

				<div class="hidden overflow-x-auto lg:block">
					<table class="w-full text-sm" style="min-width:520px">
						<thead>
							<tr class="border-b border-brand-border text-left text-brand-text-secondary">
								<th class="pb-3 font-medium">Data</th>
								<th class="pb-3 font-medium">Cliente</th>
								<th class="pb-3 text-right font-medium">Ordine</th>
								<th class="pb-3 text-right font-medium">Commissione</th>
								<th class="pb-3 text-center font-medium">Stato</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="usage in historyItems" :key="usage.id" class="border-b border-[#F0F0F0] last:border-0">
								<td class="py-3 text-brand-text">{{ formatDate(usage.created_at) }}</td>
								<td class="py-3 text-brand-text">{{ usage.buyer?.name || 'â€”' }}</td>
								<td class="py-3 text-right text-brand-text">{{ formatPrice(Number(usage.order_amount) * 100) }}</td>
								<td class="py-3 text-right font-semibold text-brand-primary">+{{ formatPrice(Number(usage.commission_amount) * 100) }}</td>
								<td class="py-3 text-center">
									<span
										:class="[
											'inline-block rounded-full px-2.5 py-[3px] text-[0.6875rem] font-medium',
											usage.status === 'confirmed'
												? 'bg-brand-success-bg text-brand-success-fg'
												: usage.status === 'paid'
													? 'bg-brand-primary/10 text-brand-primary'
													: 'bg-status-pending-bg text-status-pending-fg',
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
