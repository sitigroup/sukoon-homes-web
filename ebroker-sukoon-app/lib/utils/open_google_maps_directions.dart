import 'dart:convert';

import 'package:url_launcher/url_launcher.dart';

class NormalizedLatLng {
  const NormalizedLatLng({required this.lat, required this.lng});

  final double lat;
  final double lng;
}

NormalizedLatLng? listingMapCoordinates({
  required Map<String, dynamic> details,
}) {
  final areaListing = details['area_listing'];
  if (areaListing is Map) {
    final map = Map<String, dynamic>.from(areaListing);
    final lat = double.tryParse('${map['latitude'] ?? map['lat'] ?? ''}');
    final lng = double.tryParse('${map['longitude'] ?? map['lng'] ?? ''}');
    if (lat != null && lng != null && lat != 0 && lng != 0) {
      return NormalizedLatLng(lat: lat, lng: lng);
    }
  }

  final lat = double.tryParse('${details['latitude'] ?? ''}');
  final lng = double.tryParse('${details['longitude'] ?? ''}');
  if (lat != null && lng != null && lat != 0 && lng != 0) {
    return NormalizedLatLng(lat: lat, lng: lng);
  }
  return null;
}

Future<bool> openDirectionsForListing(Map<String, dynamic> details) async {
  final coords = listingMapCoordinates(details: details);
  if (coords == null) return false;

  final uri = Uri.parse(
    'https://www.google.com/maps/dir/?api=1&destination=${coords.lat},${coords.lng}',
  );
  return launchUrl(uri, mode: LaunchMode.externalApplication);
}

String encodeLocationComponents(List<Map<String, dynamic>> components) {
  return jsonEncode(components);
}
