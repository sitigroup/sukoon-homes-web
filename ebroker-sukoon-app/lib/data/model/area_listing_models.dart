class AreaListingItem {
  AreaListingItem({
    required this.id,
    required this.name,
    this.slug,
    this.city,
    this.state,
    this.country,
    this.subAreas = const [],
  });

  factory AreaListingItem.fromMap(Map<String, dynamic> json) {
    return AreaListingItem(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString(),
      city: json['city']?.toString(),
      state: json['state']?.toString(),
      country: json['country']?.toString(),
      subAreas: (json['sub_areas'] as List? ?? [])
          .map(
            (item) => AreaListingSubArea.fromMap(
              item as Map<String, dynamic>? ?? {},
            ),
          )
          .toList(),
    );
  }

  final int id;
  final String name;
  final String? slug;
  final String? city;
  final String? state;
  final String? country;
  final List<AreaListingSubArea> subAreas;
}

class AreaListingSubArea {
  AreaListingSubArea({
    required this.id,
    required this.areaId,
    required this.name,
    this.slug,
  });

  factory AreaListingSubArea.fromMap(Map<String, dynamic> json) {
    return AreaListingSubArea(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      areaId: int.tryParse(json['area_id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString(),
    );
  }

  final int id;
  final int areaId;
  final String name;
  final String? slug;
}

class AreaListingSelection {
  AreaListingSelection({
    this.stateId,
    this.stateName,
    this.cityId,
    this.cityName,
    this.countryName,
    this.areaId,
    this.areaName,
    this.subAreaId,
    this.subAreaName,
    this.detectedAreaName,
    this.detectedSubAreaName,
    this.manualAddress,
    this.source = 'mobile',
    this.userConfirmed = false,
  });

  factory AreaListingSelection.fromMap(Map<String, dynamic>? json) {
    if (json == null || json.isEmpty) {
      return AreaListingSelection();
    }
    return AreaListingSelection(
      stateId: int.tryParse(json['state_id']?.toString() ?? ''),
      stateName: json['state_name']?.toString() ?? json['state']?.toString(),
      cityId: int.tryParse(json['city_id']?.toString() ?? ''),
      cityName: json['city_name']?.toString() ?? json['city']?.toString(),
      countryName: json['country_name']?.toString() ?? json['country']?.toString(),
      areaId: int.tryParse(json['area_id']?.toString() ?? ''),
      areaName: json['area_name']?.toString(),
      subAreaId: int.tryParse(json['sub_area_id']?.toString() ?? ''),
      subAreaName: json['sub_area_name']?.toString(),
      detectedAreaName: json['detected_area_name']?.toString(),
      detectedSubAreaName: json['detected_sub_area_name']?.toString(),
      manualAddress: json['manual_address']?.toString(),
      source: json['area_listing_source']?.toString() ?? 'mobile',
      userConfirmed: json['user_confirmed'] == true || json['user_confirmed'] == 1,
    );
  }

  static Map<String, dynamic>? extractAreaListingMap(dynamic source) {
    if (source is Map<String, dynamic>) {
      final nested = source['area_listing'];
      if (nested is Map<String, dynamic>) return nested;
      if (nested is Map) return Map<String, dynamic>.from(nested);
    }
    if (source is Map) {
      final nested = source['area_listing'];
      if (nested is Map) return Map<String, dynamic>.from(nested);
    }
    return null;
  }

  int? stateId;
  String? stateName;
  int? cityId;
  String? cityName;
  String? countryName;
  int? areaId;
  String? areaName;
  int? subAreaId;
  String? subAreaName;
  String? detectedAreaName;
  String? detectedSubAreaName;
  String? manualAddress;
  String source;
  bool userConfirmed;

  bool get hasArea => areaId != null && areaId! > 0;

  bool get isValidForSubmit =>
      hasArea ||
      ((detectedAreaName ?? areaName ?? '').trim().isNotEmpty &&
          userConfirmed);

  String get suggestedAreaLabel {
    final parts = <String>[
      if ((detectedAreaName ?? areaName ?? '').isNotEmpty)
        (detectedAreaName ?? areaName)!,
    ];
    return parts.join(', ');
  }

  String get suggestedSubAreaLabel =>
      (detectedSubAreaName ?? subAreaName ?? '').trim();

  String get displayLabel {
    final parts = <String>[
      if ((subAreaName ?? '').isNotEmpty) subAreaName!,
      if ((areaName ?? '').isNotEmpty) areaName!,
      if ((cityName ?? '').isNotEmpty) cityName!,
      if ((stateName ?? '').isNotEmpty) stateName!,
    ];
    return parts.join(', ');
  }

  AreaListingSelection copyWith({
    int? stateId,
    String? stateName,
    int? cityId,
    String? cityName,
    String? countryName,
    int? areaId,
    String? areaName,
    int? subAreaId,
    String? subAreaName,
    String? detectedAreaName,
    String? detectedSubAreaName,
    String? manualAddress,
    String? source,
    bool? userConfirmed,
  }) {
    return AreaListingSelection(
      stateId: stateId ?? this.stateId,
      stateName: stateName ?? this.stateName,
      cityId: cityId ?? this.cityId,
      cityName: cityName ?? this.cityName,
      countryName: countryName ?? this.countryName,
      areaId: areaId ?? this.areaId,
      areaName: areaName ?? this.areaName,
      subAreaId: subAreaId ?? this.subAreaId,
      subAreaName: subAreaName ?? this.subAreaName,
      detectedAreaName: detectedAreaName ?? this.detectedAreaName,
      detectedSubAreaName: detectedSubAreaName ?? this.detectedSubAreaName,
      manualAddress: manualAddress ?? this.manualAddress,
      source: source ?? this.source,
      userConfirmed: userConfirmed ?? this.userConfirmed,
    );
  }

  Map<String, dynamic> toApiPayload() {
    return {
      if (stateId != null) 'state_id': stateId,
      if (cityId != null) 'city_id': cityId,
      if (areaId != null) 'area_id': areaId,
      if (subAreaId != null) 'sub_area_id': subAreaId,
      if ((areaName ?? '').isNotEmpty) 'area_name': areaName,
      if ((subAreaName ?? '').isNotEmpty) 'sub_area_name': subAreaName,
      if ((detectedAreaName ?? '').isNotEmpty)
        'detected_area_name': detectedAreaName,
      if ((detectedSubAreaName ?? '').isNotEmpty)
        'detected_sub_area_name': detectedSubAreaName,
      if ((manualAddress ?? '').isNotEmpty) 'manual_address': manualAddress,
      'area_listing_source': source,
      'user_confirmed': userConfirmed,
    };
  }
}

String buildAreaListingLabel(
  Map<String, dynamic>? areaListing,
  String? city,
  String? state,
) {
  final selection = AreaListingSelection.fromMap(areaListing);
  final label = selection.displayLabel;
  if (label.isNotEmpty) {
    return label;
  }
  final parts = <String>[
    if ((city ?? '').isNotEmpty) city!,
    if ((state ?? '').isNotEmpty) state!,
  ];
  return parts.join(', ');
}
