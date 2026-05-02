<script setup>
const model = defineModel({
	type: Object,
	required: true,
});

defineProps({
	saving: { type: Boolean, default: false },
});

defineEmits(['save']);
</script>

<template>
	<section class="flex flex-col gap-2.5">
		<h3 class="m-0 text-[0.6875rem] font-extrabold uppercase tracking-wider text-brand-text-muted">Permessi e stato</h3>
		<div class="flex flex-col gap-2.5 p-3 bg-brand-bg-alt border border-brand-border rounded-control">
			<label class="flex flex-col gap-1">
				<span class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Ruolo</span>
				<select v-model="model.role" class="h-10 px-3 border border-brand-border rounded-control bg-brand-card text-brand-text text-sm font-semibold cursor-pointer focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" :disabled="saving">
					<option value="User">Cliente (Privato)</option>
					<option value="Partner">Partner</option>
					<option value="Partner Pro">Partner Pro</option>
					<option value="Admin">Admin</option>
				</select>
			</label>
			<label class="flex flex-col gap-1">
				<span class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Stato account</span>
				<select v-model="model.status" class="h-10 px-3 border border-brand-border rounded-control bg-brand-card text-brand-text text-sm font-semibold cursor-pointer focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" :disabled="saving">
					<option value="active">Attivo</option>
					<option value="pending-verification">In verifica email</option>
					<option value="banned">Bannato</option>
				</select>
			</label>
			<label class="flex items-center gap-2.5 py-2 cursor-pointer">
				<span class="relative inline-block w-9 h-5">
					<input v-model="model.is_pro" type="checkbox" class="sr-only peer" :disabled="saving">
					<span class="absolute inset-0 rounded-full bg-brand-border transition peer-checked:bg-brand-primary" />
					<span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition peer-checked:translate-x-4" />
				</span>
				<span class="text-sm font-semibold text-brand-text">Utente Pro (visibile come Partner Pro)</span>
			</label>
			<button
				type="button"
				class="inline-flex items-center justify-center gap-1.5 min-h-10 px-3.5 rounded-control border border-brand-primary bg-brand-primary text-white text-sm font-bold cursor-pointer transition hover:bg-brand-primary-hover disabled:opacity-60 disabled:cursor-not-allowed"
				:disabled="saving"
				@click="$emit('save')">
				<UIcon name="mdi:check" class="w-3.5 h-3.5" />
				{{ saving ? 'Salvataggio...' : 'Salva modifiche' }}
			</button>
		</div>
	</section>
</template>
