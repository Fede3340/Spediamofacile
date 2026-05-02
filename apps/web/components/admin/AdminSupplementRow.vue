<script setup>
const props = defineProps({
	rule: { type: Object, required: true },
	supplementAmountToEuro: { type: Function, required: true },
	updateSupplementAmountFromEuro: { type: Function, required: true },
});

const emit = defineEmits(['remove', 'update:rule']);

const ruleValue = (field) => props.rule?.[field] ?? '';
const updateRule = (field, value) => {
	emit('update:rule', {
		...props.rule,
		[field]: value,
	});
};
const updateRuleAmount = (value) => {
	const nextRule = { ...props.rule };
	props.updateSupplementAmountFromEuro(nextRule, value);
	emit('update:rule', nextRule);
};
</script>

<template>
	<div class="grid grid-cols-1 tablet:grid-cols-[120px_160px_1fr_auto_auto] gap-2 items-center p-3 rounded-card border border-brand-border bg-brand-bg-alt">
		<label class="text-xs text-brand-text-secondary">Prefisso CAP
			<input :value="ruleValue('prefix')" type="text" inputmode="numeric" maxlength="5" class="mt-1 w-full h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" @input="updateRule('prefix', $event.target.value)">
		</label>
		<label class="text-xs text-brand-text-secondary">Importo (&euro;)
			<input :value="supplementAmountToEuro(rule)" type="text" class="mt-1 w-full h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" @input="updateRuleAmount($event.target.value)">
		</label>
		<label class="text-xs text-brand-text-secondary">Applica a
			<select :value="ruleValue('apply_to')" class="mt-1 w-full h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" @change="updateRule('apply_to', $event.target.value)">
				<option value="both">Origine + Destinazione</option>
				<option value="origin">Solo origine</option>
				<option value="destination">Solo destinazione</option>
			</select>
		</label>
		<button type="button" role="switch" :aria-checked="ruleValue('enabled') ? 'true' : 'false'" aria-label="Attiva supplemento" :class="ruleValue('enabled') ? 'bg-brand-primary' : 'bg-brand-border'" class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors cursor-pointer mt-4" @click="updateRule('enabled', !ruleValue('enabled'))">
			<span :class="ruleValue('enabled') ? 'translate-x-[24px]' : 'translate-x-[2px]'" class="inline-block h-[22px] w-[22px] transform rounded-full bg-white transition-transform shadow-sm" />
		</button>
		<button type="button" class="px-2.5 py-1.5 rounded-card border border-red-200 text-red-600 text-xs hover:bg-red-50 cursor-pointer mt-4" @click="$emit('remove')">
			Elimina
		</button>
	</div>
</template>
