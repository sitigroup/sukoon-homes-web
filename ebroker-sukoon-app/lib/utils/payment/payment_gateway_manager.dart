import 'package:ebroker/data/model/subscription_pacakage_model.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/utils/payment/lib/purchase_package.dart';
import 'package:ebroker/utils/payment/payment_webview_screen.dart';
import 'package:flutter/material.dart';

class PaymentGatewayManager {
  factory PaymentGatewayManager() => _instance;
  PaymentGatewayManager._internal();
  // Singleton pattern
  static final PaymentGatewayManager _instance =
      PaymentGatewayManager._internal();

  /// Main method to initiate payment
  Future<void> pay({
    required BuildContext context,
    required String paymentMethod,
    SubscriptionPackageModel? package,
    PayAsYouGoModel? payAsYouGo,
    int? listingId,
    String? listingType,
  }) async {
    try {
      // 1. Create Payment Intent
      final intentResponse = await Api.post(
        url: Api.createPaymentIntent,
        parameter: {
          'platform_type': 'app', // Corrected per user feedback
          if (package != null) 'package_id': package.id,
          if (payAsYouGo != null) 'pay_as_you_go_id': payAsYouGo.id,
          'listing_id': ?listingId,
          'listing_type': ?listingType,
          'payment_method': paymentMethod.toLowerCase(),
        },
      );

      if (intentResponse['error'] == true) {
        throw Exception(
          intentResponse['message'] ?? 'Failed to create payment intent',
        );
      }

      final data = intentResponse['data'];
      final paymentIntent = data['payment_intent'];
      final paymentUrl = paymentIntent['payment_url']?.toString();

      if (paymentUrl == null || paymentUrl.isEmpty) {
        throw Exception('Payment URL is missing from response');
      }

      // 2. Open WebView
      final result = await Navigator.push<bool>(
        context,
        MaterialPageRoute(
          builder: (context) => PaymentWebViewScreen(
            url: paymentUrl,
            gateway: paymentMethod,
          ),
        ),
      );

      // 3. Handle Result
      if (result ?? false) {
        // Success
        await PurchasePackage().purchase(
          context,
          popToRoot: payAsYouGo == null,
        );
        if (payAsYouGo != null && context.mounted) {
          Navigator.pop(context, true);
        }
      } else {
        // Failure or Cancel
        HelperUtils.showSnackBarMessage(
          context,
          'purchaseFailed', // Loc key
          type: MessageType.error,
        );
      }
    } on Exception catch (e) {
      HelperUtils.showSnackBarMessage(
        context,
        e.toString(),
        type: MessageType.error,
      );
    }
  }
}
