/**
 * @typedef {Record<string, unknown>} SchemaOrgEntity
 */

/**
 * Inietta uno o piu` JSON-LD schema.org nell'head della pagina.
 * @param {SchemaOrgEntity | SchemaOrgEntity[]} schema entita` o lista di entita` schema.org
 * @param {string} [key] prefisso chiave per deduplicare gli script
 */
export const useSchemaOrg = (schema, key = 'schema-org') => {
	const entities = Array.isArray(schema) ? schema : [schema];

	useHead({
		script: entities.map((entity, index) => ({
			key: `${key}-${index}`,
			type: 'application/ld+json',
			innerHTML: JSON.stringify(entity),
		})),
	});
};
