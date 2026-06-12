import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/proprties/widgets/google_map_screen.dart';
import 'package:ebroker/ui/screens/widgets/interactive_property_map.dart';
import 'package:ebroker/utils/open_google_maps_directions.dart'
    show NormalizedLatLng, listingMapCoordinates, openDirectionsForListing;
import 'package:flutter/material.dart';

class PropertyLocationSection extends StatelessWidget {
  const PropertyLocationSection({
    required this.property,
    super.key,
  });
  final PropertyModel property;

  Map<String, dynamic>? get _areaListing {
    final raw = property.allPropData?['area_listing'];
    if (raw is Map<String, dynamic>) {
      return raw;
    }
    if (raw is Map) {
      return Map<String, dynamic>.from(raw);
    }
    return null;
  }

  Map<String, dynamic> get _listingDetails {
    if (property.allPropData is Map<String, dynamic>) {
      return Map<String, dynamic>.from(
        property.allPropData as Map<String, dynamic>,
      );
    }
    return <String, dynamic>{
      'latitude': property.latitude,
      'longitude': property.longitude,
      'address': property.address,
      'client_address': property.clientAddress,
      if (_areaListing != null) 'area_listing': _areaListing,
    };
  }

  NormalizedLatLng? get _mapCoords => listingMapCoordinates(
        details: _listingDetails,
      );

  @override
  Widget build(BuildContext context) {
    final coords = _mapCoords;
    final lat = coords?.lat ?? 0.0;
    final lng = coords?.lng ?? 0.0;
    final hasMap = coords != null;

    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: context.color.secondaryColor,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(
          color: context.color.borderColor,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CustomText(
            'locationLbl'.translate(context),
            fontWeight: FontWeight.w600,
            fontSize: context.font.md,
            color: context.color.textColorDark,
          ),
          const SizedBox(height: 8),
          UiUtils.getDivider(context),
          const SizedBox(height: 8),
          _buildAddressSection(context),
          if (hasMap) ...[
            const SizedBox(height: 8),
            _buildDirectionsButton(context),
            const SizedBox(height: 8),
            _buildMapContainer(context, lat, lng),
          ],
        ],
      ),
    );
  }

  Widget _buildAddressSection(BuildContext context) {
    if (AppSettings.showExactLocation && _areaListing != null) {
      final rows = <({String label, String value})>[
        (
          label: 'googleAddressLbl'.translate(context),
          value: (_areaListing!['full_address']?.toString() ?? property.address ?? '')
              .trim(),
        ),
        (
          label: 'clientaddressLbl'.translate(context),
          value: (_areaListing!['manual_address']?.toString() ??
                  property.clientAddress ??
                  '')
              .trim(),
        ),
        if ((_areaListing!['area_name']?.toString() ?? '').isNotEmpty)
          (
            label: 'Area',
            value: _areaListing!['area_name'].toString(),
          ),
        if ((_areaListing!['sub_area_name']?.toString() ?? '').isNotEmpty)
          (
            label: 'Sub Area',
            value: _areaListing!['sub_area_name'].toString(),
          ),
        if ((property.country ?? '').isNotEmpty)
          (label: 'country'.translate(context), value: property.country!),
        if ((property.city ?? '').isNotEmpty)
          (label: 'city'.translate(context), value: property.city!),
        if ((property.state ?? '').isNotEmpty)
          (label: 'state'.translate(context), value: property.state!),
      ];

      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: rows
            .where((row) => row.value.isNotEmpty)
            .map(
              (row) => Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: _addressRow(context, row.label, row.value),
              ),
            )
            .toList(),
      );
    }

    return _addressRow(
      context,
      'addressLbl'.translate(context),
      property.address ?? '',
    );
  }

  Widget _addressRow(BuildContext context, String label, String value) {
    return CustomText(
      '',
      isRichText: true,
      maxLines: 6,
      textSpan: TextSpan(
        children: [
          TextSpan(
            text: '$label: ',
            style: TextStyle(
              fontSize: context.font.sm,
              color: context.color.inverseSurface,
              fontWeight: FontWeight.w500,
            ),
          ),
          TextSpan(
            text: value,
            style: TextStyle(
              fontSize: context.font.sm,
              color: context.color.textColorDark,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDirectionsButton(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: TextButton.icon(
        onPressed: () async {
          final opened = await openDirectionsForListing(_listingDetails);
          if (!opened && context.mounted) {
            HelperUtils.showSnackBarMessage(
              context,
              'somethingWentWrng',
            );
          }
        },
        icon: Icon(
          Icons.directions,
          color: context.color.tertiaryColor,
          size: 20,
        ),
        label: CustomText(
          'viewMap'.translate(context),
          color: context.color.tertiaryColor,
          fontWeight: FontWeight.w600,
          fontSize: context.font.sm,
        ),
      ),
    );
  }

  Widget _buildMapContainer(BuildContext context, double lat, double lng) {
    return SizedBox(
      height: 168.rh(context),
      child: InteractivePropertyMap(
        latitude: lat,
        longitude: lng,
        propertyType: property.propertyType ?? '',
        onFullScreenTap: () async {
          await Navigator.push(
            context,
            CupertinoPageRoute<dynamic>(
              builder: (context) {
                return Scaffold(
                  extendBodyBehindAppBar: true,
                  backgroundColor: context.color.primaryColor,
                  appBar: AppBar(
                    elevation: 0,
                    iconTheme: IconThemeData(
                      color: context.color.tertiaryColor,
                    ),
                    backgroundColor: Colors.transparent,
                  ),
                  body: GoogleMapScreen(
                    latitude: lat,
                    longitude: lng,
                    propertyType: property.propertyType ?? '',
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
