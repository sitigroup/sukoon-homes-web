import { getTenantReliabilityProfile } from "../trustVerificationApi";

const cache = new Map();
const inflight = new Map();

export async function fetchTenantReliabilityCached(customerId) {
  const id = String(customerId);
  if (!id) return null;

  if (cache.has(id)) {
    return cache.get(id);
  }

  if (inflight.has(id)) {
    return inflight.get(id);
  }

  const promise = getTenantReliabilityProfile(customerId)
    .then((data) => {
      cache.set(id, data);
      inflight.delete(id);
      return data;
    })
    .catch((err) => {
      inflight.delete(id);
      throw err;
    });

  inflight.set(id, promise);
  return promise;
}
