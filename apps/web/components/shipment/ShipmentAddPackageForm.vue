<!--
  Componente: ShipmentAddPackageForm
  Form per aggiungere un nuovo collo a un ordine in attesa di pagamento.
-->
<script setup>
const props = defineProps({
	showForm: { type: Boolean, required: true },
	addingPackage: { type: Boolean, default: false },
	addPackageError: { type: String, default: null },
	addPackageSuccess: { type: Boolean, default: false },
	newPackage: { type: Object, required: true },
});

const emit = defineEmits(['update:showForm', 'update:newPackage', 'submit']);

const packageField = (field) => props.newPackage?.[field] ?? '';
const updatePackageField = (field, value) => {
	emit('update:newPackage', {
		...props.newPackage,
		[field]: value,
	});
};
</script>

<template>
	<div class="mt-[16px]">
		<div v-if="addPackageSuccess" class="bg-[#f0fdf4] border border-[#d1fae5] rounded-[50px] px-[14px] py-[10px] text-[var(--color-brand-success)] text-[0.8125rem] mb-[12px]">
			Collo aggiunto con successo!
		</div>

		<button
			v-if="!showForm"
			type="button"
			class="inline-flex items-center gap-[6px] px-[16px] py-[10px] bg-[var(--color-brand-primary)] text-white rounded-[50px] text-[0.875rem] font-semibold hover:bg-[var(--color-brand-primary-hover)] transition cursor-pointer"
			@click="emit('update:showForm', true)">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
			Aggiungi collo
		</button>

		<div v-if="showForm" class="bg-white rounded-card p-[24px] border border-[var(--color-brand-border)]">
			<h3 class="font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)] mb-[16px]">Nuovo collo</h3>
			<div class="grid grid-cols-2 desktop:grid-cols-4 gap-[12px]">
				<div>
					<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Tipo</label>
					<select
						:value="packageField('package_type')"
						class="w-full bg-[#F8F9FB] border border-[var(--color-brand-border)] rounded-card p-[8px] text-[0.875rem]"
						@change="updatePackageField('package_type', $event.target.value)">
						<option value="Pacco">Pacco</option>
						<option value="Busta">Busta</option>
						<option value="Pallet">Pallet</option>
						<option value="Valigia">Valigia</option>
					</select>
				</div>
				<div>
					<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Quantita</label>
					<input type="number" :value="packageField('quantity')" min="1" max="999" class="w-full bg-[#F8F9FB] border border-[var(--color-brand-border)] rounded-card p-[8px] text-[0.875rem]" @input="updatePackageField('quantity', $event.target.value)">
				</div>
				<div>
					<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Peso (kg)</label>
					<input type="number" :value="packageField('weight')" min="0.1" max="1000" step="0.1" class="w-full bg-[#F8F9FB] border border-[var(--color-brand-border)] rounded-card p-[8px] text-[0.875rem]" required @input="updatePackageField('weight', $event.target.value)">
				</div>
				<div>
					<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Lunghezza (cm)</label>
					<input type="number" :value="packageField('first_size')" min="0.1" max="1000" step="0.1" class="w-full bg-[#F8F9FB] border border-[var(--color-brand-border)] rounded-card p-[8px] text-[0.875rem]" required @input="updatePackageField('first_size', $event.target.value)">
				</div>
				<div>
					<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Larghezza (cm)</label>
					<input type="number" :value="packageField('second_size')" min="0.1" max="1000" step="0.1" class="w-full bg-[#F8F9FB] border border-[var(--color-brand-border)] rounded-card p-[8px] text-[0.875rem]" required @input="updatePackageField('second_size', $event.target.value)">
				</div>
				<div>
					<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Altezza (cm)</label>
					<input type="number" :value="packageField('third_size')" min="0.1" max="1000" step="0.1" class="w-full bg-[#F8F9FB] border border-[var(--color-brand-border)] rounded-card p-[8px] text-[0.875rem]" required @input="updatePackageField('third_size', $event.target.value)">
				</div>
				<div class="col-span-2">
					<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Contenuto</label>
					<input type="text" :value="packageField('content_description')" placeholder="es. Elettronica" maxlength="255" class="w-full bg-[#F8F9FB] border border-[var(--color-brand-border)] rounded-card p-[8px] text-[0.875rem]" @input="updatePackageField('content_description', $event.target.value)">
				</div>
			</div>
			<div v-if="addPackageError" class="mt-[10px] text-red-500 text-[0.8125rem]">{{ addPackageError }}</div>
			<div class="mt-[16px] flex gap-[10px]">
				<button
type="button" :disabled="addingPackage" class="inline-flex items-center gap-[6px] px-[16px] py-[10px] bg-[var(--color-brand-accent)] text-white rounded-[50px] text-[0.875rem] font-semibold hover:opacity-90 transition disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
					@click="emit('submit')">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
					{{ addingPackage ? 'Aggiunta...' : 'Aggiungi' }}
				</button>
				<button
type="button" class="inline-flex items-center gap-[6px] px-[16px] py-[10px] bg-[var(--color-brand-border)] text-[var(--color-brand-text)] rounded-[50px] text-[0.875rem] font-semibold hover:bg-[#D0D0D0] transition cursor-pointer"
					@click="emit('update:showForm', false)">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					Annulla
				</button>
			</div>
		</div>
	</div>
</template>
