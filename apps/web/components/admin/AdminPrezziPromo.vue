<script setup>
const props = defineProps({
	promo: { type: Object, required: true },
	promoLoading: { type: Boolean, required: true },
	promoSaving: { type: Boolean, required: true },
	promoImageUploading: { type: Boolean, required: true },
	savePromo: { type: Function, required: true },
	uploadPromoImage: { type: Function, required: true },
});

const emit = defineEmits(['update:promo']);

const promoValue = (field) => props.promo?.[field] ?? '';
const updatePromo = (field, value) => {
	emit('update:promo', {
		...props.promo,
		[field]: value,
	});
};
const togglePromo = (field) => updatePromo(field, !promoValue(field));
</script>

<template>
	<SfCard padding="md" title="Promozione Sito" description="Gestisci etichetta, badge e preview promo senza uscire dalla console prezzi.">
		<template #icon>
			<UIcon name="mdi:tag-outline" class="w-5 h-5 text-brand-primary" />
		</template>

		<div v-if="promoLoading" class="py-6 flex justify-center">
			<UIcon name="mdi:loading" class="w-8 h-8 text-brand-primary animate-spin" />
		</div>

		<div v-else class="space-y-6">
			<div class="flex items-center justify-between p-4 bg-brand-bg-alt rounded-card border border-brand-border">
				<div>
					<p class="text-base font-semibold text-brand-text">Promozione attiva</p>
					<p class="text-xs text-brand-text-muted">Mostra l'etichetta promozionale su tutto il sito</p>
				</div>
				<button
					type="button"
					role="switch"
					:aria-checked="promoValue('active') ? 'true' : 'false'"
					aria-label="Attiva promozione"
					:class="promoValue('active') ? 'bg-brand-primary' : 'bg-brand-border'"
					class="relative inline-flex h-9 w-15 tablet:h-7 tablet:w-13 items-center rounded-full transition-colors cursor-pointer"
					@click="togglePromo('active')">
					<span
						:class="promoValue('active') ? 'translate-x-[28px] tablet:translate-x-[26px]' : 'translate-x-[2px]'"
						class="inline-block h-[30px] w-[30px] tablet:h-6 tablet:w-6 transform rounded-full bg-white transition-transform shadow-sm" />
				</button>
			</div>

			<SfFormGroup label="Testo etichetta">
				<div class="max-w-[400px]">
					<SfInput
						type="text"
						:model-value="promoValue('label_text')"
						placeholder="es. OFFERTA LANCIO"
						@update:model-value="(v) => updatePromo('label_text', v)" />
				</div>
			</SfFormGroup>

			<SfFormGroup label="Descrizione sconto (mostrata nell'header)" hint="Massimo 300 caratteri. Questo testo appare sotto il prezzo nella homepage.">
				<div class="max-w-[500px]">
					<SfTextarea
						:model-value="promoValue('description')"
						placeholder="es. Sconto del 20% su tutte le spedizioni nazionali! Valido fino al 31 marzo."
						:maxlength="300"
						:rows="3"
						@update:model-value="(v) => updatePromo('description', v)" />
				</div>
			</SfFormGroup>

			<SfFormGroup label="Colore etichetta">
				<div class="flex flex-wrap items-center gap-3">
					<input
						type="color"
						:value="promoValue('label_color')"
						class="w-11 h-11 rounded-control border border-brand-border cursor-pointer"
						@input="updatePromo('label_color', $event.target.value)">
					<div class="w-[140px]">
						<SfInput
							type="text"
							:model-value="promoValue('label_color')"
							placeholder="#095866"
							@update:model-value="(v) => updatePromo('label_color', v)" />
					</div>
					<span
						v-if="promo.label_text"
						:style="{ backgroundColor: promo.label_color }"
						class="inline-flex items-center px-3 py-1.5 rounded-control text-white text-sm font-bold tracking-wide">
						{{ promo.label_text }}
					</span>
				</div>
			</SfFormGroup>

			<SfFormGroup label="Immagine promozionale (opzionale)">
				<div class="flex flex-wrap items-center gap-3 tablet:gap-4">
					<label class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-bg-alt border border-brand-border rounded-pill text-sm text-brand-text hover:bg-brand-soft-bg transition cursor-pointer">
						<UIcon name="mdi:image-plus" class="w-[18px] h-[18px] text-brand-primary" />
						{{ promoImageUploading ? 'Caricamento...' : 'Carica immagine' }}
						<input type="file" accept="image/*" class="hidden" :disabled="promoImageUploading" @change="uploadPromoImage">
					</label>
					<div v-if="promo.label_image" class="flex items-center gap-2">
						<img :src="promo.label_image" alt="Promo" loading="lazy" decoding="async" width="80" height="40" class="h-10 w-auto rounded border border-brand-border">
						<SfButton variant="ghost" size="sm" @click="updatePromo('label_image', null)">Rimuovi</SfButton>
					</div>
				</div>
			</SfFormGroup>

			<div class="flex items-center justify-between p-4 bg-brand-bg-alt rounded-card border border-brand-border">
				<div>
					<p class="text-base font-semibold text-brand-text">Mostra badge sconto %</p>
					<p class="text-xs text-brand-text-muted">Mostra il badge con la percentuale di sconto accanto ai prezzi</p>
				</div>
				<button
					type="button"
					role="switch"
					:aria-checked="promoValue('show_badges') ? 'true' : 'false'"
					aria-label="Mostra badge sconto percentuale"
					:class="promoValue('show_badges') ? 'bg-brand-primary' : 'bg-brand-border'"
					class="relative inline-flex h-9 w-15 tablet:h-7 tablet:w-13 items-center rounded-full transition-colors cursor-pointer"
					@click="togglePromo('show_badges')">
					<span
						:class="promoValue('show_badges') ? 'translate-x-[28px] tablet:translate-x-[26px]' : 'translate-x-[2px]'"
						class="inline-block h-[30px] w-[30px] tablet:h-6 tablet:w-6 transform rounded-full bg-white transition-transform shadow-sm" />
				</button>
			</div>

			<div v-if="promo.active && (promo.label_text || promo.description)" class="p-5 bg-brand-bg-alt rounded-card border border-brand-border">
				<p class="text-xs font-semibold text-brand-text-muted mb-3 uppercase tracking-wider">Anteprima header homepage</p>
				<div class="bg-brand-card rounded-card p-4 shadow-sf-sm">
					<p class="text-xl font-bold text-brand-text">Spedisci in Italia</p>
					<div class="flex items-center gap-2.5 mt-1.5">
						<span class="text-base text-brand-text font-semibold">a partire da</span>
						<span class="inline-flex items-center justify-center px-3.5 py-1.5 bg-brand-primary text-white font-extrabold text-xl rounded-pill">8,90 €</span>
					</div>
					<div v-if="promo.show_badges" class="flex items-center gap-2 mt-1.5">
						<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-control bg-brand-primary text-white text-xs font-bold">-20%</span>
					</div>
					<div v-if="promo.label_text" class="mt-1.5">
						<span
							:style="{ backgroundColor: promo.label_color || '#095866' }"
							class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-control text-white text-xs font-bold tracking-wide">
							<img v-if="promo.label_image" :src="promo.label_image" alt="" loading="lazy" decoding="async" width="30" height="14" class="h-3.5 w-auto shrink-0">
							{{ promo.label_text }}
						</span>
					</div>
					<p v-if="promo.description" class="text-sm text-brand-text font-medium mt-1.5">{{ promo.description }}</p>
					<p class="text-base font-extrabold mt-2.5 text-brand-text">IVA e ritiro incluso</p>
				</div>
			</div>

			<div class="flex justify-end">
				<SfButton variant="primary" :loading="promoSaving" :disabled="promoSaving" @click="savePromo">
					<template #leading>
						<UIcon name="mdi:content-save" class="w-[18px] h-[18px]" />
					</template>
					{{ promoSaving ? "Salvataggio..." : "Salva promozione" }}
				</SfButton>
			</div>
		</div>
	</SfCard>
</template>
