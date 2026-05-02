<script setup>
const props = defineProps({
	article: { type: Object, required: true },
	previewText: { type: String, default: '' },
	editTo: { type: String, required: true },
	kind: { type: String, default: 'service' },
	publishedLabel: { type: String, default: 'Pubblicato' },
	draftLabel: { type: String, default: 'Bozza' },
	createdLabel: { type: String, default: 'Creato' },
	updatedLabel: { type: String, default: 'Aggiornato' },
	formatDate: { type: Function, required: true },
	isToggling: { type: Boolean, default: false },
	isDeleting: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle', 'delete']);

const resolvedPreview = computed(() => {
	const text = String(props.previewText || '')
		.replace(/\s+/g, ' ')
		.trim();

	if (!text) return '';
	if (text.length <= 180) return text;

	return `${text.slice(0, 177).trim()}...`;
});

const visualKind = computed(() => (props.kind === 'guide' ? 'guide' : 'service'));
</script>

<template>
	<article class="flex gap-4 p-4 rounded-card border border-brand-border bg-brand-card transition hover:border-brand-primary/40 hover:shadow-sf-sm">
		<div class="shrink-0 w-[120px] h-[80px] rounded-control overflow-hidden bg-brand-bg-alt flex items-center justify-center text-brand-text-muted">
			<img
				v-if="article.featured_image || article.image_url"
				:src="article.featured_image || article.image_url"
				alt=""
				width="120"
				height="80"
				class="w-full h-full object-cover"
				loading="lazy"
				decoding="async">
			<UIcon
				v-else-if="visualKind === 'guide'"
				name="mdi:book-open-page-variant-outline"
				class="w-8 h-8" />
			<UIcon
				v-else
				name="mdi:cube-outline"
				class="w-8 h-8" />
		</div>

		<div class="flex-1 min-w-0 flex flex-col gap-2">
			<div class="flex flex-col tablet:flex-row tablet:items-start tablet:justify-between gap-2">
				<div class="flex-1 min-w-0">
					<h3 class="m-0 text-base font-bold text-brand-text leading-tight">{{ article.title }}</h3>
					<div class="mt-1.5 flex flex-wrap items-center gap-1.5">
						<span
							:class="[
								'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold',
								article.is_published
									? 'bg-brand-success-bg text-brand-success-fg'
									: 'bg-brand-bg-alt text-brand-text-secondary',
							]">
							<UIcon
								:name="article.is_published ? 'mdi:check-circle' : 'mdi:clock-outline'"
								class="w-3 h-3" />
							{{ article.is_published ? publishedLabel : draftLabel }}
						</span>
						<span
							v-if="article.sort_order !== null && article.sort_order !== undefined && article.sort_order !== ''"
							class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold bg-brand-bg-alt text-brand-text-secondary">
							Ordine {{ article.sort_order }}
						</span>
					</div>
				</div>

				<div class="flex flex-wrap items-center gap-1.5 shrink-0">
					<button
						type="button"
						:disabled="isToggling"
						:class="[
							'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-pill border text-xs font-semibold transition disabled:opacity-50',
							article.is_published
								? 'border-brand-border bg-brand-card text-brand-text-secondary hover:bg-brand-bg-alt'
								: 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100',
						]"
						@click="emit('toggle')">
						<UIcon name="mdi:eye-outline" class="w-3.5 h-3.5" />
						{{ article.is_published ? 'Metti in bozza' : 'Pubblica' }}
					</button>

					<NuxtLink :to="editTo" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-pill border border-brand-border bg-brand-card text-brand-text text-xs font-semibold no-underline transition hover:bg-brand-bg-alt hover:border-brand-primary/40">
						<UIcon name="mdi:pencil" class="w-3.5 h-3.5" />
						Modifica
					</NuxtLink>

					<button
						type="button"
						:disabled="isDeleting"
						class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-pill border border-red-200 bg-brand-card text-red-600 text-xs font-semibold transition hover:bg-red-50 disabled:opacity-50"
						@click="emit('delete')">
						<UIcon name="mdi:delete" class="w-3.5 h-3.5" />
						Elimina
					</button>
				</div>
			</div>

			<p v-if="resolvedPreview" class="m-0 text-sm text-brand-text-secondary leading-relaxed line-clamp-2">{{ resolvedPreview }}</p>

			<div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-brand-text-muted">
				<span class="font-mono">Slug {{ article.slug || '-' }}</span>
				<span>{{ createdLabel }} {{ formatDate(article.created_at) }}</span>
				<span v-if="article.updated_at && article.updated_at !== article.created_at">{{ updatedLabel }} {{ formatDate(article.updated_at) }}</span>
			</div>
		</div>
	</article>
</template>
