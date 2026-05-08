<script setup>
const stats = [
	{ value: '5', suffix: 'anni', label: 'di attività al servizio dei clienti italiani' },
	{ value: '120k', suffix: '+', label: 'spedizioni gestite ogni anno' },
	{ value: '18k', suffix: '', label: 'clienti attivi tra PMI e privati' },
	{ value: '94', suffix: '%', label: 'consegne completate entro 48 ore' },
];
</script>

<template>
	<section class="about-stats" aria-labelledby="about-stats-title">
		<div class="my-container">
			<header class="about-stats__header">
				<span class="about-stats__accent" aria-hidden="true" />
				<p class="about-stats__eyebrow">In numeri</p>
				<h2 id="about-stats-title" class="about-stats__title">I numeri di SpediamoFacile</h2>
			</header>
			<dl class="about-stats__grid">
				<div v-for="stat in stats" :key="stat.label" class="about-stats__card">
					<dt class="about-stats__value">
						<span class="about-stats__number">{{ stat.value }}</span>
						<span v-if="stat.suffix" class="about-stats__suffix">{{ stat.suffix }}</span>
					</dt>
					<dd class="about-stats__label">{{ stat.label }}</dd>
				</div>
			</dl>
			<p class="about-stats__note">Dati aggiornati al primo trimestre 2026.</p>
		</div>
	</section>
</template>

<style scoped>
.about-stats__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 16px;
}

.about-stats__card {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 24px 16px;
	background: #ffffff;
	border-radius: 18px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.05), 0 0 0 1px rgba(9, 88, 102, 0.06);
	text-align: center;
}

.about-stats__value {
	display: flex;
	align-items: baseline;
	gap: 4px;
	font-size: 2.5rem;
	font-weight: 800;
	color: var(--color-brand-primary);
	line-height: 1;
}

.about-stats__suffix {
	font-size: 1.25rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
}

.about-stats__label {
	font-size: 0.875rem;
	font-weight: 600;
	color: var(--color-brand-text);
}

.about-stats__note {
	font-size: 0.8125rem;
	color: var(--color-brand-text-muted, #7a8190);
	text-align: center;
	margin-top: 16px;
}
</style>
