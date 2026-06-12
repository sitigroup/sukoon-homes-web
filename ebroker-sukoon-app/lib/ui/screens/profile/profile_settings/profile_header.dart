import 'package:ebroker/data/cubits/agents/agent_profile_cubit.dart';
import 'package:ebroker/exports/main_export.dart';

class ProfileHeader extends StatelessWidget {
  const ProfileHeader({required this.isGuest, super.key});

  final bool isGuest;

  @override
  Widget build(BuildContext context) {
    final user = context.watch<UserDetailsCubit>().state.user;
    final role = RoleScope.of(context);
    final isAgent = role == ActiveRole.agent;
    final agentState = context.watch<AgentProfileCubit>().state;
    final agentProfile = agentState is AgentProfileSuccess
        ? agentState.agentProfile
        : null;

    final displayName = isAgent
        ? (agentProfile?.agentName?.isEmpty == false
              ? agentProfile?.agentName
              : null)
        : user?.name;
    final displayEmail = isAgent
        ? (agentProfile?.agentEmail?.isEmpty == false
              ? agentProfile?.agentEmail
              : null)
        : user?.email;
    final displayProfile = isAgent
        ? agentProfile?.agentProfilePhoto
        : user?.profile;

    final username = isGuest
        ? 'anonymous'.translate(context)
        : displayName?.firstUpperCase() ?? '';
    final email = isGuest
        ? 'notLoggedIn'.translate(context)
        : displayEmail ?? '';

    return Container(
      padding: .all(12.rw(context)),
      margin: .symmetric(horizontal: 16.rw(context)),
      decoration: BoxDecoration(
        border: Border.all(color: context.color.borderColor, width: 1.5),
        color: context.color.secondaryColor,
        borderRadius: BorderRadius.circular(12.rw(context)),
      ),
      child: Row(
        spacing: 12.rw(context),
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: _profileImgWidget(
              context,
              (displayProfile ?? '').trim(),
            ),
          ),
          Expanded(
            child: Column(
              mainAxisAlignment: .center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    CustomText(
                      username,
                      color: context.color.inverseSurface,
                      fontSize: context.font.md,
                      fontWeight: FontWeight.w700,
                      maxLines: 2,
                    ),
                    if (!isGuest) ...[
                      if (isAgent && HiveUtils.isAgentVerified()) ...[
                        SizedBox(width: 8.rw(context)),
                        CustomImage(
                          imageUrl: AppIcons.agentBadge,
                          height: 16.rh(context),
                          color: context.color.tertiaryColor,
                        ),
                      ] else if (!isAgent && HiveUtils.isUserVerified()) ...[
                        SizedBox(width: 8.rw(context)),
                        CustomImage(
                          imageUrl: AppIcons.userBadge,
                          height: 16.rh(context),
                          color: context.color.tertiaryColor,
                        ),
                      ],
                    ],
                  ],
                ),
                CustomText(
                  email,
                  color: context.color.textColorDark,
                  fontSize: context.font.xs,
                  maxLines: 1,
                ),
              ],
            ),
          ),
          if (isGuest)
            Container(
              child: UiUtils.buildButton(
                context,
                height: 32.rh(context),
                fontSize: context.font.xs,
                showElevation: false,
                buttonTitle: 'login'.translate(context),
                buttonColor: context.color.secondaryColor,
                textColor: context.color.textLightColor,
                autoWidth: true,
                border: BorderSide(color: context.color.borderColor),
                onPressed: () async {
                  await Navigator.pushReplacementNamed(
                    context,
                    Routes.login,
                  );
                },
              ),
            ),
        ],
      ),
    );
  }

  Widget _profileImgWidget(BuildContext context, String profileUrl) {
    return GestureDetector(
      onTap: () async {
        await UiUtils.showFullScreenImage(
          context,
          provider: NetworkImage(profileUrl),
        );
      },
      child: profileUrl.isEmpty
          ? _buildDefaultPersonSVG(context)
          : CustomImage(
              imageUrl: profileUrl,
              width: 80.rw(context),
              height: 80.rh(context),
            ),
    );
  }

  Widget _buildDefaultPersonSVG(BuildContext context) {
    return Container(
      width: 80.rw(context),
      height: 80.rh(context),
      color: context.color.tertiaryColor.withValues(alpha: 0.1),
      child: FittedBox(
        fit: BoxFit.none,
        child: CustomImage(
          imageUrl: AppIcons.defaultPersonLogo,
          color: context.color.tertiaryColor,
          width: 32.rw(context),
          height: 32.rh(context),
        ),
      ),
    );
  }
}
