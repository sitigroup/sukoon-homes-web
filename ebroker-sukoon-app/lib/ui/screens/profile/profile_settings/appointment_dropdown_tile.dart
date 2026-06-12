import 'package:ebroker/app/routes.dart';
import 'package:ebroker/utils/active_role_manager.dart';
import 'package:ebroker/utils/app_icons.dart';
import 'package:ebroker/utils/custom_image.dart';
import 'package:ebroker/utils/custom_text.dart';
import 'package:ebroker/utils/extensions/extensions.dart';
import 'package:ebroker/utils/guest_checker.dart';
import 'package:ebroker/utils/responsive_size.dart';
import 'package:ebroker/utils/ui_utils.dart';
import 'package:flutter/material.dart';

class AppointmentDropdownTile extends StatefulWidget {
  const AppointmentDropdownTile({super.key});

  @override
  State<AppointmentDropdownTile> createState() =>
      AppointmentDropdownTileState();
}

class AppointmentDropdownTileState extends State<AppointmentDropdownTile> {
  bool isExpanded = false;

  @override
  Widget build(BuildContext context) {
    if (RoleScope.of(context) == ActiveRole.agent) {
      return _buildAgentExpansionTile(context);
    }
    return _buildUserSimpleTile(context);
  }

  Widget _buildAgentExpansionTile(BuildContext context) {
    return Column(
      children: [
        _buildTileItem(
          context: context,
          title: 'myAppointments'.translate(context),
          svgImagePath: AppIcons.appointment,
          onTap: () async {
            await Navigator.pushNamed(context, Routes.myAppointmentsScreen);
          },
        ),
        SizedBox(height: 16.rh(context)),
        UiUtils.getDivider(context),
        SizedBox(height: 16.rh(context)),
        _buildTileItem(
          context: context,
          title: 'configurations'.translate(context),
          svgImagePath: AppIcons.configuration,
          onTap: () async {
            await Navigator.pushNamed(context, Routes.appointmentConfiguration);
          },
        ),
      ],
    );
  }

  Widget _buildUserSimpleTile(BuildContext context) {
    return GestureDetector(
      onTap: () async {
        await GuestChecker.check(
          onNotGuest: () async {
            await Navigator.pushNamed(context, Routes.myAppointmentsScreen);
          },
        );
      },
      child: _buildTileHeader(context),
    );
  }

  Widget _buildTileHeader(BuildContext context) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: context.color.textColorDark.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(4),
          ),
          child: FittedBox(
            fit: BoxFit.none,
            child: CustomImage(
              imageUrl: AppIcons.appointment,
              height: 24.rh(context),
              width: 24.rw(context),
              color: context.color.textColorDark,
            ),
          ),
        ),
        SizedBox(width: 8.rw(context)),
        Expanded(
          child: CustomText(
            'myAppointments'.translate(context),
            fontSize: context.font.md,
            fontWeight: FontWeight.w700,
            color: context.color.textColorDark,
          ),
        ),
      ],
    );
  }

  Widget _buildTileItem({
    required BuildContext context,
    required String title,
    required String svgImagePath,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: context.color.textColorDark.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(4),
            ),
            child: FittedBox(
              fit: BoxFit.none,
              child: CustomImage(
                imageUrl: svgImagePath,
                height: 24.rh(context),
                width: 24.rw(context),
                color: context.color.textColorDark,
              ),
            ),
          ),
          SizedBox(width: 8.rw(context)),
          Expanded(
            child: CustomText(
              title,
              fontSize: context.font.md,
              fontWeight: FontWeight.w700,
              color: context.color.textColorDark,
            ),
          ),
        ],
      ),
    );
  }
}
