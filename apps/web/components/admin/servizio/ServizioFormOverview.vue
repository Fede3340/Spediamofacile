<script setup>defineProps({
  publishStateLabel: { type: String, required: true },
  publishStateHint: { type: String, required: true },
  sectionCount: { type: Number, required: true },
  faqCount: { type: Number, required: true },
});
</script>

<template>
	<div class="service-editor-overview sf-account-panel mb-[20px] rounded-[16px] p-[18px] desktop:p-[20px]">
		<div class="service-editor-overview__copy">
			<p class="service-editor-overview__eyebrow">Editor servizio</p>
			<h2 class="service-editor-overview__title">Struttura, tono e pubblicazione</h2>
			<p class="service-editor-overview__text">Base chiara, sezioni ordinate e FAQ per i dubbi ricorrenti.</p>
		</div>
		<div class="service-editor-overview__stats">
			<article class="service-editor-stat service-editor-stat--teal">
				<span class="service-editor-stat__label">Stato</span>
				<strong class="service-editor-stat__value">{{ publishStateLabel }}</strong>
				<span class="service-editor-stat__meta">{{ publishStateHint }}</span>
			</article>
			<article class="service-editor-stat service-editor-stat--teal">
				<span class="service-editor-stat__label">Sezioni</span>
				<strong class="service-editor-stat__value">{{ sectionCount }}</strong>
				<span class="service-editor-stat__meta">Blocchi principali del servizio</span>
			</article>
			<article class="service-editor-stat service-editor-stat--teal">
				<span class="service-editor-stat__label">FAQ</span>
				<strong class="service-editor-stat__value">{{ faqCount }}</strong>
				<span class="service-editor-stat__meta">Risposte rapide finali</span>
			</article>
		</div>
	</div>
</template>
