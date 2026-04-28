<script setup>const props = defineProps({
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
const emit = defineEmits();
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
	<section class="sf-account-panel service-editor-panel service-editor-panel--teal rounded-[16px] p-[20px]">
		<div class="service-editor-panel__header service-editor-panel__header--split">
			<div>
				<h2 class="service-editor-panel__title">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="service-editor-panel__icon" fill="currentColor"><path :d="iconPath"/></svg>
					{{ panelTitle }}
				</h2>
				<p class="service-editor-panel__text">{{ panelDescription }}</p>
			</div>
			<SfButton variant="secondary" size="sm" @click="emit('add')">
				<template #leading>
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[16px] h-[16px]" fill="currentColor"><path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
				</template>
				{{ addButtonLabel }}
			</SfButton>
		</div>
		<div class="service-editor-stack">
			<div v-for="(item, idx) in items" :key="idx" class="service-editor-stack-card service-editor-stack-card--teal">
				<div class="service-editor-stack-card__head">
					<span class="service-editor-stack-card__index">{{ indexLabel }} {{ idx + 1 }}</span>
					<button
						v-if="items.length > 1"
						type="button"
						@click="emit('remove', idx)"
						class="service-editor-remove">
						Rimuovi
					</button>
				</div>
				<div class="service-editor-stack-card__body">
					<input :value="item[fieldKey]" @input="onHeadingInput(idx, $event)" type="text" class="service-editor-input" :placeholder="headingPlaceholder" />
					<textarea :value="item.text" @input="onTextInput(idx, $event)" :rows="textRows" class="service-editor-textarea" :placeholder="textPlaceholder"></textarea>
				</div>
			</div>
		</div>
	</section>
</template>
