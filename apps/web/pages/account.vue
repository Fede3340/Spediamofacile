<!-- Wrapper /account/*: AccountRouteShell (sidebar+contenuto) + middleware app-auth
     a livello wrapper → tutte le /account/* ereditano la protezione (no bypass via URL). -->
<script setup>
definePageMeta({
  middleware: ['app-auth'],
});

useSeoMeta({
  title: 'Account',
  robots: 'noindex, nofollow',
});
</script>

<template>
  <AccountRouteShell>
    <NuxtPage />
  </AccountRouteShell>
</template>
