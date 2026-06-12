import 'package:ebroker/data/model/area_listing_models.dart';
import 'package:ebroker/data/repositories/area_listing_repository.dart';

/// Resolves state/city master IDs and area/sub-area from map pin via API.
class AreaListingLocationSync {
  AreaListingLocationSync._();

  static final AreaListingRepository _repository = AreaListingRepository();

  static Future<AreaListingSelection> afterAddressFieldsChanged({
    required AreaListingSelection current,
    required String city,
    required String state,
    required String country,
    bool clearAreaOnCityChange = true,
  }) async {
    final trimmedCity = city.trim();
    final trimmedState = state.trim();
    final trimmedCountry = country.trim().isEmpty ? 'India' : country.trim();

    final cityChanged =
        (current.cityName ?? '').trim().toLowerCase() !=
        trimmedCity.toLowerCase();

    var next = current.copyWith(
      cityName: trimmedCity.isEmpty ? null : trimmedCity,
      stateName: trimmedState.isEmpty ? null : trimmedState,
      countryName: trimmedCountry,
      source: 'google',
    );

    if (clearAreaOnCityChange && cityChanged) {
      next = next.clearAreaSelection();
    }

    if (trimmedCity.isEmpty) {
      return next;
    }

    try {
      final states = await _repository.fetchStates(country: trimmedCountry);
      AreaListingItem? stateItem;
      if (trimmedState.isNotEmpty) {
        stateItem = _matchByName(states, trimmedState);
      }
      stateItem ??= states.isNotEmpty ? states.first : null;

      if (stateItem == null) {
        return next;
      }

      final cities = await _repository.fetchCities(
        stateId: stateItem.id,
        state: stateItem.name,
        country: trimmedCountry,
      );
      final cityItem = _matchByName(cities, trimmedCity);
      if (cityItem == null) {
        return next.copyWith(
          stateId: stateItem.id,
          stateName: stateItem.name,
          cityId: null,
        );
      }

      return next.copyWith(
        stateId: stateItem.id,
        stateName: stateItem.name,
        cityId: cityItem.id,
        cityName: cityItem.name,
      );
    } on Exception {
      return next;
    }
  }

  /// After map pin / GPS: sync city IDs, then resolve area/sub-area from coordinates.
  static Future<AreaListingSelection> afterMapPick({
    required AreaListingSelection current,
    required double latitude,
    required double longitude,
    required String city,
    required String state,
    required String country,
    List<Map<String, dynamic>>? locationComponents,
    bool applyResolvedIds = false,
  }) async {
    final components = locationComponents ?? [];
    final trimmedCountry =
        country.trim().isEmpty ? 'India' : country.trim();

    // Every pin move clears prior area/sub-area (same as website LocationComponent).
    var next = current.clearAreaSelection();

    next = await afterAddressFieldsChanged(
      current: next,
      city: city,
      state: state,
      country: trimmedCountry,
      clearAreaOnCityChange: false,
    );

    Map<String, dynamic>? resolved;
    try {
      resolved = await _repository.resolveCoordinates(
        latitude: latitude,
        longitude: longitude,
        cityId: next.cityId,
        city: city,
        state: state,
        country: trimmedCountry,
        locationComponents: components,
      );
    } on Exception {
      resolved = null;
    }

    if (resolved != null && resolved.isNotEmpty) {
      next = next.applyResolveResult(
        resolved,
        applyResolvedIds: applyResolvedIds,
      );
    }

    if (next.suggestedAreaLabel.isEmpty) {
      next = _mergeDetectedFromComponents(next, components);
    }

    return next;
  }

  static AreaListingSelection _mergeDetectedFromComponents(
    AreaListingSelection base,
    List<Map<String, dynamic>> components,
  ) {
    final fromComponents = extractDetectedFromComponents(components);
    if (fromComponents.detectedAreaName == null &&
        fromComponents.detectedSubAreaName == null) {
      return base;
    }
    return base.copyWith(
      detectedAreaName: fromComponents.detectedAreaName ?? base.detectedAreaName,
      detectedSubAreaName:
          fromComponents.detectedSubAreaName ?? base.detectedSubAreaName,
      areaName: fromComponents.detectedAreaName ?? base.areaName,
      subAreaName: fromComponents.detectedSubAreaName ?? base.subAreaName,
      source: 'google',
      userConfirmed: false,
    );
  }

  /// City/state from map screen result (Placemark + Google address_components).
  static ({
    String city,
    String state,
    String country,
    List<Map<String, dynamic>> components,
  }) parseMapPickResult({
    required dynamic rawComponents,
    String? placemarkCity,
    String? placemarkState,
    String? placemarkCountry,
  }) {
    final components = parseAddressComponents(rawComponents);
    final fromComp = cityStateCountryFromComponents(components);

    var city = (placemarkCity ?? '').trim();
    if (city.isEmpty) {
      city = fromComp['city'] ?? '';
    }

    var state = (placemarkState ?? '').trim();
    if (state.isEmpty) {
      state = fromComp['state'] ?? '';
    }

    var country = (placemarkCountry ?? '').trim();
    if (country.isEmpty) {
      country = fromComp['country'] ?? '';
    }
    if (country.isEmpty) {
      country = 'India';
    }

    return (
      city: city,
      state: state,
      country: country,
      components: components,
    );
  }

  static Map<String, String> cityStateCountryFromComponents(
    List<Map<String, dynamic>> components,
  ) {
    String pick(List<String> types) {
      for (final component in components) {
        final compTypes = List<String>.from(component['types'] as List? ?? []);
        if (types.any(compTypes.contains)) {
          final name = component['name']?.toString().trim() ?? '';
          if (name.isNotEmpty) return _titleCase(name);
        }
      }
      return '';
    }

    final city = pick([
      'locality',
      'postal_town',
      'administrative_area_level_2',
      'sublocality_level_1',
    ]);
    final state = pick(['administrative_area_level_1']);
    final country = pick(['country']);

    return {
      'city': city,
      'state': state,
      'country': country,
    };
  }

  static List<Map<String, dynamic>> parseAddressComponents(dynamic raw) {
    if (raw == null) return [];
    if (raw is! List) return [];
    return raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .map(
          (c) => {
            'name': c['name']?.toString() ??
                c['long_name']?.toString() ??
                c['short_name']?.toString() ??
                '',
            'types': List<String>.from(c['types'] as List? ?? []),
          },
        )
        .where((c) => (c['name'] as String).isNotEmpty)
        .toList();
  }

  static ({String? detectedAreaName, String? detectedSubAreaName})
      extractDetectedFromComponents(List<Map<String, dynamic>> components) {
    String find(List<String> types) {
      for (final component in components) {
        final compTypes = List<String>.from(component['types'] as List? ?? []);
        if (types.any(compTypes.contains)) {
          final name = component['name']?.toString().trim() ?? '';
          if (name.isNotEmpty) return _titleCase(name);
        }
      }
      return '';
    }

    final area = find([
      'sublocality_level_1',
      'neighborhood',
      'administrative_area_level_3',
      'administrative_area_level_2',
    ]);
    var subArea = find([
      'sublocality_level_2',
      'sublocality_level_3',
      'route',
      'premise',
      'point_of_interest',
    ]);
    if (subArea.isEmpty) {
      subArea = find(['sublocality', 'locality']);
    }

    return (
      detectedAreaName: area.isEmpty ? null : area,
      detectedSubAreaName: subArea.isEmpty ? null : subArea,
    );
  }

  static String _titleCase(String value) {
    return value
        .trim()
        .split(RegExp(r'\s+'))
        .map(
          (word) => word.isEmpty
              ? ''
              : '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}',
        )
        .join(' ');
  }

  static AreaListingItem? _matchByName(List<AreaListingItem> items, String name) {
    final target = name.trim().toLowerCase();
    if (target.isEmpty) return null;
    for (final item in items) {
      if (item.name.trim().toLowerCase() == target) {
        return item;
      }
    }
    return null;
  }
}

extension AreaListingSelectionSync on AreaListingSelection {
  AreaListingSelection clearAreaSelection() {
    return AreaListingSelection(
      stateId: stateId,
      stateName: stateName,
      cityId: cityId,
      cityName: cityName,
      countryName: countryName,
      manualAddress: manualAddress,
      source: source,
      userConfirmed: false,
    );
  }

  AreaListingSelection applyResolveResult(
    Map<String, dynamic>? data, {
    bool applyResolvedIds = false,
  }) {
    if (data == null || data.isEmpty) {
      return this;
    }

    final detectedArea =
        data['detected_area_name']?.toString() ??
        data['area_name']?.toString() ??
        '';
    final detectedSub =
        data['detected_sub_area_name']?.toString() ??
        data['sub_area_name']?.toString() ??
        '';
    final resolvedCityId = int.tryParse(data['city_id']?.toString() ?? '');
    final resolvedAreaId = int.tryParse(data['area_id']?.toString() ?? '');
    final resolvedSubAreaId = int.tryParse(data['sub_area_id']?.toString() ?? '');

    if (applyResolvedIds && resolvedAreaId != null && resolvedAreaId > 0) {
      return copyWith(
        cityId: resolvedCityId ?? cityId,
        areaId: resolvedAreaId,
        areaName: data['area_name']?.toString() ??
            (detectedArea.isEmpty ? null : detectedArea),
        subAreaId: resolvedSubAreaId,
        subAreaName: data['sub_area_name']?.toString(),
        detectedAreaName: detectedArea.isEmpty ? null : detectedArea,
        detectedSubAreaName: detectedSub.isEmpty ? null : detectedSub,
        source: 'google',
        userConfirmed: true,
      );
    }

    if (resolvedAreaId != null && resolvedAreaId > 0 && !applyResolvedIds) {
      return clearAreaSelection().copyWith(
        cityId: resolvedCityId ?? cityId,
        detectedAreaName: detectedArea.isEmpty ? null : detectedArea,
        detectedSubAreaName: detectedSub.isEmpty ? null : detectedSub,
        areaName: detectedArea.isEmpty ? null : detectedArea,
        subAreaName: detectedSub.isEmpty ? null : detectedSub,
        source: 'google',
        userConfirmed: false,
      );
    }

    return clearAreaSelection().copyWith(
      cityId: resolvedCityId ?? cityId,
      detectedAreaName: detectedArea.isEmpty ? null : detectedArea,
      detectedSubAreaName: detectedSub.isEmpty ? null : detectedSub,
      areaName: detectedArea.isEmpty ? null : detectedArea,
      subAreaName: detectedSub.isEmpty ? null : detectedSub,
      source: 'google',
      userConfirmed: false,
    );
  }
}
