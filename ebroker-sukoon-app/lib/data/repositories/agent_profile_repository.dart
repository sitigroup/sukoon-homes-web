import 'dart:io';

import 'package:dio/dio.dart';
import 'package:ebroker/data/model/agent_profile_model.dart';
import 'package:ebroker/utils/api.dart';

class AgentProfileRepository {
  Future<AgentProfileModel> getAgentProfile() async {
    final response = await Api.get(url: Api.apiGetAgentProfile);
    return AgentProfileModel.fromMap(
      response['data'] as Map<String, dynamic>? ?? {},
    );
  }

  Future<Map<String, dynamic>> updateAgentProfile({
    String? agentName,
    String? email,
    String? address,
    String? aboutMe,
    String? mobile,
    String? countryCode,
    String? facebookId,
    String? twitterId,
    String? youtubeId,
    String? instagramId,
    File? profilePhoto,
  }) async {
    final params = <String, dynamic>{
      'agent_name': agentName,
      'agent_email': email,
      'about_me': aboutMe,
      'agent_address': address,
      'agent_mobile': mobile,
      'country_code': countryCode,
      'facebook_id': facebookId,
      'twitter_id': twitterId,
      'youtube_id': youtubeId,
      'instagram_id': instagramId,
    };

    if (profilePhoto != null) {
      params['agent_profile_photo'] = await MultipartFile.fromFile(
        profilePhoto.path,
      );
    }

    return Api.post(
      url: Api.apiUpdateAgentProfile,
      parameter: params,
    );
  }
}
