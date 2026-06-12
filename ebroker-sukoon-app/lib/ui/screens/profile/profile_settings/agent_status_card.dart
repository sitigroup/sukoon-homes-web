import 'package:ebroker/exports/main_export.dart';

class AgentStatusCard extends StatelessWidget {
  const AgentStatusCard({super.key});

  @override
  Widget build(BuildContext context) {
    context.watch<UserDetailsCubit>();
    final isAgent = RoleScope.of(context) == ActiveRole.agent;
    final becomeAgentStatus =
        HiveUtils.getUserDetails().becomeAgentStatus ?? '';
    return _buildCard(context, isAgent, becomeAgentStatus);
  }

  Widget _buildCard(
    BuildContext context,
    bool isAgent,
    String becomeAgentStatus,
  ) {
    if (becomeAgentStatus == 'pending') {
      return Container(
        padding: const EdgeInsets.all(16),
        width: MediaQuery.sizeOf(context).width,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: AlignmentDirectional.centerStart,
            end: AlignmentDirectional.centerEnd,
            colors: [
              context.color.tertiaryColor.withValues(alpha: 0.05),
              context.color.primaryColor.withValues(alpha: 0.05),
            ],
          ),
          border: Border.all(
            color: context.color.tertiaryColor.withValues(alpha: .1),
            width: 1.5,
          ),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CustomText(
              'agentProfileUnderReview'.translate(context),
              fontSize: context.font.md,
              fontWeight: FontWeight.w700,
            ),
            const SizedBox(height: 4),
            CustomText(
              'agentReviewMessage'.translate(context),
              fontSize: context.font.sm,
              color: context.color.tertiaryColor,
              maxLines: 3,
            ),
          ],
        ),
      );
    }

    if (becomeAgentStatus == 'rejected') {
      final rejectReason = HiveUtils.getUserDetails().becomeAgentRejectReason;
      return Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: context.color.secondaryColor,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: context.color.borderColor),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CustomText(
                  'agentRequestRejected'.translate(context),
                  fontSize: context.font.md,
                  fontWeight: FontWeight.w700,
                  color: context.color.error,
                ),
                if (rejectReason != null && rejectReason.isNotEmpty) ...[
                  SizedBox(width: 8.rw(context)),
                  GestureDetector(
                    onTap: () async {
                      await UiUtils.showBlurredDialoge(
                        context,
                        dialog: BlurredDialogBox(
                          acceptTextColor: context.color.buttonColor,
                          showCancleButton: false,
                          title: 'agentRequestRejected'.translate(
                            context,
                          ),
                          content: CustomText(rejectReason),
                        ),
                      );
                    },
                    child: CustomImage(
                      imageUrl: AppIcons.info,
                      width: 18,
                      height: 18,
                      color: context.color.error,
                    ),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 4),
            CustomText(
              'agentRejectedMessage'.translate(context),
              fontSize: context.font.sm,
              color: context.color.textLightColor,
              maxLines: 3,
            ),
            const SizedBox(height: 12),
            UiUtils.buildButton(
              context,
              buttonTitle: 'reApply'.translate(context),
              autoWidth: true,
              fontSize: 14.rf(context),
              height: 32.rh(context),
              onPressed: () {
                unawaited(
                  Navigator.pushNamed(
                    context,
                    Routes.becomeAgentForm,
                    arguments: {'form_type': 'become_agent'},
                  ),
                );
              },
            ),
          ],
        ),
      );
    }

    if (becomeAgentStatus == 'not_applied' || becomeAgentStatus == '') {
      return Container(
        padding: EdgeInsets.symmetric(
          horizontal: 16.rw(context),
          vertical: 12.rh(context),
        ),
        decoration: BoxDecoration(
          color: context.color.secondaryColor,
          borderRadius: BorderRadius.circular(10),
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
                    'becomeAgentForFree'.translate(context),
                    fontSize: context.font.md,
                    fontWeight: FontWeight.w500,
                  ),
                  const SizedBox(height: 4),
                  CustomText(
                    'becomeAgentSubtitle'.translate(context),
                    fontSize: context.font.xs,
                    color: context.color.textLightColor,
                    maxLines: 3,
                  ),
                  UiUtils.buildButton(
                    context,
                    buttonTitle: 'getStarted'.translate(context),
                    fontSize: 14.rf(context),
                    outerPadding: EdgeInsets.zero,
                    padding: EdgeInsets.symmetric(
                      vertical: 6.rh(context),
                      horizontal: 12.rw(context),
                    ),
                    onPressed: () {
                      unawaited(
                        Navigator.pushNamed(
                          context,
                          Routes.becomeAgentScreen,
                        ),
                      );
                    },
                    height: 20.rh(context),
                    autoWidth: true,
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            CustomImage(imageUrl: AppIcons.becomeAgentIcon),
          ],
        ),
      );
    }

    if (becomeAgentStatus == 'approved') {
      return Container(
        padding: const EdgeInsets.all(16),
        width: MediaQuery.sizeOf(context).width,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: AlignmentDirectional.centerStart,
            end: AlignmentDirectional.centerEnd,
            colors: [
              context.color.tertiaryColor.withValues(alpha: 0.05),
              context.color.primaryColor.withValues(alpha: 0.05),
            ],
          ),
          border: Border.all(
            color: context.color.tertiaryColor.withValues(alpha: .1),
            width: 1.5,
          ),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CustomText(
                    isAgent
                        ? 'switchBackToUser'.translate(context)
                        : 'switchToAgent'.translate(context),
                    fontSize: context.font.md,
                    fontWeight: FontWeight.w700,
                  ),
                  const SizedBox(height: 4),
                  CustomText(
                    isAgent
                        ? 'switchBackToUser'.translate(context)
                        : 'exploreAgentDashboard'.translate(context),
                    fontSize: context.font.sm,
                    color: context.color.tertiaryColor,
                    maxLines: 2,
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            UiUtils.buildButton(
              context,
              buttonTitle: 'switchNow'.translate(context),
              fontSize: 14,
              onPressed: () {
                if (isAgent) {
                  unawaited(ActiveRoleManager.switchToUser(context));
                } else {
                  unawaited(ActiveRoleManager.switchToAgent(context));
                }
              },
              height: 22.rh(context),
              padding: EdgeInsets.symmetric(
                horizontal: 12.rw(context),
                vertical: 6.rh(context),
              ),
              autoWidth: true,
            ),
          ],
        ),
      );
    }

    return const SizedBox.shrink();
  }
}
