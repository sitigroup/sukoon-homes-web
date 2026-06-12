import 'dart:convert';

class AgentProfileModel {
  AgentProfileModel({
    this.id,
    this.customerId,
    this.agentName,
    this.agentEmail,
    this.agentAddress,
    this.agentMobile,
    this.agentCountryCode,
    this.agentProfilePhoto,
    this.instagramId,
    this.youtubeId,
    this.twitterId,
    this.facebookId,
    this.aboutMe,
    this.agentVerificationRejectReason,
  });

  factory AgentProfileModel.fromJson(String str) =>
      AgentProfileModel.fromMap(json.decode(str) as Map<String, dynamic>);

  factory AgentProfileModel.fromMap(Map<String, dynamic> json) =>
      AgentProfileModel(
        id: json['id'] as int?,
        customerId: json['customer_id'] as int?,
        agentName: json['agent_name']?.toString(),
        agentEmail: json['agent_email']?.toString(),
        agentAddress: json['agent_address']?.toString(),
        agentMobile: json['agent_mobile']?.toString(),
        agentCountryCode: json['agent_country_code']?.toString(),
        agentProfilePhoto: json['agent_profile_photo']?.toString(),
        instagramId: json['instagram_id']?.toString(),
        youtubeId: json['youtube_id']?.toString(),
        twitterId: json['twitter_id']?.toString(),
        facebookId: json['facebook_id']?.toString(),
        aboutMe: json['about_me']?.toString(),
        agentVerificationRejectReason:
            json['agent_verification_reject_reason']?.toString(),
      );

  final int? id;
  final int? customerId;
  final String? agentName;
  final String? agentEmail;
  final String? agentAddress;
  final String? agentMobile;
  final String? agentCountryCode;
  final String? agentProfilePhoto;
  final String? instagramId;
  final String? youtubeId;
  final String? twitterId;
  final String? facebookId;
  final String? aboutMe;
  final String? agentVerificationRejectReason;

  AgentProfileModel copyWith({
    int? id,
    int? customerId,
    String? agentName,
    String? agentEmail,
    String? agentAddress,
    String? agentMobile,
    String? agentCountryCode,
    String? agentProfilePhoto,
    String? instagramId,
    String? youtubeId,
    String? twitterId,
    String? facebookId,
    String? aboutMe,
    String? agentVerificationRejectReason,
  }) => AgentProfileModel(
    id: id ?? this.id,
    customerId: customerId ?? this.customerId,
    agentName: agentName ?? this.agentName,
    agentEmail: agentEmail ?? this.agentEmail,
    agentAddress: agentAddress ?? this.agentAddress,
    agentMobile: agentMobile ?? this.agentMobile,
    agentCountryCode: agentCountryCode ?? this.agentCountryCode,
    agentProfilePhoto: agentProfilePhoto ?? this.agentProfilePhoto,
    instagramId: instagramId ?? this.instagramId,
    youtubeId: youtubeId ?? this.youtubeId,
    twitterId: twitterId ?? this.twitterId,
    facebookId: facebookId ?? this.facebookId,
    aboutMe: aboutMe ?? this.aboutMe,
    agentVerificationRejectReason:
        agentVerificationRejectReason ?? this.agentVerificationRejectReason,
  );

  String toJson() => json.encode(toMap());

  Map<String, dynamic> toMap() => {
    'id': id,
    'customer_id': customerId,
    'agent_name': agentName,
    'agent_email': agentEmail,
    'agent_address': agentAddress,
    'agent_mobile': agentMobile,
    'agent_country_code': agentCountryCode,
    'agent_profile_photo': agentProfilePhoto,
    'instagram_id': instagramId,
    'youtube_id': youtubeId,
    'twitter_id': twitterId,
    'facebook_id': facebookId,
    'about_me': aboutMe,
    'agent_verification_reject_reason': agentVerificationRejectReason,
  };

  @override
  String toString() {
    return 'AgentProfileModel(id: $id, customerId: $customerId, agentName: $agentName, agentEmail: $agentEmail, agentAddress: $agentAddress, agentMobile: $agentMobile, agentCountryCode: $agentCountryCode, agentProfilePhoto: $agentProfilePhoto, instagramId: $instagramId, youtubeId: $youtubeId, twitterId: $twitterId, facebookId: $facebookId, aboutMe: $aboutMe)';
  }
}
