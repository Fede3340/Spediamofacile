/**
 * MIDDLEWARE AGGIORNAMENTO PASSWORD (update-password.js)
 *
 * I middleware sono "controlli automatici" che vengono eseguiti PRIMA di mostrare una pagina.
 * Funzionano come un guardiano all'ingresso: controllano se l'utente ha il permesso
 * di entrare in quella pagina, e se non ce l'ha, lo mandano da un'altra parte.
 *
 * Questo middleware protegge la pagina di aggiornamento/reset della password.
 * Controlla che nell'indirizzo ci sia un "token" (un codice segreto temporaneo).
 * Il token viene inviato via email quando l'utente chiede di recuperare la password.
 *
 * Se qualcuno prova ad aprire la pagina senza il token (cioè senza aver cliccato
 * il link nell'email), viene mandato alla homepage.
 *
 * In pratica: la pagina di reset password funziona solo se si arriva
 * dal link nell'email, che contiene il token di sicurezza.
 */
export default defineNuxtRouteMiddleware((to, from) => {
	if (!to.query.token || String(to.query.token).trim() === "") {
		return navigateTo("/");
	}
});
