<!-- VINCOLO: usa classi globali .service-editor-* definite in pages/account/amministrazione/servizi/nuovo.vue — NON aggiungere scoped. -->
<script setup>defineProps({
  title: { type: String, required: true },
  slug: { type: String, required: true },
  metaDescription: { type: String, required: true },
  intro: { type: String, required: true },
  isPublished: { type: Boolean, default: false },
  publishStateHint: { type: String, required: true },
});
const emit = defineEmits();
const onTitleInput = (event) => {
    const value = event.target.value;
    emit('update:title', value);
    emit('slug-from-title');
};
</script>

<template>
	<section class="sf-account-panel service-editor-panel service-editor-panel--teal rounded-[16px] p-[20px]">
		<div class="service-editor-panel__header">
			<div>
				<h2 class="service-editor-panel__title">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="service-editor-panel__icon" fill="currentColor"><path d="M14,17H7V15H14M17,13H7V11H17M17,9H7V7H17M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z"/></svg>
					Informazioni base
				</h2>
				<p class="service-editor-panel__text">Naming, URL e testo introduttivo del servizio.</p>
			</div>
		</div>
		<div class="service-editor-field-grid">
			<div class="service-editor-field service-editor-field--half">
				<label class="service-editor-label">Titolo</label>
				<input :value="title" @input="onTitleInput" type="text" class="service-editor-input" placeholder="Titolo del servizio" />
			</div>
			<div class="service-editor-field service-editor-field--half">
				<label class="service-editor-label">Slug (URL)</label>
				<input :value="slug" @input="emit('update:slug', $event.target.value)" type="text" class="service-editor-input service-editor-input--mono" placeholder="titolo-del-servizio" />
				<p class="service-editor-field__hint">Si aggiorna automaticamente dal titolo, ma puoi rifinirlo a mano.</p>
			</div>
			<div class="service-editor-field">
				<label class="service-editor-label">Meta description</label>
				<textarea :value="metaDescription" @input="emit('update:metaDescription', $event.target.value)" rows="2" class="service-editor-textarea" placeholder="Descrizione per i motori di ricerca"></textarea>
			</div>
			<div class="service-editor-field">
				<label class="service-editor-label">Introduzione</label>
				<textarea :value="intro" @input="emit('update:intro', $event.target.value)" rows="4" class="service-editor-textarea" placeholder="Paragrafo introduttivo del servizio"></textarea>
			</div>
		</div>
		<div class="service-editor-toggle-row">
			<div class="service-editor-toggle-copy">
				<span class="service-editor-toggle-copy__label">Visibilita'</span>
				<p class="service-editor-toggle-copy__text">{{ publishStateHint }}</p>
			</div>
			<div class="service-editor-toggle-control">
				<button
					type="button"
					aria-label="Attiva o disattiva pubblicazione"
					role="switch"
					:aria-checked="isPublished ? 'true' : 'false'"
					@click="emit('update:isPublished', !isPublished)"
					:class="['service-editor-toggle', isPublished ? 'service-editor-toggle--active' : '']">
					<span :class="['service-editor-toggle__thumb', isPublished ? 'service-editor-toggle__thumb--active' : '']"></span>
				</button>
				<span class="service-editor-state-pill" :class="isPublished ? 'service-editor-state-pill--active' : ''">
					{{ isPublished ? 'Pubblicato' : 'Bozza' }}
				</span>
			</div>
		</div>
	</section>
</template>
