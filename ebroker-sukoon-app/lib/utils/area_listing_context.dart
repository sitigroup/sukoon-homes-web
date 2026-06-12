/// Feature flags for Area Listing on mobile (aligned with Sukoon web portal rules).
class AreaListingContext {
  AreaListingContext._();

  /// Regular users call suggest APIs; agents with backend permission can create areas.
  static const bool useUserPortalSaveRules = true;

  /// When false, map resolve only fills detected names until user confirms in picker.
  static const bool applyResolvedIdsOnMap = true;
}
