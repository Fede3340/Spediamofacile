<script setup>// Form di login (email + password + link "password dimenticata").
// La logica di handleLogin vive in useAuthOverlay — qui solo presentazione.
defineProps({
    form: { type: Object, required: true },
    isLoading: { type: Boolean, default: false },
    showPassword: { type: Boolean, default: false },
});
const emit = defineEmits();
const INPUT_CLS = 'w-full h-[46px] rounded-[12px] px-[14px] text-[14px] font-medium text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[2.5px] focus:ring-[#095866]/50 placeholder:text-[#aaa] outline-none transition-all duration-200';
const LABEL_CLS = 'text-[#777] text-[11px] uppercase tracking-[0.4px] font-bold block';
const CTA_CLS = 'btn-cta-filled w-full h-[50px] rounded-full text-[14px] flex items-center justify-center gap-[10px] mt-[4px] cursor-pointer active:scale-[0.985] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#E44203]/25 disabled:cursor-wait';
</script>

<template>
  <form
    class="flex flex-col gap-[8px]"
    action="javascript:void(0)"
    method="post"
    @submit.capture.prevent.stop="emit('submit')"
  >
    <div class="flex flex-col gap-[5px]">
      <label :class="LABEL_CLS" for="auth-modal-email">Email</label>
      <input
        id="auth-modal-email"
        v-model="form.email"
        :class="INPUT_CLS"
        type="email"
        autocomplete="username"
        placeholder="nome@email.com"
      />
    </div>

    <div class="flex flex-col gap-[5px]">
      <div class="flex items-center justify-between">
        <label :class="LABEL_CLS" for="auth-modal-password">Password</label>
        <button
          type="button"
          class="border-0 bg-transparent p-0 text-[13px] font-medium text-[#095866] hover:opacity-80 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#095866]/20 cursor-pointer transition-colors"
          @click="emit('enter-forgot')"
        >
          Password dimenticata?
        </button>
      </div>
      <div class="relative">
        <input
          id="auth-modal-password"
          v-model="form.password"
          :class="[INPUT_CLS, 'pr-[44px]']"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="current-password"
          placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;"
        />
        <button
          type="button"
          class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#C0C5CC] hover:text-[#777] cursor-pointer transition-colors bg-transparent border-0 p-0"
          tabindex="-1"
          :aria-label="showPassword ? 'Nascondi password' : 'Mostra password'"
          @click="emit('toggle-password')"
        >
          <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M2,5.27L3.28,4L20,20.72L18.73,22L15.65,18.92C14.5,19.3 13.28,19.5 12,19.5C7,19.5 2.73,16.39 1,12C1.69,10.24 2.79,8.69 4.19,7.46L2,5.27M12,9A3,3 0 0,1 15,12C15,12.35 14.94,12.69 14.83,13L11,9.17C11.31,9.06 11.65,9 12,9M12,4.5C17,4.5 21.27,7.61 23,12C22.18,14.08 20.79,15.88 19,17.19L17.58,15.76C18.94,14.82 20.06,13.54 20.82,12C19.17,8.64 15.76,6.5 12,6.5C10.91,6.5 9.84,6.68 8.84,7.03L7.31,5.5C8.77,4.85 10.36,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C12.69,17.5 13.37,17.43 14,17.29L11.72,15C10.29,14.85 9.15,13.71 9,12.28L5.6,8.87C4.61,9.72 3.78,10.78 3.18,12Z" /></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z" /></svg>
        </button>
      </div>
    </div>

    <button
      type="button"
      :class="CTA_CLS"
      :disabled="isLoading"
      @click="emit('submit')"
    >
      <template v-if="isLoading">
        <svg class="animate-spin w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
      </template>
      <template v-else>
        Accedi
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" /></svg>
      </template>
    </button>
  </form>
</template>
