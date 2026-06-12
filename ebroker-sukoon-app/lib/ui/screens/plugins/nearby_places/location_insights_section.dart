import 'package:ebroker/data/model/area_listing_models.dart';
import 'package:ebroker/data/model/nearby_places_model.dart';
import 'package:ebroker/data/model/property_model.dart';
import 'package:ebroker/data/repositories/nearby_places_repository.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/proprties/widgets/outdoor_facilities.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class LocationInsightsSection extends StatefulWidget {
  const LocationInsightsSection({
    required this.propertyId,
    this.city,
    this.state,
    this.areaListing,
    this.outdoorFacilities,
    super.key,
  });

  final int propertyId;
  final String? city;
  final String? state;
  final Map<String, dynamic>? areaListing;
  final List<AssignedOutdoorFacility>? outdoorFacilities;

  @override
  State<LocationInsightsSection> createState() =>
      _LocationInsightsSectionState();
}

class _LocationInsightsSectionState extends State<LocationInsightsSection> {
  final NearbyPlacesRepository _repository = NearbyPlacesRepository();
  NearbyPlacesPayload? _payload;
  bool _loading = true;
  String? _error;
  String _selectedCategorySlug = '';

  @override
  void initState() {
    super.initState();
    unawaited(_load());
  }

  Future<void> _load() async {
    try {
      final payload = await _repository.fetchForProperty(widget.propertyId);
      if (!mounted) return;
      setState(() {
        _payload = payload;
        _loading = false;
        if (payload != null && payload.categories.isNotEmpty) {
          _selectedCategorySlug = payload.categories.first.slug;
        }
      });
    } on Exception catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  bool get _hasOutdoorFacilities {
    final facilities = widget.outdoorFacilities ?? [];
    return facilities.any(
      (item) =>
          item.distance != null &&
          item.distance!.isNotEmpty &&
          item.distance != '0',
    );
  }

  bool get _hasNearbyPlaces =>
      _payload?.enabled == true && (_payload?.hasPlaces ?? false);

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return _sectionShell(
        context,
        child: const Padding(
          padding: EdgeInsets.all(16),
          child: Center(child: CircularProgressIndicator()),
        ),
      );
    }

    if (!_hasOutdoorFacilities && !_hasNearbyPlaces) {
      return const SizedBox.shrink();
    }

    final areaLabel = _payload?.areaLabel?.isNotEmpty == true
        ? _payload!.areaLabel
        : buildAreaListingLabel(widget.areaListing, widget.city, widget.state);

    return _sectionShell(
      context,
      areaLabel: areaLabel,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_hasOutdoorFacilities) ...[
            OutdoorFacilityListWidget(
              outdoorFacilityList: widget.outdoorFacilities ?? [],
            ),
            if (_hasNearbyPlaces) const SizedBox(height: 12),
          ],
          if (_hasNearbyPlaces) _buildNearbyPlaces(context),
          if (_error != null && !_hasNearbyPlaces)
            Padding(
              padding: const EdgeInsets.all(12),
              child: CustomText(
                _error!,
                color: context.color.textLightColor,
                fontSize: context.font.sm,
              ),
            ),
        ],
      ),
    );
  }

  Widget _sectionShell(
    BuildContext context, {
    required Widget child,
    String? areaLabel,
  }) {
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
            'Location Insights',
            fontWeight: FontWeight.w600,
            fontSize: context.font.md,
            color: context.color.textColorDark,
          ),
          if (areaLabel != null && areaLabel.isNotEmpty) ...[
            const SizedBox(height: 4),
            CustomText(
              areaLabel,
              fontSize: context.font.sm,
              color: context.color.textLightColor,
              maxLines: 2,
            ),
          ],
          const SizedBox(height: 8),
          UiUtils.getDivider(context),
          const SizedBox(height: 8),
          child,
        ],
      ),
    );
  }

  Widget _buildNearbyPlaces(BuildContext context) {
    final payload = _payload!;
    final categories = payload.categories
        .where((cat) => (payload.placesByCategory[cat.slug] ?? []).isNotEmpty)
        .toList();

    if (categories.isEmpty) {
      return const SizedBox.shrink();
    }

    final selectedSlug = categories.any((c) => c.slug == _selectedCategorySlug)
        ? _selectedCategorySlug
        : categories.first.slug;
    final places = payload.placesByCategory[selectedSlug] ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CustomText(
          'Nearby Places',
          fontWeight: FontWeight.w600,
          fontSize: context.font.sm,
          color: context.color.textColorDark,
        ),
        const SizedBox(height: 8),
        SizedBox(
          height: 36,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: categories.length,
            separatorBuilder: (_, __) => const SizedBox(width: 8),
            itemBuilder: (context, index) {
              final category = categories[index];
              final isSelected = category.slug == selectedSlug;
              return GestureDetector(
                onTap: () {
                  setState(() {
                    _selectedCategorySlug = category.slug;
                  });
                },
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: isSelected
                        ? context.color.tertiaryColor.withValues(alpha: 0.12)
                        : context.color.primaryColor,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: isSelected
                          ? context.color.tertiaryColor
                          : context.color.borderColor,
                    ),
                  ),
                  child: CustomText(
                    category.name,
                    fontSize: context.font.xs,
                    color: isSelected
                        ? context.color.tertiaryColor
                        : context.color.textColorDark,
                  ),
                ),
              );
            },
          ),
        ),
        const SizedBox(height: 8),
        ...places.take(6).map((place) => _placeTile(context, place)),
      ],
    );
  }

  Widget _placeTile(BuildContext context, NearbyPlaceItem place) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: context.color.primaryColor,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: context.color.borderColor),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CustomText(
                  place.name,
                  fontWeight: FontWeight.w600,
                  fontSize: context.font.sm,
                  maxLines: 2,
                ),
                if (place.distanceText != null) ...[
                  const SizedBox(height: 4),
                  CustomText(
                    place.distanceText!,
                    fontSize: context.font.xs,
                    color: context.color.textLightColor,
                  ),
                ],
                if (place.rating != null) ...[
                  const SizedBox(height: 4),
                  CustomText(
                    '${place.rating!.toStringAsFixed(1)} (${place.reviewCount ?? 0} reviews)',
                    fontSize: context.font.xs,
                    color: context.color.textLightColor,
                  ),
                ],
                if (place.vicinity != null && place.vicinity!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  CustomText(
                    place.vicinity!,
                    fontSize: context.font.xs,
                    color: context.color.textLightColor,
                    maxLines: 2,
                  ),
                ],
              ],
            ),
          ),
          if (place.showDirections &&
              place.directionsUrl != null &&
              place.directionsUrl!.isNotEmpty)
            IconButton(
              onPressed: () async {
                final uri = Uri.parse(place.directionsUrl!);
                if (await canLaunchUrl(uri)) {
                  await launchUrl(uri, mode: LaunchMode.externalApplication);
                }
              },
              icon: Icon(
                Icons.directions,
                color: context.color.tertiaryColor,
              ),
            ),
        ],
      ),
    );
  }
}
