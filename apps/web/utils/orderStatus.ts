/**
 * Order status canonici (allineati a `App\Models\Order::*` backend).
 * Usa SEMPRE le costanti, mai stringhe magiche, nei confronti runtime.
 */
export const ORDER_STATUS = Object.freeze({
  PENDING: 'pending',
  PROCESSING: 'processing',
  PAID: 'paid',
  PAYMENT_FAILED: 'payment_failed',
  AWAITING_BANK_TRANSFER: 'awaiting_bank_transfer',
  LABEL_GENERATED: 'label_generated',
  IN_TRANSIT: 'in_transit',
  OUT_FOR_DELIVERY: 'out_for_delivery',
  DELIVERED: 'delivered',
  COMPLETED: 'completed',
  IN_GIACENZA: 'in_giacenza',
  RETURNED: 'returned',
  REFUSED: 'refused',
  CANCELLED: 'cancelled',
  REFUNDED: 'refunded',
} as const);

export type OrderStatus = (typeof ORDER_STATUS)[keyof typeof ORDER_STATUS];

export const ORDER_STATUS_LABEL: Record<OrderStatus, string> = Object.freeze({
  pending: 'In attesa',
  processing: 'In lavorazione',
  paid: 'Pagato',
  payment_failed: 'Pagamento fallito',
  awaiting_bank_transfer: 'In attesa di bonifico',
  label_generated: 'Etichetta generata',
  in_transit: 'In transito',
  out_for_delivery: 'In consegna',
  delivered: 'Consegnato',
  completed: 'Completato',
  in_giacenza: 'In giacenza',
  returned: 'Reso',
  refused: 'Rifiutato',
  cancelled: 'Annullato',
  refunded: 'Rimborsato',
});

/** Label IT per uno status backend; fallback "Sconosciuto" se non mappato. */
export const labelForStatus = (status: string | null | undefined): string =>
  (status && (ORDER_STATUS_LABEL as Record<string, string>)[status]) || 'Sconosciuto';

/** Inverso: dato un label IT, restituisce lo status backend (case-insensitive). */
export const statusFromLabel = (label: string | null | undefined): OrderStatus | null => {
  if (!label) return null;
  const target = label.trim().toLowerCase();
  for (const [status, lbl] of Object.entries(ORDER_STATUS_LABEL)) {
    if (lbl.toLowerCase() === target) return status as OrderStatus;
  }
  return null;
};
