import { useEffect, useMemo, useRef, useState, useCallback } from "react";
import { createPortal } from "react-dom";
import toast from "react-hot-toast";
import {
  getAreaListingAreas,
  getAreaListingCities,
  getAreaListingStates,
  getAreaListingPermissions,
  createAreaListingArea,
  createAreaListingSubArea,
  suggestAreaListingArea,
  suggestAreaListingSubArea,
} from "./areaListingApi";
import { titleCaseLocation } from "./areaListingUtils";

const selectClass = "primaryBackgroundBg leadColor newBorderColor w-full rounded-lg border-[1.5px] px-3 py-2.5 text-sm focus:outline-none";
const buttonClass = selectClass + " h-11 truncate text-left";

const DropdownField = ({
  label,
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
  const menuRef = useRef(null);
  const [menuPosition, setMenuPosition] = useState(null);

  const updateMenuPosition = useCallback(() => {
    if (!triggerRef.current) return;
    const rect = triggerRef.current.getBoundingClientRect();
    const viewportPadding = 8;
    const maxMenuHeight = 280;
    const spaceBelow = window.innerHeight - rect.bottom - viewportPadding;
    const spaceAbove = rect.top - viewportPadding;
    const openUpward = spaceBelow < 180 && spaceAbove > spaceBelow;

    setMenuPosition({
      top: openUpward ? Math.max(viewportPadding, rect.top - maxMenuHeight - 4) : rect.bottom + 4,
      left: rect.left,
      width: rect.width,
      maxHeight: openUpward
        ? Math.min(maxMenuHeight, rect.top - viewportPadding - 4)
        : Math.min(maxMenuHeight, spaceBelow),
    });
  }, []);

  useEffect(() => {
    if (!isOpen || disabled) {
      setMenuPosition(null);
      return undefined;
    }
    updateMenuPosition();
    const handleReposition = () => updateMenuPosition();
    window.addEventListener("resize", handleReposition);
    window.addEventListener("scroll", handleReposition, true);
    return () => {
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
        className="fixed z-[9998] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
        style={{
          top: menuPosition.top,
          left: menuPosition.left,
          width: menuPosition.width,
          maxHeight: menuPosition.maxHeight,
        }}
      >
        <input
          className="mb-2 h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm outline-none focus:border-gray-300"
          value={searchValue}
          onChange={(event) => setSearchValue(event.target.value)}
          placeholder={searchPlaceholder || `Search ${label.toLowerCase()}`}
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
      <label className="mb-1 block text-sm font-medium text-gray-800">{label}</label>
      <button
        ref={triggerRef}
        type="button"
        className={buttonClass + " " + (disabled ? "cursor-not-allowed opacity-70 text-gray-400" : valueLabel ? "text-gray-900" : "text-gray-400")}
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
  const compactRef = useRef(null);

  const selectedArea = useMemo(
    () => areas.find((area) => String(area.id) === String(selectedLocationAddress?.area_id || "")),
    [areas, selectedLocationAddress?.area_id]
  );

  const update = (values) => setSelectedLocationAddress((prev) => ({ ...prev, ...values }));
  const normalize = (value = "") => titleCaseLocation(value).toLowerCase();
  const hasCityContext = Boolean(String(selectedLocationAddress?.city_id || selectedLocationAddress?.city || "").trim());

  useEffect(() => {
    if (canManageAreaListingProp !== null && canManageAreaListingProp !== undefined) {
      setCanManageAreaListing(!!canManageAreaListingProp);
      return undefined;
    }
    let mounted = true;
    getAreaListingPermissions()
      .then((res) => mounted && setCanManageAreaListing(!!res?.data?.can_manage_area_listing))
      .catch(() => mounted && setCanManageAreaListing(false));
    return () => {
      mounted = false;
    };
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
    if (requiresCity && !hasCityContext) {
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
  }, [requiresCity, hasCityContext, selectedLocationAddress?.state_id, selectedLocationAddress?.city_id, selectedLocationAddress?.city, selectedLocationAddress?.state, selectedLocationAddress?.country]);

  const areaQuery = areaSearch.trim().toLowerCase();
  const subAreaQuery = subAreaSearch.trim().toLowerCase();
  const filteredAreas = areas.filter((area) => area.name?.toLowerCase().includes(areaQuery));
  const filteredSubAreas = (selectedArea?.sub_areas || []).filter((subArea) => subArea.name?.toLowerCase().includes(subAreaQuery));
  const exactAreaExists = Boolean(areaQuery && areas.some((area) => normalize(area.name) === normalize(areaSearch)));
  const exactSubAreaExists = Boolean(subAreaQuery && (selectedArea?.sub_areas || []).some((subArea) => normalize(subArea.name) === normalize(subAreaSearch)));
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

  useEffect(() => {
    if (!selectedArea || selectedLocationAddress?.sub_area_id) return;
    const detectedSubArea = normalize(selectedLocationAddress?.detected_sub_area_name || selectedLocationAddress?.sub_area_name || "");
    if (!detectedSubArea) return;
    const matchedSubArea = (selectedArea.sub_areas || []).find((subArea) => normalize(subArea.name) === detectedSubArea);
    if (!matchedSubArea) return;
    update({ sub_area_id: matchedSubArea.id, sub_area_name: matchedSubArea.name, detected_sub_area_name: matchedSubArea.name });
  }, [selectedArea, selectedLocationAddress?.detected_sub_area_name, selectedLocationAddress?.sub_area_name, selectedLocationAddress?.sub_area_id]);

  const selectArea = (area) => {
    if (!area) {
      update({
        area_id: "",
        area_name: "",
        detected_area_name: "",
        sub_area_id: "",
        sub_area_name: "",
        detected_sub_area_name: "",
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
    });
  };

  const selectSubArea = (subArea) => {
    if (!subArea) {
      update({ sub_area_id: "", sub_area_name: "", detected_sub_area_name: "" });
      return;
    }
    update({ sub_area_id: subArea?.id || "", sub_area_name: subArea?.name || "", detected_sub_area_name: subArea?.name || "" });
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

  const addAreaFromSearch = async () => {
    const name = titleCaseLocation(areaSearch.trim());
    if (!name || loading.savingArea) return;
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
      if (canManageAreaListing) {
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
      } else {
        await suggestAreaListingArea(payload);
        toast.success("Area suggestion sent for admin approval.");
      }
      setAreaSearch("");
      setIsAreaOpen(false);
    } catch (error) {
      toast.error(error?.response?.data?.message || (canManageAreaListing ? "Unable to add area." : "Unable to send area suggestion."));
    } finally {
      setLoading((prev) => ({ ...prev, savingArea: false }));
    }
  };

  const addSubAreaFromSearch = async () => {
    const name = titleCaseLocation(subAreaSearch.trim());
    if (!name || !selectedArea?.id || loading.savingSubArea) return;
    setLoading((prev) => ({ ...prev, savingSubArea: true }));
    try {
      const payload = {
        area_id: selectedArea.id,
        name,
        center_lat: selectedLocationAddress?.latitude || selectedLocationAddress?.lat || "",
        center_lng: selectedLocationAddress?.longitude || selectedLocationAddress?.lng || "",
      };
      if (canManageAreaListing) {
        const res = await createAreaListingSubArea(payload);
        const subArea = res?.data;
        if (subArea?.id) {
          await refreshAreas();
          selectSubArea({ id: subArea.id, name: subArea.name, area_id: subArea.area_id });
          toast.success("Sub area added.");
        }
      } else {
        await suggestAreaListingSubArea(payload);
        toast.success("Sub area suggestion sent for admin approval.");
      }
      setSubAreaSearch("");
      setIsSubAreaOpen(false);
    } catch (error) {
      toast.error(error?.response?.data?.message || (canManageAreaListing ? "Unable to add sub area." : "Unable to send sub area suggestion."));
    } finally {
      setLoading((prev) => ({ ...prev, savingSubArea: false }));
    }
  };

  if (compactSeparate) {
    const cityRequiredButMissing = requiresCity && !hasCityContext;
    const areaLabel = selectedLocationAddress?.area_name || selectedLocationAddress?.detected_area_name || "";
    const subAreaLabel = selectedLocationAddress?.sub_area_name || selectedLocationAddress?.detected_sub_area_name || "";
    return (
      <div className={`grid gap-3 ${stacked ? "grid-cols-1" : "grid-cols-1 md:grid-cols-2"}`}>
        <DropdownField
          label="Area"
          valueLabel={areaLabel}
          placeholder={cityRequiredButMissing ? "Select city first" : loading.areas ? "Loading areas..." : "Select Area"}
          searchPlaceholder="Search area"
          disabled={cityRequiredButMissing}
          searchValue={areaSearch}
          setSearchValue={setAreaSearch}
          isOpen={isAreaOpen}
          setIsOpen={(next) => {
            setIsSubAreaOpen(false);
            setIsAreaOpen(typeof next === "function" ? next(isAreaOpen) : next);
          }}
        >
          <OptionButton onSelect={() => { selectArea(null); setAreaSearch(""); setIsAreaOpen(false); }}>Clear Area</OptionButton>
          {filteredAreas.length > 0 ? filteredAreas.map((area) => (
            <OptionButton key={area.id} onSelect={() => { selectArea(area); setAreaSearch(""); setIsAreaOpen(false); }}>{area.name}</OptionButton>
          )) : <div className="px-3 py-2 text-sm text-gray-500">No areas found</div>}
          {areaQuery && !exactAreaExists && (
            <OptionButton onSelect={addAreaFromSearch}>{loading.savingArea ? "Saving..." : canManageAreaListing ? `Add "${titleCaseLocation(areaSearch)}"` : `Suggest "${titleCaseLocation(areaSearch)}"`}</OptionButton>
          )}
        </DropdownField>
        <DropdownField
          label="Sub Area"
          valueLabel={subAreaLabel}
          placeholder={!selectedArea ? "Select area first" : loading.areas ? "Loading..." : "Select Sub Area"}
          searchPlaceholder="Search sub area"
          disabled={!selectedArea}
          searchValue={subAreaSearch}
          setSearchValue={setSubAreaSearch}
          isOpen={isSubAreaOpen}
          setIsOpen={(next) => {
            setIsAreaOpen(false);
            setIsSubAreaOpen(typeof next === "function" ? next(isSubAreaOpen) : next);
          }}
        >
          <OptionButton onSelect={() => { selectSubArea(null); setSubAreaSearch(""); setIsSubAreaOpen(false); }}>Clear Sub Area</OptionButton>
          {filteredSubAreas.length > 0 ? filteredSubAreas.map((subArea) => (
            <OptionButton key={subArea.id} onSelect={() => { selectSubArea(subArea); setSubAreaSearch(""); setIsSubAreaOpen(false); }}>{subArea.name}</OptionButton>
          )) : (
            <div className="px-3 py-2 text-sm text-gray-500">
              {selectedArea ? "No sub areas found" : "Select area first"}
            </div>
          )}
          {subAreaQuery && selectedArea?.id && !exactSubAreaExists && (
            <OptionButton onSelect={addSubAreaFromSearch}>{loading.savingSubArea ? "Saving..." : canManageAreaListing ? `Add "${titleCaseLocation(subAreaSearch)}"` : `Suggest "${titleCaseLocation(subAreaSearch)}"`}</OptionButton>
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
        <label className="mb-1 block text-sm font-medium text-gray-800">Area</label>
        <button
          type="button"
          className={buttonClass + " " + (cityRequiredButMissing ? "cursor-not-allowed opacity-70 text-gray-400" : selectedLabel ? "text-gray-900" : "text-gray-400")}
          disabled={cityRequiredButMissing}
          onClick={() => {
            if (cityRequiredButMissing) return;
            setIsCompactOpen((open) => !open);
          }}
        >
          {selectedLabel || (cityRequiredButMissing ? "Select city first" : loading.areas ? "Loading areas..." : "Select Area")}
        </button>
        {isCompactOpen && (
          <div className="absolute left-0 right-0 top-full z-[100] mt-1 rounded-lg border border-gray-200 bg-white p-2 shadow-lg">
            <input className="mb-2 h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm outline-none focus:border-gray-300" value={areaSearch} onChange={(event) => setAreaSearch(event.target.value)} placeholder="Search area" autoFocus />
            <div className="max-h-56 overflow-y-auto">
              <OptionButton onSelect={() => { update({ area_id: "", area_name: "", sub_area_id: "", sub_area_name: "", detected_area_name: "", detected_sub_area_name: "" }); setAreaSearch(""); setIsCompactOpen(false); }}>All Areas</OptionButton>
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
            <label className="mb-1 block text-sm font-medium text-gray-800">State</label>
            <select className={selectClass} value={selectedLocationAddress?.state_id || ""} onChange={(event) => { const state = states.find((item) => String(item.id) === event.target.value); update({ state_id: event.target.value, state: state?.name || selectedLocationAddress?.state || "", city_id: "", area_id: "", sub_area_id: "" }); }}>
              <option value="">{loading.states ? "Loading states..." : "Select State"}</option>
              {states.map((state) => <option key={state.id} value={state.id}>{state.name}</option>)}
            </select>
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-800">City</label>
            <select className={selectClass} value={selectedLocationAddress?.city_id || ""} onChange={(event) => { const city = cities.find((item) => String(item.id) === event.target.value); update({ city_id: event.target.value, city: city?.name || selectedLocationAddress?.city || "", area_id: "", sub_area_id: "" }); }}>
              <option value="">{loading.cities ? "Loading cities..." : "Select City"}</option>
              {cities.map((city) => <option key={city.id} value={city.id}>{city.name}</option>)}
            </select>
          </div>
        </>
      )}
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-800">Area</label>
        <input className="primaryBackgroundBg leadColor newBorderColor mb-2 w-full rounded-lg border-[1.5px] px-3 py-2 text-sm focus:outline-none" value={areaSearch} onChange={(event) => setAreaSearch(event.target.value)} placeholder="Search area" />
        <select className={selectClass} value={selectedLocationAddress?.area_id || ""} onChange={(event) => { const area = areas.find((item) => String(item.id) === event.target.value); selectArea(area); }}>
          <option value="">{loading.areas ? "Loading areas..." : "Select Area"}</option>
          {filteredAreas.map((area) => <option key={area.id} value={area.id}>{area.name}</option>)}
        </select>
      </div>
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-800">Sub Area</label>
        <input className="primaryBackgroundBg leadColor newBorderColor mb-2 w-full rounded-lg border-[1.5px] px-3 py-2 text-sm focus:outline-none" value={subAreaSearch} onChange={(event) => setSubAreaSearch(event.target.value)} placeholder="Search sub area" disabled={!selectedArea} />
        <select className={selectClass} value={selectedLocationAddress?.sub_area_id || ""} onChange={(event) => { const subArea = selectedArea?.sub_areas?.find((item) => String(item.id) === event.target.value); selectSubArea(subArea); }} disabled={!selectedArea}>
          <option value="">Select Sub Area</option>
          {filteredSubAreas.map((subArea) => <option key={subArea.id} value={subArea.id}>{subArea.name}</option>)}
        </select>
      </div>
    </div>
  );
};

export default AreaSubAreaSelector;
