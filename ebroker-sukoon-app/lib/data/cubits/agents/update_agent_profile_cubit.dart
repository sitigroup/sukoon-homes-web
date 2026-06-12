import 'dart:io';

import 'package:ebroker/data/repositories/agent_profile_repository.dart';
import 'package:ebroker/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class UpdateAgentProfileState {}

class UpdateAgentProfileInitial extends UpdateAgentProfileState {}

class UpdateAgentProfileInProgress extends UpdateAgentProfileState {}

class UpdateAgentProfileSuccess extends UpdateAgentProfileState {
  UpdateAgentProfileSuccess(this.response);
  final Map<String, dynamic> response;
}

class UpdateAgentProfileFailure extends UpdateAgentProfileState {
  UpdateAgentProfileFailure(this.errorMessage);
  final dynamic errorMessage;
}

class UpdateAgentProfileCubit extends Cubit<UpdateAgentProfileState> {
  UpdateAgentProfileCubit() : super(UpdateAgentProfileInitial());

  final AgentProfileRepository _repository = AgentProfileRepository();

  Future<void> updateProfile({
    String? agentName,
    String? email,
    String? mobile,
    String? countryCode,
    String? address,
    String? aboutMe,
    String? facebookId,
    String? twitterId,
    String? youtubeId,
    String? instagramId,
    File? profilePhoto,
  }) async {
    try {
      emit(UpdateAgentProfileInProgress());
      final response = await _repository.updateAgentProfile(
        agentName: agentName,
        email: email,
        mobile: mobile,
        countryCode: countryCode,
        address: address,
        aboutMe: aboutMe,
        facebookId: facebookId,

        twitterId: twitterId,
        youtubeId: youtubeId,
        instagramId: instagramId,
        profilePhoto: profilePhoto,
      );
      if (response['error'] == false) {
        emit(UpdateAgentProfileSuccess(response));
      } else {
        emit(UpdateAgentProfileFailure(response['message'].toString()));
      }
    } on ApiException catch (e) {
      emit(UpdateAgentProfileFailure(e.toString()));
    } on Exception catch (e) {
      emit(UpdateAgentProfileFailure(e.toString()));
    }
  }
}
