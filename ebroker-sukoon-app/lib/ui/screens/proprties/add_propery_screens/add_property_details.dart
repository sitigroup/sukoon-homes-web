import 'package:dio/dio.dart';
import 'package:ebroker/data/model/area_listing_models.dart';
import 'package:ebroker/data/model/category.dart';
import 'package:ebroker/data/model/languages_model.dart';
import 'package:ebroker/data/model/translation_model.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/plugins/area_listing/area_listing_picker.dart';
import 'package:ebroker/utils/area_listing_context.dart';
import 'package:ebroker/utils/area_listing_location_sync.dart';
import 'package:ebroker/ui/screens/widgets/generate_with_ai_button.dart';
import 'package:ebroker/ui/screens/widgets/panaroma_image_view.dart';
import 'package:ebroker/ui/screens/widgets/video_selector.dart';
import 'package:ebroker/utils/custom_tabbar.dart';
import 'package:ebroker/utils/hive_keys.dart';
import 'package:ebroker/utils/image_picker.dart';
import 'package:flutter/material.dart';
import 'package:hive/hive.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shimmer/shimmer.dart';

class AddPropertyDetails extends StatefulWidget {
  const AddPropertyDetails({super.key, this.propertyDetails});

  final Map<dynamic, dynamic>? propertyDetails;

  static Route<dynamic> route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments as Map?;
    return CupertinoPageRoute(
      builder: (context) {
        return AddPropertyDetails(
          propertyDetails: arguments?['details'] as Map<String, dynamic>?,
        );
      },
    );
  }

  @override
  State<AddPropertyDetails> createState() => _AddPropertyDetailsState();
}

class _AddPropertyDetailsState extends State<AddPropertyDetails>
    with TickerProviderStateMixin {
  final GlobalKey<FormState> _formKey = GlobalKey();
  late TabController _tabController;
  List<Translations> translatedFields = [];
  final translationMap = <String, dynamic>{};
  List<LanguagesModel> languages = AppSettings.languages;
  bool isMetaDetailsExpanded = true;
  bool _isGeneratingDescription = false;
  int? _generatingDescriptionIndex;
  bool _isGeneratingMeta = false;

  late PropertyModel? property = getEditPropertyData(
    widget.propertyDetails?['property'] as Map<String, dynamic>?,
  );

  late final List<TextEditingController> _titleControllers;
  late final List<TextEditingController> _descriptionControllers;

  late final TextEditingController _slugController = TextEditingController(
    text: widget.propertyDetails?['slug_id']?.toString() ?? '',
  );

  late final TextEditingController _cityNameController = TextEditingController(
    text: widget.propertyDetails?['city']?.toString() ?? '',
  );
  late final TextEditingController _stateNameController = TextEditingController(
    text: widget.propertyDetails?['state']?.toString() ?? '',
  );
  late final TextEditingController _countryNameController =
      TextEditingController(
        text: widget.propertyDetails?['country']?.toString() ?? '',
      );
  late final TextEditingController _latitudeController = TextEditingController(
    text: widget.propertyDetails?['latitude']?.toString() ?? '',
  );
  late final TextEditingController _longitudeController = TextEditingController(
    text: widget.propertyDetails?['longitude']?.toString() ?? '',
  );
  late final TextEditingController _addressController = TextEditingController(
    text: widget.propertyDetails?['address']?.toString() ?? '',
  );
  late final TextEditingController _priceController = TextEditingController(
    text: widget.propertyDetails?['price']?.toString() ?? '',
  );
  late final TextEditingController _clientAddressController =
      TextEditingController();

  late final TextEditingController _youtubeVideoLinkController =
      TextEditingController();
  late final TextEditingController _vimeoVideoLinkController =
      TextEditingController();

  bool isPrivateProperty = false;
  bool _removeVideo = false;

  /// VIDEO TYPE: 0=custom, 1=youtube, 2=vimeo
  int selectedVideoType = 1;
  File? _selectedVideoFile;

  /// Remote URL of an already-uploaded custom video (edit mode, video_type == 0).
  String _existingCustomVideoUrl = '';

  ///META DETAILS
  late final TextEditingController metaTitleController =
      TextEditingController();
  late final TextEditingController metaDescriptionController =
      TextEditingController();
  late final TextEditingController metaKeywordController =
      TextEditingController();

  ///
  Map<dynamic, dynamic> propertyData = {};
  AreaListingSelection _areaListingSelection = AreaListingSelection();
  int _areaMapEpoch = 0;
  final PickImage _propertiesImagePicker = PickImage();
  final PickImage _pick360deg = PickImage();

  // final PickImage _pickMetaTitle = PickImage();
  List<dynamic> editPropertyImageList = [];
  String threeDImageURL = '';
  String titleImageURL = '';

  // String metaImageUrl = '';
  String selectedRentType = 'Monthly';
  List<dynamic> removedImageId = [];
  int propertyType = 0;
  List<PropertyDocuments> documentFiles = [];
  List<int> removedDocumentId = [];
  int removeThreeDImage = 0;
  int removeMetaImage = 0;
  double localLatitude = 0;
  double localLongitude = 0;
  late final Map<String, dynamic> allPropData =
      widget.propertyDetails?['allPropData'] as Map<String, dynamic>? ?? {};

  // meta image new code
  late String metaImageUrl = allPropData['meta_image']?.toString() ?? '';
  late ImagePickerValue<dynamic>? metaImage = metaImageUrl != ''
      ? UrlValue(metaImageUrl)
      : null;

  // title image new code
  late ImagePickerValue<dynamic>? titleImage;

  List<dynamic> mixedPropertyImageList = [];
  bool isAlreadyShowingImageError = false;

  PropertyModel? getEditPropertyData(Map<String, dynamic>? data) {
    if (data == null) {
      return null;
    }
    return PropertyModel.fromMap(data);
  }

  @override
  void initState() {
    _areaListingSelection = AreaListingSelection.fromMap(
      AreaListingSelection.extractAreaListingMap(widget.propertyDetails) ??
          AreaListingSelection.extractAreaListingMap(allPropData) ??
          AreaListingSelection.extractAreaListingMap(property?.allPropData),
    ).copyWith(
      cityName: _cityNameController.text.isNotEmpty
          ? _cityNameController.text
          : null,
      stateName: _stateNameController.text.isNotEmpty
          ? _stateNameController.text
          : null,
      countryName: _countryNameController.text.isNotEmpty
          ? _countryNameController.text
          : 'India',
    );
    _clientAddressController.text =
        widget.propertyDetails?['client_address']?.toString() ??
        _areaListingSelection.manualAddress ??
        property?.clientAddress ??
        '';
    // Language Selector Tabs
    _tabController = TabController(length: languages.length, vsync: this);
    translatedFields =
        widget.propertyDetails?['translations'] as List<Translations>? ?? [];

    // Initialize title controllers
    _titleControllers = List.generate(languages.length, (index) {
      final langId = languages[index].id;

      final titleTranslation = translatedFields.firstWhere(
        (element) => element.languageId == langId && element.key == 'title',
        orElse: Translations.new,
      );

      return TextEditingController(
        text:
            titleTranslation.value ??
            widget.propertyDetails?['name']?.toString() ??
            '',
      );
    });

    // Initialize description controllers
    _descriptionControllers = List.generate(languages.length, (index) {
      final langId = languages[index].id;

      final descTranslation = translatedFields.firstWhere(
        (element) =>
            element.languageId == langId && element.key == 'description',
        orElse: Translations.new,
      );

      return TextEditingController(
        text:
            descTranslation.value ??
            widget.propertyDetails?['desc']?.toString() ??
            '',
      );
    });

    _titleControllers.first.addListener(() {
      setState(() {
        if (property?.slugId != null && property?.slugId != '') {
          _slugController.text = property?.slugId ?? '';
        }
        _slugController.text = generateSlug(_titleControllers.first.text);
      });
    });

    documentFiles =
        widget.propertyDetails?['documents'] as List<PropertyDocuments>? ?? [];
    propertyType = widget.propertyDetails?['propType'] == 'rent' ? 1 : 0;
    titleImageURL = widget.propertyDetails?['titleImage']?.toString() ?? '';
    titleImage = titleImageURL.isNotEmpty ? UrlValue(titleImageURL) : null;
    threeDImageURL = widget.propertyDetails?['three_d_image']?.toString() ?? '';
    removeThreeDImage =
        widget.propertyDetails?['remove_three_d_image'] as int? ?? 0;
    metaImageUrl = allPropData['meta_image']?.toString() ?? '';

    mixedPropertyImageList = List<dynamic>.from(
      widget.propertyDetails?['images'] as Iterable<dynamic>? ?? [],
    );

    if (widget.propertyDetails != null) {
      selectedRentType =
          (widget.propertyDetails?['rentduration']).toString().isEmpty
          ? 'Monthly'
          : widget.propertyDetails?['rentduration']?.toString() ?? '';
      isPrivateProperty = allPropData['is_premium'] as bool? ?? false;

      // Restore video type from edit data
      final videoType = allPropData['video_type'];
      if (videoType != null) {
        selectedVideoType = int.tryParse(videoType.toString()) ?? 1;
      }

      // Restore video link to the appropriate controller
      final videoLink = widget.propertyDetails?['video_link']?.toString() ?? '';
      if (selectedVideoType == 1) {
        _youtubeVideoLinkController.text = videoLink;
      } else if (selectedVideoType == 2) {
        _vimeoVideoLinkController.text = videoLink;
      } else if (selectedVideoType == 0) {
        _existingCustomVideoUrl = videoLink;
      }
    }

    metaTitleController.text = allPropData['meta_title']?.toString() ?? '';
    metaDescriptionController.text =
        allPropData['meta_description']?.toString() ?? '';
    metaKeywordController.text = allPropData['meta_keywords']?.toString() ?? '';

    // Update how we handle property image picker events
    _propertiesImagePicker.listener((dynamic result) {
      if (result != null) {
        try {
          if (result is List) {
            for (final file in result) {
              if (file is File) {
                mixedPropertyImageList.add(file);
              }
            }
          } else if (result is File) {
            // If a single image was selected
            mixedPropertyImageList.add(result);
          }
          setState(() {});
        } on Exception catch (_) {}
      }
    });

    super.initState();
  }

  String generateSlug(String input) {
    return input
        .replaceAll(' ', '-')
        .toLowerCase()
        .replaceAll(RegExp(r'[^\w-]'), '');
  }

  /// Validates image file size (3MB limit)
  bool _validateImageSize(File imageFile) {
    const maxSizeInBytes = 3 * 1024 * 1024; // 3 MB
    final fileSizeInBytes = imageFile.lengthSync();
    return fileSizeInBytes <= maxSizeInBytes;
  }

  /// Shows error dialog for oversized images
  Future<void> _showImageError({
    required bool isFromAllowedExtension,
  }) async {
    if (isAlreadyShowingImageError) return;
    isAlreadyShowingImageError = true;
    await UiUtils.showBlurredDialoge(
      context,
      sigmaX: 5,
      sigmaY: 5,
      dialog: BlurredDialogBox(
        svgImagePath: AppIcons.warning,
        title: isFromAllowedExtension
            ? 'invalidExtension'.translate(context)
            : 'imageTooBig'.translate(context),
        showCancleButton: false,
        onAccept: () async {
          setState(() {
            isAlreadyShowingImageError = false;
          });
        },
        acceptTextColor: context.color.buttonColor,
        content: CustomText(
          isFromAllowedExtension
              ? 'supportedFormats'.translate(context)
              : 'imageSizeLimit'.translate(context),
          textAlign: .center,
        ),
      ),
    );
  }

  Future<void> _onTapChooseLocation(FormFieldState<dynamic> state) async {
    FocusManager.instance.primaryFocus?.unfocus();

    if (Hive.box<dynamic>(
      HiveKeys.userDetailsBox,
    ).get('latitude').toString().isNotEmpty) {
      final dynamic latitudeValue =
          Hive.box<dynamic>(HiveKeys.userDetailsBox).get('latitude') ?? '0';
      localLatitude = double.tryParse(latitudeValue.toString()) ?? 0.0;
    }
    if (Hive.box<dynamic>(
      HiveKeys.userDetailsBox,
    ).get('longitude').toString().isNotEmpty) {
      final dynamic longitudeValue =
          Hive.box<dynamic>(HiveKeys.userDetailsBox).get('longitude') ?? '0';
      localLongitude = double.tryParse(longitudeValue.toString()) ?? 0.0;
    }

    final placeMark =
        await Navigator.pushNamed(
              context,
              Routes.chooseLocaitonMap,
              arguments: {},
            )
            as Map? ??
        {};
    final latlng = placeMark['latlng'] as LatLng?;
    final place = placeMark['place'] as Placemark?;
    if (latlng != null && place != null) {
      final parsed = AreaListingLocationSync.parseMapPickResult(
        rawComponents: placeMark['address_components'],
        placemarkCity: place.locality,
        placemarkState: place.administrativeArea,
        placemarkCountry: place.country,
      );

      _latitudeController.text = latlng.latitude.toString();
      _longitudeController.text = latlng.longitude.toString();
      _cityNameController.text = parsed.city;
      _countryNameController.text = parsed.country;
      _stateNameController.text = parsed.state;
      final formatted = placeMark['formatted_address']?.toString();
      _addressController.text = formatted?.isNotEmpty == true
          ? formatted!
          : [
              parsed.city,
              parsed.state,
              parsed.country,
            ].where((e) => e.isNotEmpty).join(', ');

      _areaListingSelection = await AreaListingLocationSync.afterMapPick(
        current: _areaListingSelection.copyWith(
          manualAddress: _clientAddressController.text.trim().isEmpty
              ? null
              : _clientAddressController.text.trim(),
        ),
        latitude: latlng.latitude,
        longitude: latlng.longitude,
        city: parsed.city,
        state: parsed.state,
        country: parsed.country,
        locationComponents: parsed.components,
        applyResolvedIds: AreaListingContext.applyResolvedIdsOnMap,
      );

      _areaMapEpoch++;
      state.didChange(true);
      if (mounted) setState(() {});
    } else {
      // state.didChange(false);
    }
  }

  Future<void> _onTapContinue() async {
    File? titleImageFile;
    File? v360Image;

    // Extract file from ImagePickerValue
    if (titleImage is FileValue) {
      titleImageFile = (titleImage! as FileValue).value;
    } else if (titleImage is UrlValue) {
      // Keep titleImageURL for server URL
      titleImageURL = (titleImage! as UrlValue).value;
    }

    if (_pick360deg.pickedFile != null) {
      v360Image = _pick360deg.pickedFile;
    }
    final check = _checkIfLocationIsChosen();
    if (!check) {
      Future.delayed(Duration.zero, () async {
        await UiUtils.showBlurredDialoge(
          context,
          sigmaX: 5,
          sigmaY: 5,
          dialog: BlurredDialogBox(
            svgImagePath: AppIcons.warning,
            title: 'incomplete'.translate(context),
            showCancleButton: false,
            onAccept: () async {},
            acceptTextColor: context.color.buttonColor,
            content: CustomText(
              'addressError'.translate(context),
              textAlign: .center,
            ),
          ),
        );
      });

      return;
    }

    if (!_areaListingSelection.isValidForSubmit) {
      return HelperUtils.showSnackBarMessage(
        context,
        'selectAreaRequired'.translate(context),
      );
    } else if (titleImage == null) {
      Future.delayed(Duration.zero, () async {
        await UiUtils.showBlurredDialoge(
          context,
          sigmaX: 5,
          sigmaY: 5,
          dialog: BlurredDialogBox(
            svgImagePath: AppIcons.warning,
            title: 'incomplete'.translate(context),
            showCancleButton: false,
            acceptTextColor: context.color.buttonColor,
            content: CustomText(
              'uploadImgMsgLbl'.translate(context),
              textAlign: .center,
            ),
          ),
        );
      });
      return;
    }

    if (_formKey.currentState!.validate()) {
      var documents = <String, dynamic>{};
      try {
        documents = documentFiles.fold({}, (pr, el) {
          pr.addAll({
            'documents[${pr.length}]': MultipartFile.fromFileSync(el.file!),
          });
          return pr;
        });
      } on Exception catch (_) {}

      _formKey.currentState?.save();

      final list = mixedPropertyImageList.map((e) {
        if (e is File) {
          return e;
        }
      }).toList()..removeWhere((element) => element == null);
      _clientAddressController
        ..clear()
        ..text = HiveUtils.getUserDetails().address ?? '';
      final metaImageData = metaImage?.value != '' && metaImage != null
          ? metaImage
          : null;

      if (_titleControllers.first.text.trim().isEmpty ||
          _descriptionControllers.first.text.trim().isEmpty) {
        return HelperUtils.showSnackBarMessage(
          context,
          'pleaseFillMainTitleAndDescription',
        );
      }

      for (var i = 0; i < languages.length; i++) {
        final langId = languages[i].id;

        final titleIndex = translatedFields.indexWhere(
          (t) => t.languageId == langId && t.key == 'title',
        );

        if (titleIndex != -1) {
          translatedFields[titleIndex].value = _titleControllers[i].text.trim();
        } else {
          translatedFields.add(
            Translations(
              languageId: langId,
              key: 'title',
              value: _titleControllers[i].text.trim(),
            ),
          );
        }

        // Update description
        final descIndex = translatedFields.indexWhere(
          (t) => t.languageId == langId && t.key == 'description',
        );

        if (descIndex != -1) {
          translatedFields[descIndex].value = _descriptionControllers[i].text
              .trim();
        } else {
          translatedFields.add(
            Translations(
              languageId: langId,
              key: 'description',
              value: _descriptionControllers[i].text.trim(),
            ),
          );
        }
      }

      toTranslationMap(translatedFields);

      propertyData.addAll({
        'title': _titleControllers.first.text,
        'slug_id': _slugController.text,
        'description': _descriptionControllers.first.text,
        'city': _cityNameController.text,
        'state': _stateNameController.text,
        'country': _countryNameController.text,
        'latitude': _latitudeController.text,
        'longitude': _longitudeController.text,
        'address': _addressController.text,
        'client_address': _clientAddressController.text,
        ..._areaListingSelection
            .copyWith(manualAddress: _clientAddressController.text)
            .toApiPayload(),
        'price': _priceController.text,
        'title_image': titleImageFile,
        'gallery_images': list,
        ...documents,
        'remove_gallery_images': removedImageId,
        'remove_documents': removedDocumentId,
        'remove_three_d_image': removeThreeDImage,
        'remove_meta_image': removeMetaImage,
        'category_id': widget.propertyDetails == null
            ? (Constant.addProperty['category'] as Category).id
            : widget.propertyDetails?['catId'],
        'property_type': propertyType,
        'three_d_image': v360Image,
        if (AppSettings.showDirectVideoUpload) ...{
          if (selectedVideoType == 0 &&
              (_selectedVideoFile != null ||
                  _existingCustomVideoUrl.isNotEmpty)) ...{
            'video_type': selectedVideoType,
            if (_selectedVideoFile != null)
              'custom_video': await MultipartFile.fromFile(
                _selectedVideoFile!.path,
              ),
          },
          if (selectedVideoType == 1 &&
              _youtubeVideoLinkController.text.isNotEmpty) ...{
            'video_type': selectedVideoType,
            'video_link': _youtubeVideoLinkController.text,
          },
          if (selectedVideoType == 2 &&
              _vimeoVideoLinkController.text.isNotEmpty) ...{
            'video_type': selectedVideoType,
            'video_link': _vimeoVideoLinkController.text,
          },
        } else ...{
          if (selectedVideoType == 1 &&
              _youtubeVideoLinkController.text.isNotEmpty) ...{
            'video_type': selectedVideoType,
            'video_link': _youtubeVideoLinkController.text,
          },
          if (selectedVideoType == 2 &&
              _vimeoVideoLinkController.text.isNotEmpty) ...{
            'video_type': selectedVideoType,
            'video_link': _vimeoVideoLinkController.text,
          },
        },
        'meta_title': metaTitleController.text,
        'meta_description': metaDescriptionController.text,
        'meta_keywords': metaKeywordController.text,
        if (metaImageUrl != metaImage?.value) 'meta_image': metaImageData,
        if (propertyType == 1) 'rentduration': selectedRentType,
        if (AppSettings.showPremiumToggle) 'is_premium': isPrivateProperty,
        if (_removeVideo &&
            _youtubeVideoLinkController.text.isEmpty &&
            _vimeoVideoLinkController.text.isEmpty &&
            _selectedVideoFile == null)
          'remove_video': 1,
        ...translationMap,
      });

      if (widget.propertyDetails?.containsKey('assign_facilities') ?? false) {
        propertyData['assign_facilities'] =
            widget.propertyDetails!['assign_facilities'];
      }
      if (widget.propertyDetails != null) {
        propertyData['id'] = widget.propertyDetails?['id'];
        propertyData['action_type'] = '0';
        if (widget.propertyDetails?['request_status'] != null) {
          propertyData['request_status'] =
              widget.propertyDetails!['request_status'];
        }
      }

      Future.delayed(
        Duration.zero,
        () async {
          // _pickMetaTitle.pauseSubscription();
          await Navigator.pushNamed(
            context,
            Routes.setPropertyParametersScreen,
            arguments: {
              'details': propertyData,
              'isUpdate': widget.propertyDetails != null,
            },
          );
        },
      );
    }
  }

  bool _checkIfLocationIsChosen() {
    if (_cityNameController.text == '' ||
        _stateNameController.text == '' ||
        _countryNameController.text == '' ||
        _latitudeController.text == '' ||
        _longitudeController.text == '') {
      return false;
    }
    return true;
  }

  @override
  void dispose() {
    // _pickMetaTitle.dispose();
    for (final element in _titleControllers) {
      element.dispose();
    }
    for (final element in _descriptionControllers) {
      element.dispose();
    }
    _cityNameController.dispose();
    _stateNameController.dispose();
    _countryNameController.dispose();
    _latitudeController.dispose();
    _longitudeController.dispose();
    _addressController.dispose();
    _priceController.dispose();
    _clientAddressController.dispose();
    _youtubeVideoLinkController.dispose();
    _vimeoVideoLinkController.dispose();
    _pick360deg.dispose();
    _propertiesImagePicker.dispose();
    _slugController.dispose();
    super.dispose();
  }

  Future<void> _generatePropertyDescription({required int index}) async {
    // Validate title is not empty
    if (_titleControllers[index].text.trim().isEmpty) {
      HelperUtils.showSnackBarMessage(
        context,
        'pleaseFillMainTitleAndDescription',
      );
      return;
    }

    final categoryId = widget.propertyDetails == null
        ? (Constant.addProperty['category'] as Category).id
        : widget.propertyDetails?['catId'];
    final propertyTypeStr = propertyType == 1 ? 'rent' : 'sell';
    final languageId = (languages[index].id is int)
        ? languages[index].id! as int
        : int.tryParse(languages[index].id.toString()) ?? 0;

    await context.read<GenerateAiContentCubit>().generateDescription(
      entityType: EntityType.property,
      languageId: languageId,
      entityId: widget.propertyDetails?['id']?.toString(),
      context: <String, dynamic>{
        'title': _titleControllers[index].text,
        'location': _addressController.text,
        'city': _cityNameController.text,
        'state': _stateNameController.text,
        'country': _countryNameController.text,
        'property_type': propertyTypeStr,
        'category_id': categoryId,
        'price': _priceController.text,
      },
    );
  }

  Future<void> _generatePropertyMeta() async {
    // Validate title is not empty
    if (_titleControllers.first.text.trim().isEmpty) {
      HelperUtils.showSnackBarMessage(
        context,
        'pleaseFillMetaTitle',
      );
      return;
    }

    final categoryId = widget.propertyDetails == null
        ? (Constant.addProperty['category'] as Category).id
        : widget.propertyDetails?['catId'];
    final propertyTypeStr = propertyType == 1 ? 'rent' : 'sell';

    await context.read<GenerateAiContentCubit>().generateMeta(
      entityType: EntityType.property,
      entityId: widget.propertyDetails?['id']?.toString(),
      context: <String, dynamic>{
        'title': _titleControllers.first.text,
        'location': _addressController.text,
        'city': _cityNameController.text,
        'state': _stateNameController.text,
        'country': _countryNameController.text,
        'property_type': propertyTypeStr,
        'category_id': categoryId,
        'price': _priceController.text,
      },
    );
  }

  List<Widget> documentsList() {
    return documentFiles.map((documents) {
      return Container(
        margin: const EdgeInsets.only(top: 4),
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          border: Border.all(color: context.color.borderColor),
          borderRadius: BorderRadius.circular(4),
          color: context.color.secondaryColor,
        ),
        child: Row(
          mainAxisAlignment: .spaceBetween,
          children: [
            CustomText(
              documents.name,
              maxLines: 2,
            ),
            GestureDetector(
              onTap: () {
                if (documents.id != null) {
                  removedDocumentId.add(documents.id!);
                }
                documentFiles.remove(documents);
                setState(() {});
              },
              child: Icon(
                Icons.close,
                color: context.color.textColorDark,
                size: 24.rh(context),
              ),
            ),
          ],
        ),
      );
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<GenerateAiContentCubit, GenerateAiContentState>(
      listener: (context, state) {
        if (state is GenerateDescriptionSuccess) {
          if (state.languageId != null) {
            final index = languages.indexWhere(
              (lang) =>
                  (lang.id is int
                      ? lang.id! as int
                      : int.tryParse(lang.id.toString()) ?? 0) ==
                  state.languageId,
            );
            if (index >= 0 && index < _descriptionControllers.length) {
              _descriptionControllers[index].text =
                  state.description.description;
            }
          }
          _isGeneratingDescription = false;
          _generatingDescriptionIndex = null;
          setState(() {});
        } else if (state is GenerateDescriptionFailure) {
          _isGeneratingDescription = false;
          _generatingDescriptionIndex = null;
          HelperUtils.showSnackBarMessage(
            context,
            state.error,
            type: .error,
          );
          setState(() {});
        } else if (state is GenerateDescriptionInProgress) {
          final index = languages.indexWhere(
            (lang) =>
                (lang.id is int
                    ? lang.id! as int
                    : int.tryParse(lang.id.toString()) ?? 0) ==
                state.languageId,
          );
          _isGeneratingDescription = true;
          _generatingDescriptionIndex = index >= 0 ? index : null;
          setState(() {});
        } else if (state is GenerateMetaSuccess) {
          metaTitleController.text = state.meta.metaTitle;
          metaDescriptionController.text = state.meta.metaDescription;
          metaKeywordController.text = state.meta.metaKeywords;
          _isGeneratingMeta = false;
          setState(() {});
        } else if (state is GenerateMetaFailure) {
          _isGeneratingMeta = false;
          HelperUtils.showSnackBarMessage(
            context,
            state.error,
            type: .error,
          );
          setState(() {});
        } else if (state is GenerateMetaInProgress) {
          _isGeneratingMeta = true;
          setState(() {});
        }
      },
      child: Scaffold(
        backgroundColor: context.color.primaryColor,
        bottomNavigationBar: ColoredBox(
          color: Colors.transparent,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: UiUtils.buildButton(
              context,
              onPressed: _onTapContinue,
              height: 48.rh(context),
              buttonTitle: 'continue'.translate(context),
            ),
          ),
        ),
        appBar: CustomAppBar(
          title: widget.propertyDetails == null
              ? 'ddPropertyLbl'.translate(context)
              : 'updateProperty'.translate(context),
          actions: [
            CustomText(
              '2/4',
              fontSize: context.font.sm,
              fontWeight: .w500,
              color: context.color.textColorDark,
            ),
          ],
        ),
        body: GestureDetector(
          onTap: () {
            FocusScope.of(context).unfocus();
          },
          child: Form(
            key: _formKey,
            child: SingleChildScrollView(
              physics: Constant.scrollPhysics,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: .start,
                  children: <Widget>[
                    buildLanguageSelector(context, _tabController, languages),
                    buildTitleAndDescriptionFields(
                      index: _tabController.index,
                      requiredSymbol: HelperUtils.requiredSymbol(context),
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomText('slugIdLbl'.translate(context)),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomTextFormField(
                      controller: _slugController,
                      validator: CustomTextFieldValidator.slugId,
                      action: .next,
                      hintText: 'slugIdOptional'.translate(context),
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),

                    CustomText('propertyType'.translate(context)),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    buildPropertyTypeSelector(context),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    if (AppSettings.showPremiumToggle) ...[
                      Row(
                        children: [
                          Expanded(
                            child: CustomText(
                              'isPrivateProperty'.translate(context),
                            ),
                          ),
                          CupertinoSwitch(
                            value: isPrivateProperty,
                            activeTrackColor: context.color.tertiaryColor,
                            onChanged: (value) {
                              isPrivateProperty = value;
                              setState(() {});
                            },
                          ),
                        ],
                      ),
                      SizedBox(
                        height: 8.rh(context),
                      ),
                    ],
                    SizedBox(
                      height: 35.rh(context),
                      child: Row(
                        children: [
                          Expanded(
                            child: Row(
                              children: [
                                CustomText(
                                  'addressLbl'.translate(context),
                                ),
                                const SizedBox(
                                  width: 3,
                                ),
                                HelperUtils.requiredSymbol(context),
                              ],
                            ),
                          ),
                          // const Spacer(),
                          ChooseLocationFormField(
                            initialValue:
                                widget.propertyDetails != null ||
                                _checkIfLocationIsChosen(),
                            validator: (value) {
                              //Check if it has already data so we will not validate it.
                              if (widget.propertyDetails != null ||
                                  _checkIfLocationIsChosen()) {
                                return null;
                              }

                              if (value ?? false) {
                                return null;
                              } else {
                                return 'pleaseSelectLocation'.translate(
                                  context,
                                );
                              }
                            },
                            build: (state) {
                              return Container(
                                decoration: BoxDecoration(
                                  border: Border.all(
                                    color: state.hasError
                                        ? Colors.red
                                        : Colors.transparent,
                                  ),
                                  borderRadius: BorderRadius.circular(9),
                                ),
                                child: InkWell(
                                  onTap: () async {
                                    await _onTapChooseLocation.call(state);
                                  },
                                  child: Row(
                                    mainAxisSize: .min,
                                    children: [
                                      CustomImage(
                                        imageUrl: AppIcons.location,
                                        color: context.color.textLightColor,
                                      ),
                                      const SizedBox(
                                        width: 3,
                                      ),
                                      CustomText(
                                        'chooseLocation'.translate(context),
                                        fontSize: context.font.sm,
                                        color: context.color.tertiaryColor,
                                      ),
                                      const SizedBox(
                                        width: 3,
                                      ),
                                      HelperUtils.requiredSymbol(context),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomTextFormField(
                      action: .next,
                      controller: _cityNameController,
                      isReadOnly: false,
                      validator: CustomTextFieldValidator.nullCheck,
                      hintText: 'city'.translate(context),
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomTextFormField(
                      action: .next,
                      controller: _stateNameController,
                      isReadOnly: false,
                      validator: CustomTextFieldValidator.nullCheck,
                      hintText: 'state'.translate(context),
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomTextFormField(
                      action: .next,
                      controller: _countryNameController,
                      isReadOnly: false,
                      validator: CustomTextFieldValidator.nullCheck,
                      hintText: 'country'.translate(context),
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    AreaListingPicker(
                      key: ValueKey(
                        'area_map_$_areaMapEpoch/'
                        '${_cityNameController.text}_${_stateNameController.text}_'
                        '${_areaListingSelection.detectedAreaName}_'
                        '${_areaListingSelection.detectedSubAreaName}_'
                        '${_areaListingSelection.areaId}_'
                        '${_areaListingSelection.subAreaId}',
                      ),
                      initialSelection: _areaListingSelection,
                      showStateCity: false,
                      requiresCity: true,
                      parentCityName: _cityNameController.text,
                      parentStateName: _stateNameController.text,
                      country: _countryNameController.text.isNotEmpty
                          ? _countryNameController.text
                          : 'India',
                      onChanged: (selection) {
                        _areaListingSelection = selection;
                        setState(() {});
                      },
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomTextFormField(
                      action: .next,
                      controller: _addressController,
                      hintText: 'googleAddressLbl'.translate(context),
                      maxLine: 100,
                      validator: CustomTextFieldValidator.nullCheck,
                      minLine: 4,
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomTextFormField(
                      action: .next,
                      controller: _clientAddressController,
                      hintText: 'clientaddressLbl'.translate(context),
                      maxLine: 100,
                      minLine: 4,
                      onChange: (value) {
                        _areaListingSelection = _areaListingSelection.copyWith(
                          manualAddress: value?.toString(),
                        );
                      },
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    if (propertyType == 1) ...[
                      Row(
                        children: [
                          CustomText('rentPrice'.translate(context)),
                          const SizedBox(
                            width: 3,
                          ),
                          HelperUtils.requiredSymbol(context),
                        ],
                      ),
                    ] else ...[
                      Row(
                        children: [
                          CustomText('price'.translate(context)),
                          const SizedBox(
                            width: 3,
                          ),
                          HelperUtils.requiredSymbol(context),
                        ],
                      ),
                    ],
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    Row(
                      children: [
                        Expanded(
                          child: CustomTextFormField(
                            action: .next,
                            prefix: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                              ),
                              child: CustomText(
                                '${Constant.currencySymbol} ',
                                fontWeight: .w600,
                              ),
                            ),
                            controller: _priceController,
                            formaters: [
                              FilteringTextInputFormatter.allow(
                                RegExp(r'^\d+\.?\d*'),
                              ),
                            ],
                            isReadOnly: false,
                            keyboard: TextInputType.number,
                            validator: CustomTextFieldValidator.priceCheck,
                            hintText: '00',
                          ),
                        ),
                        if (propertyType == 1) ...[
                          const SizedBox(
                            width: 5,
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12),
                            decoration: BoxDecoration(
                              color: context.color.secondaryColor,
                              border: Border.all(
                                color: context.color.borderColor,
                              ),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: DropdownButton<String>(
                              value: selectedRentType,
                              dropdownColor: context.color.primaryColor,
                              underline: const SizedBox.shrink(),
                              items: [
                                DropdownMenuItem(
                                  value: 'Daily',
                                  child: CustomText(
                                    'daily'.translate(context),
                                  ),
                                ),
                                DropdownMenuItem(
                                  value: 'Monthly',
                                  child: CustomText(
                                    'monthly'.translate(context),
                                  ),
                                ),
                                DropdownMenuItem(
                                  value: 'Quarterly',
                                  child: CustomText(
                                    'quarterly'.translate(context),
                                  ),
                                ),
                                DropdownMenuItem(
                                  value: 'Yearly',
                                  child: CustomText(
                                    'yearly'.translate(context),
                                  ),
                                ),
                              ],
                              onChanged: (value) {
                                selectedRentType = value ?? '';
                                setState(() {});
                              },
                            ),
                          ),
                        ],
                      ],
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    Row(
                      children: [
                        CustomText('uploadPictures'.translate(context)),
                        const SizedBox(
                          width: 3,
                        ),
                        CustomText(
                          'maxSize'.translate(context),
                          fontStyle: .italic,
                          fontSize: context.font.xs,
                        ),
                      ],
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    titleImageListener(),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomText('otherPictures'.translate(context)),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    propertyImagesListener(),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    DottedBorder(
                      options: RoundedRectDottedBorderOptions(
                        color: context.color.textLightColor,
                        radius: const Radius.circular(4),
                      ),
                      child: GestureDetector(
                        onTap: () async {
                          await _pick360deg.pick(pickMultiple: false);
                        },
                        child: Container(
                          clipBehavior: .antiAlias,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(4),
                          ),
                          alignment: Alignment.center,
                          height: 48.rh(context),
                          child: CustomText(
                            'add360degPicture'.translate(context),
                          ),
                        ),
                      ),
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    // SHOW 360 PICTURE CODE
                    _pick360deg.listenChangesInUI((context, image) {
                      if (image != null) {
                        return Stack(
                          children: [
                            Container(
                              width: 100.rw(context),
                              height: 100.rw(context),
                              clipBehavior: .antiAlias,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Image.file(
                                image as File,
                                fit: .cover,
                              ),
                            ),
                            Positioned.fill(
                              child: GestureDetector(
                                onTap: () async {
                                  await Navigator.push(
                                    context,
                                    CupertinoPageRoute<dynamic>(
                                      builder: (context) {
                                        return PanaromaImageScreen(
                                          imageUrl: image.path,
                                          isFileImage: true,
                                        );
                                      },
                                    ),
                                  );
                                },
                                child: Container(
                                  width: 100.rw(context),
                                  height: 100.rh(context),
                                  decoration: BoxDecoration(
                                    color: context.color.tertiaryColor
                                        .withValues(
                                          alpha: 0.5,
                                        ),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: FittedBox(
                                    fit: .none,
                                    child: Container(
                                      decoration: BoxDecoration(
                                        shape: .circle,
                                        color: context.color.secondaryColor,
                                      ),
                                      width: 60.rw(context),
                                      height: 60.rh(context),
                                      child: Center(
                                        child: Column(
                                          mainAxisSize: .min,
                                          children: [
                                            SizedBox(
                                              height: 30.rh(context),
                                              width: 30.rw(context),
                                              child: CustomImage(
                                                imageUrl: AppIcons.v360Degree,
                                                color:
                                                    context.color.textColorDark,
                                                fit: .contain,
                                              ),
                                            ),
                                            CustomText(
                                              'view'.translate(context),
                                              fontWeight: .bold,
                                              fontSize: context.font.xs,
                                              color:
                                                  context.color.textColorDark,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            closeButton(context, () {
                              setState(() {
                                if (threeDImageURL.isNotEmpty) {
                                  removeThreeDImage = 1;
                                  threeDImageURL = '';
                                } else {
                                  removeThreeDImage = 0;
                                }
                                _pick360deg.clearImage();
                              });
                            }),
                          ],
                        );
                      }
                      return Container();
                    }),
                    _pick360deg.listenChangesInUI((context, image) {
                      if (threeDImageURL != '' && image == null) {
                        return Stack(
                          children: [
                            Container(
                              width: 100.rw(context),
                              height: 100.rh(context),
                              clipBehavior: .antiAlias,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Image.network(
                                threeDImageURL,
                                fit: .cover,
                              ),
                            ),
                            Positioned.fill(
                              child: GestureDetector(
                                onTap: () async {
                                  await Navigator.push(
                                    context,
                                    CupertinoPageRoute<dynamic>(
                                      builder: (context) {
                                        return PanaromaImageScreen(
                                          imageUrl: threeDImageURL,
                                          isFileImage: true,
                                        );
                                      },
                                    ),
                                  );
                                },
                                child: Container(
                                  width: 100.rw(context),
                                  height: 100.rh(context),
                                  decoration: BoxDecoration(
                                    color: context.color.tertiaryColor
                                        .withValues(
                                          alpha: 0.68,
                                        ),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: FittedBox(
                                    fit: .none,
                                    child: Container(
                                      decoration: BoxDecoration(
                                        shape: .circle,
                                        color: context.color.secondaryColor,
                                      ),
                                      width: 60.rw(context),
                                      height: 60.rh(context),
                                      child: Center(
                                        child: Column(
                                          mainAxisSize: .min,
                                          children: [
                                            SizedBox(
                                              height: 30.rh(context),
                                              width: 30.rw(context),
                                              child: CustomImage(
                                                imageUrl: AppIcons.v360Degree,
                                                color:
                                                    context.color.textColorDark,
                                                fit: .contain,
                                              ),
                                            ),
                                            CustomText(
                                              'view'.translate(context),
                                              fontWeight: .bold,
                                              fontSize: context.font.xs,
                                              color:
                                                  context.color.textColorDark,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            closeButton(context, () {
                              setState(() {
                                _pick360deg.clearImage();
                                threeDImageURL = '';
                                removeThreeDImage = 1;
                              });
                            }),
                          ],
                        );
                      }
                      return const SizedBox.shrink();
                    }),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomText('additionals'.translate(context)),
                    SizedBox(
                      height: 8.rh(context),
                    ),

                    VideoSelector(
                      selectedVideoType: selectedVideoType,
                      onValueChanged: (value) {
                        selectedVideoType = value;
                        setState(() {});
                      },
                      youtubeController: _youtubeVideoLinkController,
                      vimeoController: _vimeoVideoLinkController,
                      selectedVideoFile: _selectedVideoFile,
                      existingCustomVideoUrl: _existingCustomVideoUrl,
                      onFileSelected: (file) {
                        // New file replaces the existing remote video
                        _selectedVideoFile = file;
                        if (file != null) _existingCustomVideoUrl = '';
                        setState(() {});
                      },
                      onRemoveVideo: () {
                        _removeVideo = true;
                        _existingCustomVideoUrl = '';
                        setState(() {});
                      },
                    ),

                    SizedBox(
                      height: 8.rh(context),
                    ),
                    CustomText('propertyDocuments'.translate(context)),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    buildDocumentsPicker(context),
                    ...documentsList(),
                    SizedBox(
                      height: 8.rh(context),
                    ),
                    SizedBox(
                      height: 8.rh(context),
                    ),

                    // Meta Details
                    Container(
                      decoration: BoxDecoration(
                        color: context.color.secondaryColor,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: context.color.borderColor),
                      ),
                      child: ExpansionTile(
                        dense: true,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                        collapsedShape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                        backgroundColor: context.color.primaryColor,
                        collapsedBackgroundColor: context.color.secondaryColor,
                        tilePadding: const EdgeInsets.symmetric(
                          horizontal: 12,
                        ),
                        childrenPadding: EdgeInsets.zero,
                        initiallyExpanded: isMetaDetailsExpanded,
                        onExpansionChanged: (value) {
                          setState(() {
                            isMetaDetailsExpanded = value;
                          });
                        },
                        trailing: Icon(
                          isMetaDetailsExpanded
                              ? Icons.keyboard_arrow_down
                              : Icons.keyboard_arrow_right,
                          color: context.color.textColorDark,
                          size: 20.rh(context),
                        ),
                        title: Row(
                          children: [
                            Expanded(
                              child: CustomText(
                                'Meta Details'.translate(context),
                                fontWeight: .w600,
                                color: context.color.textColorDark,
                              ),
                            ),
                            if (metaTitleController.text.isNotEmpty)
                              GenerateWithAiButton(
                                onTap: _generatePropertyMeta,
                                isLoading: _isGeneratingMeta,
                              ),
                          ],
                        ),
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              crossAxisAlignment: .start,
                              children: [
                                if (_isGeneratingMeta)
                                  Shimmer.fromColors(
                                    period: const Duration(
                                      milliseconds: 1000,
                                    ),
                                    baseColor: Theme.of(
                                      context,
                                    ).colorScheme.shimmerBaseColor,
                                    highlightColor: Theme.of(
                                      context,
                                    ).colorScheme.shimmerHighlightColor,
                                    child: CustomTextFormField(
                                      controller: metaTitleController,
                                      hintText: 'generating'.translate(context),
                                    ),
                                  )
                                else
                                  CustomTextFormField(
                                    controller: metaTitleController,
                                    hintText: 'Title'.translate(context),
                                  ),
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 4,
                                  ),
                                  child: CustomText(
                                    'metaTitleLength'.translate(context),
                                    fontSize: context.font.xs,
                                    color: context.color.textLightColor,
                                  ),
                                ),
                                SizedBox(
                                  height: 8.rh(context),
                                ),
                                if (_isGeneratingMeta)
                                  Shimmer.fromColors(
                                    period: const Duration(
                                      milliseconds: 1000,
                                    ),
                                    baseColor: Theme.of(
                                      context,
                                    ).colorScheme.shimmerBaseColor,
                                    highlightColor: Theme.of(
                                      context,
                                    ).colorScheme.shimmerHighlightColor,
                                    child: CustomTextFormField(
                                      controller: metaDescriptionController,
                                      hintText: 'generating'.translate(context),
                                    ),
                                  )
                                else
                                  CustomTextFormField(
                                    controller: metaDescriptionController,
                                    hintText: 'Description'.translate(
                                      context,
                                    ),
                                  ),
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 4,
                                  ),
                                  child: CustomText(
                                    'metaDescriptionLength'.translate(context),
                                    fontSize: context.font.xs,
                                    color: context.color.textLightColor,
                                  ),
                                ),
                                SizedBox(
                                  height: 8.rh(context),
                                ),
                                if (_isGeneratingMeta)
                                  Shimmer.fromColors(
                                    period: const Duration(
                                      milliseconds: 1000,
                                    ),
                                    baseColor: Theme.of(
                                      context,
                                    ).colorScheme.shimmerBaseColor,
                                    highlightColor: Theme.of(
                                      context,
                                    ).colorScheme.shimmerHighlightColor,
                                    child: CustomTextFormField(
                                      controller: metaKeywordController,
                                      hintText: 'generating'.translate(context),
                                    ),
                                  )
                                else
                                  CustomTextFormField(
                                    controller: metaKeywordController,
                                    hintText: 'Keywords'.translate(context),
                                  ),
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 4,
                                  ),
                                  child: CustomText(
                                    'metaKeywordsLength'.translate(context),
                                    fontSize: context.font.xs,
                                    color: context.color.textLightColor,
                                  ),
                                ),
                                SizedBox(
                                  height: 10.rh(context),
                                ),
                                AdaptiveImagePickerWidget(
                                  isRequired: false,
                                  title: 'addMetaImage'.translate(context),
                                  multiImage: false,
                                  value: metaImage,
                                  allowedSizeBytes:
                                      3 * 1024 * 1024, // 3 MB limit
                                  onSelect: (selected) {
                                    if (selected is FileValue) {
                                      // User selected a new meta image file -> do not remove existing on server
                                      metaImage = selected;
                                      removeMetaImage = 0;
                                      setState(() {});
                                    } else if (selected == null) {
                                      // Cleared current selection (no server removal intended)
                                      metaImage = null;
                                      setState(() {});
                                    }
                                  },
                                  onRemove: (value) {
                                    // For single-image mode, value is null. Determine prior state.
                                    final wasServerUrl =
                                        (metaImage is UrlValue) &&
                                        metaImageUrl.isNotEmpty;

                                    if (wasServerUrl) {
                                      // User removed existing server image
                                      removeMetaImage = 1;
                                      metaImageUrl = '';
                                      metaImage = UrlValue('');
                                    } else {
                                      // User removed a newly selected local file (no server removal)
                                      metaImage = null;
                                      removeMetaImage = 0;
                                    }
                                    setState(() {});
                                  },
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(
                      height: 30,
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget buildLanguageSelector(
    BuildContext context,
    TabController tabController,
    List<LanguagesModel> languages,
  ) {
    return Center(
      child: CustomTabBar(
        onTap: (value) => setState(() {
          FocusScope.of(context).unfocus();
        }),
        margin: EdgeInsets.zero,
        tabController: tabController,
        isScrollable: true,
        tabs: languages
            .map(
              (lang) => SizedBox(
                width: 85.rw(context),
                child: Tab(
                  text: lang.name ?? '',
                ),
              ),
            )
            .toList(),
      ),
    );
  }

  Map<String, dynamic> toTranslationMap(List<Translations> translatedFields) {
    translationMap.clear();

    for (var i = 0; i < languages.length; i++) {
      final langId = languages[i].id;

      final titleField = translatedFields.firstWhere(
        (t) => t.languageId == langId && t.key == 'title',
        orElse: Translations.new,
      );

      final descField = translatedFields.firstWhere(
        (t) => t.languageId == langId && t.key == 'description',
        orElse: Translations.new,
      );

      final titleValue = titleField.value?.trim() ?? '';
      final descValue = descField.value?.trim() ?? '';

      if (titleValue.isNotEmpty) {
        translationMap.addAll({
          'translations[$i][title][translation_id]': titleField.id ?? '',
          'translations[$i][title][language_id]': langId,
          'translations[$i][title][value]': titleValue,
        });
      }

      if (descValue.isNotEmpty) {
        translationMap.addAll({
          'translations[$i][description][translation_id]': descField.id ?? '',
          'translations[$i][description][language_id]': langId,
          'translations[$i][description][value]': descValue,
        });
      }
    }

    return translationMap;
  }

  Widget buildTitleAndDescriptionFields({
    required int index,
    required Widget requiredSymbol,
  }) {
    return Column(
      children: [
        Row(
          children: [
            CustomText(
              '${'propertyNameLbl'.translate(context)} (${languages[index].name})',
            ),
            const SizedBox(width: 3),
            if (index == 0) requiredSymbol,
          ],
        ),
        SizedBox(
          height: 8.rh(context),
        ),
        CustomTextFormField(
          controller: _titleControllers[index],
          validator: index == 0 ? CustomTextFieldValidator.nullCheck : null,
          action: .next,
          hintText: 'propertyNameLbl'.translate(context),
        ),
        SizedBox(
          height: 8.rh(context),
        ),
        Row(
          children: [
            Expanded(
              child: Row(
                children: [
                  CustomText(
                    '${'descriptionLbl'.translate(context)} (${languages[index].name})',
                  ),
                  const SizedBox(width: 3),
                  if (index == 0) requiredSymbol,
                ],
              ),
            ),
            if (_titleControllers[index].text.isNotEmpty)
              GenerateWithAiButton(
                onTap: () => _generatePropertyDescription(index: index),
                isLoading:
                    _isGeneratingDescription &&
                    _generatingDescriptionIndex == index,
              ),
          ],
        ),
        SizedBox(
          height: 8.rh(context),
        ),
        if (_isGeneratingDescription && _generatingDescriptionIndex == index)
          Shimmer.fromColors(
            period: const Duration(milliseconds: 1000),
            baseColor: Theme.of(context).colorScheme.shimmerBaseColor,
            highlightColor: Theme.of(
              context,
            ).colorScheme.shimmerHighlightColor,
            child: CustomTextFormField(
              action: .next,

              controller: _descriptionControllers[index],
              validator: index == 0 ? CustomTextFieldValidator.nullCheck : null,
              hintText: 'generating'.translate(context),
              maxLine: 25,
              minLine: 6,
            ),
          )
        else
          CustomTextFormField(
            action: .next,
            keyboard: .text,
            controller: _descriptionControllers[index],
            validator: index == 0 ? CustomTextFieldValidator.nullCheck : null,
            hintText: 'writeSomething'.translate(context),
            maxLine: 25,
            minLine: 6,
          ),
      ],
    );
  }

  InputDecorator buildPropertyTypeSelector(BuildContext context) {
    return InputDecorator(
      decoration: InputDecoration(
        hintStyle: TextStyle(
          color: context.color.textColorDark.withValues(alpha: 0.7),
          fontSize: context.font.md,
        ),
        filled: true,
        fillColor: context.color.secondaryColor,
        focusedBorder: OutlineInputBorder(
          borderSide: BorderSide(color: context.color.tertiaryColor),
          borderRadius: BorderRadius.circular(4),
        ),
        enabledBorder: OutlineInputBorder(
          borderSide: BorderSide(color: context.color.borderColor),
          borderRadius: BorderRadius.circular(4),
        ),
        border: OutlineInputBorder(
          borderSide: BorderSide(color: context.color.borderColor),
          borderRadius: BorderRadius.circular(4),
        ),
      ),
      child: DropdownButton<int>(
        value: propertyType,
        isExpanded: true,
        isDense: true,
        dropdownColor: context.color.secondaryColor,
        borderRadius: BorderRadius.zero,
        padding: EdgeInsets.zero,
        underline: const SizedBox.shrink(),
        items: [
          DropdownMenuItem(
            value: 0,
            child: CustomText('sell'.translate(context)),
          ),
          DropdownMenuItem(
            value: 1,
            child: CustomText('rent'.translate(context)),
          ),
        ],
        onTap: () {},
        onChanged: (value) {
          propertyType = value!;
          setState(() {});
        },
      ),
    );
  }

  Widget propertyImagesListener() {
    // Use a StatefulBuilder to ensure updates within this widget trigger rebuilds
    return StatefulBuilder(
      builder: (context, setLocalState) {
        return Column(
          crossAxisAlignment: .start,
          children: [
            Wrap(
              children: [
                // Show upload button if no images
                if (mixedPropertyImageList.isEmpty)
                  DottedBorder(
                    options: RoundedRectDottedBorderOptions(
                      color: context.color.textLightColor,
                      radius: const Radius.circular(4),
                    ),
                    child: GestureDetector(
                      onTap: () async {
                        try {
                          final picker = ImagePicker();
                          final images = await picker.pickMultiImage(
                            imageQuality: Constant.uploadImageQuality,
                          );

                          if (images.isNotEmpty) {
                            final validImages = <File>[];
                            var oversizedCount = 0;

                            for (final image in images) {
                              final file = File(image.path);
                              final hasAllowedExtension = _hasAllowedExtension(
                                file.path,
                              );
                              if (_validateImageSize(file) &&
                                  hasAllowedExtension) {
                                validImages.add(file);
                              } else {
                                if (!hasAllowedExtension) {
                                  await _showImageError(
                                    isFromAllowedExtension: true,
                                  );
                                } else {
                                  oversizedCount++;
                                }
                              }
                            }

                            if (validImages.isNotEmpty) {
                              mixedPropertyImageList.addAll(validImages);
                              setLocalState(() {});
                              setState(() {});
                            }

                            if (oversizedCount > 0) {
                              await _showImageError(
                                isFromAllowedExtension: false,
                              );
                            }
                          }
                        } on Exception catch (_) {}
                      },
                      child: Container(
                        clipBehavior: .antiAlias,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(4),
                        ),
                        alignment: Alignment.center,
                        height: 48.rh(context),
                        child: CustomText('addOtherPicture'.translate(context)),
                      ),
                    ),
                  ),

                // Display existing images
                ...mixedPropertyImageList.map((image) {
                  return Stack(
                    children: [
                      GestureDetector(
                        onTap: () async {
                          HelperUtils.unfocus();
                          if (image is String) {
                            await UiUtils.showFullScreenImage(
                              context,
                              provider: NetworkImage(image),
                            );
                          } else if (image is File) {
                            await UiUtils.showFullScreenImage(
                              context,
                              provider: FileImage(image),
                            );
                          }
                        },
                        child: Container(
                          width: 100,
                          height: 100,
                          margin: const EdgeInsets.all(5),
                          clipBehavior: .antiAlias,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: ImageAdapter(
                            image: image,
                          ),
                        ),
                      ),
                      closeButton(context, () {
                        if (image is String) {
                          try {
                            final propertyDetail =
                                widget.propertyDetails?['gallary_with_id']
                                    as List<Gallery>?;
                            if (propertyDetail != null) {
                              final galleryItem = propertyDetail.firstWhere(
                                (element) => element.imageUrl == image,
                                orElse: () => const Gallery(
                                  id: -1,
                                  image: '',
                                  imageUrl: '',
                                ),
                              );

                              if (galleryItem.id != -1) {
                                removedImageId.add(galleryItem.id);
                              }
                            }
                          } on Exception catch (_) {}
                        }

                        // Remove the image and update both states
                        mixedPropertyImageList.remove(image);
                        setLocalState(() {});
                        setState(() {});
                      }),
                    ],
                  );
                }),

                // Show add more button if images exist
                if (mixedPropertyImageList.isNotEmpty)
                  uploadPhotoCard(
                    context,
                    onTap: () async {
                      try {
                        final picker = ImagePicker();
                        final images = await picker.pickMultiImage(
                          imageQuality: Constant.uploadImageQuality,
                        );

                        if (images.isNotEmpty) {
                          final validImages = <File>[];
                          var oversizedCount = 0;

                          for (final image in images) {
                            final file = File(image.path);
                            final hasAllowedExtension = _hasAllowedExtension(
                              file.path,
                            );
                            if (_validateImageSize(file) &&
                                hasAllowedExtension) {
                              validImages.add(file);
                            } else {
                              if (!hasAllowedExtension) {
                                await _showImageError(
                                  isFromAllowedExtension: true,
                                );
                              } else {
                                oversizedCount++;
                              }
                            }
                          }

                          if (validImages.isNotEmpty) {
                            mixedPropertyImageList.addAll(validImages);
                            setLocalState(() {});
                            setState(() {});
                          }

                          if (oversizedCount > 0) {
                            await _showImageError(
                              isFromAllowedExtension: false,
                            );
                          }
                        }
                      } on Exception catch (_) {}
                    },
                  ),
              ],
            ),
          ],
        );
      },
    );
  }

  bool _hasAllowedExtension(String path) {
    final allowedExtensions = <String>{'jpg', 'jpeg', 'png', 'webp'};
    final segments = path.split('.');
    if (segments.length < 2) return false;
    final ext = segments.last.toLowerCase();
    return allowedExtensions.contains(ext);
  }

  Widget titleImageListener() {
    return AdaptiveImagePickerWidget(
      isRequired: true,
      title: 'addMainPicture'.translate(context),
      multiImage: false,
      value: titleImage,
      allowedSizeBytes: 3 * 1024 * 1024, // 3 MB limit
      onSelect: (selected) {
        if (selected is FileValue || selected is UrlValue) {
          // User selected a new title image file or it's a restored URL
          titleImage = selected;
          titleImageURL = selected is UrlValue ? selected.value : '';
          setState(() {});
        } else if (selected == null) {
          // Cleared current selection
          titleImage = null;
          titleImageURL = '';
          setState(() {});
        }
      },
      onRemove: (value) {
        // User removed the image
        titleImage = null;
        titleImageURL = '';
        setState(() {});
      },
    );
  }

  Widget buildDocumentsPicker(BuildContext context) {
    return GestureDetector(
      onTap: () async {
        final filePickerResult = await FilePicker.platform.pickFiles(
          allowMultiple: true,
          type: FileType.custom,
          allowedExtensions: ['pdf', 'doc', 'docx', 'txt'],
        );
        if (filePickerResult != null) {
          final list = filePickerResult.files.map<PropertyDocuments>((e) {
            return PropertyDocuments(
              name: e.name,
              file: e.path,
            );
          });
          documentFiles.addAll(list);
        }

        setState(() {});
      },
      child: DottedBorder(
        options: RoundedRectDottedBorderOptions(
          color: context.color.textLightColor,
          radius: const Radius.circular(4),
        ),
        child: SizedBox(
          width: double.infinity,
          height: 64.rh(context),
          child: Row(
            mainAxisAlignment: .center,
            children: [
              Icon(
                Icons.upload,
                color: context.color.textColorDark,
              ),
              const SizedBox(width: 4),
              CustomText('UploadDocs'.translate(context)),
              const SizedBox(width: 4),
              CustomText(documentFiles.length.toString()),
            ],
          ),
        ),
      ),
    );
  }
}

Widget uploadPhotoCard(BuildContext context, {required Function onTap}) {
  return GestureDetector(
    onTap: () {
      onTap.call();
    },
    child: Container(
      width: 100.rw(context),
      height: 100.rh(context),
      margin: const EdgeInsetsDirectional.only(end: 8),
      clipBehavior: .antiAlias,
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(4)),
      child: DottedBorder(
        options: RoundedRectDottedBorderOptions(
          color: context.color.textColorDark.withValues(alpha: 0.5),
          radius: const Radius.circular(4),
        ),
        child: Container(
          alignment: Alignment.center,
          child: CustomText('uploadPhoto'.translate(context)),
        ),
      ),
    ),
  );
}

PositionedDirectional closeButton(BuildContext context, Function onTap) {
  return PositionedDirectional(
    top: 4,
    end: 4,
    child: GestureDetector(
      onTap: () {
        onTap.call();
      },
      child: Container(
        decoration: BoxDecoration(
          color: context.color.primaryColor.withValues(alpha: 0.7),
          borderRadius: BorderRadius.circular(4),
        ),
        child: Padding(
          padding: const EdgeInsets.all(2),
          child: Icon(
            Icons.close,
            size: 24.rh(context),
            color: context.color.textColorDark,
          ),
        ),
      ),
    ),
  );
}

class ChooseLocationFormField extends FormField<bool> {
  ChooseLocationFormField({
    required Widget Function(FormFieldState<bool> state) build,
    super.key,
    super.onSaved,
    super.validator,
    super.initialValue,
  }) : super(
         builder: (state) {
           return build(state);
         },
       );
}

class ImageAdapter extends StatelessWidget {
  const ImageAdapter({super.key, this.image});

  final dynamic image;

  @override
  Widget build(BuildContext context) {
    if (image is String) {
      return Image.network(
        image?.toString() ?? '',
        fit: .cover,
      );
    } else if (image is File) {
      return Image.file(
        image as File,
        fit: .cover,
      );
    }
    return Container();
  }
}
