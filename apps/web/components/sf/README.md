# sf/* Atomic Library — Sprint 4.8

Libreria 10 componenti atomic del design system SpedizioneFacile.
Brad Frost methodology: **atoms** (Button, Card, Input, Badge, Icon, Skeleton)
+ **molecules** (EmptyState, Toast, Tooltip) + **organism** (Modal).

Prefisso `Sf` = autoimport Nuxt (`components/sf/*.vue` → `<SfButton />`).
Palette teal+arancione, **mai blu**. Tutti i colori via `var(--sf-*)` / `var(--color-brand-*)`.

---

## Storybook stub (commentato — no Storybook in repo)

```md
<!-- storybook: title=Atoms/SfButton -->
<SfButton variant="primary" size="md">Primary MD</SfButton>
<SfButton variant="secondary" size="md">Secondary</SfButton>
<SfButton variant="tertiary" size="sm">Tertiary SM</SfButton>
<SfButton variant="ghost">Ghost</SfButton>
<SfButton variant="cta" size="lg" icon="mdi:arrow-right" icon-position="end">CTA</SfButton>
<SfButton loading>Loading…</SfButton>
<SfButton disabled>Disabled</SfButton>

<!-- storybook: title=Atoms/SfCard -->
<SfCard variant="base" padding="md">Base card</SfCard>
<SfCard variant="featured" padding="lg" shadow="md">Featured card</SfCard>
<SfCard variant="kpi">123 €</SfCard>
<SfCard variant="flat">Flat</SfCard>

<!-- storybook: title=Atoms/SfInput -->
<SfInput v-model="v" label="Email" type="email" hint="Non condivisa" />
<SfInput v-model="v" label="Peso" suffix="kg" icon="mdi:weight" size="lg" />
<SfInput v-model="v" label="CAP" :error="'CAP non valido'" required />

<!-- storybook: title=Atoms/SfBadge -->
<SfBadge variant="success">Attivo</SfBadge>
<SfBadge variant="warning">In attesa</SfBadge>
<SfBadge variant="danger" size="xs">Errore</SfBadge>
<SfBadge variant="accent">Promo</SfBadge>

<!-- storybook: title=Atoms/SfIcon -->
<SfIcon name="mdi:check" size="small" />
<SfIcon name="mdi:package-variant" size="large" aria-label="Pacco" />

<!-- storybook: title=Molecules/SfEmptyState -->
<SfEmptyState
  icon="mdi:package-variant"
  title="Nessuna spedizione"
  description="Crea la tua prima spedizione per vederla qui."
>
  <template #action>
    <SfButton variant="cta">Crea spedizione</SfButton>
  </template>
</SfEmptyState>

<!-- storybook: title=Atoms/SfSkeleton -->
<SfSkeleton width="100%" height="20px" :count="3" />
<SfSkeleton width="120px" height="120px" rounded="var(--radius-pill)" />

<!-- storybook: title=Molecules/SfToast -->
<SfToast type="success" message="Spedizione creata" :duration="4000" />
<SfToast type="error" message="Errore generico" action="Riprova" />

<!-- storybook: title=Molecules/SfTooltip -->
<SfTooltip position="top" text="Aiuto contestuale">
  <SfButton size="sm">?</SfButton>
</SfTooltip>

<!-- storybook: title=Organisms/SfModal -->
<SfModal v-model="open" title="Conferma" size="md">
  <p>Procedere?</p>
  <template #footer>
    <SfButton variant="tertiary" @click="open=false">Annulla</SfButton>
    <SfButton variant="primary" @click="confirm">Conferma</SfButton>
  </template>
</SfModal>
```

## Token design system usati
`--button-height-xs/sm/md/lg` · `--shadow-sm/md/lg/cta/focus` ·
`--radius-sm/md/lg/pill` · `--sf-radius-button/control/card` ·
`--color-brand-primary/accent/text/*` · `--admin-status-*-bg/text` ·
`--icon-micro/small/medium/large/xlarge` · `--duration-fast/medium` · `--ease-out/bounce`.

## Accessibilità WCAG 2.1 AA
- Focus ring 3px teal 60% (`--shadow-focus`) ovunque
- `aria-busy` loading state · `aria-invalid` / `aria-describedby` su Input
- Modal: `role="dialog"` + `aria-modal="true"` + focus trap + Escape + scroll lock
- Toast: `role="status"` + `aria-live="polite"` (assertive su error)
- Prefers-reduced-motion rispettato in tutte le animazioni
