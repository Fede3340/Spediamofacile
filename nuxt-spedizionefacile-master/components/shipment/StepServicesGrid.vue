<script setup>
const props = defineProps({
	featuredService: { type: Object, default: null },
	regularServices: { type: Array, required: true },
	serviceData: { type: Object, required: true },
	serviceCardErrors: { type: Object, required: true },
	isServiceExpanded: { type: Function, required: true },
	canConfigureService: { type: Function, required: true },
	getServiceConfigureLabel: { type: Function, required: true },
	contrassegnoIncassoOptions: { type: Array, required: true },
	contrassegnoRimborsoOptions: { type: Array, required: true },
	requiresContrassegnoDettaglio: { type: Boolean, default: false },
	insurancePackages: { type: Array, required: true },
	normalizeCurrencyInput: { type: Function, required: true },
	serviceIconFilterIdle: { type: String, required: true },
	serviceIconFilterActive: { type: String, required: true },
});

const emit = defineEmits([
	'toggle-featured-service',
	'toggle-regular-service',
	'handle-service-primary-action',
	'activate-configured-service',
	'remove-configured-service',
]);

const getServiceSupportText = (service) => {
	if (!service) return '';

	const description = String(service.description || '').trim();
	const shortDescription = description.length > 34 ? `${description.slice(0, 31)}...` : description;
	const statusLabel = String(service.statusLabel || '').trim();

	if (description) return shortDescription;
	return statusLabel;
};

const getFeaturedServiceDescription = computed(() => {
	const source = props.featuredService?.isSelected
		? props.featuredService?.statusLabel || props.featuredService?.description || ''
		: props.featuredService?.description || '';
	const text = String(source).trim();
	if (!text) return '';
	if (text.length <= 62) return text;
	return `${text.slice(0, 59)}...`;
});

const INTERACTIVE_SELECTOR = 'button, a, input, textarea, select, label';

const isInteractiveTarget = (target) => target instanceof HTMLElement && Boolean(target.closest(INTERACTIVE_SELECTOR));

const handleFeaturedSurfaceClick = (event) => {
	if (!props.featuredService) return;
	if (isInteractiveTarget(event?.target)) return;
	emit('toggle-featured-service');
};

const handleFeaturedSurfaceKeydown = (event) => {
	if (!['Enter', ' '].includes(event?.key)) return;
	event.preventDefault();
	handleFeaturedSurfaceClick(event);
};

const handleRegularSurfaceClick = (service, event) => {
	if (!service) return;
	if (isInteractiveTarget(event?.target)) return;

	if (!props.canConfigureService(service)) {
		emit('toggle-regular-service', service);
		return;
	}

	if (props.isServiceExpanded(service.key)) return;
	emit('handle-service-primary-action', service);
};

const handleRegularSurfaceKeydown = (service, event) => {
	if (!['Enter', ' '].includes(event?.key)) return;
	event.preventDefault();
	handleRegularSurfaceClick(service, event);
};

const showCollapsedPrimaryAction = (service) => Boolean(service && !props.isServiceExpanded(service.key));

const getCollapsedPrimaryLabel = (service) => {
	if (!service) return '';
	if (props.canConfigureService(service)) {
		return service.isSelected ? 'Modifica' : 'Aggiungi';
	}
	return service.isSelected ? 'Rimuovi' : 'Aggiungi';
};

const handleCollapsedPrimaryAction = (service) => {
	if (!service) return;
	if (props.canConfigureService(service)) {
		emit('handle-service-primary-action', service);
		return;
	}
	emit('toggle-regular-service', service);
};

const getCollapsedPrimaryClass = (service) =>
	service?.isSelected ? 'service-option__cta service-option__cta--neutral' : 'service-option__cta service-option__cta--primary';

const showCollapsedRemoveAction = (service) =>
	Boolean(service?.isSelected && props.canConfigureService(service) && !props.isServiceExpanded(service.key));

const onInlineBeforeEnter = (el) => {
	el.style.height = '0px';
	el.style.opacity = '0';
	el.style.transform = 'translateY(-4px)';
	el.style.overflow = 'hidden';
};

const onInlineEnter = (el, done) => {
	const onTransitionEnd = (event) => {
		if (event.target !== el || event.propertyName !== 'height') return;
		el.removeEventListener('transitionend', onTransitionEnd);
		done();
	};

	el.addEventListener('transitionend', onTransitionEnd);
	requestAnimationFrame(() => {
		el.style.height = `${el.scrollHeight}px`;
		el.style.opacity = '1';
		el.style.transform = 'translateY(0)';
	});
};

const onInlineAfterEnter = (el) => {
	el.style.height = 'auto';
	el.style.overflow = 'visible';
	el.style.opacity = '';
	el.style.transform = '';
};

const onInlineBeforeLeave = (el) => {
	el.style.height = `${el.scrollHeight}px`;
	el.style.opacity = '1';
	el.style.transform = 'translateY(0)';
	el.style.overflow = 'hidden';
};

const onInlineLeave = (el, done) => {
	const onTransitionEnd = (event) => {
		if (event.target !== el || event.propertyName !== 'height') return;
		el.removeEventListener('transitionend', onTransitionEnd);
		done();
	};

	el.addEventListener('transitionend', onTransitionEnd);
	requestAnimationFrame(() => {
		el.style.height = '0px';
		el.style.opacity = '0';
		el.style.transform = 'translateY(-4px)';
	});
};
</script>

<template>
	<div class="flex flex-col gap-[28px]">

		<!-- SECTION 1: Opzioni (featuredService) -->
		<div v-if="featuredService">
			<!-- Section header -->
			<div class="flex items-center gap-[10px] mb-[14px]">
				<div class="w-[32px] h-[32px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
						<polyline points="6 9 6 2 18 2 18 9"/>
						<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
						<rect x="6" y="14" width="12" height="8"/>
					</svg>
				</div>
				<span class="text-[16px] sm:text-[17px] text-[#1d2738] font-[700]">Opzioni</span>
			</div>

			<!-- Featured service toggle card -->
			<button
				type="button"
				class="w-full rounded-[16px] p-[18px] sm:p-[20px] flex items-center gap-[14px] text-left transition-all duration-[350ms] cursor-pointer bg-white"
				:class="featuredService.isSelected
					? 'ring-[2.5px] ring-[#095866] shadow-[0_4px_16px_rgba(9,88,102,0.1)]'
					: 'ring-[1.5px] ring-[#DFE2E7] hover:ring-[2px] hover:ring-[#095866]/50 hover:bg-[#FAFBFC] hover:shadow-[0_4px_16px_rgba(9,88,102,0.06)]'"
				@click="emit('toggle-featured-service')">

				<!-- Icon box -->
				<div
					class="w-[48px] h-[48px] sm:w-[52px] sm:h-[52px] rounded-[14px] flex items-center justify-center shrink-0 transition-colors duration-[350ms]"
					:class="featuredService.isSelected ? 'bg-[#095866]' : 'bg-[#E6E9EE]'">
					<img
						src="/img/quote/second-step/no-label.png"
						alt=""
						class="w-[22px] h-[22px] object-contain transition-all duration-[350ms]"
						:style="{ filter: featuredService.isSelected ? serviceIconFilterActive : serviceIconFilterIdle }" />
				</div>

				<!-- Text content -->
				<div class="flex-1 min-w-0">
					<div class="flex items-center gap-[8px] flex-wrap">
						<span class="text-[15px] sm:text-[16px] text-[#1d2738] tracking-[-0.1px] font-[700]">{{ featuredService.name }}</span>
						<span class="bg-[#E44203] text-white px-[8px] py-[2px] rounded-full text-[10px] font-[700] flex items-center gap-[3px]">
							<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
							</svg>
							Consigliato
						</span>
					</div>
					<p class="text-[13px] text-[#777] mt-[3px] font-[500]">{{ featuredService.currentPriceLabel }}</p>
				</div>

				<!-- Toggle check -->
				<div
					class="w-[26px] h-[26px] rounded-full flex items-center justify-center border-[2.5px] transition-all duration-[350ms] shrink-0"
					:class="featuredService.isSelected
						? 'bg-[#095866] border-[#095866] text-white'
						: 'border-[#C0C5CC] text-transparent bg-[#E6E9EE]'">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
						<polyline points="20 6 9 17 4 12"/>
					</svg>
				</div>
			</button>
		</div>

		<!-- SECTION 2: Servizi aggiuntivi (regularServices) -->
		<div>
			<!-- Section header -->
			<div class="flex items-center gap-[10px] mb-[14px]">
				<div class="w-[32px] h-[32px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
						<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/>
						<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
						<polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
						<line x1="12" y1="22.08" x2="12" y2="12"/>
					</svg>
				</div>
				<span class="text-[16px] sm:text-[17px] text-[#1d2738] font-[700]">Servizi aggiuntivi</span>
			</div>

			<!-- Service cards list -->
			<div class="flex flex-col gap-[10px]">
				<div
					v-for="(service, serviceIndex) in regularServices"
					:key="service.key || serviceIndex"
					class="rounded-[16px] overflow-hidden transition-all duration-[350ms] bg-white"
					:class="service.isSelected
						? 'ring-[2.5px] ring-[#095866] shadow-[0_4px_16px_rgba(9,88,102,0.1)]'
						: 'ring-[1.5px] ring-[#DFE2E7] hover:ring-[2px] hover:ring-[#095866]/50 hover:bg-[#FAFBFC] hover:shadow-[0_4px_16px_rgba(9,88,102,0.06)]'">

					<!-- Clickable row -->
					<div
						class="p-[16px] sm:p-[18px] flex items-center gap-[14px] cursor-pointer"
						role="button"
						tabindex="0"
						@click="handleRegularSurfaceClick(service, $event)"
						@keydown="handleRegularSurfaceKeydown(service, $event)">

						<!-- Icon box -->
						<div
							class="w-[48px] h-[48px] sm:w-[52px] sm:h-[52px] rounded-[14px] flex items-center justify-center shrink-0 transition-colors duration-[350ms]"
							:class="service.isSelected ? 'bg-[#095866]' : 'bg-[#E6E9EE]'">
							<img
								:src="`/img/quote/second-step/${service.img}`"
								alt=""
								class="object-contain transition-all duration-[350ms]"
								:style="{
									width: `${service.width}px`,
									height: `${service.height}px`,
									filter: service.isSelected ? serviceIconFilterActive : serviceIconFilterIdle,
								}" />
						</div>

						<!-- Text -->
						<div class="flex-1 min-w-0">
							<div class="flex items-center gap-[8px] flex-wrap">
								<span class="text-[15px] sm:text-[16px] text-[#1d2738] tracking-[-0.1px] font-[700]">{{ service.name }}</span>
								<!-- "Configurato" badge -->
								<span
									v-if="service.isSelected && canConfigureService(service) && !isServiceExpanded(service.key)"
									class="bg-[#095866]/10 text-[#095866] text-[10px] px-[8px] py-[2px] rounded-full font-[700]">
									Configurato
								</span>
							</div>
							<p v-if="getServiceSupportText(service)" class="text-[13px] text-[#777] mt-[2px] font-[500]">
								{{ getServiceSupportText(service) }}
								<span v-if="service.priceLabel" class="font-[700] text-[#1d2738]"> {{ service.priceLabel }}</span>
							</p>
						</div>

						<!-- Right side -->
						<div class="flex items-center gap-[8px] shrink-0">
							<!-- Configura/Fatto button (only when selected & configurable) -->
							<span
								v-if="service.isSelected && canConfigureService(service)"
								class="h-[34px] px-[14px] rounded-full bg-[#F0F1F4] text-[#1d2738] text-[12px] flex items-center gap-[5px] hover:bg-[#095866] hover:text-white transition-all duration-[350ms] cursor-pointer font-[600]"
								@click="(e) => { e.stopPropagation(); emit('handle-service-primary-action', service) }">
								<svg v-if="isServiceExpanded(service.key)" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
								</svg>
								{{ isServiceExpanded(service.key) ? 'Fatto' : 'Configura' }}
							</span>

							<!-- Toggle check -->
							<div
								class="w-[26px] h-[26px] rounded-full flex items-center justify-center border-[2.5px] transition-all duration-[350ms] shrink-0"
								:class="service.isSelected
									? 'bg-[#095866] border-[#095866] text-white'
									: 'border-[#C0C5CC] text-transparent bg-[#E6E9EE]'">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
							</div>
						</div>
					</div>

					<!-- Inline panel (animated) -->
					<Transition
						@before-enter="onInlineBeforeEnter"
						@enter="onInlineEnter"
						@after-enter="onInlineAfterEnter"
						@before-leave="onInlineBeforeLeave"
						@leave="onInlineLeave">
						<div
							v-if="canConfigureService(service) && isServiceExpanded(service.key)"
							class="px-[16px] sm:px-[18px] pb-[18px] pt-[4px]">

							<!-- Divider -->
							<div class="h-[1px] bg-[#D5D9E0]" />

							<!-- Contrassegno fields -->
							<div v-if="service.key === 'contrassegno'">
								<div class="grid grid-cols-1 sm:grid-cols-2 gap-[10px] mt-[14px]">
									<!-- Importo field -->
									<div>
										<label
											:for="`contrassegno-importo-${serviceIndex}`"
											class="block text-[12px] font-[600] text-[#555] mb-[6px] uppercase tracking-[0.5px]">
											Importo
										</label>
										<div class="relative">
											<input
												:id="`contrassegno-importo-${serviceIndex}`"
												v-model="serviceData.contrassegno.importo"
												type="text"
												inputmode="decimal"
												autocomplete="off"
												class="h-[48px] sm:h-[50px] w-full rounded-[12px] px-[14px] bg-white ring-[1.5px] ring-[#DFE2E7] outline-none focus:ring-[3px] focus:ring-[#095866]/60 text-[#1d2738] text-[14px] transition-all duration-[250ms] placeholder:text-[#b0b5be] pr-[36px]"
												placeholder="0,00"
												@input="
													serviceData.contrassegno.importo = normalizeCurrencyInput($event.target.value);
													serviceCardErrors.contrassegnoImporto = '';
												" />
											<span class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#999] text-[13px] font-[700]">&euro;</span>
										</div>
										<p v-if="serviceCardErrors.contrassegnoImporto" class="text-[12px] text-[#ef4444] mt-[4px] font-[500]">
											{{ serviceCardErrors.contrassegnoImporto }}
										</p>
									</div>

									<!-- IBAN field (conditional) -->
									<div v-if="requiresContrassegnoDettaglio">
										<label
											:for="`contrassegno-iban-${serviceIndex}`"
											class="block text-[12px] font-[600] text-[#555] mb-[6px] uppercase tracking-[0.5px]">
											IBAN rimborso
										</label>
										<input
											:id="`contrassegno-iban-${serviceIndex}`"
											v-model="serviceData.contrassegno.dettaglio_rimborso"
											type="text"
											class="h-[48px] sm:h-[50px] w-full rounded-[12px] px-[14px] bg-white ring-[1.5px] ring-[#DFE2E7] outline-none focus:ring-[3px] focus:ring-[#095866]/60 text-[#1d2738] text-[14px] transition-all duration-[250ms] placeholder:text-[#b0b5be]"
											placeholder="IT60X054281110..."
											@input="serviceCardErrors.contrassegnoDettaglio = ''" />
										<p v-if="serviceCardErrors.contrassegnoDettaglio" class="text-[12px] text-[#ef4444] mt-[4px] font-[500]">
											{{ serviceCardErrors.contrassegnoDettaglio }}
										</p>
									</div>
								</div>

								<!-- Incasso pill toggle -->
								<div class="mt-[14px]">
									<span class="block text-[12px] font-[600] text-[#555] mb-[6px] uppercase tracking-[0.5px]">Incasso</span>
									<div class="flex p-[3px] bg-[#F0F1F4] rounded-full gap-[2px]" role="group" aria-label="Modalita incasso contrassegno">
										<button
											v-for="option in contrassegnoIncassoOptions"
											:key="option.value"
											type="button"
											class="flex-1 h-[38px] rounded-full text-[12px] transition-all cursor-pointer font-[600]"
											:class="serviceData.contrassegno.modalita_incasso === option.value
												? 'bg-[#095866] text-white shadow-[0_1px_4px_rgba(9,88,102,0.2)]'
												: 'text-[#777] hover:text-[#1d2738]'"
											@click="
												serviceData.contrassegno.modalita_incasso = option.value;
												serviceCardErrors.contrassegnoIncasso = '';
											">
											{{ option.label }}
										</button>
									</div>
									<p v-if="serviceCardErrors.contrassegnoIncasso" class="text-[12px] text-[#ef4444] mt-[4px] font-[500]">
										{{ serviceCardErrors.contrassegnoIncasso }}
									</p>
								</div>

								<!-- Rimborso pill toggle -->
								<div class="mt-[14px]">
									<span class="block text-[12px] font-[600] text-[#555] mb-[6px] uppercase tracking-[0.5px]">Rimborso</span>
									<div class="flex p-[3px] bg-[#F0F1F4] rounded-full gap-[2px]" role="group" aria-label="Modalita rimborso contrassegno">
										<button
											v-for="option in contrassegnoRimborsoOptions"
											:key="option.value"
											type="button"
											class="flex-1 h-[38px] rounded-full text-[12px] transition-all cursor-pointer font-[600]"
											:class="serviceData.contrassegno.modalita_rimborso === option.value
												? 'bg-[#095866] text-white shadow-[0_1px_4px_rgba(9,88,102,0.2)]'
												: 'text-[#777] hover:text-[#1d2738]'"
											@click="
												serviceData.contrassegno.modalita_rimborso = option.value;
												serviceCardErrors.contrassegnoRimborso = '';
											">
											{{ option.label }}
										</button>
									</div>
									<p v-if="serviceCardErrors.contrassegnoRimborso" class="text-[12px] text-[#ef4444] mt-[4px] font-[500]">
										{{ serviceCardErrors.contrassegnoRimborso }}
									</p>
								</div>
							</div>

							<!-- Assicurazione fields -->
							<div v-else-if="service.key === 'assicurazione'" class="mt-[14px] flex flex-col gap-[12px]">
								<div
									v-for="(pack, indexPopup) in insurancePackages"
									:key="`${service.name}-${indexPopup}`">
									<div class="flex items-center justify-between mb-[6px]">
										<span class="text-[12px] font-[600] text-[#555] uppercase tracking-[0.5px]">Collo {{ indexPopup + 1 }}</span>
										<span class="text-[12px] text-[#999] font-[500]">
											{{ pack.weight || '0' }} kg &middot; {{ pack.first_size || '0' }}&times;{{ pack.second_size || '0' }}&times;{{ pack.third_size || '0' }} cm
										</span>
									</div>
									<div class="relative">
										<input
											:id="`assicurazione-${indexPopup}`"
											v-model="serviceData.assicurazione[indexPopup]"
											type="text"
											inputmode="decimal"
											autocomplete="off"
											class="h-[48px] sm:h-[50px] w-full rounded-[12px] px-[14px] bg-white ring-[1.5px] ring-[#DFE2E7] outline-none focus:ring-[3px] focus:ring-[#095866]/60 text-[#1d2738] text-[14px] transition-all duration-[250ms] placeholder:text-[#b0b5be] pr-[36px]"
											placeholder="Valore assicurato"
											@input="
												serviceData.assicurazione[indexPopup] = normalizeCurrencyInput($event.target.value);
												serviceCardErrors.assicurazione[indexPopup] = '';
											" />
										<span class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#999] text-[13px] font-[700]">&euro;</span>
									</div>
									<p v-if="serviceCardErrors.assicurazione[indexPopup]" class="text-[12px] text-[#ef4444] mt-[4px] font-[500]">
										{{ serviceCardErrors.assicurazione[indexPopup] }}
									</p>
								</div>
							</div>

							<!-- Actions -->
							<div class="flex justify-end gap-[8px] mt-[16px]">
								<!-- Remove button -->
								<button
									v-if="service.isSelected"
									type="button"
									class="h-[38px] px-[18px] rounded-full bg-white ring-[1.5px] ring-[#DFE2E7] text-[#777] text-[13px] font-[600] cursor-pointer hover:ring-[#ef4444] hover:text-[#ef4444] transition-all"
									@click.stop.prevent="emit('remove-configured-service', service)">
									Rimuovi
								</button>
								<!-- Save button -->
								<button
									type="button"
									class="h-[38px] px-[20px] rounded-full text-white text-[13px] font-[700] cursor-pointer"
									style="background: linear-gradient(135deg, #095866, #0a7489); box-shadow: 0 2px 10px rgba(9,88,102,0.2);"
									@click.stop.prevent="emit('activate-configured-service', service)">
									Salva e attiva
								</button>
							</div>
						</div>
					</Transition>
				</div>
			</div>
		</div>

	</div>
</template>
