/**
 * @file presentation — Utility presentation.
 */
export const getShipmentFlowHeroTitle = () => "Preventivo";

export const getShipmentFlowHeroDescription = () =>
	"Colli, servizi, indirizzi e pagamento restano nello stesso flusso, con passaggi chiari e modificabili senza perdere il contesto.";

export const getVisiblePackageItems = ({ editablePackages, sessionPackages }) => {
	if (Array.isArray(editablePackages) && editablePackages.length > 0) return editablePackages;
	return Array.isArray(sessionPackages) ? sessionPackages : [];
};

export const formatColloLabel = (packageItems) => {
	const count = Array.isArray(packageItems) && packageItems.length > 0 ? packageItems.length : 1;
	return `${count} coll${count === 1 ? "o" : "i"}`;
};

export const formatTrattaLabel = (originCity, destinationCity) =>
	`${originCity || "Da definire"} -> ${destinationCity || "Da definire"}`;

export const formatPackageAccordionSummary = (packageLabel, dimensionsLabel) => {
	const parts = [packageLabel, dimensionsLabel].filter(Boolean);
	return parts.length ? parts.join(" \u00B7 ") : "Tipo, quantit\u00E0 e misure";
};

export const collectSelectedServiceItems = ({
	featuredService,
	regularServices,
	smsEmailNotification,
	notificationPriceLabel,
}) => {
	const items = [];
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

export const formatSelectedServiceSummary = (selectedServiceItems) => {
	const labels = [...new Set((selectedServiceItems || []).map((item) => String(item?.label || "").trim()).filter(Boolean))];
	if (!labels.length) return "";
	const visible = labels.slice(0, 2);
	const remaining = labels.length - visible.length;
	return remaining > 0 ? `${visible.join(", ")} +${remaining}` : visible.join(", ");
};

export const formatServicesAccordionSummary = ({
	pickupDate,
	selectedServiceSummary,
	resolvedContentDescription,
}) => {
	const parts = [];
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
}) => {
	if (deliveryMode === "pudo") {
		if (summaryOriginCity && pudoName) return `${summaryOriginCity} \u00B7 ${pudoName}`;
		if (summaryOriginCity) return `${summaryOriginCity} \u00B7 Punto BRT`;
		return "Mittente e punto BRT";
	}
	if (summaryOriginCity && summaryDestinationCity) return `${summaryOriginCity} -> ${summaryDestinationCity}`;
	if (summaryOriginCity) return `${summaryOriginCity} \u00B7 Destinazione da completare`;
	return "Mittente e destinatario";
};

export const formatPickupDate = (pickupDate) => String(pickupDate || "").trim() || "Da definire";

export const formatConfirmationContact = (contactName, fallbackLabel) =>
	String(contactName || "").trim() || fallbackLabel;

export const formatPaymentSummaryServicesLabel = (selectedServiceItems) => {
	const list = selectedServiceItems?.length ? selectedServiceItems : [{ label: "Nessun extra selezionato", price: "" }];
	const labels = list.map((item) => String(item?.label || "").trim()).filter(Boolean);
	if (!labels.length) return "Nessun extra selezionato";
	if (labels.length <= 2) return labels.join(" \u00B7 ");
	return `${labels.slice(0, 2).join(" \u00B7 ")} +${labels.length - 2}`;
};

export const formatPaymentMethodLabel = (paymentMethod) =>
	paymentMethod === "bonifico" ? "Bonifico" : paymentMethod === "wallet" ? "Wallet" : "Carta";

export const formatPaymentDeliveryLabel = (deliveryMode) =>
	deliveryMode === "pudo" ? "Consegna in Punto BRT" : "Consegna a domicilio";
