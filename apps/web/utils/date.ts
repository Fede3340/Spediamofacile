/**
 * @file date — Utility date.
 */
const ITALY_TIME_ZONE = 'Europe/Rome';
const INVALID_DATE_LABEL = 'Data non disponibile';

const createFormatter = (options) =>
	new Intl.DateTimeFormat('it-IT', {
		timeZone: ITALY_TIME_ZONE,
		...options,
	});

const dateFormatter = createFormatter({
	day: '2-digit',
	month: 'short',
	year: 'numeric',
});

const dateTimeFormatter = createFormatter({
	day: '2-digit',
	month: 'short',
	year: 'numeric',
	hour: '2-digit',
	minute: '2-digit',
});

const parseItalianDateString = (value) => {
	if (typeof value !== 'string') return null;
	const raw = value.trim();
	if (!raw) return null;

	const match = raw.match(
		/^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:[,\s]+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/,
	);
	if (!match) return null;

	const [, day, month, year, hours = '0', minutes = '0', seconds = '0'] = match;
	const parsed = new Date(
		Number(year),
		Number(month) - 1,
		Number(day),
		Number(hours),
		Number(minutes),
		Number(seconds),
	);

	return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const toValidDate = (value) => {
	if (!value) return null;
	if (value instanceof Date) {
		return Number.isNaN(value.getTime()) ? null : value;
	}

	const italianDate = parseItalianDateString(value);
	if (italianDate) return italianDate;

	const parsed = new Date(value);
	return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const formatWithFallback = (formatter, value, fallback = INVALID_DATE_LABEL) => {
	const parsed = toValidDate(value);
	return parsed ? formatter.format(parsed) : fallback;
};

export const formatDateIt = (value, fallback = INVALID_DATE_LABEL) => formatWithFallback(dateFormatter, value, fallback);

export const formatDateTimeIt = (value, fallback = INVALID_DATE_LABEL) => formatWithFallback(dateTimeFormatter, value, fallback);
