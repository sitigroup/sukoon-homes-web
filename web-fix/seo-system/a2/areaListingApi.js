import api from "@/api/axiosMiddleware";

export const getAreaListingStates = async ({ country = "" } = {}) => {
  const res = await api.get("area-listing/states", { params: { country } });
  return res.data;
};

export const getAreaListingCities = async ({ state_id = "", state = "", country = "" } = {}) => {
  const res = await api.get("area-listing/cities", { params: { state_id, state, country } });
  return res.data;
};

export const getAreaListingAreas = async ({ state_id = "", city_id = "", city = "", state = "", country = "" } = {}) => {
  const res = await api.get("area-listing/areas", { params: { state_id, city_id, city, state, country } });
  return res.data;
};

export const getAreaListingSubAreas = async ({ area_id = "" } = {}) => {
  const res = await api.get("area-listing/sub-areas", { params: { area_id } });
  return res.data;
};

export const getAreaListingPermissions = async () => {
  const res = await api.get("area-listing/permissions");
  return res.data;
};

export const createAreaListingArea = async (payload = {}) => {
  const res = await api.post("area-listing/areas", payload);
  return res.data;
};

export const createAreaListingSubArea = async (payload = {}) => {
  const res = await api.post("area-listing/sub-areas", payload);
  return res.data;
};

export const suggestAreaListingArea = async (payload = {}) => {
  const res = await api.post("location/suggest-area", payload);
  return res.data;
};

export const suggestAreaListingSubArea = async (payload = {}) => {
  const res = await api.post("location/suggest-sub-area", payload);
  return res.data;
};

export const resolveAreaListingCoordinates = async (payload = {}) => {
  const res = await api.post("area-listing/resolve-coordinates", payload);
  return res.data;
};
