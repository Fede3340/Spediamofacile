export default defineNuxtRouteMiddleware((to, from) => {
	if (!to.query || Object.keys(to.query).length === 0) {
		return navigateTo("/");
	}
});
