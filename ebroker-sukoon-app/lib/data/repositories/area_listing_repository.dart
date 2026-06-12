import 'dart:convert';

import 'package:ebroker/data/model/area_listing_models.dart';
import 'package:ebroker/utils/api.dart';

class AreaListingRepository {
  Future<List<AreaListingItem>> fetchStates({String country = 'India'}) async {
    final response = await Api.get(
      url: Api.areaListingStates,
      queryParameters: {'country': country},
      useAuthToken: false,
    );
    return _parseItems(response['data']);
  }

  Future<List<AreaListingItem>> fetchCities({
    int? stateId,
    String? state,
    String country = 'India',
  }) async {
    final response = await Api.get(
      url: Api.areaListingCities,
      queryParameters: {
        if (stateId != null) 'state_id': stateId,
        if (state != null && state.isNotEmpty) 'state': state,
        'country': country,
      },
      useAuthToken: false,
    );
    return _parseItems(response['data']);
  }

  Future<List<AreaListingItem>> fetchAreas({
    int? cityId,
    String? city,
    int? stateId,
  }) async {
    final response = await Api.get(
      url: Api.areaListingAreas,
      queryParameters: {
        if (cityId != null) 'city_id': cityId,
        if (city != null && city.isNotEmpty) 'city': city,
        if (stateId != null) 'state_id': stateId,
      },
      useAuthToken: false,
    );
    return _parseItems(response['data']);
  }

  Future<List<AreaListingSubArea>> fetchSubAreas({required int areaId}) async {
    final response = await Api.get(
      url: Api.areaListingSubAreas,
      queryParameters: {'area_id': areaId},
      useAuthToken: false,
    );
    return (response['data'] as List? ?? [])
        .map(
          (item) => AreaListingSubArea.fromMap(
            item as Map<String, dynamic>? ?? {},
          ),
        )
        .toList();
  }

  Future<bool> fetchCanManageAreaListing() async {
    final response = await Api.get(url: Api.areaListingPermissions);
    final data = response['data'] as Map<String, dynamic>? ?? {};
    return data['can_manage_area_listing'] == true ||
        data['can_manage_area_listing'] == 1;
  }

  Future<Map<String, dynamic>?> resolveCoordinates({
    required double latitude,
    required double longitude,
    int? cityId,
    String? city,
    String? state,
    String? country,
    List<Map<String, dynamic>>? locationComponents,
  }) async {
    final response = await Api.post(
      url: Api.areaListingResolveCoordinates,
      parameter: {
        'latitude': latitude,
        'longitude': longitude,
        if (cityId != null) 'city_id': cityId,
        if (city != null && city.isNotEmpty) 'city': city,
        if (state != null && state.isNotEmpty) 'state': state,
        if (country != null && country.isNotEmpty) 'country': country,
        'location_components': jsonEncode(locationComponents ?? []),
      },
    );
    final data = response['data'];
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return Map<String, dynamic>.from(data);
    return null;
  }

  Future<AreaListingItem?> createArea({
    required String name,
    int? cityId,
    String? city,
    String? state,
    String? country,
  }) async {
    final response = await Api.post(
      url: 'area-listing/areas',
      parameter: {
        'name': name,
        if (cityId != null) 'city_id': cityId,
        if (city != null && city.isNotEmpty) 'city': city,
        if (state != null && state.isNotEmpty) 'state': state,
        if (country != null && country.isNotEmpty) 'country': country,
      },
    );
    final data = response['data'];
    if (data is Map<String, dynamic>) {
      return AreaListingItem.fromMap(data);
    }
    if (data is Map) {
      return AreaListingItem.fromMap(Map<String, dynamic>.from(data));
    }
    return null;
  }

  Future<void> suggestArea({
    required String name,
    required String city,
    String? state,
    String? country,
    String? subAreaName,
    int? areaId,
  }) async {
    await Api.post(
      url: Api.areaListingSuggestArea,
      parameter: {
        'name': name,
        'city': city,
        if (state != null && state.isNotEmpty) 'state': state,
        if (country != null && country.isNotEmpty) 'country': country,
        if (subAreaName != null && subAreaName.isNotEmpty)
          'sub_area_name': subAreaName,
        if (areaId != null) 'area_id': areaId,
      },
    );
  }

  Future<void> suggestSubArea({
    required int areaId,
    required String name,
  }) async {
    await Api.post(
      url: Api.areaListingSuggestSubArea,
      parameter: {
        'area_id': areaId,
        'name': name,
      },
    );
  }

  Future<AreaListingSubArea?> createSubArea({
    required int areaId,
    required String name,
  }) async {
    final response = await Api.post(
      url: 'area-listing/sub-areas',
      parameter: {
        'area_id': areaId,
        'name': name,
      },
    );
    final data = response['data'];
    if (data is Map<String, dynamic>) {
      return AreaListingSubArea.fromMap(data);
    }
    if (data is Map) {
      return AreaListingSubArea.fromMap(Map<String, dynamic>.from(data));
    }
    return null;
  }

  List<AreaListingItem> _parseItems(dynamic data) {
    return (data as List? ?? [])
        .map(
          (item) => AreaListingItem.fromMap(
            item as Map<String, dynamic>? ?? {},
          ),
        )
        .toList();
  }
}
