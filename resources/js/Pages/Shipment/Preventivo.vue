<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineProps({
	startingPrice: { type: String, default: '8,90' },
});

const form = useForm({
	origin_postal_code: '',
	dest_postal_code: '',
	weight: '',
	first_size: '',
	second_size: '',
	third_size: '',
	package_type: 'Pacco',
	quantity: 1,
});

const isValid = computed(() =>
	form.origin_postal_code.length === 5
	&& form.dest_postal_code.length === 5
	&& Number(form.weight) > 0
);

const result = ref(null);
const loading = ref(false);

const calc = async () => {
	if (!isValid.value) return;
	loading.value = true;
	try {
		const res = await window.axios.post('/preventivo/calcola', form.data());
		result.value = res.data;
	} finally {
		loading.value = false;
	}
};

const proceed = () => {
	if (!result.value) return;
	form.post('/la-tua-spedizione/inizia');
};
</script>
<template>
	<Head title="Preventivo" />
	<div class="sf-container py-12 max-w-3xl">
		<h1 class="text-4xl font-bold mb-3">Calcola il tuo preventivo</h1>
		<p class="text-[var(--color-brand-text-secondary)] mb-8">Da {{ startingPrice }} € · IVA inclusa · Ritiro a casa</p>

		<form @submit.prevent="calc" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-4">
			<div class="grid md:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-semibold mb-1">CAP partenza</label>
					<input v-model="form.origin_postal_code" type="text" maxlength="5" pattern="\d{5}" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">CAP destinazione</label>
					<input v-model="form.dest_postal_code" type="text" maxlength="5" pattern="\d{5}" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold mb-2">Tipo</label>
				<div class="flex gap-2">
					<button v-for="t in ['Pacco', 'Pallet', 'Valigia']" :key="t" type="button" @click="form.package_type = t" :class="['px-4 py-2 rounded-full text-sm font-semibold border', form.package_type === t ? 'bg-[var(--color-brand-teal)] text-white border-[var(--color-brand-teal)]' : 'bg-white border-[var(--color-brand-border)]']">{{ t }}</button>
				</div>
			</div>
			<div class="grid md:grid-cols-4 gap-3">
				<div>
					<label class="block text-sm font-semibold mb-1">Peso (kg)</label>
					<input v-model.number="form.weight" type="number" min="0.1" step="0.1" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">Lung (cm)</label>
					<input v-model.number="form.first_size" type="number" min="1" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">Larg (cm)</label>
					<input v-model.number="form.second_size" type="number" min="1" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">Alt (cm)</label>
					<input v-model.number="form.third_size" type="number" min="1" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
			</div>
			<button type="submit" :disabled="!isValid || loading" class="w-full bg-[var(--color-brand-orange)] text-white py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] disabled:opacity-50">
				{{ loading ? 'Calcolo...' : 'Calcola prezzo' }}
			</button>
		</form>

		<div v-if="result" class="mt-6 bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 text-center">
			<div class="text-sm text-[var(--color-brand-text-muted)]">Prezzo totale</div>
			<div class="text-4xl font-bold text-[var(--color-brand-orange)] my-3">{{ result.total }}</div>
			<button @click="proceed" class="bg-[var(--color-brand-teal)] text-white px-6 py-3 rounded-full font-semibold hover:bg-[var(--color-brand-teal-dark)]">Procedi alla spedizione</button>
		</div>
	</div>
</template>
