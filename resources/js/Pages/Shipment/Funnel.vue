<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
	step: { type: String, default: 'colli' },
	draft: { type: Object, default: () => ({}) },
});

const STEPS = [
	{ id: 'colli', label: 'Colli', n: 1 },
	{ id: 'servizi', label: 'Servizi', n: 2 },
	{ id: 'indirizzi', label: 'Indirizzi', n: 3 },
	{ id: 'pagamento', label: 'Pagamento', n: 4 },
];

const currentStep = computed(() => STEPS.find(s => s.id === props.step) || STEPS[0]);
const progressPct = computed(() => Math.round((currentStep.value.n / 4) * 100));

const form = useForm({
	step: props.step,
	packages: props.draft.packages || [{ package_type: 'Pacco', quantity: 1, weight: '', first_size: '', second_size: '', third_size: '' }],
	services: props.draft.services || { date: '', service_type: '', content_description: '' },
	origin: props.draft.origin || { name: '', address: '', address_number: '', city: '', postal_code: '', province: '', telephone_number: '', email: '' },
	destination: props.draft.destination || { name: '', address: '', address_number: '', city: '', postal_code: '', province: '', telephone_number: '', email: '' },
	payment_method: props.draft.payment_method || 'stripe',
});

const submit = () => form.post(`/la-tua-spedizione/${currentStep.value.id}`, { preserveScroll: true });
const back = () => {
	const prev = STEPS[currentStep.value.n - 2];
	if (prev) form.get(`/la-tua-spedizione/${prev.id}`);
};
</script>
<template>
	<Head :title="`Step ${currentStep.n} · ${currentStep.label}`" />
	<div class="sf-container py-12 max-w-3xl">
		<nav class="mb-8">
			<ol class="hidden md:flex items-center gap-2">
				<li v-for="s in STEPS" :key="s.id" class="flex items-center gap-2 flex-1">
					<span :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm', s.n < currentStep.n ? 'bg-[var(--color-brand-teal)] text-white' : s.n === currentStep.n ? 'bg-[var(--color-brand-teal)] text-white ring-4 ring-[var(--color-brand-teal)]/20' : 'bg-[var(--color-brand-border)] text-[var(--color-brand-text-muted)]']">
						{{ s.n }}
					</span>
					<span :class="['text-sm font-semibold', s.n === currentStep.n ? 'text-[var(--color-brand-text)]' : 'text-[var(--color-brand-text-secondary)]']">{{ s.label }}</span>
					<span v-if="s.n < 4" class="flex-1 h-0.5 bg-[var(--color-brand-border)]"></span>
				</li>
			</ol>
			<div class="md:hidden">
				<div class="flex justify-between text-sm font-semibold mb-2">
					<span>Step {{ currentStep.n }} di 4 · {{ currentStep.label }}</span>
					<span>{{ progressPct }}%</span>
				</div>
				<div class="h-1 bg-[var(--color-brand-border)] rounded-full overflow-hidden">
					<div class="h-full bg-gradient-to-r from-[var(--color-brand-teal)] to-[var(--color-brand-orange)]" :style="{ width: progressPct + '%' }"></div>
				</div>
			</div>
		</nav>

		<form @submit.prevent="submit" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-4">
			<!-- STEP COLLI -->
			<template v-if="step === 'colli'">
				<h2 class="text-2xl font-bold mb-4">Cosa spedisci?</h2>
				<div v-for="(pkg, i) in form.packages" :key="i" class="border border-[var(--color-brand-border)] rounded-xl p-4 space-y-3">
					<div class="flex gap-2">
						<button v-for="t in ['Pacco', 'Pallet', 'Valigia']" :key="t" type="button" @click="pkg.package_type = t" :class="['px-3 py-1 rounded-full text-xs font-semibold border', pkg.package_type === t ? 'bg-[var(--color-brand-teal)] text-white border-[var(--color-brand-teal)]' : 'bg-white border-[var(--color-brand-border)]']">{{ t }}</button>
					</div>
					<div class="grid grid-cols-4 gap-2">
						<input v-model.number="pkg.weight" type="number" min="0.1" step="0.1" placeholder="Peso kg" class="border rounded px-2 py-2 text-sm" />
						<input v-model.number="pkg.first_size" type="number" min="1" placeholder="L cm" class="border rounded px-2 py-2 text-sm" />
						<input v-model.number="pkg.second_size" type="number" min="1" placeholder="W cm" class="border rounded px-2 py-2 text-sm" />
						<input v-model.number="pkg.third_size" type="number" min="1" placeholder="H cm" class="border rounded px-2 py-2 text-sm" />
					</div>
				</div>
			</template>

			<!-- STEP SERVIZI -->
			<template v-if="step === 'servizi'">
				<h2 class="text-2xl font-bold mb-4">Quando ritiriamo?</h2>
				<div>
					<label class="block text-sm font-semibold mb-1">Data ritiro</label>
					<input v-model="form.services.date" type="date" required class="w-full border rounded-lg px-3 py-2" />
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">Contenuto</label>
					<input v-model="form.services.content_description" type="text" placeholder="Es. Libri, Abbigliamento" required class="w-full border rounded-lg px-3 py-2" />
				</div>
			</template>

			<!-- STEP INDIRIZZI -->
			<template v-if="step === 'indirizzi'">
				<h2 class="text-2xl font-bold mb-4">Mittente e destinatario</h2>
				<div class="grid md:grid-cols-2 gap-6">
					<div class="space-y-3">
						<h3 class="font-semibold">Mittente</h3>
						<input v-model="form.origin.name" type="text" placeholder="Nome e cognome" required class="w-full border rounded-lg px-3 py-2 text-sm" />
						<div class="grid grid-cols-3 gap-2">
							<input v-model="form.origin.address" type="text" placeholder="Via" required class="col-span-2 border rounded-lg px-3 py-2 text-sm" />
							<input v-model="form.origin.address_number" type="text" placeholder="Civico" required class="border rounded-lg px-3 py-2 text-sm" />
						</div>
						<div class="grid grid-cols-3 gap-2">
							<input v-model="form.origin.postal_code" type="text" maxlength="5" placeholder="CAP" required class="border rounded-lg px-3 py-2 text-sm" />
							<input v-model="form.origin.city" type="text" placeholder="Citta" required class="border rounded-lg px-3 py-2 text-sm" />
							<input v-model="form.origin.province" type="text" maxlength="2" placeholder="PR" required class="border rounded-lg px-3 py-2 text-sm uppercase" />
						</div>
						<input v-model="form.origin.telephone_number" type="tel" placeholder="Telefono" required class="w-full border rounded-lg px-3 py-2 text-sm" />
						<input v-model="form.origin.email" type="email" placeholder="Email" required class="w-full border rounded-lg px-3 py-2 text-sm" />
					</div>
					<div class="space-y-3">
						<h3 class="font-semibold">Destinatario</h3>
						<input v-model="form.destination.name" type="text" placeholder="Nome e cognome" required class="w-full border rounded-lg px-3 py-2 text-sm" />
						<div class="grid grid-cols-3 gap-2">
							<input v-model="form.destination.address" type="text" placeholder="Via" required class="col-span-2 border rounded-lg px-3 py-2 text-sm" />
							<input v-model="form.destination.address_number" type="text" placeholder="Civico" required class="border rounded-lg px-3 py-2 text-sm" />
						</div>
						<div class="grid grid-cols-3 gap-2">
							<input v-model="form.destination.postal_code" type="text" maxlength="5" placeholder="CAP" required class="border rounded-lg px-3 py-2 text-sm" />
							<input v-model="form.destination.city" type="text" placeholder="Citta" required class="border rounded-lg px-3 py-2 text-sm" />
							<input v-model="form.destination.province" type="text" maxlength="2" placeholder="PR" required class="border rounded-lg px-3 py-2 text-sm uppercase" />
						</div>
						<input v-model="form.destination.telephone_number" type="tel" placeholder="Telefono" required class="w-full border rounded-lg px-3 py-2 text-sm" />
						<input v-model="form.destination.email" type="email" placeholder="Email" required class="w-full border rounded-lg px-3 py-2 text-sm" />
					</div>
				</div>
			</template>

			<!-- STEP PAGAMENTO -->
			<template v-if="step === 'pagamento'">
				<h2 class="text-2xl font-bold mb-4">Come paghi?</h2>
				<div class="space-y-2">
					<label v-for="m in [{id:'stripe',l:'Carta di credito'},{id:'wallet',l:'Saldo wallet'},{id:'bank_transfer',l:'Bonifico bancario'}]" :key="m.id" class="flex items-center gap-3 border rounded-xl p-4 cursor-pointer" :class="form.payment_method === m.id ? 'border-[var(--color-brand-teal)] bg-[var(--color-brand-teal)]/5' : 'border-[var(--color-brand-border)]'">
						<input v-model="form.payment_method" type="radio" :value="m.id" />
						<span class="font-semibold">{{ m.l }}</span>
					</label>
				</div>
			</template>

			<div class="flex items-center justify-between pt-6 border-t border-[var(--color-brand-border)]">
				<button v-if="currentStep.n > 1" type="button" @click="back" class="text-[var(--color-brand-text-secondary)] font-semibold">← Indietro</button>
				<span v-else></span>
				<button type="submit" :disabled="form.processing" class="bg-[var(--color-brand-orange)] text-white px-6 py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] disabled:opacity-50">
					{{ form.processing ? 'Attendi...' : currentStep.n === 4 ? 'Conferma e paga' : 'Continua' }}
				</button>
			</div>
		</form>
	</div>
</template>
