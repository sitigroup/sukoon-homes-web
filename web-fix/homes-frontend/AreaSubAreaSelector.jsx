import { useEffect, useMemo, useRef, useState, useCallback } from "react";
import { createPortal } from "react-dom";
import toast from "react-hot-toast";
import {
  getAreaListingAreas,
  getAreaListingSubAreas,
  getAreaListingCities,
  getAreaListingStates,
  getAreaListingPermissions,
  createAreaListingArea,
  createAreaListingSubArea,
} from "./areaListingApi";
import { titleCaseLocation } from "./areaListingUtils";
import { useTranslation } from "@/components/context/TranslationContext";

const selectClass = "primaryBackgroundBg leadColor newBorderColor w-full rounded-lg border-[1.5px] px-3 py-2.5 text-sm focus:outline-none";
const buttonClass = selectClass + " h-11 truncate text-left";

const DropdownField = ({
  label,
  fieldId,
  valueLabel,
  placeholder,
  searchPlaceholder,
  disabled,
  searchValue,
  setSearchValue,
  children,
  isOpen,
  setIsOpen,
}) => {
  const containerRef = useRef(null);
  const triggerRef = useRef(null);
  const triggerId = fieldId || `dropdown-${String(label || "field").toLowerCase().replace(/\s+/g, "-")}`;
  const searchInputId = `${triggerId}-search`;
  const menuRef = useRef(null);
  const [menuPosition, setMenuPosition] = useState(null);

  const updateMenuPosition = useCallback(() => {
    if (!triggerRef.current) return;
    const rect = triggerRef.current.getBoundingClientRect();
    const viewportPadding = 8;
    const maxMenuHeight = 280;
    const gap = 6;
    const spaceBelow = window.innerHeight - rect.bottom - viewportPadding;
    const spaceAbove = rect.top - viewportPadding;
    const openUpward = spaceBelow < 160 && spaceAbove > spaceBelow;
    const width = Math.max(Math.round(rect.width), 1);

    if (openUpward) {
      setMenuPosition({
        top: null,
        bottom: window.innerHeight - rect.top + gap,
        left: Math.round(rect.left),
        width,
        maxHeight: Math.max(120, Math.min(maxMenuHeight, spaceAbove - gap)),
      });
      return;
    }

    setMenuPosition({
      top: rect.bottom + gap,
      bottom: null,
      left: Math.round(rect.left),
      width,
      maxHeight: Math.max(120, Math.min(maxMenuHeight, spaceBelow)),
    });
  }, []);

  useEffect(() => {
    if (!isOpen || disabled) {
      setMenuPosition(null);
      return undefined;
    }
    let frameId = requestAnimationFrame(() => {
      updateMenuPosition();
    });
    const handleReposition = () => {
      cancelAnimationFrame(frameId);
      frameId = requestAnimationFrame(() => {
        updateMenuPosition();
      });
    };
    window.addEventListener("resize", handleReposition);
    window.addEventListener("scroll", handleReposition, true);
    return () => {
      cancelAnimationFrame(frameId);
      window.removeEventListener("resize", handleReposition);
      window.removeEventListener("scroll", handleReposition, true);
    };
  }, [isOpen, disabled, updateMenuPosition]);

  useEffect(() => {
    if (!isOpen) return undefined;
    const handlePointerDown = (event) => {
      const target = event.target;
      if (containerRef.current?.contains(target)) return;
      if (menuRef.current?.contains(target)) return;
      setIsOpen(false);
    };
    document.addEventListener("mousedown", handlePointerDown);
    return () => document.removeEventListener("mousedown", handlePointerDown);
  }, [isOpen, setIsOpen]);

  const menu =
    isOpen &&
    !disabled &&
    menuPosition &&
    typeof document !== "undefined" &&
    createPortal(
      <div
        ref={menuRef}
        className="fixed z-[99999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
        style={{
          ...(menuPosition.top != null ? { top: menuPosition.top } : {}),
          ...(menuPosition.bottom != null ? { bottom: menuPosition.bottom } : {}),
          left: menuPosition.left,
          width: menuPosition.width,
          minWidth: menuPosition.width,
          maxWidth: menuPosition.width,
          maxHeight: menuPosition.maxHeight,
          boxSizing: "border-box",
        }}
      >
        <input
          id={searchInputId}
          name={searchInputId}
          type="search"
          className="mb-2 h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm outline-none focus:border-gray-300"
          value={searchValue}
          onChange={(event) => setSearchValue(event.target.value)}
          placeholder={searchPlaceholder || `Search ${label.toLowerCase()}`}
          aria-label={searchPlaceholder || label}
          autoFocus
        />
        <div className="overflow-y-auto" style={{ maxHeight: Math.max(120, (menuPosition.maxHeight || 280) - 52) }}>
          {children}
        </div>
      </div>,
      document.body
    );

  return (
    <div className="relative" ref={containerRef}>
      <label htmlFor={triggerId} className="mb-1 block text-sm font-medium text-gray-800">{label}</label>
      <button
        id={triggerId}
        ref={triggerRef}
        type="button"
        role="combobox"
        aria-expanded={isOpen}
        aria-haspopup="listbox"
        aria-controls={isOpen ? `${triggerId}-listbox` : undefined}
        className={buttonClass + " " + (disabled ? "cursor-not-allowed opacity-70 bg-gray-100 text-gray-400" : "cursor-pointer hover:border-gray-400 " + (valueLabel ? "text-gray-900" : "text-gray-400"))}
        disabled={disabled}
        onClick={() => {
          if (disabled) return;
          setIsOpen((open) => !open);
        }}
      >
        {valueLabel || placeholder}
      </button>
      {menu}
    </div>
  );
};

const OptionButton = ({ children, onSelect }) => (
  <button
    type="button"
    className="block w-full rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
    onMouseDown={(event) => {
      event.preventDefault();
      onSelect();
    }}
  >
    {children}
  </button>
);

const AreaSubAreaSelector = ({
  selectedLocationAddress = {},
  setSelectedLocationAddress,
  compact = false,
  compactSeparate = false,
  stacked = false,
  showStateCity = true,
  requiresCity = false,
  canManageAreaListing: canManageAreaListingProp = null,
}) => {
  const t = useTranslation();
  const [states, setStates] = useState([]);
  const [cities, setCities] = useState([]);
  const [areas, setAreas] = useState([]);
  const [canManageAreaListing, setCanManageAreaListing] = useState(!!canManageAreaListingProp);
  const [loading, setLoading] = useState({ states: false, cities: false, areas: false, savingArea: false, savingSubArea: false });
  const [areaSearch, setAreaSearch] = useState("");
  const [subAreaSearch, setSubAreaSearch] = useState("");
  const [isCompactOpen, setIsCompactOpen] = useState(false);
  const [isAreaOpen, setIsAreaOpen] = useState(false);
  const [isSubAreaOpen, setIsSubAreaOpen] = useState(false);
  const [extraSubAreas, setExtraSubAreas] = useState([]);
  const compactRef = useRef(null);

  const selectedAreaFromList = useMemo(
    () => areas.find((area) => String(area.id) === String(selectedLocationAddress?.area_id || "")),
    [areas, selectedLocationAddress?.area_id]
  );

  const update = (values) => setSelectedLocationAddress((prev) => ({ ...prev, ...values }));
  const normalize = (value = "") => titleCaseLocation(value).toLowerCase();

  const effectiveSelectedArea = useMemo(() => {
    if (selectedAreaFromList) {
      const listSubs = selectedAreaFromList.sub_areas || [];
      return {
        ...selectedAreaFromList,
        sub_areas: listSubs.length ? listSubs : extraSubAreas,
      };
    }

    const areaId = String(selectedLocationAddress?.area_id || "").trim();
    if (areaId) {
      const fromList = areas.find((area) => String(area.id) === areaId);
      if (fromList) {
        const listSubs = fromList.sub_areas || [];
        return {
          ...fromList,
          sub_areas: listSubs.length ? listSubs : extraSubAreas,
        };
      }
      return {
        id: selectedLocationAddress.area_id,
        name:
          selectedLocationAddress.area_name ||
          selectedLocationAddress.detected_area_name ||
          "Selected area",
        city_id: selectedLocationAddress.city_id,
        city: selectedLocationAddress.city,
        state_id: selectedLocationAddress.state_id,
        state: selectedLocationAddress.state,
        country: selectedLocationAddress.country,
        sub_areas: extraSubAreas,
      };
    }

    const detectedAreaKey = normalize(
      selectedLocationAddress?.detected_area_name || selectedLocationAddress?.area_name || ""
    );
    if (detectedAreaKey && areas.length) {
      const matchedByName = areas.find((area) => normalize(area.name) === detectedAreaKey);
      if (matchedByName) {
        const listSubs = matchedByName.sub_areas || [];
        return {
          ...matchedByName,
          sub_areas: listSubs.length ? listSubs : extraSubAreas,
        };
      }
    }

    const detectedAreaLabel = String(
      selectedLocationAddress?.detected_area_name || selectedLocationAddress?.area_name || ""
    ).trim();
    if (detectedAreaLabel) {
      return {
        id: "",
        name: detectedAreaLabel,
        city_id: selectedLocationAddress?.city_id,
        city: selectedLocationAddress?.city,
        state_id: selectedLocationAddress?.state_id,
        state: selectedLocationAddress?.state,
        country: selectedLocationAddress?.country,
        sub_areas: [],
      };
    }

    return null;
  }, [selectedAreaFromList, extraSubAreas, selectedLocationAddress, areas]);

  const hasCityContext = Boolean(String(selectedLocationAddress?.city_id || selectedLocationAddress?.city || "").trim());
  const hasSavedArea = Boolean(
    String(
      selectedLocationAddress?.area_id ||
        selectedLocationAddress?.area_name ||
        selectedLocationAddress?.detected_area_name ||
        ""
    ).trim()
  );
  const hasDetectedSubArea = Boolean(
    String(
      selectedLocationAddress?.sub_area_id ||
        selectedLocationAddress?.sub_area_name ||
        selectedLocationAddress?.detected_sub_area_name ||
        ""
    ).trim()
  );

  useEffect(() => {
    if (canManageAreaListingProp !== null && canManageAreaListingProp !== undefined) {
      setCanManageAreaListing(!!canManageAreaListingProp);
      return undefined;
    }
    // Parent must pass permission; never infer from API (user portal would wrongly auto-bind).
    setCanManageAreaListing(false);
    return undefined;
  }, [canManageAreaListingProp]);

  useEffect(() => {
    let mounted = true;
    setLoading((prev) => ({ ...prev, states: true }));
    getAreaListingStates({ country: selectedLocationAddress?.country || "" })
      .then((res) => mounted && setStates(res?.data || []))
      .catch(() => mounted && setStates([]))
      .finally(() => mounted && setLoading((prev) => ({ ...prev, states: false })));
    return () => { mounted = false; };
  }, [selectedLocationAddress?.country]);

  useEffect(() => {
    let mounted = true;
    setLoading((prev) => ({ ...prev, cities: true }));
    getAreaListingCities({
      state_id: selectedLocationAddress?.state_id || "",
      state: selectedLocationAddress?.state || "",
      country: selectedLocationAddress?.country || "",
    })
      .then((res) => mounted && setCities(res?.data || []))
      .catch(() => mounted && setCities([]))
      .finally(() => mounted && setLoading((prev) => ({ ...prev, cities: false })));
    return () => { mounted = false; };
  }, [selectedLocationAddress?.state_id, selectedLocationAddress?.state, selectedLocationAddress?.country]);

  useEffect(() => {
    const areaId = String(selectedLocationAddress?.area_id || "").trim();
    if (!areaId) {
      setExtraSubAreas([]);
      return undefined;
    }
    if (selectedAreaFromList?.sub_areas?.length) {
      setExtraSubAreas([]);
      return undefined;
    }

    let mounted = true;
    getAreaListingSubAreas({ area_id: areaId })
      .then((res) => mounted && setExtraSubAreas(res?.data || []))
      .catch(() => mounted && setExtraSubAreas([]));
    return () => {
      mounted = false;
    };
  }, [selectedLocationAddress?.area_id, selectedAreaFromList]);

  useEffect(() => {
    if (requiresCity && !hasCityContext && !hasSavedArea) {
      setAreas([]);
      setLoading((prev) => ({ ...prev, areas: false }));
      return undefined;
    }

    let mounted = true;
    setLoading((prev) => ({ ...prev, areas: true }));
    getAreaListingAreas({
      state_id: selectedLocationAddress?.state_id || "",
      city_id: selectedLocationAddress?.city_id || "",
      city: selectedLocationAddress?.city || "",
      state: selectedLocationAddress?.state || "",
      country: selectedLocationAddress?.country || "",
    })
      .then((res) => mounted && setAreas(res?.data || []))
      .catch(() => mounted && setAreas([]))
      .finally(() => mounted && setLoading((prev) => ({ ...prev, areas: false })));
    return () => { mounted = false; };
  }, [requiresCity, hasCityContext, hasSavedArea, selectedLocationAddress?.state_id, selectedLocationAddress?.city_id, selectedLocationAddress?.city, selectedLocationAddress?.state, selectedLocationAddress?.country]);

  const areaQuery = areaSearch.trim().toLowerCase();
  const subAreaQuery = subAreaSearch.trim().toLowerCase();
  const filteredAreas = areas.filter((area) => area.name?.toLowerCase().includes(areaQuery));
  const filteredSubAreas = (effectiveSelectedArea?.sub_areas || []).filter((subArea) =>
    subArea.name?.toLowerCase().includes(subAreaQuery)
  );
  const exactAreaExists = Boolean(areaQuery && areas.some((area) => normalize(area.name) === normalize(areaSearch)));
  const exactSubAreaExists = Boolean(
    subAreaQuery &&
      (effectiveSelectedArea?.sub_areas || []).some((subArea) => normalize(subArea.name) === normalize(subAreaSearch))
  );
  const hasStaleSubAreaSelection = useMemo(() => {
    const subAreaId = String(selectedLocationAddress?.sub_area_id || "").trim();
    if (!subAreaId || !effectiveSelectedArea) return false;
    const subs = effectiveSelectedArea.sub_areas || [];
    if (!subs.length) return false;
    return !subs.some((subArea) => String(subArea.id) === subAreaId);
  }, [effectiveSelectedArea, selectedLocationAddress?.sub_area_id]);
  const compactOptions = areas
    .filter((area) => !areaQuery || area.name?.toLowerCase().includes(areaQuery))
    .map((area) => ({ area, label: area.name, value: "area:" + area.id }));
  const compactValue = selectedLocationAddress?.area_id
    ? "area:" + selectedLocationAddress.area_id
    : "";

  useEffect(() => {
    if (!isCompactOpen) return undefined;
    const handlePointerDown = (event) => {
      if (compactRef.current && !compactRef.current.contains(event.target)) setIsCompactOpen(false);
    };
    document.addEventListener("mousedown", handlePointerDown);
    return () => document.removeEventListener("mousedown", handlePointerDown);
  }, [isCompactOpen]);

  useEffect(() => {
    if (!canManageAreaListing) return undefined;
    if (selectedLocationAddress?.area_id || !areas.length) return;
    const detectedArea = normalize(selectedLocationAddress?.detected_area_name || selectedLocationAddress?.area_name || "");
    const detectedSubArea = normalize(selectedLocationAddress?.detected_sub_area_name || selectedLocationAddress?.sub_area_name || "");
    if (!detectedArea && !detectedSubArea) return;

    const matchedByArea = detectedArea ? areas.find((area) => normalize(area.name) === detectedArea) : null;
    const matchedBySubArea = !matchedByArea
      ? areas.reduce((found, area) => {
          if (found) return found;
          const subArea = (area.sub_areas || []).find((item) => {
            const name = normalize(item.name);
            return name && (name === detectedSubArea || name === detectedArea);
          });
          return subArea ? { area, subArea } : null;
        }, null)
      : null;

    if (matchedByArea) {
      update({
        state_id: matchedByArea?.state_id || selectedLocationAddress?.state_id || "",
        city_id: matchedByArea?.city_id || selectedLocationAddress?.city_id || "",
        city: matchedByArea?.city || selectedLocationAddress?.city || "",
        state: matchedByArea?.state || selectedLocationAddress?.state || "",
        area_id: matchedByArea.id,
        area_name: matchedByArea.name,
        detected_area_name: matchedByArea.name,
      });
      return;
    }

    if (matchedBySubArea) {
      update({
        state_id: matchedBySubArea.area?.state_id || selectedLocationAddress?.state_id || "",
        city_id: matchedBySubArea.area?.city_id || selectedLocationAddress?.city_id || "",
        city: matchedBySubArea.area?.city || selectedLocationAddress?.city || "",
        state: matchedBySubArea.area?.state || selectedLocationAddress?.state || "",
        area_id: matchedBySubArea.area.id,
        area_name: matchedBySubArea.area.name,
        detected_area_name: matchedBySubArea.area.name,
        sub_area_id: matchedBySubArea.subArea.id,
        sub_area_name: matchedBySubArea.subArea.name,
        detected_sub_area_name: matchedBySubArea.subArea.name,
      });
    }
  }, [areas, selectedLocationAddress?.detected_area_name, selectedLocationAddress?.area_name, selectedLocationAddress?.detected_sub_area_name, selectedLocationAddress?.sub_area_name, selectedLocationAddress?.area_id]);

  // Agent portal only: auto-link detected area name to master list
  useEffect(() => {
    if (!canManageAreaListing) return undefined;
    if (selectedLocationAddress?.area_id || !areas.length) return undefined;
    const detectedArea = normalize(selectedLocationAddress?.detected_area_name || "");
    if (!detectedArea) return undefined;
    const matched = areas.find((area) => normalize(area.name) === detectedArea);
    if (!matched) return undefined;
    update({
      state_id: matched.state_id || selectedLocationAddress?.state_id || "",
      city_id: matched.city_id || selectedLocationAddress?.city_id || "",
      city: matched.city || selectedLocationAddress?.city || "",
      state: matched.state || selectedLocationAddress?.state || "",
      area_id: matched.id,
      area_name: matched.name,
      detected_area_name: matched.name,
      area_listing_user_confirmed: true,
    });
  }, [canManageAreaListing, areas, selectedLocationAddress?.detected_area_name, selectedLocationAddress?.area_id]);

  // Only agents with Area Wise auto-bind sub_area_id; user portal must pick/suggest manually
  useEffect(() => {
    if (!canManageAreaListing) return undefined;
    if (!effectiveSelectedArea || selectedLocationAddress?.sub_area_id) return undefined;
    const detectedSubArea = normalize(selectedLocationAddress?.detected_sub_area_name || selectedLocationAddress?.sub_area_name || "");
    if (!detectedSubArea) return undefined;
    const matchedSubArea = (effectiveSelectedArea.sub_areas || []).find((subArea) => normalize(subArea.name) === detectedSubArea);
    if (!matchedSubArea) return undefined;
    update({ sub_area_id: matchedSubArea.id, sub_area_name: matchedSubArea.name, detected_sub_area_name: matchedSubArea.name });
  }, [canManageAreaListing, effectiveSelectedArea, selectedLocationAddress?.detected_sub_area_name, selectedLocationAddress?.sub_area_name, selectedLocationAddress?.sub_area_id]);

  const selectArea = (area) => {
    if (!area) {
      update({
        area_id: "",
        area_name: "",
        detected_area_name: "",
        sub_area_id: "",
        sub_area_name: "",
        detected_sub_area_name: "",
        area_listing_user_confirmed: false,
      });
      return;
    }
    update({
      state_id: area?.state_id || selectedLocationAddress?.state_id || "",
      city_id: area?.city_id || selectedLocationAddress?.city_id || "",
      city: area?.city || selectedLocationAddress?.city || "",
      state: area?.state || selectedLocationAddress?.state || "",
      country: area?.country || selectedLocationAddress?.country || "",
      area_id: area?.id || "",
      area_name: area?.name || "",
      detected_area_name: area?.name || "",
      sub_area_id: "",
      sub_area_name: "",
      detected_sub_area_name: "",
      area_listing_user_confirmed: true,
    });
  };

  const selectSubArea = (subArea) => {
    if (!subArea) {
      update({ sub_area_id: "", sub_area_name: "", detected_sub_area_name: "", area_listing_user_confirmed: false });
      return;
    }
    update({
      sub_area_id: subArea?.id || "",
      sub_area_name: subArea?.name || "",
      detected_sub_area_name: subArea?.name || "",
      area_listing_user_confirmed: true,
    });
  };

  const refreshAreas = async () => {
    const res = await getAreaListingAreas({
      state_id: selectedLocationAddress?.state_id || "",
      city_id: selectedLocationAddress?.city_id || "",
      city: selectedLocationAddress?.city || "",
      state: selectedLocationAddress?.state || "",
      country: selectedLocationAddress?.country || "",
    });
    setAreas(res?.data || []);
  };

  const applyDetectedAreaFromSearch = (name) => {
    const matched = areas.find((area) => normalize(area.name) === normalize(name));
    update({
      area_id: matched?.id ? String(matched.id) : "",
      area_name: name,
      detected_area_name: name,
      sub_area_id: "",
      sub_area_name: "",
      detected_sub_area_name: "",
      area_listing_user_confirmed: !!matched?.id,
      area_listing_user_suggested: !matched?.id,
    });
    setAreaSearch("");
    setIsAreaOpen(false);
    toast.success("Area will be submitted when you save the property.");
  };

  const addAreaFromSearch = async () => {
    const name = titleCaseLocation(areaSearch.trim());
    if (!name || loading.savingArea) return;

    if (!canManageAreaListing) {
      applyDetectedAreaFromSearch(name);
      return;
    }

    setLoading((prev) => ({ ...prev, savingArea: true }));
    try {
      const payload = {
        name,
        city_id: selectedLocationAddress?.city_id || "",
        city: selectedLocationAddress?.city || "",
        state: selectedLocationAddress?.state || "",
        country: selectedLocationAddress?.country || "India",
        center_lat: selectedLocationAddress?.latitude || selectedLocationAddress?.lat || "",
        center_lng: selectedLocationAddress?.longitude || selectedLocationAddress?.lng || "",
      };
      const res = await createAreaListingArea(payload);
      const area = res?.data;
      if (area?.id) {
        await refreshAreas();
        selectArea({
          id: area.id,
          name: area.name,
          city_id: area.city_id,
          city: area.city,
          state: area.state,
          country: area.country,
          sub_areas: area.sub_areas || [],
        });
        toast.success("Area added.");
      }
      setAreaSearch("");
      setIsAreaOpen(false);
    } catch (error) {
      toast.error(error?.response?.data?.message || "Unable to add area.");
    } finally {
      setLoading((prev) => ({ ...prev, savingArea: false }));
    }
  };

  const resolveAreaIdForSubAreaAction = () => {
    const directId = String(effectiveSelectedArea?.id || selectedLocationAddress?.area_id || "").trim();
    if (directId) return directId;

    const detectedAreaKey = normalize(
      selectedLocationAddress?.detected_area_name || selectedLocationAddress?.area_name || ""
    );
    if (!detectedAreaKey || !areas.length) return "";

    const matched = areas.find((area) => normalize(area.name) === detectedAreaKey);
    return matched?.id ? String(matched.id) : "";
  };

  const applyDetectedSubAreaFromSearch = (name) => {
    const areaId = resolveAreaIdForSubAreaAction();
    const parentArea = areas.find((area) => String(area.id) === String(areaId));
    const matchedSub = (parentArea?.sub_areas || []).find(
      (sub) => normalize(sub.name) === normalize(name)
    );
    update({
      sub_area_id: matchedSub?.id ? String(matchedSub.id) : "",
      sub_area_name: name,
      detected_sub_area_name: name,
      area_listing_user_confirmed: !!matchedSub?.id,
      area_listing_user_suggested: !matchedSub?.id,
    });
    setSubAreaSearch("");
    setIsSubAreaOpen(false);
    toast.success("Sub area will be submitted when you save the property.");
  };

  const addSubAreaFromSearch = async () => {
    const name = titleCaseLocation(subAreaSearch.trim());
    if (!name || loading.savingSubArea) return;

    if (!canManageAreaListing) {
      if (!hasSavedArea) {
        toast.error("Set the area from the map or pick one from the list first.");
        return;
      }
      applyDetectedSubAreaFromSearch(name);
      return;
    }

    const areaId = resolveAreaIdForSubAreaAction();
    if (!areaId) {
      toast.error("Select an area from the list before adding a sub area.");
      return;
    }

    setLoading((prev) => ({ ...prev, savingSubArea: true }));
    try {
      const payload = {
        area_id: areaId,
        name,
        center_lat: selectedLocationAddress?.latitude || selectedLocationAddress?.lat || "",
        center_lng: selectedLocationAddress?.longitude || selectedLocationAddress?.lng || "",
      };
      const res = await createAreaListingSubArea(payload);
      const subArea = res?.data;
      if (subArea?.id) {
        await refreshAreas();
        selectSubArea({ id: subArea.id, name: subArea.name, area_id: subArea.area_id });
        toast.success("Sub area added.");
      }
      setSubAreaSearch("");
      setIsSubAreaOpen(false);
    } catch (error) {
      toast.error(error?.response?.data?.message || "Unable to add sub area.");
    } finally {
      setLoading((prev) => ({ ...prev, savingSubArea: false }));
    }
  };

  if (compactSeparate) {
    const cityRequiredButMissing = requiresCity && !hasCityContext && !hasSavedArea;
    const selectedAreaName = selectedLocationAddress?.area_name || selectedLocationAddress?.detected_area_name || "";
    const selectedSubAreaName = selectedLocationAddress?.sub_area_name || selectedLocationAddress?.detected_sub_area_name || "";
    const canEditSubArea = Boolean(
      effectiveSelectedArea ||
        selectedLocationAddress?.area_id ||
        (hasSavedArea && hasCityContext)
    );
    const canApplySubAreaFromSearch = Boolean(subAreaQuery && !exactSubAreaExists && canEditSubArea);
    return (
      <div className={`grid gap-3 ${stacked ? "grid-cols-1" : "grid-cols-1 md:grid-cols-2"}`}>
        <DropdownField
          fieldId="area-selector-dropdown-area"
          label={t("areaLabel")}
          valueLabel={selectedAreaName}
          placeholder={cityRequiredButMissing ? t("selectCityFirst") : loading.areas ? t("loadingAreas") : t("selectArea")}
          searchPlaceholder={t("searchArea")}
          disabled={cityRequiredButMissing}
          searchValue={areaSearch}
          setSearchValue={setAreaSearch}
          isOpen={isAreaOpen}
          setIsOpen={(next) => {
            setIsSubAreaOpen(false);
            setIsAreaOpen(typeof next === "function" ? next(isAreaOpen) : next);
          }}
        >
          <OptionButton onSelect={() => { selectArea(null); setAreaSearch(""); setIsAreaOpen(false); }}>{t("clearArea")}</OptionButton>
          {filteredAreas.length > 0 ? filteredAreas.map((area) => (
            <OptionButton key={area.id} onSelect={() => { selectArea(area); setAreaSearch(""); setIsAreaOpen(false); }}>{area.name}</OptionButton>
          )) : <div className="px-3 py-2 text-sm text-gray-500">No areas found</div>}
          {areaQuery && !exactAreaExists && (
            <OptionButton onSelect={addAreaFromSearch}>
              {loading.savingArea
                ? "Saving..."
                : canManageAreaListing
                  ? `Add "${titleCaseLocation(areaSearch)}"`
                  : `Use "${titleCaseLocation(areaSearch)}"`}
            </OptionButton>
          )}
        </DropdownField>
        <DropdownField
          fieldId="area-selector-dropdown-subarea"
          label={t("subAreaLabel")}
          valueLabel={selectedSubAreaName}
          placeholder={!canEditSubArea ? t("selectAreaFirst") : loading.areas ? t("loadingEllipsis") : t("selectSubArea")}
          searchPlaceholder={t("searchArea")}
          disabled={!canEditSubArea}
          searchValue={subAreaSearch}
          setSearchValue={setSubAreaSearch}
          isOpen={isSubAreaOpen}
          setIsOpen={(next) => {
            setIsAreaOpen(false);
            setIsSubAreaOpen(typeof next === "function" ? next(isSubAreaOpen) : next);
          }}
        >
          {hasStaleSubAreaSelection && (
            <div className="mb-1 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900">
              Current sub area is not in the list. Clear it, pick another, or type a name to suggest.
            </div>
          )}
          <OptionButton onSelect={() => { selectSubArea(null); setSubAreaSearch(""); setIsSubAreaOpen(false); }}>{t("clearSubArea")}</OptionButton>
          {filteredSubAreas.length > 0 ? filteredSubAreas.map((subArea) => (
            <OptionButton key={subArea.id} onSelect={() => { selectSubArea(subArea); setSubAreaSearch(""); setIsSubAreaOpen(false); }}>{subArea.name}</OptionButton>
          )) : (
            <div className="px-3 py-2 text-sm text-gray-500">
              {canEditSubArea ? t("selectSubArea") : t("selectAreaFirst")}
            </div>
          )}
          {canApplySubAreaFromSearch && (
            <OptionButton onSelect={addSubAreaFromSearch}>
              {loading.savingSubArea
                ? "Saving..."
                : canManageAreaListing
                  ? `Add "${titleCaseLocation(subAreaSearch)}"`
                  : `Use "${titleCaseLocation(subAreaSearch)}"`}
            </OptionButton>
          )}
        </DropdownField>
      </div>
    );
  }

  if (compact) {
    const cityRequiredButMissing = requiresCity && !hasCityContext;
    const selectedLabel =
      selectedLocationAddress?.area_name ||
      selectedLocationAddress?.detected_area_name ||
      compactOptions.find((option) => option.value === compactValue)?.label ||
      "";

    return (
      <div className="relative" ref={compactRef}>
        <label htmlFor="area-selector-compact" className="mb-1 block text-sm font-medium text-gray-800">{t("areaLabel")}</label>
        <button
          id="area-selector-compact"
          type="button"
          role="combobox"
          aria-haspopup="listbox"
          aria-expanded={isCompactOpen}
          className={buttonClass + " " + (cityRequiredButMissing ? "cursor-not-allowed opacity-70 text-gray-400" : selectedLabel ? "text-gray-900" : "text-gray-400")}
          disabled={cityRequiredButMissing}
          onClick={() => {
            if (cityRequiredButMissing) return;
            setIsCompactOpen((open) => !open);
          }}
        >
          {selectedLabel || (cityRequiredButMissing ? t("selectCityFirst") : loading.areas ? t("loadingAreas") : t("selectArea"))}
        </button>
        {isCompactOpen && (
          <div className="absolute left-0 right-0 top-full z-[100] mt-1 rounded-lg border border-gray-200 bg-white p-2 shadow-lg">
            <input id="area-selector-compact-search" name="area_search" type="search" aria-label={t("searchArea")} className="mb-2 h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm outline-none focus:border-gray-300" value={areaSearch} onChange={(event) => setAreaSearch(event.target.value)} placeholder={t("searchArea")} autoFocus />
            <div className="max-h-56 overflow-y-auto">
              <OptionButton onSelect={() => { update({ area_id: "", area_name: "", sub_area_id: "", sub_area_name: "", detected_area_name: "", detected_sub_area_name: "" }); setAreaSearch(""); setIsCompactOpen(false); }}>{t("allAreas")}</OptionButton>
              {compactOptions.length > 0 ? compactOptions.map((option) => (
                <OptionButton key={option.value} onSelect={() => { update({ state_id: option.area?.state_id || selectedLocationAddress?.state_id || "", city_id: option.area?.city_id || selectedLocationAddress?.city_id || "", city: option.area?.city || selectedLocationAddress?.city || "", state: option.area?.state || selectedLocationAddress?.state || "", country: option.area?.country || selectedLocationAddress?.country || "", area_id: option.area?.id || "", area_name: option.area?.name || "", detected_area_name: option.area?.name || "", sub_area_id: "", sub_area_name: "", detected_sub_area_name: "" }); setAreaSearch(""); setIsCompactOpen(false); }}>{option.label}</OptionButton>
              )) : <div className="px-3 py-2 text-sm text-gray-500">No areas found</div>}
            </div>
          </div>
        )}
      </div>
    );
  }

  const gridColumns = showStateCity ? "grid-cols-1 md:grid-cols-2" : "grid-cols-1 md:grid-cols-2";

  return (
    <div className={"grid " + gridColumns + " gap-3"}>
      {showStateCity && (
        <>
          <div>
            <label htmlFor="area-selector-state" className="mb-1 block text-sm font-medium text-gray-800">State</label>
            <select id="area-selector-state" name="state_id" className={selectClass} value={selectedLocationAddress?.state_id || ""} onChange={(event) => { const state = states.find((item) => String(item.id) === event.target.value); update({ state_id: event.target.value, state: state?.name || selectedLocationAddress?.state || "", city_id: "", area_id: "", sub_area_id: "" }); }}>
              <option value="">{loading.states ? "Loading states..." : "Select State"}</option>
              {states.map((state) => <option key={state.id} value={state.id}>{state.name}</option>)}
            </select>
          </div>
          <div>
            <label htmlFor="area-selector-city" className="mb-1 block text-sm font-medium text-gray-800">City</label>
            <select id="area-selector-city" name="city_id" className={selectClass} value={selectedLocationAddress?.city_id || ""} onChange={(event) => { const city = cities.find((item) => String(item.id) === event.target.value); update({ city_id: event.target.value, city: city?.name || selectedLocationAddress?.city || "", area_id: "", sub_area_id: "" }); }}>
              <option value="">{loading.cities ? "Loading cities..." : "Select City"}</option>
              {cities.map((city) => <option key={city.id} value={city.id}>{city.name}</option>)}
            </select>
          </div>
        </>
      )}
      <div>
        <label htmlFor="area-selector-area-search" className="mb-1 block text-sm font-medium text-gray-800">Area</label>
        <input id="area-selector-area-search" name="area_search" type="search" aria-label={t("searchArea")} className="primaryBackgroundBg leadColor newBorderColor mb-2 w-full rounded-lg border-[1.5px] px-3 py-2 text-sm focus:outline-none" value={areaSearch} onChange={(event) => setAreaSearch(event.target.value)} placeholder={t("searchArea")} />
        <select id="area-selector-area" name="area_id" className={selectClass} value={selectedLocationAddress?.area_id || ""} onChange={(event) => { const area = areas.find((item) => String(item.id) === event.target.value); selectArea(area); }}>
          <option value="">{loading.areas ? t("loadingAreas") : t("selectArea")}</option>
          {filteredAreas.map((area) => <option key={area.id} value={area.id}>{area.name}</option>)}
        </select>
      </div>
      <div>
        <label htmlFor="area-selector-subarea-search" className="mb-1 block text-sm font-medium text-gray-800">{t("subAreaLabel")}</label>
        <input id="area-selector-subarea-search" name="sub_area_search" type="search" aria-label="Search sub area" className="primaryBackgroundBg leadColor newBorderColor mb-2 w-full rounded-lg border-[1.5px] px-3 py-2 text-sm focus:outline-none" value={subAreaSearch} onChange={(event) => setSubAreaSearch(event.target.value)} placeholder="Search sub area" disabled={!effectiveSelectedArea} />
        <select id="area-selector-subarea" name="sub_area_id" className={selectClass} value={selectedLocationAddress?.sub_area_id || ""} onChange={(event) => { const subArea = effectiveSelectedArea?.sub_areas?.find((item) => String(item.id) === event.target.value); selectSubArea(subArea); }} disabled={!effectiveSelectedArea}>
          <option value="">{t("selectSubArea")}</option>
          {filteredSubAreas.map((subArea) => <option key={subArea.id} value={subArea.id}>{subArea.name}</option>)}
        </select>
      </div>
    </div>
  );
};

export default AreaSubAreaSelector;
