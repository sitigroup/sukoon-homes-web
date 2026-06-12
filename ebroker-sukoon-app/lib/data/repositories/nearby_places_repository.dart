import 'package:ebroker/data/model/nearby_places_model.dart';
import 'package:ebroker/utils/api.dart';

class NearbyPlacesRepository {
  Future<NearbyPlacesPayload?> fetchForProperty(int propertyId) async {
    final response = await Api.get(
      url: '${Api.nearbyPlacesProperty}$propertyId',
      useAuthToken: true,
    );

    if (response['error'] == true) {
      return null;
    }

    final data = response['data'];
    if (data is! Map<String, dynamic>) {
      return null;
    }

    return NearbyPlacesPayload.fromMap(data);
  }
}
