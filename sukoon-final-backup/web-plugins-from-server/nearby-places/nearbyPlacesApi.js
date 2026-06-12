import api from "@/api/axiosMiddleware";

export const getPropertyNearbyPlaces = async (propertyId) => {
  const res = await api.get(`nearby-places/property/${propertyId}`);
  return res.data;
};
