<script setup>
const team = [
	{ name: 'Marco Donadel', role: 'CEO & Co-founder', initials: 'MD' },
	{ name: 'Elena Rosati', role: 'CTO', initials: 'ER' },
	{ name: 'Luca Bianchi', role: 'Head of Operations', initials: 'LB' },
	{ name: 'Sara Conti', role: 'Customer Care Lead', initials: 'SC' },
];
</script>

<template>
	<section class="about-team" aria-labelledby="about-team-title">
		<div class="my-container">
			<header class="about-team__header">
				<span class="about-team__accent" aria-hidden="true"/>
				<p class="about-team__eyebrow">Le persone</p>
				<h2 id="about-team-title" class="about-team__title">Chi c'è dietro la piattaforma</h2>
				<p class="about-team__lead">
					Un team piccolo e diretto: ogni messaggio che ricevi arriva da una persona di questa
					lista o da chi lavora con noi tutti i giorni.
				</p>
			</header>
			<ul class="about-team__grid">
				<li v-for="member in team" :key="member.name" class="about-team__card">
					<div class="about-team__avatar" aria-hidden="true">
						<span class="about-team__initials">{{ member.initials }}</span>
					</div>
					<p class="about-team__name">{{ member.name }}</p>
					<p class="about-team__role">{{ member.role }}</p>
				</li>
			</ul>
		</div>
	</section>
</template>

<style scoped>
.about-team__lead {
	max-width: 600px;
	margin: 0 auto 16px;
	font-size: 0.9375rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.55;
	text-align: center;
}

.about-team__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
	gap: 20px;
}

.about-team__card {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 10px;
	padding: 24px 16px;
	background: #ffffff;
	border-radius: 18px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.05), 0 0 0 1px rgba(9, 88, 102, 0.06);
	text-align: center;
	list-style: none;
}

.about-team__avatar {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 72px;
	height: 72px;
	border-radius: 50%;
	background: linear-gradient(135deg, var(--color-brand-primary), #0a7489);
	color: #ffffff;
}

.about-team__initials {
	font-size: 1.5rem;
	font-weight: 700;
	color: #ffffff;
}

.about-team__name {
	font-size: 1rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.about-team__role {
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--color-brand-orange, #E44203);
	letter-spacing: 0.04em;
	text-transform: uppercase;
}

.about-team__accent {
	width: 36px;
	height: 3px;
	background: var(--color-brand-primary);
	border-radius: 2px;
	margin: 8px auto 0;
}
</style>
