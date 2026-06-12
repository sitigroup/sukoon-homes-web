const isSettingEnabled = (value, defaultEnabled = true) => {
  if (value === undefined || value === null || value === "") {
    return defaultEnabled;
  }

  if (value === true || value === 1 || value === "1") {
    return true;
  }

  if (value === false || value === 0 || value === "0") {
    return false;
  }

  return defaultEnabled;
};

export const useCustomPropertyLayoutSettings = (webSettings) => ({
  showAgentCard: isSettingEnabled(webSettings?.custom_property_show_agent_card),
  showShareSave: isSettingEnabled(webSettings?.custom_property_show_share_save),
  showScheduleVisit: isSettingEnabled(webSettings?.custom_property_show_schedule_visit),
  showNearby: isSettingEnabled(webSettings?.custom_property_show_nearby),
});
