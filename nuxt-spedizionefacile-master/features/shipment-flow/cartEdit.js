import { computed, ref } from "vue";

export const useShipmentStepCartEdit = ({
	sanctumClient,
	session,
	syncSelectedServicesVisual,
	shipmentFlowStore,
}) => {
	const route = useRoute();
	const rawEditParam = Array.isArray(route.query.edit)
		? route.query.edit[0]
		: (route.query.edit ?? route.query.edit_id);
	const parsedEditCartId = Number(rawEditParam);
	const editCartId = Number.isInteger(parsedEditCartId) && parsedEditCartId > 0 ? parsedEditCartId : null;
	const loadingEditData = ref(!!editCartId);

	const editablePackages = computed(() => {
		if (shipmentFlowStore?.packages?.length) return shipmentFlowStore?.packages;
		if (editCartId && shipmentFlowStore?.packages?.length > 0 && !session.value?.data?.packages?.length) {
			return shipmentFlowStore?.packages;
		}
		if (session.value?.data?.packages?.length) return session.value.data.packages;
		return [];
	});

	const populateAddress = (target, source) => {
		if (!source) return;
		target.value.full_name = source.name || "";
		target.value.address = source.address || "";
		target.value.address_number = source.address_number || "";
		target.value.city = source.city || "";
		target.value.postal_code = source.postal_code || "";
		target.value.province = source.province || "";
		target.value.telephone_number = source.telephone_number || "";
		target.value.email = source.email || "";
		target.value.additional_information = source.additional_information || "";
		target.value.intercom_code = source.intercom_code || "";
	};

	const clearInvalidEditState = async () => {
		shipmentFlowStore.editingCartItemId = null;
		const nextQuery = { ...route.query };
		delete nextQuery.edit;
		delete nextQuery.edit_id;
		await navigateTo({ path: route.path, query: nextQuery }, { replace: true });
	};

	const loadCartItemForEdit = async ({ originAddress, destinationAddress, services, showAddressFields }) => {
		if (!editCartId) return;
		try {
			const result = await sanctumClient(`/api/cart/${editCartId}`);
			const item = result?.data || result;

			shipmentFlowStore.editingCartItemId = editCartId;

			populateAddress(originAddress, item.origin_address);
			populateAddress(destinationAddress, item.destination_address);

			if (item.services) {
				services.value.date = item.services.date || "";
				services.value.time = item.services.time || "";
				services.value.service_type = item.services.service_type || "";
				shipmentFlowStore.pickupDate = item.services.date || "";

				const serviceTypes = (item.services.service_type || "")
					.split(", ")
					.filter((service) => service && service !== "Nessuno");
				shipmentFlowStore.servicesArray = serviceTypes;
				syncSelectedServicesVisual();
			}

			if (item.content_description) {
				shipmentFlowStore.contentDescription = item.content_description;
			}

			if (item.services?.serviceData) {
				shipmentFlowStore.serviceData = { ...item.services.serviceData };
			}

			const priceInEuro = item.single_price ? (Number(item.single_price) / 100) : 0;
			shipmentFlowStore.packages = [{
				package_type: item.package_type || "Pacco",
				quantity: item.quantity || 1,
				weight: item.weight,
				first_size: item.first_size,
				second_size: item.second_size,
				third_size: item.third_size,
				weight_price: item.weight_price,
				volume_price: item.volume_price,
				single_price: priceInEuro,
			}];

			shipmentFlowStore.shipmentDetails = {
				origin_city: item.origin_address?.city || "",
				origin_postal_code: item.origin_address?.postal_code || "",
				destination_city: item.destination_address?.city || "",
				destination_postal_code: item.destination_address?.postal_code || "",
				date: item.services?.date || "",
			};

			showAddressFields.value = true;
		} catch (error) {
			shipmentFlowStore.editingCartItemId = null;
			const statusCode = Number(error?.response?.status || error?.statusCode || 0);
			if (statusCode === 404) {
				await clearInvalidEditState();
			}
		} finally {
			loadingEditData.value = false;
		}
	};

	return {
		editCartId,
		editablePackages,
		loadCartItemForEdit,
		loadingEditData,
	};
};
