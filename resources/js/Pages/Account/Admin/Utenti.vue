<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
defineProps({ users: { type: Object, default: () => ({ data: [] }) } });
const search = ref('');
const filter = ref('');
</script>
<template>
	<Head title="Utenti admin" />
	<div class="sf-container py-12">
		<h1 class="text-3xl font-bold mb-6">Utenti</h1>
		<div class="grid md:grid-cols-4 gap-4 mb-6">
			<div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase font-semibold text-[var(--color-brand-text-muted)]">Totale</div><div class="text-2xl font-bold">{{ users.total }}</div></div>
		</div>
		<div class="bg-white border rounded-2xl overflow-hidden">
			<table class="w-full">
				<thead class="bg-[var(--color-brand-bg)] text-left text-xs uppercase font-semibold">
					<tr><th class="p-3">Nome</th><th class="p-3">Email</th><th class="p-3">Ruolo</th><th class="p-3">Stato</th></tr>
				</thead>
				<tbody>
					<tr v-for="u in users.data" :key="u.id" class="border-t">
						<td class="p-3">{{ u.name }} {{ u.surname }}</td>
						<td class="p-3 text-sm">{{ u.email }}</td>
						<td class="p-3"><span class="text-xs px-2 py-1 rounded-full bg-[var(--color-brand-bg)]">{{ u.role }}</span></td>
						<td class="p-3 text-sm">{{ u.email_verified_at ? 'Verificato' : 'In attesa' }}</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
