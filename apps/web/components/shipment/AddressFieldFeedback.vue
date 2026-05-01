<script setup>
/**
 * Pattern feedback campo indirizzo:
 * - mostra errore di validazione (con role="alert" + aria-describedby tramite errorId)
 * - mostra "chip assist" (suggerimento auto-fill: CAP, città, provincia)
 *
 * Estratto da AddressFormFields.vue per evitare 8 ripetizioni identiche del markup.
 * Markup output identico al precedente — zero cambiamenti CSS/visuali.
 */
const props = defineProps({
	field: { type: String, required: true },
	typeKey: { type: String, required: true, validator: (v) => ['origin', 'dest'].includes(v) },
});

// InjectionKey tipato (Ondata 6 — sostituisce string-key 'shipmentFormHandlers').
const handlers = inject(shipmentFormHandlersKey);
if (!handlers) throw new Error('AddressFieldFeedback: shipmentFormHandlersKey non iniettata');
const { getFieldError, fieldErrorText, getFieldAssist, applyFieldAssist } = handlers;

const error = computed(() => getFieldError(props.typeKey, props.field));
const assist = computed(() => getFieldAssist(props.typeKey, props.field));
const visible = computed(() => Boolean(error.value || assist.value));
const errorId = computed(() => `${props.typeKey === 'origin' ? '' : 'dest_'}${props.field}_error`);
// applyFieldAssist accetta l'oggetto FieldAssist completo (non typeKey/field).
const onApply = () => assist.value && applyFieldAssist(assist.value);
</script>

<template>
	<div v-if="visible" class="address-form-field__feedback">
		<p
			v-if="error"
			:id="errorId"
			role="alert"
			class="field-gentle-error">
			{{ fieldErrorText(typeKey, field) }}
		</p>
		<button
			v-if="assist"
			type="button"
			class="field-assist-chip"
			@click="onApply">
			{{ assist.label }}
		</button>
	</div>
</template>
