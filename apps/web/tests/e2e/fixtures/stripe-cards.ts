/**
 * Stripe test cards fixtures.
 *
 * Riferimento ufficiale: https://docs.stripe.com/testing#cards
 * Usare SOLO in test E2E con chiavi test (pk_test_/sk_test_). In produzione
 * queste carte falliscono con `card_declined`.
 */

export interface StripeTestCard {
	readonly number: string;
	readonly cvc: string;
	readonly exp: string; // MM/YY (data futura relativa alla run)
	readonly brand: 'visa' | 'mastercard' | 'amex';
	readonly label: string;
	readonly expectedOutcome: 'succeeded' | 'declined' | 'requires_action';
}

// Data scadenza sempre 4 anni avanti dalla run (evita scadenze fisse).
const futureExpiry = (): string => {
	const date = new Date();
	const year = (date.getFullYear() + 4).toString().slice(-2);
	return `12/${year}`;
};

export const stripeCards = {
	valid: {
		number: '4242 4242 4242 4242',
		cvc: '123',
		exp: futureExpiry(),
		brand: 'visa',
		label: 'Visa successo',
		expectedOutcome: 'succeeded',
	},
	declined: {
		number: '4000 0000 0000 0002',
		cvc: '123',
		exp: futureExpiry(),
		brand: 'visa',
		label: 'Visa declined (generic_decline)',
		expectedOutcome: 'declined',
	},
	insufficientFunds: {
		number: '4000 0000 0000 9995',
		cvc: '123',
		exp: futureExpiry(),
		brand: 'visa',
		label: 'Fondi insufficienti',
		expectedOutcome: 'declined',
	},
	requires3DS: {
		number: '4000 0027 6000 3184',
		cvc: '123',
		exp: futureExpiry(),
		brand: 'visa',
		label: '3D Secure richiesto',
		expectedOutcome: 'requires_action',
	},
} satisfies Record<string, StripeTestCard>;

export type StripeCardKey = keyof typeof stripeCards;
