<script setup>
import '~/assets/css/admin.css';

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
	<article class="sf-admin-content-row">
		<div class="sf-admin-content-row__media">
			<!-- width/height intrinseche per prevenire CLS nella row admin. -->
			<img
				v-if="article.featured_image || article.image_url"
				:src="article.featured_image || article.image_url"
				alt=""
				width="120"
				height="80"
				class="sf-admin-content-row__image"
				loading="lazy"
				decoding="async" />
			<svg
				v-else-if="visualKind === 'guide'"
				aria-hidden="true"
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				class="sf-admin-content-row__fallback-icon"
				fill="currentColor">
				<path d="M19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M7,7H17V9H7V7M7,11H17V13H7V11M7,15H13V17H7V15Z" />
			</svg>
			<svg
				v-else
				aria-hidden="true"
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				class="sf-admin-content-row__fallback-icon"
				fill="currentColor">
				<path d="M12,2L2,7L12,12L22,7L12,2M4,9.5V16.5L12,21L20,16.5V9.5L12,14L4,9.5Z" />
			</svg>
		</div>

		<div class="sf-admin-content-row__body">
			<div class="sf-admin-content-row__header">
				<div class="sf-admin-content-row__headline">
					<h3 class="sf-admin-content-row__title">{{ article.title }}</h3>
					<div class="sf-admin-content-row__badges">
						<span
							class="sf-admin-content-row__badge"
							:class="article.is_published ? 'sf-admin-content-row__badge--published' : 'sf-admin-content-row__badge--draft'">
							<svg
								v-if="article.is_published"
								aria-hidden="true"
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								class="h-[12px] w-[12px]"
								fill="currentColor">
								<path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" />
							</svg>
							<svg
								v-else
								aria-hidden="true"
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								class="h-[12px] w-[12px]"
								fill="currentColor">
								<path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" />
							</svg>
							{{ article.is_published ? publishedLabel : draftLabel }}
						</span>
						<span
							v-if="article.sort_order !== null && article.sort_order !== undefined && article.sort_order !== ''"
							class="sf-admin-content-row__badge sf-admin-content-row__badge--order">
							Ordine {{ article.sort_order }}
						</span>
					</div>
				</div>

				<div class="sf-admin-content-row__actions">
					<button
						type="button"
						:disabled="isToggling"
						class="sf-admin-content-row__action"
						:class="article.is_published ? 'sf-admin-content-row__action--quiet' : 'sf-admin-content-row__action--warm'"
						@click="emit('toggle')">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[14px] w-[14px]" fill="currentColor">
							<path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" />
						</svg>
						{{ article.is_published ? 'Metti in bozza' : 'Pubblica' }}
					</button>

					<NuxtLink :to="editTo" class="sf-admin-content-row__action sf-admin-content-row__action--neutral">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[14px] w-[14px]" fill="currentColor">
							<path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z" />
						</svg>
						Modifica
					</NuxtLink>

					<button
						type="button"
						:disabled="isDeleting"
						class="sf-admin-content-row__action sf-admin-content-row__action--danger"
						@click="emit('delete')">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[14px] w-[14px]" fill="currentColor">
							<path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z" />
						</svg>
						Elimina
					</button>
				</div>
			</div>

			<p class="sf-admin-content-row__preview">{{ resolvedPreview }}</p>

			<div class="sf-admin-content-row__meta">
				<span class="sf-admin-content-row__slug">Slug {{ article.slug || '-' }}</span>
				<span>{{ createdLabel }} {{ formatDate(article.created_at) }}</span>
				<span v-if="article.updated_at && article.updated_at !== article.created_at">{{ updatedLabel }} {{ formatDate(article.updated_at) }}</span>
			</div>
		</div>
	</article>
</template>
