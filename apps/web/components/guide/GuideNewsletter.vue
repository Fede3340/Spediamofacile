<script setup>
import '~/assets/css/content.css';
</script>

<template>
	<div>
		<section class="guide-newsletter">
			<div class="my-container">
				<div class="guide-newsletter__inner">
					<div class="guide-newsletter__text">
						<h2 class="guide-newsletter__title font-montserrat">Ricevi le guide via email</h2>
						<p class="guide-newsletter__desc">
							Iscriviti per ricevere nuove guide, consigli e aggiornamenti direttamente nella tua casella di posta.
						</p>
					</div>
					<form class="guide-newsletter__form" @submit.prevent>
						<input
							type="email"
							placeholder="La tua email..."
							class="guide-newsletter__input"
						/>
						<button type="submit" class="guide-newsletter__btn">
							Iscriviti
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
							</svg>
						</button>
					</form>
				</div>
			</div>
		</section>

		<section class="guide-bottom-cta">
			<div class="my-container text-center">
				<NuxtLink
					to="/preventivo"
					class="guide-cta-btn"
				>
					Calcola preventivo
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
					</svg>
				</NuxtLink>
			</div>
		</section>
	</div>
</template>

