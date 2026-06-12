import 'package:ebroker/data/model/agent_profile_model.dart';
import 'package:ebroker/data/repositories/agent_profile_repository.dart';
import 'package:ebroker/utils/api.dart';
import 'package:ebroker/utils/hive_utils.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class AgentProfileState {}

class AgentProfileInitial extends AgentProfileState {}

class AgentProfileInProgress extends AgentProfileState {}

class AgentProfileSuccess extends AgentProfileState {
  AgentProfileSuccess(this.agentProfile);
  final AgentProfileModel agentProfile;
}

class AgentProfileFailure extends AgentProfileState {
  AgentProfileFailure(this.errorMessage);
  final dynamic errorMessage;
}

class AgentProfileCubit extends Cubit<AgentProfileState> {
  AgentProfileCubit() : super(_initialStateFromCache());

  final AgentProfileRepository _repository = AgentProfileRepository();

  static AgentProfileState _initialStateFromCache() {
    final cached = HiveUtils.getAgentProfileData();
    if (cached == null) return AgentProfileInitial();
    return AgentProfileSuccess(cached);
  }

  Future<void> fetchAgentProfile() async {
    try {
      if (state is! AgentProfileSuccess) {
        emit(AgentProfileInProgress());
      }
      final profile = await _repository.getAgentProfile();
      await HiveUtils.setAgentProfileData(profile.toMap());
      emit(AgentProfileSuccess(profile));
    } on ApiException catch (e) {
      emit(AgentProfileFailure(e.toString()));
    } on Exception catch (e) {
      emit(AgentProfileFailure(e.toString()));
    }
  }

  Future<void> reset() async {
    await HiveUtils.clearAgentProfileData();
    emit(AgentProfileInitial());
  }
}
