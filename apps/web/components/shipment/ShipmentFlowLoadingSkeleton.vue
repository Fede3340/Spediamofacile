<template>
	<div
		class="mt-[18px] md:mt-[20px] rounded-[28px] border border-[#D7E4E7] bg-white/[0.94] p-[18px] sm:p-[24px] shadow-[0_18px_45px_rgba(15,23,42,0.08)]"
		role="status"
		aria-live="polite"
		data-test="shipment-step-initial-loading">
		<div class="flex items-start gap-[12px]">
			<div
				class="mt-[2px] h-[18px] w-[18px] rounded-full border-[2px] border-[#095866]/20 border-t-[#095866] animate-spin shrink-0" />
			<div class="min-w-0">
				<p class="text-[#1d2738] text-[15px] sm:text-[16px] tracking-[-0.1px]" style="font-weight: 700">
					Ripristiniamo la tua spedizione
				</p>
				<p class="mt-[4px] text-[#667085] text-[13px] leading-[1.55]" style="font-weight: 500">
					Carichiamo i dati salvati prima di mostrare gli step, cosi la pagina non cambia struttura durante l'hydration.
				</p>
			</div>
		</div>

		<div class="mt-[18px] space-y-[12px]" aria-hidden="true">
			<div
				v-for="sectionIndex in 3"
				:key="sectionIndex"
				class="rounded-[22px] border border-[#E6ECEF] bg-[#F8FAFB] px-[16px] py-[18px]">
				<div class="flex items-center justify-between gap-[12px]">
					<div class="min-w-0 flex-1 space-y-[9px]">
						<div class="h-[10px] w-[88px] rounded-full bg-[#D8E3E6]" />
						<div class="h-[14px] max-w-[320px] rounded-full bg-[#E3EAED]" />
					</div>
					<div class="h-[18px] w-[66px] rounded-full bg-[#E3EAED] shrink-0" />
				</div>
				<div v-if="sectionIndex === 1" class="mt-[16px] grid gap-[10px] sm:grid-cols-3">
					<div class="h-[54px] rounded-[16px] bg-white" />
					<div class="h-[54px] rounded-[16px] bg-white" />
					<div class="h-[54px] rounded-[16px] bg-white" />
				</div>
			</div>
		</div>
	</div>
</template>
