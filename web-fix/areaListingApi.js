import api from "@/utils/api";

export const getAreaListingPermissions = () =>
  api.get("/area-listing/permissions").then((r) => r.data);

export const getAreaListingStates = (params) =>
  api.get("/area-listing/states", { params }).then((r) => r.data);

export const getAreaListingCities = (params) =>
  api.get("/area-listing/cities", { params }).then((r) => r.data);

export const getAreaListingAreas = (params) =>
  api.get("/area-listing/areas", { params }).then((r) => r.data);

export const getAreaListingSubAreas = (params) =>
  api.get("/area-listing/sub-areas", { params }).then((r) => r.data);

export const storeAreaListingArea = (payload) =>
  api.post("/area-listing/areas", payload).then((r) => r.data);

export const storeAreaListingSubArea = (payload) =>
  api.post("/area-listing/sub-areas", payload).then((r) => r.data);

export const suggestAreaListingArea = (payload) =>
  api.post("/location/suggest-area", payload).then((r) => r.data);

export const suggestAreaListingSubArea = (payload) =>
  api.post("/location/suggest-sub-area", payload).then((r) => r.data);
