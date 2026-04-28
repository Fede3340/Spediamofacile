<script setup>
definePageMeta({ layout: 'default' });

useSeoMeta({
  title: 'Pagina non trovata',
  description: 'La pagina che stai cercando non esiste o è stata spostata.',
});
</script>

<template>
  <section class="min-h-[400px] py-[24px] desktop:py-[28px]">
    <div class="my-container">
      <div class="bg-white rounded-[18px] border border-[var(--color-brand-border)] shadow-sm p-[20px_16px] desktop:p-[24px] max-w-[560px] mx-auto text-center">
        <!-- SVG illustrazione pacco smarrito -->
        <svg
          class="mx-auto mb-[24px] w-[100px] h-[100px]"
          viewBox="0 0 100 100"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <!-- Pacco -->
          <rect x="20" y="30" width="60" height="50" rx="4" stroke="var(--color-brand-primary)" stroke-width="3" fill="#F0F9FB"/>
          <line x1="20" y1="50" x2="80" y2="50" stroke="var(--color-brand-primary)" stroke-width="3"/>
          <line x1="50" y1="50" x2="50" y2="80" stroke="var(--color-brand-primary)" stroke-width="2"/>
          <!-- Nastro superiore -->
          <path d="M40 30 L50 18 L60 30" stroke="var(--color-brand-primary)" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
          <!-- Punto interrogativo -->
          <circle cx="75" cy="25" r="14" fill="var(--color-brand-primary)" opacity="0.12"/>
          <text x="75" y="31" text-anchor="middle" font-size="18" font-weight="bold" fill="var(--color-brand-primary)">?</text>
        </svg>

        <!-- Codice errore -->
        <h1 class="font-montserrat text-[4rem] font-[800] text-[var(--color-brand-primary)] mb-[4px] leading-none">404</h1>

        <!-- Messaggio principale -->
        <h2 class="font-montserrat text-[1.5rem] font-[800] text-[var(--color-brand-text)] mb-[8px]">
          Pagina non trovata
        </h2>

        <!-- Testo descrittivo -->
        <p class="text-[var(--color-brand-text-secondary)] text-[0.9375rem] leading-relaxed mb-[28px]">
          Ci dispiace, la pagina che stai cercando non esiste o potrebbe essere
          stata spostata. Torna alla homepage per continuare la navigazione.
        </p>

        <!-- Link homepage -->
        <SfButton to="/" variant="primary">
          <template #leading>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
          </template>
          Torna alla homepage
        </SfButton>

        <!-- Link secondario -->
        <p class="text-[var(--color-brand-text-muted)] text-[0.8125rem] mt-[16px]">
          Oppure vai al <NuxtLink to="/preventivo" class="text-[var(--color-brand-primary)] hover:opacity-80 font-medium">preventivo</NuxtLink>
          o alla pagina <NuxtLink to="/contatti" class="text-[var(--color-brand-primary)] hover:opacity-80 font-medium">contatti</NuxtLink>.
        </p>
      </div>
    </div>
  </section>
</template>
