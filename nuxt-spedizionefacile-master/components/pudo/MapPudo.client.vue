<!-- Implementazione Leaflet diretta, senza wrapper vue-leaflet (evita errori di patch runtime). -->
<script setup>
import { escapeHtml } from '~/utils/html';

let L = null;

const props = defineProps({
	points: { type: Array, default: () => [] },
	selectedId: { type: String, default: null },
	referencePoint: { type: Object, default: null },
});

const emit = defineEmits(['select', 'map-click']);

const mapEl = ref(null);
const mapReady = ref(false);
const tileLayerReady = ref(false);
const tileLayerError = ref(false);

const defaultCenter = [41.9028, 12.4964];
const defaultZoom = 6;

let mapInstance = null;
let tileLayer = null;
let pointsLayer = null;
let referenceLayer = null;
let mapSlowTimer = null;
let invalidateTimers = [];
let resizeHandler = null;
let mapDblClickHandler = null;

const parseCoordinate = (value) => {
	if (value === null || value === undefined || value === '') return null;
	const parsed = Number.parseFloat(String(value).trim().replace(',', '.'));
	return Number.isFinite(parsed) ? parsed : null;
};

const isFiniteCoordinate = (value) => Number.isFinite(parseCoordinate(value));

const getPointKey = (point, index = 0) => {
	const explicit = String(point?.ui_key || point?.pudo_id || point?.carrier_pudo_id || '').trim();
	if (explicit) return explicit;
	const lat = parseCoordinate(point?.latitude ?? point?.lat);
	const lng = parseCoordinate(point?.longitude ?? point?.lng);
	const latPart = Number.isFinite(lat) ? lat.toFixed(6) : 'na';
	const lngPart = Number.isFinite(lng) ? lng.toFixed(6) : 'na';
	return `map-fallback-${index}-${latPart}-${lngPart}`;
};

const parsedReferencePoint = computed(() => {
	const latitude = parseCoordinate(props.referencePoint?.latitude);
	const longitude = parseCoordinate(props.referencePoint?.longitude);
	if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
	return {
		latitude,
		longitude,
		address: props.referencePoint?.address || '',
		city: props.referencePoint?.city || '',
		zip_code: props.referencePoint?.zip_code || '',
		label: props.referencePoint?.label || '',
	};
});

const validPoints = computed(() =>
	(props.points || [])
		.filter((p) => isFiniteCoordinate(p?.latitude) && isFiniteCoordinate(p?.longitude))
		.map((p, index) => ({ ...p, __mapKey: getPointKey(p, index) }))
);

const formatDistance = (meters) => {
	const value = Number(meters);
	if (!Number.isFinite(value)) return '';
	if (value >= 1000) return `${(value / 1000).toFixed(1)} km`;
	return `${Math.round(value)} m`;
};

const createPointIcon = (isSelected) => {
	const size = isSelected ? 36 : 28;
	const inner = isSelected
		? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
		: '<span class="pudo-marker-dot"></span>';

	const html = `
		<div class="pudo-marker ${isSelected ? 'is-selected' : ''}" style="width:${size}px;height:${size}px;">
			${inner}
		</div>
		<div class="pudo-marker-tip ${isSelected ? 'is-selected' : ''}"></div>
	`;

	return L.divIcon({
		className: 'pudo-marker-icon-wrap',
		html,
		iconSize: [size, size + 10],
		iconAnchor: [Math.round(size / 2), size + 10],
		popupAnchor: [0, -(size + 4)],
	});
};

const createReferenceIcon = () => {
	const html = `
		<div class="pudo-ref-marker">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"></path>
				<circle cx="12" cy="9" r="2.5"></circle>
			</svg>
		</div>
		<div class="pudo-ref-tip"></div>
	`;

	return L.divIcon({
		className: 'pudo-reference-icon-wrap',
		html,
		iconSize: [32, 42],
		iconAnchor: [16, 42],
		popupAnchor: [0, -36],
	});
};

const clearInvalidateTimers = () => {
	invalidateTimers.forEach((timer) => window.clearTimeout(timer));
	invalidateTimers = [];
};

const queueInvalidateMapSize = () => {
	if (!mapInstance) return;
	clearInvalidateTimers();
	[0, 120, 360, 900].forEach((delay) => {
		const timer = window.setTimeout(() => {
			if (!mapInstance) return;
			try {
				mapInstance.invalidateSize({ animate: false });
			} catch {
				// no-op
			}
		}, delay);
		invalidateTimers.push(timer);
	});
};

const clearLayers = () => {
	if (pointsLayer) pointsLayer.clearLayers();
	if (referenceLayer) referenceLayer.clearLayers();
};

const renderMapData = () => {
	if (!mapInstance || !mapReady.value || !pointsLayer || !referenceLayer) return;

	clearLayers();

	const bounds = [];
	const reference = parsedReferencePoint.value;

	if (reference) {
		const refLatLng = [reference.latitude, reference.longitude];
		bounds.push(refLatLng);
		const popupLabel = reference.label || [reference.address, [reference.zip_code, reference.city].filter(Boolean).join(' ')].filter(Boolean).join(', ');

		L.marker(refLatLng, { icon: createReferenceIcon() })
			.bindPopup(`<div style="font-size:13px;line-height:1.4"><b>Punto di riferimento</b><br>${escapeHtml(popupLabel || 'Posizione selezionata')}</div>`)
			.addTo(referenceLayer);
	}

	validPoints.value.forEach((point, index) => {
		const key = String(point.__mapKey || getPointKey(point, index));
		const isSelected = key === String(props.selectedId || '');
		const lat = parseCoordinate(point.latitude);
		const lng = parseCoordinate(point.longitude);
		if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

		const marker = L.marker([lat, lng], { icon: createPointIcon(isSelected) });
		marker.on('click', (event) => {
			event?.originalEvent?.stopPropagation?.();
			emit('select', point);
		});

		const popupHtml = `
			<div style="font-size:13px;line-height:1.4;min-width:180px;">
				<b>${escapeHtml(point.name || 'Punto BRT')}</b><br>
				${escapeHtml(point.address || '')}<br>
				${escapeHtml([point.zip_code, point.city].filter(Boolean).join(' '))}
				${point.distance_meters ? `<br><span style="color:var(--color-brand-primary);font-weight:600">Distanza: ${escapeHtml(formatDistance(point.distance_meters))}</span>` : ''}
			</div>
		`;
		marker.bindPopup(popupHtml);
		marker.addTo(pointsLayer);
		bounds.push([lat, lng]);
	});

	queueInvalidateMapSize();

	if (!bounds.length) {
		mapInstance.setView(defaultCenter, defaultZoom, { animate: false });
		return;
	}

	if (bounds.length === 1) {
		mapInstance.setView(bounds[0], reference ? 14 : 13, { animate: true });
		return;
	}

	mapInstance.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
};

const onTileLayerLoad = () => {
	tileLayerReady.value = true;
	tileLayerError.value = false;
	if (mapSlowTimer) {
		window.clearTimeout(mapSlowTimer);
		mapSlowTimer = null;
	}
};

const onTileLayerError = () => {
	if (!tileLayerReady.value) tileLayerError.value = true;
};

onMounted(async () => {
	const leafletModule = await import('leaflet');
	await import('leaflet/dist/leaflet.css');
	L = leafletModule.default || leafletModule;

	if (!mapEl.value) return;

	mapInstance = L.map(mapEl.value, {
		zoomControl: true,
		preferCanvas: true,
		doubleClickZoom: false,
	});
	mapInstance.setView(defaultCenter, defaultZoom);
	mapDblClickHandler = (event) => {
		const latitude = parseCoordinate(event?.latlng?.lat);
		const longitude = parseCoordinate(event?.latlng?.lng);
		if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
		emit('map-click', { latitude, longitude });
	};
	mapInstance.on('dblclick', mapDblClickHandler);

	tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
	});
	tileLayer.on('load', onTileLayerLoad);
	tileLayer.on('tileerror', onTileLayerError);
	tileLayer.addTo(mapInstance);

	pointsLayer = L.layerGroup().addTo(mapInstance);
	referenceLayer = L.layerGroup().addTo(mapInstance);

	mapReady.value = true;
	tileLayerReady.value = false;
	tileLayerError.value = false;

	if (mapSlowTimer) window.clearTimeout(mapSlowTimer);
	mapSlowTimer = window.setTimeout(() => {
		if (!tileLayerReady.value) tileLayerError.value = true;
	}, 5000);

	resizeHandler = () => queueInvalidateMapSize();
	window.addEventListener('resize', resizeHandler, { passive: true });

	renderMapData();
});

watch([validPoints, parsedReferencePoint, () => props.selectedId], () => {
	nextTick(() => renderMapData());
}, { flush: 'post' });

onBeforeUnmount(() => {
	if (mapSlowTimer) {
		window.clearTimeout(mapSlowTimer);
		mapSlowTimer = null;
	}
	clearInvalidateTimers();
	if (resizeHandler) {
		window.removeEventListener('resize', resizeHandler);
		resizeHandler = null;
	}

	if (tileLayer) {
		tileLayer.off('load', onTileLayerLoad);
		tileLayer.off('tileerror', onTileLayerError);
	}
	if (mapInstance && mapDblClickHandler) {
		mapInstance.off('dblclick', mapDblClickHandler);
		mapDblClickHandler = null;
	}

	if (mapInstance) {
		mapInstance.remove();
		mapInstance = null;
	}

	tileLayer = null;
	pointsLayer = null;
	referenceLayer = null;
});
</script>

<template>
	<div class="relative w-full h-full min-h-[320px] tablet:min-h-[360px] desktop:min-h-[420px] rounded-[16px] overflow-hidden border border-[var(--color-brand-border)] bg-[#F4F7F9]">
		<div ref="mapEl" class="w-full h-full" />

		<div
			v-if="!mapReady || (!tileLayerReady && !tileLayerError)"
			class="absolute inset-0 flex items-center justify-center bg-[#F8F9FB]/90 text-[var(--color-brand-text-secondary)] text-[0.875rem]">
			Caricamento mappa...
		</div>

		<div
			v-else-if="tileLayerError"
			class="absolute inset-0 flex items-center justify-center bg-[#F8F9FB]/95 text-[var(--color-brand-text-secondary)] text-[0.875rem] text-center px-[14px]">
			Impossibile caricare la mappa ora. Riprova tra qualche secondo.
		</div>
	</div>
</template>

<\!-- CSS in assets/css/shipment-step.css (PUDO markers section) -->
