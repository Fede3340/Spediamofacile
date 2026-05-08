<script setup>
/**
 * ServiceConfigPanel — pannello inline di configurazione servizio
 * (contrassegno + assicurazione). Estratto da StepServicesGrid.vue
 * senza modifiche logiche o stilistiche. Gli stili .service-panel*
 * sono definiti nel parent (StepServicesGrid.vue).
 */
const props = defineProps({
	service: { type: Object, required: true },
	serviceIndex: { type: Number, required: true },
	serviceData: { type: Object, required: true },
	serviceCardErrors: { type: Object, required: true },
	updateContrassegnoField: { type: Function, required: true },
	updateAssicurazioneValue: { type: Function, required: true },
	clearContrassegnoError: { type: Function, required: true },
	clearAssicurazioneError: { type: Function, required: true },
	contrassegnoIncassoOptions: { type: Array, required: true },
	contrassegnoRimborsoOptions: { type: Array, required: true },
	requiresContrassegnoDettaglio: { type: Boolean, default: false },
	insurancePackages: { type: Array, required: true },
	normalizeCurrencyInput: { type: Function, required: true },
	removeLocked: { type: Boolean, default: false },
	activateLocked: { type: Boolean, default: false },
});

const emit = defineEmits(['remove', 'activate']);

const showContrassegnoRimborso = computed(() => props.serviceData?.contrassegno?.modalita_incasso === 'assegno');

const contrassegnoValue = (field) => props.serviceData?.contrassegno?.[field] || '';
const assicurazioneValue = (index) => props.serviceData?.assicurazione?.[index] || '';

const updateContrassegnoInput = (field, value, errorKey) => {
	props.updateContrassegnoField(field, value);
	props.clearContrassegnoError(errorKey);
};

const updateContrassegnoCurrency = (field, rawValue, errorKey) => {
	updateContrassegnoInput(field, props.normalizeCurrencyInput(rawValue), errorKey);
};

const selectContrassegnoIncasso = (value) => {
	updateContrassegnoInput('modalita_incasso', value, 'contrassegnoIncasso');
	if (value === 'assegno') return;
	props.updateContrassegnoField('modalita_rimborso', '');
	props.updateContrassegnoField('dettaglio_rimborso', '');
	props.clearContrassegnoError('contrassegnoRimborso');
	props.clearContrassegnoError('contrassegnoDettaglio');
};

const updateAssicurazioneCurrency = (index, rawValue) => {
	props.updateAssicurazioneValue(index, props.normalizeCurrencyInput(rawValue));
	props.clearAssicurazioneError(index);
};
</script>

<template>
	<div class="service-panel">
		<div class="service-panel__divider"/>

		<div v-if="service.key === 'contrassegno'" class="service-panel__content service-panel__content--contrassegno">
			<div class="service-panel__grid service-panel__grid--two service-panel__grid--contrassegno">
				<div class="service-panel__field">
					<label :for="`contrassegno-importo-${serviceIndex}`" class="service-panel__label">Importo da incassare</label>
					<div class="service-panel__input-wrap">
						<input
							:id="`contrassegno-importo-${serviceIndex}`"
							:value="contrassegnoValue('importo')"
							type="text"
							inputmode="decimal"
							autocomplete="off"
							class="service-panel__input"
							placeholder="0,00"
							@input="updateContrassegnoCurrency('importo', $event.target.value, 'contrassegnoImporto')" >
						<span class="service-panel__suffix">&euro;</span>
					</div>
					<p v-if="serviceCardErrors.contrassegnoImporto" class="service-panel__error">{{ serviceCardErrors.contrassegnoImporto }}</p>
				</div>

				<div v-if="requiresContrassegnoDettaglio" class="service-panel__field">
					<label :for="`contrassegno-iban-${serviceIndex}`" class="service-panel__label">IBAN rimborso</label>
					<input
						:id="`contrassegno-iban-${serviceIndex}`"
						:value="contrassegnoValue('dettaglio_rimborso')"
						type="text"
						class="service-panel__input"
						placeholder="IT60X054281110..."
						@input="updateContrassegnoInput('dettaglio_rimborso', $event.target.value, 'contrassegnoDettaglio')" >
					<p v-if="serviceCardErrors.contrassegnoDettaglio" class="service-panel__error">{{ serviceCardErrors.contrassegnoDettaglio }}</p>
					<p class="service-panel__meta">Conto su cui accreditiamo l'importo incassato se scegli rimborso tramite bonifico.</p>
				</div>
			</div>

			<div class="service-panel__contrassegno-groups">
				<div class="service-panel__field service-panel__field--choice">
					<label class="service-panel__label">Tipo incasso</label>
					<div class="service-panel__choice-shell service-panel__choice-shell--contrassegno">
						<div
							class="sf-shared-segment-strip sf-shared-segment-strip--compact sf-shared-segment-strip--two service-panel__contrassegno-strip"
							role="group"
							aria-label="Modalita incasso contrassegno">
							<button
								v-for="option in contrassegnoIncassoOptions"
								:key="option.value"
								type="button"
								class="sf-shared-segment sf-shared-segment--compact"
								:class="{ 'sf-shared-segment--active': serviceData.contrassegno.modalita_incasso === option.value }"
								@click="selectContrassegnoIncasso(option.value)">
								{{ option.label }}
							</button>
						</div>
						<p class="service-panel__meta">Contanti o assegno consegnati al corriere al momento del ritiro.</p>
					</div>
					<p v-if="serviceCardErrors.contrassegnoIncasso" class="service-panel__error">{{ serviceCardErrors.contrassegnoIncasso }}</p>
				</div>

				<div
					v-if="showContrassegnoRimborso"
					class="service-panel__field service-panel__field--choice">
					<label class="service-panel__label">Tipo rimborso</label>
					<div class="service-panel__choice-shell service-panel__choice-shell--contrassegno">
						<div
							class="sf-shared-segment-strip sf-shared-segment-strip--compact sf-shared-segment-strip--three service-panel__contrassegno-strip"
							role="group"
							aria-label="Modalita accredito contrassegno">
							<button
								v-for="option in contrassegnoRimborsoOptions"
								:key="option.value"
								type="button"
								class="sf-shared-segment sf-shared-segment--compact"
								:class="{ 'sf-shared-segment--active': serviceData.contrassegno.modalita_rimborso === option.value }"
								@click="updateContrassegnoInput('modalita_rimborso', option.value, 'contrassegnoRimborso')">
								{{ option.label }}
							</button>
						</div>
						<p class="service-panel__meta">Scegli come vuoi ricevere l'accredito del contrassegno incassato.</p>
					</div>
					<p v-if="serviceCardErrors.contrassegnoRimborso" class="service-panel__error">{{ serviceCardErrors.contrassegnoRimborso }}</p>
				</div>
			</div>

			<p class="service-panel__note">
				Questi campi riguardano l'accredito del contrassegno incassato alla consegna. Per eventuali rimborsi da pacco danneggiato
				apri invece una richiesta dal <NuxtLink to="/account/assistenza" class="service-panel__note-link">centro assistenza</NuxtLink>.
			</p>
		</div>

		<div v-else-if="service.key === 'assicurazione'" class="service-panel__content">
			<div class="service-panel__grid service-panel__grid--two">
				<div
					v-for="(pack, indexPopup) in insurancePackages"
					:key="`${service.name}-${indexPopup}`"
					class="service-panel__field">
					<label :for="`assicurazione-${indexPopup}`" class="service-panel__label">
						Valore collo {{ indexPopup + 1 }}
					</label>
					<div class="service-panel__input-wrap">
						<input
							:id="`assicurazione-${indexPopup}`"
							:value="assicurazioneValue(indexPopup)"
							type="text"
							inputmode="decimal"
							autocomplete="off"
							class="service-panel__input"
							placeholder="Valore merce"
							@input="updateAssicurazioneCurrency(indexPopup, $event.target.value)" >
						<span class="service-panel__suffix">&euro;</span>
					</div>
					<p class="service-panel__meta">
						{{ pack.weight || '0' }} kg / {{ pack.first_size || '0' }} x {{ pack.second_size || '0' }} x {{ pack.third_size || '0' }} cm
					</p>
					<p v-if="serviceCardErrors.assicurazione[indexPopup]" class="service-panel__error">{{ serviceCardErrors.assicurazione[indexPopup] }}</p>
				</div>
			</div>
		</div>

		<div class="service-panel__footer">
			<SfButton
				v-if="service.isSelected"
				variant="secondary"
				:disabled="removeLocked"
				@click.stop.prevent="emit('remove', service)">
				Rimuovi
			</SfButton>
			<SfButton
				:disabled="activateLocked"
				@click.stop.prevent="emit('activate', service)">
				Salva e attiva
			</SfButton>
		</div>
	</div>
</template>
