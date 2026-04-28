/**
 * @file checkoutSubmissionContext — Utility checkoutSubmissionContext.
 */
import {
	createClientSubmissionId,
	ensureClientSubmissionId,
	readClientSubmissionId,
	readNestedClientSubmissionId,
} from '~/utils/shipment'

/**
 * @typedef {Record<string, unknown> | null | undefined} MaybeRecord
 */

/**
 * @typedef {Object} BuildContextInput
 * @property {boolean} [preferExisting]
 * @property {boolean} [generate]
 * @property {MaybeRecord} [existingOrder]
 * @property {string|number|null} [existingOrderId]
 * @property {MaybeRecord} [pendingShipment]
 * @property {MaybeRecord} [sessionData]
 * @property {MaybeRecord} [cachedContext]
 * @property {string|null} [localSubmissionId]
 * @property {number} [total]
 * @property {MaybeRecord} [billingPayload]
 */

/**
 * @typedef {Object} SubmissionContextResult
 * @property {string} clientSubmissionId
 * @property {{ signature: string, client_submission_id: string }} context
 */

// Boundary canonico del contesto checkout lato frontend.
// Qui vive solo la logica di deduplicazione / riuso del client_submission_id
// e la signature del payload pagabile; il pagamento reale resta in usePayment.

/**
 * @param {MaybeRecord} shipmentFlowStore
 * @returns {MaybeRecord}
 */
export const readPendingShipmentDraft = (shipmentFlowStore) => (
	shipmentFlowStore?.pendingShipment && typeof shipmentFlowStore?.pendingShipment === 'object'
		? shipmentFlowStore?.pendingShipment
		: null
)

/**
 * @param {MaybeRecord} shipmentFlowStore
 * @param {unknown} submissionId
 */
export const syncPendingShipmentSubmissionId = (shipmentFlowStore, submissionId) => {
	const normalized = typeof submissionId === 'string' ? submissionId.trim() : ''
	if (!normalized) return

	const pendingShipment = readPendingShipmentDraft(shipmentFlowStore)
	if (!pendingShipment) return
	if (pendingShipment.client_submission_id === normalized) return

	shipmentFlowStore.pendingShipment = {
		...pendingShipment,
		client_submission_id: normalized,
	}
}

/**
 * @param {MaybeRecord} existingOrder
 * @returns {string|null}
 */
export const readExistingOrderSubmissionId = (existingOrder) => (
	readClientSubmissionId(existingOrder)
)

/**
 * @param {{ existingOrderId?: string|number|null, total?: number, billingPayload?: MaybeRecord }} params
 * @returns {string}
 */
export const buildCheckoutSubmissionSignature = ({
	existingOrderId,
	total = 0,
	billingPayload = null,
}) => JSON.stringify({
	existingOrderId: existingOrderId ?? null,
	total: Number(total || 0),
	billingPayload: billingPayload || null,
})

/**
 * @param {BuildContextInput} input
 * @returns {SubmissionContextResult}
 */
export const buildCheckoutSubmissionContext = ({
	preferExisting = true,
	generate = true,
	existingOrder = null,
	existingOrderId = null,
	pendingShipment = null,
	sessionData = null,
	cachedContext = null,
	localSubmissionId = null,
	total = 0,
	billingPayload = null,
}) => {
	const signature = buildCheckoutSubmissionSignature({
		existingOrderId: existingOrderId || existingOrder?.id || null,
		total,
		billingPayload,
	})

	const existingOrderSubmissionId = preferExisting
		? readExistingOrderSubmissionId(existingOrder)
		: null

	if (existingOrderSubmissionId) {
		return {
			clientSubmissionId: existingOrderSubmissionId,
			context: {
				signature,
				client_submission_id: existingOrderSubmissionId,
			},
		}
	}

	if (
		cachedContext?.signature === signature
		&& typeof cachedContext?.client_submission_id === 'string'
		&& cachedContext.client_submission_id.trim() !== ''
	) {
		const cachedSubmissionId = cachedContext.client_submission_id.trim()
		return {
			clientSubmissionId: cachedSubmissionId,
			context: {
				signature,
				client_submission_id: cachedSubmissionId,
			},
		}
	}

	const nestedKnownSubmissionId = preferExisting
		? readNestedClientSubmissionId(
			existingOrder,
			pendingShipment,
			sessionData?.pendingShipment || null,
			sessionData,
			localSubmissionId ? { client_submission_id: localSubmissionId } : null,
		)
		: readNestedClientSubmissionId(
			pendingShipment,
			sessionData?.pendingShipment || null,
			sessionData,
		)

	let submissionId = nestedKnownSubmissionId

	if (!submissionId && pendingShipment) {
		submissionId = ensureClientSubmissionId(pendingShipment)
	}

	if (!submissionId && generate) {
		submissionId = typeof localSubmissionId === 'string' && localSubmissionId.trim() !== ''
			? localSubmissionId.trim()
			: createClientSubmissionId()
	}

	return {
		clientSubmissionId: submissionId || '',
		context: {
			signature,
			client_submission_id: submissionId || '',
		},
	}
}
