import 'package:country_picker/country_picker.dart';
import 'package:ebroker/data/cubits/agents/agent_profile_cubit.dart';
import 'package:ebroker/data/cubits/agents/update_agent_profile_cubit.dart';
import 'package:ebroker/data/model/agent_profile_model.dart';
import 'package:ebroker/data/repositories/auth_repository.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/auth/country_picker.dart';
import 'package:ebroker/ui/screens/widgets/image_cropper.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shimmer/shimmer.dart';

class EditAgentProfileScreen extends StatefulWidget {
  const EditAgentProfileScreen({super.key});

  static Route<dynamic> route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider(create: (_) => UpdateAgentProfileCubit()),
        ],
        child: const EditAgentProfileScreen(),
      ),
    );
  }

  @override
  State<EditAgentProfileScreen> createState() => _EditAgentProfileScreenState();
}

class _EditAgentProfileScreenState extends State<EditAgentProfileScreen> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _mobileController = TextEditingController();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _aboutMeController = TextEditingController();
  final TextEditingController _facebookController = TextEditingController();
  final TextEditingController _twitterController = TextEditingController();
  final TextEditingController _youtubeController = TextEditingController();
  final TextEditingController _instagramController = TextEditingController();

  File? _profilePhoto;
  String? _currentProfileUrl;
  String? flagEmoji;
  String? selectedCountryCode = HiveUtils.getUserDetails().countryCode ?? '';

  @override
  void initState() {
    super.initState();
    unawaited(context.read<AgentProfileCubit>().fetchAgentProfile());
    unawaited(
      HelperUtils.getSimCountry().then((value) {
        selectedCountryCode = value.phoneCode;
        flagEmoji = value.flagEmoji;
        setState(() {});
      }),
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _mobileController.dispose();
    _addressController.dispose();
    _aboutMeController.dispose();
    _facebookController.dispose();
    _twitterController.dispose();
    _youtubeController.dispose();
    _instagramController.dispose();
    super.dispose();
  }

  void _populateFields(AgentProfileModel profile) {
    _nameController.text = profile.agentName ?? '';
    _emailController.text = profile.agentEmail ?? '';
    _mobileController.text = profile.agentMobile ?? '';
    _addressController.text = profile.agentAddress ?? '';
    _aboutMeController.text = profile.aboutMe ?? '';
    _facebookController.text = profile.facebookId ?? '';
    _twitterController.text = profile.twitterId ?? '';
    _youtubeController.text = profile.youtubeId ?? '';
    _instagramController.text = profile.instagramId ?? '';
    _currentProfileUrl = profile.agentProfilePhoto;
    setState(() {});
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final checkInternet = await HelperUtils.checkInternet();
    if (!checkInternet) {
      HelperUtils.showSnackBarMessage(context, 'lblchecknetwork');
      return;
    }
    if (selectedCountryCode == null || selectedCountryCode == '') {
      HelperUtils.showSnackBarMessage(
        context,
        'pleaseSelectCountry'.translate(context),
      );
      return;
    }
    await context.read<UpdateAgentProfileCubit>().updateProfile(
      agentName: _nameController.text.trim(),
      email: _emailController.text.trim(),
      mobile: _mobileController.text.trim(),
      countryCode: selectedCountryCode,
      address: _addressController.text.trim(),
      aboutMe: _aboutMeController.text.trim(),
      facebookId: _facebookController.text.trim(),
      twitterId: _twitterController.text.trim(),
      youtubeId: _youtubeController.text.trim(),
      instagramId: _instagramController.text.trim(),
      profilePhoto: _profilePhoto,
    );
  }

  Future<void> _showPicker() async {
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: context.color.secondaryColor,
      builder: (bc) {
        return SafeArea(
          child: Wrap(
            children: <Widget>[
              ListTile(
                leading: const Icon(Icons.photo_library),
                title: CustomText('gallery'.translate(context)),
                onTap: () async {
                  await _pickImage(ImageSource.gallery);
                  Navigator.of(context).pop();
                },
              ),
              ListTile(
                leading: const Icon(Icons.photo_camera),
                title: CustomText('camera'.translate(context)),
                onTap: () async {
                  await _pickImage(ImageSource.camera);
                  Navigator.of(context).pop();
                },
              ),
              if (_profilePhoto != null)
                ListTile(
                  leading: const Icon(Icons.clear_rounded),
                  title: CustomText('lblremove'.translate(context)),
                  onTap: () {
                    _profilePhoto = null;
                    Navigator.of(context).pop();
                    setState(() {});
                  },
                ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _pickImage(ImageSource imageSource) async {
    CropImage.init(context);
    final pickedFile = await ImagePicker().pickImage(source: imageSource);
    if (pickedFile != null) {
      final croppedFile = await CropImage.crop(filePath: pickedFile.path);
      if (croppedFile == null) {
        _profilePhoto = null;
      } else {
        _profilePhoto = File(croppedFile.path);
      }
    } else {
      _profilePhoto = null;
    }
    setState(() {});
  }

  void showCountryCode() {
    showCountryPicker(
      context: context,
      showPhoneCode: true,
      countryListTheme: CountryListThemeData(
        borderRadius: BorderRadius.circular(8),
        backgroundColor: context.color.backgroundColor,
        textStyle: TextStyle(color: context.color.textColorDark),
        inputDecoration: InputDecoration(
          hintStyle: TextStyle(color: context.color.textColorDark),
          helperStyle: TextStyle(color: context.color.textColorDark),
          prefixIcon: const Icon(Icons.search),
          iconColor: context.color.tertiaryColor,
          prefixIconColor: context.color.tertiaryColor,
          focusedBorder: OutlineInputBorder(
            borderSide: BorderSide(color: context.color.tertiaryColor),
          ),
          floatingLabelStyle: TextStyle(color: context.color.tertiaryColor),
          labelText: 'search'.translate(context),
          border: const OutlineInputBorder(),
          labelStyle: TextStyle(color: context.color.textColorDark),
        ),
      ),
      onSelect: (value) {
        flagEmoji = value.flagEmoji;
        selectedCountryCode = value.phoneCode;
        setState(() {});
      },
    );
  }

  Widget _getProfileImage() {
    if (_profilePhoto != null) {
      return Image.file(_profilePhoto!, fit: BoxFit.contain);
    }
    if ((_currentProfileUrl ?? '').isNotEmpty) {
      return CustomImage(imageUrl: _currentProfileUrl!);
    }
    return CustomImage(
      imageUrl: AppIcons.defaultPersonLogo,
      color: context.color.tertiaryColor,
    );
  }

  Widget _buildProfilePicture() {
    return Stack(
      children: [
        Container(
          height: 124.rh(context),
          width: 124.rw(context),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: context.color.tertiaryColor, width: 2),
          ),
          child: Container(
            alignment: Alignment.center,
            clipBehavior: Clip.antiAlias,
            decoration: BoxDecoration(
              color: context.color.tertiaryColor.withValues(alpha: 0.2),
              shape: BoxShape.circle,
            ),
            width: 106.rw(context),
            height: 106.rh(context),
            child: _getProfileImage(),
          ),
        ),
        PositionedDirectional(
          bottom: 0,
          end: 0,
          child: GestureDetector(
            onTap: _showPicker,
            child: Container(
              height: 37.rh(context),
              width: 37.rw(context),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                border: Border.all(
                  color: context.color.buttonColor,
                  width: 2,
                ),
                shape: BoxShape.circle,
                color: context.color.tertiaryColor,
              ),
              child: CustomImage(
                imageUrl: AppIcons.edit,
                height: 18.rh(context),
                width: 18.rw(context),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildTextField(
    BuildContext context, {
    required String title,
    required TextEditingController controller,
    CustomTextFieldValidator? validator,
    bool? readOnly,
    int? maxLine,
    TextInputType? keyboard,
    Widget? prefix,
    Widget? suffix,
    List<TextInputFormatter>? formatters,
    TextDirection? textDirection,
    dynamic Function(dynamic value)? onChange,
    int? maxLength,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(height: 8.rh(context)),
        CustomText(
          title.translate(context),
          fontSize: context.font.sm,
          fontWeight: FontWeight.w600,
        ),
        SizedBox(height: 8.rh(context)),
        CustomTextFormField(
          maxLength: maxLength,
          textDirection: textDirection,
          controller: controller,
          isReadOnly: readOnly,
          validator: validator,
          maxLine: maxLine,
          keyboard: keyboard,
          fillColor: context.color.secondaryColor,
          onChange: onChange,
          prefix: prefix,
          suffix: suffix,
          formaters: formatters,
        ),
      ],
    );
  }

  Widget _buildShimmer() {
    return ListView.separated(
      itemBuilder: (context, index) => index == 0
          ? Shimmer.fromColors(
              period: const Duration(milliseconds: 1000),
              baseColor: Theme.of(context).colorScheme.shimmerBaseColor,
              highlightColor: Theme.of(
                context,
              ).colorScheme.shimmerHighlightColor,
              child: Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.shimmerContentColor,
                  shape: BoxShape.circle,
                ),
              ),
            )
          : CustomShimmer(height: 50.rh(context)),
      separatorBuilder: (context, index) =>
          SizedBox(height: index == 0 ? 24 : 16),
      itemCount: 10,
    );
  }

  @override
  Widget build(BuildContext context) {
    final isEmailLogin =
        HiveUtils.getUserLoginType() == LoginType.google ||
        HiveUtils.getUserLoginType() == LoginType.apple ||
        HiveUtils.getUserLoginType() == LoginType.email;
    final isPhoneLogin = HiveUtils.getUserLoginType() == LoginType.phone;
    return GestureDetector(
      onTap: () => FocusScope.of(context).unfocus(),
      child: Scaffold(
        backgroundColor: context.color.primaryColor,
        appBar: CustomAppBar(title: 'editProfile'.translate(context)),
        body: Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
          child: BlocConsumer<AgentProfileCubit, AgentProfileState>(
            listener: (context, state) {
              if (state is AgentProfileSuccess) {
                _populateFields(state.agentProfile);
              }
            },
            builder: (context, state) {
              if (state is AgentProfileInProgress) return _buildShimmer();
              if (state is AgentProfileFailure) {
                return SomethingWentWrong(
                  errorMessage: state.errorMessage.toString(),
                );
              }
              return BlocConsumer<
                UpdateAgentProfileCubit,
                UpdateAgentProfileState
              >(
                listener: (context, state) {
                  if (state is UpdateAgentProfileSuccess) {
                    HelperUtils.showSnackBarMessage(context, 'profileupdated');
                    unawaited(
                      context.read<AgentProfileCubit>().fetchAgentProfile(),
                    );
                    Navigator.pop(context);
                  } else if (state is UpdateAgentProfileFailure) {
                    HelperUtils.showSnackBarMessage(
                      context,
                      state.errorMessage.toString(),
                    );
                  }
                },
                builder: (context, updateState) {
                  return SingleChildScrollView(
                    physics: Constant.scrollPhysics,
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Align(child: _buildProfilePicture()),
                          _buildTextField(
                            context,
                            title: 'fullName',
                            controller: _nameController,
                            validator: CustomTextFieldValidator.nullCheck,
                          ),
                          _buildTextField(
                            context,
                            title: 'email',
                            controller: _emailController,
                            readOnly: isEmailLogin,
                            validator: CustomTextFieldValidator.email,
                          ),
                          _buildTextField(
                            context,
                            textDirection: Directionality.of(context),
                            title: 'mobile',
                            readOnly: isPhoneLogin,
                            keyboard: TextInputType.phone,
                            maxLength: HelperUtils.getMaxPhoneLength(
                              selectedCountryCode!,
                            ),
                            onChange: (value) {
                              setState(() {
                                _mobileController.text =
                                    HelperUtils.formatPhoneNumber(
                                      _mobileController.text,
                                      selectedCountryCode!,
                                    );
                              });
                            },
                            prefix: CountryPickerWidget(
                              flagEmoji: flagEmoji,
                              onTap: showCountryCode,
                              countryCode: selectedCountryCode,
                            ),
                            controller: _mobileController,
                            formatters: [
                              FilteringTextInputFormatter.digitsOnly,
                            ],
                            validator: Constant.isDemoModeOn
                                ? CustomTextFieldValidator.nullCheck
                                : CustomTextFieldValidator.phoneNumber,
                          ),
                          _buildTextField(
                            context,
                            title: 'addressLbl',
                            controller: _addressController,
                            validator: CustomTextFieldValidator.nullCheck,
                          ),
                          _buildTextField(
                            context,
                            title: 'aboutMe',
                            controller: _aboutMeController,
                            validator: CustomTextFieldValidator.nullCheck,
                            maxLine: 4,
                            keyboard: TextInputType.multiline,
                          ),
                          const SizedBox(height: 10),
                          CustomText(
                            'enablesNewSection'.translate(context),
                            fontWeight: FontWeight.w300,
                            fontSize: context.font.xs,
                            color: context.color.textColorDark.withValues(
                              alpha: 0.8,
                            ),
                          ),
                          _buildTextField(
                            context,
                            title: 'instagram',
                            controller: _instagramController,
                            validator: CustomTextFieldValidator.link,
                          ),
                          _buildTextField(
                            context,
                            title: 'facebook',
                            controller: _facebookController,
                            validator: CustomTextFieldValidator.link,
                          ),
                          _buildTextField(
                            context,
                            title: 'youtube',
                            controller: _youtubeController,
                            validator: CustomTextFieldValidator.link,
                          ),
                          _buildTextField(
                            context,
                            title: 'twitter',
                            controller: _twitterController,
                            validator: CustomTextFieldValidator.link,
                          ),
                          SizedBox(height: 45.rh(context)),
                          UiUtils.buildButton(
                            context,
                            outerPadding: const EdgeInsets.only(bottom: 16),
                            onPressed: () => unawaited(_submit()),
                            isInProgress:
                                updateState is UpdateAgentProfileInProgress,
                            height: 48.rh(context),
                            buttonTitle: 'updateProfile'.translate(context),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              );
            },
          ),
        ),
      ),
    );
  }
}
