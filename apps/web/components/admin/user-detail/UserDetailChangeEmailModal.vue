<script setup>
defineProps({
	open: { type: Boolean, default: false },
	fullName: { type: String, default: '' },
	saving: { type: Boolean, default: false },
});

const email = defineModel('email', { type: String, default: '' });

const emit = defineEmits(['update:open', 'confirm']);

const close = () => emit('update:open', false);
</script>

<template>
	<ClientOnly>
		<UModal
			v-if="open"
			:open="open"
			title="Cambia email"
			description="Inserisci la nuova email dell'utente e conferma l'aggiornamento."
			:ui="{ overlay: 'bg-[#09131c]/36 backdrop-blur-[6px]', content: '!divide-y-0 !ring-0 !p-0 sf-modal-surface w-[min(calc(100vw-1rem),26rem)]', body: '!p-0' }"
			@update:open="$emit('update:open', $event)">
			<template #body>
				<section class="p-5 flex flex-col gap-3">
					<h3 class="m-0 text-base font-extrabold text-brand-text">Cambia email</h3>
					<p class="m-0 text-sm text-brand-text-secondary">Inserisci la nuova email per {{ fullName }}. L'utente verra avvisato.</p>
					<input
						v-model="email"
						type="email"
						class="h-10 px-3 border border-brand-border rounded-control bg-brand-card text-brand-text text-sm focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
						placeholder="nome@dominio.it">
					<div class="flex justify-end gap-2">
						<SfButton variant="secondary" :disabled="saving" @click="close">Annulla</SfButton>
						<SfButton :loading="saving" :disabled="saving || !email" @click="$emit('confirm')">
							{{ saving ? 'Salvataggio...' : 'Conferma' }}
						</SfButton>
					</div>
				</section>
			</template>
		</UModal>
	</ClientOnly>
</template>
