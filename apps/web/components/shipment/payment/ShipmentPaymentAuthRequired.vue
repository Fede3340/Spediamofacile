<script setup>
const emit = defineEmits(['request-login']);
</script>

<template>
	<div
		class="payment-auth-required"
		role="region"
		aria-label="Accesso richiesto per completare il pagamento">
		<div class="payment-auth-required__icon" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
				<path d="M7 11V7a5 5 0 0 1 10 0v4" />
			</svg>
		</div>
		<div class="payment-auth-required__copy">
			<p class="payment-auth-required__title">Accedi per completare il pagamento</p>
			<p class="payment-auth-required__sub">
				Serve un account SpediamoFacile per pagare in sicurezza, tracciare la spedizione e gestire resi, reclami e fatture.
			</p>
		</div>
		<div class="payment-auth-required__actions">
			<SfButton size="lg" @click="emit('request-login', 'login')">Accedi</SfButton>
			<SfButton variant="secondary" size="lg" @click="emit('request-login', 'register')">Crea account</SfButton>
		</div>
	</div>
</template>
