import { describe, expect, it } from 'vitest';
import {
	buildCheckoutSuccessQuery,
	clearCheckoutSuccessQuery,
	readCheckoutSuccessState,
} from '~/utils/checkout';

describe('checkoutSuccess query helpers', () => {
	it('riconosce una conferma checkout valida dalla query', () => {
		expect(
			readCheckoutSuccessState({
				checkout_success: '1',
				order_ids: '1542,1543',
				payment_method: 'bonifico',
			}),
		).toEqual({
			active: true,
			orderIds: ['1542', '1543'],
			paymentMethod: 'bonifico',
		});
	});

	it('ignora query incomplete o non valide', () => {
		expect(readCheckoutSuccessState({ checkout_success: '1' })).toEqual({
			active: false,
			orderIds: [],
			paymentMethod: '',
		});
		expect(readCheckoutSuccessState({ order_ids: '1542' })).toEqual({
			active: false,
			orderIds: [],
			paymentMethod: '',
		});
	});

	it('costruisce la query di successo preservando i parametri esistenti', () => {
		expect(
			buildCheckoutSuccessQuery(
				{ order_id: '77', foo: 'bar' },
				{ orderIds: ['1542'], paymentMethod: 'wallet' },
			),
		).toEqual({
			order_id: '77',
			foo: 'bar',
			checkout_success: '1',
			order_ids: '1542',
			payment_method: 'wallet',
		});
	});

	it('pulisce solo i parametri del successo checkout', () => {
		expect(
			clearCheckoutSuccessQuery({
				order_id: '77',
				checkout_success: '1',
				order_ids: '1542',
				payment_method: 'wallet',
			}),
		).toEqual({
			order_id: '77',
		});
	});
});
