<script setup>
const props = defineProps({
	promo: { type: Object, required: true },
	promoLoading: { type: Boolean, required: true },
	promoSaving: { type: Boolean, required: true },
	promoImageUploading: { type: Boolean, required: true },
	// Functions
	savePromo: { type: Function, required: true },
	uploadPromoImage: { type: Function, required: true },
});
</script>

<template>
	<div class="rounded-[16px] p-[16px] tablet:p-[20px] desktop:p-[24px] border border-[#E9EBEC] bg-white overflow-hidden shadow-sm">
		<h2 class="text-[1.125rem] font-bold text-[#252B42] mb-[6px] flex items-center gap-[8px]">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[20px] h-[20px] text-[#095866]" fill="currentColor"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82zM7 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
			Promozione Sito
		</h2>
		<p class="text-[0.75rem] text-[#737373] mb-[18px]">Gestisci etichetta, badge e preview promo senza uscire dalla console prezzi.</p>

		<div v-if="promoLoading" class="py-[24px] flex justify-center">
			<div class="w-[32px] h-[32px] border-3 border-[#E9EBEC] border-t-[#095866] rounded-full animate-spin"></div>
		</div>

		<div v-else class="space-y-[20px]">
			<!-- Toggle promozione attiva -->
			<div class="flex items-center justify-between p-[16px] bg-[#FAFBFC] rounded-[12px] border border-[#E9EBEC]">
				<div>
					<p class="text-[0.9375rem] font-semibold text-[#252B42]">Promozione attiva</p>
					<p class="text-[0.75rem] text-[#737373]">Mostra l'etichetta promozionale su tutto il sito</p>
				</div>
				<button type="button"
					role="switch"
					:aria-checked="promo.active ? 'true' : 'false'"
					aria-label="Attiva promozione"
					@click="promo.active = !promo.active"
					:class="promo.active ? 'bg-[#095866]' : 'bg-[#C8CCD0]'"
					class="relative inline-flex h-[36px] w-[60px] tablet:h-[28px] tablet:w-[52px] items-center rounded-full transition-colors cursor-pointer">
					<span
						:class="promo.active ? 'translate-x-[28px] tablet:translate-x-[26px]' : 'translate-x-[2px]'"
						class="inline-block h-[30px] w-[30px] tablet:h-[24px] tablet:w-[24px] transform rounded-full bg-white transition-transform shadow-sm" />
				</button>
			</div>

			<!-- Testo etichetta -->
			<div>
				<label class="block text-[0.8125rem] font-medium text-[#252B42] mb-[6px]">Testo etichetta</label>
				<input
					type="text"
					v-model="promo.label_text"
					placeholder="es. OFFERTA LANCIO"
					maxlength="100"
					class="w-full max-w-[400px] bg-[#FAFBFC] border border-[#E9EBEC] rounded-[50px] h-[48px] tablet:h-[44px] px-[16px] text-[1rem] tablet:text-[0.875rem] text-[#252B42] placeholder:text-[#A0A5AB] focus:border-[#095866] focus:outline-none" />
			</div>

			<!-- Descrizione sconto -->
			<div>
				<label class="block text-[0.8125rem] font-medium text-[#252B42] mb-[6px]">Descrizione sconto (mostrata nell'header)</label>
				<textarea
					v-model="promo.description"
					placeholder="es. Sconto del 20% su tutte le spedizioni nazionali! Valido fino al 31 marzo."
					maxlength="300"
					rows="3"
					class="w-full max-w-[500px] bg-[#FAFBFC] border border-[#E9EBEC] rounded-[16px] px-[16px] py-[12px] text-[0.875rem] text-[#252B42] placeholder:text-[#A0A5AB] focus:border-[#095866] focus:outline-none resize-y"></textarea>
				<p class="text-[0.6875rem] text-[var(--color-brand-text-muted)] mt-[4px]">Massimo 300 caratteri. Questo testo appare sotto il prezzo nella homepage.</p>
			</div>

			<!-- Colore etichetta -->
			<div>
				<label class="block text-[0.8125rem] font-medium text-[#252B42] mb-[6px]">Colore etichetta</label>
				<div class="flex flex-wrap items-center gap-[12px]">
					<input
						type="color"
						v-model="promo.label_color"
						class="w-[44px] h-[44px] rounded-[12px] border border-[#E9EBEC] cursor-pointer" />
					<input
						type="text"
						v-model="promo.label_color"
						placeholder="#095866"
						maxlength="20"
						class="w-[140px] bg-[#FAFBFC] border border-[#E9EBEC] rounded-[50px] h-[44px] px-[16px] text-[0.875rem] text-[#252B42] font-mono focus:border-[#095866] focus:outline-none" />
					<span
						v-if="promo.label_text"
						:style="{ backgroundColor: promo.label_color }"
						class="inline-flex items-center px-[12px] py-[6px] rounded-[12px] text-white text-[0.8125rem] font-bold tracking-wide">
						{{ promo.label_text }}
					</span>
				</div>
			</div>

			<!-- Upload immagine -->
			<div>
				<label class="block text-[0.8125rem] font-medium text-[#252B42] mb-[6px]">Immagine promozionale (opzionale)</label>
				<div class="flex flex-wrap items-center gap-[12px] tablet:gap-[16px]">
					<label class="inline-flex items-center gap-[8px] px-[16px] py-[10px] bg-[#FAFBFC] border border-[#E9EBEC] rounded-[50px] text-[0.875rem] text-[#252B42] hover:bg-[rgba(9,88,102,0.06)] transition cursor-pointer">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] text-[#095866]" fill="currentColor"><path d="M5,3A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H14.09C14.03,20.67 14,20.34 14,20C14,19.32 14.19,18.68 14.54,18H5L8.5,13.5L11,16.5L14.5,12L16.73,14.97C17.7,14.34 18.84,14 20,14C20.34,14 20.67,14.03 21,14.09V5A2,2 0 0,0 19,3H5M19,16V19H16V21H19V24H21V21H24V19H21V16H19Z"/></svg>
						{{ promoImageUploading ? 'Caricamento...' : 'Carica immagine' }}
						<input type="file" accept="image/*" class="hidden" @change="uploadPromoImage" :disabled="promoImageUploading" />
					</label>
					<div v-if="promo.label_image" class="flex items-center gap-[8px]">
						<img :src="promo.label_image" alt="Promo" loading="lazy" decoding="async" width="80" height="40" class="h-[40px] w-auto rounded-[6px] border border-[#E9EBEC]" />
						<button type="button" @click="promo.label_image = null" class="text-red-500 text-[0.75rem] hover:opacity-80 cursor-pointer">Rimuovi</button>
					</div>
				</div>
			</div>

			<!-- Toggle badge sconto % -->
			<div class="flex items-center justify-between p-[16px] bg-[#FAFBFC] rounded-[12px] border border-[#E9EBEC]">
				<div>
					<p class="text-[0.9375rem] font-semibold text-[#252B42]">Mostra badge sconto %</p>
					<p class="text-[0.75rem] text-[#737373]">Mostra il badge con la percentuale di sconto accanto ai prezzi</p>
				</div>
				<button type="button"
					role="switch"
					:aria-checked="promo.show_badges ? 'true' : 'false'"
					aria-label="Mostra badge sconto percentuale"
					@click="promo.show_badges = !promo.show_badges"
					:class="promo.show_badges ? 'bg-[#095866]' : 'bg-[#C8CCD0]'"
					class="relative inline-flex h-[36px] w-[60px] tablet:h-[28px] tablet:w-[52px] items-center rounded-full transition-colors cursor-pointer">
					<span
						:class="promo.show_badges ? 'translate-x-[28px] tablet:translate-x-[26px]' : 'translate-x-[2px]'"
						class="inline-block h-[30px] w-[30px] tablet:h-[24px] tablet:w-[24px] transform rounded-full bg-white transition-transform shadow-sm" />
				</button>
			</div>

			<!-- Anteprima live -->
			<div v-if="promo.active && (promo.label_text || promo.description)" class="p-[20px] bg-[#F0F4F5] rounded-[12px] border border-[#D0D8DA]">
				<p class="text-[0.75rem] font-semibold text-[#737373] mb-[12px] uppercase tracking-wider">Anteprima header homepage</p>
				<div class="bg-white rounded-[12px] p-[16px] shadow-sm">
					<p class="text-[1.25rem] font-bold text-[#222]">Spedisci in Italia</p>
					<div class="flex items-center gap-[10px] mt-[6px]">
						<span class="text-[1rem] text-[#444] font-semibold">a partire da</span>
						<span class="inline-flex items-center justify-center px-[14px] py-[6px] bg-[#095866] text-white font-extrabold text-[1.25rem] rounded-[40px]">8,90 &euro;</span>
					</div>
					<div v-if="promo.show_badges" class="flex items-center gap-[8px] mt-[6px]">
						<span class="inline-flex items-center gap-[4px] px-[8px] py-[3px] rounded-[12px] bg-[#095866] text-white text-[0.75rem] font-bold">-20%</span>
					</div>
					<div v-if="promo.label_text" class="mt-[6px]">
						<span
							:style="{ backgroundColor: promo.label_color || '#095866' }"
							class="inline-flex items-center gap-[6px] px-[10px] py-[4px] rounded-[12px] text-white text-[0.75rem] font-bold tracking-wide">
							<img v-if="promo.label_image" :src="promo.label_image" alt="" loading="lazy" decoding="async" width="30" height="14" class="h-[14px] w-auto shrink-0" />
							{{ promo.label_text }}
						</span>
					</div>
					<p v-if="promo.description" class="text-[0.8125rem] text-[#444] font-medium mt-[6px]">{{ promo.description }}</p>
					<p class="text-[0.9375rem] font-extrabold mt-[10px] text-[#222]">IVA e ritiro incluso</p>
				</div>
			</div>

			<!-- Salva promozione -->
			<div class="flex justify-end">
				<button type="button" @click="savePromo" :disabled="promoSaving" class="inline-flex min-h-[44px] items-center gap-[8px] px-[18px] py-[10px] bg-[#E44203] hover:bg-[#cd3a00] text-white rounded-[50px] text-[0.875rem] font-semibold transition-colors cursor-pointer disabled:opacity-50">
					<svg v-if="promoSaving" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] animate-spin" fill="currentColor"><path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z"/></svg>
					<svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px]" fill="currentColor"><path d="M15,9H5V5H15M12,19A3,3 0 0,1 9,16A3,3 0 0,1 12,13A3,3 0 0,1 15,16A3,3 0 0,1 12,19M17,3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3Z"/></svg>
					{{ promoSaving ? "Salvataggio..." : "Salva promozione" }}
				</button>
			</div>
		</div>
	</div>
</template>
