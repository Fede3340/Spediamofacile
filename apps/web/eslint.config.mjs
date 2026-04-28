import { createConfigForNuxt } from '@nuxt/eslint-config/flat';

export default createConfigForNuxt({
	features: {
		tooling: true,
	},
}).append({
	// Nuxt auto-imports: questi composable/helper vengono iniettati automaticamente
	// e non servono import espliciti. Li dichiariamo come globals per ESLint.
	languageOptions: {
		globals: {
			// Nuxt 3 auto-imports core
			useSanctumClient: 'readonly',
			useSanctumAuth: 'readonly',
			useSanctumFetch: 'readonly',
			useRoute: 'readonly',
			useRouter: 'readonly',
			useNuxtApp: 'readonly',
			useState: 'readonly',
			useCookie: 'readonly',
			useRequestEvent: 'readonly',
			useRequestHeaders: 'readonly',
			useRuntimeConfig: 'readonly',
			useFetch: 'readonly',
			useAsyncData: 'readonly',
			useSeoMeta: 'readonly',
			useHead: 'readonly',
			useAppConfig: 'readonly',
			useError: 'readonly',
			// Vue 3 lifecycle/reactivity auto-import (Nuxt)
			onScopeDispose: 'readonly',
			onBeforeUnmount: 'readonly',
			onUnmounted: 'readonly',
			onMounted: 'readonly',
			ref: 'readonly',
			reactive: 'readonly',
			computed: 'readonly',
			watch: 'readonly',
			watchEffect: 'readonly',
			nextTick: 'readonly',
			defineEmits: 'readonly',
			defineProps: 'readonly',
			defineExpose: 'readonly',
			withDefaults: 'readonly',
			defineNuxtRouteMiddleware: 'readonly',
			defineNuxtPlugin: 'readonly',
			defineNuxtComponent: 'readonly',
			definePageMeta: 'readonly',
			navigateTo: 'readonly',
			abortNavigation: 'readonly',
			createError: 'readonly',
			refreshNuxtData: 'readonly',
			refreshCookie: 'readonly',
			clearError: 'readonly',
			clearNuxtData: 'readonly',
			showError: 'readonly',
			// Auto-import internal helpers (definite in utils/auth.js, ecc.)
			useAuthUiSnapshotPersistence: 'readonly',
			runAuthBootstrap: 'readonly',
			waitForPostAuthSync: 'readonly',
			// Composables auto-import del progetto
			usePriceBands: 'readonly',
			useSession: 'readonly',
			useShipmentFlowStore: 'readonly',
			useCartFetch: 'readonly',
			useUiFeedback: 'readonly',
			useFunnelAnalytics: 'readonly',
			useCart: 'readonly',
			useAuth: 'readonly',
			useAddressBook: 'readonly',
			usePudo: 'readonly',
			useStatusBadge: 'readonly',
			useConfirmDialog: 'readonly',
			useCookieConsent: 'readonly',
			useShipmentForm: 'readonly',
			useShipmentLocationAutocomplete: 'readonly',
			useLocationSearch: 'readonly',
			useAdmin: 'readonly',
			usePayment: 'readonly',
			useQuote: 'readonly',
			useFunnel: 'readonly',
			useOrderDetail: 'readonly',
			useOrdersList: 'readonly',
			useFaqs: 'readonly',
			useChartLogic: 'readonly',
			useSmartValidation: 'readonly',
		},
	},
	rules: {
		// Vieta console.log/error/debug; permetti solo console.warn
		'no-console': ['warn', { allow: ['warn'] }],
		// Vue specifiche
		'vue/multi-word-component-names': 'off',
		'vue/no-multiple-template-root': 'off',
		// Rilassa per compatibilità con codice esistente
		'vue/require-default-prop': 'off',
		'vue/no-v-html': 'warn',
		// Catch vuoti tollerati dove il commento spiega il perché
		'no-empty': ['error', { allowEmptyCatch: true }],
		// Qualità preventiva: regole native ESLint (no plugin extra)
		'eqeqeq': ['error', 'always', { null: 'ignore' }],
		'no-var': 'error',
		'prefer-const': ['warn', { destructuring: 'all' }],
		// Quality gate: warn su file troppo grandi (god-files anti-pattern).
		// 400 LOC e' la soglia per refactor in store/sub-composable.
		// Solo warn, non error: i file legacy (es. usePreventivo, useAdminPrezzi)
		// vanno migrati progressivamente, non bloccati subito.
		'max-lines': ['warn', { max: 400, skipBlankLines: true, skipComments: true }],
		'max-lines-per-function': ['warn', { max: 100, skipBlankLines: true, skipComments: true, IIFEs: true }],
	},
});
