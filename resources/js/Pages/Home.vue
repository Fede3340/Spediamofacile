<script setup>
import { Link, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
	startingPrice: { type: String, default: '8,90' },
});

const form = ref({
	origin_postal_code: '',
	dest_postal_code: '',
	weight: '',
	package_type: 'Pacco',
});

const isValid = computed(() =>
	form.value.origin_postal_code.length === 5
	&& form.value.dest_postal_code.length === 5
	&& Number(form.value.weight) > 0
);

const submitQuote = () => {
	if (!isValid.value) return;
	const params = new URLSearchParams(form.value).toString();
	window.location.href = `/preventivo?${params}`;
};
</script>

<template>
	<Head title="Spedizioni BRT al miglior prezzo" />
	<div class="bg-gradient-to-b from-white to-[var(--color-brand-bg)] py-12">
		<div class="sf-container grid md:grid-cols-2 gap-12 items-center">
			<div>
				<h1 class="text-4xl md:text-5xl font-bold leading-tight mb-4">
					Spedisci in <span class="text-[var(--color-brand-orange)]">tutta Italia</span>
				</h1>
				<p class="text-lg text-[var(--color-brand-text-secondary)] mb-6">
					Ritiro a domicilio, consegna veloce, prezzo fisso.
				</p>
				<div class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 shadow-sm">
					<div class="flex items-baseline gap-2 mb-4">
						<span class="text-sm text-[var(--color-brand-text-muted)]">Da</span>
						<span class="text-3xl font-bold text-[var(--color-brand-orange)]">{{ startingPrice }}€</span>
						<span class="text-sm text-[var(--color-brand-text-muted)]">IVA inclusa</span>
					</div>
					<form @submit.prevent="submitQuote" class="space-y-3">
						<div class="grid grid-cols-2 gap-3">
							<input v-model="form.origin_postal_code" type="text" maxlength="5" placeholder="CAP partenza" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm" />
							<input v-model="form.dest_postal_code" type="text" maxlength="5" placeholder="CAP destinazione" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm" />
						</div>
						<div class="grid grid-cols-2 gap-3">
							<input v-model.number="form.weight" type="number" min="0.1" step="0.1" placeholder="Peso kg" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm" />
							<select v-model="form.package_type" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm">
								<option value="Pacco">Pacco</option>
								<option value="Pallet">Pallet</option>
								<option value="Valigia">Valigia</option>
							</select>
						</div>
						<button type="submit" :disabled="!isValid" class="w-full bg-[var(--color-brand-orange)] text-white py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] disabled:opacity-50 disabled:cursor-not-allowed">
							Calcola preventivo
						</button>
					</form>
				</div>
				<div class="flex items-center gap-6 mt-6 text-sm text-[var(--color-brand-text-secondary)]">
					<span>✓ Pagamento sicuro</span>
					<span>✓ Corriere BRT</span>
					<span>✓ Ritiro 24h</span>
				</div>
			</div>
			<div class="hidden md:block">
				<div class="aspect-square bg-gradient-to-br from-[var(--color-brand-teal)] to-[var(--color-brand-teal-dark)] rounded-3xl flex items-center justify-center text-white">
					<svg width="200" height="200" viewBox="0 0 24 24" fill="currentColor"><path d="M18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5M19.5,9.5L21.46,12H17V9.5M6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5M20,8H17V4H3C1.89,4 1,4.89 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8Z"/></svg>
				</div>
			</div>
		</div>
	</div>

	<section class="sf-container py-16">
		<div class="text-center mb-10">
			<p class="text-sm font-semibold text-[var(--color-brand-orange)] uppercase tracking-wide">Come funziona</p>
			<h2 class="text-3xl md:text-4xl font-bold mt-2">Spedire non e mai stato cosi semplice</h2>
		</div>
		<div class="grid md:grid-cols-3 gap-8">
			<div class="text-center">
				<div class="w-16 h-16 mx-auto rounded-full bg-[var(--color-brand-teal)] text-white flex items-center justify-center text-2xl font-bold mb-4">1</div>
				<h3 class="font-bold text-lg mb-2">Calcola</h3>
				<p class="text-[var(--color-brand-text-secondary)]">Inserisci i dati del pacco e ottieni il prezzo immediato.</p>
			</div>
			<div class="text-center">
				<div class="w-16 h-16 mx-auto rounded-full bg-[var(--color-brand-teal)] text-white flex items-center justify-center text-2xl font-bold mb-4">2</div>
				<h3 class="font-bold text-lg mb-2">Prenota</h3>
				<p class="text-[var(--color-brand-text-secondary)]">Scegli data ritiro, paga in sicurezza con Stripe o bonifico.</p>
			</div>
			<div class="text-center">
				<div class="w-16 h-16 mx-auto rounded-full bg-[var(--color-brand-teal)] text-white flex items-center justify-center text-2xl font-bold mb-4">3</div>
				<h3 class="font-bold text-lg mb-2">Spediamo</h3>
				<p class="text-[var(--color-brand-text-secondary)]">BRT ritira a casa tua e consegna in 24-48h.</p>
			</div>
		</div>
	</section>
</template>
