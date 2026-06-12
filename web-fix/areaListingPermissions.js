/**
 * Area listing permissions — must match backend AreaListingService::userCanAutoCreateAreas
 * for agent portal saves. User portal (/user/...) always suggests only.
 */

export const isAgentPortalPath = (pathname = '') => String(pathname || '').includes('/agent/');

export const isUserPortalPath = (pathname = '') => String(pathname || '').includes('/user/');

/** Account has admin "Area Wise" permission (agent + flag). */
export const canUserManageAreaListing = (userData = {}) => {
  if (!userData || typeof userData !== 'object') {
    return false;
  }

  const isAgent = !!Number(userData.is_agent);
  return isAgent && !!Number(userData.can_manage_area_listing);
};

/** UI + map resolve: add/link areas only on agent portal with permission. */
export const canManageAreaListingInContext = (userData = {}, pathname = '') => {
  if (!isAgentPortalPath(pathname)) {
    return false;
  }
  return canUserManageAreaListing(userData);
};

/** User portal save: only send master area IDs when user picked from dropdown (not GPS auto-fill). */
export const buildAreaListingSaveFields = (location = {}, { isUserPortal = false } = {}) => {
  const base = {
    state_id: location.state_id || '',
    city_id: location.city_id || '',
    area_name: location.area_name || '',
    sub_area_name: location.sub_area_name || '',
    detected_area_name: location.detected_area_name || location.area_name || '',
    detected_sub_area_name: location.detected_sub_area_name || location.sub_area_name || '',
    area_listing_source: location.area_listing_source || 'google',
    location_is_verified: location.location_is_verified || false,
  };

  if (!isUserPortal) {
    return {
      ...base,
      area_id: location.area_id || '',
      sub_area_id: location.sub_area_id || '',
    };
  }

  const normalizeLabel = (value = '') =>
    String(value || '')
      .trim()
      .toLowerCase()
      .replace(/\s+/g, ' ');

  const userPicked = !!location.area_listing_user_confirmed;
  const detectedArea = (location.detected_area_name || location.area_name || '').trim();
  const detectedSub = (location.detected_sub_area_name || location.sub_area_name || '').trim();
  const areaLabelMatchesId =
  !location.area_id ||
  normalizeLabel(location.area_name || '') === normalizeLabel(detectedArea);
  const subLabelMatchesId =
  !location.sub_area_id ||
  normalizeLabel(location.sub_area_name || '') === normalizeLabel(detectedSub);
  const hasManualArea = !location.area_id || !areaLabelMatchesId;
  const hasManualSub = !location.sub_area_id || !subLabelMatchesId;

  const fields = {
    ...base,
    area_listing_user_portal: 1,
    ...(hasManualArea || hasManualSub ? { area_listing_user_suggested: 1 } : {}),
  };

  if (userPicked && location.area_id && areaLabelMatchesId) {
    fields.area_id = location.area_id;
  }
  if (userPicked && location.sub_area_id && subLabelMatchesId) {
    fields.sub_area_id = location.sub_area_id;
  }
  if (hasManualArea && detectedArea !== '') {
    fields.detected_area_name = detectedArea;
    fields.area_name = detectedArea;
    if (!userPicked || !location.area_id || !areaLabelMatchesId) {
      fields.area_id = '';
    }
    if (!subLabelMatchesId || hasManualSub) {
      fields.sub_area_id = '';
    }
  }
  if (hasManualSub && detectedSub !== '') {
    fields.detected_sub_area_name = detectedSub;
    fields.sub_area_name = detectedSub;
    if (!userPicked || !location.sub_area_id || !subLabelMatchesId) {
      fields.sub_area_id = '';
    }
  }

  return fields;
};
