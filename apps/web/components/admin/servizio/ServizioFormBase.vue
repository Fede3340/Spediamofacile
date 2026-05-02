<script setup>
defineProps({
	title: { type: String, required: true },
	slug: { type: String, required: true },
	metaDescription: { type: String, required: true },
	intro: { type: String, required: true },
	isPublished: { type: Boolean, default: false },
	publishStateHint: { type: String, required: true },
});
const emit = defineEmits(['update:title', 'slug-from-title', 'update:slug', 'update:metaDescription', 'update:intro', 'update:isPublished']);
const onTitleInput = (event) => {
	const value = event.target.value;
	emit('update:title', value);
	emit('slug-from-title');
};
</script>

<template>
	<SfCard padding="md" class="grid gap-4">
		<div class="grid gap-2">
			<h2 class="m-0 inline-flex items-center gap-2.5 text-lg font-extrabold text-brand-text leading-tight">
				<UIcon name="mdi:file-document-outline" class="w-5 h-5 shrink-0" />
				Informazioni base
			</h2>
			<p class="m-0 max-w-[46rem] text-sm text-brand-text-secondary">Naming, URL e testo introduttivo del servizio.</p>
		</div>
		<div class="grid grid-cols-1 desktop:grid-cols-2 gap-4">
			<div class="grid gap-1.5">
				<label class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">Titolo</label>
				<input :value="title" type="text" class="w-full px-3.5 py-3 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" placeholder="Titolo del servizio" @input="onTitleInput">
			</div>
			<div class="grid gap-1.5">
				<label class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">Slug (URL)</label>
				<input :value="slug" type="text" class="w-full px-3.5 py-3 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text font-mono focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" placeholder="titolo-del-servizio" @input="emit('update:slug', $event.target.value)">
				<p class="m-0 text-xs text-brand-text-muted">Si aggiorna automaticamente dal titolo, ma puoi rifinirlo a mano.</p>
			</div>
			<div class="grid gap-1.5 desktop:col-span-2">
				<label class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">Meta description</label>
				<textarea :value="metaDescription" rows="2" class="w-full px-3.5 py-3 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text resize-y focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" placeholder="Descrizione per i motori di ricerca" @input="emit('update:metaDescription', $event.target.value)" />
			</div>
			<div class="grid gap-1.5 desktop:col-span-2">
				<label class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">Introduzione</label>
				<textarea :value="intro" rows="4" class="w-full px-3.5 py-3 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text resize-y focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" placeholder="Paragrafo introduttivo del servizio" @input="emit('update:intro', $event.target.value)" />
			</div>
		</div>
		<div class="flex flex-col tablet:flex-row tablet:items-center tablet:justify-between gap-4 pt-2 border-t border-brand-border">
			<div class="grid gap-1 max-w-[34rem]">
				<span class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">Visibilita'</span>
				<p class="m-0 text-sm text-brand-text-secondary">{{ publishStateHint }}</p>
			</div>
			<div class="inline-flex items-center gap-2.5 flex-wrap tablet:justify-end">
				<button
					type="button"
					aria-label="Attiva o disattiva pubblicazione"
					role="switch"
					:aria-checked="isPublished ? 'true' : 'false'"
					:class="['relative w-13 h-7.5 rounded-full transition-colors cursor-pointer', isPublished ? 'bg-brand-primary' : 'bg-brand-border']"
					@click="emit('update:isPublished', !isPublished)">
					<span :class="['absolute top-[3px] left-[3px] w-6 h-6 rounded-full bg-white shadow-md transition-transform', isPublished ? 'translate-x-[22px]' : 'translate-x-0']" />
				</button>
				<span :class="['inline-flex items-center min-h-8 px-3 rounded-pill border text-sm font-extrabold', isPublished ? 'border-brand-primary/30 bg-brand-soft-bg text-brand-primary' : 'border-brand-border bg-brand-bg-alt text-brand-text-secondary']">
					{{ isPublished ? 'Pubblicato' : 'Bozza' }}
				</span>
			</div>
		</div>
	</SfCard>
</template>
