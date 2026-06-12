import 'package:ebroker/data/model/subscription_pacakage_model.dart';
import 'package:ebroker/exports/main_export.dart';

import 'package:ebroker/utils/payment/payment_gateway_manager.dart';

class PaymentManager {
  PaymentManager();

  Future<void> pay({
    required BuildContext context,
    required String gatewayKey,
    SubscriptionPackageModel? package,
    PayAsYouGoModel? payAsYouGo,
    int? listingId,
    String? listingType,
  }) async {
    // if (Platform.isIOS) return; // Should be handled by UI using InAppPurchaseManager

    await PaymentGatewayManager().pay(
      context: context,
      package: package,
      payAsYouGo: payAsYouGo,
      paymentMethod: gatewayKey,
      listingId: listingId,
      listingType: listingType,
    );
  }
}
