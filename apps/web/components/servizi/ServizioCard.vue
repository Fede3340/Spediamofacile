<script setup>
const props = defineProps({
	service: { type: Object, required: true },
	meta: { type: Object, required: true },
	highlights: { type: Array, default: () => [] },
	visualTone: { type: String, default: 'primary' },
	description: { type: String, default: '' },
});

const iconBySlug = {
	'pagamento-alla-consegna': 'mdi:cash-multiple',
	'spedizione-senza-etichetta': 'mdi:printer-off-outline',
	'ritiro-a-domicilio': 'mdi:home-import-outline',
	'assicurazione': 'mdi:shield-check-outline',
	'assicurazione-spedizione': 'mdi:shield-check-outline',
	'sponda-idraulica': 'mdi:forklift',
	'spedizione-programmata': 'mdi:calendar-clock-outline',
};
const serviceIcon = iconBySlug[props.service.slug] || 'mdi:truck-outline';
</script>

<template>
	<NuxtLink
		:to="`/servizi/${service.slug}`"
		class="sv-card"
	>
		<div
			class="sv-card__visual"
			:class="`sv-card__visual--${visualTone}`"
		>
			<div class="sv-card__visual-header">
				<div class="sv-card__icon-shell" aria-hidden="true">
					<UIcon :name="serviceIcon" class="sv-card__icon" />
				</div>
				<span
					class="sv-card__badge"
					:style="{ background: meta.badgeColor }"
				>
					{{ meta.badge }}
				</span>
			</div>

			<div v-if="highlights.length" class="sv-card__chips">
				<span
					v-for="chip in highlights"
					:key="`${service.slug}-${chip}`"
					class="sv-card__chip">
					{{ chip }}
				</span>
			</div>
		</div>

		<div class="sv-card__body">
			<h3 class="sv-card__title">{{ service.title }}</h3>
			<p class="sv-card__desc">{{ description }}</p>
			<div class="sv-card__footer">
				<span class="sv-card__time">
					<UIcon name="mdi:clock-outline" class="sv-card__time-icon" />
					{{ meta.readTime }}
				</span>
				<span class="sv-card__cta">
					Scopri
					<UIcon name="mdi:arrow-right" class="sv-card__cta-icon" />
				</span>
			</div>
		</div>
	</NuxtLink>
</template>

<style scoped>
/* Card servizio: visual header con tono brand + body bianco con highlight + CTA. */
.sv-card {
	display: flex;
	flex-direction: column;
	background: #fff;
	border-radius: 18px;
	overflow: hidden;
	border: 1px solid var(--color-brand-border, #E9EBEC);
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.04);
	text-decoration: none;
	color: inherit;
	transition: transform 200ms ease, box-shadow 200ms ease, border-color 200ms ease;
	height: 100%;
}
.sv-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 12px 28px rgba(9, 88, 102, 0.12);
	border-color: rgba(9, 88, 102, 0.25);
}
.sv-card:focus-visible {
	outline: 3px solid rgba(228, 66, 3, 0.35);
	outline-offset: 2px;
}

/* Visual header — tono variabile per tipologia servizio */
.sv-card__visual {
	padding: 20px 20px 22px;
	display: flex;
	flex-direction: column;
	gap: 16px;
	min-height: 148px;
	background: var(--gradient-card-primary, linear-gradient(135deg, rgba(9, 88, 102, 0.08) 0%, rgba(9, 88, 102, 0.02) 100%));
	border-bottom: 1px solid rgba(9, 88, 102, 0.08);
}
.sv-card__visual--primary { background: linear-gradient(135deg, rgba(9, 88, 102, 0.10) 0%, rgba(9, 88, 102, 0.02) 100%); }
.sv-card__visual--accent { background: linear-gradient(135deg, rgba(228, 66, 3, 0.10) 0%, rgba(228, 66, 3, 0.02) 100%); }
.sv-card__visual--soft { background: linear-gradient(135deg, rgba(95, 124, 132, 0.10) 0%, rgba(95, 124, 132, 0.02) 100%); }

.sv-card__visual-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}
.sv-card__icon-shell {
	width: 44px;
	height: 44px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	border-radius: 12px;
	background: #fff;
	border: 1px solid rgba(9, 88, 102, 0.12);
	box-shadow: 0 2px 6px rgba(9, 88, 102, 0.06);
	flex-shrink: 0;
}
.sv-card__icon {
	width: 22px;
	height: 22px;
	color: var(--color-brand-primary, #095866);
}
.sv-card__badge {
	display: inline-flex;
	align-items: center;
	padding: 5px 12px;
	border-radius: 999px;
	font-size: 11px;
	font-weight: 700;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	color: #fff;
	white-space: nowrap;
}

.sv-card__chips {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
}
.sv-card__chip {
	display: inline-flex;
	align-items: center;
	padding: 4px 10px;
	border-radius: 999px;
	background: #fff;
	border: 1px solid rgba(9, 88, 102, 0.12);
	font-size: 12px;
	font-weight: 600;
	color: var(--color-brand-text, #1D2738);
	line-height: 1.3;
}

/* Body card */
.sv-card__body {
	padding: 18px 20px 20px;
	display: flex;
	flex-direction: column;
	gap: 10px;
	flex: 1;
}
.sv-card__title {
	font-size: 18px;
	font-weight: 700;
	color: var(--color-brand-text, #1D2738);
	margin: 0;
	line-height: 1.3;
}
.sv-card__desc {
	font-size: 14px;
	line-height: 1.55;
	color: var(--color-brand-text-secondary, #5A6474);
	margin: 0;
	flex: 1;
	display: -webkit-box;
	-webkit-line-clamp: 4;
	-webkit-box-orient: vertical;
	overflow: hidden;
}
.sv-card__footer {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 6px;
	padding-top: 12px;
	border-top: 1px solid var(--color-brand-border, #E9EBEC);
}
.sv-card__time {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 12px;
	font-weight: 600;
	color: var(--color-brand-text-muted, #6b7280);
}
.sv-card__time-icon {
	width: 14px;
	height: 14px;
}
.sv-card__cta {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 13px;
	font-weight: 700;
	color: var(--color-brand-accent, #E44203);
	transition: gap 200ms ease;
}
.sv-card:hover .sv-card__cta {
	gap: 10px;
}
.sv-card__cta-icon {
	width: 16px;
	height: 16px;
}
</style>

