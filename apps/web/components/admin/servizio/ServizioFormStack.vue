<script setup>
const props = defineProps({
	items: { type: Array, required: true },
	panelTitle: { type: String, required: true },
	panelDescription: { type: String, required: true },
	indexLabel: { type: String, required: true },
	addButtonLabel: { type: String, required: true },
	headingPlaceholder: { type: String, required: true },
	textPlaceholder: { type: String, required: true },
	textRows: { type: Number, default: 4 },
	iconPath: { type: String, required: true },
	fieldKey: { type: String, required: true },
});
const emit = defineEmits(['update:items', 'add', 'remove']);
const onHeadingInput = (index, event) => {
	const value = event.target.value;
	const next = [...props.items];
	next[index] = { ...next[index], [props.fieldKey]: value };
	emit('update:items', next);
};
const onTextInput = (index, event) => {
	const value = event.target.value;
	const next = [...props.items];
	next[index] = { ...next[index], text: value };
	emit('update:items', next);
};
</script>

<template>
	<SfCard padding="md" class="grid gap-4">
		<div class="grid grid-cols-1 desktop:grid-cols-[minmax(0,1fr)_auto] items-start gap-4">
			<div class="grid gap-2">
				<h2 class="m-0 inline-flex items-center gap-2.5 text-lg font-extrabold text-brand-text leading-tight">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 shrink-0" fill="currentColor"><path :d="iconPath" /></svg>
					{{ panelTitle }}
				</h2>
				<p class="m-0 max-w-[46rem] text-sm text-brand-text-secondary">{{ panelDescription }}</p>
			</div>
			<SfButton variant="secondary" size="sm" @click="emit('add')">
				<template #leading>
					<UIcon name="mdi:plus" class="w-4 h-4" />
				</template>
				{{ addButtonLabel }}
			</SfButton>
		</div>
		<div class="grid gap-3.5">
			<div v-for="(item, idx) in items" :key="idx" class="relative grid gap-3.5 p-4 rounded-card border border-brand-border bg-brand-bg-alt overflow-hidden">
				<span class="absolute inset-y-0 left-0 w-1 bg-brand-primary" aria-hidden="true" />
				<div class="flex flex-col gap-3 tablet:flex-row tablet:items-center tablet:justify-between">
					<span class="text-sm font-extrabold uppercase tracking-wider text-brand-text-muted">{{ indexLabel }} {{ idx + 1 }}</span>
					<button
						v-if="items.length > 1"
						type="button"
						class="inline-flex items-center justify-center min-h-8 px-3 border border-red-200 rounded-pill bg-red-50 text-red-600 text-xs font-extrabold transition hover:bg-red-100"
						@click="emit('remove', idx)">
						Rimuovi
					</button>
				</div>
				<div class="grid gap-2.5">
					<input
						:value="item[fieldKey]"
						type="text"
						class="w-full px-3.5 py-3 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text shadow-inner focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
						:placeholder="headingPlaceholder"
						@input="onHeadingInput(idx, $event)">
					<textarea
						:value="item.text"
						:rows="textRows"
						class="w-full px-3.5 py-3 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text shadow-inner resize-y focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
						:placeholder="textPlaceholder"
						@input="onTextInput(idx, $event)" />
				</div>
			</div>
		</div>
	</SfCard>
</template>
