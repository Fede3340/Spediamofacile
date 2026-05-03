<script setup>

const faqs = [
	{
		q: 'Quanto costa spedire con SpediamoFacile?',
		a: 'Le tariffe partono da 5,90 € per pacchi fino a 5 kg in Italia. Il prezzo dipende da peso, dimensioni e destinazione: calcolalo in 30 secondi senza registrazione.',
	},
	{
		q: 'Quanto tempo impiega la consegna?',
		a: 'Per le spedizioni nazionali la consegna avviene in 24/48 ore lavorative. In Europa i tempi vanno da 2 a 5 giorni lavorativi a seconda del paese.',
	},
];

const openFaqIndexes = ref([]);
function isFaqOpen(i) {
	return openFaqIndexes.value.includes(i);
}
function toggleFaq(i) {
	const idx = openFaqIndexes.value.indexOf(i);
	if (idx >= 0) {
		openFaqIndexes.value.splice(idx, 1);
	} else {
		openFaqIndexes.value.push(i);
	}
}
</script>

<template>
	<section class="faq" aria-labelledby="faq-title">
		<div class="container faq__inner">
			<aside class="faq__aside">
				<HomeSectionHead
					eyebrow="Domande frequenti"
					title="Tutto quello che vuoi sapere"
					title-id="faq-title"
					subtitle="Le risposte alle domande più comuni dei nostri clienti."
					align="left" />
				<div class="faq__aside-meta" data-reveal>
					<p class="faq__aside-hint">Non trovi la risposta? Scrivici o guarda le guide complete.</p>
					<div class="faq__aside-actions">
						<SfButton to="/faq">Vai a tutte le FAQ</SfButton>
						<SfButton variant="secondary" to="/contatti">Contatta l'assistenza</SfButton>
					</div>
				</div>
			</aside>

			<ul class="faq__list" role="list">
				<li
					v-for="(item, i) in faqs"
					:key="i"
					class="faq__item"
					:data-open="isFaqOpen(i) ? 'true' : 'false'"
					data-reveal>
					<button
						:id="`faq-trigger-${i}`"
						type="button"
						class="faq__q"
						:aria-expanded="isFaqOpen(i)"
						:aria-controls="`faq-panel-${i}`"
						@click="toggleFaq(i)">
						<span>{{ item.q }}</span>
						<span class="faq__icon" aria-hidden="true">
							<svg viewBox="0 0 16 16" focusable="false">
								<path d="M3 6l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</span>
					</button>
					<div
						v-show="isFaqOpen(i)"
						:id="`faq-panel-${i}`"
						role="region"
						:aria-labelledby="`faq-trigger-${i}`"
						class="faq__a">
						<p>{{ item.a }}</p>
					</div>
				</li>
			</ul>
		</div>
	</section>
</template>

<style scoped>
.container {
	max-width: 1280px;
	margin-inline: auto;
	padding-inline: 14px;
}
@media (min-width: 1024px) {
	.container { padding-inline: 40px; }
}
.faq { padding-block: 72px; background: #f7faf9; }
.faq__inner {
	display: grid;
	gap: 32px;
}
.faq__aside {
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	gap: 28px;
}
.faq__aside-meta {
	padding: 20px;
	border-radius: 16px;
	background: #ffffff;
	border: 1px solid #e3ece9;
	box-shadow: 0 6px 18px rgba(9, 88, 102, 0.05);
	display: flex;
	flex-direction: column;
	gap: 14px;
}
.faq__aside-hint {
	margin: 0;
	font-size: 14px;
	line-height: 1.55;
	color: #475559;
}
.faq__aside-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
}
@media (min-width: 1024px) {
	.faq__inner { grid-template-columns: 1fr 1.4fr; align-items: stretch; gap: 56px; }
	.faq__aside { position: sticky; top: 88px; }
}
.faq__list {
	margin: 0;
	padding: 0;
	list-style: none;
	display: grid;
	gap: 12px;
}
.faq__item {
	background: #ffffff;
	border: 1px solid #eef2f0;
	border-radius: 14px;
	overflow: hidden;
	transition: border-color var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease);
}
.faq__item[data-open="true"] {
	border-color: #c7d6d2;
	box-shadow: 0 8px 22px -16px rgba(9, 88, 102, 0.25);
}
.faq__q {
	width: 100%;
	background: transparent;
	border: 0;
	padding: 18px 20px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	text-align: left;
	font: inherit;
	font-size: 16px;
	font-weight: 600;
	color: #0d3a44;
	cursor: pointer;
}
.faq__q:focus-visible {
	outline: none;
	box-shadow: inset 0 0 0 3px rgba(9, 88, 102, 0.18);
}
.faq__icon {
	color: #095866;
	transition: transform var(--sf-t1) var(--sf-ease);
	display: inline-grid;
	place-items: center;
	width: 22px;
	height: 22px;
}
.faq__icon svg { width: 16px; height: 16px; }
.faq__item[data-open="true"] .faq__icon { transform: rotate(180deg); color: #E44203; }
.faq__a {
	padding: 0 20px 18px;
	font-size: 15px;
	line-height: 1.6;
	color: #475559;
}
.faq__a p { margin: 0; }
</style>
