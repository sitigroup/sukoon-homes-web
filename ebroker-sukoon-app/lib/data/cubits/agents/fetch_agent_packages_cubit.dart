import 'package:ebroker/data/model/agent_package_model.dart';
import 'package:ebroker/data/repositories/subscription_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class FetchAgentPackagesState {}

class FetchAgentPackagesInitial extends FetchAgentPackagesState {}

class FetchAgentPackagesInProgress extends FetchAgentPackagesState {}

class FetchAgentPackagesSuccess extends FetchAgentPackagesState {
  FetchAgentPackagesSuccess({required this.response});
  final AgentPackageResponseModel response;
}

class FetchAgentPackagesFailure extends FetchAgentPackagesState {
  FetchAgentPackagesFailure(this.errorMessage);
  final dynamic errorMessage;
}

class FetchAgentPackagesCubit extends Cubit<FetchAgentPackagesState> {
  FetchAgentPackagesCubit() : super(FetchAgentPackagesInitial());
  final SubscriptionRepository _subscriptionRepository =
      SubscriptionRepository();

  Future<void> fetchPackages() async {
    try {
      emit(FetchAgentPackagesInProgress());
      final result = await _subscriptionRepository.getAgentPackages();
      emit(FetchAgentPackagesSuccess(response: result));
    } on Exception catch (e) {
      emit(FetchAgentPackagesFailure(e));
    }
  }
}
