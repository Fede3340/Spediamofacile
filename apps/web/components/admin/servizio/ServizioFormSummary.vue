<script setup>
defineProps({
	checklistItems: { type: Array, required: true },
	completedCount: { type: Number, required: true },
	saving: { type: Boolean, default: false },
	submitLabel: { type: String, default: '' },
});
const emit = defineEmits(['save']);
</script>

<template>
	<aside class="min-w-0">
		<SfCard padding="md" class="grid gap-4 desktop:sticky desktop:top-32">
			<div class="grid gap-1.5">
				<p class="text-xs font-bold uppercase tracking-wider text-brand-text-muted">Checklist</p>
				<h2 class="font-display text-xl font-extrabold text-brand-text leading-tight">Pronto alla pubblicazione</h2>
				<p class="text-sm text-brand-text-secondary">
					{{ completedCount }}/{{ checklistItems.length }} elementi chiave completati.
				</p>
			</div>
			<ul class="grid gap-2.5 m-0 p-0 list-none">
				<li
					v-for="item in checklistItems"
					:key="item.label"
					:class="[
						'inline-flex items-center gap-2.5 text-sm leading-relaxed',
						item.done ? 'text-brand-text font-bold' : 'text-brand-text-secondary',
					]">
					<span :class="['w-3 h-3 shrink-0 rounded-full border-2', item.done ? 'border-brand-primary bg-brand-primary' : 'border-brand-border bg-brand-card']" aria-hidden="true" />
					<span>{{ item.label }}</span>
				</li>
			</ul>
			<div class="grid gap-1 p-3.5 rounded-card border border-brand-border bg-brand-bg-alt">
				<p class="text-xs font-bold uppercase tracking-wider text-brand-text-muted">Promemoria rapido</p>
				<p class="text-sm text-brand-text-secondary">
					Salva in bozza per mantenere URL e struttura stabili.
				</p>
			</div>
			<SfButton :loading="saving" :disabled="saving" full @click="emit('save')">
				<template #leading>
					<UIcon name="mdi:content-save" class="w-4 h-4" />
				</template>
				{{ saving ? "Salvataggio..." : (submitLabel || 'Crea servizio') }}
			</SfButton>
		</SfCard>
	</aside>
</template>
