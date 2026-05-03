<script setup>
import { escapeHtml } from '~/utils/html';
const props = defineProps({
  points: { type: Array, required: true },
  selectedKey: { type: [String, Number, null], default: null },
  referencePoint: { type: [Object, null], default: null },
});
const emit = defineEmits(['select']);
const mapEl = ref(null);
const ready = ref(false);
const tileError = ref(false);
let LeafletNS = null;
let mapInstance = null;
let pointsLayer = null;
let referenceLayer = null;
const markerByKey = new Map();
const ITALY_CENTER = [41.9028, 12.4964];
const ITALY_ZOOM = 6;
const teal = '#095866';
const orange = '#E44203';
const tealDark = '#074a56';
const buildMarkerSvg = (selected) => {
    const fill = selected ? orange : teal;
    const stroke = selected ? '#a52f02' : tealDark;
    return `
		<svg xmlns="http://www.w3.org/2000/svg" width="30" height="40" viewBox="0 0 30 40">
			<path d="M15 1 C7 1 1 7 1 15 c0 9.5 14 23.5 14 23.5 S29 24.5 29 15 C29 7 23 1 15 1 z"
				fill="${fill}" stroke="${stroke}" stroke-width="2"/>
			<circle cx="15" cy="14" r="5" fill="#ffffff"/>
		</svg>`;
};
const validPoints = computed(() => (props.points || []).filter((p) => Number.isFinite(Number(p.latitude)) && Number.isFinite(Number(p.longitude))));
const buildIcon = (selected) => {
    if (!LeafletNS)
        return null;
    return LeafletNS.divIcon({
        className: 'pudo-marker-icon',
        html: buildMarkerSvg(selected),
        iconSize: [30, 40],
        iconAnchor: [15, 38],
        popupAnchor: [0, -34],
    });
};
const buildPopupHtml = (p) => {
    const distance = Number.isFinite(Number(p.distance_meters))
        ? `<div style="margin-top:4px;color:${tealDark};font-weight:600">${Number(p.distance_meters) >= 1000
            ? `${(Number(p.distance_meters) / 1000).toFixed(1)} km`
            : `${Math.round(Number(p.distance_meters))} m`}</div>`
        : '';
    return `
		<div style="font-family:inherit;min-width:200px;max-width:260px">
			<div style="font-weight:700;color:${teal};font-size:0.9rem;line-height:1.2">${escapeHtml(p.name)}</div>
			<div style="margin-top:4px;color:#374151;font-size:0.8125rem;line-height:1.3">
				${escapeHtml(p.address)}<br>${escapeHtml(p.zip_code)} ${escapeHtml(p.city)}
			</div>
			${distance}
		</div>`;
};
const renderPoints = () => {
    if (!mapInstance || !LeafletNS)
        return;
    pointsLayer?.clearLayers();
    markerByKey.clear();
    if (!pointsLayer) {
        pointsLayer = LeafletNS.layerGroup().addTo(mapInstance);
    }
    const items = validPoints.value;
    if (!items.length)
        return;
    items.forEach((p) => {
        const lat = Number(p.latitude);
        const lng = Number(p.longitude);
        const isSelected = props.selectedKey != null && String(props.selectedKey) === String(p.ui_key);
        const icon = buildIcon(isSelected);
        const marker = LeafletNS.marker([lat, lng], icon ? { icon } : undefined);
        marker.bindPopup(buildPopupHtml(p));
        marker.on('click', () => {
            emit('select', p);
        });
        marker.addTo(pointsLayer);
        markerByKey.set(String(p.ui_key), marker);
    });
};
const renderReference = () => {
    if (!mapInstance || !LeafletNS)
        return;
    referenceLayer?.clearLayers();
    if (!props.referencePoint)
        return;
    if (!referenceLayer)
        referenceLayer = LeafletNS.layerGroup().addTo(mapInstance);
    const ref = props.referencePoint;
    const ringIcon = LeafletNS.divIcon({
        className: 'pudo-reference-icon',
        html: `<div style="width:18px;height:18px;border-radius:50%;background:${orange};border:3px solid #fff;box-shadow:0 0 0 2px ${orange}"></div>`,
        iconSize: [18, 18],
        iconAnchor: [9, 9],
    });
    LeafletNS.marker([ref.latitude, ref.longitude], { icon: ringIcon, interactive: false })
        .addTo(referenceLayer);
};
const fitToContent = () => {
    if (!mapInstance || !LeafletNS)
        return;
    const items = validPoints.value;
    const ref = props.referencePoint;
    const coords = items.map((p) => [Number(p.latitude), Number(p.longitude)]);
    if (ref)
        coords.push([ref.latitude, ref.longitude]);
    if (!coords.length) {
        mapInstance.setView(ITALY_CENTER, ITALY_ZOOM);
        return;
    }
    if (coords.length === 1) {
        const onlyCoordinate = coords[0];
        if (onlyCoordinate)
            mapInstance.setView(onlyCoordinate, 14);
        return;
    }
    const bounds = LeafletNS.latLngBounds(coords);
    mapInstance.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
};
const focusSelected = () => {
    if (!props.selectedKey || !mapInstance)
        return;
    const marker = markerByKey.get(String(props.selectedKey));
    if (!marker)
        return;
    mapInstance.setView(marker.getLatLng(), Math.max(mapInstance.getZoom(), 14));
    marker.openPopup();
};
onMounted(async () => {
    if (!mapEl.value)
        return;
    try {
        const mod = await import('leaflet');
        await import('leaflet/dist/leaflet.css');
        LeafletNS = (mod.default || mod);
        mapInstance = LeafletNS.map(mapEl.value, {
            center: ITALY_CENTER,
            zoom: ITALY_ZOOM,
            zoomControl: true,
            scrollWheelZoom: true,
        });
        const tile = LeafletNS.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        });
        tile.on('tileerror', () => { tileError.value = true; });
        tile.addTo(mapInstance);
        ready.value = true;
        renderPoints();
        renderReference();
        fitToContent();
    }
    catch {
        tileError.value = true;
    }
});
onBeforeUnmount(() => {
    pointsLayer?.clearLayers();
    referenceLayer?.clearLayers();
    mapInstance?.remove();
    mapInstance = null;
    pointsLayer = null;
    referenceLayer = null;
    markerByKey.clear();
    LeafletNS = null;
});
watch(() => props.points, () => {
    renderPoints();
    fitToContent();
}, { deep: false });
watch(() => props.referencePoint, () => {
    renderReference();
    fitToContent();
}, { deep: true });
watch(() => props.selectedKey, () => {
    renderPoints();
    focusSelected();
});
</script>

<template>
	<div class="relative w-full h-full min-h-[400px] rounded-[16px] overflow-hidden border border-[var(--color-brand-border,#E9EBEC)] bg-[#F2F8F9]">
		<div ref="mapEl" class="absolute inset-0" :aria-busy="!ready" aria-label="Mappa punti BRT" role="application"/>
		<div
			v-if="tileError"
			class="absolute inset-x-3 bottom-3 z-[400] rounded-[10px] bg-white/95 px-3 py-2 text-[0.75rem] text-[var(--color-brand-text-secondary,#4b5563)] border border-[var(--color-brand-border,#E9EBEC)]">
			Tile mappa non disponibili. Verifica la connessione.
		</div>
	</div>
</template>

<style scoped>
:deep(.leaflet-container) {
	font-family: inherit;
	background: #F2F8F9;
}
:deep(.leaflet-popup-content-wrapper) {
	border-radius: 12px;
	box-shadow: 0 8px 24px rgba(9, 88, 102, 0.18);
}
:deep(.leaflet-popup-tip) {
	box-shadow: none;
}
:deep(.pudo-marker-icon) {
	background: transparent;
	border: 0;
}
</style>
