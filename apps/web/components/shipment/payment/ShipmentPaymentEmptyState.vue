<template>
	<div class="flex flex-col items-center gap-[16px] py-[32px] px-[20px] text-center">
		<span class="inline-flex h-[48px] w-[48px] items-center justify-center rounded-full bg-[#FFF5EF] text-[#E44203]">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="12" cy="12" r="10" />
				<line x1="12" y1="8" x2="12" y2="12" />
				<line x1="12" y1="16" x2="12.01" y2="16" />
			</svg>
		</span>
		<div class="max-w-[420px]">
			<h3 class="text-[16px] font-bold text-[#1d2738] mb-[6px]">Nessun preventivo attivo</h3>
			<p class="text-[14px] leading-[1.5] text-[#5C6473]">
				Il carrello è vuoto o il preventivo è scaduto. Torna al passo 1 per calcolare un nuovo prezzo e completare l'ordine.
			</p>
		</div>
		<NuxtLink to="/" class="sf-flow-cta sf-flow-cta--primary">
			Torna al preventivo
			<span class="sf-flow-cta__arrow" aria-hidden="true">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M5 12h14M13 5l7 7-7 7" />
				</svg>
			</span>
		</NuxtLink>
	</div>
</template>
