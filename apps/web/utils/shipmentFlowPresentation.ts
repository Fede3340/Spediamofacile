/**
 * @file presentation - pure UI copy helpers for the shipment funnel.
 */

type PackageLike = Record<string, unknown>;
type ServiceItem = {
	label: string;
	price: string;
};
type ServiceCardLike = {
	isSelected?: boolean;
	name?: string;
	price?: string;
};
type VisiblePackageArgs = {
	editablePackages?: PackageLike[] | null;
	sessionPackages?: PackageLike[] | null;
};
type SelectedServiceArgs = {
	featuredService?: ServiceCardLike | null;
	regularServices?: ServiceCardLike[] | null;
	smsEmailNotification?: boolean;
	notificationPriceLabel?: string;
};

export const getShipmentFlowHeroTitle = (): string => "Preventivo";

export const getShipmentFlowHeroDescription = (): string =>
	"Colli, servizi, indirizzi e pagamento restano nello stesso flusso, con passaggi chiari e modificabili senza perdere il contesto.";

export const getVisiblePackageItems = ({ editablePackages, sessionPackages }: VisiblePackageArgs): PackageLike[] => {
	if (Array.isArray(editablePackages) && editablePackages.length > 0) return editablePackages;
	return Array.isArray(sessionPackages) ? sessionPackages : [];
};

export const formatColloLabel = (packageItems?: PackageLike[] | null): string => {
	const count = Array.isArray(packageItems) && packageItems.length > 0 ? packageItems.length : 1;
	return `${count} coll${count === 1 ? "o" : "i"}`;
};

/**
 * Capitalizza un nome città mantenendo apostrofi e separatori (es. "milano due" -> "Milano Due",
 * "sant'antimo" -> "Sant'Antimo", "reggio emilia" -> "Reggio Emilia").
 */
const toCityCase = (name?: string | null): string => {
	if (!name) return "";
	return String(name)
		.toLowerCase()
		.replace(/(^|[\s'\-/])([a-zà-ÿ])/g, (_, sep: string, ch: string) => sep + ch.toUpperCase());
};

export const formatTrattaLabel = (originCity?: string | null, destinationCity?: string | null): string =>
	`${toCityCase(originCity) || "Da definire"} -> ${toCityCase(destinationCity) || "Da definire"}`;

export const formatPackageAccordionSummary = (packageLabel?: string | null, dimensionsLabel?: string | null): string => {
	const parts = [packageLabel, dimensionsLabel].filter(Boolean);
	return parts.length ? parts.join(" \u00B7 ") : "Tipo, quantita e misure";
};

export const collectSelectedServiceItems = ({
	featuredService,
	regularServices,
	smsEmailNotification,
	notificationPriceLabel,
}: SelectedServiceArgs): ServiceItem[] => {
	const items: ServiceItem[] = [];
	if (featuredService?.isSelected) {
		items.push({
			label: featuredService.name || "Senza Etichetta",
			price: featuredService.price || "",
		});
	}
	for (const service of regularServices || []) {
		if (service?.isSelected) {
			items.push({
				label: service.name || "",
				price: service.price || "",
			});
		}
	}
	if (smsEmailNotification) {
		items.push({
			label: "Notifiche SMS",
			price: notificationPriceLabel || "",
		});
	}
	return items;
};

export const formatSelectedServiceSummary = (selectedServiceItems?: ServiceItem[] | null): string => {
	const labels = [...new Set((selectedServiceItems || []).map((item) => String(item.label || "").trim()).filter(Boolean))];
	if (!labels.length) return "";
	const visible = labels.slice(0, 2);
	const remaining = labels.length - visible.length;
	return remaining > 0 ? `${visible.join(", ")} +${remaining}` : visible.join(", ");
};

export const formatServicesAccordionSummary = ({
	pickupDate,
	selectedServiceSummary,
	resolvedContentDescription,
}: {
	pickupDate?: string | null;
	selectedServiceSummary?: string | null;
	resolvedContentDescription?: string | null;
}): string => {
	const parts: string[] = [];
	if (pickupDate) parts.push(`Ritiro ${pickupDate}`);
	if (selectedServiceSummary) parts.push(selectedServiceSummary);
	if (!parts.length && resolvedContentDescription) parts.push("Contenuto inserito");
	return parts.length ? parts.slice(0, 2).join(" \u00B7 ") : "Ritiro, extra e contenuto";
};

export const formatAddressAccordionSummary = ({
	deliveryMode,
	summaryOriginCity,
	summaryDestinationCity,
	pudoName,
}: {
	deliveryMode?: string | null;
	summaryOriginCity?: string | null;
	summaryDestinationCity?: string | null;
	pudoName?: string | null;
}): string => {
	const origin = toCityCase(summaryOriginCity);
	const destination = toCityCase(summaryDestinationCity);
	const pudo = toCityCase(pudoName);
	if (deliveryMode === "pudo") {
		if (origin && pudo) return `${origin} \u00B7 ${pudo}`;
		if (origin) return `${origin} \u00B7 Punto BRT`;
		return "Mittente e punto BRT";
	}
	if (origin && destination) return `${origin} -> ${destination}`;
	if (origin) return `${origin} \u00B7 Destinazione da completare`;
	return "Mittente e destinatario";
};

export const formatPickupDate = (pickupDate?: string | null): string => String(pickupDate || "").trim() || "Da definire";

export const formatConfirmationContact = (contactName: unknown, fallbackLabel: string): string =>
	String(contactName || "").trim() || fallbackLabel;

export const formatPaymentSummaryServicesLabel = (selectedServiceItems?: ServiceItem[] | null): string => {
	const list = selectedServiceItems?.length ? selectedServiceItems : [{ label: "Nessun extra selezionato", price: "" }];
	const labels = list.map((item) => String(item.label || "").trim()).filter(Boolean);
	if (!labels.length) return "Nessun extra selezionato";
	if (labels.length <= 2) return labels.join(" \u00B7 ");
	return `${labels.slice(0, 2).join(" \u00B7 ")} +${labels.length - 2}`;
};

export const formatPaymentMethodLabel = (paymentMethod?: string | null): string =>
	paymentMethod === "bonifico" ? "Bonifico" : paymentMethod === "wallet" ? "Wallet" : "Carta";

export const formatPaymentDeliveryLabel = (deliveryMode?: string | null): string =>
	deliveryMode === "pudo" ? "Consegna in Punto BRT" : "Consegna a domicilio";
