<script setup>// Carica autenticazione.css solo quando l'overlay è montato (code-splitting route-specific).
import '~/assets/css/autenticazione.css';
// Shell del modal autenticazione. Orchestra 4 flussi distinti (Login / Registrati /
// Forgot password / Verifica email) delegando la presentazione a sub-componenti:
//   • AuthLoginForm        — tab Login (email + password + link forgot)
//   • AuthRegisterForm     — tab Registrati (dati + privacy + Turnstile)
//   • AuthForgotForm       — flusso "password dimenticata"
//   • AuthVerifyForm       — flusso "verifica email OTP"
//   • AuthSocialButtons    — 3 bottoni Google/Apple/Facebook (login + register)
// Logica di business in useAuthOverlay (composable). Stato locale qui: solo forgot mode.
const { loginForm, registerForm, isLoading, resendLoading, authError, authSuccess, socialError, verificationMode, verificationLoading, verificationCode, verificationError, verificationSuccess, showLoginPassword, showRegisterPassword, showRegisterPasswordConfirm, socialErrorTone, isBusy, isOpen, selectedTab, entryMode, authProviders, clearFeedback, resetVerificationMode, closeModal, startSocialAuth, handleLogin, handleRegister, handleVerificationInput, handleVerificationKeydown, verifyCode, resendVerificationEmail, } = useAuthOverlay();
const { clearEntryMode } = useAuthModalStore();
// Cloudflare Turnstile (CAPTCHA) sul form di registrazione.
const registerTurnstile = useTurnstile();
// Wrapper handleRegister che blocca se il CAPTCHA non e' stato risolto
// e propaga il token come campo extra sul payload via registerForm.
const handleRegisterWithCaptcha = () => {
    if (!registerTurnstile.isReady.value) {
        // forziamo il feedback utente senza far submit
        return;
    }
    // Aggiungo il token al form prima della POST
    ;
    registerForm.value.cf_turnstile_token = registerTurnstile.token.value;
    handleRegister();
};
const modalDescription = computed(() => {
    if (forgotMode.value) {
        return 'Inserisci la tua email e ti invieremo il link per reimpostare la password.';
    }
    if (verificationMode.value) {
        return 'Inserisci il codice ricevuto per completare la verifica del tuo account.';
    }
    return selectedTab.value === 'register'
        ? 'Crea il tuo account per gestire spedizioni, pagamenti e profilo in un solo flusso.'
        : 'Accedi al tuo account per continuare con preventivo, ordini e pagamenti.';
});
// --- Forgot password state (local to this component) ---
const forgotMode = ref(false);
const forgotEmail = ref('');
const forgotLoading = ref(false);
const forgotError = ref('');
const forgotSuccess = ref('');
const sanctum = useSanctumClient();
const enterForgotMode = () => {
    forgotMode.value = true;
    forgotEmail.value = loginForm.value.email || '';
    forgotError.value = '';
    forgotSuccess.value = '';
    clearFeedback();
};
const exitForgotMode = () => {
    forgotMode.value = false;
    forgotError.value = '';
    forgotSuccess.value = '';
    clearEntryMode();
};
watch([isOpen, entryMode], ([open, mode]) => {
    if (!open) {
        exitForgotMode();
        return;
    }
    if (mode === 'forgot' && selectedTab.value === 'login' && !forgotMode.value) {
        enterForgotMode();
    }
}, { immediate: true });
const handleForgotPassword = async () => {
    forgotError.value = '';
    forgotSuccess.value = '';
    if (!forgotEmail.value) {
        forgotError.value = 'Inserisci la tua email per continuare.';
        return;
    }
    forgotLoading.value = true;
    try {
        const response = await sanctum('/api/forgot-password', {
            method: 'POST',
            body: { email: forgotEmail.value },
        });
        forgotSuccess.value = response?.message || 'Link di recupero inviato. Controlla la tua email.';
    }
    catch (error) {
        const data = error?.response?._data || error?.data || {};
        forgotError.value = data?.message || "Errore durante l'invio. Riprova.";
    }
    finally {
        forgotLoading.value = false;
    }
};
// Reset forgot mode when switching tabs
watch(selectedTab, () => {
    if (forgotMode.value)
        exitForgotMode();
});
// Body scroll lock: il contenuto sotto NON deve muoversi quando il modal è aperto.
// Salva scrollY, blocca position:fixed, ripristina su close/unmount.
const scrollLockState = { scrollY: 0, locked: false };
const lockBodyScroll = () => {
    if (typeof document === 'undefined' || scrollLockState.locked)
        return;
    scrollLockState.scrollY = window.scrollY || window.pageYOffset || 0;
    const body = document.body;
    body.style.position = 'fixed';
    body.style.top = `-${scrollLockState.scrollY}px`;
    body.style.left = '0';
    body.style.right = '0';
    body.style.width = '100%';
    body.style.overflow = 'hidden';
    scrollLockState.locked = true;
};
const unlockBodyScroll = () => {
    if (typeof document === 'undefined' || !scrollLockState.locked)
        return;
    const body = document.body;
    body.style.position = '';
    body.style.top = '';
    body.style.left = '';
    body.style.right = '';
    body.style.width = '';
    body.style.overflow = '';
    window.scrollTo(0, scrollLockState.scrollY);
    scrollLockState.locked = false;
};
const cleanupAuthScrollLock = () => {
    if (typeof document === 'undefined')
        return;
    window.setTimeout(() => {
        if (isOpen.value)
            return;
        const hasOpenDialog = document.querySelector('[data-slot="content"][data-state="open"], [role="dialog"][data-state="open"]');
        if (hasOpenDialog)
            return;
        const body = document.body;
        body.style.position = '';
        body.style.top = '';
        body.style.left = '';
        body.style.right = '';
        body.style.width = '';
        body.style.overflow = '';
    }, 250);
};
watch(isOpen, (open) => {
    if (open)
        lockBodyScroll();
    else {
        unlockBodyScroll();
        cleanupAuthScrollLock();
    }
}, { immediate: true });
onBeforeUnmount(() => {
    unlockBodyScroll();
});
// Design tokens. ! per vincere sui default di app.config.
const modalUi = {
    overlay: 'fixed inset-0 bg-black/50 backdrop-blur-[10px]',
    content: '!px-0 !pt-0 !pb-0 !p-0 !ring-0 !ring-transparent !divide-y-0 !border-0 !bg-transparent w-[calc(100vw-1rem)] max-w-[440px] max-h-[calc(100dvh-10px)] sm:max-h-[calc(100dvh-14px)] max-sm:max-h-[100dvh] max-sm:h-[100dvh] max-sm:w-full max-sm:max-w-full max-sm:rounded-none overflow-hidden rounded-[18px] shadow-[0_24px_80px_rgba(0,0,0,0.2),0_0_0_1px_rgba(9,88,102,0.06)]',
    body: '!p-0 !sm:p-0 overflow-visible',
    header: 'sr-only',
    title: 'sr-only',
    wrapper: 'sr-only',
};
</script>

<template>
  <UModal
    v-model:open="isOpen"
    :dismissible="!isBusy"
    :scrollable="false"
    :close="false"
    :fullscreen="false"
    :overlay="true"
    :title="forgotMode ? 'Recupera password' : verificationMode ? 'Verifica account' : 'Accedi o registrati'"
    :description="modalDescription"
    :ui="modalUi"
  >
    <template #body>
      <div class="relative flex flex-col overflow-hidden" @click.stop @pointerdown.stop>
        <!-- ── Teal accent bar ── -->
        <div
          class="w-full h-[4px] shrink-0"
          style="background: linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)"
          aria-hidden="true"
        />

        <!-- ── Content area ── -->
        <div
          class="relative px-[14px] sm:px-[16px] pt-[48px] pb-[13px] flex flex-col gap-[10px]"
          style="background: var(--gradient-page-surface);"
        >
          <!-- Close button: 44x44 tap target (WCAG 2.5.5) con pallino visivo 30x30 -->
          <button
            type="button"
            class="absolute top-[3px] right-[3px] z-20 flex h-[44px] w-[44px] items-center justify-center rounded-full bg-transparent text-[#52606D] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#095866]/25 cursor-pointer transition-all duration-[350ms] group"
            aria-label="Chiudi finestra"
            @click="closeModal"
          >
            <span class="flex h-[30px] w-[30px] items-center justify-center rounded-full bg-[#E6E9EE] group-hover:bg-[#DDE3E9] transition-colors">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
            </span>
          </button>

          <!-- Heading (solo per forgot / verification) -->
          <template v-if="forgotMode || verificationMode">
            <h2
              class="m-0 pr-[44px] text-[22px] font-extrabold leading-[1.08] text-[#1D2738] sm:text-[24px]"
              style="font-family: var(--font-montserrat, 'Montserrat', sans-serif)"
            >
              <template v-if="forgotMode">Recupera password</template>
              <template v-else>Verifica account</template>
            </h2>
          </template>

          <!-- ── Pill tabs (hidden during forgot/verification) ── -->
          <div
            v-if="!forgotMode && !verificationMode"
            role="tablist"
            aria-label="Accesso o registrazione"
            class="sf-shared-segment-strip sf-shared-segment-strip--two sf-shared-segment-strip--compact auth-overlay-segment-strip mt-[0]"
            style="box-shadow: inset 0 1px 2px rgba(0,0,0,0.04)"
          >
            <button
              type="button"
              role="tab"
              :aria-selected="selectedTab === 'login'"
              :class="['sf-shared-segment', 'sf-shared-segment--compact', 'auth-overlay-segment', selectedTab === 'login' ? 'sf-shared-segment--active' : '']"
              @click="selectedTab = 'login'; resetVerificationMode(); clearFeedback()"
            >
              Accedi
            </button>
            <button
              type="button"
              role="tab"
              :aria-selected="selectedTab === 'register'"
              :class="['sf-shared-segment', 'sf-shared-segment--compact', 'auth-overlay-segment', selectedTab === 'register' ? 'sf-shared-segment--active' : '']"
              @click="selectedTab = 'register'; resetVerificationMode(); clearFeedback()"
            >
              Registrati
            </button>
          </div>

          <!-- ── Feedback messages globali (social / auth / success) ── -->
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
          <AuthForgotForm
            v-if="forgotMode"
            :email="forgotEmail"
            :is-loading="forgotLoading"
            :error="forgotError"
            :success="forgotSuccess"
            @update:email="forgotEmail = $event"
            @submit="handleForgotPassword"
            @back="exitForgotMode"
          />

          <!-- ================= VERIFICATION MODE ================= -->
          <AuthVerifyForm
            v-else-if="verificationMode"
            :email="loginForm.email"
            :code="verificationCode"
            :is-loading="verificationLoading"
            :resend-loading="resendLoading"
            :error="verificationError"
            :success="verificationSuccess"
            @submit="verifyCode"
            @resend="resendVerificationEmail"
            @back="resetVerificationMode()"
            @input="(index, ev) => handleVerificationInput(index, ev)"
            @keydown="(index, ev) => handleVerificationKeydown(index, ev)"
          />

          <!-- ================= LOGIN FORM ================= -->
          <AuthLoginForm
            v-else-if="selectedTab === 'login'"
            :form="loginForm"
            :is-loading="isLoading"
            :show-password="showLoginPassword"
            @submit="handleLogin"
            @enter-forgot="enterForgotMode"
            @toggle-password="showLoginPassword = !showLoginPassword"
          />

          <!-- ================= REGISTER FORM ================= -->
          <AuthRegisterForm
            v-else
            :form="registerForm"
            :is-loading="isLoading"
            :show-password="showRegisterPassword"
            :show-password-confirm="showRegisterPasswordConfirm"
            :turnstile="registerTurnstile"
            @submit="handleRegisterWithCaptcha"
            @toggle-password="showRegisterPassword = !showRegisterPassword"
            @toggle-password-confirm="showRegisterPasswordConfirm = !showRegisterPasswordConfirm"
          />

          <!-- ================= SOCIAL AUTH (solo Login + Registrati) ================= -->
          <AuthSocialButtons
            v-if="!forgotMode && !verificationMode"
            :providers="authProviders"
            @social-auth="startSocialAuth"
          />
        </div>
      </div>
    </template>
  </UModal>
</template>
