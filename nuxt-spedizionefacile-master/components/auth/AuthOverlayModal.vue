<script setup lang="ts">
import { DialogDescription, DialogTitle } from 'reka-ui'

const {
  loginForm, registerForm, isLoading, resendLoading,
  authError, authSuccess, socialError, verificationMode,
  verificationLoading, verificationCode, verificationError,
  verificationSuccess, showLoginPassword, showRegisterPassword,
  showRegisterPasswordConfirm, socialErrorTone, isBusy,
  isOpen, selectedTab, authProviders,
  clearFeedback, resetVerificationMode, closeModal,
  startSocialAuth, handleLogin, handleRegister,
  handleVerificationInput, handleVerificationKeydown,
  verifyCode, resendVerificationEmail,
} = useAuthOverlay()

// --- Forgot password state (local to this component) ---
const forgotMode = ref(false)
const forgotEmail = ref('')
const forgotLoading = ref(false)
const forgotError = ref('')
const forgotSuccess = ref('')

const sanctum = useSanctumClient()

const enterForgotMode = () => {
  forgotMode.value = true
  forgotEmail.value = loginForm.value.email || ''
  forgotError.value = ''
  forgotSuccess.value = ''
  clearFeedback()
}

const exitForgotMode = () => {
  forgotMode.value = false
  forgotError.value = ''
  forgotSuccess.value = ''
}

const handleForgotPassword = async () => {
  forgotError.value = ''
  forgotSuccess.value = ''
  if (!forgotEmail.value) {
    forgotError.value = 'Inserisci la tua email per continuare.'
    return
  }
  forgotLoading.value = true
  try {
    const response = await sanctum<{ message?: string }>('/api/forgot-password', {
      method: 'POST',
      body: { email: forgotEmail.value },
    })
    forgotSuccess.value = response?.message || 'Link di recupero inviato. Controlla la tua email.'
  } catch (error: any) {
    const data = error?.response?._data || error?.data || {}
    forgotError.value = data?.message || "Errore durante l'invio. Riprova."
  } finally {
    forgotLoading.value = false
  }
}

// Reset forgot mode when switching tabs
watch(selectedTab, () => {
  if (forgotMode.value) exitForgotMode()
})

// Reset forgot mode when modal closes
watch(isOpen, (open) => {
  if (!open) exitForgotMode()
})

// --- Design tokens ---
const modalUi = {
  overlay: 'bg-black/45 backdrop-blur-[10px]',
  content: 'w-[calc(100vw-1rem)] max-w-[420px] max-h-[90vh] overflow-hidden rounded-[20px] border-0 shadow-[0_24px_80px_rgba(0,0,0,0.2),0_0_0_1px_rgba(9,88,102,0.06)]',
  body: 'p-0 overflow-y-auto overscroll-contain [scrollbar-width:none] [-webkit-overflow-scrolling:touch]',
}

const INPUT_CLS = 'w-full h-[48px] sm:h-[50px] rounded-[12px] px-[16px] text-[15px] font-semibold text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 placeholder:text-[#999] outline-none transition-all duration-200'
const LABEL_CLS = 'text-[#777] text-[11px] uppercase tracking-[0.4px] font-bold block'
const CTA_CLS = 'w-full h-[52px] rounded-full text-white text-[15px] font-bold flex items-center justify-center gap-[8px] mt-[4px] cursor-pointer transition-all duration-[350ms] hover:-translate-y-[2px] active:scale-[0.985] disabled:opacity-70 disabled:cursor-wait disabled:hover:translate-y-0'
const CTA_STYLE = 'background: linear-gradient(135deg, #E44203 0%, #c73600 100%); box-shadow: 0 6px 24px rgba(228,66,3,0.22)'
const PILL_ACTIVE = 'bg-[#095866] text-white font-bold shadow-[0_2px_8px_rgba(9,88,102,0.2)]'
const PILL_INACTIVE = 'text-[#777] font-medium hover:text-[#1d2738] hover:bg-white/50'
</script>

<template>
  <UModal
    v-model:open="isOpen"
    :dismissible="!isBusy"
    :close="false"
    :ui="modalUi"
  >
    <template #body>
      <div class="relative flex flex-col">
        <!-- SR-only title for accessibility -->
        <div class="sr-only">
          <DialogTitle>Accedi o registrati</DialogTitle>
          <DialogDescription>
            Accedi o crea un account per continuare con spedizioni, salvataggi e checkout.
          </DialogDescription>
        </div>

        <!-- ── Teal accent bar ── -->
        <div
          class="w-full h-[4px] shrink-0"
          style="background: linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)"
          aria-hidden="true"
        />

        <!-- ── Content area ── -->
        <div
          class="relative px-[24px] sm:px-[28px] pt-[24px] pb-[28px] flex flex-col gap-[16px]"
          style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)"
        >
          <!-- Close button -->
          <button
            type="button"
            class="absolute top-[14px] right-[14px] z-10 w-[34px] h-[34px] rounded-full bg-[#E6E9EE] hover:bg-[#D5D9E0] flex items-center justify-center cursor-pointer transition-all duration-[350ms]"
            aria-label="Chiudi"
            @click="closeModal"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2.5" stroke-linecap="round">
              <path d="M18 6L6 18M6 6l12 12" />
            </svg>
          </button>

          <!-- Heading -->
          <h2
            class="text-[22px] font-extrabold leading-[1.2] text-[#1D2738] m-0 pr-[42px]"
            style="font-family: var(--font-montserrat, 'Montserrat', sans-serif)"
          >
            <template v-if="forgotMode">Recupera password</template>
            <template v-else-if="verificationMode">Verifica account</template>
            <template v-else>{{ selectedTab === 'login' ? 'Bentornato' : 'Benvenuto' }}</template>
          </h2>

          <!-- ── Pill tabs (hidden during forgot/verification) ── -->
          <div
            v-if="!forgotMode && !verificationMode"
            class="flex gap-[3px] bg-[#E6E9EE] rounded-full p-[3px] mr-[42px]"
            style="box-shadow: inset 0 1px 2px rgba(0,0,0,0.04)"
          >
            <button
              type="button"
              :class="['flex-1 h-[42px] rounded-full text-[14px] cursor-pointer transition-all duration-[350ms]', selectedTab === 'login' ? PILL_ACTIVE : PILL_INACTIVE]"
              @click="selectedTab = 'login'; resetVerificationMode(); clearFeedback()"
            >
              Accedi
            </button>
            <button
              type="button"
              :class="['flex-1 h-[42px] rounded-full text-[14px] cursor-pointer transition-all duration-[350ms]', selectedTab === 'register' ? PILL_ACTIVE : PILL_INACTIVE]"
              @click="selectedTab = 'register'; resetVerificationMode(); clearFeedback()"
            >
              Registrati
            </button>
          </div>

          <!-- ── Feedback messages ── -->
          <div
            v-if="socialError"
            :class="[
              'flex items-center gap-[8px] rounded-[12px] px-[14px] py-[11px] overflow-hidden',
              socialErrorTone === 'muted'
                ? 'bg-[#f7fafb] ring-[1px] ring-[#dfe8ec]'
                : 'bg-[#FFF5F2] ring-[1px] ring-[#E44203]/10',
            ]"
          >
            <svg v-if="socialErrorTone !== 'muted'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
            <span :class="socialErrorTone === 'muted' ? 'text-[#666] text-[13px] font-medium' : 'text-[#E44203] text-[13px] font-semibold'">{{ socialError }}</span>
          </div>

          <div v-if="authError" class="flex items-center gap-[8px] bg-[#FFF5F2] ring-[1px] ring-[#E44203]/10 rounded-[12px] px-[14px] py-[11px] overflow-hidden">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
            <span class="text-[#E44203] text-[13px] font-semibold">{{ authError }}</span>
          </div>

          <div v-if="authSuccess" class="flex items-center gap-[8px] bg-[#f0fdf4] ring-[1px] ring-[#166534]/10 rounded-[12px] px-[14px] py-[11px] overflow-hidden">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>
            <span class="text-[#166534] text-[13px] font-semibold">{{ authSuccess }}</span>
          </div>

          <!-- ================= FORGOT PASSWORD MODE ================= -->
          <div v-if="forgotMode" class="flex flex-col gap-[16px]">
            <p class="text-[14px] leading-[1.5] text-[#666] m-0">
              Inserisci la tua email e ti invieremo un link per reimpostare la password.
            </p>

            <div v-if="forgotError" class="flex items-center gap-[8px] bg-[#FFF5F2] ring-[1px] ring-[#E44203]/10 rounded-[12px] px-[14px] py-[11px]">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
              <span class="text-[#E44203] text-[13px] font-semibold">{{ forgotError }}</span>
            </div>
            <div v-if="forgotSuccess" class="flex items-center gap-[8px] bg-[#f0fdf4] ring-[1px] ring-[#166534]/10 rounded-[12px] px-[14px] py-[11px]">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>
              <span class="text-[#166534] text-[13px] font-semibold">{{ forgotSuccess }}</span>
            </div>

            <div class="flex flex-col gap-[5px]">
              <label :class="LABEL_CLS" for="auth-forgot-email">Email</label>
              <input
                id="auth-forgot-email"
                v-model="forgotEmail"
                :class="INPUT_CLS"
                type="email"
                autocomplete="email"
                placeholder="nome@email.com"
              />
            </div>

            <button
              type="button"
              :class="CTA_CLS"
              :style="CTA_STYLE"
              :disabled="forgotLoading"
              @click="handleForgotPassword"
            >
              <template v-if="forgotLoading">
                <svg class="animate-spin w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              </template>
              <template v-else>Invia link di recupero</template>
            </button>

            <button
              type="button"
              class="inline-flex items-center justify-center gap-[8px] text-[14px] font-medium text-[#095866] hover:text-[#0a7489] hover:underline cursor-pointer transition-colors bg-transparent border-0 p-0"
              @click="exitForgotMode"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7" /></svg>
              Torna al login
            </button>
          </div>

          <!-- ================= VERIFICATION MODE ================= -->
          <div v-else-if="verificationMode" class="flex flex-col gap-[16px]">
            <p class="text-[14px] leading-[1.5] text-[#666] m-0">
              Inserisci il codice a 6 cifre inviato a <strong class="text-[#1d2738]">{{ loginForm.email }}</strong>.
            </p>

            <div class="flex items-center justify-center gap-[8px]">
              <input
                v-for="(digit, index) in verificationCode"
                :key="index"
                :value="digit"
                :data-verification-index="index"
                type="text"
                inputmode="numeric"
                maxlength="1"
                class="w-[44px] h-[48px] rounded-[12px] bg-[#F8F9FB] text-center text-[16px] font-bold ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 focus:bg-white outline-none transition-all duration-200"
                @input="handleVerificationInput(index, $event)"
                @keydown="handleVerificationKeydown(index, $event)"
              />
            </div>

            <div v-if="verificationError" class="flex items-center gap-[8px] bg-[#FFF5F2] ring-[1px] ring-[#E44203]/10 rounded-[12px] px-[14px] py-[11px]">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
              <span class="text-[#E44203] text-[13px] font-semibold">{{ verificationError }}</span>
            </div>
            <div v-if="verificationSuccess" class="flex items-center gap-[8px] bg-[#f0fdf4] ring-[1px] ring-[#166534]/10 rounded-[12px] px-[14px] py-[11px]">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>
              <span class="text-[#166534] text-[13px] font-semibold">{{ verificationSuccess }}</span>
            </div>

            <button
              type="button"
              :class="CTA_CLS"
              :style="CTA_STYLE"
              :disabled="verificationLoading"
              @click="verifyCode"
            >
              <template v-if="verificationLoading">
                <svg class="animate-spin w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              </template>
              <template v-else>Verifica e continua</template>
            </button>

            <div class="flex items-center justify-between gap-[12px] text-[13px]">
              <button
                type="button"
                class="text-[#095866] font-medium hover:underline cursor-pointer bg-transparent border-0 p-0 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="resendLoading"
                @click="resendVerificationEmail"
              >
                {{ resendLoading ? 'Invio...' : 'Invia nuovo codice' }}
              </button>
              <button
                type="button"
                class="text-[#888] font-medium hover:text-[#555] hover:underline cursor-pointer bg-transparent border-0 p-0 transition-colors"
                @click="resetVerificationMode()"
              >
                Torna indietro
              </button>
            </div>
          </div>

          <!-- ================= LOGIN FORM ================= -->
          <form
            v-else-if="selectedTab === 'login'"
            class="flex flex-col gap-[12px]"
            action="javascript:void(0)"
            method="post"
            @submit.capture.prevent.stop="handleLogin"
          >
            <div class="flex flex-col gap-[5px]">
              <label :class="LABEL_CLS" for="auth-modal-email">Email</label>
              <input
                id="auth-modal-email"
                v-model="loginForm.email"
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
                  class="text-[#095866] text-[13px] font-medium cursor-pointer hover:underline bg-transparent border-0 p-0 transition-colors"
                  @click="enterForgotMode"
                >
                  Password dimenticata?
                </button>
              </div>
              <div class="relative">
                <input
                  id="auth-modal-password"
                  v-model="loginForm.password"
                  :class="[INPUT_CLS, 'pr-[44px]']"
                  :type="showLoginPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;"
                />
                <button
                  type="button"
                  class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#C0C5CC] hover:text-[#777] cursor-pointer transition-colors bg-transparent border-0 p-0"
                  tabindex="-1"
                  :aria-label="showLoginPassword ? 'Nascondi password' : 'Mostra password'"
                  @click="showLoginPassword = !showLoginPassword"
                >
                  <svg v-if="showLoginPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M2,5.27L3.28,4L20,20.72L18.73,22L15.65,18.92C14.5,19.3 13.28,19.5 12,19.5C7,19.5 2.73,16.39 1,12C1.69,10.24 2.79,8.69 4.19,7.46L2,5.27M12,9A3,3 0 0,1 15,12C15,12.35 14.94,12.69 14.83,13L11,9.17C11.31,9.06 11.65,9 12,9M12,4.5C17,4.5 21.27,7.61 23,12C22.18,14.08 20.79,15.88 19,17.19L17.58,15.76C18.94,14.82 20.06,13.54 20.82,12C19.17,8.64 15.76,6.5 12,6.5C10.91,6.5 9.84,6.68 8.84,7.03L7.31,5.5C8.77,4.85 10.36,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C12.69,17.5 13.37,17.43 14,17.29L11.72,15C10.29,14.85 9.15,13.71 9,12.28L5.6,8.87C4.61,9.72 3.78,10.78 3.18,12Z" /></svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z" /></svg>
                </button>
              </div>
            </div>

            <button
              type="button"
              :class="CTA_CLS"
              :style="CTA_STYLE"
              :disabled="isLoading"
              @click="handleLogin"
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

          <!-- ================= REGISTER FORM ================= -->
          <form
            v-else
            class="flex flex-col gap-[12px]"
            action="javascript:void(0)"
            method="post"
            @submit.capture.prevent.stop="handleRegister"
          >
            <!-- User type segmented -->
            <div
              class="flex gap-[3px] bg-[#E6E9EE] rounded-full p-[3px] max-w-full sm:max-w-[280px]"
              style="box-shadow: inset 0 1px 2px rgba(0,0,0,0.04)"
            >
              <button
                type="button"
                :class="['flex-1 h-[40px] rounded-full text-[14px] cursor-pointer transition-all duration-[350ms]', registerForm.user_type === 'privato' ? PILL_ACTIVE : PILL_INACTIVE]"
                @click="registerForm.user_type = 'privato'"
              >
                Privato
              </button>
              <button
                type="button"
                :class="['flex-1 h-[40px] rounded-full text-[14px] cursor-pointer transition-all duration-[350ms]', registerForm.user_type === 'commerciante' ? PILL_ACTIVE : PILL_INACTIVE]"
                @click="registerForm.user_type = 'commerciante'"
              >
                Azienda
              </button>
            </div>

            <!-- Nome / Cognome -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-[10px]">
              <div class="flex flex-col gap-[5px]">
                <label :class="LABEL_CLS">Nome</label>
                <input v-model="registerForm.name" :class="INPUT_CLS" type="text" autocomplete="given-name" placeholder="Mario" />
              </div>
              <div class="flex flex-col gap-[5px]">
                <label :class="LABEL_CLS">Cognome</label>
                <input v-model="registerForm.surname" :class="INPUT_CLS" type="text" autocomplete="family-name" placeholder="Rossi" />
              </div>
            </div>

            <!-- Email / Conferma email -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-[10px]">
              <div class="flex flex-col gap-[5px]">
                <label :class="LABEL_CLS">Email</label>
                <input v-model="registerForm.email" :class="INPUT_CLS" type="email" autocomplete="email" placeholder="nome@email.com" />
              </div>
              <div class="flex flex-col gap-[5px]">
                <label :class="LABEL_CLS">Conferma email</label>
                <input v-model="registerForm.email_confirmation" :class="INPUT_CLS" type="email" autocomplete="email" placeholder="Conferma email" />
              </div>
            </div>

            <!-- Prefisso / Telefono -->
            <div class="grid grid-cols-1 md:grid-cols-[108px_1fr] gap-[10px]">
              <div class="flex flex-col gap-[5px]">
                <label :class="LABEL_CLS">Prefisso</label>
                <select v-model="registerForm.prefix" :class="[INPUT_CLS, 'pr-[36px]']" style="appearance: auto">
                  <option value="+39">+39 IT</option>
                  <option value="+49">+49 DE</option>
                </select>
              </div>
              <div class="flex flex-col gap-[5px]">
                <label :class="LABEL_CLS">Telefono</label>
                <input v-model="registerForm.telephone_number" :class="INPUT_CLS" type="tel" autocomplete="tel" placeholder="Numero di telefono" />
              </div>
            </div>

            <!-- Password / Conferma password -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-[10px]">
              <div class="flex flex-col gap-[5px]">
                <label :class="LABEL_CLS">Password</label>
                <div class="relative">
                  <input
                    v-model="registerForm.password"
                    :class="[INPUT_CLS, 'pr-[44px]']"
                    :type="showRegisterPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    placeholder="Min. 8 caratteri"
                  />
                  <button type="button" class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#C0C5CC] hover:text-[#777] cursor-pointer transition-colors bg-transparent border-0 p-0" tabindex="-1" @click="showRegisterPassword = !showRegisterPassword">
                    <svg v-if="showRegisterPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M2,5.27L3.28,4L20,20.72L18.73,22L15.65,18.92C14.5,19.3 13.28,19.5 12,19.5C7,19.5 2.73,16.39 1,12C1.69,10.24 2.79,8.69 4.19,7.46L2,5.27M12,9A3,3 0 0,1 15,12C15,12.35 14.94,12.69 14.83,13L11,9.17C11.31,9.06 11.65,9 12,9M12,4.5C17,4.5 21.27,7.61 23,12C22.18,14.08 20.79,15.88 19,17.19L17.58,15.76C18.94,14.82 20.06,13.54 20.82,12C19.17,8.64 15.76,6.5 12,6.5C10.91,6.5 9.84,6.68 8.84,7.03L7.31,5.5C8.77,4.85 10.36,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C12.69,17.5 13.37,17.43 14,17.29L11.72,15C10.29,14.85 9.15,13.71 9,12.28L5.6,8.87C4.61,9.72 3.78,10.78 3.18,12Z" /></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z" /></svg>
                  </button>
                </div>
              </div>
              <div class="flex flex-col gap-[5px]">
                <label :class="LABEL_CLS">Conferma password</label>
                <div class="relative">
                  <input
                    v-model="registerForm.password_confirmation"
                    :class="[INPUT_CLS, 'pr-[44px]']"
                    :type="showRegisterPasswordConfirm ? 'text' : 'password'"
                    autocomplete="new-password"
                    placeholder="Ripeti password"
                  />
                  <button type="button" class="absolute right-[14px] top-1/2 -translate-y-1/2 text-[#C0C5CC] hover:text-[#777] cursor-pointer transition-colors bg-transparent border-0 p-0" tabindex="-1" @click="showRegisterPasswordConfirm = !showRegisterPasswordConfirm">
                    <svg v-if="showRegisterPasswordConfirm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M2,5.27L3.28,4L20,20.72L18.73,22L15.65,18.92C14.5,19.3 13.28,19.5 12,19.5C7,19.5 2.73,16.39 1,12C1.69,10.24 2.79,8.69 4.19,7.46L2,5.27M12,9A3,3 0 0,1 15,12C15,12.35 14.94,12.69 14.83,13L11,9.17C11.31,9.06 11.65,9 12,9M12,4.5C17,4.5 21.27,7.61 23,12C22.18,14.08 20.79,15.88 19,17.19L17.58,15.76C18.94,14.82 20.06,13.54 20.82,12C19.17,8.64 15.76,6.5 12,6.5C10.91,6.5 9.84,6.68 8.84,7.03L7.31,5.5C8.77,4.85 10.36,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C12.69,17.5 13.37,17.43 14,17.29L11.72,15C10.29,14.85 9.15,13.71 9,12.28L5.6,8.87C4.61,9.72 3.78,10.78 3.18,12Z" /></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><path d="M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z" /></svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Password hint -->
            <p class="text-[#999] text-[11px] -mt-[4px] font-normal">Min. 8 caratteri, maiuscola, minuscola e numero</p>

            <button
              type="button"
              :class="CTA_CLS"
              :style="CTA_STYLE"
              :disabled="isLoading"
              @click="handleRegister"
            >
              <template v-if="isLoading">
                <svg class="animate-spin w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              </template>
              <template v-else>
                Crea account
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" /></svg>
              </template>
            </button>

            <!-- Terms -->
            <p class="text-center text-[#999] text-[11px] mt-[2px] leading-[1.5] font-normal">
              Registrandoti accetti i
              <NuxtLink to="/termini-condizioni" class="text-[#095866] underline cursor-pointer hover:text-[#0a7489]">Termini di Servizio</NuxtLink>
              e la
              <NuxtLink to="/privacy-policy" class="text-[#095866] underline cursor-pointer hover:text-[#0a7489]">Privacy Policy</NuxtLink>
            </p>
          </form>

          <!-- ================= SOCIAL AUTH ================= -->
          <template v-if="!forgotMode && !verificationMode">
            <!-- Divider -->
            <div class="flex items-center gap-[12px] mt-[2px]" aria-hidden="true">
              <div class="flex-1 h-[1px] bg-[#DFE2E7]" />
              <span class="text-[#999] text-[11px] font-medium shrink-0">oppure continua con</span>
              <div class="flex-1 h-[1px] bg-[#DFE2E7]" />
            </div>

            <div class="flex flex-col gap-[8px]">
              <!-- Google full-width -->
              <button
                type="button"
                :class="[
                  'w-full h-[48px] rounded-full bg-white ring-[1.5px] ring-[#DFE2E7] hover:ring-[2px] hover:ring-[#095866]/50 text-[#1d2738] text-[14px] font-semibold flex items-center justify-center gap-[10px] cursor-pointer transition-all duration-[350ms] hover:shadow-[0_4px_16px_rgba(9,88,102,0.06)] hover:bg-[#FAFBFC]',
                  !authProviders.google && 'opacity-45 !cursor-not-allowed pointer-events-none',
                ]"
                @click="startSocialAuth('google')"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="shrink-0">
                  <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4" />
                  <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                  <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 001 12c0 1.77.42 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05" />
                  <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                </svg>
                Google
              </button>

              <!-- Apple + Facebook grid -->
              <div class="grid grid-cols-2 gap-[8px]">
                <button
                  type="button"
                  :class="[
                    'h-[48px] rounded-full bg-[#000000] text-white text-[14px] font-semibold flex items-center justify-center gap-[8px] cursor-pointer hover:bg-[#1a1a1a] transition-colors',
                    !authProviders.apple && 'opacity-45 !cursor-not-allowed pointer-events-none',
                  ]"
                  @click="startSocialAuth('apple')"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="white" class="shrink-0">
                    <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.32 2.32-2.22 4.45-3.74 4.25z" />
                  </svg>
                  Apple
                </button>
                <button
                  type="button"
                  :class="[
                    'h-[48px] rounded-full bg-[#1877F2] text-white text-[14px] font-semibold flex items-center justify-center gap-[8px] cursor-pointer hover:bg-[#166FE5] transition-colors',
                    !authProviders.facebook && 'opacity-45 !cursor-not-allowed pointer-events-none',
                  ]"
                  @click="startSocialAuth('facebook')"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="white" class="shrink-0">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                  </svg>
                  Facebook
                </button>
              </div>
            </div>
          </template>

        </div>
      </div>
    </template>
  </UModal>
</template>
