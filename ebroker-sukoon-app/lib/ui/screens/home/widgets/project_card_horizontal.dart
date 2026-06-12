import 'dart:developer';

import 'package:ebroker/data/model/project_model.dart';
import 'package:ebroker/data/repositories/check_package.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/widgets/promoted_widget.dart';
import 'package:flutter/material.dart';

class ProjectHorizontalCard extends StatefulWidget {
  const ProjectHorizontalCard({
    required this.project,
    required this.isRejected,
    super.key,
    this.statusButton,
    this.disableTap,
    this.showFeatured,
  });

  final ProjectModel project;
  final bool isRejected;

  final StatusButton? statusButton;

  final bool? disableTap;
  final bool? showFeatured;

  @override
  State<ProjectHorizontalCard> createState() => _ProjectHorizontalCardState();
}

class _ProjectHorizontalCardState extends State<ProjectHorizontalCard> {
  bool _isNavigating = false;

  @override
  Widget build(BuildContext context) {
    final isMyProject =
        widget.project.addedBy.toString() == HiveUtils.getUserId();
    return GestureDetector(
      onTap: () async {
        // Prevent multiple taps
        if (_isNavigating) return;

        // if (disableTap ?? false) return;
        final hasInternet = await HelperUtils.checkInternet();

        if (!hasInternet) {
          return HelperUtils.showSnackBarMessage(
            context,
            'noInternet',
            type: .error,
          );
        }

        setState(() {
          _isNavigating = true;
        });

        try {
          if (!isMyProject && (widget.project.isPremium ?? false)) {
            // Check package availability for non-owner users
            final checkPackage = CheckPackage();
            final packageAvailable = await checkPackage.checkPackageAvailable(
              packageType: PackageType.projectAccess,
            );

            if (!packageAvailable) {
              await UiUtils.showBlurredDialoge(
                context,
                dialog: const BlurredSubscriptionDialogBox(
                  packageType: SubscriptionPackageType.projectAccess,
                  isAcceptContainesPush: true,
                ),
              );
              return;
            }
          }

          try {
            await HelperUtils.loadAndNavigateToProjectDetails(
              context: context,
              projectId: widget.project.id!,
              isMyProject: isMyProject,
              showLoader: true,
            );
          } on Exception catch (_) {
            // Project navigation errors are handled by the shared helper flow.
          }
        } on Exception catch (e) {
          log(e.toString());
          // Project navigation errors are handled by the shared helper flow.
        } finally {
          if (mounted) {
            setState(() {
              _isNavigating = false;
            });
          }
        }
      },
      child: Container(
        height: 130.rh(context),
        width: MediaQuery.sizeOf(context).width,
        margin: EdgeInsets.only(bottom: 12.rh(context)),
        padding: EdgeInsets.all(8.rw(context)),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: context.color.borderColor),
          color: context.color.secondaryColor,
        ),
        child: Row(
          children: [
            Stack(
              children: [
                SizedBox(
                  height: 114.rh(context),
                  width: 127.rw(context),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: CustomImage(
                      imageUrl: widget.project.image ?? '',
                      width: MediaQuery.of(context).size.width,
                      loadingImageHash: widget.project.lowQualityImage,
                    ),
                  ),
                ),
                if ((widget.project.isPromoted ?? false) ||
                    (widget.showFeatured ?? false))
                  PositionedDirectional(
                    bottom: 4.rh(context),
                    start: 4.rw(context),
                    child: const PromotedCard(),
                  ),
                if (widget.project.isPremium ?? false)
                  PositionedDirectional(
                    start: 4.rh(context),
                    top: 4.rh(context),
                    child: CustomImage(
                      imageUrl: AppIcons.premium,
                      width: 18.rw(context),
                      height: 18.rh(context),
                    ),
                  ),
                if ((widget.project.isPromoted ?? false) ||
                    (widget.showFeatured ?? false))
                  PositionedDirectional(
                    bottom: 4.rh(context),
                    start: 4.rw(context),
                    child: const PromotedCard(),
                  ),
              ],
            ),
            SizedBox(width: 12.rw(context)),
            Expanded(
              child: Column(
                crossAxisAlignment: .start,
                children: <Widget>[
                  Row(
                    children: [
                      CustomImage(
                        imageUrl: widget.project.category?.image ?? '',
                        color: context.color.textLightColor,
                        width: 18.rw(context),
                        height: 18.rh(context),
                      ),
                      const SizedBox(width: 4),
                      Expanded(
                        child: CustomText(
                          widget.project.category?.translatedName ??
                              widget.project.category?.category ??
                              '',
                          fontWeight: .w600,
                          fontSize: context.font.xxs,
                          color: context.color.textLightColor,
                          maxLines: 1,
                        ),
                      ),
                      if (isMyProject &&
                          widget.project.requestStatus == 'approved') ...[
                        SizedBox(width: 4.rw(context)),
                        CustomText(
                          widget.project.isExpired ?? false
                              ? 'expired'.translate(context)
                              : UiUtils.getExpiryCountdown(
                                  context,
                                  widget.project.expiryDate,
                                ),
                          maxLines: 1,
                          fontSize: context.font.xxs,
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 8),
                  CustomText(
                    widget.project.translatedTitle ??
                        widget.project.title ??
                        '',
                    maxLines: 1,
                    fontSize: context.font.sm,
                    fontWeight: .w400,
                    color: context.color.textColorDark,
                  ),
                  const SizedBox(height: 4),
                  CustomText(
                    '${widget.project.city}, ${widget.project.state}, ${widget.project.country}',
                    maxLines: 1,
                    fontSize: context.font.xs,
                    fontWeight: .w400,
                    color: context.color.textColorDark,
                  ),
                  const SizedBox(height: 8),
                  UiUtils.getDivider(context),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: CustomText(
                          widget.project.type?.toLowerCase().translate(
                                context,
                              ) ??
                              '',
                          maxLines: 2,
                          fontSize: context.font.xxs,
                          fontWeight: .w600,
                          color: context.color.tertiaryColor,
                        ),
                      ),
                      if (widget.statusButton != null) ...[
                        GestureDetector(
                          onTap: () async {
                            if (widget.isRejected) {
                              await UiUtils.showBlurredDialoge(
                                context,
                                dialog: BlurredDialogBox(
                                  acceptTextColor: context.color.buttonColor,
                                  showCancleButton: false,
                                  title: widget.statusButton!.lable,
                                  content: CustomText(
                                    widget.project.rejectReason?.reason
                                            .toString() ??
                                        '',
                                  ),
                                ),
                              );
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: widget.statusButton!.color,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Row(
                              children: [
                                CustomText(
                                  widget.statusButton!.lable,
                                  fontWeight: .bold,
                                  fontSize: context.font.xxs,
                                  color:
                                      widget.statusButton?.textColor ??
                                      Colors.black,
                                ),
                                if (widget.isRejected) ...[
                                  const SizedBox(width: 2),
                                  CustomImage(
                                    imageUrl: AppIcons.info,
                                    width: 16,
                                    height: 16,
                                    color: widget.statusButton!.textColor,
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
