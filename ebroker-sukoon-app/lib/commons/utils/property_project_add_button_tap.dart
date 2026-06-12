import 'package:ebroker/exports/main_export.dart';

Future<void> handleAddPropertyOrProjectTap(
  BuildContext context,
  PropertyAddType type,
) async {
  final hasInternet = await HelperUtils.checkInternet();
  final user = HiveUtils.getUserDetails();
  final isAgent = ActiveRoleManager.isAgent;
  final verificationStatus = isAgent
      ? user.agentVerificationStatus
      : user.userVerificationStatus;
  if (!hasInternet) {
    return HelperUtils.showSnackBarMessage(
      context,
      'noInternet',
      type: MessageType.error,
    );
  }
  await GuestChecker.check(
    onNotGuest: () async {
      unawaited(Widgets.showLoader(context));

      try {
        final isProfileCompleted =
            user.email != '' &&
            user.email != null &&
            (user.email?.isNotEmpty ?? false) &&
            user.mobile != '' &&
            user.mobile != null &&
            (user.mobile?.isNotEmpty ?? false) &&
            user.name != '' &&
            user.name != null &&
            (user.name?.isNotEmpty ?? false) &&
            user.address != '' &&
            (user.address?.isNotEmpty ?? false) &&
            user.address != null &&
            user.profile != '' &&
            (user.profile?.isNotEmpty ?? false) &&
            user.profile != null;

        if (!isProfileCompleted) {
          Widgets.hideLoder(context);
          await _showCompleteProfileDialog(context);
        } else if ((ActiveRoleManager.isAgent
                ? AppSettings.isVerificationRequiredForAgent
                : AppSettings.isVerificationRequiredForUser) &&
            verificationStatus != 'approved') {
          Widgets.hideLoder(context);
          await _showVerificationRequiredDialog(context);
        } else {
          Widgets.hideLoder(context);
          await _navigateToAddScreen(context, type);
        }
      } on Exception catch (e) {
        Widgets.hideLoder(context);
        HelperUtils.showSnackBarMessage(
          context,
          e.toString(),
          type: MessageType.error,
        );
      }
    },
  );
}

Future<void> _showCompleteProfileDialog(BuildContext context) async {
  final user = HiveUtils.getUserDetails();
  await UiUtils.showBlurredDialoge(
    context,
    dialog: BlurredDialogBox(
      title: 'completeProfile'.translate(context),
      isAcceptContainesPush: true,
      svgImagePath: AppIcons.logoutIllustration,
      onAccept: () async {
        await Navigator.popAndPushNamed(
          context,
          Routes.editProfile,
          arguments: {
            'from': 'home',
            'navigateToHome': true,
          },
        );
      },
      content:
          user.profile == '' &&
              (user.name != '' && user.email != '' && user.address != '')
          ? CustomText(
              'uploadProfilePicture'.translate(context),
              textAlign: .center,
            )
          : CustomText(
              'completeProfileFirst'.translate(context),
              textAlign: .center,
            ),
    ),
  );
}

Future<void> _showVerificationRequiredDialog(BuildContext context) async {
  await UiUtils.showBlurredDialoge(
    context,
    dialog: BlurredDialogBox(
      content: CustomText(
        'completeAgentVerificationToContinue'.translate(context),
      ),
      title: 'agentVerificationRequired'.translate(context),
      isAcceptContainesPush: true,
      onAccept: () async {
        await HelperUtils.goToNextPage(
          Routes.userVerificationForm,
          context,
          false,
        );
      },
    ),
  );
}

Future<void> _navigateToAddScreen(
  BuildContext context,
  PropertyAddType propertyAddType,
) async {
  if (propertyAddType == .project) {
    await context.read<ManageProjectCubit>().clear();
  }
  if (propertyAddType == .property) {
    await context.read<CreatePropertyCubit>().clear();
  }
  if (context.read<FetchCategoryCubit>().state is! FetchCategorySuccess) {
    await context.read<FetchCategoryCubit>().fetchCategories(
      loadWithoutDelay: true,
      forceRefresh: false,
    );
  }
  Widgets.hideLoder(context);

  await Navigator.pushNamed(
    context,
    Routes.selectPropertyTypeScreen,
    arguments: {'type': propertyAddType},
  );

  Widgets.hideLoder(context);
}
