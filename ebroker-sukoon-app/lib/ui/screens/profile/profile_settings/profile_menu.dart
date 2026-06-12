import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/profile/profile_settings/appointment_dropdown_tile.dart';
import 'package:ebroker/ui/screens/profile/profile_settings/profile_tile.dart';
import 'package:ebroker/ui/screens/profile/profile_settings/update_tile.dart';
import 'package:url_launcher/url_launcher.dart';

enum _Visibility { all, notAgent, notUser }

class _Item {
  const _Item({
    required this.titleKey,
    required this.icon,
    required this.onTap,
    this.visibility = _Visibility.all,
    this.isSwitchBox = false,
    this.titleOverride,
  });

  final String titleKey;
  final String? titleOverride;
  final String icon;
  final VoidCallback onTap;
  final _Visibility visibility;
  final bool isSwitchBox;

  bool isVisibleFor(ActiveRole role) => switch (visibility) {
    _Visibility.all => true,
    _Visibility.notAgent => role != ActiveRole.agent,
    _Visibility.notUser => role != ActiveRole.user,
  };
}

class ProfileMenu extends StatelessWidget {
  const ProfileMenu({
    required this.isGuest,
    required this.onShareApp,
    required this.onRateUs,
    required this.onDeleteAccount,
    super.key,
  });

  final bool isGuest;
  final VoidCallback onShareApp;
  final VoidCallback onRateUs;
  final VoidCallback onDeleteAccount;

  List<_Item> _buildItems(BuildContext context) => [
    if (!isGuest)
      _Item(
        titleKey: 'editProfile',
        icon: AppIcons.profile,
        onTap: () async {
          final route = ActiveRoleManager.isAgent
              ? Routes.editAgentProfile
              : Routes.editProfile;
          await HelperUtils.goToNextPage(
            route,
            context,
            false,
            args: {'from': 'profile'},
          );
        },
      ),
    _Item(
      titleKey: 'myAds',
      icon: AppIcons.promoted,
      onTap: () async {
        await GuestChecker.check(
          onNotGuest: () async {
            await Navigator.pushNamed(context, Routes.myAdvertisment);
          },
        );
      },
    ),
    _Item(
      titleKey: 'subscription',
      icon: AppIcons.subscription,
      onTap: () async {
        await GuestChecker.check(
          onNotGuest: () async {
            await Navigator.pushNamed(
              context,
              Routes.subscriptionPackageListRoute,
            );
          },
        );
      },
    ),
    _Item(
      titleKey: 'transactionHistory',
      icon: AppIcons.transaction,
      onTap: () async {
        await GuestChecker.check(
          onNotGuest: () async {
            await Navigator.pushNamed(context, Routes.transactionHistory);
          },
        );
      },
    ),
    _Item(
      titleKey: 'trustVerification',
      titleOverride: 'Sukoon Trust & Verification',
      icon: AppIcons.shieldTick,
      onTap: () async {
        await GuestChecker.check(
          onNotGuest: () async {
            await Navigator.pushNamed(context, Routes.trustVerificationHub);
          },
        );
      },
    ),
    _Item(
      titleKey: 'personalized',
      icon: AppIcons.magic,
      visibility: _Visibility.notAgent,
      onTap: () async {
        await GuestChecker.check(
          onNotGuest: () async {
            await Navigator.pushNamed(
              context,
              Routes.personalizedPropertyScreen,
              arguments: {'type': PersonalizedVisitType.normal},
            );
          },
        );
      },
    ),
    _Item(
      titleKey: 'faqScreen',
      visibility: _Visibility.notAgent,
      icon: AppIcons.faqs,
      onTap: () async {
        await Navigator.pushNamed(context, Routes.faqsScreen);
      },
    ),
    _Item(
      titleKey: 'language',
      icon: AppIcons.language,
      onTap: () async {
        await Navigator.pushNamed(context, Routes.languageListScreenRoute);
      },
    ),
    _Item(
      titleKey: 'darkTheme',
      icon: AppIcons.darkTheme,
      isSwitchBox: true,
      onTap: () {},
    ),
    _Item(
      titleKey: 'notifications',
      icon: AppIcons.notification,
      visibility: _Visibility.notAgent,
      onTap: () async {
        await GuestChecker.check(
          onNotGuest: () async {
            await Navigator.pushNamed(context, Routes.notificationPage);
          },
        );
      },
    ),
    _Item(
      titleKey: 'articles',
      icon: AppIcons.articles,
      visibility: _Visibility.notAgent,
      onTap: () async {
        await Navigator.pushNamed(context, Routes.articlesScreenRoute);
      },
    ),
    _Item(
      titleKey: 'favorites',
      icon: AppIcons.heartFilled,
      visibility: _Visibility.notAgent,
      onTap: () async {
        await GuestChecker.check(
          onNotGuest: () async {
            await Navigator.pushNamed(context, Routes.favoritesScreen);
          },
        );
      },
    ),
    _Item(
      titleKey: 'areaConvertor',
      visibility: _Visibility.notAgent,
      icon: AppIcons.areaConvertor,
      onTap: () async {
        await Navigator.pushNamed(context, Routes.areaConvertorScreen);
      },
    ),
    _Item(
      titleKey: 'shareApp',
      icon: AppIcons.shareApp,
      onTap: onShareApp,
    ),
    _Item(
      titleKey: 'rateUs',
      icon: AppIcons.rateUs,
      onTap: onRateUs,
    ),
    _Item(
      titleKey: 'contactUs',
      icon: AppIcons.contactUs,
      onTap: () async {
        await Navigator.pushNamed(context, Routes.contactUs);
      },
    ),
    _Item(
      titleKey: 'aboutUs',
      icon: AppIcons.aboutUs,
      onTap: () async {
        await Navigator.pushNamed(
          context,
          Routes.profileSettings,
          arguments: {
            'title': 'aboutUs'.translate(context),
            'param': Api.aboutApp,
          },
        );
      },
    ),
    _Item(
      titleKey: 'termsConditions',
      icon: AppIcons.terms,
      onTap: () async {
        await Navigator.pushNamed(
          context,
          Routes.profileSettings,
          arguments: {
            'title': 'termsConditions'.translate(context),
            'param': Api.termsAndConditions,
          },
        );
      },
    ),
    _Item(
      titleKey: 'privacyPolicy',
      icon: AppIcons.privacy,
      onTap: () async {
        await Navigator.pushNamed(
          context,
          Routes.profileSettings,
          arguments: {
            'title': 'privacyPolicy'.translate(context),
            'param': Api.privacyPolicy,
          },
        );
      },
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        border: Border.all(color: context.color.borderColor),
        color: context.color.secondaryColor,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Builder(
        builder: (context) {
          final role = RoleScope.of(context);
          final items = _buildItems(
            context,
          ).where((item) => item.isVisibleFor(role)).toList();

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              for (var i = 0; i < items.length; i++) ...[
                if (i > 0) _divider(context),
                ProfileTile(
                  title: items[i].titleOverride ??
                      items[i].titleKey.translate(context),
                  svgImagePath: items[i].icon,
                  isSwitchBox: items[i].isSwitchBox,
                  onTap: items[i].onTap,
                ),
                // Appointments injected after myAds for non-user roles
                if (items[i].titleKey == 'myAds') ...[
                  _divider(context),
                  const AppointmentDropdownTile(),
                ],
              ],
              // Custom pages from API
              BlocBuilder<FetchCustomPagesCubit, FetchCustomPagesState>(
                builder: (context, state) {
                  if (state is! FetchCustomPagesSuccess ||
                      state.pages.isEmpty) {
                    return const SizedBox.shrink();
                  }
                  return Column(
                    children: state.pages.map((page) {
                      return Column(
                        children: [
                          _divider(context),
                          ProfileTile(
                            title: page.displayTitle,
                            svgImagePath: page.icon ?? AppIcons.articles,
                            onTap: () async {
                              await Navigator.pushNamed(
                                context,
                                Routes.profileSettings,
                                arguments: {
                                  'title': page.displayTitle,
                                  'content': page.displayContent,
                                },
                              );
                            },
                          ),
                        ],
                      );
                    }).toList(),
                  );
                },
              ),
              if (Constant.isUpdateAvailable) ...[
                _divider(context),
                UpdateTile(
                  title: 'update'.translate(context),
                  newVersion: Constant.newVersionNumber,
                  isUpdateAvailable: Constant.isUpdateAvailable,
                  svgImagePath: AppIcons.update,
                  onTap: () async {
                    if (Platform.isIOS) {
                      await launchUrl(Uri.parse(Constant.appstoreURLios));
                    } else if (Platform.isAndroid) {
                      await launchUrl(Uri.parse(Constant.playstoreURLAndroid));
                    }
                  },
                ),
              ],
              if (!isGuest && role != ActiveRole.agent) ...[
                _divider(context),
                ProfileTile(
                  title: 'deleteAccount'.translate(context),
                  svgImagePath: AppIcons.delete,
                  onTap: () {
                    if (Constant.isDemoModeOn &&
                        (HiveUtils.getUserDetails().isDemoUser ?? false)) {
                      HelperUtils.showSnackBarMessage(
                        context,
                        'thisActionNotValidDemo',
                      );
                      return;
                    }
                    onDeleteAccount();
                  },
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}

Widget _divider(BuildContext context) => Padding(
  padding: const EdgeInsets.symmetric(vertical: 12),
  child: Container(height: 1, color: context.color.borderColor),
);
