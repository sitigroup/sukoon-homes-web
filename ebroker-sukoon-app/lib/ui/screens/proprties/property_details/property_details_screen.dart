import 'dart:developer';

import 'package:collection/collection.dart';
import 'package:ebroker/data/cubits/advertisement/fetch_ad_banners_cubit.dart';
import 'package:ebroker/data/cubits/agents/fetch_property_cubit.dart';
import 'package:ebroker/data/cubits/appointment/post/create_appointment_request_cubit.dart';
import 'package:ebroker/data/cubits/interested/get_interested_user_cubit.dart';
import 'package:ebroker/data/cubits/property/change_property_status_cubit.dart';
import 'package:ebroker/data/cubits/property/delete_property_cubit.dart';
import 'package:ebroker/data/cubits/property/fetch_similar_properties_cubit.dart';
import 'package:ebroker/data/cubits/property/interest/change_interest_in_property_cubit.dart';
import 'package:ebroker/data/cubits/property/renew_listing_cubit.dart';
import 'package:ebroker/data/cubits/property/report/property_report_cubit.dart';
import 'package:ebroker/data/cubits/property/update_property_status.dart';
import 'package:ebroker/data/cubits/utility/mortgage_calculator_cubit.dart';
import 'package:ebroker/data/model/ad_banner_model.dart';
import 'package:ebroker/data/model/category.dart';
import 'package:ebroker/data/model/project_model.dart';
import 'package:ebroker/data/model/subscription_pacakage_model.dart';
import 'package:ebroker/data/repositories/check_package.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/proprties/property_details/utils/property_details_helpers.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_agent_gallery_section.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_description_section.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_documents_section.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_enable_disable_section.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_header.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_image_carousel.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_interest_button.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_mortgage_banner.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_owner_bottom_bar.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_similar_properties_section.dart';
import 'package:ebroker/ui/screens/proprties/property_details/widgets/property_top_actions.dart';
import 'package:ebroker/ui/screens/proprties/widgets/interested_users.dart';
import 'package:ebroker/ui/screens/proprties/widgets/mortgage_calculator.dart';
import 'package:ebroker/ui/screens/plugins/nearby_places/location_insights_section.dart';
import 'package:ebroker/ui/screens/plugins/trust_verification/trust_public_trust_section.dart';
import 'package:ebroker/ui/screens/proprties/widgets/property_contact_buttons.dart';
import 'package:ebroker/ui/screens/proprties/widgets/property_location_section.dart';
import 'package:ebroker/ui/screens/proprties/widgets/property_parameters_grid.dart';
import 'package:ebroker/ui/screens/proprties/widgets/report_property_widget.dart';
import 'package:ebroker/ui/screens/widgets/panaroma_image_view.dart';
import 'package:ebroker/utils/admob/banner_ad_load_widget.dart';
import 'package:ebroker/utils/admob/interstitial_ad_manager.dart';
import 'package:flutter/material.dart';

class PropertyDetails extends StatefulWidget {
  const PropertyDetails({
    required this.property,
    super.key,
    this.fromPropertyAddSuccess,
    this.fromMyProperty,
    this.fromCompleteEnquiry,
  });

  final PropertyModel? property;
  final bool? fromMyProperty;
  final bool? fromCompleteEnquiry;
  final bool? fromPropertyAddSuccess;

  @override
  PropertyDetailsState createState() => PropertyDetailsState();

  static Route<dynamic> route(RouteSettings routeSettings) {
    try {
      final arguments = routeSettings.arguments as Map?;
      return CupertinoPageRoute(
        builder: (_) => MultiBlocProvider(
          providers: [
            BlocProvider(
              create: (context) => ChangeInterestInPropertyCubit(),
            ),
            BlocProvider(
              create: (context) => UpdatePropertyStatusCubit(),
            ),
            BlocProvider(
              create: (context) => DeletePropertyCubit(),
            ),
            BlocProvider(
              create: (context) => PropertyReportCubit(),
            ),
            BlocProvider(
              create: (context) => GetInterestedUserCubit(),
            ),
            BlocProvider(
              create: (context) => FetchAgentsPropertyCubit(),
            ),
            BlocProvider(
              create: (context) => FetchSimilarPropertiesCubit(),
            ),
            BlocProvider(
              create: (context) => RenewListingCubit(),
            ),
          ],
          child: PropertyDetails(
            property:
                arguments?['propertyData'] as PropertyModel? ?? PropertyModel(),
            fromMyProperty: arguments?['fromMyProperty'] as bool? ?? false,
            fromCompleteEnquiry:
                arguments?['fromCompleteEnquiry'] as bool? ?? false,
            fromPropertyAddSuccess: arguments?['fromSuccess'] as bool? ?? false,
          ),
        ),
      );
    } on Exception catch (_) {
      rethrow;
    }
  }
}

class PropertyDetailsState extends State<PropertyDetails>
    with TickerProviderStateMixin, AutomaticKeepAliveClientMixin {
  static const detailsPageSizedBoxHeight = 8.0;

  final ValueNotifier<bool> isEnabled = ValueNotifier(false);
  final InterstitialAdManager interstitialAdManager = InterstitialAdManager();

  PropertyModel? _property;
  List<Gallery> _galleryItems = [];
  bool _adLoaded = false;
  bool _isReported = false;
  int _currentImageIndex = 0;
  bool _isMortgageCalculatorOpening = false;

  bool get _isAddedByCurrentUser =>
      widget.property?.addedBy.toString() == HiveUtils.getUserId();

  bool get _isProfileCompleted {
    final isAgent = ActiveRoleManager.isAgent;
    if (isAgent) {
      final user = HiveUtils.getAgentProfileData();
      return user?.agentEmail != '' &&
          user?.agentMobile != '' &&
          user?.agentName != '' &&
          user?.agentAddress != '' &&
          user?.agentProfilePhoto != '';
    }
    final user = HiveUtils.getUserDetails();
    return user.email != '' &&
        user.mobile != '' &&
        user.name != '' &&
        user.address != '' &&
        user.profile != '';
  }

  @override
  bool get wantKeepAlive => true;

  @override
  void dispose() {
    isEnabled.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    _property = widget.property;
    _galleryItems = List.from(widget.property?.gallery ?? const <Gallery>[]);
    isEnabled.value = widget.property?.status.toString() == '1';
    _isReported =
        widget.property?.allPropData?['is_reported'] as bool? ?? false;

    if (!_isAddedByCurrentUser) {
      unawaited(_loadAd());
      unawaited(interstitialAdManager.load());
    }

    unawaited(
      context.read<FetchAdBannersCubit>().fetch(page: 'property_detail'),
    );
    unawaited(context.read<FetchOutdoorFacilityListCubit>().fetch());

    if (_isAddedByCurrentUser) {
      try {
        unawaited(
          context.read<GetInterestedUserCubit>().fetch(
            '${widget.property?.id}',
          ),
        );
      } on Exception catch (_) {
        Widgets.hideLoder(context);
      }
    }

    if (!HiveUtils.isGuest()) {
      unawaited(context.read<GetChatListCubit>().fetch(forceRefresh: false));
    }

    if (HiveUtils.getActiveRole() != ActiveRole.agent &&
        widget.property?.id != null) {
      unawaited(
        context.read<FetchSimilarPropertiesCubit>().fetchSimilarProperty(
          propertyId: widget.property!.id!,
        ),
      );
      unawaited(context.read<FetchPropertyReportReasonsListCubit>().fetch());
    }

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _injectVideoInGallery();
    });
  }

  Future<void> _onBackPress({required bool isFromAppBar}) async {
    if (!_isAddedByCurrentUser) {
      await interstitialAdManager.show();
    }

    context.read<MortgageCalculatorCubit>().emptyMortgageCalculatorData();
    unawaited(context.read<FetchAdBannersCubit>().fetch(page: 'homepage'));

    if (widget.fromPropertyAddSuccess ?? false) {
      if (!mounted) return;

      await context.read<FetchMyPropertiesCubit>().fetchMyProperties(
        status: '',
        type: '',
      );

      if (!mounted) return;

      await Navigator.of(context).pushNamedAndRemoveUntil(
        Routes.main,
        (route) => false,
        arguments: {'from': 'propertySuccess'},
      );
      return;
    }

    if (!isFromAppBar) {
      Future.delayed(Duration.zero, () async {
        Navigator.pop(context);
      });
    }
  }

  Future<void> _loadAd() async {
    if (_isAddedByCurrentUser || !Constant.isAdmobAdsEnabled) {
      return;
    }

    setState(() {
      _adLoaded = true;
    });
  }

  void _injectVideoInGallery() {
    final videoUrl = widget.property?.video?.trim();
    if (videoUrl == null || videoUrl.isEmpty) {
      return;
    }

    final hasVideo = _galleryItems.any((item) => item.isVideo ?? false);
    if (hasVideo) {
      return;
    }

    final videoGallery = Gallery(
      id: 99999999999,
      image: videoUrl,
      imageUrl: '',
      isVideo: true,
    );

    if (_galleryItems.length < 2) {
      _galleryItems.add(videoGallery);
    } else {
      _galleryItems.insert(0, videoGallery);
    }

    if (mounted) {
      setState(() {});
    }
  }

  String? _statusFilter(String value) {
    if (value == 'Sell' || value == 'sell') {
      return 'sold'.translate(context);
    }
    if (value == 'Rent' || value == 'rent') {
      return 'rented'.translate(context);
    }
    if (value == 'Rented' || value == 'rented') {
      return 'rent'.translate(context);
    }

    return null;
  }

  int? _getStatus(String type) {
    int? value;
    if (type == 'Sell' || type == 'sell') {
      value = 2;
    } else if (type == 'Rent' || type == 'rent') {
      value = 3;
    } else if (type == 'Rented' || type == 'rented') {
      value = 1;
    }
    return value;
  }

  bool _hasDocuments() {
    return _property?.documents?.isNotEmpty ?? false;
  }

  bool _isThreeDImageAvailable() {
    final threeDImage = _property?.threeDImage;
    return !(threeDImage == '' || threeDImage == null);
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    final property = _property;
    if (property == null) {
      return const SizedBox.shrink();
    }

    return MultiBlocListener(
      listeners: [
        BlocListener<
          CreateAppointmentRequestCubit,
          CreateAppointmentRequestState
        >(
          listener: (context, state) {
            if (state is CreateAppointmentRequestSuccess) {
              HelperUtils.showSnackBarMessage(
                context,
                'appointmentScheduledSuccessfully',
                type: .success,
              );
              Navigator.pop(context);
            }
            if (state is CreateAppointmentRequestFailure) {
              HelperUtils.showSnackBarMessage(
                context,
                state.errorMessage,
                type: .error,
              );
            }
          },
        ),
        BlocListener<RenewListingCubit, RenewListingState>(
          listener: (context, state) {
            if (state is RenewListingInProgress) {
              unawaited(Widgets.showLoader(context));
            }
            if (state is RenewListingSuccess) {
              Widgets.hideLoder(context);
              HelperUtils.showSnackBarMessage(
                context,
                state.message,
                type: .success,
              );
            }
            if (state is RenewListingFailure) {
              Widgets.hideLoder(context);
              HelperUtils.showSnackBarMessage(
                context,
                state.errorMessage,
                type: .error,
              );
            }
          },
        ),
        BlocListener<DeletePropertyCubit, DeletePropertyState>(
          listener: (context, state) async {
            if (state is DeletePropertySuccess) {
              if (!mounted) return;
              Navigator.pop(context);
            }
            if (state is DeletePropertyFailure) {
              HelperUtils.showSnackBarMessage(
                context,
                state.errorMessage,
                type: .error,
              );
            }
          },
        ),
        BlocListener<UpdatePropertyStatusCubit, UpdatePropertyStatusState>(
          listener: (context, state) async {
            if (state is UpdatePropertyStatusSuccess) {
              Widgets.hideLoder(context);
              HelperUtils.showSnackBarMessage(
                context,
                'statusUpdated'.translate(context),
                type: .success,
              );

              _refreshMyPropertiesStatus();
              setState(() {});
            }
            if (state is UpdatePropertyStatusFail) {
              Widgets.hideLoder(context);
            }
          },
        ),
      ],
      child: AnnotatedRegion(
        value: UiUtils.getSystemUiOverlayStyle(context: context),
        child: PopScope(
          canPop: false,
          onPopInvokedWithResult: (didPop, _) async {
            if (didPop) return;
            await _onBackPress(isFromAppBar: false);
          },
          child: Scaffold(
            appBar: CustomAppBar(
              onTapBackButton: () async {
                await _onBackPress(isFromAppBar: true);
              },
              actions: [
                PropertyTopActions(
                  property: property,
                  isThreeDImageAvailable: _isThreeDImageAvailable(),
                  onActionSelected: _handleTopActionSelection,
                  onTapThreeDView: _openThreeDView,
                ),
              ],
            ),
            backgroundColor: context.color.primaryColor,
            floatingActionButton: _isAddedByCurrentUser
                ? const SizedBox.shrink()
                : Container(),
            bottomNavigationBar: _buildBottomNavigationBar(property),
            floatingActionButtonLocation:
                FloatingActionButtonLocation.centerFloat,
            body: SingleChildScrollView(
              physics: Constant.scrollPhysics,
              child: Column(
                crossAxisAlignment: .start,
                children: [
                  PropertyImageCarousel(
                    property: property,
                    currentIndex: _currentImageIndex,
                    onPageChanged: (index) {
                      setState(() {
                        _currentImageIndex = index;
                      });
                    },
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Column(
                      crossAxisAlignment: .start,
                      children: <Widget>[
                        PropertyHeader(
                          property: property,
                        ),
                        const SizedBox(
                          height: detailsPageSizedBoxHeight,
                        ),
                        if (_isAddedByCurrentUser) ...[
                          PropertyEnableDisableSection(
                            isEnabled: isEnabled,
                            isDisabled:
                                property.requestStatus.toString() ==
                                    'pending' ||
                                (property.isExpired ?? false),
                            onChanged: _handleEnableDisableChanged,
                          ),
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                        ],
                        if (property.description != null) ...[
                          PropertyDescriptionSection(
                            property: property,
                          ),
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                        ],
                        if (property.propertyType.toString().toLowerCase() ==
                            'sell') ...[
                          PropertyMortgageBanner(
                            property: property,
                            onPressed: _openMortgageCalculator,
                          ),
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                        ],
                        _buildAboveFacilitiesBanner(),
                        if (property.parameters?.isNotEmpty ?? false) ...[
                          PropertyParametersGrid(
                            property: property,
                          ),
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                        ],
                        _buildAdWidget(),
                        if (property.id != null) ...[
                          LocationInsightsSection(
                            propertyId: property.id!,
                            city: property.city,
                            state: property.state,
                            areaListing: property.allPropData?['area_listing']
                                as Map<String, dynamic>?,
                            outdoorFacilities:
                                property.assignedOutdoorFacility,
                          ),
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                        ],
                        if (int.tryParse(property.addedBy ?? '') != null &&
                            int.tryParse(property.addedBy ?? '')! > 0) ...[
                          TrustPublicTrustSection(
                            customerId: int.parse(property.addedBy!),
                          ),
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                        ],
                        PropertyLocationSection(
                          property: property,
                        ),
                        if (!HiveUtils.isGuest() &&
                            HiveUtils.getUserId() != property.addedBy) ...[
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                          PropertyInterestButton(
                            property: property,
                          ),
                        ],
                        const SizedBox(
                          height: detailsPageSizedBoxHeight,
                        ),
                        PropertyAgentGallerySection(
                          property: property,
                          gallery: _galleryItems,
                          onScheduleAppointment: _scheduleAppointment,
                        ),
                        if (!reportedProperties.contains(property.id) &&
                            property.addedBy.toString() !=
                                HiveUtils.getUserId() &&
                            !_isReported &&
                            property.id != null) ...[
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                          ReportPropertyButton(
                            propertyId: property.id!,
                            onSuccess: () {
                              setState(() {});
                            },
                          ),
                        ],
                        if (_hasDocuments()) ...[
                          const SizedBox(
                            height: detailsPageSizedBoxHeight,
                          ),
                          PropertyDocumentsSection(
                            property: property,
                          ),
                        ],
                        const SizedBox(
                          height: detailsPageSizedBoxHeight,
                        ),
                        PropertySimilarPropertiesSection(
                          property: property,
                        ),
                        SizedBox(height: 24.rh(context)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _handleTopActionSelection(String value) async {
    if (value == 'share') {
      await HelperUtils.share(
        context,
        _property?.slugId ?? '',
      );
      return;
    }

    if (value == 'renewListing') {
      await _handleRenewProperty();
      return;
    }

    if (value == 'interestedUsers') {
      await _showInterestedUsersBottomSheet();
      return;
    }

    if (value == 'markAsSold' ||
        value == 'markAsRented' ||
        value == 'markAsRent') {
      await _showStatusChangeDialog(value);
    }
  }

  Future<void> _openThreeDView() async {
    await Navigator.push(
      context,
      CupertinoPageRoute<dynamic>(
        builder: (context) => PanaromaImageScreen(
          imageUrl: _property?.threeDImage ?? '',
        ),
      ),
    );
  }

  Future<void> _showInterestedUsersBottomSheet() async {
    final interestedUserCubitReference = context.read<GetInterestedUserCubit>();

    await showModalBottomSheet<dynamic>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(8),
          topRight: Radius.circular(8),
        ),
      ),
      backgroundColor: context.color.secondaryColor,
      constraints: BoxConstraints(
        minWidth: double.infinity,
        maxHeight: context.screenHeight * 0.7,
        minHeight: context.screenHeight * 0.3,
      ),
      builder: (context) {
        return InterestedUserListWidget(
          totalCount: '${widget.property?.totalInterestedUsers}',
          interestedUserCubitReference: interestedUserCubitReference,
        );
      },
    );
  }

  Future<void> _showStatusChangeDialog(String value) async {
    final property = _property;
    if (property == null) {
      return;
    }

    final statusValue = value == 'markAsRented'
        ? 'rent'
        : value == 'markAsRent'
        ? 'rented'
        : 'sold';

    final action = await UiUtils.showBlurredDialoge(
      context,
      dialog: BlurredDialogBuilderBox(
        title: 'changePropertyStatus'.translate(context),
        acceptButtonName: 'change'.translate(context),
        titleSize: context.font.md,
        titleWeight: .w500,
        cancelButtonBorderColor: context.color.tertiaryColor,
        acceptTextColor: context.color.buttonColor,
        cancelTextColor: context.color.tertiaryColor,
        cancelButtonColor: context.color.secondaryColor,
        contentBuilder: (context, s) {
          return Column(
            mainAxisSize: .min,
            children: [
              Container(
                decoration: BoxDecoration(
                  color: context.color.secondaryColor,
                  borderRadius: BorderRadius.circular(4),
                  border: Border.all(
                    color: context.color.borderColor,
                  ),
                ),
                width: s.maxWidth,
                height: 48.rh(context),
                child: Center(
                  child: CustomText(
                    '${(property.propertyType ?? '').translate(context)} ${'property'.translate(context)}',
                    color: context.color.inverseSurface,
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(8),
                child: CustomText(
                  'to'.translate(context),
                  fontSize: 15,
                  fontWeight: .w600,
                ),
              ),
              Container(
                width: s.maxWidth,
                decoration: BoxDecoration(
                  color: context.color.secondaryColor,
                  borderRadius: BorderRadius.circular(4),
                  border: Border.all(
                    color: context.color.borderColor,
                  ),
                ),
                height: 48.rh(context),
                child: Center(
                  child: CustomText(
                    '${_statusFilter(statusValue) ?? statusValue.translate(context)} ${'property'.translate(context)}',
                    color: context.color.inverseSurface,
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );

    if (action == true) {
      if (Constant.isDemoModeOn) {
        return HelperUtils.showSnackBarMessage(
          context,
          'thisActionNotValidDemo'.translate(context),
          type: .warning,
        );
      }
      Future.delayed(Duration.zero, () async {
        await context.read<UpdatePropertyStatusCubit>().update(
          propertyId: property.id,
          status: _getStatus(property.propertyType ?? ''),
        );
        await _onBackPress(isFromAppBar: false);
      });
    }
  }

  void _refreshMyPropertiesStatus() {
    final property = _property;
    if (property?.id == null || property?.propertyType == null) {
      return;
    }

    try {
      context.read<FetchMyPropertiesCubit>().updateStatus(
        property!.id!,
        property.propertyType!,
      );
    } on Exception catch (error, stackTrace) {
      log(
        'Unable to refresh my properties state after status update.',
        error: error,
        stackTrace: stackTrace,
      );
    }
  }

  Widget _buildAboveFacilitiesBanner() {
    return BlocBuilder<FetchAdBannersCubit, FetchAdBannersState>(
      builder: (context, adBannerState) {
        AdBanner? banner;
        if (adBannerState is FetchAdBannersSuccess) {
          banner = adBannerState.banners.firstWhereOrNull(
            (item) =>
                item.placement == AdBannerPlacementType.aboveFacilities.value,
          );
        }

        if (adBannerState is! FetchAdBannersSuccess ||
            adBannerState.banners.isEmpty ||
            banner == null) {
          return const SizedBox.shrink();
        }

        return Padding(
          padding: const EdgeInsets.symmetric(
            vertical: 16,
            horizontal: 18,
          ),
          child: GestureDetector(
            onTap: () => HelperUtils.onTapBanner(
              context,
              banner,
            ),
            child: CustomImage(
              imageUrl: banner.image ?? '',
              width: context.screenWidth,
              height: 80.rs(context),
              fit: .fill,
            ),
          ),
        );
      },
    );
  }

  Future<void> _handleRenewProperty() async {
    final property = _property;
    if (property?.id == null) {
      return;
    }

    final checkPackage = CheckPackage();
    final packageAvailable = await checkPackage.checkPackageAvailable(
      packageType: PackageType.propertyList,
    );

    if (!packageAvailable) {
      PayAsYouGoModel? payAsYouGoPackage;
      bool? isBankTransferActive;
      var availableOnlineGateways = <String>[];

      try {
        await context.read<GetApiKeysCubit>().fetch();
        final apiKeyState = context.read<GetApiKeysCubit>().state;
        if (apiKeyState is GetApiKeysSuccess) {
          isBankTransferActive = apiKeyState.bankTransferStatus == '1';
          availableOnlineGateways = apiKeyState.enabledPaymentGateways;

          await context.read<FetchSubscriptionPackagesCubit>().fetchPackages();
          final packageState = context
              .read<FetchSubscriptionPackagesCubit>()
              .state;
          if (packageState is FetchSubscriptionPackagesSuccess) {
            final match = packageState.packageResponseModel.payAsYouGo
                .where((p) => p.type == 'property')
                .toList();
            if (match.isNotEmpty) {
              payAsYouGoPackage = match.first;
            }
          }
        }
      } on Exception catch (_) {}

      await UiUtils.showBlurredDialoge(
        context,
        dialog: BlurredSubscriptionDialogBox(
          packageType: SubscriptionPackageType.propertyList,
          isAcceptContainesPush: true,
          preFetchedPayAsYouGo: payAsYouGoPackage,
          preFetchedIsBankTransferActive: isBankTransferActive,
          preFetchedAvailableOnlineGateways: availableOnlineGateways,
        ),
      );
      return;
    }

    await context.read<RenewListingCubit>().renew(
      id: property!.id!,
      type: 'property',
    );
  }

  Future<void> _scheduleAppointment() async {
    final property = _property;
    if (property == null) {
      return;
    }

    final propertiesData = PropertyDetailsHelpers.toPropertiesData(property);
    final agentData = PropertyDetailsHelpers.createAgentCustomerData(property);

    await Navigator.pushNamed(
      context,
      Routes.appointmentFlow,
      arguments: {
        'isAdmin': property.isAdmin ?? false,
        'agentDetails': agentData,
        'preSelectedProperty': propertiesData,
      },
    );
  }

  Future<void> _handleEnableDisableChanged(bool newValue) async {
    final property = _property;
    if (property?.id == null) {
      return;
    }

    final cubit = context.read<ChangePropertyStatusCubit>();
    final currentState = cubit.state;

    if (currentState is ChangePropertyStatusInProgress) {
      return;
    }

    final status = !isEnabled.value ? 1 : 0;
    isEnabled.value = newValue;

    try {
      await cubit.enableProperty(
        propertyId: property!.id!,
        status: status,
      );

      final newState = cubit.state;
      if (newState is ChangePropertyStatusFailure) {
        isEnabled.value = !newValue;

        final errorMessage = newState.error.contains('429')
            ? 'tooManyRequestsPleaseWait'.translate(context)
            : newState.error;

        HelperUtils.showSnackBarMessage(
          context,
          errorMessage,
          type: .error,
        );
      }
    } on Exception catch (_) {
      isEnabled.value = !newValue;
      HelperUtils.showSnackBarMessage(
        context,
        'somethingWentWrong',
        type: .error,
      );
    }
  }

  Future<void> _openMortgageCalculator() async {
    if (_isMortgageCalculatorOpening) {
      return;
    }

    setState(() {
      _isMortgageCalculatorOpening = true;
    });

    try {
      await GuestChecker.check(
        onNotGuest: () async {
          try {
            final checkPackage = CheckPackage();
            final packageAvailable = await checkPackage.checkPackageAvailable(
              packageType: PackageType.mortgageCalculatorDetail,
            );
            if (packageAvailable) {
              await showModalBottomSheet<dynamic>(
                sheetAnimationStyle: const AnimationStyle(
                  duration: Duration(milliseconds: 500),
                  reverseDuration: Duration(milliseconds: 200),
                ),
                showDragHandle: true,
                backgroundColor: context.color.secondaryColor,
                isScrollControlled: true,
                shape: const RoundedRectangleBorder(
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(8),
                    topRight: Radius.circular(8),
                  ),
                ),
                context: context,
                builder: (context) => Padding(
                  padding: EdgeInsets.only(
                    bottom: MediaQuery.of(context).viewInsets.bottom,
                  ),
                  child: MortgageCalculator(
                    property: widget.property!,
                  ),
                ),
              );
            } else {
              await UiUtils.showBlurredDialoge(
                context,
                dialog: const BlurredSubscriptionDialogBox(
                  packageType: SubscriptionPackageType.mortgageCalculatorDetail,
                ),
              );
            }
          } on Exception catch (error) {
            log(error.toString());
          }
        },
      );
    } finally {
      if (mounted) {
        setState(() {
          _isMortgageCalculatorOpening = false;
        });
      }
    }
  }

  Widget _buildBottomNavigationBar(PropertyModel property) {
    if (HiveUtils.isGuest() || HiveUtils.getUserId() != property.addedBy) {
      return PropertyContactButtons(property: property);
    }

    return BlocBuilder<FetchMyPropertiesCubit, FetchMyPropertiesState>(
      builder: (context, state) {
        final model = _getPropertyModel(state);
        if (model == null) {
          return const SizedBox.shrink();
        }

        return PropertyOwnerBottomBar(
          showFeatureButton:
              !HiveUtils.isGuest() &&
              !Constant.isDemoModeOn &&
              (model.isFeatureAvailable ?? false) &&
              model.isExpired != true,
          showEditButton:
              model.propertyType?.toLowerCase() != 'sold' &&
              model.propertyType?.toLowerCase() != 'rented' &&
              model.requestStatus?.toLowerCase() != 'pending' &&
              model.isExpired != true,
          onFeaturePressed: _handleFeatureButtonPress,
          onEditPressed: _handleEditButtonPress,
          onDeletePressed: _handleDeleteButtonPress,
        );
      },
    );
  }

  PropertyModel? _getPropertyModel(FetchMyPropertiesState state) {
    if (state is FetchMyPropertiesSuccess) {
      if (_property?.id == null) {
        return null;
      }
      try {
        return state.myProperty.firstWhere(
          (element) => element.id == _property?.id,
          orElse: () {
            log(
              'Property with ID ${_property?.id} not found in FetchMyPropertiesSuccess state. It might have been deleted. Falling back to widget.property.',
              name: 'FetchMyPropertiesCubit',
            );
            return widget.property ?? PropertyModel();
          },
        );
      } on Exception catch (error, stackTrace) {
        log(
          error.toString(),
          name: 'FetchMyPropertiesCubit',
          stackTrace: stackTrace,
        );
      }
    }
    return widget.property;
  }

  Future<void> _handleFeatureButtonPress(
    GetSubscriptionPackageLimitsState state,
  ) async {
    await context.read<GetSubsctiptionPackageLimitsCubit>().getLimits(
      packageType: 'property_feature',
    );

    if (state is GetSubsctiptionPackageLimitsFailure) {
      await _showFeatureSubscriptionDialog();
    } else if (state is GetSubscriptionPackageLimitsSuccess) {
      if (state.error) {
        await _showPackageLimitDialog(state.message);
      } else {
        await _showCreateAdvertisementDialog();
      }
    }
  }

  Future<void> _showFeatureSubscriptionDialog() async {
    await UiUtils.showBlurredDialoge(
      context,
      dialog: const BlurredSubscriptionDialogBox(
        packageType: SubscriptionPackageType.propertyFeature,
        isAcceptContainesPush: true,
      ),
    );
  }

  Future<void> _showPackageLimitDialog(String message) async {
    await UiUtils.showBlurredDialoge(
      context,
      dialog: BlurredDialogBox(
        title: message.firstUpperCase(),
        isAcceptContainesPush: true,
        onAccept: _navigateToSubscriptionPackages,
        content: CustomText('yourPackageLimitOver'.translate(context)),
      ),
    );
  }

  Future<void> _navigateToSubscriptionPackages() async {
    await Navigator.popAndPushNamed(
      context,
      Routes.subscriptionPackageListRoute,
      arguments: {
        'from': 'propertyDetails',
        'isBankTransferEnabled': _getBankTransferStatus(),
      },
    );
  }

  bool _getBankTransferStatus() {
    final apiKeysState = context.read<GetApiKeysCubit>().state;
    return apiKeysState is GetApiKeysSuccess &&
        apiKeysState.bankTransferStatus == '1';
  }

  Future<void> _showCreateAdvertisementDialog() async {
    final property = _property;
    if (property == null) {
      return;
    }

    try {
      await showDialog<dynamic>(
        context: context,
        barrierColor: Colors.black87,
        builder: (context) => CreateAdvertisementPopup(
          property: property,
          isProject: false,
          project: ProjectModel(),
        ),
      );
    } on Exception catch (error) {
      HelperUtils.showSnackBarMessage(context, error.toString());
    }
  }

  Future<void> _handleEditButtonPress() async {
    if (Constant.isDemoModeOn &&
        (HiveUtils.getUserDetails().isDemoUser ?? false)) {
      HelperUtils.showSnackBarMessage(
        context,
        'thisActionNotValidDemo',
      );
      return;
    }

    unawaited(Widgets.showLoader(context));

    try {
      if (!await _checkProfileCompletion()) {
        return;
      }

      await _processEditProperty();
    } on Exception catch (_) {
      // Error handling is done in _processEditProperty
    } finally {
      Widgets.hideLoder(context);
    }
  }

  Future<bool> _checkProfileCompletion() async {
    final verificationRequired = ActiveRoleManager.isAgent
        ? AppSettings.isVerificationRequiredForAgent
        : AppSettings.isVerificationRequiredForUser;

    if (verificationRequired && !_isProfileCompleted) {
      await _showProfileCompletionDialog();
      Widgets.hideLoder(context);
      return false;
    }
    return true;
  }

  Future<void> _showProfileCompletionDialog() async {
    await UiUtils.showBlurredDialoge(
      context,
      dialog: BlurredDialogBox(
        title: 'completeProfile'.translate(context),
        isAcceptContainesPush: true,
        onAccept: _navigateToEditProfile,
        content: CustomText('completeProfileFirst'.translate(context)),
      ),
    );
  }

  Future<void> _navigateToEditProfile() async {
    await Navigator.popAndPushNamed(
      context,
      Routes.editProfile,
      arguments: {
        'from': 'home',
        'navigateToHome': true,
      },
    );
  }

  Future<void> _processEditProperty() async {
    final property = _property;
    if (property?.category?.id == null) {
      return;
    }

    try {
      final category = await context.read<FetchCategoryCubit>().get(
        property!.category!.id!,
      );

      final mappedParameters = PropertyDetailsHelpers.mapCategoryParameters(
        category: category,
        property: property,
      );

      _updateConstantAddProperty(category, mappedParameters);

      Widgets.hideLoder(context);

      await Navigator.pushNamed(
        context,
        Routes.addPropertyDetailsScreen,
        arguments: {
          'details': PropertyDetailsHelpers.buildEditablePropertyDetails(
            property,
          ),
        },
      );
    } on Exception catch (_) {
      Widgets.hideLoder(context);
      rethrow;
    }
  }

  void _updateConstantAddProperty(
    Category category,
    List<dynamic> mappedParameters,
  ) {
    final property = _property;
    if (property?.category == null) {
      return;
    }

    Constant.addProperty.addAll({
      'category': Category(
        category: property?.category!.category,
        id: property?.category?.id,
        image: property?.category?.image,
        parameterTypes: mappedParameters,
      ),
    });
  }

  Future<void> _handleDeleteButtonPress() async {
    if (!(await _checkDemoModeDelete())) {
      return;
    }

    await _showDeleteConfirmationDialog();
  }

  Future<bool> _checkDemoModeDelete() async {
    final isPropertyActive = _property?.status.toString() == '1';
    if (Constant.isDemoModeOn &&
        isPropertyActive &&
        (HiveUtils.getUserDetails().isDemoUser ?? false)) {
      HelperUtils.showSnackBarMessage(
        context,
        'thisActionNotValidDemo',
      );
      return false;
    }
    return true;
  }

  Future<dynamic> _showDeleteConfirmationDialog() async {
    final property = _property;
    if (property?.id == null) {
      return null;
    }

    await UiUtils.showBlurredDialoge(
      context,
      dialog: BlurredDialogBox(
        title: 'deleteBtnLbl'.translate(context),
        isAcceptContainesPush: true,
        onAccept: () async {
          await context.read<DeletePropertyCubit>().delete(property!.id!).then(
            (value) async {
              HelperUtils.showSnackBarMessage(
                context,
                'propertyDeletedSuccessfully'.translate(context),
                type: .success,
              );
              await context.read<FetchMyPropertiesCubit>().fetchMyProperties(
                type: '',
                status: '',
              );
              Navigator.pop(context);
            },
          );
        },
        content: CustomText(
          'deletepropertywarning'.translate(context),
          maxLines: 5,
          textAlign: .center,
        ),
      ),
    );
  }

  Widget _buildAdWidget() {
    if (!_adLoaded || !Constant.isAdmobAdsEnabled) {
      return const SizedBox.shrink();
    }

    return BlocBuilder<FetchAdBannersCubit, FetchAdBannersState>(
      builder: (context, adBannerState) {
        if (adBannerState is FetchAdBannersSuccess &&
            adBannerState.banners.isNotEmpty) {
          return Container(
            alignment: Alignment.center,
            margin: const EdgeInsets.only(bottom: 8),
            child: const BannerAdWidget(),
          );
        }
        return const SizedBox.shrink();
      },
    );
  }
}
