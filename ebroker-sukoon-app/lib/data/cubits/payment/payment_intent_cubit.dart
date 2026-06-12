import 'package:ebroker/data/repositories/payment_intent_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class PaymentIntentState {}

class PaymentIntentInitial extends PaymentIntentState {}

class PaymentIntentInProgress extends PaymentIntentState {}

class PaymentIntentSuccess extends PaymentIntentState {
  PaymentIntentSuccess(this.paymentIntent);
  final Map<String, dynamic> paymentIntent;
}

class PaymentIntentFailure extends PaymentIntentState {
  PaymentIntentFailure(this.error);
  final dynamic error;
}

class PaymentIntentCubit extends Cubit<PaymentIntentState> {
  PaymentIntentCubit({PaymentIntentRepository? repository})
    : _repository = repository ?? PaymentIntentRepository(),
      super(PaymentIntentInitial());

  final PaymentIntentRepository _repository;

  Future<Map<String, dynamic>?> createIntent({
    required String paymentMethod,
    int? packageId,
    int? payAsYouGoId,
  }) async {
    try {
      emit(PaymentIntentInProgress());
      final intent = await _repository.fetchPaymentIntent(
        packageId: packageId,
        payAsYouGoId: payAsYouGoId,
        paymentMethod: paymentMethod,
      );
      emit(PaymentIntentSuccess(intent));
      return intent;
    } on Exception catch (e) {
      emit(PaymentIntentFailure(e.toString()));
      return null;
    }
  }
}
