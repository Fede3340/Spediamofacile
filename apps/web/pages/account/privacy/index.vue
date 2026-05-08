<script setup>
/*
 * /account/privacy — Privacy e dati GDPR.
 * P1.3: download dati personali via /api/me/export-data (alias canonico,
 * audit log lato server, streaming attachment JSON).
 */
definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'Privacy e dati',
	description: 'Esporta o gestisci i tuoi dati personali in conformita\' al GDPR.',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();
const { message: feedback, showSuccess, showError } = useFlashMessage();
const downloading = ref(false);

const downloadData = async () => {
	if (downloading.value) return;
	downloading.value = true;
	try {
		const blob = await sanctum('/api/me/export-data', { method: 'GET', responseType: 'blob' });
		const url = window.URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = `spediamofacile-export-${new Date().toISOString().slice(0, 10)}.json`;
		document.body.appendChild(link);
		link.click();
		window.URL.revokeObjectURL(url);
		link.remove();
		showSuccess('Esportazione completata, file scaricato.');
	} catch (e) {
		showError(e, 'Impossibile esportare i dati. Riprova tra poco.');
	} finally {
		downloading.value = false;
	}
};
</script>

<template>
	<AccountPageSection>
		<AccountPageHeader
			title="Privacy e dati"
			description="Esporta o gestisci i tuoi dati personali in conformita' al GDPR."
			current="Privacy"
		/>

		<SfActionBanner :message="feedback" />

		<SfCard padding="md">
			<h2 class="text-base font-bold text-brand-text mb-2">Scarica i tuoi dati</h2>
			<p class="text-sm text-brand-text-secondary mb-4">
				Riceverai un file JSON con i tuoi dati: profilo, ordini, indirizzi, wallet,
				preferenze notifiche, consensi cookie e ultime sessioni.
			</p>
			<SfButton :loading="downloading" @click="downloadData">
				Scarica i miei dati
			</SfButton>
		</SfCard>
	</AccountPageSection>
</template>
