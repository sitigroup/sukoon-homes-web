import 'package:ebroker/data/model/agent_profile_model.dart';
import 'package:ebroker/utils/admob/native_ad_manager.dart';

class CustomerData implements NativeAdWidgetContainer {
  const CustomerData({
    required this.id,
    required this.slugId,
    required this.name,
    required this.profile,
    required this.mobile,
    required this.email,
    required this.address,
    required this.city,
    required this.country,
    required this.state,
    required this.projectCount,
    required this.propertyCount,
    required this.propertiesSoldCount,
    required this.propertiesRentedCount,
    required this.isAppointmentAvailable,
    required this.isAgentVerified,
    required this.isAdmin,
    required this.agentProfile,
  });

  CustomerData.fromJson(Map<String, dynamic> json)
    : id = json['id'] as int,
      slugId = json['slug_id']?.toString() ?? '',
      name = json['name']?.toString() ?? '',
      profile = json['profile']?.toString() ?? '',
      mobile = json['mobile']?.toString() ?? '',
      email = json['email']?.toString() ?? '',
      address = json['address']?.toString() ?? '',
      city = json['city']?.toString() ?? '',
      country = json['country']?.toString() ?? '',
      state = json['state']?.toString() ?? '',
      projectCount = json['projects_count']?.toString() ?? '',
      propertyCount = json['property_count']?.toString() ?? '',
      propertiesSoldCount = json['properties_sold_count']?.toString() ?? '',
      propertiesRentedCount = json['properties_rented_count']?.toString() ?? '',
      isAppointmentAvailable =
          json['is_appointment_available'] as bool? ?? false,
      isAgentVerified = json['is_agent_verified'] as bool? ?? false,
      agentProfile = AgentProfileModel.fromMap(
        json['agent_profile'] as Map<String, dynamic>? ?? {},
      ),
      isAdmin = json['is_admin'] as bool? ?? false;

  final int id;
  final String slugId;
  final String name;
  final String profile;
  final String mobile;
  final String email;
  final String address;
  final String city;
  final String country;
  final String state;
  final String projectCount;
  final String propertyCount;
  final String propertiesSoldCount;
  final String propertiesRentedCount;
  final bool isAppointmentAvailable;
  final bool isAgentVerified;
  final bool isAdmin;
  final AgentProfileModel agentProfile;
}
