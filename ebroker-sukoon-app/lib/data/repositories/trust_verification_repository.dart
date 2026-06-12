import 'package:ebroker/data/model/trust_verification_models.dart';
import 'package:ebroker/utils/api.dart';

class TrustVerificationRepository {
  Future<TrustVerificationProfile?> fetchPublicTrust(int customerId) async {
    if (customerId <= 0) return null;
    final response = await Api.get(
      url: Api.trustVerificationPublicTrust(customerId),
      useAuthToken: false,
    );
    final data = response['data'];
    if (data == null) return null;
    return TrustVerificationProfile.fromMap(
      data as Map<String, dynamic>?,
    );
  }

  Future<TrustVerificationProfile> fetchMyTrustProfile() async {
    final response = await Api.get(url: Api.trustVerificationTrustProfile);
    return TrustVerificationProfile.fromMap(
      response['data'] as Map<String, dynamic>?,
    );
  }

  Future<List<TrustVerificationOrderSummary>> fetchMyOrders() async {
    final response = await Api.get(url: Api.trustVerificationOrders);
    final list = response['data'] as List? ?? [];
    return list
        .map(
          (e) => TrustVerificationOrderSummary.fromMap(
            e as Map<String, dynamic>? ?? {},
          ),
        )
        .toList();
  }

  Future<List<TrustVerificationIssuedBadge>> fetchMyVerificationBadges() async {
    final response = await Api.get(
      url: Api.trustVerificationVerificationBadges,
    );
    final list = response['data'] as List? ?? [];
    return list
        .map(
          (e) => TrustVerificationIssuedBadge.fromMap(
            e as Map<String, dynamic>? ?? {},
          ),
        )
        .toList();
  }

  Future<TrustVerificationOrderDetail> fetchOrderDetail(int orderId) async {
    final response = await Api.get(url: Api.trustVerificationOrder(orderId));
    return TrustVerificationOrderDetail.fromMap(
      response['data'] as Map<String, dynamic>? ?? {},
    );
  }

  Future<List<TrustVerificationCity>> fetchCities() async {
    final response = await Api.get(
      url: Api.trustVerificationCities,
      useAuthToken: false,
    );
    final list = response['data'] as List? ?? [];
    return list
        .map(
          (e) => TrustVerificationCity.fromMap(
            e as Map<String, dynamic>? ?? {},
          ),
        )
        .where((c) => c.slug.isNotEmpty)
        .toList();
  }

  Future<List<TrustVerificationPackage>> fetchPackages({
    required String type,
    required String citySlug,
  }) async {
    final response = await Api.get(
      url: Api.trustVerificationPackages,
      queryParameters: {'type': type, 'city': citySlug},
      useAuthToken: false,
    );
    final list = response['data'] as List? ?? [];
    return list
        .map(
          (e) => TrustVerificationPackage.fromMap(
            e as Map<String, dynamic>? ?? {},
          ),
        )
        .where((p) => p.id > 0)
        .toList();
  }

  Future<TrustVerificationOrderDetail> submitOrder(
    Map<String, dynamic> payload,
  ) async {
    final response = await Api.post(
      url: Api.trustVerificationOrders,
      parameter: payload,
    );
    if (response['error'] == true) {
      throw ApiException(response['message']?.toString() ?? 'Order failed');
    }
    return TrustVerificationOrderDetail.fromMap(
      response['data'] as Map<String, dynamic>? ?? {},
    );
  }

  Future<String> createPaymentUrl(int orderId) async {
    final response = await Api.post(
      url: Api.trustVerificationPaymentIntent(orderId),
      parameter: {
        'payment_method': 'cashfree',
        'platform_type': 'app',
      },
    );
    if (response['error'] == true) {
      throw ApiException(
        response['message']?.toString() ?? 'Payment failed',
      );
    }
    final data = response['data'] as Map<String, dynamic>? ?? {};
    final intent = data['payment_intent'] as Map<String, dynamic>? ?? data;
    final url = intent['payment_url']?.toString() ??
        intent['url']?.toString() ??
        data['payment_url']?.toString();
    if (url == null || url.isEmpty) {
      throw ApiException('Payment URL missing');
    }
    return url;
  }

  Future<void> cancelOrder(int orderId) async {
    final response = await Api.post(
      url: Api.trustVerificationCancelOrder(orderId),
      parameter: {},
    );
    if (response['error'] == true) {
      throw ApiException(response['message']?.toString() ?? 'Cancel failed');
    }
  }
}
