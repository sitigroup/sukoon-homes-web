class NearbyPlacesPayload {
  NearbyPlacesPayload({
    required this.enabled,
    this.travelTimeEnabled = false,
    this.qualityFilterEnabled = false,
    this.propertyId,
    this.areaLabel,
    this.categories = const [],
    this.placesByCategory = const {},
    this.message,
  });

  factory NearbyPlacesPayload.fromMap(Map<String, dynamic> json) {
    final placesRaw = json['places'] as Map<String, dynamic>? ?? {};
    final placesByCategory = <String, List<NearbyPlaceItem>>{};
    for (final entry in placesRaw.entries) {
      placesByCategory[entry.key] = (entry.value as List? ?? [])
          .map(
            (item) => NearbyPlaceItem.fromMap(
              item as Map<String, dynamic>? ?? {},
            ),
          )
          .toList();
    }

    return NearbyPlacesPayload(
      enabled: json['enabled'] == true,
      travelTimeEnabled: json['travel_time_enabled'] == true,
      qualityFilterEnabled: json['quality_filter_enabled'] == true,
      propertyId: int.tryParse(json['property_id']?.toString() ?? ''),
      areaLabel: json['area_label']?.toString(),
      categories: (json['categories'] as List? ?? [])
          .map(
            (item) => NearbyPlaceCategory.fromMap(
              item as Map<String, dynamic>? ?? {},
            ),
          )
          .toList(),
      placesByCategory: placesByCategory,
      message: json['message']?.toString(),
    );
  }

  final bool enabled;
  final bool travelTimeEnabled;
  final bool qualityFilterEnabled;
  final int? propertyId;
  final String? areaLabel;
  final List<NearbyPlaceCategory> categories;
  final Map<String, List<NearbyPlaceItem>> placesByCategory;
  final String? message;

  bool get hasPlaces => placesByCategory.values.any((items) => items.isNotEmpty);
}

class NearbyPlaceCategory {
  NearbyPlaceCategory({
    required this.id,
    required this.name,
    required this.slug,
    this.icon,
  });

  factory NearbyPlaceCategory.fromMap(Map<String, dynamic> json) {
    return NearbyPlaceCategory(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      icon: json['icon']?.toString(),
    );
  }

  final int id;
  final String name;
  final String slug;
  final String? icon;
}

class NearbyPlaceItem {
  NearbyPlaceItem({
    required this.name,
    this.distanceText,
    this.rating,
    this.reviewCount,
    this.isOpen,
    this.directionsUrl,
    this.showDirections = false,
    this.vicinity,
  });

  factory NearbyPlaceItem.fromMap(Map<String, dynamic> json) {
    return NearbyPlaceItem(
      name: json['name']?.toString() ?? '',
      distanceText: json['distance_text']?.toString(),
      rating: double.tryParse(json['rating']?.toString() ?? ''),
      reviewCount: int.tryParse(json['review_count']?.toString() ?? ''),
      isOpen: json['is_open'] == true,
      directionsUrl: json['directions_url']?.toString(),
      showDirections: json['show_directions'] == true,
      vicinity: json['vicinity']?.toString(),
    );
  }

  final String name;
  final String? distanceText;
  final double? rating;
  final int? reviewCount;
  final bool? isOpen;
  final String? directionsUrl;
  final bool showDirections;
  final String? vicinity;
}
