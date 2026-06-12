import 'package:ebroker/data/model/project_model.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/proprties/widgets/google_map_screen.dart';
import 'package:ebroker/ui/screens/widgets/interactive_property_map.dart';
import 'package:ebroker/utils/open_google_maps_directions.dart'
    show NormalizedLatLng, listingMapCoordinates, openDirectionsForListing;
import 'package:flutter/material.dart';

class ProjectLocationSection extends StatelessWidget {
  const ProjectLocationSection({
    required this.project,
    super.key,
  });

  final ProjectModel project;

  Map<String, dynamic>? get _areaListing {
    final raw = project.rawData?['area_listing'];
    if (raw is Map<String, dynamic>) return raw;
    if (raw is Map) return Map<String, dynamic>.from(raw);
    return null;
  }

  Map<String, dynamic> get _listingDetails => {
        'latitude': project.latitude,
        'longitude': project.longitude,
        'address': project.location,
        if (_areaListing != null) 'area_listing': _areaListing,
      };

  NormalizedLatLng? get _mapCoords =>
      listingMapCoordinates(details: _listingDetails);

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
        border: Border.all(color: context.color.borderColor),
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
          CustomText(
            project.location ?? '',
            maxLines: 6,
            color: context.color.textColorDark,
          ),
          if (hasMap) ...[
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerLeft,
              child: TextButton.icon(
                onPressed: () async {
                  final opened =
                      await openDirectionsForListing(_listingDetails);
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
            ),
            const SizedBox(height: 8),
            SizedBox(
              height: 168.rh(context),
              child: InteractivePropertyMap(
                latitude: lat,
                longitude: lng,
                propertyType: project.type ?? '',
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
                            propertyType: project.type ?? '',
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ],
      ),
    );
  }
}
