import axios from "@/api/axiosMiddleware";

const CACHE_KEY = "sukoon_theme_public_v1";
const CACHE_TTL_MS = 5 * 60 * 1000;

let memoryCache = null;
let memoryCacheAt = 0;

function readSessionCache() {
  if (typeof window === "undefined") return null;
  try {
    const raw = sessionStorage.getItem(CACHE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (Date.now() - parsed.at > CACHE_TTL_MS) return null;
    return parsed.data;
  } catch {
    return null;
  }
}

function writeSessionCache(data) {
  if (typeof window === "undefined") return;
  try {
    sessionStorage.setItem(CACHE_KEY, JSON.stringify({ at: Date.now(), data }));
  } catch {
    /* quota */
  }
}

export async function fetchPublicTheme({ force = false } = {}) {
  if (!force && memoryCache && Date.now() - memoryCacheAt < CACHE_TTL_MS) {
    return memoryCache;
  }

  const session = !force ? readSessionCache() : null;
  if (session) {
    memoryCache = session;
    memoryCacheAt = Date.now();
    return session;
  }

  const response = await axios.get("theme/public");
  const payload = response?.data?.data ?? response?.data ?? null;

  if (payload?.published === false || !payload?.tokens) {
    memoryCache = { published: false };
    memoryCacheAt = Date.now();
    writeSessionCache({ published: false });
    return { published: false };
  }

  const data = payload;
  memoryCache = data;
  memoryCacheAt = Date.now();
  writeSessionCache(data);
  return data;
}

export function clearThemeCache() {
  memoryCache = null;
  memoryCacheAt = 0;
  if (typeof window !== "undefined") {
    try {
      sessionStorage.removeItem(CACHE_KEY);
    } catch {
      /* ignore */
    }
  }
}
