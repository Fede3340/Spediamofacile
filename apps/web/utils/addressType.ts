/**
 * Tipi indirizzo canonici per il funnel spedizione.
 * I valori italiani 'Partenza'/'Destinazione' sono il contratto API col backend
 * (vedi `app/Models/PackageAddress::type`); le label inglesi qui servono solo
 * per chiamate runtime frontend uniformi.
 */
export const ADDRESS_TYPE = Object.freeze({
  ORIGIN: 'Partenza',
  DESTINATION: 'Destinazione',
} as const);

export type AddressType = (typeof ADDRESS_TYPE)[keyof typeof ADDRESS_TYPE];

export const ADDRESS_TYPE_LABEL: Record<AddressType, string> = Object.freeze({
  Partenza: 'Indirizzo di partenza',
  Destinazione: 'Indirizzo di destinazione',
});
