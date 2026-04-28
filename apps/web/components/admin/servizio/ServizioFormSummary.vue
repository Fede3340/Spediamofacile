<script setup>defineProps({
  checklistItems: { type: Array, required: true },
  completedCount: { type: Number, required: true },
  saving: { type: Boolean, default: false },
  submitLabel: { type: String, default: '' },
});
const emit = defineEmits();
</script>

<template>
	<aside class="service-editor-side">
		<div class="sf-account-panel service-editor-summary rounded-[16px] p-[20px]">
			<div class="service-editor-summary__top">
				<p class="service-editor-summary__eyebrow">Checklist</p>
				<h2 class="service-editor-summary__title">Pronto alla pubblicazione</h2>
				<p class="service-editor-summary__text">
					{{ completedCount }}/{{ checklistItems.length }} elementi chiave completati.
				</p>
			</div>
			<ul class="service-editor-summary__list">
				<li
					v-for="item in checklistItems"
					:key="item.label"
					class="service-editor-summary__item"
					:class="{ 'service-editor-summary__item--done': item.done }">
					<span class="service-editor-summary__dot" aria-hidden="true"></span>
					<span>{{ item.label }}</span>
				</li>
			</ul>
			<div class="service-editor-summary__note">
				<p class="service-editor-summary__note-title">Promemoria rapido</p>
				<p class="service-editor-summary__note-text">
					Salva in bozza per mantenere URL e struttura stabili.
				</p>
			</div>
			<button type="button" @click="emit('save')" :disabled="saving" class="btn-primary btn-compact inline-flex w-full items-center justify-center gap-[6px] disabled:opacity-50">
				<svg aria-hidden="true" v-if="saving" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] animate-spin" fill="currentColor"><path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z"/></svg>
				<svg aria-hidden="true" v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px]" fill="currentColor"><path d="M15,9H5V5H15M12,19A3,3 0 0,1 9,16A3,3 0 0,1 12,13A3,3 0 0,1 15,16A3,3 0 0,1 12,19M17,3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3Z"/></svg>
				{{ saving ? "Salvataggio..." : (submitLabel || 'Crea servizio') }}
			</button>
		</div>
	</aside>
</template>
