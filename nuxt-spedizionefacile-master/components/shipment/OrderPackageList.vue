<!-- COMPONENTE: OrderPackageList -->
<script setup>
import '~/assets/css/components/sf-order-package-list.css';

const props = defineProps({
	packages: { type: Array, default: () => [] },
	showPrices: { type: Boolean, default: true },
});

// formatPrice(cents) -> "12,50 EUR" stile italiano (utility centralizzata)
import { formatPriceSafe as formatPrice } from '~/utils/price.js';

// Map package_type -> label italiano caratteristico
const PACKAGE_TYPE_LABELS = {
	box: 'Scatola',
	envelope: 'Busta',
	pallet: 'Pallet',
	tube: 'Tubo',
	custom: 'Personalizzato',
};

const typeLabel = (type) => PACKAGE_TYPE_LABELS[type?.toLowerCase?.()] || type || 'Collo';

const totalColli = computed(() => props.packages.reduce((sum, p) => sum + (Number(p.quantity) || 1), 0));
const totalPeso = computed(() => {
	const sum = props.packages.reduce((acc, p) => acc + (Number(p.weight) || 0) * (Number(p.quantity) || 1), 0);
	return sum.toLocaleString('it-IT', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
});
const totalPrezzo = computed(() => props.packages.reduce((sum, p) => sum + (Number(p.single_price) || 0) * (Number(p.quantity) || 1), 0));
</script>

<template>
	<section class="order-package-list" aria-label="Elenco colli">
		<header class="order-package-list__header">
			<div>
				<p class="order-package-list__kicker">Colli</p>
				<h2 class="order-package-list__title">Pacchi spediti</h2>
			</div>
			<div class="order-package-list__totals">
				<span class="order-package-list__chip">{{ totalColli }} {{ totalColli === 1 ? 'collo' : 'colli' }}</span>
				<span class="order-package-list__chip order-package-list__chip--alt">{{ totalPeso }} kg</span>
			</div>
		</header>

		<!-- Empty -->
		<p v-if="!packages.length" class="order-package-list__empty">Nessun collo registrato per questo ordine.</p>

		<!-- Tabella desktop -->
		<div v-else class="order-package-list__table-wrap">
			<table class="order-package-list__table">
				<thead>
					<tr>
						<th scope="col" class="order-package-list__th">#</th>
						<th scope="col" class="order-package-list__th">Tipo</th>
						<th scope="col" class="order-package-list__th order-package-list__th--center">Qta</th>
						<th scope="col" class="order-package-list__th order-package-list__th--right">Peso</th>
						<th scope="col" class="order-package-list__th">Dimensioni (L x P x H)</th>
						<th scope="col" class="order-package-list__th">Contenuto</th>
						<th v-if="showPrices" scope="col" class="order-package-list__th order-package-list__th--right">Prezzo</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(pkg, idx) in packages" :key="pkg.id || idx" class="order-package-list__row">
						<td class="order-package-list__td order-package-list__td--index">{{ idx + 1 }}</td>
						<td class="order-package-list__td">
							<span class="order-package-list__type">
								<span class="order-package-list__type-dot" aria-hidden="true"></span>
								{{ typeLabel(pkg.package_type) }}
							</span>
						</td>
						<td class="order-package-list__td order-package-list__td--center">{{ pkg.quantity || 1 }}</td>
						<td class="order-package-list__td order-package-list__td--right">{{ pkg.weight }} kg</td>
						<td class="order-package-list__td order-package-list__td--mono">
							{{ pkg.first_size }} x {{ pkg.second_size }} x {{ pkg.third_size }} cm
						</td>
						<td class="order-package-list__td order-package-list__td--muted">
							{{ pkg.content_description || '—' }}
						</td>
						<td v-if="showPrices" class="order-package-list__td order-package-list__td--right order-package-list__td--price">
							{{ formatPrice(pkg.single_price) }}
						</td>
					</tr>
				</tbody>
				<tfoot v-if="showPrices">
					<tr>
						<td colspan="6" class="order-package-list__td order-package-list__td--right order-package-list__td--total-label">
							Totale colli
						</td>
						<td class="order-package-list__td order-package-list__td--right order-package-list__td--total">
							{{ formatPrice(totalPrezzo) }}
						</td>
					</tr>
				</tfoot>
			</table>
		</div>

		<!-- Cards mobile -->
		<ul v-if="packages.length" class="order-package-list__cards" role="list">
			<li v-for="(pkg, idx) in packages" :key="`m-${pkg.id || idx}`" class="order-package-list__card">
				<div class="order-package-list__card-head">
					<span class="order-package-list__card-index">Collo {{ idx + 1 }}</span>
					<span class="order-package-list__type">
						<span class="order-package-list__type-dot" aria-hidden="true"></span>
						{{ typeLabel(pkg.package_type) }}
					</span>
				</div>
				<dl class="order-package-list__card-grid">
					<div>
						<dt>Quantita</dt>
						<dd>{{ pkg.quantity || 1 }}</dd>
					</div>
					<div>
						<dt>Peso</dt>
						<dd>{{ pkg.weight }} kg</dd>
					</div>
					<div>
						<dt>Dimensioni</dt>
						<dd class="order-package-list__td--mono">{{ pkg.first_size }} x {{ pkg.second_size }} x {{ pkg.third_size }} cm</dd>
					</div>
					<div v-if="pkg.content_description">
						<dt>Contenuto</dt>
						<dd>{{ pkg.content_description }}</dd>
					</div>
					<div v-if="showPrices">
						<dt>Prezzo</dt>
						<dd class="order-package-list__td--price">{{ formatPrice(pkg.single_price) }}</dd>
					</div>
				</dl>
			</li>
		</ul>
	</section>
</template>
