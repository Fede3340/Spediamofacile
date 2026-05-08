<script setup>// Form di registrazione (radio profilo + dati personali + password + privacy + Turnstile).
// La logica di handleRegister + gating CAPTCHA vive in useAuthOverlay / wrapper — qui solo presentazione.
const props = defineProps({
    form: { type: Object, required: true },
    isLoading: { type: Boolean, default: false },
    showPassword: { type: Boolean, default: false },
    showPasswordConfirm: { type: Boolean, default: false },
    turnstile: { type: Object, required: true },
});
const emit = defineEmits(['submit', 'toggle-password', 'toggle-password-confirm', 'update:form', 'update:turnstileToken']);
const formValue = (field) => props.form?.[field] ?? '';
const updateFormField = (field, value) => {
	emit('update:form', {
		...props.form,
		[field]: value,
	});
};
const updateBooleanField = (field, value) => updateFormField(field, Boolean(value));
const turnstileToken = computed({
	get: () => props.turnstile?.token?.value || '',
	set: (value) => emit('update:turnstileToken', value),
});
const INPUT_CLS = 'w-full h-[46px] rounded-control px-[14px] text-sm font-medium text-brand-text bg-white ring-[1.5px] ring-brand-border focus:ring-[2.5px] focus:ring-[var(--color-brand-primary)]/50 placeholder:text-[#aaa] outline-none transition-all duration-200';
const LABEL_CLS = 'text-[#777] text-[11px] uppercase tracking-[0.4px] font-bold block';
const CTA_CLS = 'btn-cta-filled w-full h-[50px] rounded-full text-sm flex items-center justify-center gap-[10px] mt-[4px] cursor-pointer active:scale-[0.985] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[var(--color-brand-accent)]/25 disabled:cursor-wait';
</script>

<template>
  <form
    class="flex flex-col gap-[14px]"
    action="javascript:void(0)"
    method="post"
    @submit.capture.prevent.stop="emit('submit')"
  >
    <!-- Tipo profilo: radio inline compatti -->
    <fieldset class="flex gap-[18px] p-0 m-0 mt-1 mb-0.5 border-0" aria-label="Tipo profilo">
      <label class="group inline-flex items-center gap-[7px] text-sm font-semibold text-brand-text-secondary select-none cursor-pointer has-[:checked]:text-brand-text">
        <input
          :checked="formValue('user_type') === 'privato'"
          type="radio"
          value="privato"
          class="peer absolute w-px h-px overflow-hidden opacity-0 [clip:rect(0_0_0_0)] focus-visible:outline-2 focus-visible:outline-brand-primary focus-visible:outline-offset-2"
          @change="updateFormField('user_type', 'privato')"
        >
        <span
          class="relative inline-block w-4 h-4 shrink-0 rounded-full border-2 border-brand-border bg-white transition-[border-color,box-shadow] peer-checked:border-brand-primary peer-checked:after:content-[''] peer-checked:after:absolute peer-checked:after:top-1/2 peer-checked:after:left-1/2 peer-checked:after:w-2 peer-checked:after:h-2 peer-checked:after:rounded-full peer-checked:after:bg-brand-primary peer-checked:after:-translate-x-1/2 peer-checked:after:-translate-y-1/2 peer-focus-visible:shadow-[0_0_0_3px_rgba(9,88,102,0.22)]"
          aria-hidden="true"
        />
        <span>Privato</span>
      </label>
      <label class="group inline-flex items-center gap-[7px] text-sm font-semibold text-brand-text-secondary select-none cursor-pointer has-[:checked]:text-brand-text">
        <input
          :checked="formValue('user_type') === 'commerciante'"
          type="radio"
          value="commerciante"
          class="peer absolute w-px h-px overflow-hidden opacity-0 [clip:rect(0_0_0_0)] focus-visible:outline-2 focus-visible:outline-brand-primary focus-visible:outline-offset-2"
          @change="updateFormField('user_type', 'commerciante')"
        >
        <span
          class="relative inline-block w-4 h-4 shrink-0 rounded-full border-2 border-brand-border bg-white transition-[border-color,box-shadow] peer-checked:border-brand-primary peer-checked:after:content-[''] peer-checked:after:absolute peer-checked:after:top-1/2 peer-checked:after:left-1/2 peer-checked:after:w-2 peer-checked:after:h-2 peer-checked:after:rounded-full peer-checked:after:bg-brand-primary peer-checked:after:-translate-x-1/2 peer-checked:after:-translate-y-1/2 peer-focus-visible:shadow-[0_0_0_3px_rgba(9,88,102,0.22)]"
          aria-hidden="true"
        />
        <span>Azienda</span>
      </label>
    </fieldset>

    <!-- Nome / Cognome -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-[8px]">
      <div class="flex flex-col gap-[5px]">
        <label :class="LABEL_CLS" for="auth-reg-name">Nome</label>
        <input id="auth-reg-name" :value="formValue('name')" :class="INPUT_CLS" type="text" autocomplete="given-name" placeholder="Mario" @input="updateFormField('name', $event.target.value)" >
      </div>
      <div class="flex flex-col gap-[5px]">
        <label :class="LABEL_CLS" for="auth-reg-surname">Cognome</label>
        <input id="auth-reg-surname" :value="formValue('surname')" :class="INPUT_CLS" type="text" autocomplete="family-name" placeholder="Rossi" @input="updateFormField('surname', $event.target.value)" >
      </div>
    </div>

    <!-- Email / Conferma email -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-[8px]">
      <div class="flex flex-col gap-[5px]">
        <label :class="LABEL_CLS" for="auth-reg-email">Email</label>
        <input id="auth-reg-email" :value="formValue('email')" :class="INPUT_CLS" type="email" autocomplete="email" placeholder="nome@email.com" @input="updateFormField('email', $event.target.value)" >
      </div>
      <div class="flex flex-col gap-[5px]">
        <label :class="LABEL_CLS" for="auth-reg-email-confirm">Conferma email</label>
        <input id="auth-reg-email-confirm" :value="formValue('email_confirmation')" :class="INPUT_CLS" type="email" autocomplete="email" placeholder="Conferma email" @input="updateFormField('email_confirmation', $event.target.value)" >
      </div>
    </div>

    <!-- Prefisso / Telefono -->
    <div class="grid grid-cols-1 md:grid-cols-[104px_1fr] gap-[8px]">
      <div class="flex flex-col gap-[5px]">
        <label :class="LABEL_CLS" for="auth-reg-prefix">Prefisso</label>
        <select id="auth-reg-prefix" :value="formValue('prefix')" :class="[INPUT_CLS, 'pr-[36px]']" style="appearance: auto" @change="updateFormField('prefix', $event.target.value)">
          <option value="+39">+39 IT</option>
          <option value="+49">+49 DE</option>
        </select>
      </div>
      <div class="flex flex-col gap-[5px]">
        <label :class="LABEL_CLS" for="auth-reg-phone">Telefono</label>
        <input id="auth-reg-phone" :value="formValue('telephone_number')" :class="INPUT_CLS" type="tel" autocomplete="tel" placeholder="Numero di telefono" @input="updateFormField('telephone_number', $event.target.value)" >
      </div>
    </div>

    <!-- Password / Conferma password -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-[8px]">
      <div class="flex flex-col gap-[5px]">
        <label :class="LABEL_CLS" for="auth-reg-password">Password</label>
        <div class="relative">
          <input
            id="auth-reg-password"
            :value="formValue('password')"
            :class="[INPUT_CLS, 'pr-[44px]']"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="new-password"
            placeholder="Min. 8 caratteri"
            @input="updateFormField('password', $event.target.value)"
          >
          <button
            type="button"
            :aria-label="showPassword ? 'Nascondi password' : 'Mostra password'"
            class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#C0C5CC] hover:text-[#777] cursor-pointer transition-colors bg-transparent border-0 p-0"
            tabindex="-1"
            @click="emit('toggle-password')"
          >
            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M2,5.27L3.28,4L20,20.72L18.73,22L15.65,18.92C14.5,19.3 13.28,19.5 12,19.5C7,19.5 2.73,16.39 1,12C1.69,10.24 2.79,8.69 4.19,7.46L2,5.27M12,9A3,3 0 0,1 15,12C15,12.35 14.94,12.69 14.83,13L11,9.17C11.31,9.06 11.65,9 12,9M12,4.5C17,4.5 21.27,7.61 23,12C22.18,14.08 20.79,15.88 19,17.19L17.58,15.76C18.94,14.82 20.06,13.54 20.82,12C19.17,8.64 15.76,6.5 12,6.5C10.91,6.5 9.84,6.68 8.84,7.03L7.31,5.5C8.77,4.85 10.36,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C12.69,17.5 13.37,17.43 14,17.29L11.72,15C10.29,14.85 9.15,13.71 9,12.28L5.6,8.87C4.61,9.72 3.78,10.78 3.18,12Z" /></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z" /></svg>
          </button>
        </div>
      </div>
      <div class="flex flex-col gap-[5px]">
        <label :class="LABEL_CLS" for="auth-reg-password-confirm">Conferma password</label>
        <div class="relative">
          <input
            id="auth-reg-password-confirm"
            :value="formValue('password_confirmation')"
            :class="[INPUT_CLS, 'pr-[44px]']"
            :type="showPasswordConfirm ? 'text' : 'password'"
            autocomplete="new-password"
            placeholder="Ripeti password"
            @input="updateFormField('password_confirmation', $event.target.value)"
          >
          <button
            type="button"
            :aria-label="showPasswordConfirm ? 'Nascondi conferma password' : 'Mostra conferma password'"
            class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#C0C5CC] hover:text-[#777] cursor-pointer transition-colors bg-transparent border-0 p-0"
            tabindex="-1"
            @click="emit('toggle-password-confirm')"
          >
            <svg v-if="showPasswordConfirm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M2,5.27L3.28,4L20,20.72L18.73,22L15.65,18.92C14.5,19.3 13.28,19.5 12,19.5C7,19.5 2.73,16.39 1,12C1.69,10.24 2.79,8.69 4.19,7.46L2,5.27M12,9A3,3 0 0,1 15,12C15,12.35 14.94,12.69 14.83,13L11,9.17C11.31,9.06 11.65,9 12,9M12,4.5C17,4.5 21.27,7.61 23,12C22.18,14.08 20.79,15.88 19,17.19L17.58,15.76C18.94,14.82 20.06,13.54 20.82,12C19.17,8.64 15.76,6.5 12,6.5C10.91,6.5 9.84,6.68 8.84,7.03L7.31,5.5C8.77,4.85 10.36,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C12.69,17.5 13.37,17.43 14,17.29L11.72,15C10.29,14.85 9.15,13.71 9,12.28L5.6,8.87C4.61,9.72 3.78,10.78 3.18,12Z" /></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z" /></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Password hint -->
    <p class="text-[var(--color-brand-text-muted)] text-[10px] -mt-[2px] font-normal">Min. 8 caratteri, maiuscola, minuscola e numero</p>

    <label class="flex items-start gap-[9px] rounded-control bg-white/70 px-[11px] py-[10px] ring-[1px] ring-brand-border">
      <input
        :checked="Boolean(form.privacy_accepted)"
        type="checkbox"
        class="mt-[1px] h-[16px] w-[16px] shrink-0 accent-[var(--color-brand-primary)]"
        @change="updateBooleanField('privacy_accepted', $event.target.checked)"
      >
      <span class="text-[11px] leading-[1.55] text-[#667085]">
        Accetto la
        <NuxtLink to="/privacy-policy" class="font-semibold text-[var(--color-brand-primary)] underline underline-offset-2 hover:text-[#0a7489]">
          Privacy Policy
        </NuxtLink>
        e i
        <NuxtLink to="/termini-e-condizioni" class="font-semibold text-[var(--color-brand-primary)] underline underline-offset-2 hover:text-[#0a7489]">
          Termini e Condizioni
        </NuxtLink>.
      </span>
    </label>

    <div class="flex justify-center mt-[4px]">
      <NuxtTurnstile
        v-model="turnstileToken"
        @expired="turnstile.onExpire"
        @error="turnstile.onError"
      />
    </div>

    <button
      type="button"
      :class="CTA_CLS"
      :disabled="isLoading || !form.privacy_accepted || !turnstile.isReady.value"
      @click="emit('submit')"
    >
      <template v-if="isLoading">
        <svg class="animate-spin w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
      </template>
      <template v-else>
        Crea account
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" /></svg>
      </template>
    </button>
  </form>
</template>
