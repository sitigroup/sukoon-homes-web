import 'package:flutter/services.dart';

class MapStyleCache {
  MapStyleCache._();

  static String? _cachedDark;

  static Future<String> darkMapStyle() async {
    if (_cachedDark != null) return _cachedDark!;
    try {
      _cachedDark = await rootBundle.loadString('assets/map_styles/dark_map.json');
    } on Exception {
      _cachedDark = '';
    }
    return _cachedDark!;
  }
}
