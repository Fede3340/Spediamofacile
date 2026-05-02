<script setup>
import { computed } from 'vue';

const props = defineProps({
	user: { type: Object, required: true },
	formatDate: { type: Function, required: true },
});

const userType = computed(() => {
	const t = (props.user?.user_type || 'privato').toLowerCase();
	return t === 'commerciante' || t === 'azienda' ? 'Azienda' : 'Privato';
});
</script>

<template>
	<section class="flex flex-col gap-2.5">
		<h3 class="m-0 text-[0.6875rem] font-extrabold uppercase tracking-wider text-brand-text-muted">Dati profilo</h3>
		<dl class="grid grid-cols-2 gap-2 m-0 p-3 bg-brand-bg-alt border border-brand-border rounded-control">
			<div>
				<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">ID</dt>
				<dd class="mt-0.5 text-sm font-semibold text-brand-text break-words">#{{ user.id }}</dd>
			</div>
			<div>
				<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Tipo</dt>
				<dd class="mt-0.5 text-sm font-semibold text-brand-text break-words">{{ userType }}</dd>
			</div>
			<div>
				<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Telefono</dt>
				<dd class="mt-0.5 text-sm font-semibold text-brand-text break-words">{{ user.telephone_number || '—' }}</dd>
			</div>
			<div>
				<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Codice referral</dt>
				<dd class="mt-0.5 text-xs font-mono font-semibold text-brand-text break-words">{{ user.referral_code || '—' }}</dd>
			</div>
			<div>
				<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Registrato</dt>
				<dd class="mt-0.5 text-sm font-semibold text-brand-text break-words">{{ formatDate(user.created_at) }}</dd>
			</div>
			<div>
				<dt class="text-[0.625rem] font-bold uppercase tracking-wider text-brand-text-muted">Email verificata</dt>
				<dd class="mt-0.5 text-sm font-semibold text-brand-text break-words">{{ user.email_verified_at ? formatDate(user.email_verified_at) : 'No' }}</dd>
			</div>
		</dl>
	</section>
</template>
