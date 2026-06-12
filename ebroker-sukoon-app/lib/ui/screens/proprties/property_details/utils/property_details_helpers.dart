import 'package:ebroker/data/model/agent/agents_properties_models/customer_data.dart';
import 'package:ebroker/data/model/agent/agents_properties_models/properties_data.dart';
import 'package:ebroker/data/model/agent_profile_model.dart';
import 'package:ebroker/data/model/category.dart';
import 'package:ebroker/exports/main_export.dart';

class PropertyDetailsHelpers {
  const PropertyDetailsHelpers._();

  static PropertiesData toPropertiesData(PropertyModel property) {
    return PropertiesData(
      id: property.id ?? 0,
      slugId: property.slugId ?? '',
      city: property.city ?? '',
      state: property.state ?? '',
      country: property.country ?? '',
      price: property.price?.toString() ?? '',
      categoryId: property.category?.id?.toString() ?? '',
      propertyType: property.propertyType ?? '',
      title: property.title ?? '',
      translatedTitle: property.translatedTitle ?? '',
      translatedDescription: property.translatedDescription ?? '',
      titleImage: property.titleImage ?? '',
      isPremium: property.allPropData['is_premium']?.toString() ?? '',
      address: property.address ?? '',
      addedBy: property.addedBy ?? '',
      promoted: property.promoted ?? false,
      isFavourite: property.isFavourite ?? '',
      category: property.category == null
          ? Category()
          : Category(
              id: property.category!.id,
              category: property.category!.category,
              image: property.category!.image,
              translatedName: property.category!.translatedName,
            ),
      rentduration: property.rentduration ?? '',
    );
  }

  static CustomerData createAgentCustomerData(PropertyModel property) {
    return CustomerData(
      id: int.tryParse(property.addedBy ?? '0') ?? 0,
      slugId: '',
      name: property.customerName ?? '',
      profile: property.customerProfile ?? '',
      mobile: property.customerNumber ?? '',
      email: property.customerEmail ?? '',
      address: '',
      city: '',
      country: '',
      state: '',
      agentProfile: AgentProfileModel(),
      projectCount: property.projectsCount ?? '',
      propertyCount: property.propertiesCount ?? '',
      propertiesSoldCount: '',
      propertiesRentedCount: '',
      isAppointmentAvailable: property.isAppointmentAvailable ?? false,
      isAgentVerified: property.isVerified ?? false,
      isAdmin: property.isAdmin ?? false,
    );
  }

  static List<dynamic> mapCategoryParameters({
    required Category category,
    required PropertyModel property,
  }) {
    return category.parameterTypes!.map((id) {
      final index =
          property.parameters?.indexWhere(
            (element) => element.id == id['id'],
          ) ??
          -1;

      return index != -1 ? property.parameters![index] : id;
    }).toList();
  }

  static Map<String, dynamic> buildEditablePropertyDetails(
    PropertyModel property,
  ) {
    return {
      'id': property.id,
      'catId': property.category?.id,
      'propType': property.propertyType,
      'name': property.title,
      'desc': property.description,
      'city': property.city,
      'state': property.state,
      'country': property.country,
      'latitude': property.latitude,
      'longitude': property.longitude,
      'address': property.address,
      'client_address': property.clientAddress,
      'video_link': property.video,
      'price': property.price,
      'parms': property.parameters,
      'allPropData': property.allPropData,
      'images': property.gallery?.map((e) => e.imageUrl).toList(),
      'gallary_with_id': property.gallery,
      'rentduration': property.rentduration,
      'assign_facilities': property.assignedOutdoorFacility,
      'titleImage': property.titleImage,
      'slug_id': property.slugId,
      'three_d_image': property.threeDImage,
      'documents': property.documents,
      'translations': property.translations,
      'request_status': property.requestStatus,
    };
  }
}
