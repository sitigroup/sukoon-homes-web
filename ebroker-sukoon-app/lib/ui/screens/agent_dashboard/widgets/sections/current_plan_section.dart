import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/agent_dashboard/cubits/agent_dashboard_active_packages_cubit.dart';
import 'package:ebroker/ui/screens/agent_dashboard/models/agent_dashboard_active_packages_model.dart';
import 'package:ebroker/ui/screens/agent_dashboard/widgets/common/dashboard_card.dart';
import 'package:ebroker/ui/screens/agent_dashboard/widgets/common/progress_bar_row.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class CurrentPlanSection extends StatelessWidget {
  const CurrentPlanSection({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<
      AgentDashboardActivePackagesCubit,
      AgentDashboardActivePackagesState
    >(
      builder: (context, state) {
        if (state is AgentDashboardActivePackagesInitial ||
            state is AgentDashboardActivePackagesLoading) {
          return Padding(
            padding: EdgeInsets.symmetric(horizontal: 16.rw(context)),
            child: CustomShimmer(
              height: 200.rh(context),
              borderRadius: 16,
            ),
          );
        }
        if (state is AgentDashboardActivePackagesFailure) {
          return _wrapper(
            context,
            CustomText(
              state.errorMessage.toString(),
              fontSize: context.font.xs,
              color: context.color.textLightColor,
            ),
          );
        }
        if (state is AgentDashboardActivePackagesSuccess) {
          if (state.packages.isEmpty) {
            return _checkoutPlans(context);
          }
          return _PlanCard(package: state.packages.first);
        }
        return const SizedBox.shrink();
      },
    );
  }

  Widget _wrapper(BuildContext context, Widget child) {
    return DashboardCard(child: child);
  }

  Widget _checkoutPlans(BuildContext context) {
    return DashboardCard(
      padding: EdgeInsets.all(16.rw(context)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              CustomText(
                'noMyPackagesFoundDescription'.translate(context),
                fontSize: context.font.md,
                fontWeight: FontWeight.w700,
                color: context.color.textColorDark,
              ),
              SizedBox(height: 16.rh(context)),
              _OutlineButton(
                label: 'viewPlans'.translate(context),
                onTap: () => Navigator.pushNamed(
                  context,
                  Routes.subscriptionPackageListRoute,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _PlanCard extends StatelessWidget {
  const _PlanCard({required this.package});

  final AgentDashboardActivePackageModel package;

  String _formatDate(String? raw) {
    if (raw == null || raw.isEmpty) return '—';
    try {
      final dt = DateTime.parse(raw).toLocal();
      return DateFormat('EEEE, d MMM yyyy').format(dt);
    } on Exception catch (_) {
      return raw;
    }
  }

  @override
  Widget build(BuildContext context) {
    final planName = package.translatedName.isNotEmpty
        ? package.translatedName
        : package.name;

    return DashboardCard(
      padding: EdgeInsets.all(16.rw(context)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: CustomText(
                  'currentPlan'.translate(context),
                  fontSize: context.font.md,
                  fontWeight: FontWeight.w700,
                  color: context.color.textColorDark,
                ),
              ),
              CustomText(
                planName,
                fontSize: context.font.md,
                fontWeight: FontWeight.w700,
                color: context.color.tertiaryColor,
              ),
            ],
          ),
          if (package.features.isNotEmpty) ...[
            SizedBox(height: 14.rh(context)),
            Container(
              padding: EdgeInsets.symmetric(
                horizontal: 10.rw(context),
                vertical: 5.rh(context),
              ),
              decoration: BoxDecoration(
                color: context.color.tertiaryColor.withValues(alpha: .08),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.article_outlined,
                    size: 16.rw(context),
                    color: context.color.tertiaryColor,
                  ),
                  SizedBox(width: 6.rw(context)),
                  CustomText(
                    'listing'.translate(context),
                    fontSize: context.font.sm,
                    fontWeight: FontWeight.w600,
                    color: context.color.tertiaryColor,
                  ),
                ],
              ),
            ),
            SizedBox(height: 14.rh(context)),
            Row(
              children: [
                for (int i = 0; i < package.features.length && i < 2; i++)
                  Expanded(
                    child: Padding(
                      padding: EdgeInsets.only(
                        right: i == 0 ? 16.rw(context) : 0,
                      ),
                      child: _FeatureProgress(
                        feature: package.features[i],
                      ),
                    ),
                  ),
              ],
            ),
          ],
          SizedBox(height: 14.rh(context)),
          Container(
            padding: EdgeInsets.all(12.rw(context)),
            decoration: BoxDecoration(
              color: context.color.tertiaryColor.withValues(alpha: .06),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      CustomText(
                        'startedOn'.translate(context),
                        fontSize: context.font.xs,
                        color: context.color.textLightColor,
                      ),
                      SizedBox(height: 4.rh(context)),
                      CustomText(
                        _formatDate(package.startDate),
                        fontSize: context.font.sm,
                        fontWeight: FontWeight.w600,
                        color: context.color.textColorDark,
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      CustomText(
                        'endsOn'.translate(context),
                        fontSize: context.font.xs,
                        color: context.color.textLightColor,
                      ),
                      SizedBox(height: 4.rh(context)),
                      CustomText(
                        _formatDate(package.endDate),
                        fontSize: context.font.sm,
                        fontWeight: FontWeight.w600,
                        color: context.color.textColorDark,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          SizedBox(height: 14.rh(context)),

          _OutlineButton(
            label: 'viewMore'.translate(context),
            onTap: () => Navigator.pushNamed(
              context,
              Routes.subscriptionPackageListRoute,
            ),
          ),
        ],
      ),
    );
  }
}

class _FeatureProgress extends StatelessWidget {
  const _FeatureProgress({required this.feature});

  final AgentDashboardActivePackageFeature feature;

  @override
  Widget build(BuildContext context) {
    final used = feature.usedLimit ?? 0;
    final total = feature.totalLimit ?? feature.limit ?? 0;
    final label = feature.translatedName.isNotEmpty
        ? feature.translatedName
        : feature.name;

    return ProgressBarRow(
      label: label,
      current: used,
      total: feature.isUnlimited ? (used > 0 ? used : 1) : total,
      color: context.color.tertiaryColor,
      trailing: feature.isUnlimited
          ? CustomText(
              'unlimited'.translate(context),
              fontSize: context.font.xs,
              fontWeight: FontWeight.w600,
              color: context.color.tertiaryColor,
            )
          : null,
    );
  }
}

class _OutlineButton extends StatelessWidget {
  const _OutlineButton({required this.label, required this.onTap});
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 12.rh(context)),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: context.color.secondaryColor,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: context.color.borderColor),
        ),
        child: CustomText(
          label,
          fontSize: context.font.sm,
          fontWeight: FontWeight.w600,
          color: context.color.textColorDark,
        ),
      ),
    );
  }
}
