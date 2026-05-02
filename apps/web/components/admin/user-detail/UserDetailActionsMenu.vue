<script setup>
defineProps({
	isBanned: { type: Boolean, default: false },
	canMaster: { type: Boolean, default: false },
});

defineEmits(['reset-password', 'toggle-ban', 'change-email', 'impersonate']);
</script>

<template>
	<section class="flex flex-col gap-2.5 pt-3.5 border-t border-brand-border">
		<h3 class="m-0 text-[0.6875rem] font-extrabold uppercase tracking-wider text-brand-text-muted">Azioni rapide</h3>
		<div class="grid grid-cols-2 mobile:grid-cols-1 gap-2">
			<button
				type="button"
				class="inline-flex items-center justify-center gap-1.5 min-h-10 px-3 rounded-control border border-brand-border bg-brand-card text-brand-text text-sm font-bold cursor-pointer transition hover:bg-brand-bg-alt hover:border-brand-primary/40"
				@click="$emit('reset-password')">
				<UIcon name="mdi:lock-reset" class="w-4 h-4" />
				Reset password
			</button>
			<button
				type="button"
				:class="[
					'inline-flex items-center justify-center gap-1.5 min-h-10 px-3 rounded-control border text-sm font-bold cursor-pointer transition',
					isBanned
						? 'bg-brand-primary text-white border-brand-primary hover:bg-brand-primary-hover'
						: 'bg-brand-card text-red-700 border-red-200 hover:bg-red-50 hover:border-red-300',
				]"
				@click="$emit('toggle-ban')">
				<UIcon name="mdi:cancel" class="w-4 h-4" />
				{{ isBanned ? 'Rimuovi ban' : 'Banna utente' }}
			</button>
			<button
				v-if="canMaster"
				type="button"
				class="inline-flex items-center justify-center gap-1.5 min-h-10 px-3 rounded-control border border-brand-border bg-brand-card text-brand-text text-sm font-bold cursor-pointer transition hover:bg-brand-bg-alt hover:border-brand-primary/40"
				@click="$emit('change-email')">
				<UIcon name="mdi:email-edit-outline" class="w-4 h-4" />
				Cambia email
			</button>
			<button
				v-if="canMaster"
				type="button"
				class="inline-flex items-center justify-center gap-1.5 min-h-10 px-3 rounded-control border border-brand-accent bg-brand-accent text-white text-sm font-bold cursor-pointer transition hover:brightness-95"
				@click="$emit('impersonate')">
				<UIcon name="mdi:account-arrow-right" class="w-4 h-4" />
				Impersona
			</button>
		</div>
	</section>
</template>
