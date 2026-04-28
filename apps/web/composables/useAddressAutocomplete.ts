/**
 * useLocation.js - Aggregatore ricerca geografica:
 *   useLocationSearch      → città/CAP via /api/locations/*
 *   useAddressAutocomplete → autocomplete + validazione coerenza CAP/città/prov
 *   useAddressPudo         → selezione punto PUDO BRT
 * ARCHIVIATO: _archive/cleanup-features-2026-04-20/composables-consolidati-location-address/
 */
import { ref, reactive, watch } from "vue";
import { dedupeLocations, getProvinceLabel, locationKey, normalizeLocationText } from "~/utils/location";

// ─────────────────────────────────────────────────────────────────────────────
// SEZIONE 1: LocationSearch (ex useLocationSearch)
// Ricerca città/CAP generica via API pubblica /api/locations/*
// ─────────────────────────────────────────────────────────────────────────────

export function useAddressAutocomplete({
  originAddress,
  destinationAddress,
  deliveryMode,
  sv,
  sanctumClient,
}) {
  // --- SUGGESTION REFS ---
  const originProvinceSuggestions = ref([]);
  const destProvinceSuggestions = ref([]);
  const originCitySuggestions = ref([]);
  const destCitySuggestions = ref([]);
  const originCapSuggestions = ref([]);
  const destCapSuggestions = ref([]);

  // --- DEBOUNCE/SEQUENCING STATE ---
  const citySearchTimeout = { origin: null, dest: null };
  const capSearchTimeout = { origin: null, dest: null };

  // Cleanup debounce: evita fetch su scope smontata se utente naviga via durante typing.
  onScopeDispose(() => {
    if (citySearchTimeout.origin) clearTimeout(citySearchTimeout.origin);
    if (citySearchTimeout.dest) clearTimeout(citySearchTimeout.dest);
    if (capSearchTimeout.origin) clearTimeout(capSearchTimeout.origin);
    if (capSearchTimeout.dest) clearTimeout(capSearchTimeout.dest);
  });
  const citySearchSeq = reactive({ origin: 0, dest: 0 });
  const capSearchSeq = reactive({ origin: 0, dest: 0 });
  const locationLinkHints = reactive({ origin: [], dest: [] });

  // --- HELPERS ---
  const normalizeLocationText = (value = "") =>
    String(value)
      .normalize("NFD")
      .replace(/[\u0300-\u036F]/g, "")
      .toLowerCase()
      .trim();

  const getSectionAddress = (section) =>
    section === "origin" ? originAddress.value : destinationAddress.value;

  const setSectionCitySuggestions = (section, suggestions) => {
    if (section === "origin") originCitySuggestions.value = suggestions;
    else destCitySuggestions.value = suggestions;
  };

  const setSectionCapSuggestions = (section, suggestions) => {
    if (section === "origin") originCapSuggestions.value = suggestions;
    else destCapSuggestions.value = suggestions;
  };

  const setSectionProvinceSuggestions = (section, suggestions) => {
    if (section === "origin") originProvinceSuggestions.value = suggestions;
    else destProvinceSuggestions.value = suggestions;
  };

  // --- FORMATTERS ---
  const formatCitySuggestionLabel = (location) => {
    const province = getProvinceLabel(location);
    if (province) return `${location.place_name} (${province}) - ${location.postal_code}`;
    return `${location.place_name} - ${location.postal_code}`;
  };

  const formatCapSuggestionLabel = (location) => {
    const province = getProvinceLabel(location);
    if (province) return `${location.postal_code} - ${location.place_name} (${province})`;
    return `${location.postal_code} - ${location.place_name}`;
  };

  // --- DEDUPE (local: mantiene comportamento originale) ---
  const dedupeLocations = (locations) => {
    if (!Array.isArray(locations)) return [];
    const seen = new Set();
    const result = [];
    for (const loc of locations) {
      const key = `${String(loc?.postal_code || "").trim()}|${normalizeLocationText(loc?.place_name)}|${getProvinceLabel(loc)}`;
      if (!key || seen.has(key)) continue;
      seen.add(key);
      result.push(loc);
    }
    return result;
  };

  const setProvinceSuggestionsFromLocations = (section, locations) => {
    const provinces = [...new Set(
      dedupeLocations(locations)
        .map((loc) => getProvinceLabel(loc))
        .filter(Boolean)
    )].sort();
    setSectionProvinceSuggestions(section, provinces.slice(0, 20));
  };

  const isLocationCoherent = (location, city, province) => {
    const cityNorm = normalizeLocationText(city);
    const provinceNorm = normalizeLocationText(province);
    const locCityNorm = normalizeLocationText(location?.place_name);
    const locProvinceNorm = normalizeLocationText(getProvinceLabel(location));
    if (cityNorm && locCityNorm !== cityNorm) return false;
    if (provinceNorm && locProvinceNorm !== provinceNorm) return false;
    return true;
  };

  const applyLocationToSection = (section, location) => {
    const addr = getSectionAddress(section);
    addr.city = location.place_name || addr.city;
    addr.postal_code = String(location.postal_code || addr.postal_code || "");
    const province = getProvinceLabel(location);
    if (province) addr.province = province;
    setSectionCitySuggestions(section, []);
    setSectionCapSuggestions(section, []);
    setSectionProvinceSuggestions(section, []);
    sv.clearError(`${section}_city`);
    sv.clearError(`${section}_postal_code`);
    sv.clearError(`${section}_province`);
  };

  // --- PROVINCIA INPUT/SELECT ---
  const onProvinciaInput = (section, value) => {
    const filtered = sv.filterProvincia(value);
    const contextualLocations = dedupeLocations([
      ...(section === "origin" ? originCitySuggestions.value : destCitySuggestions.value),
      ...(section === "origin" ? originCapSuggestions.value : destCapSuggestions.value),
    ]);
    const contextualProvinces = [...new Set(
      contextualLocations
        .map((loc) => getProvinceLabel(loc))
        .filter(Boolean)
    )].filter((prov) => prov.startsWith(filtered));
    const provinceSuggestions = contextualProvinces.length > 0
      ? contextualProvinces.slice(0, 20)
      : sv.getProvinceSuggestions(filtered);

    if (section === "origin") {
      originAddress.value.province = filtered;
      originProvinceSuggestions.value = provinceSuggestions;
    } else {
      destinationAddress.value.province = filtered;
      destProvinceSuggestions.value = provinceSuggestions;
    }
    sv.onInput(`${section}_province`, () => sv.validateProvincia(`${section}_province`, filtered));
  };

  const selectProvincia = (section, prov) => {
    if (section === "origin") {
      originAddress.value.province = prov;
      originProvinceSuggestions.value = [];
    } else {
      destinationAddress.value.province = prov;
      destProvinceSuggestions.value = [];
    }
    sv.clearError(`${section}_province`);
    void validateAddressLocationLink(section);
  };

  // --- CAP SUGGESTIONS FROM CITY ---
  const loadCapSuggestionsFromCity = async (section, cityValue) => {
    const city = String(cityValue || "").trim();
    if (city.length < 2) return;
    try {
      const results = await sanctumClient(`/api/locations/by-city?city=${encodeURIComponent(city)}&limit=300`);
      const cityNorm = normalizeLocationText(city);
      const filtered = dedupeLocations(results)
        .filter((loc) => normalizeLocationText(loc.place_name).startsWith(cityNorm))
        .sort((a, b) => String(a.postal_code).localeCompare(String(b.postal_code)));
      setSectionCapSuggestions(section, filtered.slice(0, 40));
      setProvinceSuggestionsFromLocations(section, filtered);
    } catch (error) {
      // silent: autocomplete suggestions are non-critical
    }
  };

  // --- FOCUS HANDLERS ---
  const onCityFocus = (section) => {
    const addr = getSectionAddress(section);
    if (addr.city && String(addr.city).trim().length >= 2) {
      void onCityInput(section, addr.city, { immediate: true });
    }
  };

  const onCapFocus = (section) => {
    const addr = getSectionAddress(section);
    const cap = String(addr.postal_code || "");
    if (cap.length >= 3) {
      void onCapInput(section, cap, { immediate: true });
      return;
    }
    if (String(addr.city || "").trim().length >= 2) {
      void loadCapSuggestionsFromCity(section, addr.city);
    }
  };

  const onProvinceFocus = (section) => {
    const addr = getSectionAddress(section);
    const filtered = sv.filterProvincia(addr.province || "");
    onProvinciaInput(section, filtered);
    if (!filtered && String(addr.postal_code || "").length >= 3) {
      void onCapInput(section, addr.postal_code, { immediate: true });
    }
    if (!filtered && String(addr.city || "").trim().length >= 2) {
      void onCityInput(section, addr.city, { immediate: true });
    }
  };

  // --- CITY AUTOCOMPLETE ---
  const onCityInput = async (section, value, options = {}) => {
    clearTimeout(citySearchTimeout[section]);

    sv.onInput(`${section}_city`, () => {
      if (!value || !String(value).trim()) {
        sv.setError(`${section}_city`, "Città è obbligatoria");
      } else {
        sv.clearError(`${section}_city`);
      }
    });

    if (!value || value.length < 2) {
      setSectionCitySuggestions(section, []);
      return;
    }

    const delay = options.immediate ? 0 : 260;
    citySearchTimeout[section] = setTimeout(async () => {
      const seq = ++citySearchSeq[section];
      try {
        const results = await sanctumClient(`/api/locations/by-city?city=${encodeURIComponent(value)}&limit=300`);
        if (seq !== citySearchSeq[section]) return;

        const queryNorm = normalizeLocationText(value);
        const addr = getSectionAddress(section);
        const capPrefix = String(addr.postal_code || "");
        const provincePrefix = normalizeLocationText(addr.province || "");

        let suggestions = dedupeLocations(results).filter((loc) =>
          normalizeLocationText(loc.place_name).startsWith(queryNorm)
        );

        if (capPrefix.length >= 3) {
          suggestions = suggestions.filter((loc) =>
            String(loc.postal_code || "").startsWith(capPrefix)
          );
        }

        if (provincePrefix.length === 2) {
          suggestions = suggestions.filter((loc) =>
            normalizeLocationText(getProvinceLabel(loc)) === provincePrefix
          );
        }

        suggestions.sort((a, b) => {
          const aName = normalizeLocationText(a.place_name);
          const bName = normalizeLocationText(b.place_name);
          const aExact = aName === queryNorm ? 0 : 1;
          const bExact = bName === queryNorm ? 0 : 1;
          if (aExact !== bExact) return aExact - bExact;
          if (aName.length !== bName.length) return aName.length - bName.length;
          if (aName !== bName) return aName.localeCompare(bName);
          return String(a.postal_code || "").localeCompare(String(b.postal_code || ""));
        });

        setSectionCitySuggestions(section, suggestions.slice(0, 25));
        setProvinceSuggestionsFromLocations(section, suggestions);

        if (capPrefix.length >= 3) {
          setSectionCapSuggestions(
            section,
            suggestions
              .filter((loc) => String(loc.postal_code || "").startsWith(capPrefix))
              .slice(0, 40)
          );
        }
      } catch (error) {
        setSectionCitySuggestions(section, []);
      }
    }, delay);
  };

  const selectCity = (section, location) => {
    applyLocationToSection(section, location);
  };

  const selectCap = (section, location) => {
    applyLocationToSection(section, location);
  };

  // --- NAME INPUT ---
  const onNameInput = (section, value) => {
    const capitalized = sv.autoCapitalize(value);
    if (section === "origin") {
      originAddress.value.full_name = capitalized;
    } else {
      destinationAddress.value.full_name = capitalized;
    }
    sv.onInput(`${section}_full_name`, () => sv.validateNomeCognome(`${section}_full_name`, capitalized));
  };

  // --- CAP INPUT ---
  const onCapInput = async (section, value, options = {}) => {
    clearTimeout(capSearchTimeout[section]);
    const filtered = sv.filterCAP(value);
    if (section === "origin") {
      originAddress.value.postal_code = filtered;
    } else {
      destinationAddress.value.postal_code = filtered;
    }
    sv.onInput(`${section}_postal_code`, () => sv.validateCAP(`${section}_postal_code`, filtered));

    if (!filtered || filtered.length < 3) {
      setSectionCapSuggestions(section, []);
      return;
    }

    const delay = options.immediate ? 0 : 220;
    capSearchTimeout[section] = setTimeout(async () => {
      const seq = ++capSearchSeq[section];
      const addr = getSectionAddress(section);
      const cityNorm = normalizeLocationText(addr.city);
      const provinceNorm = normalizeLocationText(addr.province);

      try {
        let results = [];
        if (filtered.length === 5) {
          results = await sanctumClient(`/api/locations/by-cap?cap=${encodeURIComponent(filtered)}`);
        } else {
          results = await sanctumClient(`/api/locations/search?q=${encodeURIComponent(filtered)}&limit=300`);
        }
        if (seq !== capSearchSeq[section]) return;

        let suggestions = dedupeLocations(results).filter((loc) =>
          String(loc.postal_code || "").startsWith(filtered)
        );

        if (cityNorm.length >= 2) {
          suggestions = suggestions.filter((loc) =>
            normalizeLocationText(loc.place_name).startsWith(cityNorm)
          );
        }

        if (provinceNorm.length === 2) {
          suggestions = suggestions.filter((loc) =>
            normalizeLocationText(getProvinceLabel(loc)) === provinceNorm
          );
        }

        suggestions.sort((a, b) => {
          const aCap = String(a.postal_code || "");
          const bCap = String(b.postal_code || "");
          if (aCap !== bCap) return aCap.localeCompare(bCap);
          return normalizeLocationText(a.place_name).localeCompare(normalizeLocationText(b.place_name));
        });

        setSectionCapSuggestions(section, suggestions.slice(0, 40));
        setProvinceSuggestionsFromLocations(section, suggestions);

        if (filtered.length === 5) {
          const exactCoherent = suggestions.find((loc) =>
            isLocationCoherent(loc, addr.city, addr.province)
          );
          if (exactCoherent) {
            applyLocationToSection(section, exactCoherent);
          } else if (!addr.city && suggestions.length === 1) {
            applyLocationToSection(section, suggestions[0]);
          }
        }
      } catch (error) {
        setSectionCapSuggestions(section, []);
      }
    }, delay);
  };

  // --- TELEFONO INPUT ---
  const onTelefonoInput = (section, value) => {
    const formatted = sv.formatTelefono(value);
    if (section === "origin") {
      originAddress.value.telephone_number = formatted;
    } else {
      destinationAddress.value.telephone_number = formatted;
    }
    sv.onInput(`${section}_telephone_number`, () => sv.validateTelefono(`${section}_telephone_number`, formatted));
  };

  // --- SMART BLUR ---
  const smartBlur = (section, field) => {
    const key = `${section}_${field}`;
    const addr = section === "origin" ? originAddress.value : destinationAddress.value;
    const value = addr[field];

    if (field === "full_name") {
      sv.onBlur(key, () => sv.validateNomeCognome(key, value));
    } else if (field === "city") {
      sv.onBlur(key, () => {
        if (!value || !String(value).trim()) sv.setError(key, "Città è obbligatoria");
        else sv.clearError(key);
      });
      setTimeout(() => setSectionCitySuggestions(section, []), 200);
      void validateAddressLocationLink(section);
    } else if (field === "postal_code") {
      sv.onBlur(key, () => sv.validateCAP(key, value));
      setTimeout(() => setSectionCapSuggestions(section, []), 200);
      void validateAddressLocationLink(section);
    } else if (field === "telephone_number") {
      sv.onBlur(key, () => sv.validateTelefono(key, value));
    } else if (field === "email") {
      sv.onBlur(key, () => sv.validateEmail(key, value));
    } else if (field === "province") {
      sv.onBlur(key, () => sv.validateProvincia(key, value));
      setTimeout(() => {
        setSectionProvinceSuggestions(section, []);
      }, 200);
      void validateAddressLocationLink(section);
    } else {
      sv.onBlur(key, () => {
        if (!value || !String(value).trim()) {
          sv.setError(key, "Campo obbligatorio");
        } else {
          sv.clearError(key);
        }
      });
    }
  };

  // --- LOCATION LINK VALIDATION ---
  const validateAddressLocationLink = async (section) => {
    if (section === "dest" && deliveryMode.value === "pudo") return true;

    const addr = getSectionAddress(section);
    const city = String(addr.city || "").trim();
    const province = sv.filterProvincia(addr.province || "");
    const cap = sv.filterCAP(addr.postal_code || "");
    if (!city || !province || cap.length !== 5) return true;

    try {
      const results = dedupeLocations(await sanctumClient(`/api/locations/by-cap?cap=${encodeURIComponent(cap)}`));
      locationLinkHints[section] = results;
      if (!results.length) {
        sv.setError(`${section}_postal_code`, `CAP ${cap} non trovato.`);
        return false;
      }

      const cityNorm = normalizeLocationText(city);
      const provinceNorm = normalizeLocationText(province);
      const exact = results.find((loc) =>
        normalizeLocationText(loc.place_name) === cityNorm &&
        normalizeLocationText(getProvinceLabel(loc)) === provinceNorm
      );

      if (!exact) {
        const cityMatch = results.find((loc) => normalizeLocationText(loc.place_name) === cityNorm);
        const provinceMatch = results.find((loc) => normalizeLocationText(getProvinceLabel(loc)) === provinceNorm);
        const hint = results[0];
        const hintProvince = getProvinceLabel(hint);
        const hintText = hintProvince ? `${hint.place_name} (${hintProvince})` : hint.place_name;

        sv.setError(`${section}_postal_code`, `CAP ${cap} non coerente con città/provincia.`);
        if (!cityMatch) sv.setError(`${section}_city`, `Per CAP ${cap} la città corretta è ${hintText}.`);
        if (!provinceMatch) sv.setError(`${section}_province`, `Provincia non coerente con CAP ${cap}.`);
        return false;
      }

      addr.city = exact.place_name || addr.city;
      addr.province = getProvinceLabel(exact) || addr.province;
      sv.clearError(`${section}_city`);
      sv.clearError(`${section}_province`);
      sv.clearError(`${section}_postal_code`);
      locationLinkHints[section] = [];
      return true;
    } catch (error) {
      locationLinkHints[section] = [];
      return true;
    }
  };

  return {
    // Suggestion refs
    originProvinceSuggestions,
    destProvinceSuggestions,
    originCitySuggestions,
    destCitySuggestions,
    originCapSuggestions,
    destCapSuggestions,
    // Utilities
    normalizeLocationText,
    getSectionAddress,
    formatCitySuggestionLabel,
    formatCapSuggestionLabel,
    dedupeLocations,
    applyLocationToSection,
    locationLinkHints,
    // Input handlers
    onProvinciaInput,
    selectProvincia,
    onCityInput,
    onCapInput,
    onNameInput,
    onTelefonoInput,
    selectCity,
    selectCap,
    // Focus handlers
    onCityFocus,
    onCapFocus,
    onProvinceFocus,
    // Blur/validation
    smartBlur,
    validateAddressLocationLink,
  };
}
