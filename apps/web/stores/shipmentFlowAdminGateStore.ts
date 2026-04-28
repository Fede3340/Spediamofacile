/**
 * shipmentFlowAdminGateStore — challenge admin per accessi fuori flusso.
 * Mostra modal "verifica" quando un admin tenta di entrare nel funnel
 * spedizione da una rotta non canonica (vedi middleware/shipment-validation).
 */
import { defineStore } from 'pinia';

export const useShipmentFlowAdminGateStore = defineStore('shipmentFlowAdminGate', () => {
    const challenge = ref(null);

    function openGate(payload = {}) {
        challenge.value = {
            targetPath: payload?.targetPath || '/',
            lastValidRoute: payload?.lastValidRoute || '/preventivo',
            reason: payload?.reason || 'accesso fuori flusso',
            createdAt: Date.now(),
        };
    }
    function closeGate() {
        challenge.value = null;
    }

    return { challenge, openGate, closeGate };
});
